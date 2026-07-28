<?php
/**
 * 404 Handler
 *
 * Renders the "page not found" page in place, with a real 404 status.
 *
 * It used to answer 302 -> /index.php?not_found=1, which then replied 200 with a
 * copy of the home page: Google reads that as a soft 404, so every dead link
 * pointing at the site created one more indexable duplicate of the directory
 * (reported on 2026-07-28 as "Duplicate without user-selected canonical").
 * Never redirect from here: an error page must carry the error status itself.
 */

// Make sure we have access to all required constants and functions
require_once __DIR__ . '/bootstrap.php';

// Set proper 404 status code
http_response_code(404);

// Belt and braces: the 404 status already keeps this page out of the index, and
// the header also covers the legacy ?not_found=1 URLs Google indexed. Sent from
// PHP on purpose, so it does not depend on any Apache module.
header('X-Robots-Tag: noindex');

// Log the 404 if logger is available
global $logger;
if (isset($logger)) {
    $logger->info('404 Not Found', [
        'uri' => $_SERVER['REQUEST_URI'],
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
    ]);
}

// Read by header.php: error title, robots meta instead of a canonical
global $page_title, $page_noindex;
$page_title = __('page_not_found');
$page_noindex = true;

require_once __DIR__ . '/header.php';
?>

<!-- Utility classes limited to the ones already present in the generated
     assets/css/styles.css, so this page needs no Tailwind rebuild -->
<div class="not-found text-center py-8">
    <h1 class="page-title mb-4 text-gray-700 font-thin text-4xl"><?php echo __('page_not_found'); ?></h1>
    <p class="mb-8 text-gray-500"><?php echo __('page_not_found_message'); ?></p>
    <a href="<?php echo url(); ?>" class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-4 py-2 text-gray-400 shadow-sm hover:text-gray-600 hover:border-gray-300 transition-colors duration-200">
        <i class="ri-arrow-left-line"></i>
        <?php echo __('back_to_directory'); ?>
    </a>
</div>

<?php
require_once __DIR__ . '/footer.php';
