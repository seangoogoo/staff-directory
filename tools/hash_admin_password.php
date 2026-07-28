<?php
/**
 * Admin Password Hashing Tool
 *
 * Replaces the cleartext ADMIN_PASSWORD stored in staff_dir_env/.env with an
 * ADMIN_PASSWORD_HASH produced by password_hash(). Existing installations were
 * created before the installer wrote a hash, so their .env still holds the admin
 * password in readable form; this script performs that one-off migration.
 *
 * Usage:
 * php tools/hash_admin_password.php [--dry-run] [--force]
 *
 * Options:
 * --dry-run  Show the lines that would change without writing anything
 * --force    Skip the confirmation prompt
 *
 * Guarantees:
 * - idempotent: does nothing if ADMIN_PASSWORD_HASH already holds a value
 * - a timestamped backup of .env is written before any modification
 * - the resulting hash is read back and verified; the backup is restored if it fails
 * - the cleartext password is never printed
 */

// CLI only: this tool prompts on stdin and must never be reachable over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

define('ENV_PATH', dirname(__DIR__) . '/staff_dir_env/.env');

$dry_run = in_array('--dry-run', $argv);
$force = in_array('--force', $argv);

echo "Admin Password Hashing Tool\n";
echo "===========================\n";

if (!is_readable(ENV_PATH)) {
    echo "ERROR: environment file not found or not readable: " . ENV_PATH . "\n";
    exit(1);
}

$content = file_get_contents(ENV_PATH);
if ($content === false) {
    echo "ERROR: could not read " . ENV_PATH . "\n";
    exit(1);
}

// Split on any line ending, keeping the original separator so the file is rewritten
// with the endings it already used.
$eol = strpos($content, "\r\n") !== false ? "\r\n" : "\n";
$lines = preg_split('/\r\n|\n|\r/', $content);

/**
 * Locate every active (non-commented) line declaring a key.
 *
 * @param array  $lines File lines
 * @param string $key   Environment key to find
 * @return array List of line indexes, in file order
 */
function find_env_lines($lines, $key) {
    $found = [];
    foreach ($lines as $i => $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=/', $line)) {
            $found[] = $i;
        }
    }
    return $found;
}

// Read the values through the application's own parser rather than re-deriving them.
// config/env_loader.php decides what .env actually means: it strips surrounding quotes
// and, when a key appears twice, lets the LAST occurrence win. Re-implementing either
// rule here would eventually hash a password the application never used — which locks
// the single admin account out with a hash that looks perfectly valid.
require_once dirname(__DIR__) . '/config/env_loader.php';
$env = load_env(ENV_PATH);
$hash_value = isset($env['ADMIN_PASSWORD_HASH']) ? $env['ADMIN_PASSWORD_HASH'] : '';
$plain = isset($env['ADMIN_PASSWORD']) ? $env['ADMIN_PASSWORD'] : '';

$hash_indexes = find_env_lines($lines, 'ADMIN_PASSWORD_HASH');
$plain_indexes = find_env_lines($lines, 'ADMIN_PASSWORD');

// Already migrated: nothing to do.
if ($hash_value !== '') {
    echo "ADMIN_PASSWORD_HASH is already set. Nothing to do.\n";
    if ($plain !== '') {
        echo "NOTE: an active ADMIN_PASSWORD line is still present (line "
            . implode(', ', array_map(function ($i) { return $i + 1; }, $plain_indexes)) . ").\n";
        echo "      It is no longer used for authentication; remove it by hand.\n";
    }
    exit(0);
}

// Nothing to migrate from.
if ($plain === '') {
    echo "ERROR: no ADMIN_PASSWORD found in " . ENV_PATH . ", and no ADMIN_PASSWORD_HASH either.\n";
    echo "       Generate a hash by hand and add it to the file:\n";
    echo "       php -r 'echo password_hash(\"your-password\", PASSWORD_DEFAULT), PHP_EOL;'\n";
    exit(1);
}

$hash = password_hash($plain, PASSWORD_DEFAULT);
$marker = '# ADMIN_PASSWORD replaced by ADMIN_PASSWORD_HASH on ' . date('Y-m-d H:i:s');
$hash_line = 'ADMIN_PASSWORD_HASH=' . $hash;

// Build the new content. EVERY cleartext line becomes a marker comment carrying no value
// — leaving a duplicate behind would keep a readable password in the file — and the hash
// takes the place of the first one so it stays in the admin credentials block.
$new_lines = $lines;
foreach ($plain_indexes as $i) {
    $new_lines[$i] = $marker;
}
if (!empty($hash_indexes)) {
    // An empty ADMIN_PASSWORD_HASH line already exists: fill the last one rather than
    // adding another, since that is the occurrence the parser reads.
    $new_lines[end($hash_indexes)] = $hash_line;
} else {
    $new_lines[$plain_indexes[0]] = $marker . $eol . $hash_line;
}
$new_content = implode($eol, $new_lines);

echo "\nChanges to " . ENV_PATH . ":\n";
foreach ($plain_indexes as $i) {
    echo "  - line " . ($i + 1) . ": ADMIN_PASSWORD=... removed\n";
}
echo "  + " . $marker . "\n";
echo "  + " . $hash_line . "\n";

if ($dry_run) {
    echo "\nDry run: nothing was written.\n";
    exit(0);
}

if (!$force) {
    echo "\nWARNING: this rewrites your environment file.\n";
    echo "Make sure you know the admin password: it will no longer be readable anywhere.\n";
    echo "Continue? (y/n): ";
    $handle = fopen('php://stdin', 'r');
    $answer = trim(fgets($handle));
    fclose($handle);
    if (strtolower($answer) !== 'y') {
        echo "Cancelled.\n";
        exit(0);
    }
}

// Timestamped backup first: it is the only way back if anything goes wrong.
$backup_path = ENV_PATH . '.backup_' . date('Ymd_His');
if (file_put_contents($backup_path, $content) === false) {
    echo "ERROR: could not write the backup " . $backup_path . ". Aborting.\n";
    exit(1);
}
@chmod($backup_path, 0600);
echo "\nBackup written: " . $backup_path . "\n";

if (file_put_contents(ENV_PATH, $new_content) === false) {
    echo "ERROR: could not write " . ENV_PATH . ". The file is unchanged.\n";
    echo "       Delete the backup once you are done: " . $backup_path . "\n";
    exit(1);
}

// Read the file back and verify the hash survived the write, so a corrupted value can
// never lock the admin out unnoticed. The parser is already loaded above.
$reloaded = load_env(ENV_PATH);
$stored = isset($reloaded['ADMIN_PASSWORD_HASH']) ? $reloaded['ADMIN_PASSWORD_HASH'] : '';

if ($stored === '' || !password_verify($plain, $stored)) {
    echo "ERROR: the stored hash does not verify.\n";
    if (file_put_contents(ENV_PATH, $content) === false) {
        // Worst case: the file is left rewritten with a hash nobody can log in against.
        // Say so loudly and name the file to copy back by hand.
        echo "       AND the restore failed. Copy the backup over it manually, now:\n";
        echo "       cp " . $backup_path . " " . ENV_PATH . "\n";
    } else {
        echo "       " . ENV_PATH . " was restored from the backup, admin login is unaffected.\n";
        echo "       Delete the backup once you are done: " . $backup_path . "\n";
    }
    exit(1);
}

echo "Hash written and verified. The admin password is no longer stored in readable form.\n";
echo "Log in once to confirm, then delete the backup: it still contains the cleartext password.\n";
exit(0);
