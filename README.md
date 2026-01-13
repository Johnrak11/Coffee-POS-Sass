# ☕ Coffee POS SaaS

A modern, full-stack Point of Sale system for coffee shops with multi-tenant support, guest QR ordering, kitchen display, and advanced product customization.

![License](https://img.shields.io/badge/license-Proprietary-red)
![Laravel](https://img.shields.io/badge/Laravel-11.x-red)
![Vue](https://img.shields.io/badge/Vue-3.x-green)
![TypeScript](https://img.shields.io/badge/TypeScript-5.x-blue)

## 🌟 Features

### For Customers (Guests)

- 📱 **QR Code Ordering** - Scan table QR to browse menu and order
- 🎨 **Product Customization** - Choose Size, Sugar, Ice, Toppings
- 🛒 **Cart Management** - Add, edit, remove items easily
- 💳 **KHQR Payment** - Cambodian QR payment integration

### For Staff (Cashiers)

- 💰 **Fast POS Interface** - Quick order placement
- 🎯 **Product Options Modal** - Select customizations visually
- 📊 **Order History** - View and manage past orders
- 🧾 **Receipt Printing** - Professional receipt generation

### For Kitchen (Baristas)

- 🧑‍🍳 **Kitchen Display System** - Real-time order queue
- ⏱️ **Timer Display** - Track order waiting times
- ✅ **Status Updates** - Mark orders as preparing/complete
- 🎨 **Highlighted Options** - Customizations in orange for clarity

### For Admins

- 📦 **Product Management** - Add/edit products with variants
- 👥 **Staff Management** - Create and manage staff accounts
- 📊 **Analytics Dashboard** - Sales insights and reports
- 🏪 **Multi-tenant Support** - Manage multiple shop locations

### For Super Admins

- 🏢 **Shop Management** - Create and configure shops
- 💰 **Subscription Control** - Manage shop subscriptions
- 🔧 **System Settings** - Global configuration

## 🏗️ Tech Stack

### Backend

- **Framework**: Laravel 11.x
- **Database**: MySQL 5.7+
- **Authentication**: Laravel Sanctum
- **Payment**: KHQR (Bakong API)

### Frontend

- **Framework**: Vue 3 (Composition API)
- **Language**: TypeScript
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **State**: Pinia
- **Routing**: Vue Router

## 🚀 Quick Start

### Prerequisites

- PHP >= 8.1
- Composer
- MySQL >= 5.7
- Node.js >= 18.x
- npm >= 9.x

### Installation

1. **Clone the repository**

```bash
git clone https://github.com/Johnrak11/Coffee-POS-Sass.git
cd Coffee-POS-Sass
```

2. **Backend Setup**

```bash
cd backend
composer install
cp .env.example .env
# Configure your .env file
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

3. **Frontend Setup**

```bash
cd frontend
npm install
# Create .env with VITE_API_URL=http://localhost:8000/api
npm run dev
```

4. **Access the Application**

- Frontend: http://localhost:3000
- Backend API: http://localhost:8000
- Super Admin: http://localhost:3000/super-admin

### Default Credentials

**Super Admin:**

- Email: `admin@example.com`
- Password: `password`

**Shop Terminal (Lucky Cafe):**

- Slug: `lucky-cafe`
- Password: `123456`

See [Backend README](./backend/README.md) and [Frontend README](./frontend/README.md) for detailed setup instructions.

## 📖 Documentation

- [Backend Documentation](./backend/README.md) - API setup, routes, troubleshooting
- [Frontend Documentation](./frontend/README.md) - Vue app setup, features, debugging
- [Product Options Feature](./walkthrough.md) - Detailed implementation guide

## 🎯 Key Features Showcase

### Product Customization

Real-world cafe experience with multiple option groups:

```javascript
// Example: Latte customization
Size: Small ($0) | Medium (+$0.50) | Large (+$1.00)
Sugar: 0% | 50% | 100%
Ice: Normal | Extra (+$0.25)
```

### Kitchen Display

Orders show with all customizations highlighted:

```
Order #ORD-20260113-0001
Table 5

2x Latte
  ▸ Size: Large
  ▸ Sugar: 50%
  ▸ Ice: Extra
```

### Multi-Tenant Architecture

- Each shop has its own:
  - Menu (categories, products, variants)
  - Staff (cashiers, baristas)
  - Tables (QR codes)
  - Orders and analytics

## 🗂️ Project Structure

```
Coffee-POS-Sass/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
│       └── api.php
├── frontend/                # Vue.js SPA
│   ├── src/
│   │   ├── components/
│   │   │   └── ProductCustomizeModal.vue  ★
│   │   ├── stores/
│   │   ├── views/
│   │   │   ├── POSView.vue
│   │   │   ├── KitchenView.vue
│   │   │   └── MenuView.vue
│   │   └── router/
│   └── package.json
└── README.md
```

## 🔧 Configuration

### Backend (.env)

```env
DB_DATABASE=coffee_pos_saas
DB_USERNAME=root
DB_PASSWORD=

SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=password

BAKONG_TOKEN=your_khqr_token  # Optional
```

### Frontend (.env)

```env
VITE_API_URL=http://localhost:8000/api
```

## 🐛 Common Issues & Solutions

### "Cannot connect to API"

- Ensure backend is running on port 8000
- Check `VITE_API_URL` in frontend `.env`
- Verify CORS settings in backend

### "No query results for model"

- Controller method parameters must match route order
- Example: `update($request, $shopSlug, $productId)`

### "Module not found"

- Delete `node_modules` and `npm install`
- Check import paths use `@/` alias

See full troubleshooting guides in:

- [Backend Troubleshooting](./backend/README.md#troubleshooting)
- [Frontend Troubleshooting](./frontend/README.md#troubleshooting)

## 📊 Database Schema

### Core Tables

- `shops` - Multi-tenant shops
- `users` - Super admins and staff
- `products` - Menu items
- `product_variants` - Options (Size, Sugar, etc.)
- `orders` - Customer orders
- `order_items` - Line items
- `order_item_options` - Selected customizations ⭐

### Relationships

```
Product (1) --> (N) ProductVariant
Order (1) --> (N) OrderItem
OrderItem (1) --> (N) OrderItemOption
```

## 🚢 Deployment

### Backend (Laravel)

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Frontend (Vue)

```bash
npm run build
# Upload dist/ folder to web server
```

Configure web server for SPA routing (see [Frontend README](./frontend/README.md)).

## 🤝 Contributing

This is a proprietary project. For bug reports or feature requests, please create an issue.

## 📄 License

Proprietary - All rights reserved

## 👨‍💻 Author

**Johnrak11**

- GitHub: [@Johnrak11](https://github.com/Johnrak11)

## 🙏 Acknowledgments

- Laravel Framework
- Vue.js Team
- Tailwind CSS
- Bakong KHQR Payment API

---

**Built with ❤️ for the coffee shop community**
