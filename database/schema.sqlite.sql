-- Sumruddha Sala E-Portal — Notification & Alerts schema (SQLite)

CREATE TABLE districts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE
);

CREATE TABLE talukas (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  district_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  FOREIGN KEY (district_id) REFERENCES districts(id)
);

CREATE TABLE schools (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  taluka_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  FOREIGN KEY (taluka_id) REFERENCES talukas(id)
);

CREATE TABLE projects (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  project_type TEXT NOT NULL DEFAULT 'Construction',   -- Construction / Non-Construction
  funding_source TEXT NOT NULL,                         -- Annual Plan / Minor Mineral Fund / ZP Own Fund / CSR Fund
  stage TEXT NOT NULL,                                   -- Planning & Approval / Demolition / Walls & Construction / Foundation & Plinth / Finishing & Handover
  completion_pct INTEGER NOT NULL DEFAULT 0,
  delay_days INTEGER NOT NULL DEFAULT 0,
  geotag_status TEXT NOT NULL DEFAULT 'Pending',         -- Verified / Pending / Missing
  officer TEXT,
  sanctioned_amount REAL NOT NULL DEFAULT 0,
  utilized_amount REAL NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'in_progress',            -- in_progress / completed / blocked
  FOREIGN KEY (school_id) REFERENCES schools(id)
);

CREATE TABLE notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  type TEXT NOT NULL CHECK (type IN ('critical','pending','info','success')),
  title TEXT NOT NULL,
  description TEXT NOT NULL,
  reason TEXT,
  action_label TEXT NOT NULL,
  priority_label TEXT NOT NULL,                          -- Delayed / Pending / Information / Completed
  remarks TEXT,
  is_read INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE deadlines (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  label TEXT NOT NULL,
  due_date DATE NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE activity_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_created ON notifications(created_at);
CREATE INDEX idx_projects_school ON projects(school_id);
CREATE INDEX idx_schools_taluka ON schools(taluka_id);
