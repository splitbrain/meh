DELETE FROM comments WHERE id IN (SELECT comment_id FROM mastodon_posts);

DROP TABLE mastodon_posts;

CREATE TABLE mastodon_posts (
    id TEXT PRIMARY KEY,
    thread_id TEXT NOT NULL REFERENCES mastodon_threads(id) ON DELETE CASCADE,
    comment_id INTEGER NOT NULL REFERENCES comments(id) ON DELETE CASCADE
);
