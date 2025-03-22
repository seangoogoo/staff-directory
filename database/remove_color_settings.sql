-- SQL file to remove background color and text color settings from placeholder_settings table
-- Created: 2025-03-22

-- Delete background color setting
DELETE FROM placeholder_settings WHERE setting_key = 'bg_color';

-- Delete text color setting
DELETE FROM placeholder_settings WHERE setting_key = 'text_color';

-- Output message
SELECT 'Removed background color and text color settings from placeholder_settings table' AS message;
