<?php
require_once 'auth/config.php';
require_once 'includes/header.php';

$user = getCurrentUser();

// Default values
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12; // Products per page
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$subscriptionFilter = isset($_GET['subscription']) ? $_GET['subscription'] : '';

// Build query
$query = '/rest/v1/products?is_active=eq.true';

// Add search filter
if (!empty($search)) {
    // Search in name and description
    $query .= '&or=(name.ilike.' . urlencode('*' . $search . '*') . ',description.ilike.' . urlencode('*' . $search . '*') . ')';
}

// Add category filter
if (!empty($category)) {
    $query .= '&category=eq.' . urlencode($category);
}

// Add subscription filter
if ($subscriptionFilter === 'subscription') {
    $query .= '&is_subscription=eq.true';
} elseif ($subscriptionFilter === 'one-time') {
    $query .= '&is_subscription=eq.false';
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $query .= '&order=price.asc';
        break;
    case 'price_desc':
        $query .= '&order=price.desc';
        break;
    case 'name_desc':
        $query .= '&order=name.desc';
        break;
    case 'newest':
        $query .= '&order=created_at.desc';
        break;
    default: // name_asc
        $query .= '&order=name.asc';
        break;
}

// Count total products for pagination
$countQuery = $query . '&select=id';
$countResponse = supabaseRequest($countQuery, 'GET');
$totalProducts = 0;

if ($countResponse['statusCode'] === 200) {
    $totalProducts = count($countResponse['data']);
}

$totalPages = ceil($totalProducts / $perPage);
$page = min($page, max(1, $totalPages)); // Ensure page is within valid range

// Add pagination
$offset = ($page - 1) * $perPage;
$query .= '&select=*&limit=' . $perPage . '&offset=' . $offset;

// Fetch products
$productsResponse = supabaseRequest($query, 'GET');
$products = [];

if ($productsResponse['statusCode'] === 200) {
    $products = $productsResponse['data'];
}

// Get categories for filter
$categoriesResponse = supabaseRequest(
    '/rest/v1/products?select=category&is_active=eq.true',
    'GET'
);

$categories = [];
if ($categoriesResponse['statusCode'] === 200) {
    // Extract unique categories
    foreach ($categoriesResponse['data'] as $item) {
        if (!empty($item['category']) && !in_array($item['category'], $categories)) {
            $categories[] = $item['category'];
        }
    }
    sort($categories);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - BlueHaven</title>
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

        .btn {
            padding: 0.5rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
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

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Products Page Styles */
        .products-section {
            padding: 3rem 0;
            flex: 1;
        }

        .products-header {
            margin-bottom: 2rem;
        }

        .products-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .products-header p {
            color: var(--text-secondary);
        }

        .products-filters {
            background-color: var(--secondary-bg);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .form-control {
            padding: 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid var(--border-color);
            background-color: var(--primary-bg);
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .search-group {
            position: relative;
        }

        .search-group .form-control {
            padding-right: 3rem;
        }

        .search-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .product-image {
            height: 200px;
            width: 100%;
            background-color: #2d3748;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-weight: 500;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .placeholder-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            z-index: 5;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .product-image-badge svg {
            width: 14px;
            height: 14px;
        }

        .product-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            min-height: 220px;
        }

        .product-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .product-title h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-right: auto;
            width: 100%;
        }

        @media (min-width: 1024px) {
            .product-title h3 {
                width: auto;
            }
        }

        .product-price {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }

        .subscription-price {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .product-badge {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .subscription-badge {
            display: inline-block;
            background-color: #10b981;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .product-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 4.8em;
        }

        .product-card .btn {
            width: 100%;
            text-align: center;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 3rem;
            gap: 0.5rem;
        }

        .pagination-item {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.375rem;
            background-color: var(--secondary-bg);
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination-item:hover {
            background-color: var(--accent-color);
            color: white;
        }

        .pagination-item.active {
            background-color: var(--accent-color);
            color: white;
            font-weight: 600;
        }

        .pagination-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-item.disabled:hover {
            background-color: var(--secondary-bg);
            color: var(--text-primary);
        }

        .empty-products {
            text-align: center;
            padding: 3rem;
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
        }

        .empty-products h2 {
            margin-bottom: 1rem;
        }

        .empty-products p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
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

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
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
                        <li><a href="products.php" class="active">Store</a></li>
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
                    <?php echo getCartIconHtml($user); ?>
                    <span class="user-greeting">Hello, <?php echo htmlspecialchars($user['user_metadata']['first_name'] ?? $user['email']); ?></span>
                    <a href="profile.php" class="btn btn-outline">My Profile</a>
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

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="products-header">
                <h1>All Products</h1>
                <p>Browse our collection of high-quality FiveM resources</p>
            </div>

            <!-- Filters -->
            <div class="products-filters">
                <form action="products.php" method="GET" class="filter-form">
                    <div class="form-group search-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-control" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subscription">Product Type</label>
                        <select id="subscription" name="subscription" class="form-control" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="one-time" <?php echo $subscriptionFilter === 'one-time' ? 'selected' : ''; ?>>One-time Purchase</option>
                            <option value="subscription" <?php echo $subscriptionFilter === 'subscription' ? 'selected' : ''; ?>>Subscription</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort" class="form-control" onchange="this.form.submit()">
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
            <div class="empty-products">
                <h2>No Products Found</h2>
                <p>We couldn't find any products matching your criteria. Try adjusting your filters or search terms.</p>
                <a href="products.php" class="btn btn-primary">Clear Filters</a>
            </div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                        <div class="placeholder-image">Product Image</div>
                        <?php endif; ?>

                        <?php if ($product['is_subscription']): ?>
                        <div class="product-image-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                            </svg>
                            <span><?php echo $product['subscription_interval'] === 'month' ? 'Monthly' : 'Yearly'; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-title">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <?php if ($product['is_subscription']): ?>
                            <span class="product-price subscription-price">
                                $<?php echo number_format($product['subscription_price'], 2); ?>/<?php echo $product['subscription_interval']; ?>
                            </span>
                            <?php else: ?>
                            <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['is_subscription']): ?>
                        <div class="product-badge">
                            <span class="subscription-badge">Subscription</span>
                        </div>
                        <?php endif; ?>
                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                        <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-primary">View Item</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="products.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&subscription=<?php echo urlencode($subscriptionFilter); ?>" class="pagination-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
                <?php else: ?>
                <span class="pagination-item disabled">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </span>
                <?php endif; ?>

                <?php
                // Calculate range of page numbers to display
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                // Always show first page
                if ($startPage > 1) {
                    echo '<a href="products.php?page=1&search=' . urlencode($search) . '&category=' . urlencode($category) . '&sort=' . urlencode($sort) . '&subscription=' . urlencode($subscriptionFilter) . '" class="pagination-item">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="pagination-item">...</span>';
                    }
                }

                // Display page numbers
                for ($i = $startPage; $i <= $endPage; $i++) {
                    $activeClass = $i === $page ? 'active' : '';
                    echo '<a href="products.php?page=' . $i . '&search=' . urlencode($search) . '&category=' . urlencode($category) . '&sort=' . urlencode($sort) . '&subscription=' . urlencode($subscriptionFilter) . '" class="pagination-item ' . $activeClass . '">' . $i . '</a>';
                }

                // Always show last page
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span class="pagination-item">...</span>';
                    }
                    echo '<a href="products.php?page=' . $totalPages . '&search=' . urlencode($search) . '&category=' . urlencode($category) . '&sort=' . urlencode($sort) . '&subscription=' . urlencode($subscriptionFilter) . '" class="pagination-item">' . $totalPages . '</a>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                <a href="products.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&subscription=<?php echo urlencode($subscriptionFilter); ?>" class="pagination-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
                <?php else: ?>
                <span class="pagination-item disabled">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            const nav = document.querySelector('nav ul');
            const authButtons = document.querySelector('.auth-buttons');

            nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
            authButtons.style.display = authButtons.style.display === 'flex' ? 'none' : 'flex';
        });
    </script>
</body>
</html>
