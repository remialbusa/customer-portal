#!/bin/bash
# ──────────────────────────────────────────────────────────────
# deploy.sh — cPanel deployment script
#
# Run this ON THE SERVER after uploading files, or call it from
# GitHub Actions SSH step. Expects to be run from the portal/
# directory (or pass the path as $1).
# ──────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="${1:-$(pwd)}"
cd "$APP_DIR"

echo "▶ Deploying from: $(pwd)"
echo "▶ PHP: $(php -v | head -1)"

# ── 1. Dependencies ──────────────────────────────────────────
echo ""
echo "── Installing PHP dependencies (production) ──"
composer install --no-dev --no-progress --prefer-dist --optimize-autoloader

# ── 2. Environment ──────────────────────────────────────────
echo ""
echo "── Checking .env ──"
if [ ! -f .env ]; then
    echo "⚠  No .env found — copying from .env.production"
    if [ -f .env.production ]; then
        cp .env.production .env
    elif [ -f .env.example ]; then
        cp .env.example .env
        php artisan key:generate --force
        echo "⚠  Generated new APP_KEY — update .env with production values!"
    else
        echo "❌ No .env or .env.example found. Aborting."
        exit 1
    fi
fi

# ── 3. Database ─────────────────────────────────────────────
echo ""
echo "── Running migrations ──"
php artisan migrate --force

# ── 4. Caches ───────────────────────────────────────────────
echo ""
echo "── Building caches ──"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache 2>/dev/null || true

# ── 5. Permissions ──────────────────────────────────────────
echo ""
echo "── Fixing permissions ──"
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod 664 database/database.sqlite 2>/dev/null || true

# ── 6. Cleanup ──────────────────────────────────────────────
echo ""
echo "── Clearing old caches ──"
php artisan cache:clear 2>/dev/null || true

# ── Done ─────────────────────────────────────────────────────
echo ""
echo "✅ Deployment complete!"
echo "   URL: $(grep APP_URL .env | cut -d= -f2 | tr -d '"')"
