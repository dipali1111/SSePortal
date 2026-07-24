CREATE DATABASE IF NOT EXISTS samruddh_shala;
USE samruddh_shala;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    role ENUM('HM','Admin','Engineer') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hm_id INT NOT NULL,
    project_name VARCHAR(180) NOT NULL,
    work_type ENUM('New Construction','Repair Work','Renovation','Toilet Construction','Classroom Construction') NOT NULL,
    location VARCHAR(180) NOT NULL,
    status ENUM('Pending','In Progress','Completed','Delayed') NOT NULL,
    progress_percentage INT DEFAULT 0,
    stage VARCHAR(80) NOT NULL,
    start_date DATE,
    expected_completion_date DATE,
    total_budget DECIMAL(12,2) DEFAULT 0,
    utilized_amount DECIMAL(12,2) DEFAULT 0,
    last_update_date DATE,
    last_remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hm_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS progress_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    hm_id INT NOT NULL,
    project_stage VARCHAR(80) NOT NULL,
    progress_percentage INT NOT NULL,
    remarks TEXT NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (hm_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS blockers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    hm_id INT NOT NULL,
    blocker_type VARCHAR(80) NOT NULL,
    title VARCHAR(180) NOT NULL,
    reason TEXT NOT NULL,
    expected_impact TEXT,
    attachment VARCHAR(255) DEFAULT NULL,
    status ENUM('Reported','Under Review','Resolved') DEFAULT 'Reported',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (hm_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hm_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(80) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hm_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS fund_utilization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    allocated_amount DECIMAL(12,2) DEFAULT 0,
    utilized_amount DECIMAL(12,2) DEFAULT 0,
    remaining_amount DECIMAL(12,2) DEFAULT 0,
    utilization_percentage INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

INSERT INTO users (full_name, email, role, password_hash) VALUES
('Head Master', 'hm@samruddhshala.gov', 'HM', '$2y$10$N5LqD7dM8S7e6q5hQ8Y8Xel3pVQJ7k2oP0zBv9E1D7o4o8A5jF1y');

INSERT INTO projects (hm_id, project_name, work_type, location, status, progress_percentage, stage, start_date, expected_completion_date, total_budget, utilized_amount, last_update_date, last_remark) VALUES
(1, 'Primary School Class Room Extension', 'Classroom Construction', 'Bengaluru North', 'In Progress', 65, 'Construction', '2026-01-12', '2026-08-30', 1200000, 780000, '2026-07-18', 'Brickwork completed and plastering in progress.'),
(1, 'Toilet Block Upgrade', 'Toilet Construction', 'Kolar District', 'Pending', 15, 'Foundation', '2026-02-09', '2026-09-15', 650000, 98000, '2026-07-10', 'Pending final site clearance.'),
(1, 'School Compound Repair Works', 'Repair Work', 'Mysuru', 'Completed', 100, 'Completed', '2025-11-11', '2026-03-18', 450000, 450000, '2026-03-18', 'Project handed over successfully.'),
(1, 'Science Lab Renovation', 'Renovation', 'Tumakuru', 'Delayed', 48, 'Roofing', '2026-03-05', '2026-10-22', 900000, 430000, '2026-07-16', 'Material supply issue affecting roof work.' );

INSERT INTO progress_updates (project_id, hm_id, project_stage, progress_percentage, remarks, photo, submitted_at) VALUES
(1, 1, 'Construction', 65, 'Construction work has progressed well with wall finishing.', NULL, '2026-07-18 10:30:00'),
(2, 1, 'Foundation', 15, 'Foundation works have started and site review approved.', NULL, '2026-07-10 09:00:00'),
(4, 1, 'Roofing', 48, 'Roof slab progress is delayed due to materials shortage.', NULL, '2026-07-16 12:00:00');

INSERT INTO blockers (project_id, hm_id, blocker_type, title, reason, expected_impact, attachment, status, created_at) VALUES
(4, 1, 'Material Shortage', 'Cement Supply Delay', 'Cement supply is delayed, affecting roof completion.', 'Likely 8 day delay in roofing phase.', NULL, 'Reported', '2026-07-16 09:45:00'),
(1, 1, 'Approval Delay', 'Electricity Approval Pending', 'Electrical sanction approval is pending from the district office.', 'May delay final electrification milestone.', NULL, 'Under Review', '2026-07-18 08:20:00');

INSERT INTO notifications (hm_id, title, message, type, is_read, created_at) VALUES
(1, 'New Work Assigned', 'Primary School Class Room Extension has been assigned to you.', 'assignment', 0, '2026-07-18 08:00:00'),
(1, 'Progress Update Approved', 'Your update for Primary School Class Room Extension was approved.', 'progress', 0, '2026-07-18 11:00:00'),
(1, 'Blocker Status Update', 'The cement supply delay is now under review.', 'blocker', 1, '2026-07-16 10:00:00');

INSERT INTO fund_utilization (project_id, allocated_amount, utilized_amount, remaining_amount, utilization_percentage, updated_at) VALUES
(1, 1200000, 780000, 420000, 65, '2026-07-18 10:30:00'),
(2, 650000, 98000, 552000, 15, '2026-07-10 09:00:00'),
(3, 450000, 450000, 0, 100, '2026-03-18 09:00:00'),
(4, 900000, 430000, 470000, 48, '2026-07-16 12:00:00');
