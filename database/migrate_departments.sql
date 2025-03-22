-- Migration script for adding departments table and updating staff_members table
-- Execute this script on the existing staff_dir database

-- Create a backup of the staff_members table
CREATE TABLE IF NOT EXISTS `staff_members_backup` LIKE `staff_members`;
INSERT INTO `staff_members_backup` SELECT * FROM `staff_members`;

-- Create the new departments table
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `color` varchar(7) DEFAULT '#6c757d',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert the default departments
INSERT INTO `departments` (`name`, `description`, `color`) VALUES
('IT', 'Information Technology department responsible for technical infrastructure and software development', '#007bff'),
('Marketing', 'Marketing department handling brand promotion, advertising, and customer outreach', '#28a745'),
('HR', 'Human Resources department managing staffing, benefits, and employee relations', '#dc3545'),
('Finance', 'Finance department overseeing accounting, budgeting, and financial reporting', '#ffc107'),
('Operations', 'Operations department managing day-to-day business activities and logistics', '#17a2b8');

-- Collect any additional unique departments from the staff_members table and insert them into the departments table
INSERT IGNORE INTO `departments` (`name`, `description`, `color`)
SELECT DISTINCT department, CONCAT(department, ' department'), '#6c757d'
FROM `staff_members`
ORDER BY department;

-- Add the new department_id column to the staff_members table
ALTER TABLE `staff_members` ADD COLUMN `department_id` int(11) AFTER `last_name`;

-- Update the department_id based on the department name
UPDATE `staff_members` s
JOIN `departments` d ON s.department = d.name
SET s.department_id = d.id;

-- Make department_id NOT NULL after data migration
ALTER TABLE `staff_members` MODIFY COLUMN `department_id` int(11) NOT NULL;

-- Add the foreign key constraint
ALTER TABLE `staff_members`
ADD KEY `department_id` (`department_id`),
ADD CONSTRAINT `staff_members_ibfk_1` 
FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) 
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Drop the old department column
ALTER TABLE `staff_members` DROP COLUMN `department`;

-- Show that the migration was successful
SELECT 'Migration completed successfully!' as message;
SELECT 'The following SQL command can be used to verify the migration:' as note;
SELECT 'SELECT s.id, s.first_name, s.last_name, d.name as department, s.job_title, s.email FROM staff_members s JOIN departments d ON s.department_id = d.id;' as verification_query;
