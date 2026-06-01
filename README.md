# Inventory Manager — Backend API

A multi-tenant dealership inventory management platform built with **Laravel 12** and **PostgreSQL**. It provides AI-powered inventory listing generation, a CRM pipeline, real-time chat widgets, and external API integrations for dealer networks.

## Tech Stack

| Layer        | Technology                                              |
| ------------ | ------------------------------------------------------- |
| Framework    | Laravel 12 (PHP ≥ 8.2)                                  |
| Database     | PostgreSQL 15+                                          |
| Queue        | Database driver (`inventory`, `default` queues)         |
| WebSockets   | Laravel Reverb                                          |
| Auth         | Laravel Sanctum (token-based SPA auth)                  |
| Permissions  | Custom tenant-scoped RBAC + Spatie (legacy Spatie roles) |
| AI           | OpenRouter (LLM), OpenAI (embeddings), Groq             |
| File Storage | Local disk (configurable to S3-compatible)              |
| PDF          | barryvdh/laravel-dompdf                                 |
| Image        | Intervention Image                                      |

---

## Prerequisites

- **PHP** ≥ 8.2 with extensions: `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd` or `imagick`
- **Composer** ≥ 2.x
- **PostgreSQL** ≥ 15
- **Node.js** ≥ 18 (only needed if building frontend assets)

---

## Local Development Setup

### 1. Clone & Install

```bash
git clone <repository-url> inventory-manager-backend
cd inventory-manager-backend
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configure Environment

Edit `.env` and set the database connection to PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=inventory_manager_backend
DB_USERNAME=your_pg_user
DB_PASSWORD=your_pg_password
```

### 3. Create Database & Seed

```bash
# Create the PostgreSQL database first
createdb inventory_manager_backend

# Run all migrations and seed the demo data
php artisan migrate:fresh --seed
```

This seeds the database with the full demo dataset — users, tenants, inventory items, leads, permissions, roles, chat conversations, and all related data.

### 4. Start Development Services

Use the included helper script to launch the dev server and queue worker:

```bash
chmod +x start.sh
./start.sh
```

This opens two terminal tabs:
- **SERVER** — `php artisan serve` (http://localhost:8000)
- **QUEUE** — `php artisan queue:work --queue=inventory,default`

To stop all services:

```bash
./stop.sh
```

### 5. Optional Services

Uncomment lines in `start.sh` if you need:

```bash
# WebSocket server (real-time chat / notifications)
php artisan reverb:start

# Telegram bot webhook tunnel (for dev)
php artisan telegram:ngrok
```

---

## Environment Variables Reference

### Required

| Variable            | Description                          | Example                               |
| ------------------- | ------------------------------------ | ------------------------------------- |
| `APP_KEY`           | Application encryption key           | Auto-generated via `key:generate`     |
| `APP_URL`           | Backend URL                          | `https://api.yourdomain.com`          |
| `FRONTEND_URL`      | Frontend app URL (CORS)              | `https://app.yourdomain.com`          |
| `DB_CONNECTION`     | Database driver                      | `pgsql`                               |
| `DB_HOST`           | Database host                        | `127.0.0.1`                           |
| `DB_PORT`           | Database port                        | `5432`                                |
| `DB_DATABASE`       | Database name                        | `inventory_manager_backend`           |
| `DB_USERNAME`       | Database user                        | `postgres`                            |
| `DB_PASSWORD`       | Database password                    | `secret`                              |

### AI / LLM

| Variable                  | Description                     | Example                                          |
| ------------------------- | ------------------------------- | ------------------------------------------------ |
| `OPENROUTER_API_KEY`      | OpenRouter API key for LLM      | `sk-or-...`                                      |
| `OPENROUTER_MODEL`        | Default LLM model               | `meta-llama/llama-3.3-70b-instruct:free`         |
| `OPENROUTER_IMAGE_MODEL`  | Image generation model          | `bytedance-seed/seedream-4.5`                    |
| `OPENAI_API_KEY`          | OpenAI key for embeddings       | `sk-...`                                         |
| `GROQ_API_KEY`            | Groq key for fast inference     | `gsk_...`                                        |

### WebSockets (Reverb)

| Variable             | Description                  | Example                  |
| -------------------- | ---------------------------- | ------------------------ |
| `REVERB_APP_ID`      | Reverb application ID        | `inventory-generator`    |
| `REVERB_APP_KEY`     | Reverb app key               | `inventory-key`          |
| `REVERB_APP_SECRET`  | Reverb app secret            | `inventory-secret`       |
| `REVERB_HOST`        | Reverb server host           | `0.0.0.0`               |
| `REVERB_PORT`        | Reverb server port           | `8080`                   |

### Telegram Bot (Optional)

| Variable                    | Description                | Example              |
| --------------------------- | -------------------------- | -------------------- |
| `TELEGRAM_BOT_TOKEN`        | Bot token from BotFather   | `123456:ABC-DEF...`  |
| `TELEGRAM_BOT_USERNAME`     | Bot username               | `my_dealer_bot`      |
| `TELEGRAM_WEBHOOK_SECRET`   | Webhook verification       | `random-secret`      |

### Queue & Cache

| Variable              | Description                  | Default      |
| --------------------- | ---------------------------- | ------------ |
| `QUEUE_CONNECTION`    | Queue driver                 | `database`   |
| `CACHE_STORE`         | Cache driver                 | `database`   |
| `SESSION_DRIVER`      | Session driver               | `database`   |

---

## Production Deployment

### 1. Server Requirements

- Ubuntu 22.04+ / Debian 12+ (or any Linux with PHP 8.2+)
- Nginx or Apache
- PostgreSQL 15+
- Supervisor (for queue workers)
- SSL certificate (Let's Encrypt or similar)

### 2. Install System Dependencies

```bash
sudo apt update && sudo apt install -y \
  php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml \
  php8.2-bcmath php8.2-curl php8.2-gd php8.2-tokenizer \
  php8.2-zip php8.2-cli \
  postgresql postgresql-contrib \
  nginx supervisor composer unzip git
```

### 3. Deploy Application

```bash
# Clone to web directory
cd /var/www
git clone <repository-url> inventory-manager-backend
cd inventory-manager-backend

# Install production dependencies
composer install --no-dev --optimize-autoloader

# Set up environment
cp .env.example .env
php artisan key:generate

# Edit .env with production values (see Environment Variables above)
nano .env
```

Set these in your production `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
FRONTEND_URL=https://app.yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=inventory_manager_backend
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 4. Database Setup

```bash
# Create database and user
sudo -u postgres psql <<SQL
CREATE USER inventory_user WITH PASSWORD 'strong_password_here';
CREATE DATABASE inventory_manager_backend OWNER inventory_user;
GRANT ALL PRIVILEGES ON DATABASE inventory_manager_backend TO inventory_user;
SQL

# Run migrations and seed demo data
php artisan migrate:fresh --seed

# Or for subsequent deployments (migrations only, no data reset):
php artisan migrate --force
```

### 5. File Permissions

```bash
sudo chown -R www-data:www-data /var/www/inventory-manager-backend
sudo chmod -R 755 /var/www/inventory-manager-backend
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Storage Link

```bash
php artisan storage:link
```

### 7. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 8. Nginx Configuration

Create `/etc/nginx/sites-available/inventory-api`:

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/inventory-manager-backend/public;

    index index.php;

    charset utf-8;
    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/inventory-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 9. SSL with Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.yourdomain.com
```

### 10. Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/inventory-worker.conf`:

```ini
[program:inventory-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/inventory-manager-backend/artisan queue:work --queue=inventory,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/inventory-manager-backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start inventory-worker:*
```

### 11. WebSocket Server (Optional — Reverb)

If your frontend uses real-time features, add a Supervisor config for Reverb:

Create `/etc/supervisor/conf.d/inventory-reverb.conf`:

```ini
[program:inventory-reverb]
command=php /var/www/inventory-manager-backend/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/inventory-manager-backend/storage/logs/reverb.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start inventory-reverb
```

Proxy WebSocket traffic through Nginx by adding to your server block:

```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 60s;
}
```

---

## Deployment Updates

For subsequent deployments after the initial setup:

```bash
cd /var/www/inventory-manager-backend

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run new migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers to pick up new code
sudo supervisorctl restart inventory-worker:*

# Restart Reverb if running
sudo supervisorctl restart inventory-reverb
```

---

## Utility Scripts

| Script     | Purpose                                           |
| ---------- | ------------------------------------------------- |
| `start.sh` | Launch dev server + queue worker in terminal tabs  |
| `stop.sh`  | Kill all running artisan dev processes             |
| `clean.sh` | Clear all Laravel caches (config, routes, views)   |

---

## Project Structure

```
app/
├── Http/Controllers/    # 40+ controllers (Auth, Inventory, CRM, Chat, etc.)
├── Models/              # 68 Eloquent models
├── Jobs/                # 11 queue jobs (AI generation, imports, etc.)
├── Services/            # Business logic layer
└── Events/              # Real-time broadcast events

database/
├── migrations/          # Schema migrations (PostgreSQL)
└── seeders/             # Full database seeders (demo data)

routes/
├── web.php              # Main app routes (Sanctum-protected)
├── api.php              # External API routes (token-auth)
├── channels.php         # WebSocket channel authorization
└── console.php          # Artisan command schedules
```

---

## Troubleshooting

**Queue jobs not processing?**
```bash
# Check Supervisor status
sudo supervisorctl status

# View worker logs
tail -f storage/logs/worker.log

# Restart workers
sudo supervisorctl restart inventory-worker:*
```

**Permission errors after deployment?**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**Stale config after `.env` changes?**
```bash
php artisan config:cache
# or clear everything:
./clean.sh
```

**Database connection refused?**
```bash
# Verify PostgreSQL is running
sudo systemctl status postgresql

# Test connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected';"
```
