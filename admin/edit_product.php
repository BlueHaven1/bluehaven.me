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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $imageUrl = filter_input(INPUT_POST, 'imageUrl', FILTER_SANITIZE_URL);
    $isFeatured = isset($_POST['isFeatured']) ? true : false;
    $isActive = isset($_POST['isActive']) ? true : false;

    // Subscription data
    $isSubscription = isset($_POST['isSubscription']) ? true : false;
    $subscriptionPrice = filter_input(INPUT_POST, 'subscriptionPrice', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $subscriptionInterval = filter_input(INPUT_POST, 'subscriptionInterval', FILTER_SANITIZE_STRING);
    $subscriptionDescription = filter_input(INPUT_POST, 'subscriptionDescription', FILTER_SANITIZE_STRING);

    // Validate input
    if (empty($name) || empty($description) || empty($price) || empty($category)) {
        $error = 'Name, description, price, and category are required';
    } else if (!is_numeric($price) || $price <= 0) {
        $error = 'Price must be a positive number';
    } else if ($isSubscription && (!is_numeric($subscriptionPrice) || $subscriptionPrice <= 0)) {
        $error = 'Subscription price must be a positive number';
    } else if ($isSubscription && empty($subscriptionInterval)) {
        $error = 'Subscription interval is required for subscription products';
    } else {
        // Update product in Supabase
        $productData = [
            'name' => $name,
            'description' => $description,
            'price' => (float) $price,
            'category' => $category,
            'image_url' => $imageUrl,
            'is_featured' => $isFeatured ? true : false,
            'is_active' => $isActive ? true : false,
            'is_subscription' => $isSubscription ? true : false,
            'updated_at' => date('c')
        ];

        // Add subscription fields if it's a subscription product
        if ($isSubscription) {
            $productData['subscription_price'] = (float) $subscriptionPrice;
            $productData['subscription_interval'] = $subscriptionInterval;
            $productData['subscription_description'] = $subscriptionDescription;
        } else {
            // Clear subscription fields if it's not a subscription product
            $productData['subscription_price'] = 0;
            $productData['subscription_interval'] = null;
            $productData['subscription_description'] = null;
        }

        // Log the data being sent
        error_log('Updating product: ' . $productId);
        error_log('Product data: ' . print_r($productData, true));

        $updateResponse = authenticatedRequest(
            '/rest/v1/products?id=eq.' . urlencode($productId),
            'PATCH',
            $productData
        );

        // Log the response
        error_log('Update response: ' . print_r($updateResponse, true));

        if ($updateResponse['statusCode'] === 204) {
            header('Location: index.php?success=Product updated successfully');
            exit;
        } else {
            $error = 'Failed to update product: ' . ($updateResponse['data']['message'] ?? 'Unknown error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Dashboard - BlueHaven</title>
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

        /* Form Styles */
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

        .form-card {
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
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
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
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

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            nav ul {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
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

    <!-- Edit Product Form -->
    <section class="admin-container">
        <div class="container">
            <div class="admin-header">
                <h1>Edit Product</h1>
                <p>Update product information</p>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <div class="form-card">
                <form action="edit_product.php?id=<?php echo htmlspecialchars($productId); ?>" method="post">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($)</label>
                            <input type="number" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="scripts" <?php echo ($product['category'] ?? '') === 'scripts' ? 'selected' : ''; ?>>Scripts</option>
                                <option value="websites" <?php echo ($product['category'] ?? '') === 'websites' ? 'selected' : ''; ?>>Websites</option>
                                <option value="vehicles" <?php echo ($product['category'] ?? '') === 'vehicles' ? 'selected' : ''; ?>>Vehicles</option>
                                <option value="eup" <?php echo ($product['category'] ?? '') === 'eup' ? 'selected' : ''; ?>>EUP</option>
                                <option value="other" <?php echo ($product['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="imageUrl">Image URL</label>
                        <input type="url" id="imageUrl" name="imageUrl" class="form-control" value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                        <small style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.25rem; display: block;">Enter a URL to an image for the product</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="isFeatured" name="isFeatured" <?php echo ($product['is_featured'] ?? false) ? 'checked' : ''; ?>>
                                <label for="isFeatured">Featured Product</label>
                            </div>
                            <small style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.25rem; display: block;">Featured products appear on the homepage</small>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="isActive" name="isActive" <?php echo ($product['is_active'] ?? true) ? 'checked' : ''; ?>>
                                <label for="isActive">Active Product</label>
                            </div>
                            <small style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.25rem; display: block;">Inactive products are not visible to users</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="isSubscription" name="isSubscription" <?php echo ($product['is_subscription'] ?? false) ? 'checked' : ''; ?> onchange="toggleSubscriptionFields()">
                            <label for="isSubscription">Subscription Product</label>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.25rem; display: block;">Enable if this product requires a recurring payment</small>
                    </div>

                    <div id="subscriptionFields" style="display: none; border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
                        <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: var(--accent-color);">Subscription Details</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="subscriptionPrice">Monthly Price ($)</label>
                                <input type="number" id="subscriptionPrice" name="subscriptionPrice" class="form-control" value="<?php echo htmlspecialchars($product['subscription_price'] ?? ''); ?>" step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="subscriptionInterval">Billing Interval</label>
                                <select id="subscriptionInterval" name="subscriptionInterval" class="form-control">
                                    <option value="month" <?php echo ($product['subscription_interval'] ?? '') === 'month' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="year" <?php echo ($product['subscription_interval'] ?? '') === 'year' ? 'selected' : ''; ?>>Yearly</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subscriptionDescription">Subscription Benefits</label>
                            <textarea id="subscriptionDescription" name="subscriptionDescription" class="form-control" placeholder="Describe what's included in the subscription..."><?php echo htmlspecialchars($product['subscription_description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script>
        function toggleSubscriptionFields() {
            const isSubscription = document.getElementById('isSubscription').checked;
            const subscriptionFields = document.getElementById('subscriptionFields');

            if (isSubscription) {
                subscriptionFields.style.display = 'block';
                document.getElementById('subscriptionPrice').required = true;
            } else {
                subscriptionFields.style.display = 'none';
                document.getElementById('subscriptionPrice').required = false;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleSubscriptionFields();
        });
    </script>
</body>
</html>
