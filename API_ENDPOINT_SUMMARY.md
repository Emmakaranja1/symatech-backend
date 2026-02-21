# Symatech Backend API - Endpoint Summary

## Overview

Complete API endpoint documentation for the Symatech e-commerce backend with Redis state management, authentication, and reporting capabilities.

## Base URL
```
https://your-domain.com/api
```

## Authentication

### Public Routes
- `POST /register` - User registration
- `POST /login` - User login  
- `POST /jwt/register` - JWT user registration
- `POST /jwt/register-admin` - JWT admin registration
- `POST /jwt/login` - JWT user login

### Sanctum Protected Routes
- `POST /logout` - User logout
- `GET /user` - Get authenticated user

### JWT Protected Routes
- `GET /jwt/me` - Get authenticated JWT user
- `POST /jwt/logout` - JWT logout
- `POST /jwt/refresh` - Refresh JWT token

## Admin Routes (Sanctum + Admin Role Required)

### User Management
- `GET /admin/users` - List all users
- `PATCH /admin/users/{id}/activate` - Activate user account
- `PATCH /admin/users/{id}/deactivate` - Deactivate user account
- `POST /admin/register` - Register new admin user

### Product Management
- `GET /admin/products` - List all products
- `POST /admin/products` - Create new product
- `GET /admin/products/{id}` - Get product details
- `PUT /admin/products/{id}` - Update product
- `DELETE /admin/products/{id}` - Delete product

### Order Management
- `GET /admin/orders` - List all orders with filters
- `GET /admin/orders/export/excel` - Export orders to Excel
- `GET /admin/orders/export/pdf` - Export orders to PDF

### Payment Management
- `GET /admin/payments` - List all payments
- `POST /payments/refund` - Process payment refund

### Reporting System
- `GET /admin/reports/dashboard` - Dashboard statistics
- `GET /admin/reports/user-registration-trends` - User registration trends
- `GET /admin/reports/user-registration-trends/export/excel` - Export trends to Excel
- `GET /admin/reports/user-registration-trends/export/pdf` - Export trends to PDF
- `GET /admin/reports/activity-log` - Complete activity log
- `GET /admin/reports/activity-log/export/excel` - Export activity log to Excel
- `GET /admin/reports/activity-log/export/pdf` - Export activity log to PDF
- `GET /admin/reports/normal-user-activity` - Normal user activity only
- `GET /admin/reports/normal-user-activity/export/excel` - Export normal user activity to Excel
- `GET /admin/reports/normal-user-activity/export/pdf` - Export normal user activity to PDF

## User Routes (Sanctum + User Role Required)

### Product Viewing
- `GET /products` - List available products
- `GET /products/{id}` - Get product details

### Order Management
- `GET /orders` - List user's orders
- `POST /orders` - Create new order
- `GET /orders/{id}` - Get order details
- `DELETE /orders/{id}` - Cancel order

### Payment Processing
- `POST /payments/mpesa/initiate` - Initiate M-Pesa payment
- `POST /payments/mpesa/verify` - Verify M-Pesa payment
- `POST /payments/flutterwave/initiate` - Initiate Flutterwave payment
- `POST /payments/flutterwave/verify` - Verify Flutterwave payment
- `GET /payments` - List user's payment history

## Payment Callbacks (Public - Webhook Endpoints)

### Payment Provider Callbacks
- `POST /callbacks/mpesa` - M-Pesa payment confirmation webhook
- `POST /callbacks/flutterwave` - Flutterwave payment confirmation webhook

## Redis State Management (Public - Testing Endpoints)

### Shopping Cart Management
- `POST /redis/cart/add` - Add item to shopping cart
- `GET /redis/cart` - Get cart contents with totals
- `PUT /redis/cart/quantity` - Update item quantity in cart
- `DELETE /redis/cart/item` - Remove specific item from cart
- `GET /redis/cart/summary` - Get cart summary (count + total)
- `DELETE /redis/cart` - Clear entire shopping cart

### User Preferences Storage
- `POST /redis/preferences/set` - Set individual user preference
- `GET /redis/preferences/get` - Get specific user preference
- `GET /redis/preferences/all` - Get all user preferences
- `POST /redis/preferences/multiple` - Set multiple preferences at once
- `DELETE /redis/preferences/remove` - Remove specific preference
- `DELETE /redis/preferences/clear` - Clear all user preferences

### Payment Session Management
- `POST /redis/payment/session/create` - Create temporary payment session
- `GET /redis/payment/session` - Get payment session details
- `PUT /redis/payment/session/update` - Update payment session data
- `PUT /redis/payment/session/extend` - Extend session expiry time
- `GET /redis/payment/session/validity` - Check if session is still valid
- `DELETE /redis/payment/session` - Delete payment session

### Rate Limiting System
- `POST /redis/rate-limit/check` - Check and increment rate limit
- `GET /redis/rate-limit/status` - Get current rate limit status
- `POST /redis/rate-limit/multiple` - Check multiple rate limits
- `GET /redis/rate-limit/blocked` - Check if identifier is blocked
- `GET /redis/rate-limit/test` - Automated rate limiting test
- `DELETE /redis/rate-limit/clear` - Clear rate limit for identifier

### Redis Connection Monitoring
- `GET /redis/connection/test` - Test basic Redis connection
- `GET /redis/connection/operations` - Test Redis basic operations performance
- `GET /redis/connection/hash` - Test Redis hash operations performance
- `GET /redis/connection/info` - Get Redis server information
- `GET /redis/connection/monitor` - Comprehensive connection monitoring
- `GET /redis/connection/health` - Simple health check endpoint

## Response Formats

### Success Response
```json
{
    "success": true,
    "data": { ... },
    "message": "Operation completed successfully"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... }
}
```

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

### Authentication Error (401/403)
```json
{
    "success": false,
    "message": "Unauthenticated or Unauthorized"
}
```

## Rate Limiting

API endpoints implement rate limiting using Redis:
- **Default**: 60 requests per minute per IP
- **Authentication**: 100 requests per minute per user
- **Admin routes**: 200 requests per minute per admin
- **Payment endpoints**: 10 requests per minute per user

## Security Features

### Authentication Methods
- **Laravel Sanctum**: Token-based authentication for web/mobile
- **JWT**: Token-based authentication for API clients
- **Role-based Access**: Admin and user role separation

### Data Protection
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection
- Rate limiting

### Activity Logging
All CRUD operations are automatically logged:
- User registration/login actions
- Product creation/modification/deletion
- Order creation and status changes
- Payment processing and status updates
- Admin actions and system changes

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |
| 503 | Service Unavailable |

## Pagination

List endpoints support pagination:
```json
{
    "data": [ ... ],
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200
}
```

Query parameters:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)

## Filtering and Sorting

### Date Range Filtering
- `start_date`: Filter from date (Y-m-d)
- `end_date`: Filter to date (Y-m-d)

### Status Filtering
- `status`: Filter by status field

### User Filtering
- `user_id`: Filter by specific user ID

### Sorting
- `sort`: Field to sort by
- `order`: asc or desc (default: desc)

## Export Formats

### Excel Export (.xlsx)
- Professional formatting
- Multiple sheets when applicable
- Filtered data respect applied filters
- Automatic filename with timestamp

### PDF Export
- Professional report layout
- Summary statistics
- Formatted tables
- Company branding ready

## Testing

### Postman Collection
Complete Postman collection available in `docs/` folder:
- All endpoints configured
- Authentication examples
- Test scenarios
- Error handling examples

### Environment Setup
```bash
# Development server
php artisan serve

# Run tests
php artisan test

# Clear cache
php artisan cache:clear
php artisan config:clear
```

## Production Considerations

### Environment Variables
Set `APP_DEBUG=false` in production
Configure proper database and Redis connections
Set secure JWT secrets
Configure payment provider credentials

### Performance
Redis caching enabled
Database indexes optimized
Rate limiting active
Activity logging configured

### Security
HTTPS required
CORS configured
Firewall rules applied
Regular security updates

## Support

For API support and documentation:
- Check `docs/` folder for detailed guides
- Review activity logs for troubleshooting
- Monitor Redis connection health
- Check payment provider documentation
