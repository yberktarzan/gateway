<?php

/**
 * Quick Auth Test Script
 *
 * A minimal script to quickly test if auth endpoints are working.
 * Usage: php quick_test.php
 */
$baseUrl = 'http://127.0.0.1:8000/api';
$timestamp = time();
$email = "quicktest{$timestamp}@example.com";
$password = 'TestPass123';

function request($method, $url, $data = null, $token = null)
{
    $ch = curl_init();
    $headers = ['Content-Type: application/json', 'Accept: application/json'];

    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['code' => 0, 'data' => null, 'error' => $error];
    }

    return ['code' => $code, 'data' => json_decode($response, true), 'raw' => $response];
}

echo "🚀 Quick Auth Test\n";
echo "Testing URL: $baseUrl\n\n";

try {
    // 1. Health Check
    echo '1. Health Check... ';
    $health = request('GET', "$baseUrl/health");
    if ($health['code'] == 0) {
        echo "❌ CONNECTION ERROR: {$health['error']}\n";
        echo "💡 Make sure Laravel server is running: php artisan serve\n";
        exit(1);
    }
    echo ($health['code'] == 200) ? "✅ OK\n" : "❌ FAILED ({$health['code']})\n";

    // 2. Register
    echo '2. Register User... ';
    $registerData = [
        'name' => 'Quick Test',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ];
    echo '   Sending: '.json_encode($registerData)."\n";

    $register = request('POST', "$baseUrl/auth/register", $registerData);
    if ($register['code'] == 201) {
        echo "✅ OK\n";
    } else {
        echo "❌ FAILED ({$register['code']})\n";
        if (isset($register['data'])) {
            echo '   Full response: '.json_encode($register['data'])."\n";
        }
    }

    // 3. Login
    echo '3. Login... ';
    echo "(trying email: $email) ";
    $login = request('POST', "$baseUrl/auth/login", [
        'email' => $email,
        'password' => $password,
    ]);
    if ($login['code'] == 200) {
        echo "✅ OK\n";
    } else {
        echo "❌ FAILED ({$login['code']})\n";
        if (isset($login['data'])) {
            echo '   Full response: '.json_encode($login['data'])."\n";
        }
    }

    $token = $login['data']['data']['token'] ?? null;

    if ($token) {
        // 4. Get Profile
        echo '4. Get Profile... ';
        $profile = request('GET', "$baseUrl/auth/profile", null, $token);
        echo ($profile['code'] == 200) ? "✅ OK\n" : "❌ FAILED ({$profile['code']})\n";

        // 5. Update Profile
        echo '5. Update Profile... ';
        $update = request('PATCH', "$baseUrl/auth/profile", [
            'first_name' => 'Updated',
            'last_name' => 'User',
        ], $token);
        echo ($update['code'] == 200) ? "✅ OK\n" : "❌ FAILED ({$update['code']})\n";

        // 6. Logout
        echo '6. Logout... ';
        $logout = request('POST', "$baseUrl/auth/logout", null, $token);
        echo ($logout['code'] == 200) ? "✅ OK\n" : "❌ FAILED ({$logout['code']})\n";
    } else {
        echo "❌ No token received, skipping authenticated tests\n";
    }

    echo "\n✨ Test completed! Check http://localhost:8000/docs for API documentation.\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
