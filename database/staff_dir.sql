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
('IT', 'Information Technology department responsible for technical infrastructure and software development', '#94C2F3'),
('Marketing', 'Marketing department handling brand promotion, advertising, and customer outreach', '#A8D8AD'),
('HR', 'Human Resources department managing staffing, benefits, and employee relations', '#F4B8B8'),
('Finance', 'Finance department overseeing accounting, budgeting, and financial reporting', '#FFEAAA'),
('Operations', 'Operations department managing day-to-day business activities and logistics', '#9FE0E0');

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
('John', 'Doe', 1, 'Software Developer', 'john.doe@example.com', ''),
('Jane', 'Smith', 2, 'Marketing Manager', 'jane.smith@example.com', ''),
('Michael', 'Johnson', 3, 'HR Specialist', 'michael.johnson@example.com', ''),
('Emily', 'Williams', 4, 'Financial Analyst', 'emily.williams@example.com', ''),
('David', 'Brown', 1, 'Network Administrator', 'david.brown@example.com', '');

-- Placeholder Settings Table
CREATE TABLE IF NOT EXISTS `placeholder_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default placeholder settings
INSERT INTO `placeholder_settings` (`setting_key`, `setting_value`) VALUES
('font_weight', 'Regular'),
('bg_color', '#cccccc'),
('text_color', '#ffffff'),
('font_size_factor', '3');
