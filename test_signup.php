<?php
require_once 'auth/config.php';

echo "<h1>Supabase Signup Test</h1>";

// Test data
$testEmail = "test_" . time() . "@example.com";
$testPassword = "Password123!";
$userData = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'full_name' => 'Test User'
];

echo "<h2>Test Data</h2>";
echo "<p>Email: $testEmail</p>";
echo "<p>Password: $testPassword</p>";
echo "<pre>User Data: " . print_r($userData, true) . "</pre>";

echo "<h2>Testing Signup</h2>";

try {
    // Attempt to sign up
    $response = signUp($testEmail, $testPassword, $userData);
    
    echo "<h3>Response Status Code: " . $response['statusCode'] . "</h3>";
    
    if ($response['statusCode'] >= 200 && $response['statusCode'] < 300) {
        echo '<p style="color:green">Signup successful!</p>';
    } else {
        echo '<p style="color:red">Signup failed!</p>';
    }
    
    echo "<h3>Response Data:</h3>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    
} catch (Exception $e) {
    echo '<p style="color:red">Error: ' . $e->getMessage() . '</p>';
}

echo "<h2>Testing Login</h2>";

try {
    // Attempt to sign in with the test account
    $response = signIn($testEmail, $testPassword);
    
    echo "<h3>Response Status Code: " . $response['statusCode'] . "</h3>";
    
    if ($response['statusCode'] >= 200 && $response['statusCode'] < 300) {
        echo '<p style="color:green">Login successful!</p>';
    } else {
        echo '<p style="color:red">Login failed!</p>';
    }
    
    echo "<h3>Response Data:</h3>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    
} catch (Exception $e) {
    echo '<p style="color:red">Error: ' . $e->getMessage() . '</p>';
}
?>
