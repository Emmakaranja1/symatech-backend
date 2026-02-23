-- PostgreSQL initialization script for Symatech
-- This script runs when the PostgreSQL container starts for the first time

-- Create extensions if needed
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Set timezone
SET timezone = 'UTC';

-- Grant permissions to the user
GRANT ALL PRIVILEGES ON DATABASE symatech TO symatech_user;

-- Create additional schemas if needed
-- CREATE SCHEMA IF NOT EXISTS app_schema;
-- GRANT ALL ON SCHEMA app_schema TO symatech_user;
