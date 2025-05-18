<?php
require_once 'auth/config.php';

// Check if user is logged in
if (!isAuthenticated()) {
    echo "<h1>Not logged in</h1>";
    echo "<p>Please <a href='login.php'>log in</a> first.</p>";
    exit;
}

// Get current user data
$user = getCurrentUser();

// Test direct SQL query to check products table
$response = authenticatedRequest(
    '/rest/v1/rpc/check_products_table',
    'POST',
    []
);

echo "<h1>Check Products Table</h1>";
echo "<pre>";
print_r($response);
echo "</pre>";

// Try to get all products
$productsResponse = authenticatedRequest(
    '/rest/v1/products?select=*',
    'GET'
);

echo "<h2>All Products</h2>";
echo "<pre>";
print_r($productsResponse);
echo "</pre>";

// Try to get a specific product
$specificProductResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.staff-panel&select=*',
    'GET'
);

echo "<h2>Specific Product (staff-panel)</h2>";
echo "<pre>";
print_r($specificProductResponse);
echo "</pre>";

// Try to update a product
$updateData = [
    'name' => 'Staff Panel Updated',
    'is_featured' => true,
    'updated_at' => date('c')
];

$updateResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.staff-panel',
    'PATCH',
    $updateData
);

echo "<h2>Update Response</h2>";
echo "<pre>";
print_r($updateResponse);
echo "</pre>";

// Check if the update worked
$afterUpdateResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.staff-panel&select=*',
    'GET'
);

echo "<h2>After Update</h2>";
echo "<pre>";
print_r($afterUpdateResponse);
echo "</pre>";
?>
