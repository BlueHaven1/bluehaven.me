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

// Get user profile from Supabase
$profileResponse = authenticatedRequest(
    '/rest/v1/profiles?id=eq.' . $userId . '&select=*',
    'GET'
);

$profile = null;
if ($profileResponse['statusCode'] === 200 && !empty($profileResponse['data'])) {
    $profile = $profileResponse['data'][0];
} else {
    header('Location: index.php?error=User not found');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $avatarUrl = filter_input(INPUT_POST, 'avatarUrl', FILTER_SANITIZE_URL);
    
    // Validate input
    if (empty($firstName) || empty($lastName) || empty($role)) {
        $error = 'First name, last name, and role are required';
    } else {
        // Update profile in Supabase
        $profileData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $firstName . ' ' . $lastName,
            'avatar_url' => $avatarUrl,
            'role' => $role,
            'updated_at' => date('c')
        ];
        
        $updateResponse = authenticatedRequest(
            '/rest/v1/profiles?id=eq.' . $userId,
            'PATCH',
            $profileData
        );
        
        if ($updateResponse['statusCode'] === 204) {
            // Also update user metadata in auth.users
            $userData = [
                'data' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'full_name' => $firstName . ' ' . $lastName,
                    'avatar_url' => $avatarUrl,
                    'role' => $role
                ]
            ];
            
            // Note: This would require admin privileges in Supabase
            // For now, we'll just update the profiles table
            
            header('Location: index.php?success=User updated successfully');
            exit;
        } else {
            $error = 'Failed to update user';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Dashboard - BlueHaven</title>
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
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
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
    
    <!-- Edit User Form -->
    <section class="admin-container">
        <div class="container">
            <div class="admin-header">
                <h1>Edit User</h1>
                <p>Update user information</p>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <div class="form-card">
                <form action="edit_user.php?id=<?php echo htmlspecialchars($userId); ?>" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" class="form-control" value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" class="form-control" value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="user" <?php echo ($profile['role'] ?? 'user') === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($profile['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="superadmin" <?php echo ($profile['role'] ?? '') === 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="avatarUrl">Avatar URL</label>
                        <input type="url" id="avatarUrl" name="avatarUrl" class="form-control" value="<?php echo htmlspecialchars($profile['avatar_url'] ?? ''); ?>" placeholder="https://example.com/avatar.jpg">
                        <small style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.25rem; display: block;">Enter a URL to an image for the user's profile picture</small>
                    </div>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>
</html>
