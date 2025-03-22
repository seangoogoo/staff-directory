-- Migration script for adding color column to departments table
-- This script assumes the departments table already exists

-- Add color column if it doesn't exist
ALTER TABLE `departments` 
ADD COLUMN IF NOT EXISTS `color` varchar(7) DEFAULT '#6c757d' AFTER `description`;

-- Update default departments with specific colors
UPDATE `departments` SET `color` = '#007bff' WHERE `name` = 'IT' AND (`color` IS NULL OR `color` = '#6c757d');
UPDATE `departments` SET `color` = '#28a745' WHERE `name` = 'Marketing' AND (`color` IS NULL OR `color` = '#6c757d');
UPDATE `departments` SET `color` = '#dc3545' WHERE `name` = 'HR' AND (`color` IS NULL OR `color` = '#6c757d');
UPDATE `departments` SET `color` = '#ffc107' WHERE `name` = 'Finance' AND (`color` IS NULL OR `color` = '#6c757d');
UPDATE `departments` SET `color` = '#17a2b8' WHERE `name` = 'Operations' AND (`color` IS NULL OR `color` = '#6c757d');

-- Set default color for any other departments that don't have a color
UPDATE `departments` SET `color` = '#6c757d' WHERE `color` IS NULL;

-- Show that the migration was successful
SELECT 'Color column added to departments table successfully!' as message;
