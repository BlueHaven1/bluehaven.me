<?php
require_once 'auth/config.php';

echo "<h1>Supabase Connection Test</h1>";

echo "<h2>Configuration</h2>";
echo "<p>Supabase URL: " . (SUPABASE_URL === 'YOUR_SUPABASE_URL' ? '<span style="color:red">NOT SET</span>' : '<span style="color:green">SET</span>') . "</p>";
echo "<p>Supabase Key: " . (SUPABASE_KEY === 'YOUR_SUPABASE_ANON_KEY' ? '<span style="color:red">NOT SET</span>' : '<span style="color:green">SET</span>') . "</p>";

echo "<h2>Testing Connection</h2>";

try {
    // Test a simple request to Supabase
    $response = supabaseRequest('/auth/v1/settings', 'GET');
    
    echo "<h3>Response Status Code: " . $response['statusCode'] . "</h3>";
    
    if ($response['statusCode'] >= 200 && $response['statusCode'] < 300) {
        echo '<p style="color:green">Connection successful!</p>';
    } else {
        echo '<p style="color:red">Connection failed!</p>';
    }
    
    echo "<h3>Response Data:</h3>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    
} catch (Exception $e) {
    echo '<p style="color:red">Error: ' . $e->getMessage() . '</p>';
}
?>
