<?php
require_once 'auth/config.php';

// Check if user is logged in and has admin role
if (!isAuthenticated() || !isAdmin()) {
    echo "<h1>Not authorized</h1>";
    echo "<p>You must be logged in as an admin to use this page.</p>";
    exit;
}

// Get current user data
$user = getCurrentUser();

// Function to display results
function displayResult($title, $response) {
    echo "<h2>$title</h2>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    echo "<hr>";
}

// Test 1: Create a test product
$testProductId = 'test-product-' . time();
$createData = [
    'id' => $testProductId,
    'name' => 'Test Product',
    'description' => 'This is a test product created at ' . date('Y-m-d H:i:s'),
    'price' => 99.99,
    'category' => 'test',
    'is_featured' => false,
    'is_active' => true,
    'created_at' => date('c'),
    'updated_at' => date('c')
];

$createResponse = authenticatedRequest(
    '/rest/v1/products',
    'POST',
    $createData
);

displayResult("1. Create Test Product", $createResponse);

// Test 2: Get the created product
$getResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.' . urlencode($testProductId) . '&select=*',
    'GET'
);

displayResult("2. Get Created Product", $getResponse);

// Test 3: Update the product
$updateData = [
    'name' => 'Updated Test Product',
    'is_featured' => true,
    'updated_at' => date('c')
];

$updateResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.' . urlencode($testProductId),
    'PATCH',
    $updateData
);

displayResult("3. Update Product", $updateResponse);

// Test 4: Get the updated product
$getUpdatedResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.' . urlencode($testProductId) . '&select=*',
    'GET'
);

displayResult("4. Get Updated Product", $getUpdatedResponse);

// Test 5: Delete the test product
$deleteResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.' . urlencode($testProductId),
    'DELETE'
);

displayResult("5. Delete Test Product", $deleteResponse);

// Test 6: Verify deletion
$verifyDeleteResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.' . urlencode($testProductId) . '&select=*',
    'GET'
);

displayResult("6. Verify Deletion", $verifyDeleteResponse);

// Test 7: Get all products
$allProductsResponse = authenticatedRequest(
    '/rest/v1/products?select=*',
    'GET'
);

displayResult("7. All Products", $allProductsResponse);

// Test 8: Get featured products
$featuredProductsResponse = authenticatedRequest(
    '/rest/v1/products?is_featured=eq.true&select=*',
    'GET'
);

displayResult("8. Featured Products", $featuredProductsResponse);

// Test 9: Check RLS policies
echo "<h2>9. RLS Policies Check</h2>";
echo "<p>If you're seeing products above, the RLS policies are working correctly for your admin role.</p>";
echo "<p>Your current role: " . htmlspecialchars($user['user_metadata']['role'] ?? 'Not set') . "</p>";
echo "<hr>";

// Test 10: Check direct SQL access
$sqlResponse = authenticatedRequest(
    '/rest/v1/rpc/check_products_table',
    'POST',
    []
);

displayResult("10. SQL Table Check", $sqlResponse);
?>
