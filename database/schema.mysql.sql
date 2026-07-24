-- Sumruddha Sala E-Portal — Notification & Alerts schema (MySQL / MariaDB)
-- Use this when deploying to a real LAMP host. Import with:
--   mysql -u root -p sumruddha_sala < database/schema.mysql.sql

CREATE TABLE districts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE talukas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  district_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE schools (
  id INT AUTO_INCREMENT PRIMARY KEY,
  taluka_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  FOREIGN KEY (taluka_id) REFERENCES talukas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  project_type VARCHAR(30) NOT NULL DEFAULT 'Construction',
  funding_source VARCHAR(60) NOT NULL,
  stage VARCHAR(60) NOT NULL,
  completion_pct TINYINT UNSIGNED NOT NULL DEFAULT 0,
  delay_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  geotag_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  officer VARCHAR(120),
  sanctioned_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  utilized_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'in_progress',
  FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  type ENUM('critical','pending','info','success') NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  reason VARCHAR(200),
  action_label VARCHAR(60) NOT NULL,
  priority_label VARCHAR(30) NOT NULL,
  remarks TEXT,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id),
  INDEX idx_type (type),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE deadlines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  label VARCHAR(150) NOT NULL,
  due_date DATE NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
