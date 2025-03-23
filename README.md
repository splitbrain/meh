# Meh... another comment system

## Features



## Overview

## Devel Quick Start

You can use this to get a development environment up and running quickly. This is not suitable for production use. But should answer all your questions about how Meh works.

    cd backend
    composer install
    cd ../frontend
    npm install
    npm run build
    cd ..
    cp .env.example .env
    $EDITOR .env
    ./meh migrate
    php -s localhost:8000 -t public

You can then use `http://localhost:8000/` as the base URL for all components.

## Server


* [Command Line Tool](doc/cli.md)
* [Database Setup and Upgrade](doc/migrate.md) 
* [Configuration](doc/config.md)
* [Multi-site Support](doc/multisite.md)
* [Mastodon Integration](doc/mastodon.md)
* [Email Notifications](doc/smtp.md)
* [Importing from Disqus](doc/disqus.md)


## Client (your blog)

* [meh-form](./frontend/src/components/meh-form/readme.md)
* [meh-comments](./frontend/src/components/meh-comments/readme.md)
* [meh-login](./frontend/src/components/meh-login/readme.md)
