-- Add user column and create indexes
ALTER TABLE comments ADD COLUMN user TEXT DEFAULT NULL;
CREATE INDEX idx_comments_user ON comments(user);
CREATE INDEX idx_comments_ip ON comments(ip);
