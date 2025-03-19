-- Database schema for Staff Directory Application

CREATE DATABASE IF NOT EXISTS `staff_dir`;
USE `staff_dir`;

-- Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default departments
INSERT INTO `departments` (`name`, `description`) VALUES
('IT', 'Information Technology department responsible for technical infrastructure and software development'),
('Marketing', 'Marketing department handling brand promotion, advertising, and customer outreach'),
('HR', 'Human Resources department managing staffing, benefits, and employee relations'),
('Finance', 'Finance department overseeing accounting, budgeting, and financial reporting'),
('Operations', 'Operations department managing day-to-day business activities and logistics');

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
