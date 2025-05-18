<?php
require_once '../auth/config.php';

// Check if user is logged in and has admin role
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ../index.php?error=You do not have permission to access this page');
    exit;
}

// Get current user data
$user = getCurrentUser();

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php?error=Product ID is required');
    exit;
}

$productId = $_GET['id'];

// Get product data from Supabase
$productResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.' . urlencode($productId) . '&select=*',
    'GET'
);

$product = null;
if ($productResponse['statusCode'] === 200 && !empty($productResponse['data'])) {
    $product = $productResponse['data'][0];
} else {
    header('Location: index.php?error=Product not found');
    exit;
}

// Get purchases for this product
$purchasesResponse = authenticatedRequest(
    '/rest/v1/purchases?product_id=eq.' . urlencode($productId) . '&select=*',
    'GET'
);

$purchases = [];
if ($purchasesResponse['statusCode'] === 200) {
    $purchases = $purchasesResponse['data'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Admin Dashboard - BlueHaven</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        header {
            background-color: var(--secondary-bg);
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
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
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Product View Styles */
        .admin-container {
            flex: 1;
            padding: 2rem 1rem;
        }
        
        .admin-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .admin-header p {
            color: var(--text-secondary);
        }
        
        .admin-header .actions {
            display: flex;
            gap: 1rem;
        }
        
        .product-card {
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .product-header {
            display: flex;
            margin-bottom: 2rem;
        }
        
        .product-image {
            width: 200px;
            height: 200px;
            background-color: var(--primary-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            margin-right: 2rem;
            flex-shrink: 0;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .product-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }
        
        .product-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
        }
        
        .badge-primary {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
        }
        
        .section-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table th {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .data-table tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .data-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.9rem;
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
        
        .btn-danger {
            background-color: var(--error-color);
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            nav ul {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .product-header {
                flex-direction: column;
            }
            
            .product-image {
                width: 100%;
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .admin-header .actions {
                margin-top: 1rem;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo">Blue<span>Haven</span></a>
                
                <nav>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="#">Store</a></li>
                        <li><a href="#">Team</a></li>
                        <li><a href="#">Reviews</a></li>
                        <li><a href="#">Documentation</a></li>
                    </ul>
                </nav>
                
                <div class="auth-buttons">
                    <span class="user-greeting">Hello, <?php echo htmlspecialchars($user['user_metadata']['first_name'] ?? $user['email']); ?></span>
                    <a href="../profile.php" class="btn btn-outline">My Profile</a>
                    <a href="../auth/logout_handler.php" class="btn btn-outline">Logout</a>
                </div>
                
                <button class="mobile-menu-btn">☰</button>
            </div>
        </div>
    </header>
    
    <!-- Product View -->
    <section class="admin-container">
        <div class="container">
            <div class="admin-header">
                <div>
                    <h1>Product Details</h1>
                    <p>View and manage product information</p>
                </div>
                
                <div class="actions">
                    <a href="index.php" class="btn btn-outline">Back to Dashboard</a>
                    <a href="edit_product.php?id=<?php echo htmlspecialchars($productId); ?>" class="btn btn-primary">Edit Product</a>
                    <a href="delete_product.php?id=<?php echo htmlspecialchars($productId); ?>" class="btn btn-danger">Delete Product</a>
                </div>
            </div>
            
            <div class="product-card">
                <div class="product-header">
                    <div class="product-image">
                        <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">No Image</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                        
                        <div class="product-meta">
                            <div class="product-meta-item">
                                <span>ID:</span>
                                <strong><?php echo htmlspecialchars($product['id']); ?></strong>
                            </div>
                            
                            <div class="product-meta-item">
                                <span>Category:</span>
                                <strong><?php echo htmlspecialchars(ucfirst($product['category'] ?? 'N/A')); ?></strong>
                            </div>
                            
                            <div class="product-meta-item">
                                <span>Status:</span>
                                <span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            
                            <div class="product-meta-item">
                                <span>Featured:</span>
                                <span class="badge <?php echo $product['is_featured'] ? 'badge-warning' : 'badge-primary'; ?>">
                                    <?php echo $product['is_featured'] ? 'Featured' : 'Not Featured'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="product-price">
                            $<?php echo number_format($product['price'], 2); ?>
                        </div>
                        
                        <div class="product-description">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                        
                        <div class="product-meta">
                            <div class="product-meta-item">
                                <span>Created:</span>
                                <strong><?php echo date('M d, Y', strtotime($product['created_at'])); ?></strong>
                            </div>
                            
                            <div class="product-meta-item">
                                <span>Last Updated:</span>
                                <strong><?php echo date('M d, Y', strtotime($product['updated_at'])); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h2 class="section-title">Purchase History</h2>
            
            <?php if (empty($purchases)): ?>
            <div class="empty-state">
                <p>No purchases found for this product.</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?php echo substr(htmlspecialchars($purchase['id']), 0, 8) . '...'; ?></td>
                        <td><?php echo substr(htmlspecialchars($purchase['user_id']), 0, 8) . '...'; ?></td>
                        <td>$<?php echo number_format($purchase['amount'], 2); ?></td>
                        <td><?php echo date('M d, Y', strtotime($purchase['created_at'])); ?></td>
                        <td>
                            <span class="badge <?php 
                                if ($purchase['status'] === 'completed') echo 'badge-success';
                                else if ($purchase['status'] === 'pending') echo 'badge-warning';
                                else echo 'badge-danger';
                            ?>">
                                <?php echo htmlspecialchars(ucfirst($purchase['status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
