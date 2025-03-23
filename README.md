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

## Components

* [meh-form](./frontend/src/components/meh-form/readme.md)
* [meh-comments](./frontend/src/components/meh-comments/readme.md)
* [meh-login](./frontend/src/components/meh-login/readme.md)
