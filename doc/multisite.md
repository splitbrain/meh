# Multi-site Support

Meh includes built-in support for managing multiple sites from a single installation. This allows you to host comments for multiple websites or blogs using a single instance of Meh.

## How It Works

Each site in Meh:

- Has its own SQLite database file (stored in the configured `db_path` directory)
- Can have its own site-specific configuration
- Can be managed separately via the command line tool

The site name is used as the database filename (e.g., `blog1.sqlite`, `forum.sqlite`).

## Creating a New Site

To create a new site, simply run the [migrate command](migrate) with the `--site` parameter:

```
./meh --site blog2 migrate
```
## Site-specific Configuration

Each site can have its own configuration values stored in its database.

See the [Configuration documentation](config.md) for more details.

## Using the CLI with Multiple Sites

All CLI commands support the `--site` (or `-s`) parameter:

See the [CLI documentation](cli.md) for more information.

## Client Integration

When embedding the Meh components in your site, you need to specify the site name in the API URL:

```html
<meh-comments
  backend="https://comments.example.com/"
  site="blog1"
  post="/path/to/post">
</meh-comments>
```

## Environment Variables vs. Database Configuration

- Environment variables (or `.env` file settings) apply to all sites
- Database configurations are site-specific and override environment variables

This allows you to set common defaults via environment variables while customizing specific settings per site.

## Cron Jobs for Multiple Sites

If you're using features like [Mastodon integration](mastodon.md), you'll need separate cron jobs for each site:

```
# Run Mastodon import for each site hourly
0 * * * * cd /path/to/meh && ./meh --site blog1 mastodon
0 * * * * cd /path/to/meh && ./meh --site blog2 mastodon
```
