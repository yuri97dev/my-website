# My Website

Laravel blog with PostgreSQL, Redis, and Nginx.

## Prerequisites

- [Git](https://git-scm.com/)
- [Docker](https://docs.docker.com/get-docker/)
- PHP, Composer, and Node.js (to run install/build commands on the host — the project directory is bind-mounted into the containers)

## Production

Run the steps below in order.

### 1. Clone the repository

```bash
git clone git@github.com:yuri97dev/my-website.git
cd my-website
```

### 2. Configure environment variables

```bash
cp .env.example .env
```

Edit `.env` and set at least:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://yuri97.com`
- `DB_PASSWORD` — PostgreSQL password
- `REDIS_PASSWORD` — Redis password (required)
- `HTTP_PORT` — port exposed by Nginx (default: `8080`)

Also set `USER_ID` and `GROUP_ID` to the server user's IDs (`id -u` and `id -g`) to avoid volume permission issues.

### 3. Start the containers

```bash
docker compose up -d --build --wait
```

The `--wait` flag blocks until all healthchecks pass (PostgreSQL, Redis, and PHP-FPM), so Nginx does not start before the app is ready.

### 4. Install and build

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate   # first install only, if APP_KEY is empty
php artisan migrate --force
npm ci
npm run build
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Open the site

Visit [https://yuri97.com](https://yuri97.com) in your browser.

## Updating in production

After a `git pull`, run:

```bash
docker compose up -d --build --wait
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
