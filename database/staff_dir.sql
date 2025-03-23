-- Database schema for Staff Directory Application

CREATE DATABASE IF NOT EXISTS `staff_dir`;
USE `staff_dir`;

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
  `department_id` int(11) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `staff_members_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample data
-- Note: We're using department IDs now instead of department names
INSERT INTO `staff_members` (`first_name`, `last_name`, `department_id`, `job_title`, `email`, `profile_picture`) VALUES
('Antoine', 'Dupont', 2, 'Frontend Developer', 'antoine.dupont@staffdirectory.com', ''),
('Sophia', 'Müller', 3, 'Backend Developer', 'sophia.muller@staffdirectory.com', ''),
('Marco', 'Rossi', 4, 'Mobile Developer (iOS)', 'marco.rossi@staffdirectory.com', ''),
('Amélie', 'Laurent', 5, 'QA Tester', 'amelie.laurent@staffdirectory.com', ''),
('Lukas', 'Schmidt', 6, 'DevOps Engineer', 'lukas.schmidt@staffdirectory.com', ''),
('Isabella', 'Bianchi', 7, 'UX Designer', 'isabella.bianchi@staffdirectory.com', ''),
('Henrik', 'Andersson', 8, 'Security Analyst', 'henrik.andersson@staffdirectory.com', ''),
('Elena', 'Petrov', 9, 'Data Analyst', 'elena.petrov@staffdirectory.com', ''),
('Pierre', 'Moreau', 10, 'Technical Support Specialist', 'pierre.moreau@staffdirectory.com', ''),
('Ingrid', 'Nielsen', 11, 'Project Manager', 'ingrid.nielsen@staffdirectory.com', ''),
('Sven', 'Eriksson', 12, 'IT Support', 'sven.eriksson@staffdirectory.com', ''),
('Clara', 'Fischer', 1, 'Business Analyst', 'clara.fischer@staffdirectory.com', ''),
('Matteo', 'Conti', 2, 'UI Developer', 'matteo.conti@staffdirectory.com', ''),
('Camille', 'Bernard', 3, 'API Developer', 'camille.bernard@staffdirectory.com', ''),
('Andreas', 'Weber', 4, 'Mobile Developer (Android)', 'andreas.weber@staffdirectory.com', ''),
('Sophie', 'Dubois', 5, 'QA Analyst', 'sophie.dubois@staffdirectory.com', ''),
('Viktor', 'Kowalski', 6, 'Systems Administrator', 'viktor.kowalski@staffdirectory.com', ''),
('Alessandra', 'Ferrari', 7, 'UI Designer', 'alessandra.ferrari@staffdirectory.com', ''),
('Nikolai', 'Ivanov', 8, 'Security Engineer', 'nikolai.ivanov@staffdirectory.com', ''),
('Elisa', 'Santos', 9, 'Data Engineer', 'elisa.santos@staffdirectory.com', ''),
('François', 'Martin', 10, 'Customer Support Engineer', 'francois.martin@staffdirectory.com', ''),
('Linnea', 'Lindholm', 11, 'Scrum Master', 'linnea.lindholm@staffdirectory.com', ''),
('Jens', 'Hoffmann', 12, 'Network Administrator', 'jens.hoffmann@staffdirectory.com', '');

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
('show_logo', '1'); -- 1 = show logo, 0 = hide logo
