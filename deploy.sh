#!/bin/bash

# Stop existing containers
docker-compose down

# Build images without cache to ensure latest code
docker-compose build --no-cache

# Start containers in detached mode
docker-compose up -d

# Run migrations and seeders if needed
echo "Waiting for database to be ready..."
sleep 30
docker-compose exec -T backend php artisan migrate --force
docker-compose exec -T backend php artisan optimize:clear
docker-compose exec -T backend php artisan config:cache
docker-compose exec -T backend php artisan route:cache
docker-compose exec -T backend php artisan view:cache

echo "Deployment completed successfully!"
