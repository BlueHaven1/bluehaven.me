<?php
require_once '../auth/config.php';

// Check if user is logged in and has admin role
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ../index.php?error=You do not have permission to access this page');
    exit;
}

// Get current user data
$user = getCurrentUser();

// Get all users from Supabase
$usersResponse = authenticatedRequest(
    '/rest/v1/profiles?select=*',
    'GET'
);

$users = [];
if ($usersResponse['statusCode'] === 200) {
    $users = $usersResponse['data'];
}

// Get all products from Supabase
$productsResponse = authenticatedRequest(
    '/rest/v1/products?select=*',
    'GET'
);

$products = [];
if ($productsResponse['statusCode'] === 200) {
    $products = $productsResponse['data'];
}

// Get all purchases from Supabase
$purchasesResponse = authenticatedRequest(
    '/rest/v1/purchases?select=*',
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
    <title>Admin Dashboard - BlueHaven</title>
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

        /* Admin Dashboard Styles */
        .admin-container {
            flex: 1;
            padding: 2rem 1rem;
        }

        .admin-header {
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .admin-header p {
            color: var(--text-secondary);
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-change {
            font-size: 0.9rem;
            color: var(--success-color);
        }

        .admin-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .admin-tab {
            padding: 1rem 1.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-secondary);
        }

        .admin-tab.active {
            color: var(--text-primary);
            border-bottom: 2px solid var(--accent-color);
        }

        .admin-tab:hover {
            color: var(--text-primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
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

        .search-bar {
            display: flex;
            margin-bottom: 1.5rem;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem 0 0 0.375rem;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .search-btn {
            padding: 0.75rem 1.25rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 0 0.375rem 0.375rem 0;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-btn:hover {
            background-color: #2563eb;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            nav ul {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .admin-stats {
                grid-template-columns: 1fr;
            }

            .admin-tabs {
                overflow-x: auto;
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

    <!-- Admin Dashboard -->
    <section class="admin-container">
        <div class="container">
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <p>Manage users, products, and purchases</p>
            </div>

            <!-- Stats Cards -->
            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-value"><?php echo count($users); ?></div>
                </div>

                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="stat-value"><?php echo count($products); ?></div>
                </div>

                <div class="stat-card">
                    <h3>Total Purchases</h3>
                    <div class="stat-value"><?php echo count($purchases); ?></div>
                </div>

                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="stat-value">$<?php
                        $revenue = 0;
                        foreach ($purchases as $purchase) {
                            $revenue += $purchase['amount'];
                        }
                        echo number_format($revenue, 2);
                    ?></div>
                </div>
            </div>

            <!-- Admin Tabs -->
            <div class="admin-tabs">
                <div class="admin-tab active" data-tab="users">Users</div>
                <div class="admin-tab" data-tab="products">Products</div>
                <div class="admin-tab" data-tab="purchases">Purchases</div>
            </div>

            <!-- Users Tab -->
            <div class="tab-content active" id="users-tab">
                <div class="search-bar">
                    <input type="text" class="search-input" id="user-search" placeholder="Search users...">
                    <button class="search-btn">Search</button>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $profile): ?>
                        <tr>
                            <td><?php echo substr(htmlspecialchars($profile['id']), 0, 8) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($profile['full_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                // Get user email from auth.users (not available in profiles)
                                echo 'user@example.com'; // Placeholder - would need to fetch from auth.users
                                ?>
                            </td>
                            <td>
                                <span class="badge <?php
                                    $role = $profile['role'] ?? 'user';
                                    if ($role === 'admin') echo 'badge-warning';
                                    else if ($role === 'superadmin') echo 'badge-danger';
                                    else echo 'badge-primary';
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($role)); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($profile['created_at'])); ?></td>
                            <td class="actions">
                                <a href="edit_user.php?id=<?php echo htmlspecialchars($profile['id']); ?>" class="btn btn-outline btn-sm">Edit</a>
                                <?php if (($profile['role'] ?? 'user') !== 'admin' && ($profile['role'] ?? 'user') !== 'superadmin'): ?>
                                <a href="set_admin.php?id=<?php echo htmlspecialchars($profile['id']); ?>&role=admin" class="btn btn-primary btn-sm">Make Admin</a>
                                <?php elseif (($profile['role'] ?? '') === 'admin' && hasRole('superadmin')): ?>
                                <a href="set_admin.php?id=<?php echo htmlspecialchars($profile['id']); ?>&role=user" class="btn btn-warning btn-sm">Remove Admin</a>
                                <?php endif; ?>
                                <?php if (hasRole('superadmin') && ($profile['role'] ?? '') !== 'superadmin'): ?>
                                <a href="set_admin.php?id=<?php echo htmlspecialchars($profile['id']); ?>&role=superadmin" class="btn btn-danger btn-sm">Make Superadmin</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Products Tab -->
            <div class="tab-content" id="products-tab">
                <div class="search-bar">
                    <input type="text" class="search-input" id="product-search" placeholder="Search products...">
                    <button class="search-btn">Search</button>
                </div>

                <div style="margin-bottom: 1rem;">
                    <a href="add_product.php" class="btn btn-primary">Add New Product</a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($product['category'] ?? 'N/A')); ?></td>
                            <td>
                                <span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="view_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-primary btn-sm">View</a>
                                <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-outline btn-sm">Edit</a>
                                <a href="delete_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Purchases Tab -->
            <div class="tab-content" id="purchases-tab">
                <div class="search-bar">
                    <input type="text" class="search-input" id="purchase-search" placeholder="Search purchases...">
                    <button class="search-btn">Search</button>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?php echo substr(htmlspecialchars($purchase['id']), 0, 8) . '...'; ?></td>
                            <td><?php echo substr(htmlspecialchars($purchase['user_id']), 0, 8) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($purchase['product_name']); ?></td>
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
                            <td class="actions">
                                <a href="view_purchase.php?id=<?php echo htmlspecialchars($purchase['id']); ?>" class="btn btn-outline btn-sm">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.admin-tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));

                    // Add active class to clicked tab
                    this.classList.add('active');

                    // Hide all tab contents
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Show the corresponding tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });

            // Search functionality
            const userSearch = document.getElementById('user-search');
            const productSearch = document.getElementById('product-search');
            const purchaseSearch = document.getElementById('purchase-search');

            if (userSearch) {
                userSearch.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const userRows = document.querySelectorAll('#users-tab tbody tr');

                    userRows.forEach(row => {
                        const name = row.cells[1].textContent.toLowerCase();
                        const email = row.cells[2].textContent.toLowerCase();

                        if (name.includes(searchTerm) || email.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            if (productSearch) {
                productSearch.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const productRows = document.querySelectorAll('#products-tab tbody tr');

                    productRows.forEach(row => {
                        const id = row.cells[0].textContent.toLowerCase();
                        const name = row.cells[1].textContent.toLowerCase();
                        const category = row.cells[3].textContent.toLowerCase();

                        if (id.includes(searchTerm) || name.includes(searchTerm) || category.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            if (purchaseSearch) {
                purchaseSearch.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const purchaseRows = document.querySelectorAll('#purchases-tab tbody tr');

                    purchaseRows.forEach(row => {
                        const id = row.cells[0].textContent.toLowerCase();
                        const user = row.cells[1].textContent.toLowerCase();
                        const product = row.cells[2].textContent.toLowerCase();

                        if (id.includes(searchTerm) || user.includes(searchTerm) || product.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
