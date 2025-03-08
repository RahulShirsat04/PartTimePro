-- Users table for both job seekers and employers
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('jobseeker', 'employer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Job seeker profiles
CREATE TABLE IF NOT EXISTS jobseeker_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    skills TEXT,
    education TEXT,
    experience TEXT,
    resume_path VARCHAR(255),
    profile_picture VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Employer profiles
CREATE TABLE IF NOT EXISTS employer_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    company_description TEXT,
    industry VARCHAR(100),
    website VARCHAR(255),
    address TEXT,
    phone VARCHAR(20),
    logo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employer_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    location VARCHAR(100),
    salary_range VARCHAR(50),
    job_type ENUM('part-time', 'temporary', 'contract') NOT NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Job applications
CREATE TABLE IF NOT EXISTS job_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT,
    jobseeker_id INT,
    status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (jobseeker_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Drop existing messages table
DROP TABLE IF EXISTS messages;

-- Create messages table with proper structure
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create index for better performance
CREATE INDEX idx_messages_users ON messages(sender_id, receiver_id);
CREATE INDEX idx_messages_read ON messages(receiver_id, is_read);

-- Add is_read column if it doesn't exist
ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_read BOOLEAN DEFAULT FALSE;

-- Add user_type column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS user_type ENUM('employer', 'jobseeker') NOT NULL DEFAULT 'jobseeker';

-- Update your user to be an employer if needed
UPDATE users SET user_type = 'employer' WHERE id = 1;  -- Assuming your user ID is 1 

-- Add profile_picture column to jobseeker_profiles if it doesn't exist
ALTER TABLE jobseeker_profiles ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255);

-- Update existing messages to have is_read set
UPDATE messages SET is_read = FALSE WHERE is_read IS NULL;

-- Add website column to employer_profiles if it doesn't exist
ALTER TABLE employer_profiles ADD COLUMN IF NOT EXISTS website VARCHAR(255); 