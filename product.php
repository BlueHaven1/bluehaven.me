<?php
require_once 'auth/config.php';

// Check if user is logged in
$user = getCurrentUser();

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php?error=Product ID is required');
    exit;
}

$productId = $_GET['id'];

// Get product data from Supabase
$productResponse = supabaseRequest(
    '/rest/v1/products?id=eq.' . urlencode($productId) . '&select=*',
    'GET'
);

$product = null;
if ($productResponse['statusCode'] === 200 && !empty($productResponse['data'])) {
    $product = $productResponse['data'][0];
    
    // Check if product is active
    if (!$product['is_active']) {
        header('Location: index.php?error=Product not available');
        exit;
    }
} else {
    header('Location: index.php?error=Product not found');
    exit;
}

// Get related products from the same category
$relatedProductsResponse = supabaseRequest(
    '/rest/v1/products?category=eq.' . urlencode($product['category']) . '&id=neq.' . urlencode($productId) . '&is_active=eq.true&select=*&limit=3',
    'GET'
);

$relatedProducts = [];
if ($relatedProductsResponse['statusCode'] === 200) {
    $relatedProducts = $relatedProductsResponse['data'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - BlueHaven</title>
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
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Header Styles */
        header {
            background-color: var(--secondary-bg);
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--border-color);
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
        
        /* Product Detail Styles */
        .product-detail {
            padding: 3rem 0;
        }
        
        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }
        
        .product-image {
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            aspect-ratio: 16 / 9;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .product-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
        }
        
        .product-category {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            text-transform: capitalize;
        }
        
        .product-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
        }
        
        .product-description {
            margin-bottom: 2rem;
            color: var(--text-secondary);
        }
        
        .product-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        /* Related Products */
        .related-products {
            padding: 3rem 0;
            background-color: var(--secondary-bg);
        }
        
        .section-title {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .section-subtitle {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            background-color: var(--primary-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-card .product-image {
            height: 200px;
            background-color: var(--secondary-bg);
        }
        
        .product-card .product-info {
            padding: 1.5rem;
        }
        
        .product-card .product-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .product-card h3 {
            font-size: 1.25rem;
        }
        
        .product-card .product-price {
            font-size: 1.25rem;
            margin-bottom: 0;
        }
        
        .product-card .product-description {
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Footer */
        footer {
            background-color: var(--secondary-bg);
            padding: 3rem 0 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
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
            border-top: 1px solid var(--border-color);
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            nav ul {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .product-container {
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
                    <?php if ($user): ?>
                    <span class="user-greeting">Hello, <?php echo htmlspecialchars($user['user_metadata']['first_name'] ?? $user['email']); ?></span>
                    <a href="profile.php" class="btn btn-primary">My Profile</a>
                    <a href="auth/logout_handler.php" class="btn btn-outline">Logout</a>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-btn">☰</button>
            </div>
        </div>
    </header>
    
    <!-- Product Detail Section -->
    <section class="product-detail">
        <div class="container">
            <div class="product-container">
                <div class="product-image">
                    <?php if (!empty($product['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-size: 1.25rem;">No Image Available</div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-meta">
                        <span class="product-category"><?php echo htmlspecialchars(ucfirst($product['category'] ?? 'N/A')); ?></span>
                        <?php if ($product['is_featured']): ?>
                        <span style="color: #f59e0b;">★ Featured Product</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <div class="product-actions">
                        <a href="#" class="btn btn-primary">Purchase Now</a>
                        <a href="#" class="btn btn-outline">Add to Cart</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Related Products Section -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="related-products">
        <div class="container">
            <h2 class="section-title">Related Products</h2>
            <p class="section-subtitle">You might also be interested in these products</p>
            
            <div class="products-grid">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($relatedProduct['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($relatedProduct['image_url']); ?>" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">
                        <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">Product Image</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-title">
                            <h3><?php echo htmlspecialchars($relatedProduct['name']); ?></h3>
                            <span class="product-price">$<?php echo number_format($relatedProduct['price'], 2); ?></span>
                        </div>
                        <p class="product-description"><?php echo htmlspecialchars($relatedProduct['description']); ?></p>
                        <a href="product.php?id=<?php echo htmlspecialchars($relatedProduct['id']); ?>" class="btn btn-primary">View Item</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
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
