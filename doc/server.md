# Server Setup

There are different ways to get Meh up and running. You can use Docker, Docker Compose, or set it up manually on your server.

## Using Docker

Meh is available as a Docker image for easy deployment. It's based on the official PHP image and includes everything you need to run Meh.

Images are available on the [GitHub Container Registry (GHCR)](https://github.com/splitbrain/meh/pkgs/container/meh) and [Docker Hub](https://hub.docker.com/r/splitbrain/meh).

To start the container use:

```bash
docker run --name meh -p 8080:80 -v meh-data:/app/data splitbrain/meh:latest
```

To run the [command line tool](cli.md) use:

```bash
docker exec meh /app/meh
```

Use the [command line tool](cli.md) to [initialize the database](migrate.md) and configure your installation. [Configuration](configuration.md) can also be done via environment variables.


## Using Docker Compose

Docker Compose makes it easy to define and manage your Meh deployment configuration. Here's a minimal example:

```yaml
services:
  meh:
    image: splitbrain/meh:latest
    ports:
      - "8080:80"
    volumes:
      - ./data:/app/data
    restart: unless-stopped
```

Start the container with:

```bash
docker-compose up -d
```

To run the [command line tool](cli.md) use:

```bash
docker-compose exec meh /app/meh
```

Use the [command line tool](cli.md) to [initialize the database](migrate.md) and configure your installation. [Configuration](configuration.md) can also be done via environment variables.

## Manual Setup (production)

Of course, you can run the whole thing the classical way.

### Requirements

- PHP 8.1 or higher
- SQLite extension for PHP
- Web server (Apache, Nginx, etc.)

### Installation Steps

1. Download the latest meh-release.zip from https://github.com/splitbrain/meh/releases

2. Unzip the archive on your server
   ```bash
   unzip meh-release.zip
   ```
3. Point the document root of your web server to the `public` directory

Use the [command line tool](cli.md) to [initialize the database](migrate.md) and configure your installation. [Configuration](configuration.md) can also be done via environment variables.

### URL Rewriting

Meh requires URL rewriting to work correctly. There's a `.htaccess` file included that handles this for Apache. For other web servers, you need to configure URL rewriting manually.


## Manual Setup (development)

If you want to tinker with the code, you will need to use composer and node to install and build the dependencies. 

### Requirements

- PHP 8.1 or higher
- SQLite extension for PHP
- Composer (for installation)
- Node.js and npm (for building the frontend)
- Web server (Apache, Nginx, etc.)

### Installation Steps

1. Clone the repository:
   ```bash
   git clone https://github.com/splitbrain/meh.git
   cd meh
   ```

2. Install backend dependencies:
   ```bash
   cd backend
   composer install
   cd ..
   ```

3. Build the frontend:
   ```bash
   cd frontend
   npm install
   npm run build
   cd ..
   ```

4. Use the PHP development server  your web server to the `public` directory
   ```bash
   php -S localhost:8000 -t public
   ```

Use the [command line tool](cli.md) to [initialize the database](migrate.md) and configure your installation. [Configuration](configuration.md) can also be done via environment variables.
