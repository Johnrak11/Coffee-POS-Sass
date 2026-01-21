#!/usr/bin/env sh
set -e

# Load environment variables
if [ -f backend/.env ]; then
  # Use the backend .env if it exists
  export $(grep -v '^#' backend/.env | xargs)
elif [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

echo "Deploying KafeSrok..."

# 1. Check and Create Database (using existing kafesrok-db)
# We assume kafesrok-db is running and accessible via docker exec
DB_NAME=${DB_DATABASE:-laravel}
mysql_pass=${DB_PASSWORD:-secret}

if [ -z "$DB_NAME" ]; then
  echo "DB_DATABASE is not set. Skipping DB creation check."
else
  echo "Checking database '$DB_NAME' in container 'kafesrok-db'..."
  # Use root/secret (or loaded DB_PASSWORD) as per docker-compose default
  docker exec kafesrok-db mariadb -uroot -p"$mysql_pass" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
  echo "Database check complete."
fi

# 2. Deploy Containers
# Pull not needed if building locally, but good practice if using images
# docker compose pull

echo "Building and starting containers..."
docker compose down --remove-orphans || true
docker compose up -d --build

# 3. Run Migrations
echo "Running migrations..."
# Wait for backend to be ready (simple sleep or check)
sleep 10
docker exec kafesrok-backend php artisan migrate --force
docker exec kafesrok-backend php artisan optimize:clear
docker exec kafesrok-backend php artisan config:cache
docker exec kafesrok-backend php artisan route:cache
docker exec kafesrok-backend php artisan view:cache

echo "Deployment finished! Site live at https://kafesrok.johnrak.online"
