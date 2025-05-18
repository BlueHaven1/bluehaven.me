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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Role - BlueHaven</title>
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
        .label {
            font-weight: bold;
            margin-right: 10px;
        }
        pre {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .role {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .role-user {
            background-color: #e5e7eb;
            color: #374151;
        }
        .role-admin {
            background-color: #fef3c7;
            color: #92400e;
        }
        .role-superadmin {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .success {
            color: #10b981;
        }
        .error {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <h1>User Role Information</h1>
    
    <div class="card">
        <h2>User Information</h2>
        <p><span class="label">User ID:</span> <?php echo htmlspecialchars($user['id']); ?></p>
        <p><span class="label">Email:</span> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><span class="label">Name:</span> <?php echo htmlspecialchars($user['user_metadata']['full_name'] ?? 'N/A'); ?></p>
    </div>
    
    <div class="card">
        <h2>Role Information</h2>
        
        <h3>Session Role</h3>
        <p><span class="label">Role in Session:</span> 
            <?php 
            $sessionRole = $user['user_metadata']['role'] ?? 'Not set';
            $roleClass = 'role-user';
            if ($sessionRole === 'admin') $roleClass = 'role-admin';
            if ($sessionRole === 'superadmin') $roleClass = 'role-superadmin';
            ?>
            <span class="role <?php echo $roleClass; ?>"><?php echo htmlspecialchars($sessionRole); ?></span>
        </p>
        
        <h3>Database Role</h3>
        <p><span class="label">Role in Database:</span> 
            <?php 
            $dbRole = $profile['role'] ?? 'Not set';
            $roleClass = 'role-user';
            if ($dbRole === 'admin') $roleClass = 'role-admin';
            if ($dbRole === 'superadmin') $roleClass = 'role-superadmin';
            ?>
            <span class="role <?php echo $roleClass; ?>"><?php echo htmlspecialchars($dbRole); ?></span>
        </p>
        
        <h3>Role Check Results</h3>
        <p><span class="label">isAdmin() returns:</span> 
            <?php if (isAdmin()): ?>
            <span class="success">TRUE</span> - You have admin access
            <?php else: ?>
            <span class="error">FALSE</span> - You do not have admin access
            <?php endif; ?>
        </p>
        
        <p><span class="label">hasRole('admin') returns:</span> 
            <?php if (hasRole('admin')): ?>
            <span class="success">TRUE</span>
            <?php else: ?>
            <span class="error">FALSE</span>
            <?php endif; ?>
        </p>
        
        <p><span class="label">hasRole('superadmin') returns:</span> 
            <?php if (hasRole('superadmin')): ?>
            <span class="success">TRUE</span>
            <?php else: ?>
            <span class="error">FALSE</span>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="card">
        <h2>Session Data</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="card">
        <h2>Profile Data from Database</h2>
        <pre><?php print_r($profile); ?></pre>
    </div>
    
    <p><a href="index.php">Back to Home</a></p>
    
    <?php if (isAdmin()): ?>
    <p><a href="admin/index.php">Go to Admin Panel</a></p>
    <?php endif; ?>
</body>
</html>
