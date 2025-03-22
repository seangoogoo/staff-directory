-- Migration script to add placeholder settings table
-- Created: 2025-03-22

-- Create placeholder_settings table
CREATE TABLE IF NOT EXISTS `placeholder_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
-- First check if settings already exist to avoid duplicates
INSERT INTO `placeholder_settings` (`setting_key`, `setting_value`)
SELECT 'font_weight', 'Regular'
WHERE NOT EXISTS (SELECT 1 FROM `placeholder_settings` WHERE `setting_key` = 'font_weight');

INSERT INTO `placeholder_settings` (`setting_key`, `setting_value`)
SELECT 'bg_color', '#cccccc'
WHERE NOT EXISTS (SELECT 1 FROM `placeholder_settings` WHERE `setting_key` = 'bg_color');

INSERT INTO `placeholder_settings` (`setting_key`, `setting_value`)
SELECT 'text_color', '#ffffff'
WHERE NOT EXISTS (SELECT 1 FROM `placeholder_settings` WHERE `setting_key` = 'text_color');

INSERT INTO `placeholder_settings` (`setting_key`, `setting_value`)
SELECT 'font_size_factor', '3'
WHERE NOT EXISTS (SELECT 1 FROM `placeholder_settings` WHERE `setting_key` = 'font_size_factor');
