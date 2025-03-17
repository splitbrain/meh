CREATE TABLE mastodon_posts (
    uri TEXT PRIMARY KEY,
    comment_id INTEGER NOT NULL,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
);
