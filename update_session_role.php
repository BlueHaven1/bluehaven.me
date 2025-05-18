<?php
require_once 'auth/config.php';

// Check if user is logged in
if (!isAuthenticated()) {
    echo "<h1>Not logged in</h1>";
    echo "<p>Please <a href='login.php'>log in</a> first.</p>";
    exit;
}

// Get current user data
$user = getCurrentUser();

// Get profile data from Supabase
$profileResponse = authenticatedRequest(
    '/rest/v1/profiles?id=eq.' . $user['id'] . '&select=*',
    'GET'
);

$profile = null;
if ($profileResponse['statusCode'] === 200 && !empty($profileResponse['data'])) {
    $profile = $profileResponse['data'][0];
}

// Update session with role from database
if ($profile && isset($profile['role'])) {
    if (!isset($_SESSION['user']['user_metadata'])) {
        $_SESSION['user']['user_metadata'] = [];
    }
    
    $oldRole = $_SESSION['user']['user_metadata']['role'] ?? 'Not set';
    $_SESSION['user']['user_metadata']['role'] = $profile['role'];
    
    $message = "Session role updated from '$oldRole' to '{$profile['role']}'";
    $success = true;
} else {
    $message = "Failed to update session role. Profile not found or role not set.";
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Session Role - BlueHaven</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #3b82f6;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .success {
            color: #10b981;
            background-color: rgba(16, 185, 129, 0.1);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .error {
            color: #ef4444;
            background-color: rgba(239, 68, 68, 0.1);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <h1>Update Session Role</h1>
    
    <div class="card">
        <?php if ($success): ?>
        <div class="success">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php else: ?>
        <div class="error">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php endif; ?>
        
        <p>Current session role: <strong><?php echo htmlspecialchars($_SESSION['user']['user_metadata']['role'] ?? 'Not set'); ?></strong></p>
        <p>Database role: <strong><?php echo htmlspecialchars($profile['role'] ?? 'Not set'); ?></strong></p>
    </div>
    
    <p>
        <a href="check_role.php" class="btn">Check Role</a>
        <a href="index.php" class="btn">Back to Home</a>
        <?php if (isAdmin()): ?>
        <a href="admin/index.php" class="btn">Go to Admin Panel</a>
        <?php endif; ?>
    </p>
</body>
</html>
