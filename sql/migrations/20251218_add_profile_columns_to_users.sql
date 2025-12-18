-- Add profile columns to users table
-- Migration: 20251218_add_profile_columns_to_users.sql

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS photo VARCHAR(255) NULL AFTER mobile,
ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER photo,
ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL AFTER address,
ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other') NULL AFTER date_of_birth;

-- Update the comment
ALTER TABLE users COMMENT = 'Users table with profile information';
