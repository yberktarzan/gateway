<?php

/**
 * Company API Test Script
 *
 * This script tests company endpoints to ensure they're working correctly.
 * Run this script from the command line: php test_company.php
 */

// Configuration
$baseUrl = 'http://127.0.0.1:8000/api';
$testEmail = 'company_test_'.time().'@example.com';
$testPassword = 'TestPassword123!';
$testName = 'Company Test User';

// Colors for console output
class Colors
{
    const GREEN = "\033[32m";

    const RED = "\033[31m";

    const YELLOW = "\033[33m";

    const BLUE = "\033[34m";

    const RESET = "\033[0m";
}

function makeRequest($url, $method = 'GET', $data = null, $headers = [])
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
        ], $headers),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($data && in_array($method, ['POST', 'PATCH', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'body' => $response ? json_decode($response, true) : null,
    ];
}

function testEndpoint($name, $url, $method = 'GET', $data = null, $headers = [], $expectedStatus = 200)
{
    $result = makeRequest($url, $method, $data, $headers);

    if ($result['status'] === $expectedStatus) {
        echo Colors::GREEN.'✓ PASS'.Colors::RESET." - $name\n";

        return $result;
    } else {
        echo Colors::RED.'✗ FAIL'.Colors::RESET." - $name (Expected: $expectedStatus, Got: {$result['status']})\n";
        if ($result['body']) {
            echo '  Response: '.json_encode($result['body'], JSON_PRETTY_PRINT)."\n";
        }

        return false;
    }
}

// Start testing
echo Colors::BLUE."🚀 Starting Company API Tests\n";
echo "Base URL: $baseUrl\n";
echo "Test Email: $testEmail\n\n".Colors::RESET;

$authToken = null;
$companyId = null;

// 1. Health Check
echo Colors::YELLOW."=== HEALTH CHECK ===\n".Colors::RESET;
testEndpoint('API Health Check', "$baseUrl/health");

// 2. Register User (needed for authenticated endpoints)
echo Colors::YELLOW."\n=== USER SETUP ===\n".Colors::RESET;
$registerData = [
    'name' => $testName,
    'email' => $testEmail,
    'password' => $testPassword,
    'password_confirmation' => $testPassword,
];

$registerResult = testEndpoint('User Registration', "$baseUrl/auth/register", 'POST', $registerData, [], 201);

if ($registerResult) {
    // 3. Login User
    $loginData = [
        'email' => $testEmail,
        'password' => $testPassword,
    ];

    $loginResult = testEndpoint('User Login', "$baseUrl/auth/login", 'POST', $loginData);

    if ($loginResult && isset($loginResult['body']['data']['token'])) {
        $authToken = $loginResult['body']['data']['token'];
        echo '  Auth Token: '.substr($authToken, 0, 20)."...\n";
    }
}

// 4. Company Tests
echo Colors::YELLOW."\n=== COMPANY ENDPOINTS ===\n".Colors::RESET;

// Test public endpoints first
testEndpoint('Get All Companies', "$baseUrl/companies");
testEndpoint('Get Active Companies', "$baseUrl/companies/active");
testEndpoint('Get VIP Companies', "$baseUrl/companies/vip");
testEndpoint('Get Company Statistics', "$baseUrl/companies/statistics");

// Test authenticated endpoints if we have a token
if ($authToken) {
    $authHeaders = ["Authorization: Bearer $authToken"];

    // Create Company
    $companyData = [
        'name' => 'Test Tech Company',
        'country_code' => 'US',
        'description' => [
            'en' => 'A test technology company for API testing',
            'tr' => 'API testi için test teknoloji şirketi',
        ],
        'website' => 'https://testtechcompany.com',
        'logo' => 'logos/test-tech.jpg',
    ];

    $createResult = testEndpoint('Create Company', "$baseUrl/companies", 'POST', $companyData, $authHeaders, 201);

    if ($createResult && isset($createResult['body']['data']['id'])) {
        $companyId = $createResult['body']['data']['id'];
        echo "  Created Company ID: $companyId\n";

        // Get company by ID
        testEndpoint('Get Company by ID', "$baseUrl/companies/$companyId");

        // Update Company
        $updateData = [
            'name' => 'Updated Test Tech Company',
            'description' => [
                'en' => 'Updated description for the test company',
                'tr' => 'Test şirketi için güncellenmiş açıklama',
            ],
        ];

        testEndpoint('Update Company', "$baseUrl/companies/$companyId", 'PATCH', $updateData, $authHeaders);

        // Get user's companies
        testEndpoint('Get My Companies', "$baseUrl/companies/my", 'GET', null, $authHeaders);

        // Delete Company
        testEndpoint('Delete Company', "$baseUrl/companies/$companyId", 'DELETE', null, $authHeaders);
    }

    // Test error cases
    echo Colors::YELLOW."\n=== ERROR HANDLING ===\n".Colors::RESET;
    testEndpoint('Get Non-existent Company', "$baseUrl/companies/99999", 'GET', null, [], 404);

    // Test validation errors
    $invalidCompanyData = [
        'name' => '', // Empty name should fail
        'country_code' => 'INVALID', // Invalid country code
    ];
    testEndpoint('Create Company with Invalid Data', "$baseUrl/companies", 'POST', $invalidCompanyData, $authHeaders, 422);

} else {
    echo Colors::YELLOW."⚠ Skipping authenticated endpoint tests - no auth token available\n".Colors::RESET;
}

// Test filtering
echo Colors::YELLOW."\n=== FILTERING TESTS ===\n".Colors::RESET;
testEndpoint('Filter Companies by Country', "$baseUrl/companies?country_code=US");
testEndpoint('Search Companies', "$baseUrl/companies?search=tech");
testEndpoint('Filter VIP Companies by Country', "$baseUrl/companies/vip?country_code=US");

// Summary
echo Colors::YELLOW."\n=== TEST SUMMARY ===\n".Colors::RESET;
echo Colors::GREEN."✅ Company API tests completed!\n".Colors::RESET;
echo "\n📝 Notes:\n";
echo "- Make sure your Laravel server is running on http://localhost:8000\n";
echo "- Check the database for the created test companies\n";
echo "- All company descriptions are translatable (en/tr support)\n";
echo "- VIP and status management require admin privileges in production\n";
echo "- For production testing, update the \$baseUrl variable\n";
echo "\n💡 To view API documentation: http://localhost:8000/docs\n";
