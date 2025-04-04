    </main>

    <!-- Basic Admin Footer: Padding, top border, small muted text -->
    <footer class="admin-footer mt-8 py-4 border-t border-gray-200 text-sm text-gray-500">
        <!-- Standard container -->
        <div class="container w-full max-w-screen-xl mx-auto px-4">
            <p>&copy; <?php echo date('Y'); ?> <a href="https://github.com/seangoogoo/staff-directory" target="_blank" rel="noopener noreferrer">Staff Directory</a> Admin Panel</p>
        </div>
    </footer>

    <?php
    // Output any missing image messages as console logs
    if (isset($missing_images) && !empty($missing_images)) {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            console.group("⚠️ Admin Alert: Missing Staff Profile Images")
            console.info("Found " + ' . count($missing_images) . ' + " staff member(s) with missing profile images")
            ' . implode("
            ", array_map(function($msg) { return "console.warn('" . addslashes($msg) . "')"; }, $missing_images)) . '
            console.info("Please upload new images or remove the references from the database")
            console.groupEnd()
        })
        </script>';
    }
    ?>
</body>
</html>
