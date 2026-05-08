-- Create database if not exists
CREATE DATABASE IF NOT EXISTS uagl_db;

-- Ensure user has all permissions
GRANT ALL PRIVILEGES ON uagl_db.* TO 'uagl_user'@'%';
FLUSH PRIVILEGES;
