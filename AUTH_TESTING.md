# Auth Testing Scripts

This directory contains PHP scripts to test the authentication API endpoints.

## Available Scripts

### 1. `test_auth.php` - Comprehensive Test Suite
A complete testing script that covers all authentication endpoints and scenarios.

**Features:**
- ✅ User registration and login
- ✅ Profile management (get, update)
- ✅ Token refresh and logout
- ✅ Password change functionality
- ✅ Password reset flow
- ✅ Error handling tests
- ✅ Colored console output
- ✅ Detailed test results and summary

**Usage:**
```bash
php test_auth.php
```

### 2. `quick_test.php` - Fast Basic Test
A minimal script for quick verification that core endpoints are working.

**Features:**
- ✅ Basic registration, login, profile, logout flow
- ✅ Minimal output for quick checks
- ✅ Fast execution

**Usage:**
```bash
php quick_test.php
```

## Prerequisites

1. **Laravel Server Running:**
   ```bash
   php artisan serve
   # Server should be running on http://localhost:8000
   ```

2. **Database Setup:**
   - Make sure your database is configured and migrated
   - The scripts will create test users automatically

3. **cURL Extension:**
   - PHP cURL extension must be enabled

## Configuration

You can modify these variables at the top of each script:

```php
$baseUrl = 'http://localhost:8000/api';  // Your API base URL
$testEmail = 'test@example.com';         // Test user email
$testPassword = 'TestPassword123!';      // Test user password
```

## API Documentation

After running the tests, you can view the complete API documentation at:
http://localhost:8000/docs

## Test Results

### Expected Behavior:
- **Registration:** Should create a new user and return 201
- **Login:** Should authenticate and return access token (200)
- **Profile:** Should return user data when authenticated (200)
- **Update:** Should modify profile data (200)
- **Logout:** Should invalidate token (200)
- **Error Cases:** Should return appropriate error codes (401, 422, etc.)

### Common Issues:
1. **Server not running:** Make sure `php artisan serve` is running
2. **Database errors:** Run `php artisan migrate` if needed
3. **Permission errors:** Check file permissions and storage directory
4. **Port conflicts:** Change port if 8000 is in use: `php artisan serve --port=8080`

## Multi-Language Testing

The API supports multiple languages. To test with Turkish responses:

```bash
# Add this header to requests:
'Accept-Language: tr'
```

## Cleanup

The test scripts create users in your database. In a production environment, you may want to clean up test data:

```sql
DELETE FROM users WHERE email LIKE '%test%' OR email LIKE '%quicktest%';
```

## Security Notes

- Test scripts use test credentials only
- Don't use production URLs with test credentials
- Test tokens are automatically invalidated after logout tests