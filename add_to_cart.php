<?php
require_once 'auth/config.php';

// Check if user is logged in
$user = getCurrentUser();
if (!$user) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Check if product ID is provided
if (!isset($_POST['product_id'])) {
    header('Location: index.php?error=Product ID is required');
    exit;
}

$productId = $_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($quantity <= 0) {
    $quantity = 1;
}

// Check if product exists and is active
$productResponse = supabaseRequest(
    '/rest/v1/products?id=eq.' . urlencode($productId) . '&is_active=eq.true&select=id',
    'GET'
);

if ($productResponse['statusCode'] !== 200 || empty($productResponse['data'])) {
    header('Location: index.php?error=Product not found or not available');
    exit;
}

// Check if product is already in cart
$cartItemResponse = authenticatedRequest(
    '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']) . '&product_id=eq.' . urlencode($productId) . '&select=*',
    'GET'
);

if ($cartItemResponse['statusCode'] === 200 && !empty($cartItemResponse['data'])) {
    // Update quantity
    $existingItem = $cartItemResponse['data'][0];
    $newQuantity = $existingItem['quantity'] + $quantity;
    
    $updateData = [
        'quantity' => $newQuantity,
        'updated_at' => date('c')
    ];
    
    $updateResponse = authenticatedRequest(
        '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']) . '&product_id=eq.' . urlencode($productId),
        'PATCH',
        $updateData
    );
    
    if ($updateResponse['statusCode'] === 204) {
        // Redirect back to the product page or referrer
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'product.php?id=' . urlencode($productId);
        header('Location: ' . $redirectUrl . '&success=Product quantity updated in cart');
        exit;
    } else {
        // Redirect with error
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'product.php?id=' . urlencode($productId);
        header('Location: ' . $redirectUrl . '&error=Failed to update cart');
        exit;
    }
} else {
    // Add new item to cart
    $cartItemData = [
        'user_id' => $user['id'],
        'product_id' => $productId,
        'quantity' => $quantity,
        'created_at' => date('c'),
        'updated_at' => date('c')
    ];
    
    $createResponse = authenticatedRequest(
        '/rest/v1/cart_items',
        'POST',
        $cartItemData
    );
    
    if ($createResponse['statusCode'] === 201) {
        // Redirect back to the product page or referrer
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'product.php?id=' . urlencode($productId);
        header('Location: ' . $redirectUrl . '&success=Product added to cart');
        exit;
    } else {
        // Redirect with error
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'product.php?id=' . urlencode($productId);
        header('Location: ' . $redirectUrl . '&error=Failed to add product to cart');
        exit;
    }
}
?>
