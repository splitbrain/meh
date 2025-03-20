CREATE table mastodon_threads
(
    id      TEXT PRIMARY KEY,
    account TEXT NOT NULL,
    url     TEXT NOT NULL,
    uri     TEXT NOT NULL,
    post    TEXT NOT NULL, -- path of the post
    created_at TIMESTAMP
)
