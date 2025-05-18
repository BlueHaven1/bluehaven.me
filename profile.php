<?php
require_once 'auth/config.php';

// Check if user is logged in
if (!isAuthenticated()) {
    header('Location: login.php?error=Please log in to access your profile');
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
    <title>My Profile - BlueHaven</title>
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

        /* Profile Styles */
        .profile-container {
            flex: 1;
            padding: 3rem 1rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .profile-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .profile-card {
            background-color: var(--secondary-bg);
            border-radius: 0.5rem;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .profile-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-tab {
            padding: 1rem 1.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-secondary);
        }

        .profile-tab.active {
            color: var(--text-primary);
            border-bottom: 2px solid var(--accent-color);
        }

        .profile-tab:hover {
            color: var(--text-primary);
        }

        .profile-content {
            padding: 2rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background-color: var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--text-secondary);
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            color: var(--text-secondary);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
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

            .profile-tabs {
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
                    <span class="user-greeting">Hello, <?php echo htmlspecialchars($user['user_metadata']['first_name'] ?? $user['email']); ?></span>
                    <a href="auth/logout_handler.php" class="btn btn-outline">Logout</a>
                </div>

                <button class="mobile-menu-btn">☰</button>
            </div>
        </div>
    </header>

    <!-- Profile Section -->
    <section class="profile-container">
        <div class="container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Manage your account information and settings</p>
            </div>

            <!-- Alert Messages (shown conditionally) -->
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-tabs">
                    <div class="profile-tab active" data-tab="profile">Profile Information</div>
                    <div class="profile-tab" data-tab="security">Security</div>
                    <div class="profile-tab" data-tab="purchases">Purchases</div>
                </div>

                <div class="profile-content">
                    <!-- Profile Tab -->
                    <div class="tab-content active" id="profile-tab">
                        <div class="profile-info">
                            <div class="profile-avatar">
                                <?php if (!empty($profile['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($profile['avatar_url']); ?>" alt="Profile Avatar">
                                <?php else: ?>
                                <?php echo strtoupper(substr($user['user_metadata']['first_name'] ?? $user['email'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <h2><?php echo htmlspecialchars($profile['full_name'] ?? $user['email']); ?></h2>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>

                        <form action="auth/update_profile_handler.php" method="post">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name</label>
                                    <input type="text" id="firstName" name="firstName" class="form-control" value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="lastName">Last Name</label>
                                    <input type="text" id="lastName" name="lastName" class="form-control" value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="avatarUrl">Avatar URL</label>
                                <input type="url" id="avatarUrl" name="avatarUrl" class="form-control" value="<?php echo htmlspecialchars($profile['avatar_url'] ?? ''); ?>" placeholder="https://example.com/avatar.jpg">
                                <small style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.25rem; display: block;">Enter a URL to an image for your profile picture</small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-content" id="security-tab">
                        <h2 style="margin-bottom: 1.5rem;">Change Password</h2>

                        <form action="auth/update_password_handler.php" method="post">
                            <div class="form-group">
                                <label for="currentPassword">Current Password</label>
                                <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>

                    <!-- Purchases Tab -->
                    <div class="tab-content" id="purchases-tab">
                        <h2 style="margin-bottom: 1.5rem;">Your Purchases</h2>

                        <?php
                        // Get purchases from Supabase
                        $purchasesResponse = authenticatedRequest(
                            '/rest/v1/purchases?user_id=eq.' . $user['id'] . '&select=*',
                            'GET'
                        );

                        $purchases = [];
                        if ($purchasesResponse['statusCode'] === 200) {
                            $purchases = $purchasesResponse['data'];
                        }

                        if (empty($purchases)):
                        ?>
                        <div style="text-align: center; padding: 2rem 0;">
                            <p style="color: var(--text-secondary); margin-bottom: 1rem;">You haven't made any purchases yet.</p>
                            <a href="#" class="btn btn-outline">Browse Store</a>
                        </div>
                        <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid var(--border-color);">Product</th>
                                        <th style="text-align: left; padding: 0.75rem; border-bottom: 1px solid var(--border-color);">Date</th>
                                        <th style="text-align: right; padding: 0.75rem; border-bottom: 1px solid var(--border-color);">Amount</th>
                                        <th style="text-align: center; padding: 0.75rem; border-bottom: 1px solid var(--border-color);">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);"><?php echo htmlspecialchars($purchase['product_name']); ?></td>
                                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);"><?php echo date('M d, Y', strtotime($purchase['created_at'])); ?></td>
                                        <td style="text-align: right; padding: 0.75rem; border-bottom: 1px solid var(--border-color);">$<?php echo number_format($purchase['amount'], 2); ?></td>
                                        <td style="text-align: center; padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                            <span style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; background-color: <?php echo $purchase['status'] === 'completed' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>; color: <?php echo $purchase['status'] === 'completed' ? 'var(--success-color)' : 'var(--error-color)'; ?>;">
                                                <?php echo ucfirst(htmlspecialchars($purchase['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.profile-tab');
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
        });
    </script>
</body>
</html>
