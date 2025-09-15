<?php

require_once 'vendor/autoload.php';

/**
 * Test script to verify logo upload functionality for companies
 * This script tests the company creation and update with logo file uploads
 */

// Configuration
$baseUrl = 'http://localhost:8000/api';
$testEmail = 'test_logo@example.com';
$testPassword = 'password123';

echo "=== Company Logo Upload Test ===\n\n";

// Step 1: Register a test user
echo "1. Registering test user...\n";
$registerData = [
    'name' => 'Logo Test User',
    'email' => $testEmail,
    'password' => $testPassword,
    'password_confirmation' => $testPassword,
];

$registerResponse = makeCurlRequest($baseUrl.'/auth/register', 'POST', $registerData);
echo 'Register response: '.json_encode($registerResponse, JSON_PRETTY_PRINT)."\n\n";

// Step 2: Login to get auth token
echo "2. Logging in...\n";
$loginData = [
    'email' => $testEmail,
    'password' => $testPassword,
];

$loginResponse = makeCurlRequest($baseUrl.'/auth/login', 'POST', $loginData);
echo 'Login response: '.json_encode($loginResponse, JSON_PRETTY_PRINT)."\n\n";

if (! isset($loginResponse['data']['token'])) {
    echo "❌ Failed to get auth token. Exiting.\n";
    exit(1);
}

$token = $loginResponse['data']['token'];
echo '✅ Auth token obtained: '.substr($token, 0, 20)."...\n\n";

// Step 3: Create a simple test image file
echo "3. Creating test logo file...\n";
$logoPath = '/tmp/test_logo.png';
createTestImage($logoPath);
echo "✅ Test logo created at: $logoPath\n\n";

// Step 4: Create company with logo
echo "4. Creating company with logo...\n";
$companyData = [
    'name' => 'Logo Test Company',
    'country_code' => 'US',
    'description[en]' => 'A test company with logo',
    'description[tr]' => 'Logolu test şirketi',
    'website' => 'https://logotest.example.com',
];

$createResponse = makeCurlRequestWithFile(
    $baseUrl.'/companies',
    'POST',
    $companyData,
    'logo',
    $logoPath,
    $token
);
echo 'Create company response: '.json_encode($createResponse, JSON_PRETTY_PRINT)."\n\n";

if (! isset($createResponse['data']['id'])) {
    echo "❌ Failed to create company. Exiting.\n";
    exit(1);
}

$companyId = $createResponse['data']['id'];
echo "✅ Company created with ID: $companyId\n";

// Check if logo URL is generated
if (isset($createResponse['data']['logo_url'])) {
    echo '✅ Logo URL generated: '.$createResponse['data']['logo_url']."\n\n";
} else {
    echo "⚠️ Logo URL not found in response\n\n";
}

// Step 5: Update company with new logo
echo "5. Creating new test logo for update...\n";
$newLogoPath = '/tmp/test_logo_new.jpg';
createTestImage($newLogoPath, 'jpg');
echo "✅ New test logo created at: $newLogoPath\n\n";

echo "6. Updating company with new logo...\n";
$updateData = [
    'name' => 'Updated Logo Test Company',
    'description[en]' => 'Updated test company with new logo',
];

$updateResponse = makeCurlRequestWithFile(
    $baseUrl.'/companies/'.$companyId,
    'PATCH',
    $updateData,
    'logo',
    $newLogoPath,
    $token,
    'image/jpeg'
);
echo 'Update company response: '.json_encode($updateResponse, JSON_PRETTY_PRINT)."\n\n";

// Step 6: Get company to verify logo
echo "7. Getting company details to verify logo...\n";
$getResponse = makeCurlRequest($baseUrl.'/companies/'.$companyId, 'GET', null, $token);
echo 'Get company response: '.json_encode($getResponse, JSON_PRETTY_PRINT)."\n\n";

// Step 7: Clean up - Delete company
echo "8. Cleaning up - deleting company...\n";
$deleteResponse = makeCurlRequest($baseUrl.'/companies/'.$companyId, 'DELETE', null, $token);
echo 'Delete response: '.json_encode($deleteResponse, JSON_PRETTY_PRINT)."\n\n";

// Clean up test files
unlink($logoPath);
unlink($newLogoPath);
echo "✅ Test files cleaned up\n\n";

echo "=== Test Complete ===\n";

/**
 * Make a cURL request
 */
function makeCurlRequest($url, $method = 'GET', $data = null, $token = null)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        $token ? 'Authorization: Bearer '.$token : '',
    ]);

    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_error($ch)) {
        echo 'cURL Error: '.curl_error($ch)."\n";
    }

    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($decoded === null) {
        return ['raw_response' => $response, 'http_code' => $httpCode];
    }

    return $decoded;
}

/**
 * Make a cURL request with file upload
 */
function makeCurlRequestWithFile($url, $method, $data, $fileField, $filePath, $token, $mimeType = 'image/png')
{
    $ch = curl_init();

    // Prepare multipart form data
    $postData = $data;
    $postData[$fileField] = new CURLFile($filePath, $mimeType, basename($filePath));

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer '.$token,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_error($ch)) {
        echo 'cURL Error: '.curl_error($ch)."\n";
    }

    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($decoded === null) {
        return ['raw_response' => $response, 'http_code' => $httpCode];
    }

    return $decoded;
}

/**
 * Create a simple test image file
 */
function createTestImage($path, $format = 'png')
{
    $image = imagecreate(100, 100);
    $background = imagecolorallocate($image, 255, 255, 255); // White background
    $textColor = imagecolorallocate($image, 0, 0, 0); // Black text

    imagestring($image, 5, 10, 40, 'LOGO', $textColor);

    if ($format === 'jpg' || $format === 'jpeg') {
        imagejpeg($image, $path, 90);
    } else {
        imagepng($image, $path);
    }

    imagedestroy($image);
}
