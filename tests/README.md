# Test Scripts Organization

This directory contains all test scripts and debugging files organized by category.

## 📁 Directory Structure

### `/tests/scripts/`
Contains all shell scripts for testing payment integrations:
- **M-Pesa Scripts**: `mpesa_*.sh`, `test_*mpesa*.sh`
- **Flutterwave Scripts**: `flutterwave_*.sh`
- **General Scripts**: `test_*.sh`, `final_*.sh`

### `/tests/postman/`
Contains Postman collections and API test files:
- `flutterwave_postman_collection.json` - Complete Flutterwave API collection
- `payment_integration_postman.json` - Full payment integration tests

### `/tests/debug/`
Contains debugging and temporary files:
- `debug_auth.php` - Authentication debugging script
- Other debug files as needed

## 🚀 Usage

### Running Test Scripts
```bash
# Navigate to scripts directory
cd tests/scripts/

# Make scripts executable
chmod +x *.sh

# Run specific test
./test_actual_credentials.sh
```

### Importing Postman Collections
1. Open Postman
2. Click "Import"
3. Select files from `/tests/postman/` directory
4. Update environment variables with your credentials

## 🔧 Environment Variables Required

### M-Pesa
- `MPESA_CONSUMER_KEY`
- `MPESA_CONSUMER_SECRET`
- `MPESA_SHORTCODE`
- `MPESA_PASSKEY`
- `MPESA_ENVIRONMENT`
- `MPESA_CALLBACK_URL`

### Flutterwave
- `FLUTTERWAVE_SECRET_KEY`
- `FLUTTERWAVE_PUBLIC_KEY`
- `FLUTTERWAVE_ENCRYPTION_KEY`
- `FLUTTERWAVE_ENVIRONMENT`
- `FLUTTERWAVE_CALLBACK_URL`

## 📋 Test Coverage

### M-Pesa Tests
- ✅ OAuth token generation
- ✅ STK Push initiation
- ✅ Payment verification
- ✅ Callback handling
- ✅ Error scenarios

### Flutterwave Tests
- ✅ Payment link creation
- ✅ Payment verification
- ✅ Transaction details
- ✅ Refund processing
- ✅ Balance checking

## 🚨 Important Notes

- All test scripts are excluded from Git via `.gitignore`
- These are for local development and debugging only
- Never commit sensitive credentials or API keys
- Use sandbox environment for testing
- Monitor rate limits when testing APIs

## 🔄 Maintenance

- Clean up old test scripts regularly
- Update Postman collections when API changes
- Keep README updated with new test procedures
- Remove sensitive data before sharing
