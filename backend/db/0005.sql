-- Add parent column to comments table
ALTER TABLE comments ADD COLUMN parent INTEGER DEFAULT NULL REFERENCES comments(id) ON DELETE SET NULL;
