# My Website

Laravel blog with PostgreSQL, Redis, and Nginx.

## Prerequisites

- [Git](https://git-scm.com/)
- [Docker](https://docs.docker.com/get-docker/)

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
docker compose up -d --build
```

### 4. Install PHP dependencies

```bash
docker compose exec app composer install --no-dev --optimize-autoloader
```

### 5. Generate application key

Only required on first install if `APP_KEY` is empty:

```bash
docker compose exec app php artisan key:generate
```

### 6. Run migrations

```bash
docker compose exec app php artisan migrate --force
```

### 7. Build frontend assets

```bash
docker compose exec app npm ci
docker compose exec app npm run build
```

### 8. Set up storage and cache

```bash
docker compose exec app php artisan storage:link
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### 9. Open the site

Visit [https://yuri97.com](https://yuri97.com) in your browser.

## Updating in production

After a `git pull`, run:

```bash
docker compose up -d --build
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```
