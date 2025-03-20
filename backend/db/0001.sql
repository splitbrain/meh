CREATE TABLE comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post TEXT NOT NULL, -- path of the post
    author TEXT NOT NULL,
    ip TEXT DEFAULT '',
    email TEXT DEFAULT '',
    website TEXT DEFAULT '',
    text TEXT NOT NULL,
    html TEXT NOT NULL,
    avatar TEXT DEFAULT '',
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'spam', 'deleted')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

