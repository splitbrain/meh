# Configuration

Meh uses a layered configuration system that allows for flexible setup across multiple sites:

1. **Default Values**: Every configuration option has a sensible default value.

2. **Environment Variables**: Defaults can be overridden by environment variables prefixed with `MEH_` (e.g., the `site_url` config can be set via the `MEH_SITE_URL` env var).

3. **`.env` File**: For convenience, you can place environment variables in a `.env` file in the project root. The project contains a `.env.example` file that you can copy and modify.

4. **Database Storage**: Site-specific configurations can stored in each site's database and take precedence over environment variables.

## Configuration Hierarchy

When Meh looks for a configuration value, it checks these sources in order:

1. Default values (lowest priority)
2. Environment variables / `.env` file
3. Database values (highest priority)

## Managing Configuration

Use the `meh config` [CLI command](cli) to view or modify database configuration values:

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
