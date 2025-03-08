-- Add is_read column to messages table if it doesn't exist
ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_read BOOLEAN DEFAULT FALSE;

-- Update any existing messages to have is_read set to FALSE
UPDATE messages SET is_read = FALSE WHERE is_read IS NULL; 