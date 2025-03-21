<?php
// Start with auth checks before any output
require_once 'auth/auth.php';

// Check if user is logged in (will redirect if not logged in)
require_login();

// Now include admin header which outputs HTML with navigation
require_once '../includes/admin_header.php';

// Default settings
$placeholder_settings = [
    'font_weight' => 'Regular',
    'bg_color' => '#cccccc',
    'text_color' => '#ffffff',
    'font_size_factor' => 3 // Default font size factor (higher = larger font)
];

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Validate font weight
    $valid_weights = ['Thin', 'ExtraLight', 'Light', 'Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold', 'Black'];
    $font_weight = isset($_POST['font_weight']) && in_array($_POST['font_weight'], $valid_weights)
        ? $_POST['font_weight']
        : 'Regular';

    // Validate colors (simple validation for hex format)
    $bg_color = isset($_POST['bg_color']) && preg_match('/^#[a-f0-9]{6}$/i', $_POST['bg_color'])
        ? $_POST['bg_color']
        : '#cccccc';

    $text_color = isset($_POST['text_color']) && preg_match('/^#[a-f0-9]{6}$/i', $_POST['text_color'])
        ? $_POST['text_color']
        : '#ffffff';

    // Validate font size factor (between 1 and 6)
    $font_size_factor = isset($_POST['font_size_factor']) && is_numeric($_POST['font_size_factor'])
        && $_POST['font_size_factor'] >= 1 && $_POST['font_size_factor'] <= 6
        ? (float)$_POST['font_size_factor']
        : 3;

    // Save settings to database
    try {
        // Update each setting in the database
        $settings = [
            'font_weight' => $font_weight,
            'bg_color' => $bg_color,
            'text_color' => $text_color,
            'font_size_factor' => $font_size_factor
        ];
        
        foreach ($settings as $key => $value) {
            // Check if setting exists
            $check = $conn->prepare("SELECT id FROM placeholder_settings WHERE setting_key = ?");
            $check->bind_param("s", $key);
            $check->execute();
            $result = $check->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing setting
                $stmt = $conn->prepare("UPDATE placeholder_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->bind_param("ss", $value, $key);
                $stmt->execute();
            } else {
                // Insert new setting
                $stmt = $conn->prepare("INSERT INTO placeholder_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->bind_param("ss", $value, $key);
                $stmt->execute();
            }
            
            // Close statements
            $check->close();
            $stmt->close();
        }
        
        $success_message = "Settings saved successfully!";
        
        // Update our current settings for this page
        $placeholder_settings['font_weight'] = $font_weight;
        $placeholder_settings['bg_color'] = $bg_color;
        $placeholder_settings['text_color'] = $text_color;
        $placeholder_settings['font_size_factor'] = $font_size_factor;
        
        // Clear placeholder images to force regeneration
        $placeholder_dir = __DIR__ . '/../uploads/placeholders';
        if (is_dir($placeholder_dir)) {
            // Clear both PNG and WebP placeholder images
            $png_files = glob($placeholder_dir . '/*.png');
            $webp_files = glob($placeholder_dir . '/*.webp');
            $all_files = array_merge($png_files, $webp_files);

            foreach ($all_files as $file) {
                @unlink($file);
            }
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Load existing settings from database
$db_placeholder_settings = get_placeholder_settings_from_db();

// Merge with defaults if we have database settings
if (!empty($db_placeholder_settings)) {
    $placeholder_settings = array_merge($placeholder_settings, $db_placeholder_settings);
} else {
    // Fallback to file if database fails
    $settings_file = __DIR__ . '/../includes/placeholder_settings.php';
    if (file_exists($settings_file)) {
        include $settings_file;
    }
}

// Generate a sample placeholder image with current settings
$sample_initials = 'AB';
$sample_size = '200x200';
$sample_image_url = get_staff_image_url([
    'first_name' => 'Admin',
    'last_name' => 'Buddy',
    'profile_picture' => ''
], $sample_size);

?>

<div class="admin-container">
    <h1>Placeholder Image Settings</h1>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="settings-container">
        <div class="row">
            <div class="col-md-6">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="font_weight">Font Weight:</label>
                        <select name="font_weight" id="font_weight" class="form-control">
                            <?php
                            $weights = ['Thin', 'ExtraLight', 'Light', 'Regular', 'Medium', 'SemiBold', 'Bold', 'ExtraBold', 'Black'];
                            foreach ($weights as $weight) {
                                $selected = ($weight === $placeholder_settings['font_weight']) ? 'selected' : '';
                                echo "<option value=\"{$weight}\" {$selected}>{$weight}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="bg_color">Background Color:</label>
                        <input type="color" name="bg_color" id="bg_color" class="form-control"
                               value="<?php echo $placeholder_settings['bg_color']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="text_color">Text Color:</label>
                        <input type="color" name="text_color" id="text_color" class="form-control"
                               value="<?php echo $placeholder_settings['text_color']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="font_size_factor">Font Size:</label>
                        <input type="range" name="font_size_factor" id="font_size_factor" class="form-control"
                               min="1" max="6" step="0.5"
                               value="<?php echo isset($placeholder_settings['font_size_factor']) ? $placeholder_settings['font_size_factor'] : 3; ?>">
                        <div class="range-labels">
                            <span class="small">Small</span>
                            <span class="current-value"><?php echo isset($placeholder_settings['font_size_factor']) ? $placeholder_settings['font_size_factor'] : 3; ?></span>
                            <span class="large">Large</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>

            <div class="col-md-6">
                <div class="preview-container">
                    <h3>Preview</h3>
                    <div class="sample-image">
                        <img src="<?php echo $sample_image_url; ?>" alt="Sample Placeholder" class="img-fluid">
                    </div>
                    <p class="mt-3">Current Settings:</p>
                    <ul>
                        <li><strong>Font Weight:</strong> <?php echo $placeholder_settings['font_weight']; ?></li>
                        <li><strong>Background Color:</strong> <?php echo $placeholder_settings['bg_color']; ?></li>
                        <li><strong>Text Color:</strong> <?php echo $placeholder_settings['text_color']; ?></li>
                        <li><strong>Font Size Factor:</strong> <span id="font-size-display"><?php echo isset($placeholder_settings['font_size_factor']) ? $placeholder_settings['font_size_factor'] : 3; ?></span></li>
                    </ul>
                    <p class="text-muted">
                       <small><i>Note: Saving settings will clear all existing placeholder images to force regeneration with new settings.</i></small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update font size display when slider changes
    document.addEventListener('DOMContentLoaded', function() {
        const fontSizeSlider = document.getElementById('font_size_factor')
        const fontSizeDisplay = document.querySelector('.range-labels .current-value')
        const fontSizePreview = document.getElementById('font-size-display')

        if (fontSizeSlider && fontSizeDisplay && fontSizePreview) {
            fontSizeSlider.addEventListener('input', function() {
                const value = parseFloat(this.value).toFixed(1)
                // Remove trailing zero if value is whole number
                const displayValue = value.endsWith('.0') ? value.slice(0, -2) : value
                fontSizeDisplay.textContent = displayValue
                fontSizePreview.textContent = displayValue
            })
        }
    })
</script>

<?php require_once '../includes/footer.php'; ?>
