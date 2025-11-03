-- Add last_login field to users table
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL;

-- Update existing users to have NULL last_login (indicating they haven't logged in yet)
UPDATE users SET last_login = NULL WHERE last_login IS NOT NULL;
