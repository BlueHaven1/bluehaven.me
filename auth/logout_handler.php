<?php
require_once 'config.php';

// Check if user is authenticated
if (isAuthenticated()) {
    // Sign out from Supabase
    $response = signOut($_SESSION['access_token']);
    
    // Clear session data
    $_SESSION = [];
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['refresh_token'])) {
        setcookie('refresh_token', '', time() - 3600, '/', '', false, true);
    }
}

// Redirect to home page
header('Location: ../index.php');
exit;
