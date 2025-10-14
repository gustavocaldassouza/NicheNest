-- Migration script to add moderation features
-- Run this if you already have an existing database

-- Add flagged column to posts table (check if column exists first)
-- Note: MySQL 5.7 and below don't support ADD COLUMN IF NOT EXISTS
-- This will fail gracefully if the column already exists
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'posts' 
     AND COLUMN_NAME = 'flagged') = 0,
    'ALTER TABLE posts ADD COLUMN flagged BOOLEAN DEFAULT FALSE AFTER replies_count, ADD INDEX idx_flagged (flagged)',
    'SELECT "Column flagged already exists" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create moderation_logs table
CREATE TABLE IF NOT EXISTS moderation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    moderator_id INT NOT NULL,
    action ENUM('flag_post', 'unflag_post', 'delete_post', 'suspend_user', 'activate_user') NOT NULL,
    target_type ENUM('post', 'user') NOT NULL,
    target_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_moderator_id (moderator_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
);
