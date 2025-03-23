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

If not specified, the default site name "meh" will be used.

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

Use the `meh config` command to view or modify configuration values:

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

## Components

* [meh-form](./frontend/src/components/meh-form/readme.md)
* [meh-comments](./frontend/src/components/meh-comments/readme.md)
* [meh-login](./frontend/src/components/meh-login/readme.md)
