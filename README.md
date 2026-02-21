# Symatech Backend API

A Laravel-based REST API for e-commerce order management with role-based access control.

## Features

- **Authentication**: User registration/login with Laravel Sanctum tokens
- **Role-based Access**: Admin and user roles with specific permissions
- **Product Management**: CRUD operations for products (admin only)
- **Order Management**: Create, view, and manage orders with stock tracking
- **Payment Integration**: M-Pesa and Flutterwave payment processing
- **Export Functionality**: Export orders to Excel and PDF (admin only)
- **Activity Logging**: Comprehensive audit trail using Spatie Activitylog
- **Data Validation**: Robust input validation and error handling

## Tech Stack

- **Framework**: Laravel 10.x
- **PHP**: ^8.1
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **Payment Processing**: M-Pesa API, Flutterwave API
- **Logging**: Spatie Laravel Activitylog
- **Export**: Maatwebsite Excel, DomPDF
- **HTTP Client**: Guzzle

## Installation

1. Clone the repository
```bash
git clone <repository-url>
cd symatech-backend
```

2. Install dependencies
```bash
composer install
```

3. Environment setup
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=symatech
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Configure payment providers in `.env`
```env
# M-Pesa Configuration
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=your_shortcode
MPESA_PASSKEY=your_passkey
MPESA_ENVIRONMENT=sandbox
MPESA_CALLBACK_URL=https://your-domain.com/api/callbacks/mpesa

# Flutterwave Configuration
FLUTTERWAVE_SECRET_KEY=your_secret_key
FLUTTERWAVE_PUBLIC_KEY=your_public_key
FLUTTERWAVE_ENCRYPTION_KEY=your_encryption_key
FLUTTERWAVE_ENVIRONMENT=sandbox
FLUTTERWAVE_CALLBACK_URL=https://your-domain.com/api/callbacks/flutterwave
```

6. Run migrations
```bash
php artisan migrate
```

7. Seed database (optional)
```bash
php artisan db:seed
```

## API Endpoints

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout (authenticated)

### Admin Routes (Authenticated + Admin Role)
- `GET /api/admin/users` - List all users
- `PATCH /api/admin/users/{id}/activate` - Activate user
- `PATCH /api/admin/users/{id}/deactivate` - Deactivate user
- `POST /api/admin/register` - Register new admin
- `GET /api/admin/products` - List products
- `POST /api/admin/products` - Create product
- `GET /api/admin/products/{id}` - Show product
- `PUT /api/admin/products/{id}` - Update product
- `DELETE /api/admin/products/{id}` - Delete product
- `GET /api/admin/orders` - List all orders with filters
- `GET /api/admin/orders/export/excel` - Export orders to Excel
- `GET /api/admin/orders/export/pdf` - Export orders to PDF

### User Routes (Authenticated + User Role)
- `GET /api/products` - List products
- `GET /api/products/{id}` - Show product
- `GET /api/orders` - List user's orders
- `POST /api/orders` - Create order
- `GET /api/orders/{id}` - Show order
- `DELETE /api/orders/{id}` - Delete order

### Payment Routes (Authenticated + User Role)
- `POST /api/payments/mpesa/initiate` - Initiate M-Pesa payment
- `POST /api/payments/mpesa/verify` - Verify M-Pesa payment
- `POST /api/payments/flutterwave/initiate` - Initiate Flutterwave payment
- `POST /api/payments/flutterwave/verify` - Verify Flutterwave payment
- `GET /api/payments` - List user's payments

### Payment Callbacks (Public)
- `POST /api/callbacks/mpesa` - M-Pesa payment callback
- `POST /api/callbacks/flutterwave` - Flutterwave payment callback

## Database Schema

### Users Table
- id, name, email, password, role (user/admin), status (boolean), timestamps

### Products Table
- id, name, description, price, stock, timestamps

### Orders Table
- id, user_id, product_id, quantity, total_price, status, timestamps

### Payments Table
- id, order_id, user_id, payment_method, transaction_id, amount, status, metadata, timestamps

### Activity Logs
- Automatic logging of all CRUD operations

## Payment Integration

The API supports payment processing through M-Pesa and Flutterwave.

### M-Pesa Integration
- **STK Push**: Initiate mobile payments via M-Pesa
- **Payment Verification**: Check payment status
- **Callback Handling**: Process payment confirmations
- **Sandbox Support**: Test with M-Pesa sandbox environment

### Flutterwave Integration
- **Payment Links**: Generate secure payment URLs
- **Multiple Methods**: Support card, bank transfer, and M-Pesa
- **Transaction Verification**: Confirm payment completion
- **Refund Processing**: Handle payment refunds
- **Webhook Support**: Process payment callbacks

### Payment Flow
1. User initiates payment for an order
2. Payment link/STK push is generated
3. User completes payment via chosen method
4. Payment callback updates order status
5. User can verify payment status

### Testing
Test scripts and Postman collections are available in the `tests/` directory for local development and API testing.

## Security Features

- Token-based authentication with Laravel Sanctum
- Role-based access control middleware
- Input validation and sanitization
- Password hashing
- Account activation/deactivation
- Activity logging for audit trails

## Testing

```bash
php artisan test
```

## Development

```bash
# Start development server
php artisan serve

# View logs
tail -f storage/logs/laravel.log
```

## License

MIT License
