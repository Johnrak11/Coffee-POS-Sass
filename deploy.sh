#!/usr/bin/env sh
set -e

# Load environment variables
if [ -f backend/.env ]; then
  # Use the backend .env if it exists
  # Filter out comments (including indented) and empty lines
  export $(grep -v '^[[:space:]]*#' backend/.env | grep -v '^[[:space:]]*$' | xargs)
elif [ -f .env ]; then
  export $(grep -v '^[[:space:]]*#' .env | grep -v '^[[:space:]]*$' | xargs)
fi

echo "Deploying KafeSrok..."

# 1. Deploy Containers (DB will be created automatically by Docker)
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
