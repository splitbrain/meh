# Meh...


A simple comment system for your static (or non-static) site.


## Quick Start

    cd backend
    composer install
    cd ../frontend
    npm install
    npm run build
    cd ..
    ./meh migrate
    ./meh config site_url https://myblog.example.com
    ./meh config admin_password supersecret

Point your Apache to the `public` directory and you're good to go.

## Command Line Tool

Meh comes with a command line tool that helps you manage your comment system. The `meh` command provides utilities for database management, configuration, and importing comments from other platforms.

To see available commands:

    ./meh

All commands support the `--site` (or `-s`) parameter to specify which site to operate on. This is useful if you're managing multiple sites with the same Meh installation.

    ./meh --site myblog config

If not specified, the default site name "meh" will be used. Site names have to be lowercase and can only contain letters, numbers, and underscores. Each site corresponds to a separate SQLite database.

## Database Migration

The `migrate` command is essential for setting up and maintaining your Meh installation:

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

## Configuration

Meh uses a layered configuration system that allows for flexible setup across multiple sites:

1. **Default Values**: Every configuration option has a sensible default value.

2. **Environment Variables**: Defaults can be overridden by environment variables prefixed with `MEH_` (e.g., the `site_url` config can be set via the `MEH_SITE_URL` env var).

3. **`.env` File**: For convenience, you can place environment variables in a `.env` file in the project root. The project contains a `.env.example` file that you can copy and modify.

4. **Database Storage**: Site-specific configurations can stored in each site's database and take precedence over environment variables.

### Configuration Hierarchy

When Meh looks for a configuration value, it checks these sources in order:

1. Default values (lowest priority)
2. Environment variables / `.env` file
3. Database values (highest priority)

### Managing Configuration

Use the `meh config` command to view or modify database configuration values:

```
# View all configuration values and their sources
./meh config

# View a specific configuration value
./meh config site_url

# Set a configuration value in the database
./meh config site_url https://example.com

# Remove a database configuration (revert to environment or default)
./meh config site_url ""
```

For multi-site setups, use the `--site` parameter:

```
./meh --site blog1 config site_url https://blog1.example.com
./meh --site blog2 config site_url https://blog2.example.com
```
 
Configs set up via Environment always apply to all sites, while configs set up via the database are site-specific.

## Mastodon Integration

Meh includes built-in support for integrating with Mastodon, allowing you to import replies to your Mastodon posts as comments on your site.

### Configuration

To set up Mastodon integration:

1. Configure your Mastodon account in your site settings:

```
./meh config mastodon_account "@yourusername@instance.social"
```

2. If you're using GoToSocial, even read access requires an API token. The easiest way to get one is to use the [Access Token Generator for Mastodon API](https://takahashim.github.io/mastodon-access-token/). You only need the `read` scope.

```
./meh config mastodon_token "your-api-token"
```

3. Set up a cron job to periodically fetch new posts and replies:

```
# Run every hour to check for new Mastodon posts and replies
0 * * * * /path/to/meh mastodon
```

For multi-site setups you need to set up a `mastodon_account` config and cron job for each site. But you can have all sites use the same account.

### How It Works

The Mastodon integration:

1. Fetches posts from your configured Mastodon account
2. Identifies posts that link to your site
3. Tracks these posts in the database
4. Periodically checks for replies to these posts
5. Imports replies as comments on the corresponding blog post

This creates a seamless bridge between discussions on your blog and on the Fediverse.

### Manual Import

You can manually trigger the Mastodon import process at any time:

```
./meh mastodon
```

This is useful for testing or for an initial import of existing conversations.

## Components

* [meh-form](./frontend/src/components/meh-form/readme.md)
* [meh-comments](./frontend/src/components/meh-comments/readme.md)
* [meh-login](./frontend/src/components/meh-login/readme.md)
