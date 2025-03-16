CREATE TABLE comments {
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post TEXT NOT NULL, -- path of the post
    author TEXT NOT NULL,
    ip TEXT NOT DEFAULT '',
    email TEXT DEFAULT '',
    website TEXT DEFAULT '',
    text TEXT NOT NULL,
    html TEXT NOT NULL,
    avatar TEXT DEFAULT '',
    status ENUM('pending', 'approved', 'spam', 'deleted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
};

