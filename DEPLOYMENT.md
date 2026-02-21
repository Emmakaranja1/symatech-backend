# Symatech Backend - Simple Render Deployment Guide

## Quick Deployment Steps

### 1. Push to GitHub
```bash
git add .
git commit -m "Ready for Render deployment"
git push origin main
```

### 2. Create Render Services

#### Web Service
1. Go to Render Dashboard → New → Web Service
2. Connect your GitHub repository
3. Configure:
   - **Name**: symatech-backend
   - **Environment**: PHP
   - **Runtime**: PHP 8.2
   - **Plan**: Starter
   - **Health Check Path**: `/api/health`
   - **Auto-Deploy**: Enabled

#### PostgreSQL Database
1. Go to Render Dashboard → New → PostgreSQL
2. Configure:
   - **Name**: symatech-db
   - **Plan**: Starter
   - **Database Name**: symatech
   - **User**: symatech_user

#### Redis
1. Go to Render Dashboard → New → Redis
2. Configure:
   - **Name**: symatech-redis
   - **Plan**: Starter

### 3. Set Environment Variables

In your Web Service settings, add these environment variables:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com
LOG_LEVEL=error

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-hostname.onrender.com
DB_PORT=5432
DB_DATABASE=symatech
DB_USERNAME=symatech_user
DB_PASSWORD=your_db_password

# Redis
REDIS_HOST=your-redis-hostname.onrender.com
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# JWT
JWT_SECRET=your_jwt_secret_key_here
JWT_TTL=1440
JWT_REFRESH_TTL=20160
JWT_ALGORITHM=HS256

# M-PESA (if using)
MPESA_CONSUMER_KEY=your_mpesa_consumer_key
MPESA_CONSUMER_SECRET=your_mpesa_consumer_secret
MPESA_PASSKEY=your_mpesa_passkey
MPESA_SHORTCODE=your_mpesa_shortcode
MPESA_ENVIRONMENT=production
MPESA_CALLBACK_URL=https://your-app-name.onrender.com/api/callbacks/mpesa

# Flutterwave (if using)
FLUTTERWAVE_SECRET_KEY=your_flutterwave_secret_key
FLUTTERWAVE_PUBLIC_KEY=your_flutterwave_public_key
FLUTTERWAVE_ENCRYPTION_KEY=your_flutterwave_encryption_key
FLUTTERWAVE_ENVIRONMENT=production
FLUTTERWAVE_CALLBACK_URL=https://your-app-name.onrender.com/api/callbacks/flutterwave
```

### 4. Deploy

1. Push any changes to GitHub
2. Render will automatically detect and deploy
3. Monitor the deployment logs
4. Test the health check endpoint: `https://your-app-name.onrender.com/api/health`

### 5. Test Your API

```bash
# Health check
curl https://your-app-name.onrender.com/api/health

# Test registration
curl -X POST https://your-app-name.onrender.com/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Test login
curl -X POST https://your-app-name.onrender.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

## That's It!

Your Laravel backend is now deployed and ready to use. The `render.yaml` file handles all the build and deployment configuration automatically.

## Optional: Manual Build Settings

If you prefer not to use `render.yaml`, you can set these manually in the Render dashboard:

- **Build Command**: 
  ```bash
  composer install --no-dev --optimize-autoloader && cp .env.example .env && php artisan key:generate && php artisan storage:link && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
  ```
- **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`
