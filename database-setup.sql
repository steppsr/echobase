
### 3. database-setup.sql

```sql
-- =============================================
-- ECHOBASE Database Setup Script
-- Run this file once in phpMyAdmin or via CLI:
--   mysql -u youruser -p echobase < database-setup.sql
-- =============================================

CREATE DATABASE IF NOT EXISTS `echobase` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `echobase`;

-- Projects table
CREATE TABLE IF NOT EXISTS `projects` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `name`            VARCHAR(255) NOT NULL,
  `description`     TEXT,
  `status`          ENUM('backlog','todo','in_progress','review','done') NOT NULL DEFAULT 'backlog',
  `priority`        ENUM('low','medium','high','urgent') DEFAULT 'medium',
  `tags`            TEXT,
  `logo_path`       VARCHAR(500) DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project notes
CREATE TABLE IF NOT EXISTS `project_notes` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT NOT NULL,
  `note`        TEXT NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project documents
CREATE TABLE IF NOT EXISTS `project_documents` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `project_id`    INT NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_path`   VARCHAR(500) NOT NULL,
  `mime_type`     VARCHAR(150),
  `file_size`     INT,
  `uploaded_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample starter projects
INSERT IGNORE INTO `projects` (`name`, `description`, `status`, `priority`, `tags`) VALUES
('Death Star Plans', 'Steal the technical readouts and get them to the Rebellion', 'in_progress', 'urgent', 'mission,priority1'),
('Echo Base Construction', 'Build the new rebel headquarters on Hoth', 'todo', 'high', 'base,construction');