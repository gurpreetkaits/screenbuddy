# Production Deployment Guide

## The FFProbe "Forgetting" Issue - SOLVED ✅

### Why `config:cache` "Forgot" FFProbe

**The Problem:**
Your `.env` file contains **Mac-specific paths** that don't exist on Linux servers:
```bash
FFMPEG_PATH=/opt/homebrew/bin/ffmpeg   # ❌ Mac only
FFPROBE_PATH=/opt/homebrew/bin/ffprobe # ❌ Mac only
```

When you run `config:cache`, Laravel caches these **wrong paths**, so FFProbe fails on production!

---

## Production Setup - Step by Step

### 1️⃣ Install FFmpeg on Production Server

```bash
# SSH into your production server
ssh user@your-server.com

# Install FFmpeg
sudo apt update
sudo apt install ffmpeg -y

# Verify installation
which ffmpeg   # Should output: /usr/bin/ffmpeg
which ffprobe  # Should output: /usr/bin/ffprobe

# Test it works
ffmpeg -version
ffprobe -version
```

### 2️⃣ Update Production `.env` File

```bash
# Edit .env on production server
nano /var/www/html/screensense/.env

# Change these lines to Linux paths:
FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe

# You can remove these (not used anymore):
# FFMPEG_BINARIES=/opt/homebrew/bin/ffmpeg
# FFPROBE_BINARIES=/opt/homebrew/bin/ffprobe
```

### 3️⃣ Deploy Latest Code

```bash
cd /var/www/html/screensense

# Pull latest code
git pull origin master

# Install dependencies (if composer.json changed)
composer install --optimize-autoloader --no-dev

# Run migrations (if needed)
php artisan migrate --force

# Clear and recache config (IMPORTANT!)
php artisan config:clear
php artisan config:cache

# Clear other caches
php artisan route:clear
php artisan view:clear

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### 4️⃣ Test FFProbe Works

```bash
# Run this command to test thumbnail generation
php artisan video:thumbnail 1

# It should now show:
# FFmpeg Config:
#   FFMPEG_PATH: /usr/bin/ffmpeg
#   FFPROBE_PATH: /usr/bin/ffprobe
```

---

## How `config:cache` Works

### What Happens When You Run `php artisan config:cache`:

1. ✅ Reads all files from `config/` directory
2. ✅ Evaluates ALL `env()` calls and replaces with actual `.env` values
3. ✅ Combines everything into: `bootstrap/cache/config.php`
4. ❌ Laravel **STOPS** reading `.env` file
5. ❌ Direct `env()` calls in your code return `null`

### The Golden Rules:

✅ **DO:**
```php
// In config files (config/media-library.php):
'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),

// In your code (models, controllers, commands):
$ffmpegPath = config('media-library.ffmpeg_path');
```

❌ **DON'T:**
```php
// In your code (WRONG - won't work when cached):
$ffmpegPath = env('FFMPEG_PATH');
```

---

## Production Deployment Workflow

### When You Push New Code:

```bash
# On production server:
git pull origin master
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache  # Recache with latest config
php artisan route:cache   # Optional: cache routes too
sudo systemctl restart php8.2-fpm
```

### When You Change `.env`:

```bash
# After editing .env:
php artisan config:cache  # Must recache!
sudo systemctl restart php8.2-fpm
```

### When Debugging Config Issues:

```bash
# Clear ALL caches:
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# View current cached config:
cat bootstrap/cache/config.php | grep ffmpeg
```

---

## Local vs Production Config

### Local Development (`.env`):
```bash
# Mac paths (Homebrew)
FFMPEG_PATH=/opt/homebrew/bin/ffmpeg
FFPROBE_PATH=/opt/homebrew/bin/ffprobe

# Don't cache config locally (so .env changes work immediately)
php artisan config:clear
```

### Production Server (`.env`):
```bash
# Linux paths (APT)
FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe

# Always cache config in production (for performance)
php artisan config:cache
```

---

## Troubleshooting

### FFProbe Still Not Working?

1. **Check FFmpeg is installed:**
   ```bash
   which ffmpeg
   which ffprobe
   ```

2. **Check `.env` has correct paths:**
   ```bash
   cat .env | grep FF
   ```

3. **Check cached config:**
   ```bash
   php artisan tinker
   >>> config('media-library.ffmpeg_path')
   >>> config('media-library.ffprobe_path')
   ```

4. **Clear and recache:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

5. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Recent Fixes Applied

✅ Fixed `RegenerateThumbnail` command to use `config()` instead of `env()`
✅ Fixed video streaming memory issues (chunks limited to 10MB)
✅ Removed trim functionality from frontend

**Last Updated:** 2025-12-08
