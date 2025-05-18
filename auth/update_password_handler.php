<?php
require_once 'config.php';

// Check if user is logged in
if (!isAuthenticated()) {
    header('Location: ../login.php?error=Please log in to update your password');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        header('Location: ../profile.php?error=All password fields are required');
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        header('Location: ../profile.php?error=New passwords do not match');
        exit;
    }

    if (strlen($newPassword) < 8) {
        header('Location: ../profile.php?error=Password must be at least 8 characters long');
        exit;
    }

    // Get current user data
    $user = getCurrentUser();

    // Verify current password by attempting to sign in
    $verifyResponse = signIn($user['email'], $currentPassword);

    if ($verifyResponse['statusCode'] !== 200) {
        header('Location: ../profile.php?error=Current password is incorrect');
        exit;
    }

    // Update password in Supabase
    $updateResponse = authenticatedRequest(
        '/auth/v1/user',
        'PUT',
        ['password' => $newPassword]
    );

    // Check if update was successful
    if ($updateResponse['statusCode'] === 200) {
        // Redirect to profile page with success message
        header('Location: ../profile.php?success=Password updated successfully');
        exit;
    } else {
        // Handle error
        $errorMessage = 'Failed to update password';

        if (isset($updateResponse['data']['error'])) {
            $errorMessage = $updateResponse['data']['error_description'] ?? $updateResponse['data']['error'];
        }

        header('Location: ../profile.php?error=' . urlencode($errorMessage));
        exit;
    }
} else {
    // If not a POST request, redirect to profile page
    header('Location: ../profile.php');
    exit;
}
