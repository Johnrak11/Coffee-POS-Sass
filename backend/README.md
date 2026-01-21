# Coffee POS SaaS - Backend

A Laravel-based API backend for a modern Coffee Shop Point of Sale system with multi-tenant support, product options/modifiers, and real-world cafe ordering features.

## Features

- 🏪 **Multi-tenant Architecture** - Support multiple coffee shops
- 🛒 **Advanced Product Options** - Size, Sugar, Ice, Toppings customization
- 📱 **QR Code Ordering** - Guest ordering via QR codes
- 🧑‍🍳 **Kitchen Display System** - Real-time order management
- 💳 **Payment Integration** - KHQR (Cambodian QR Payment) support
- 👥 **Role-based Access** - Super Admin, Shop Admin, Staff, Barista
- 📊 **Analytics & Reporting** - Sales tracking and insights

## Prerequisites

- PHP >= 8.4
- Docker & Docker Compose
- Composer

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Johnrak11/Coffee-POS-Sass.git
cd Coffee-POS-Sass/backend
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

**Crucial Production Settings (Docker):**
Ensure these are set in `docker-compose.yml` or your `.env`:

```env
APP_NAME=KafeSrok
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kafesrok.johnrak.online

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=kafesrok_production
DB_USERNAME=laravel
DB_PASSWORD=secret

SANCTUM_STATEFUL_DOMAINS=kafesrok.johnrak.online
SESSION_DOMAIN=.johnrak.online
```

### 3. Docker Deployment (Recommended)

Navigate to the project root and run the deployment script:

```bash
./deploy.sh
```

This script will:

1. Build `kafesrok-backend` (PHP 8.4 + Nginx) and `kafesrok-frontend`.
2. Start MariaDB in a container named `kafesrok-db`.
3. Auto-run migrations and clear caches.

### 4. Manual Commands (Docker Exec)

If you need to run commands manually inside the container:

**Run Migrations:**

```bash
docker exec kafesrok-backend php artisan migrate --force
```

**Seed Database (Force required in production):**

```bash
# General Seeding
docker exec kafesrok-backend php artisan db:seed --force

# Specific Class (Use single quotes to avoid shell escaping issues)
docker exec kafesrok-backend php artisan db:seed --class='Database\Seeders\ShopSeeder' --force
```

**Clear Cache:**

```bash
docker exec kafesrok-backend php artisan optimize:clear
```

## Default Credentials

### Super Admin

- Email: `admin@example.com` (or from `.env`)
- Password: `password` (or from `.env`)

### Shop Terminal

- Shop Slug: `lucky-cafe`
- Password: `123456`

### Staff Users

- Cashier: `cashier@luckycafe.com` / `password`
- Barista: `barista@luckycafe.com` / `password`

## API Routes

### Guest Routes (No Auth)

- `POST /api/guest/scan/{qrToken}` - Scan table QR code
- `GET /api/guest/menu/{shopSlug}` - Get shop menu
- `POST /api/guest/cart/add` - Add item to cart
- `POST /api/guest/checkout` - Create order

### Staff Routes (Auth Required)

- `POST /api/staff/auth` - Staff login
- `GET /api/staff/orders` - Get orders
- `POST /api/staff/orders` - Create POS order
- `GET /api/staff/kitchen/{shopSlug}/orders` - Get kitchen orders
- `POST /api/staff/kitchen/orders/{id}/status` - Update order status

### Admin Routes

- `GET /api/staff/admin/{shopSlug}/menu/products` - List products
- `POST /api/staff/admin/{shopSlug}/menu/products` - Create product
- `PUT /api/staff/admin/{shopSlug}/menu/products/{id}` - Update product
- `DELETE /api/staff/admin/{shopSlug}/menu/products/{id}` - Delete product

See `routes/api.php` for complete route list.

## Configuration

### Adding a New Shop

1. Login as Super Admin
2. Navigate to Super Admin portal
3. Click "Add Shop"
4. Fill in shop details
5. Set terminal password

### Configuring KHQR Payment

1. Get your Bakong API token
2. Add to `.env`:
    ```env
    BAKONG_TOKEN=your_token_here
    ```
3. Update shop settings with merchant info

### Setting Up Product Options

Products can have multiple option groups (Size, Sugar, Ice, etc.):

1. Go to Admin → Products
2. Create/Edit a product
3. Click "+ Add Option"
4. Set:
    - **Group**: e.g., "Size", "Sugar Level"
    - **Option**: e.g., "Large", "50%"
    - **Price**: Extra charge (can be $0)
5. Save product

Example:

```
Group: "Size"     Option: "Small"   Price: $0.00
Group: "Size"     Option: "Medium"  Price: $0.50
Group: "Size"     Option: "Large"   Price: $1.00
Group: "Sugar"    Option: "0%"      Price: $0.00
Group: "Sugar"    Option: "50%"     Price: $0.00
Group: "Sugar"    Option: "100%"    Price: $0.00
```

## Troubleshooting

### Issue: "No query results for model"

**Cause**: Route parameters mismatch  
**Solution**: Ensure controller methods accept parameters in order matching route definition

```php
// Route: /api/admin/{shopSlug}/products/{productId}
// Controller must have:
public function update(Request $request, $shopSlug, $productId)
```

### Issue: "Add [field] to fillable property"

**Cause**: Mass assignment protection  
**Solution**: Add field to model's `$fillable` array

```php
protected $fillable = ['name', 'price', 'category_id'];
```

### Issue: Database connection refused

**Causes**:

1. MySQL not running
2. Wrong credentials in `.env`
3. Database doesn't exist

**Solutions**:

```bash
# Check MySQL status
# Windows: Services → MySQL
# Linux: sudo systemctl status mysql

# Test connection
mysql -u root -p

# Create database
CREATE DATABASE coffee_pos_saas;
```

### Issue: Migrations fail

**Solutions**:

```bash
# Reset migrations (WARNING: destroys data)
php artisan migrate:fresh

# With seeding
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

### Issue: 500 Internal Server Error

**Debug Steps**:

1. Check logs: `storage/logs/laravel.log`
2. Enable debug mode: `APP_DEBUG=true` in `.env`
3. Check permissions:
    ```bash
    chmod -R 775 storage bootstrap/cache
    ```

### Issue: CORS Errors

**Solution**: Already configured in `config/cors.php`  
If still issues, verify frontend URL matches:

```php
'allowed_origins' => ['http://localhost:3000'],
```

### Issue: Token Mismatch

**Causes**: Sanctum not configured properly  
**Solution**:

1. Publish Sanctum config:
    ```bash
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
    ```
2. Check `config/sanctum.php` stateful domains

## Database Structure

### Key Tables

- `shops` - Coffee shop tenants
- `users` - Super admins and staff
- `products` - Menu items
- `product_variants` - Product options (Size, Sugar, etc.)
- `orders` - Customer orders
- `order_items` - Items in orders
- `order_item_options` - Selected product options
- `cart_items` - Guest cart
- `shop_tables` - QR code tables
- `table_sessions` - Active guest sessions

## Development

### Running Tests

```bash
php artisan test
```

### Clearing Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Code Style

This project follows PSR-12 coding standards.

## Production Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure production database
3. Set secure `APP_KEY`
4. Configure web server (Nginx/Apache)
5. Set up SSL certificate
6. Configure queue workers for background jobs
7. Set up scheduled tasks (cron jobs)

```bash
# Generate optimized autoloader
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Support

For issues and questions:

- GitHub Issues: https://github.com/Johnrak11/Coffee-POS-Sass/issues
- Email: support@example.com

## License

Proprietary - All rights reserved
