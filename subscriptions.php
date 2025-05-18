<?php
require_once 'auth/config.php';
require_once 'includes/header.php';

// Check if user is logged in
$user = getCurrentUser();
if (!$user) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=' . urlencode('subscriptions.php'));
    exit;
}

// Handle subscription actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $subscriptionId = $_GET['id'];
    
    // Verify subscription belongs to user
    $verifyResponse = authenticatedRequest(
        '/rest/v1/subscriptions?id=eq.' . urlencode($subscriptionId) . '&user_id=eq.' . urlencode($user['id']) . '&select=id',
        'GET'
    );
    
    if ($verifyResponse['statusCode'] !== 200 || empty($verifyResponse['data'])) {
        $error = 'Subscription not found or does not belong to you';
    } else {
        if ($action === 'cancel') {
            // Cancel subscription
            $updateData = [
                'status' => 'cancelled',
                'end_date' => date('c', strtotime('+1 month')), // End at next billing cycle
                'updated_at' => date('c')
            ];
            
            $updateResponse = authenticatedRequest(
                '/rest/v1/subscriptions?id=eq.' . urlencode($subscriptionId),
                'PATCH',
                $updateData
            );
            
            if ($updateResponse['statusCode'] === 204) {
                $success = 'Subscription cancelled successfully. You will have access until the end of your current billing period.';
            } else {
                $error = 'Failed to cancel subscription';
            }
        } elseif ($action === 'reactivate') {
            // Reactivate subscription
            $updateData = [
                'status' => 'active',
                'end_date' => null,
                'updated_at' => date('c')
            ];
            
            $updateResponse = authenticatedRequest(
                '/rest/v1/subscriptions?id=eq.' . urlencode($subscriptionId),
                'PATCH',
                $updateData
            );
            
            if ($updateResponse['statusCode'] === 204) {
                $success = 'Subscription reactivated successfully';
            } else {
                $error = 'Failed to reactivate subscription';
            }
        }
    }
}

// Get user's subscriptions
$subscriptionsResponse = authenticatedRequest(
    '/rest/v1/subscriptions?user_id=eq.' . urlencode($user['id']) . '&select=*,product:products(*)',
    'GET'
);

$subscriptions = [];
if ($subscriptionsResponse['statusCode'] === 200) {
    $subscriptions = $subscriptionsResponse['data'];
}

// Get subscription payment history
$paymentsResponse = authenticatedRequest(
    '/rest/v1/subscription_payments?subscription_id=in.(' . 
    implode(',', array_map(function($sub) { return '"' . $sub['id'] . '"'; }, $subscriptions)) . 
    ')&select=*,subscription:subscriptions(id,product:products(name))&order=payment_date.desc',
    'GET'
);

$payments = [];
if ($paymentsResponse['statusCode'] === 200) {
    $payments = $paymentsResponse['data'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscriptions - BlueHaven</title>
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
            --warning-color: #f59e0b;
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
        
        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Subscription Styles */
        .subscriptions-section {
            padding: 3rem 0;
            flex: 1;
        }
        
        .subscriptions-header {
            margin-bottom: 2rem;
        }
        
        .subscriptions-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .subscriptions-header p {
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
        
        .subscriptions-empty {
            text-align: center;
            padding: 3rem;
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
        }
        
        .subscriptions-empty h2 {
            margin-bottom: 1rem;
        }
        
        .subscriptions-empty p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .subscription-card {
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .subscription-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .subscription-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .subscription-title h3 {
            font-size: 1.25rem;
        }
        
        .subscription-status {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .status-cancelled {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
        }
        
        .status-expired {
            background-color: rgba(107, 114, 128, 0.1);
            color: var(--text-secondary);
        }
        
        .subscription-content {
            padding: 1.5rem;
        }
        
        .subscription-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: 500;
        }
        
        .subscription-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        /* Payment History */
        .payment-history {
            margin-top: 3rem;
        }
        
        .payment-history h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .payment-table th {
            text-align: left;
            padding: 1rem;
            background-color: var(--secondary-bg);
            border-bottom: 1px solid var(--border-color);
        }
        
        .payment-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .payment-status {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .payment-completed {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .payment-failed {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
        }
        
        .payment-refunded {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
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
            
            .payment-table {
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
            
            .subscription-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .subscription-actions {
                flex-direction: column;
            }
            
            .subscription-actions .btn {
                width: 100%;
                text-align: center;
            }
        }
        
        <?php echo getCartIconCss(); ?>
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
                        <li><a href="#">Store</a></li>
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
    
    <!-- Subscriptions Section -->
    <section class="subscriptions-section">
        <div class="container">
            <div class="subscriptions-header">
                <h1>My Subscriptions</h1>
                <p>Manage your active subscriptions and view payment history</p>
            </div>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if (empty($subscriptions)): ?>
            <div class="subscriptions-empty">
                <h2>No Subscriptions Found</h2>
                <p>You don't have any active subscriptions at the moment.</p>
                <a href="index.php" class="btn btn-primary">Browse Products</a>
            </div>
            <?php else: ?>
                <?php foreach ($subscriptions as $subscription): ?>
                <div class="subscription-card">
                    <div class="subscription-header">
                        <div class="subscription-title">
                            <h3><?php echo htmlspecialchars($subscription['product']['name']); ?></h3>
                            <span class="subscription-status status-<?php echo $subscription['status']; ?>">
                                <?php echo ucfirst($subscription['status']); ?>
                            </span>
                        </div>
                        
                        <div>
                            <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                Started on <?php echo date('F j, Y', strtotime($subscription['start_date'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="subscription-content">
                        <div class="subscription-details">
                            <div class="detail-item">
                                <span class="detail-label">Plan</span>
                                <span class="detail-value">
                                    $<?php echo number_format($subscription['product']['subscription_price'], 2); ?>/<?php echo $subscription['product']['subscription_interval']; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Billing Cycle</span>
                                <span class="detail-value">
                                    <?php echo $subscription['product']['subscription_interval'] === 'month' ? 'Monthly' : 'Yearly'; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Next Billing Date</span>
                                <span class="detail-value">
                                    <?php if ($subscription['status'] === 'active'): ?>
                                    <?php echo date('F j, Y', strtotime($subscription['next_billing_date'] ?? '+1 month')); ?>
                                    <?php elseif ($subscription['status'] === 'cancelled' && $subscription['end_date']): ?>
                                    Access until <?php echo date('F j, Y', strtotime($subscription['end_date'])); ?>
                                    <?php else: ?>
                                    N/A
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Auto-Renew</span>
                                <span class="detail-value">
                                    <?php echo $subscription['status'] === 'active' ? 'Yes' : 'No'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="subscription-actions">
                            <?php if ($subscription['status'] === 'active'): ?>
                            <a href="subscriptions.php?action=cancel&id=<?php echo htmlspecialchars($subscription['id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this subscription? You will still have access until the end of your current billing period.')">Cancel Subscription</a>
                            <?php elseif ($subscription['status'] === 'cancelled' && (!$subscription['end_date'] || strtotime($subscription['end_date']) > time())): ?>
                            <a href="subscriptions.php?action=reactivate&id=<?php echo htmlspecialchars($subscription['id']); ?>" class="btn btn-success">Reactivate Subscription</a>
                            <?php endif; ?>
                            <a href="product.php?id=<?php echo htmlspecialchars($subscription['product']['id']); ?>" class="btn btn-outline">View Product</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($payments)): ?>
            <div class="payment-history">
                <h2>Payment History</h2>
                
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subscription</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Next Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['subscription']['product']['name']); ?></td>
                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                            <td>
                                <span class="payment-status payment-<?php echo $payment['status']; ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($payment['next_payment_date']): ?>
                                <?php echo date('F j, Y', strtotime($payment['next_payment_date'])); ?>
                                <?php else: ?>
                                N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                        <li><a href="#">Scripts</a></li>
                        <li><a href="#">Vehicle Liveries</a></li>
                        <li><a href="#">EUP Packages</a></li>
                        <li><a href="#">Web Solutions</a></li>
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
