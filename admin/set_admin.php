<?php
require_once '../auth/config.php';

// Check if user is logged in and has admin role
if (!isAuthenticated() || !isAdmin()) {
    header('Location: ../index.php?error=You do not have permission to access this page');
    exit;
}

// Get current user data
$user = getCurrentUser();

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php?error=User ID is required');
    exit;
}

$userId = $_GET['id'];
$role = $_GET['role'] ?? 'admin';

// Validate role
if (!in_array($role, ['user', 'admin', 'superadmin'])) {
    header('Location: index.php?error=Invalid role');
    exit;
}

// Check if current user is superadmin or trying to set superadmin role
$currentUserRole = $user['user_metadata']['role'] ?? 'user';
if ($currentUserRole !== 'superadmin' && $role === 'superadmin') {
    header('Location: index.php?error=Only superadmins can set superadmin role');
    exit;
}

// Update user role in Supabase
$profileData = [
    'role' => $role,
    'updated_at' => date('c')
];

$updateResponse = authenticatedRequest(
    '/rest/v1/profiles?id=eq.' . $userId,
    'PATCH',
    $profileData
);

// Check if update was successful
if ($updateResponse['statusCode'] === 204) {
    // Also update user metadata in auth.users
    $userData = [
        'data' => [
            'role' => $role
        ]
    ];
    
    // Note: This would require admin privileges in Supabase
    // For now, we'll just update the profiles table
    
    header('Location: index.php?success=User role updated to ' . ucfirst($role));
    exit;
} else {
    header('Location: index.php?error=Failed to update user role');
    exit;
}
?>
