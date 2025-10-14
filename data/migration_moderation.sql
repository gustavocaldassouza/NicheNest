-- Migration script to add moderation features
-- Run this if you already have an existing database

-- Add flagged column to posts table
ALTER TABLE posts 
ADD COLUMN flagged BOOLEAN DEFAULT FALSE AFTER replies_count,
ADD INDEX idx_flagged (flagged);

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
