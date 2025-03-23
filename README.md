# Meh... another comment system

## Features



## Overview

Meh is a commenting system with a client-server architecture:

- **Server Component**: A PHP backend that handles comment storage (in SQLite), moderation, and API endpoints. It can be installed on your own server and supports multiple sites from a single installation.

- **Client Components**: A set of web components (custom HTML elements) that can be embedded in any website to display and submit comments. These components communicate with the server via a REST API.

This architecture provides several advantages:

- **Simple Integration**: Add comments to any website by including a JavaScript file and using custom HTML tags
- **Static Site Compatibility**: Works with static site generators like Hugo, Jekyll, or Eleventy
- **Multi-Site Support**: Host comments for multiple websites from a single Meh installation
- **Customizable**: Style components to match your site's design using CSS variables
- **Privacy-Focused**: Self-hosted solution that gives you full control over your data

The web components handle all the user interface aspects (comment display, form submission, admin login), while the server manages data storage, authentication, and business logic.

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

## Server Setup


* [Command Line Tool](doc/cli.md)
* [Database Setup and Upgrade](doc/migrate.md) 
* [Configuration](doc/config.md)
* [Multi-site Support](doc/multisite.md)
* [Mastodon Integration](doc/mastodon.md)
* [Email Notifications](doc/smtp.md)
* [Gravatar Integration](doc/gravatar.md)
* [Importing from Disqus](doc/disqus.md)


## Client Setup and Usage (on your blog)

* [Component Setup](doc/components.md)
  * [Styling Components](doc/styling.md)
* [Comment Moderation](doc/moderation.md)
* [Customizing Translations](doc/translations.md)
