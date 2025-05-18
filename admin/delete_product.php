<?php
require_once '../auth/config.php';

// Check if user is logged in and has admin role
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ../index.php?error=You do not have permission to access this page');
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php?error=Product ID is required');
    exit;
}

$productId = $_GET['id'];

// Confirm deletion if not confirmed
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
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

    // Show confirmation page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Delete Product - Admin Dashboard - BlueHaven</title>
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
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }

            .confirmation-card {
                background-color: var(--secondary-bg);
                border-radius: 0.5rem;
                padding: 2rem;
                max-width: 500px;
                width: 100%;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                text-align: center;
            }

            h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
                color: var(--error-color);
            }

            p {
                margin-bottom: 1.5rem;
                color: var(--text-secondary);
                line-height: 1.6;
            }

            .product-info {
                background-color: rgba(0, 0, 0, 0.2);
                padding: 1rem;
                border-radius: 0.375rem;
                margin-bottom: 1.5rem;
                text-align: left;
            }

            .product-info p {
                margin-bottom: 0.5rem;
            }

            .product-info strong {
                color: var(--text-primary);
            }

            .actions {
                display: flex;
                justify-content: center;
                gap: 1rem;
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

            .btn-danger {
                background-color: var(--error-color);
                color: white;
                border: none;
            }

            .btn-danger:hover {
                background-color: #dc2626;
            }
        </style>
    </head>
    <body>
        <div class="confirmation-card">
            <h1>Delete Product</h1>
            <p>Are you sure you want to delete this product? This action cannot be undone.</p>

            <div class="product-info">
                <p><strong>ID:</strong> <?php echo htmlspecialchars($product['id']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars(ucfirst($product['category'] ?? 'N/A')); ?></p>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-primary">Cancel</a>
                <a href="delete_product.php?id=<?php echo htmlspecialchars($productId); ?>&confirm=yes" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Log the delete operation
error_log('Deleting product: ' . $productId);

// Delete product from Supabase
$deleteResponse = authenticatedRequest(
    '/rest/v1/products?id=eq.' . urlencode($productId),
    'DELETE'
);

// Log the response
error_log('Delete response: ' . print_r($deleteResponse, true));

if ($deleteResponse['statusCode'] === 204) {
    header('Location: index.php?success=Product deleted successfully');
    exit;
} else {
    $errorMessage = 'Failed to delete product';
    if (isset($deleteResponse['data']['message'])) {
        $errorMessage .= ': ' . $deleteResponse['data']['message'];
    }
    header('Location: index.php?error=' . urlencode($errorMessage));
    exit;
}
?>
