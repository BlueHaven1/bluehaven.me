<?php
require_once 'auth/config.php';

// Check if user is logged in
$user = getCurrentUser();
if (!$user) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Check if purchase ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$purchaseId = $_GET['id'];

// Get purchase details
$purchaseResponse = authenticatedRequest(
    '/rest/v1/purchases?id=eq.' . urlencode($purchaseId) . '&select=*',
    'GET'
);

$purchase = null;
if ($purchaseResponse['statusCode'] === 200 && !empty($purchaseResponse['data'])) {
    $purchase = $purchaseResponse['data'][0];

    // Verify that the purchase belongs to the current user
    if ($purchase['user_id'] !== $user['id']) {
        header('Location: index.php?error=Unauthorized');
        exit;
    }
} else {
    header('Location: index.php?error=Purchase not found');
    exit;
}

// Get purchase items
$purchaseItemsResponse = authenticatedRequest(
    '/rest/v1/purchase_items?purchase_id=eq.' . urlencode($purchaseId) . '&select=*,product:products(*)',
    'GET'
);

$purchaseItems = [];
if ($purchaseItemsResponse['statusCode'] === 200) {
    $purchaseItems = $purchaseItemsResponse['data'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Successful - BlueHaven</title>
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
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            border: none;
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

        /* Success Page Styles */
        .success-section {
            padding: 4rem 0;
            flex: 1;
        }

        .success-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .success-header {
            background-color: var(--success-color);
            padding: 2rem;
            text-align: center;
        }

        .success-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: white;
        }

        .success-header p {
            color: rgba(255, 255, 255, 0.8);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            color: var(--success-color);
        }

        .success-content {
            padding: 2rem;
        }

        .order-details {
            margin-bottom: 2rem;
        }

        .order-details h3 {
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
        }

        .detail-value {
            color: var(--text-secondary);
        }

        .purchased-items {
            margin-bottom: 2rem;
        }

        .purchased-items h3 {
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-details {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .item-image {
            width: 60px;
            height: 60px;
            background-color: var(--primary-bg);
            border-radius: 0.25rem;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-name {
            font-weight: 500;
        }

        .item-price {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .item-quantity {
            background-color: var(--primary-bg);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.9rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border-color);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .success-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
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
        }

        @media (max-width: 768px) {
            nav ul, .auth-buttons {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .success-actions {
                flex-direction: column;
            }

            .success-actions .btn {
                width: 100%;
                text-align: center;
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

    <!-- Success Section -->
    <section class="success-section">
        <div class="container">
            <div class="success-container">
                <div class="success-header">
                    <div class="success-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <h1>Purchase Successful!</h1>
                    <p>Thank you for your purchase. Your order has been processed successfully.</p>
                </div>

                <div class="success-content">
                    <div class="order-details">
                        <h3>Order Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Order ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($purchaseId); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($purchase['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value" style="color: var(--success-color); font-weight: 500;"><?php echo ucfirst(htmlspecialchars($purchase['status'])); ?></span>
                        </div>
                    </div>

                    <div class="purchased-items">
                        <h3>Purchased Items</h3>
                        <?php foreach ($purchaseItems as $item): ?>
                        <div class="item-row">
                            <div class="item-details">
                                <div class="item-image">
                                    <?php if (!empty($item['product']['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['product']['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product']['name']); ?>">
                                    <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-size: 0.8rem;">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="item-name"><?php echo htmlspecialchars($item['product']['name']); ?></div>
                                    <div class="item-price">$<?php echo number_format($item['price_at_purchase'], 2); ?></div>
                                </div>
                            </div>
                            <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                        </div>
                        <?php endforeach; ?>

                        <div class="total-row">
                            <span>Total</span>
                            <span>$<?php echo number_format($purchase['total_amount'], 2); ?></span>
                        </div>
                    </div>

                    <div class="success-actions">
                        <?php if (isset($_GET['subscriptions']) && $_GET['subscriptions'] === 'true'): ?>
                        <a href="subscriptions.php" class="btn btn-primary">Manage Subscriptions</a>
                        <?php endif; ?>
                        <a href="products.php" class="btn btn-outline">Continue Shopping</a>
                        <a href="#" class="btn btn-primary">Download Receipt</a>
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
