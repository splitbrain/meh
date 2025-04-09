# ![Meh Logo](meh.svg) Meh…

## …another comment system

Meh…

* …is a simple, no-frills commenting system
* …has threaded comments
* …is self-hosted
* …has multi-site support from a single installation
* …has multi-language support
* …has Mastodon integration
* …has Disqus import
* …is simple enough to understand and modify
* …made by [splitbrain](https://www.splitbrain.org)

## Overview

Meh is a commenting system with a client-server architecture:

- **Server Component**: A PHP backend that handles comment storage (in SQLite), moderation, and API endpoints. It can be
  installed on your own server and supports multiple sites from a single installation.

- **Client Components**: A set of web components (custom HTML elements) that can be embedded in any website to display
  and submit comments. These components communicate with the server via a REST API.

## Quick Start

A quick and dirty way to get started is to use the built-in PHP server on your local machine.

```bash
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
```

You can now browse this documentation at `http://localhost:8000`. It will also serve the Meh components and the API.

In your blog, add the following to your HTML:

```html
<!-- Add a comment form -->
<script type="module" src="http://localhost:8000/meh/meh.esm.js"></script>

<meh-form>
    <meh-mastodon></meh-mastodon>
    <meh-login></meh-login>
</meh-form>
<meh-comments></meh-comments>
```

This should give you a rough idea how Meh works. For a production setup, you should point your web server at the `public` directory.

More details on how to set up the server can be found in the [Server Setup](doc/server.md) section.

## Server Setup

* [Server Setup](doc/server.md)
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

## Alternatives

Check out [open-source self-hosted comments for a static website](https://lisakov.com/projects/open-source-comments/) for a big list of similar projects.
