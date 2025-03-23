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




## Components

* [meh-form](./frontend/src/components/meh-form/readme.md)
* [meh-comments](./frontend/src/components/meh-comments/readme.md)
* [meh-login](./frontend/src/components/meh-login/readme.md)
