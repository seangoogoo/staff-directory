    </main>
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Staff Directory Admin Panel</p>
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
    
    <!-- Login modal removed - not needed in admin area since users are already authenticated -->
</body>
</html>
