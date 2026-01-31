# 🛒 E-Commerce API

A comprehensive RESTful API for e-commerce applications built with Laravel 12, featuring real-time order tracking, payment processing with Stripe, and role-based access control.

## ✨ Features

- 🔐 **Multi-Role Authentication** (Admin, Customer, Delivery)
- 🛍️ **Product Management** with categories and inventory tracking
- 🛒 **Shopping Cart** functionality
- 💳 **Payment Integration** with Stripe
- 📦 **Order Management** with status tracking
- 🔔 **Real-time Notifications** via Laravel Reverb
- 📧 **Email Notifications** for order updates
- 🎫 **Queue System** for background jobs
- 🔒 **Permission-based Access Control** using Spatie Laravel Permission
- 🛡️ **Rate Limiting** to prevent abuse and DDoS attacks

## 🚀 Tech Stack

- **Framework:** Laravel 12
- **Database:** SQLite (configurable to MySQL/PostgreSQL)
- **Authentication:** Laravel Sanctum
- **Permissions:** Spatie Laravel Permission
- **Payment:** Stripe PHP SDK
- **Real-time:** Laravel Reverb
- **Queue:** Database Driver
- **Cache:** Database Driver

## 📋 Prerequisites

- PHP >= 8.2
- Composer
- Node.js & NPM
- SQLite or MySQL/PostgreSQL

## 🔧 Installation

1. **Clone the repository**
```bash
git clone <your-repo-url>
cd ecommerce-api
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install Node dependencies**
```bash
npm install
```

4. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configure your `.env` file**
```env
# Database
DB_CONNECTION=sqlite

# Stripe (Get from https://dashboard.stripe.com)
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret

# Reverb (Real-time Broadcasting)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@yourapp.com
```

6. **Run Migrations & Seeders**
```bash
php artisan migrate --seed
```

7. **Create Storage Link**
```bash
php artisan storage:link
```

## 🏃 Running the Application

You need to run these commands in **separate terminal windows**:

```bash
# Terminal 1: Start the application server
php artisan serve

# Terminal 2: Start the queue worker
php artisan queue:work

# Terminal 3: Start Reverb for real-time features
php artisan reverb:start
```

Or use the dev script (requires `concurrently`):
```bash
composer run dev
```

## 📚 API Documentation

### Authentication Endpoints

#### Admin
- `POST /api/admin/register` - Register admin
- `POST /api/admin/login` - Admin login
- `POST /api/admin/logout` - Admin logout (requires auth)
- `GET /api/admin/me` - Get admin profile (requires auth)

#### Customer
- `POST /api/customer/register` - Register customer
- `POST /api/customer/login` - Customer login
- `POST /api/customer/logout` - Customer logout (requires auth)
- `GET /api/customer/me` - Get customer profile (requires auth)

#### Delivery
- `POST /api/delivery/register` - Register delivery personnel
- `POST /api/delivery/login` - Delivery login
- `POST /api/delivery/logout` - Delivery logout (requires auth)
- `GET /api/delivery/me` - Get delivery profile (requires auth)

### Product Endpoints

- `GET /api/products` - List all products
- `GET /api/products/{id}` - Get product details
- `POST /api/products` - Create product (requires `create products` permission)
- `PUT /api/products/{id}` - Update product (requires `create products` permission)
- `DELETE /api/products/{id}` - Delete product (requires `create products` permission)

### Category Endpoints

- `GET /api/categories` - List all categories
- `GET /api/categories/{id}` - Get category details
- `GET /api/categories/{id}/products` - Get products by category
- `POST /api/categories` - Create category (requires `create categories` permission)
- `PUT /api/categories/{id}` - Update category (requires `create categories` permission)
- `DELETE /api/categories/{id}` - Delete category (requires `create categories` permission)

### Cart Endpoints

- `GET /api/carts` - Get user's cart (requires `create orders` permission)
- `POST /api/carts` - Add item to cart (requires `create orders` permission)
- `PUT /api/carts/{id}` - Update cart item (requires `create orders` permission)
- `DELETE /api/carts/{id}` - Remove item from cart (requires `create orders` permission)

### Order Endpoints

- `POST /api/checkout` - Create order from cart (requires `create orders` permission)
- `GET /api/order` - Get order history (requires `create orders` permission)
- `GET /api/order/{id}` - Get order details (requires `create orders` permission)

### Payment Endpoints

- `POST /api/order/{order}/payments` - Create payment intent (requires `create orders` permission)
- `POST /api/payments/{payment}/confirm` - Confirm payment (requires `create orders` permission)
- `POST /api/webhooks/stripe` - Stripe webhook handler (public)

## 🔑 Default Roles & Permissions

### Admin Role
- Full access to all resources
- Can manage products, categories, orders, and users

### Customer Role
- Can view products and categories
- Can create and manage their own orders
- Can add items to cart and checkout

### Delivery Role
- Can view orders and deliveries
- Can update delivery status

## 🎯 Key Features Explained

### Real-time Order Updates
Orders broadcast status changes via Laravel Reverb to:
- Private channel: `user.{userId}.orders` (for customers)
- Private channel: `admin.orders` (for admins)

### Email Notifications
Automatic emails sent for:
- Order confirmation
- Order shipped
- Order delivered
- Order cancelled

### Payment Flow
1. Customer creates order via checkout
2. System creates Stripe Payment Intent
3. Customer completes payment on frontend
4. Stripe webhook confirms payment
5. Order status updated to "Paid"
6. Email notification sent

### Rate Limiting
The API implements rate limiting to prevent abuse:

| Endpoint Type | Rate Limit | Scope |
|--------------|------------|-------|
| Authentication | 5 requests/minute | Per IP |
| Payment Operations | 10 requests/minute | Per User/IP |
| General API | 60 requests/minute | Per User/IP |
| Public Endpoints | 60 requests/minute | Per IP |

## 🧪 Testing

### Using Postman
Import the included `postman_collection.json` file into Postman to test all API endpoints:

1. Open Postman
2. Click **Import**
3. Select `postman_collection.json`
4. Update the `base_url` variable if needed
5. Start testing!

The collection includes:
- ✅ Authentication flows (Admin, Customer, Delivery)
- ✅ Product CRUD operations
- ✅ Category management
- ✅ Cart operations
- ✅ Checkout process
- ✅ Payment creation and confirmation

### Running Tests
```bash
composer test
```

### Manual Testing
```bash
# Test authentication
curl -X POST http://127.0.0.1:8000/api/customer/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Test product listing
curl http://127.0.0.1:8000/api/products
```

## 🔒 Security

This application implements multiple security layers:

- **Authentication**: Token-based auth with Laravel Sanctum
- **Authorization**: Role and permission-based access control
- **Rate Limiting**: Protection against brute force and DDoS
- **Input Validation**: All inputs are validated
- **SQL Injection Prevention**: Using Eloquent ORM
- **XSS Protection**: Laravel's built-in escaping
- **Payment Security**: Stripe webhook signature verification

For detailed security information, see [SECURITY.md](SECURITY.md).

**Security Checklist for Production:**
- [ ] Set `APP_DEBUG=false`
- [ ] Use production Stripe keys
- [ ] Enable HTTPS
- [ ] Configure CORS properly
- [ ] Set strong database passwords
- [ ] Enable rate limiting
- [ ] Configure proper logging

## 📝 Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `STRIPE_KEY` | Stripe publishable key | Yes |
| `STRIPE_SECRET` | Stripe secret key | Yes |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook signing secret | Yes |
| `REVERB_APP_ID` | Reverb application ID | Yes |
| `REVERB_APP_KEY` | Reverb application key | Yes |
| `REVERB_APP_SECRET` | Reverb application secret | Yes |

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👨‍💻 Author

Ahmed Fawzy - [GitHub Profile](https://github.com/ahmed-fawzy-hub)

## 🙏 Acknowledgments

- Laravel Framework
- Spatie Laravel Permission
- Stripe PHP SDK
- Laravel Reverb

