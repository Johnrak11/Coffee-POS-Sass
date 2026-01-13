# Coffee POS SaaS - Frontend

A modern Vue.js 3 frontend for Coffee Shop Point of Sale system with guest ordering, staff POS, kitchen display, and admin management.

## Features

- 🎨 **Modern UI/UX** - Beautiful, responsive design with Tailwind CSS
- 📱 **Multi-Platform** - Web-based, works on desktop, tablet, and mobile
- 🛒 **Guest Ordering** - QR code menu browsing and ordering
- 💰 **POS System** - Fast cashier interface with product customization
- 🧑‍🍳 **Kitchen Display** - Real-time order tracking for baristas
- 📊 **Admin Dashboard** - Product, staff, and order management
- 🌓 **Dark Mode** - Theme toggle for light/dark preferences
- ✨ **Product Options** - Size, Sugar, Ice customization modal

## Prerequisites

- Node.js >= 18.x
- npm >= 9.x or yarn >= 1.22.x

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Johnrak11/Coffee-POS-Sass.git
cd Coffee-POS-Sass/frontend
```

### 2. Install Dependencies

```bash
npm install
# or
yarn install
```

### 3. Environment Configuration

Create a `.env` file in the `frontend` directory:

```env
VITE_API_URL=http://localhost:8000/api
```

For production:

```env
VITE_API_URL=https://your-domain.com/api
```

### 4. Start Development Server

```bash
npm run dev
# or
yarn dev
```

The application will be available at `http://localhost:3000`

## Project Structure

```
frontend/
├── src/
│   ├── assets/          # Static assets (images, fonts)
│   ├── components/      # Reusable Vue components
│   │   ├── InvoiceModal.vue
│   │   ├── OrderDetailModal.vue
│   │   ├── PaymentModal.vue
│   │   ├── ProductCustomizeModal.vue  ★ Product options
│   │   └── ReceiptModal.vue
│   ├── router/          # Vue Router configuration
│   ├── services/        # API client services
│   │   └── api.ts       # Axios API wrapper
│   ├── stores/          # Pinia stores
│   │   ├── auth.ts      # Authentication state
│   │   ├── cart.ts      # Guest cart state
│   │   ├── kitchen.ts   # Kitchen orders state
│   │   ├── pos.ts       # POS state
│   │   ├── session.ts   # Guest session state
│   │   └── theme.ts     # Theme preferences
│   ├── views/           # Page components
│   │   ├── AdminLayout.vue
│   │   ├── KitchenLayout.vue
│   │   ├── KitchenView.vue
│   │   ├── MenuView.vue         # Guest menu
│   │   ├── POSLayout.vue
│   │   ├── POSView.vue          # Cashier interface
│   │   ├── ProductListView.vue  # Product management
│   │   └── ...
│   ├── App.vue
│   └── main.ts
├── public/              # Public static files
├── index.html
├── package.json
├── tailwind.config.js   # Tailwind CSS config
├── tsconfig.json
└── vite.config.ts       # Vite configuration
```

## Key Routes

### Guest Routes

- `/menu/:shopSlug` - Browse menu and order
- `/cart/:shopSlug` - View cart and checkout

### Staff Routes

- `/login/:shopSlug` - Staff login
- `/pos` - Point of Sale interface
- `/pos/orders` - Order history
- `/kitchen` - Kitchen Display System

### Admin Routes

- `/admin/products` - Product management
- `/admin/categories` - Category management
- `/admin/staff` - Staff management
- `/admin/tables` - Table/QR management
- `/admin/settings` - Shop settings

### Super Admin Routes

- `/super-admin` - Dashboard
- `/super-admin/shops` - Manage all shops

## Configuration

### API Endpoint

Set the backend API URL in `.env`:

```env
VITE_API_URL=http://localhost:8000/api
```

The API client is located at `src/services/api.ts`

### Port Configuration

Change the dev server port in `vite.config.ts`:

```ts
export default defineConfig({
  server: {
    port: 3000, // Change this
  },
});
```

### Theme Configuration

Customize colors in `tailwind.config.js`:

```js
theme: {
  extend: {
    colors: {
      primary: {
        50: '#fff7ed',
        // ... customize your brand colors
      }
    }
  }
}
```

### Dark Mode

The app uses `class` strategy for dark mode:

```vue
<!-- Toggle theme -->
<button @click="themeStore.toggleTheme()">
  Toggle Dark Mode
</button>
```

Theme state is persisted in `localStorage`.

## Features Guide

### Product Customization Modal

When a product has variants (Size, Sugar, Ice), clicking it opens `ProductCustomizeModal.vue`:

**How it works:**

1. Product click checks `product.variants && product.variants.length > 0`
2. Modal groups variants by `name` field (e.g., "Size", "Sugar")
3. User selects one option per group (radio buttons)
4. Price updates in real-time
5. Quantity selector included
6. "Add to Order" sends selected options to backend

**Used in:**

- POS View (`POSView.vue`)
- Guest Menu (`MenuView.vue`)

### State Management (Pinia)

**Auth Store** (`auth.ts`):

- User authentication
- Role-based access
- Token management

**POS Store** (`pos.ts`):

- Current order items
- Product catalog
- Cart subtotal/total
- Payment processing

**Kitchen Store** (`kitchen.ts`):

- Active orders (queue, preparing)
- Auto-refresh every 5 seconds
- Status updates

**Theme Store** (`theme.ts`):

- Dark/light mode toggle
- Sidebar collapse state
- localStorage persistence

## Building for Production

### Build

```bash
npm run build
# or
yarn build
```

Built files will be in `dist/` directory.

### Preview Production Build

```bash
npm run preview
# or
yarn preview
```

### Deploy

Upload `dist/` folder to your web server or use services like:

- Vercel: `vercel --prod`
- Netlify: `netlify deploy --prod`
- AWS S3 + CloudFront
- GitHub Pages

**Important**: Set correct `VITE_API_URL` before building!

## Troubleshooting

### Issue: "Cannot connect to API"

**Causes**:

1. Backend not running
2. Wrong `VITE_API_URL` in `.env`
3. CORS issues

**Solutions**:

```bash
# Check backend is running
curl http://localhost:8000/api/health

# Verify .env file exists and has correct URL
cat .env

# Restart dev server after changing .env
npm run dev
```

### Issue: "Module not found" errors

**Causes**: Missing dependencies  
**Solution**:

```bash
# Delete node_modules and reinstall
rm -rf node_modules
npm install

# Clear npm cache if needed
npm cache clean --force
```

### Issue: Build fails with TypeScript errors

**Solutions**:

```bash
# Check TypeScript configuration
npx tsc --noEmit

# Update dependencies
npm update

# Skip type checking (not recommended)
npm run build -- --mode production --no-type-check
```

### Issue: Slow HMR (Hot Module Replacement)

**Solutions**:

1. Exclude `node_modules` from file watcher
2. Increase `server.watch` limits in `vite.config.ts`:
   ```ts
   server: {
     watch: {
       usePolling: false,
     }
   }
   ```
3. Use `vite` instead of `npm run dev` directly

### Issue: "404 Not Found" on refresh

**Cause**: SPA routing vs server routing mismatch  
**Solution**: Configure server for SPA:

**Nginx**:

```nginx
location / {
  try_files $uri $uri/ /index.html;
}
```

**Apache** (`.htaccess`):

```apache
RewriteEngine On
RewriteBase /
RewriteRule ^index\.html$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```

### Issue: Environment variables not loading

**Causes**:

1. `.env` file missing
2. Variables not prefixed with `VITE_`
3. Server not restarted

**Solutions**:

- All env vars must start with `VITE_`
- Restart dev server after changing `.env`
- Access via `import.meta.env.VITE_API_URL`

### Issue: Tailwind classes not working

**Solutions**:

```bash
# Rebuild Tailwind config
npx tailwindcss init -p

# Check tailwind.config.js content paths:
content: [
  "./index.html",
  "./src/**/*.{vue,js,ts,jsx,tsx}",
],
```

### Issue: Product Options Modal not opening

**Debug Steps**:

1. Check product has `variants` array in response
2. Verify `ProductCustomizeModal` is imported
3. Check console for errors
4. Ensure `showCustomizeModal` state works

```vue
<script>
console.log("Product variants:", product.variants);
console.log("Modal state:", showCustomizeModal.value);
</script>
```

### Issue: Images not displaying

**Causes**:

1. Wrong path (use `/images/` or `@/assets/`)
2. Image URL from backend is invalid

**Solutions**:

```vue
<!-- Public folder -->
<img src="/logo.png" />

<!-- Assets folder (Vite will process) -->
<img :src="new URL('@/assets/logo.png', import.meta.url).href" />

<!-- Dynamic from API -->
<img :src="product.image_url || '/placeholder.png'" />
```

## Development Tips

### Code Style

- Use Composition API (`<script setup>`)
- TypeScript for type safety
- Tailwind CSS for styling (avoid inline styles)
- Pinia for state management

### Performance

- Lazy load routes:
  ```ts
  {
    path: '/admin',
    component: () => import('./views/AdminLayout.vue')
  }
  ```
- Use `v-if` vs `v-show` appropriately
- Debounce search inputs
- Virtual scrolling for long lists

### Debugging

```bash
# Enable Vue DevTools
npm install -g @vue/devtools

# Run Vite with debug
DEBUG=vite:* npm run dev

# Check bundle size
npm run build -- --mode analyze
```

## Browser Support

- Chrome/Edge >= 90
- Firefox >= 88
- Safari >= 14

## Testing

```bash
# Unit tests (if configured)
npm run test:unit

# E2E tests (if configured)
npm run test:e2e
```

## Support

For issues and questions:

- GitHub Issues: https://github.com/Johnrak11/Coffee-POS-Sass/issues
- Email: support@example.com

## License

Proprietary - All rights reserved
