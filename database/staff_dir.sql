-- Database schema for Staff Directory Application

CREATE DATABASE IF NOT EXISTS `staff_dir`;
USE `staff_dir`;

-- Staff Members Table
CREATE TABLE IF NOT EXISTS `staff_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample data
INSERT INTO `staff_members` (`first_name`, `last_name`, `department`, `job_title`, `email`, `profile_picture`) VALUES
('John', 'Doe', 'IT', 'Software Developer', 'john.doe@example.com', ''),
('Jane', 'Smith', 'Marketing', 'Marketing Manager', 'jane.smith@example.com', ''),
('Michael', 'Johnson', 'HR', 'HR Specialist', 'michael.johnson@example.com', ''),
('Emily', 'Williams', 'Finance', 'Financial Analyst', 'emily.williams@example.com', ''),
('David', 'Brown', 'IT', 'Network Administrator', 'david.brown@example.com', '');
