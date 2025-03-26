-- Migration script to add companies table and update staff_members table
-- Created: 2025-03-25

-- Create companies table
CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `logo` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert example companies
INSERT INTO `companies` (`name`, `description`, `logo`) VALUES
('NeuroSoft GmbH', 'Leading software development company specializing in enterprise solutions and cutting-edge technologies', '/assets/images/companies/neurosoft_logo.png'),
('EcoVert Consultants', 'Environmental consulting firm helping businesses implement sustainable practices', '/assets/images/companies/ecovert_logo.png'),
('MediTech AG', 'Healthcare provider focusing on innovative medical technologies and patient care', '/assets/images/companies/meditech_logo.png');

-- Add company_id column to staff_members table
ALTER TABLE `staff_members` 
ADD COLUMN `company_id` int(11) NOT NULL DEFAULT 1 AFTER `last_name`,
ADD KEY `company_id` (`company_id`),
ADD CONSTRAINT `staff_members_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Re-order columns to put company_id before department_id (this requires recreating table in MySQL)
-- This comment is a note for manual handling since MySQL doesn't support direct column reordering

-- Link all existing staff members to the first company (NeuroSoft GmbH, company_id=1)
UPDATE `staff_members` SET `company_id` = 1;

-- Add sample staff for EcoVert Consultants (company_id=2)
INSERT INTO `staff_members` (`first_name`, `last_name`, `company_id`, `department_id`, `job_title`, `email`, `profile_picture`) VALUES
('Léa', 'Dubois', 2, 1, 'Environmental Analyst', 'lea.dubois@ecovert.com', ''),
('Matthias', 'Wagner', 2, 9, 'Sustainability Data Scientist', 'matthias.wagner@ecovert.com', ''),
('Sofia', 'Lombardi', 2, 7, 'Environmental UI Designer', 'sofia.lombardi@ecovert.com', ''),
('Pascal', 'Lefevre', 2, 11, 'Project Coordinator', 'pascal.lefevre@ecovert.com', '');

-- Add sample staff for MediTech AG (company_id=3)
INSERT INTO `staff_members` (`first_name`, `last_name`, `company_id`, `department_id`, `job_title`, `email`, `profile_picture`) VALUES
('Astrid', 'Bergman', 3, 2, 'Healthcare Frontend Developer', 'astrid.bergman@meditech.com', ''),
('Klaus', 'Müller', 3, 3, 'Medical Systems Developer', 'klaus.muller@meditech.com', ''),
('Charlotte', 'Rousseau', 3, 5, 'Healthcare QA Specialist', 'charlotte.rousseau@meditech.com', ''),
('Mikkel', 'Jensen', 3, 8, 'Healthcare Security Officer', 'mikkel.jensen@meditech.com', '');

-- Add default company setting to app_settings
INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('default_company_id', '1');

-- Create a directory for company logos if it doesn't exist
-- Note: This SQL comment is a reminder to manually create this directory on the server
-- mkdir -p public/assets/images/companies
