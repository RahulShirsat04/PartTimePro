-- Add website column to employer_profiles table if it doesn't exist
ALTER TABLE employer_profiles ADD COLUMN IF NOT EXISTS website VARCHAR(255); 