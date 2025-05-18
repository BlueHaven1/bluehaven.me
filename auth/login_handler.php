<?php
require_once 'config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Validate input
    if (empty($email) || empty($password)) {
        header('Location: ../login.php?error=Please fill in all fields');
        exit;
    }

    // Attempt to sign in
    $response = signIn($email, $password);

    // Debug response
    error_log('Supabase Login Response: ' . print_r($response, true));

    // Check if sign in was successful
    if ($response['statusCode'] === 200 && isset($response['data']['access_token'])) {
        // Store user data in session
        $_SESSION['access_token'] = $response['data']['access_token'];
        $_SESSION['refresh_token'] = $response['data']['refresh_token'];
        $_SESSION['user'] = $response['data']['user'];

        // Get user profile data including role
        $userId = $response['data']['user']['id'];
        $profileResponse = supabaseRequest(
            '/rest/v1/profiles?id=eq.' . $userId . '&select=*',
            'GET',
            null,
            $response['data']['access_token']
        );

        // If profile exists, add role to user metadata
        if ($profileResponse['statusCode'] === 200 && !empty($profileResponse['data'])) {
            $profile = $profileResponse['data'][0];
            if (isset($profile['role'])) {
                // Add role to user metadata in session
                if (!isset($_SESSION['user']['user_metadata'])) {
                    $_SESSION['user']['user_metadata'] = [];
                }
                $_SESSION['user']['user_metadata']['role'] = $profile['role'];
            }
        }

        // Set cookie if remember me is checked
        if ($remember) {
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            setcookie('refresh_token', $response['data']['refresh_token'], $expiry, '/', '', false, true);
        }

        // Redirect to dashboard or home page
        header('Location: ../index.php');
        exit;
    } else {
        // Handle error
        $errorMessage = 'Invalid email or password';

        if (isset($response['data']['error'])) {
            $errorMessage = $response['data']['error_description'] ?? $response['data']['error'];
        }

        header('Location: ../login.php?error=' . urlencode($errorMessage));
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header('Location: ../login.php');
    exit;
}
