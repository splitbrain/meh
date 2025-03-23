# Command Line Tool

Meh comes with a command line tool that helps you manage your comment system. The `meh` command provides utilities for database management, configuration, and importing comments from other platforms.

To see available commands:

    ./meh

All commands support the `--site` (or `-s`) parameter to specify which site to operate on. This is useful if you're managing multiple sites with the same Meh installation.

    ./meh --site myblog config

If not specified, the default site name "meh" will be used. Site names have to be lowercase and can only contain letters, numbers, and underscores. Each site corresponds to a separate SQLite database.
