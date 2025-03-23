# Database Setup and Upgrade

The `migrate` [CLI command](cli) is essential for setting up and maintaining your Meh installation:

```
./meh migrate
```

This command:

1. Creates a new SQLite database for your site if it doesn't exist yet
2. Applies any pending database schema migrations to keep your database structure up-to-date
3. Generates a secure JWT secret if one doesn't exist

You should run this command:
- When first setting up Meh
- After updating to a new version of Meh
- When creating a new site in a multi-site setup (with the `--site` parameter)

For multi-site setups, specify the site name:

```
./meh --site blog2 migrate
```

It is important to run this command for each of your sites when upgrading to a new version of Meh.
