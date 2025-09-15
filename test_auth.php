<?php

/**
 * Authentication API Test Script
 *
 * This script tests all auth endpoints to ensure they're working correctly.
 * Run this script from the command line: php test_auth.php
 */

// Configuration
$baseUrl = 'http://127.0.0.1:8000/api';
$testEmail = 'test_'.time().'@example.com'; // Unique email each time
$testPassword = 'TestPassword123!';
$testName = 'Test User';
$testPhone = '+123456'.rand(1000, 9999); // Unique phone each time

// Colors for console output
class Colors
{
    const GREEN = "\033[32m";

    const RED = "\033[31m";

    const YELLOW = "\033[33m";

    const BLUE = "\033[34m";

    const RESET = "\033[0m";
}

// Helper function to make HTTP requests
function makeRequest($method, $url, $data = null, $headers = [])
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
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
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error: $error");
    }

    return [
        'status_code' => $httpCode,
        'body' => json_decode($response, true),
        'raw_body' => $response,
    ];
}

// Test result tracking
$testResults = [];
$totalTests = 0;
$passedTests = 0;

function runTest($testName, $expectedCode, $actualCode, $response = null)
{
    global $testResults, $totalTests, $passedTests;

    $totalTests++;
    $passed = ($actualCode == $expectedCode);

    if ($passed) {
        $passedTests++;
        echo Colors::GREEN.'✓ PASS'.Colors::RESET." - $testName\n";
    } else {
        echo Colors::RED.'✗ FAIL'.Colors::RESET." - $testName (Expected: $expectedCode, Got: $actualCode)\n";
        if ($response) {
            echo Colors::YELLOW.'  Response: '.json_encode($response, JSON_PRETTY_PRINT).Colors::RESET."\n";
        }
    }

    $testResults[] = [
        'name' => $testName,
        'passed' => $passed,
        'expected' => $expectedCode,
        'actual' => $actualCode,
    ];
}

function printHeader($title)
{
    echo "\n".Colors::BLUE."=== $title ===".Colors::RESET."\n";
}

function printSummary()
{
    global $totalTests, $passedTests;

    echo "\n".Colors::BLUE.'=== TEST SUMMARY ==='.Colors::RESET."\n";
    echo "Total Tests: $totalTests\n";
    echo Colors::GREEN."Passed: $passedTests".Colors::RESET."\n";
    echo Colors::RED.'Failed: '.($totalTests - $passedTests).Colors::RESET."\n";
    echo 'Success Rate: '.round(($passedTests / $totalTests) * 100, 2)."%\n\n";

    if ($passedTests == $totalTests) {
        echo Colors::GREEN.'🎉 All tests passed!'.Colors::RESET."\n";
    } else {
        echo Colors::RED.'❌ Some tests failed. Check the output above.'.Colors::RESET."\n";
    }
}

// Global variables to store auth data
$authToken = null;
$userId = null;

try {
    echo Colors::BLUE."🚀 Starting Authentication API Tests\n".Colors::RESET;
    echo "Base URL: $baseUrl\n";
    echo "Test Email: $testEmail\n\n";

    // Clean up: Try to delete test user first (ignore errors)
    try {
        // We'll skip cleanup for now since we don't have a delete endpoint
    } catch (Exception $e) {
        // Ignore cleanup errors
    }

    printHeader('HEALTH CHECK');

    // Test API Health
    try {
        $response = makeRequest('GET', "$baseUrl/health");
        runTest('API Health Check', 200, $response['status_code'], $response['body']);
    } catch (Exception $e) {
        echo Colors::RED.'✗ FAIL - API Health Check: '.$e->getMessage().Colors::RESET."\n";
    }

    printHeader('USER REGISTRATION');

    // Test User Registration
    try {
        $registerData = [
            'name' => $testName,
            'email' => $testEmail,
            'password' => $testPassword,
            'password_confirmation' => $testPassword,
            'phone' => $testPhone,
            'country_code' => 'US',
        ];

        $response = makeRequest('POST', "$baseUrl/auth/register", $registerData);
        runTest('User Registration', 201, $response['status_code'], $response['body']);

        if ($response['status_code'] == 201 && isset($response['body']['data']['user']['id'])) {
            $userId = $response['body']['data']['user']['id'];
            echo Colors::YELLOW."  Created User ID: $userId".Colors::RESET."\n";
        }
    } catch (Exception $e) {
        echo Colors::RED.'✗ FAIL - User Registration: '.$e->getMessage().Colors::RESET."\n";
    }

    printHeader('USER LOGIN');

    // Test User Login
    try {
        $loginData = [
            'email' => $testEmail,
            'password' => $testPassword,
        ];

        $response = makeRequest('POST', "$baseUrl/auth/login", $loginData);
        runTest('User Login', 200, $response['status_code'], $response['body']);

        if ($response['status_code'] == 200 && isset($response['body']['data']['token'])) {
            $authToken = $response['body']['data']['token'];
            echo Colors::YELLOW.'  Auth Token: '.substr($authToken, 0, 20).'...'.Colors::RESET."\n";
        }
    } catch (Exception $e) {
        echo Colors::RED.'✗ FAIL - User Login: '.$e->getMessage().Colors::RESET."\n";
    }

    // If we have an auth token, test authenticated endpoints
    if ($authToken) {
        $authHeaders = ["Authorization: Bearer $authToken"];

        printHeader('AUTHENTICATED ENDPOINTS');

        // Test Auth Check
        try {
            $response = makeRequest('GET', "$baseUrl/auth/check", null, $authHeaders);
            runTest('Auth Check', 200, $response['status_code'], $response['body']);
        } catch (Exception $e) {
            echo Colors::RED.'✗ FAIL - Auth Check: '.$e->getMessage().Colors::RESET."\n";
        }

        // Test Get Profile
        try {
            $response = makeRequest('GET', "$baseUrl/auth/profile", null, $authHeaders);
            runTest('Get Profile', 200, $response['status_code'], $response['body']);
        } catch (Exception $e) {
            echo Colors::RED.'✗ FAIL - Get Profile: '.$e->getMessage().Colors::RESET."\n";
        }

        // Test Update Profile
        try {
            $updateData = [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'phone' => '+0987654321',
            ];

            $response = makeRequest('PATCH', "$baseUrl/auth/profile", $updateData, $authHeaders);
            runTest('Update Profile', 200, $response['status_code'], $response['body']);
        } catch (Exception $e) {
            echo Colors::RED.'✗ FAIL - Update Profile: '.$e->getMessage().Colors::RESET."\n";
        }

        // Test Token Refresh
        try {
            $response = makeRequest('POST', "$baseUrl/auth/refresh", null, $authHeaders);
            runTest('Token Refresh', 200, $response['status_code'], $response['body']);

            if ($response['status_code'] == 200 && isset($response['body']['data']['token'])) {
                $newToken = $response['body']['data']['token'];
                echo Colors::YELLOW.'  New Token: '.substr($newToken, 0, 20).'...'.Colors::RESET."\n";
                $authToken = $newToken; // Use new token for remaining tests
                $authHeaders = ["Authorization: Bearer $authToken"];
            }
        } catch (Exception $e) {
            echo Colors::RED.'✗ FAIL - Token Refresh: '.$e->getMessage().Colors::RESET."\n";
        }

        printHeader('PASSWORD MANAGEMENT');

        // Test Change Password
        try {
            $newPassword = 'NewTestPassword123!';
            $changePasswordData = [
                'current_password' => $testPassword,
                'new_password' => $newPassword,
                'new_password_confirmation' => $newPassword,
            ];

            $response = makeRequest('POST', "$baseUrl/auth/password/change", $changePasswordData, $authHeaders);
            runTest('Change Password', 200, $response['status_code'], $response['body']);

            // Update password for subsequent tests
            if ($response['status_code'] == 200) {
                $testPassword = $newPassword;
            }
        } catch (Exception $e) {
            echo Colors::RED.'✗ FAIL - Change Password: '.$e->getMessage().Colors::RESET."\n";
        }

        // Test Logout
        try {
            $response = makeRequest('POST', "$baseUrl/auth/logout", null, $authHeaders);
            runTest('User Logout', 200, $response['status_code'], $response['body']);
        } catch (Exception $e) {
            echo Colors::RED.'✗ FAIL - User Logout: '.$e->getMessage().Colors::RESET."\n";
        }
    } else {
        echo Colors::RED.'⚠ Skipping authenticated endpoint tests - no auth token available'.Colors::RESET."\n";
    }

    printHeader('PASSWORD RESET FLOW');

    // Test Forgot Password
    try {
        $forgotPasswordData = ['email' => $testEmail];
        $response = makeRequest('POST', "$baseUrl/auth/password/reset-link", $forgotPasswordData);
        runTest('Forgot Password', 200, $response['status_code'], $response['body']);
    } catch (Exception $e) {
        echo Colors::RED.'✗ FAIL - Forgot Password: '.$e->getMessage().Colors::RESET."\n";
    }

    printHeader('ERROR HANDLING TESTS');

    // Test Invalid Login
    try {
        $invalidLoginData = [
            'email' => $testEmail,
            'password' => 'WrongPassword123!',
        ];

        $response = makeRequest('POST', "$baseUrl/auth/login", $invalidLoginData);
        runTest('Invalid Login (Wrong Password)', 401, $response['status_code'], $response['body']);
    } catch (Exception $e) {
        echo Colors::RED.'✗ FAIL - Invalid Login Test: '.$e->getMessage().Colors::RESET."\n";
    }

    // Test Duplicate Registration
    try {
        $duplicateData = [
            'name' => 'Duplicate User',
            'email' => $testEmail, // Same email
            'password' => $testPassword,
            'password_confirmation' => $testPassword,
        ];

        $response = makeRequest('POST', "$baseUrl/auth/register", $duplicateData);
        runTest('Duplicate Registration', 422, $response['status_code'], $response['body']);
    } catch (Exception $e) {
        echo Colors::RED.'✗ FAIL - Duplicate Registration Test: '.$e->getMessage().Colors::RESET."\n";
    }

    // Test Invalid Token Access
    try {
        $invalidHeaders = ['Authorization: Bearer invalid-token-123'];
        $response = makeRequest('GET', "$baseUrl/auth/profile", null, $invalidHeaders);
        runTest('Invalid Token Access', 401, $response['status_code'], $response['body']);
    } catch (Exception $e) {
        echo Colors::RED.'✗ FAIL - Invalid Token Test: '.$e->getMessage().Colors::RESET."\n";
    }

} catch (Exception $e) {
    echo Colors::RED.'❌ Fatal Error: '.$e->getMessage().Colors::RESET."\n";
} finally {
    printSummary();
}

echo Colors::BLUE."\n📝 Notes:\n".Colors::RESET;
echo "- Make sure your Laravel server is running on http://localhost:8000\n";
echo "- Check the database for the created test user\n";
echo "- Email verification tokens are generated but not tested here\n";
echo "- Password reset requires email functionality to be fully tested\n";
echo "- For production testing, update the \$baseUrl variable\n\n";

echo Colors::YELLOW."💡 To view API documentation: http://localhost:8000/docs\n".Colors::RESET;
