<?php
require_once 'auth/config.php';

// Check if user is logged in
$user = getCurrentUser();
if (!$user) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=' . urlencode('checkout.php'));
    exit;
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
        $totalAmount += $item['product']['price'] * $item['quantity'];
    }
}

// If cart is empty, redirect to cart page
if (empty($cartItems)) {
    header('Location: cart.php?error=Your cart is empty');
    exit;
}

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create purchase record
    $purchaseData = [
        'user_id' => $user['id'],
        'total_amount' => $totalAmount,
        'status' => 'completed',
        'created_at' => date('c'),
        'updated_at' => date('c')
    ];

    $purchaseResponse = authenticatedRequest(
        '/rest/v1/purchases',
        'POST',
        $purchaseData
    );

    if ($purchaseResponse['statusCode'] === 201 && isset($purchaseResponse['data'][0]['id'])) {
        $purchaseId = $purchaseResponse['data'][0]['id'];

        // Add purchase items and handle subscriptions
        $allItemsAdded = true;
        $subscriptionsCreated = true;
        $hasSubscriptions = false;

        foreach ($cartItems as $item) {
            // Check if this is a subscription product
            $isSubscription = $item['product']['is_subscription'] ?? false;

            // Add to purchase items
            $purchaseItemData = [
                'purchase_id' => $purchaseId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price_at_purchase' => $isSubscription ? $item['product']['price'] : $item['product']['price'], // One-time fee for subscriptions
                'created_at' => date('c')
            ];

            $purchaseItemResponse = authenticatedRequest(
                '/rest/v1/purchase_items',
                'POST',
                $purchaseItemData
            );

            if ($purchaseItemResponse['statusCode'] !== 201) {
                $allItemsAdded = false;
                break;
            }

            // If it's a subscription product, create a subscription
            if ($isSubscription) {
                $hasSubscriptions = true;

                // Calculate next billing date based on interval
                $nextBillingDate = date('c', strtotime('+1 ' . ($item['product']['subscription_interval'] ?? 'month')));

                $subscriptionData = [
                    'user_id' => $user['id'],
                    'product_id' => $item['product_id'],
                    'status' => 'active',
                    'start_date' => date('c'),
                    'next_billing_date' => $nextBillingDate,
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ];

                $subscriptionResponse = authenticatedRequest(
                    '/rest/v1/subscriptions',
                    'POST',
                    $subscriptionData
                );

                if ($subscriptionResponse['statusCode'] === 201 && isset($subscriptionResponse['data'][0]['id'])) {
                    // Create initial subscription payment record
                    $subscriptionId = $subscriptionResponse['data'][0]['id'];

                    $paymentData = [
                        'subscription_id' => $subscriptionId,
                        'amount' => (float) $item['product']['subscription_price'],
                        'status' => 'completed',
                        'payment_date' => date('c'),
                        'next_payment_date' => $nextBillingDate
                    ];

                    $paymentResponse = authenticatedRequest(
                        '/rest/v1/subscription_payments',
                        'POST',
                        $paymentData
                    );

                    if ($paymentResponse['statusCode'] !== 201) {
                        $subscriptionsCreated = false;
                        break;
                    }
                } else {
                    $subscriptionsCreated = false;
                    break;
                }
            }
        }

        if ($allItemsAdded && $subscriptionsCreated) {
            // Clear the cart
            $clearCartResponse = authenticatedRequest(
                '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']),
                'DELETE'
            );

            if ($clearCartResponse['statusCode'] === 204) {
                // Redirect to success page
                $redirectUrl = 'purchase_success.php?id=' . $purchaseId;
                if ($hasSubscriptions) {
                    $redirectUrl .= '&subscriptions=true';
                }
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $error = 'Failed to clear cart after purchase';
            }
        } else if (!$allItemsAdded) {
            $error = 'Failed to add all items to purchase';
        } else {
            $error = 'Failed to create subscription records';
        }
    } else {
        $error = 'Failed to create purchase record';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BlueHaven</title>
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

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        <?php echo getCartIconCss(); ?>

        /* Checkout Styles */
        .checkout-section {
            padding: 3rem 0;
            flex: 1;
        }

        .checkout-header {
            margin-bottom: 2rem;
        }

        .checkout-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .checkout-header p {
            color: var(--text-secondary);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .checkout-form {
            background-color: var(--secondary-bg);
            padding: 2rem;
            border-radius: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .order-summary {
            background-color: var(--secondary-bg);
            padding: 2rem;
            border-radius: 0.5rem;
            align-self: start;
        }

        .order-summary h3 {
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
        }

        .order-items {
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .item-price {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .item-quantity {
            background-color: var(--primary-bg);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            margin-left: 1rem;
            font-size: 0.9rem;
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

            .checkout-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            nav ul, .auth-buttons {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .form-row {
                grid-template-columns: 1fr;
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

    <!-- Checkout Section -->
    <section class="checkout-section">
        <div class="container">
            <div class="checkout-header">
                <h1>Checkout</h1>
                <p>Complete your purchase</p>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <div class="checkout-container">
                <div class="checkout-form">
                    <form action="checkout.php" method="post">
                        <h3>Billing Information</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="firstName" class="form-control" value="<?php echo htmlspecialchars($user['user_metadata']['first_name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="lastName" class="form-control" value="<?php echo htmlspecialchars($user['user_metadata']['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly>
                        </div>

                        <h3 style="margin-top: 2rem;">Payment Information</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">This is a demo checkout. No actual payment will be processed.</p>

                        <div class="form-group">
                            <label for="cardNumber">Card Number</label>
                            <input type="text" id="cardNumber" name="cardNumber" class="form-control" value="4242 4242 4242 4242" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry">Expiry Date</label>
                                <input type="text" id="expiry" name="expiry" class="form-control" value="12/25" required>
                            </div>

                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" class="form-control" value="123" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary checkout-btn">Complete Purchase</button>
                    </form>
                </div>

                <div class="order-summary">
                    <h3>Order Summary</h3>

                    <div class="order-items">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="order-item">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['product']['name']); ?></div>
                                <div class="item-price">$<?php echo number_format($item['product']['price'], 2); ?></div>
                            </div>
                            <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($totalAmount, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>

                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($totalAmount, 2); ?></span>
                    </div>
                </div>
            </div>
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
