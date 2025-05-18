<?php
require_once 'auth/config.php';

// Check if user is logged in
$user = getCurrentUser();
if (!$user) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=' . urlencode('cart.php'));
    exit;
}

// Handle cart actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'remove' && isset($_GET['id'])) {
        $productId = $_GET['id'];

        // Remove item from cart
        $deleteResponse = authenticatedRequest(
            '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']) . '&product_id=eq.' . urlencode($productId),
            'DELETE'
        );

        if ($deleteResponse['statusCode'] === 204) {
            header('Location: cart.php?success=Item removed from cart');
            exit;
        } else {
            $error = 'Failed to remove item from cart';
        }
    } elseif ($action === 'update' && isset($_POST['quantity']) && isset($_POST['product_id'])) {
        $productId = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity <= 0) {
            // If quantity is 0 or negative, remove the item
            $deleteResponse = authenticatedRequest(
                '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']) . '&product_id=eq.' . urlencode($productId),
                'DELETE'
            );

            if ($deleteResponse['statusCode'] === 204) {
                header('Location: cart.php?success=Item removed from cart');
                exit;
            } else {
                $error = 'Failed to remove item from cart';
            }
        } else {
            // Update quantity
            $updateData = [
                'quantity' => $quantity,
                'updated_at' => date('c')
            ];

            $updateResponse = authenticatedRequest(
                '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']) . '&product_id=eq.' . urlencode($productId),
                'PATCH',
                $updateData
            );

            if ($updateResponse['statusCode'] === 204) {
                header('Location: cart.php?success=Cart updated');
                exit;
            } else {
                $error = 'Failed to update cart';
            }
        }
    } elseif ($action === 'clear') {
        // Clear entire cart
        $deleteResponse = authenticatedRequest(
            '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']),
            'DELETE'
        );

        if ($deleteResponse['statusCode'] === 204) {
            header('Location: cart.php?success=Cart cleared');
            exit;
        } else {
            $error = 'Failed to clear cart';
        }
    }
}

// Get cart items
$cartItemsResponse = authenticatedRequest(
    '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']) . '&select=*,product:products(*)',
    'GET'
);

$cartItems = [];
$totalAmount = 0;

if ($cartItemsResponse['statusCode'] === 200) {
    $cartItems = $cartItemsResponse['data'];

    // Calculate total
    foreach ($cartItems as $item) {
        if ($item['product']['is_subscription']) {
            // For subscription products, add both the subscription price and any one-time setup fee
            $totalAmount += ($item['product']['subscription_price'] + $item['product']['price']) * $item['quantity'];
        } else {
            // For regular products, just use the product price
            $totalAmount += $item['product']['price'] * $item['quantity'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - BlueHaven</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php require_once 'includes/header.php'; ?>
    <style>
        :root {
            --primary-bg: #0f0f0f;
            --secondary-bg: #1a1a1a;
            --accent-color: #3b82f6;
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --border-color: #2a2a2a;
            --error-color: #ef4444;
            --success-color: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        header {
            background-color: var(--secondary-bg);
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
        }

        .logo span {
            color: var(--accent-color);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: var(--accent-color);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .user-greeting {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-right: 0.5rem;
        }

        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .btn-outline {
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-primary);
        }

        .btn-outline:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .btn-danger {
            background-color: var(--error-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        <?php echo getCartIconCss(); ?>

        /* Cart Styles */
        .cart-section {
            padding: 3rem 0;
            flex: 1;
        }

        .cart-header {
            margin-bottom: 2rem;
        }

        .cart-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .cart-header p {
            color: var(--text-secondary);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .cart-empty {
            text-align: center;
            padding: 3rem;
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
        }

        .cart-empty h2 {
            margin-bottom: 1rem;
        }

        .cart-empty p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .cart-table th {
            text-align: left;
            padding: 1rem;
            background-color: var(--secondary-bg);
            border-bottom: 1px solid var(--border-color);
        }

        .cart-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .cart-product {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cart-product-image {
            width: 80px;
            height: 80px;
            background-color: var(--secondary-bg);
            border-radius: 0.25rem;
            overflow: hidden;
        }

        .cart-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-product-details h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .cart-product-details p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .quantity-input {
            display: flex;
            align-items: center;
        }

        .quantity-input input {
            width: 60px;
            padding: 0.5rem;
            text-align: center;
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.25rem;
        }

        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }

        .cart-summary {
            background-color: var(--secondary-bg);
            padding: 1.5rem;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 400px;
            margin-left: auto;
        }

        .cart-summary h3 {
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .summary-row.total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .checkout-btn {
            width: 100%;
            margin-top: 1.5rem;
            padding: 1rem;
            font-size: 1.1rem;
        }

        /* Footer */
        footer {
            background-color: var(--secondary-bg);
            padding: 3rem 0 1.5rem;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .footer-column h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 0.5rem;
        }

        .footer-column a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-column a:hover {
            color: var(--accent-color);
        }

        .copyright {
            text-align: center;
            color: var(--text-secondary);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }

            .cart-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 768px) {
            nav ul, .auth-buttons {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .cart-actions {
                flex-direction: column;
                gap: 1.5rem;
            }

            .cart-summary {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">Blue<span>Haven</span></a>

                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Store</a></li>
                        <li><a href="#">Team</a></li>
                        <li><a href="#">Reviews</a></li>
                        <li><a href="#">Documentation</a></li>
                        <?php if (isAdmin()): ?>
                        <li><a href="admin/index.php" style="color: var(--accent-color);">Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="auth-buttons">
                    <?php echo getCartIconHtml($user); ?>
                    <span class="user-greeting">Hello, <?php echo htmlspecialchars($user['user_metadata']['first_name'] ?? $user['email']); ?></span>
                    <a href="profile.php" class="btn btn-outline">My Profile</a>
                    <a href="auth/logout_handler.php" class="btn btn-outline">Logout</a>
                </div>

                <button class="mobile-menu-btn">☰</button>
            </div>
        </div>
    </header>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <div class="cart-header">
                <h1>Your Cart</h1>
                <p>Review and manage your items before checkout</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
            <div class="cart-empty">
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any products to your cart yet.</p>
                <a href="products.php" class="btn btn-primary">Browse Products</a>
            </div>
            <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <div class="cart-product">
                                <div class="cart-product-image">
                                    <?php if (!empty($item['product']['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['product']['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product']['name']); ?>">
                                    <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-product-details">
                                    <h3><?php echo htmlspecialchars($item['product']['name']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($item['product']['description'], 0, 50) . (strlen($item['product']['description']) > 50 ? '...' : '')); ?></p>
                                    <?php if ($item['product']['is_subscription']): ?>
                                    <span style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; background-color: rgba(16, 185, 129, 0.1); color: var(--success-color); margin-top: 0.5rem;">
                                        Subscription
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($item['product']['is_subscription']): ?>
                            <div>
                                $<?php echo number_format($item['product']['subscription_price'], 2); ?>/<?php echo $item['product']['subscription_interval']; ?>
                                <?php if ($item['product']['price'] > 0): ?>
                                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    + $<?php echo number_format($item['product']['price'], 2); ?> setup
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            $<?php echo number_format($item['product']['price'], 2); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="cart.php?action=update" method="post" class="quantity-input">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product_id']); ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" onchange="this.form.submit()">
                            </form>
                        </td>
                        <td>
                            <?php if ($item['product']['is_subscription']): ?>
                            $<?php echo number_format(($item['product']['subscription_price'] + $item['product']['price']) * $item['quantity'], 2); ?>
                            <?php else: ?>
                            $<?php echo number_format($item['product']['price'] * $item['quantity'], 2); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="cart.php?action=remove&id=<?php echo htmlspecialchars($item['product_id']); ?>" class="btn btn-outline" onclick="return confirm('Are you sure you want to remove this item?')">Remove</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-actions">
                <a href="cart.php?action=clear" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear your cart?')">Clear Cart</a>

                <div class="cart-summary">
                    <h3>Order Summary</h3>

                    <?php
                    // Calculate one-time and recurring amounts
                    $oneTimeAmount = 0;
                    $recurringAmount = 0;
                    $hasSubscriptions = false;

                    foreach ($cartItems as $item) {
                        if ($item['product']['is_subscription']) {
                            $hasSubscriptions = true;
                            // Add one-time setup fee to one-time amount
                            $oneTimeAmount += $item['product']['price'] * $item['quantity'];
                            // Add subscription price to recurring amount
                            $recurringAmount += $item['product']['subscription_price'] * $item['quantity'];
                        } else {
                            // Regular product price goes to one-time amount
                            $oneTimeAmount += $item['product']['price'] * $item['quantity'];
                        }
                    }
                    ?>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($totalAmount, 2); ?></span>
                    </div>

                    <?php if ($hasSubscriptions): ?>
                    <div style="margin: 1rem 0; padding: 0.75rem; background-color: rgba(59, 130, 246, 0.1); border-radius: 0.375rem;">
                        <div style="margin-bottom: 0.5rem; font-weight: 500; color: var(--accent-color);">Subscription Details</div>
                        <?php if ($oneTimeAmount > 0): ?>
                        <div class="summary-row" style="margin-bottom: 0.5rem;">
                            <span style="font-size: 0.9rem;">One-time payment</span>
                            <span style="font-size: 0.9rem;">$<?php echo number_format($oneTimeAmount, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-row" style="margin-bottom: 0;">
                            <span style="font-size: 0.9rem;">Recurring payment</span>
                            <span style="font-size: 0.9rem;">$<?php echo number_format($recurringAmount, 2); ?>/month</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>

                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($totalAmount, 2); ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-primary checkout-btn">Proceed to Checkout</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>BlueHaven</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Our Team</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Products</h3>
                    <ul>
                        <li><a href="products.php?category=scripts">Scripts</a></li>
                        <li><a href="products.php?category=vehicles">Vehicle Liveries</a></li>
                        <li><a href="products.php?category=eup">EUP Packages</a></li>
                        <li><a href="products.php?category=websites">Web Solutions</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Tutorials</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Refund Policy</a></li>
                        <li><a href="#">License</a></li>
                    </ul>
                </div>
            </div>

            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> BlueHaven. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
