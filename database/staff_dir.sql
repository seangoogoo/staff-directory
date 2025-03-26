-- Database schema for Staff Directory Application

CREATE DATABASE IF NOT EXISTS `staff_dir`;
USE `staff_dir`;

-- Companies Table
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
('NeuroSoft GmbH', 'Leading software development company specializing in enterprise solutions and cutting-edge technologies', '/uploads/companies/software-company.svg'),
('EcoVert Consultants', 'Environmental consulting firm helping businesses implement sustainable practices', '/uploads/companies/environmental-company.svg'),
('MediTech AG', 'Healthcare provider focusing on innovative medical technologies and patient care', '/uploads/companies/healthcare-company.svg');

-- Departments Table
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

-- Insert default departments
INSERT INTO `departments` (`name`, `description`, `color`) VALUES
('Product Management', 'Defines product vision, roadmaps, and requirements', '#94C2F3'),
('Frontend Development', 'Develops user interfaces and client-side logic', '#A8D8AD'),
('Backend Development', 'Builds server-side logic, APIs, and databases', '#F4B8B8'),
('Mobile Development', 'Creates applications for mobile platforms (iOS, Android)', '#FFEAAA'),
('Quality Assurance (QA)', 'Tests software to ensure quality and functionality', '#9FE0E0'),
('DevOps', 'Manages infrastructure, deployment, and automation', '#B19CD9'),
('UI/UX Design', 'Designs user interfaces and user experiences', '#F8C471'),
('Cybersecurity', 'Protects systems and data from security threats', '#EC7063'),
('Data Analysis', 'Analyzes data to provide insights and improve products', '#5DADE2'),
('Technical Support', 'Provides technical assistance to users', '#45B39D'),
('Project Management', 'Plans, executes, and monitors software development projects', '#D7BDE2'),
('General IT/Systems', 'Manages internal company computer systems, and network', '#7DCEA0');

-- Staff Members Table
CREATE TABLE IF NOT EXISTS `staff_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT 1,
  `department_id` int(11) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `company_id` (`company_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `staff_members_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `staff_members_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample data for NeuroSoft GmbH (company_id=1)
-- Note: We're using department IDs now instead of department names
INSERT INTO `staff_members` (`first_name`, `last_name`, `company_id`, `department_id`, `job_title`, `email`, `profile_picture`) VALUES
('Antoine', 'Dupont', 1, 2, 'Frontend Developer', 'antoine.dupont@staffdirectory.com', ''),
('Sophia', 'Müller', 1, 3, 'Backend Developer', 'sophia.muller@staffdirectory.com', ''),
('Marco', 'Rossi', 1, 4, 'Mobile Developer (iOS)', 'marco.rossi@staffdirectory.com', ''),
('Amélie', 'Laurent', 1, 5, 'QA Tester', 'amelie.laurent@staffdirectory.com', ''),
('Lukas', 'Schmidt', 1, 6, 'DevOps Engineer', 'lukas.schmidt@staffdirectory.com', ''),
('Isabella', 'Bianchi', 1, 7, 'UX Designer', 'isabella.bianchi@staffdirectory.com', ''),
('Henrik', 'Andersson', 1, 8, 'Security Analyst', 'henrik.andersson@staffdirectory.com', ''),
('Elena', 'Petrov', 1, 9, 'Data Analyst', 'elena.petrov@staffdirectory.com', ''),
('Pierre', 'Moreau', 1, 10, 'Technical Support Specialist', 'pierre.moreau@staffdirectory.com', ''),
('Ingrid', 'Nielsen', 1, 11, 'Project Manager', 'ingrid.nielsen@staffdirectory.com', ''),
('Sven', 'Eriksson', 1, 12, 'IT Support', 'sven.eriksson@staffdirectory.com', ''),
('Clara', 'Fischer', 1, 1, 'Business Analyst', 'clara.fischer@staffdirectory.com', ''),
('Matteo', 'Conti', 1, 2, 'UI Developer', 'matteo.conti@staffdirectory.com', ''),
('Camille', 'Bernard', 1, 3, 'API Developer', 'camille.bernard@staffdirectory.com', ''),
('Andreas', 'Weber', 1, 4, 'Mobile Developer (Android)', 'andreas.weber@staffdirectory.com', ''),
('Sophie', 'Dubois', 1, 5, 'QA Analyst', 'sophie.dubois@staffdirectory.com', ''),
('Viktor', 'Kowalski', 1, 6, 'Systems Administrator', 'viktor.kowalski@staffdirectory.com', ''),
('Alessandra', 'Ferrari', 1, 7, 'UI Designer', 'alessandra.ferrari@staffdirectory.com', ''),
('Nikolai', 'Ivanov', 1, 8, 'Security Engineer', 'nikolai.ivanov@staffdirectory.com', ''),
('Elisa', 'Santos', 1, 9, 'Data Engineer', 'elisa.santos@staffdirectory.com', ''),
('François', 'Martin', 1, 10, 'Customer Support Engineer', 'francois.martin@staffdirectory.com', ''),
('Linnea', 'Lindholm', 1, 11, 'Scrum Master', 'linnea.lindholm@staffdirectory.com', ''),
('Jens', 'Hoffmann', 1, 12, 'Network Administrator', 'jens.hoffmann@staffdirectory.com', '');

-- Sample staff for EcoVert Consultants (company_id=2)
INSERT INTO `staff_members` (`first_name`, `last_name`, `company_id`, `department_id`, `job_title`, `email`, `profile_picture`) VALUES
('Léa', 'Dubois', 2, 1, 'Environmental Analyst', 'lea.dubois@ecovert.com', ''),
('Matthias', 'Wagner', 2, 9, 'Sustainability Data Scientist', 'matthias.wagner@ecovert.com', ''),
('Sofia', 'Lombardi', 2, 7, 'Environmental UI Designer', 'sofia.lombardi@ecovert.com', ''),
('Pascal', 'Lefevre', 2, 11, 'Project Coordinator', 'pascal.lefevre@ecovert.com', '');

-- Sample staff for MediTech AG (company_id=3)
INSERT INTO `staff_members` (`first_name`, `last_name`, `company_id`, `department_id`, `job_title`, `email`, `profile_picture`) VALUES
('Astrid', 'Bergman', 3, 2, 'Healthcare Frontend Developer', 'astrid.bergman@meditech.com', ''),
('Klaus', 'Müller', 3, 3, 'Medical Systems Developer', 'klaus.muller@meditech.com', ''),
('Charlotte', 'Rousseau', 3, 5, 'Healthcare QA Specialist', 'charlotte.rousseau@meditech.com', ''),
('Mikkel', 'Jensen', 3, 8, 'Healthcare Security Officer', 'mikkel.jensen@meditech.com', '');

-- Application Settings Table
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default application settings
INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('font_weight', 'Regular'),
('font_size_factor', '3'),
('custom_logo_path', ''),
('frontend_title', 'My company'),
('admin_title', 'Staff Directory Admin'),
('show_logo', '1'), -- 1 = show logo, 0 = hide logo
('default_company_id', '1'); -- Default company to show when no company is selected
