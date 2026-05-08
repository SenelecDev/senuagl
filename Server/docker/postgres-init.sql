-- PostgreSQL initialization script
-- This script runs automatically when the database container starts

-- Create the database (already created by POSTGRES_DB env var, but explicit here)
-- Database uagl_db is created automatically

-- The user is created automatically by POSTGRES_USER and POSTGRES_PASSWORD env vars

-- Grant all privileges to the user
GRANT ALL PRIVILEGES ON DATABASE uagl_db TO uagl_user;

-- Connect to the database and grant schema privileges
\c uagl_db;
GRANT ALL PRIVILEGES ON SCHEMA public TO uagl_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO uagl_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON SEQUENCES TO uagl_user;
