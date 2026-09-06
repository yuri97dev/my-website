# My Website

Laravel blog with PostgreSQL, Redis, Ollama, and Nginx.

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
- `TRUSTED_PROXIES=127.0.0.1,::1,172.18.0.1`
- `DB_PASSWORD` — PostgreSQL password
- `REDIS_PASSWORD` — Redis password (required)
- `HTTP_PORT` — port exposed by Nginx (default: `8080`)
- `OLLAMA_PORT` — port exposed by Ollama (default: `11434`)
- `OLLAMA_EMBED_MODEL` — embedding model for semantic search (default: `bge-m3`)
- `OLLAMA_CHAT_MODEL` — chat model registered in Ollama (default: `google_gemma-4-E2B-it`)
- `OLLAMA_EMBED_DIMENSION` — vector dimensions for pgvector (default: `1024` for `bge-m3`)

Also set `USER_ID` and `GROUP_ID` to the server user's IDs (`id -u` and `id -g`) to avoid volume permission issues.

### 2.2 Ollama

The stack includes [Ollama](https://docs.ollama.com/docker) for local embeddings and chat inference. By default it runs on CPU.

- **Containers** (app, worker, external stacks on the same network) reach it at `http://ollama-server:11434`
- **Host** reaches it at `http://127.0.0.1:11434` only (not exposed on LAN/internet)
- **Shared network** name: `my-website-internal` (configurable via `DOCKER_NETWORK`)

**NVIDIA GPU** (requires [NVIDIA Container Toolkit](https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/latest/install-guide.html)):

```bash
docker compose -f docker-compose.yml -f docker-compose.gpu.yml up -d --build --wait
```

**AMD GPU** (uses the `rocm` image tag per official docs):

```bash
docker compose -f docker-compose.yml -f docker-compose.amd.yml up -d --build --wait
```

After the first start, pull the embedding model:

```bash
docker exec my-website-ollama ollama pull bge-m3
```

**Gemma 4 (chat model)**

Download [google_gemma-4-E2B-it-Q4_K_M.gguf](https://huggingface.co/bartowski/google_gemma-4-E2B-it-GGUF/blob/main/google_gemma-4-E2B-it-Q4_K_M.gguf) on the host (~3.5 GB). Files go in `storage/ai_models/` (gitignored):

```bash
mkdir -p storage/ai_models
curl -L -o storage/ai_models/google_gemma-4-E2B-it-Q4_K_M.gguf \
  "https://huggingface.co/bartowski/google_gemma-4-E2B-it-GGUF/resolve/main/google_gemma-4-E2B-it-Q4_K_M.gguf"
```

Create a `Modelfile` next to the GGUF and register the model in Ollama. Recreate the container if you changed `docker-compose.yml` so the volume mount takes effect:

```bash
printf 'FROM ./google_gemma-4-E2B-it-Q4_K_M.gguf\n' > storage/ai_models/Modelfile
docker compose up -d ollama-server
docker exec my-website-ollama ollama create google_gemma-4-E2B-it -f /models/ai/Modelfile
```

Set `OLLAMA_CHAT_MODEL=google_gemma-4-E2B-it` in `.env` (already the default in `.env.example`). A GPU is strongly recommended for acceptable chat latency.

Verify the API from the host:

```bash
curl http://localhost:11434/api/tags
docker exec my-website-ollama ollama run google_gemma-4-E2B-it "Hello"
```

### 2.1 Host Nginx reverse proxy (production-like local setup)

If you run Nginx on the host machine and proxy traffic to the container port, use:

```nginx
server {
    listen 80;
    server_name yuri97.local;

    client_max_body_size 100M;

    location / {
        proxy_pass http://127.0.0.1:8080;

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
    }
}
```

Using `X-Forwarded-For $remote_addr` on the edge proxy prevents client-supplied spoofed IP chains.

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
