<?php
require_once 'config.php';

// Check if user is logged in
if (!isAuthenticated()) {
    header('Location: ../login.php?error=Please log in to update your profile');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get current user data
    $user = getCurrentUser();

    // Get form data
    $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
    $avatarUrl = filter_input(INPUT_POST, 'avatarUrl', FILTER_SANITIZE_URL);

    // Validate input
    if (empty($firstName) || empty($lastName)) {
        header('Location: ../profile.php?error=First name and last name are required');
        exit;
    }

    // Prepare user metadata
    $userData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'full_name' => $firstName . ' ' . $lastName,
        'avatar_url' => $avatarUrl
    ];

    // Update user metadata in Supabase Auth
    $updateAuthResponse = authenticatedRequest(
        '/auth/v1/user',
        'PUT',
        ['data' => $userData]
    );

    // Check if auth update was successful
    if ($updateAuthResponse['statusCode'] !== 200) {
        $errorMessage = 'Failed to update profile';

        if (isset($updateAuthResponse['data']['error'])) {
            $errorMessage = $updateAuthResponse['data']['error_description'] ?? $updateAuthResponse['data']['error'];
        }

        header('Location: ../profile.php?error=' . urlencode($errorMessage));
        exit;
    }

    // Update profile in Supabase Database
    $profileData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'full_name' => $firstName . ' ' . $lastName,
        'avatar_url' => $avatarUrl,
        'updated_at' => date('c')
    ];

    $updateProfileResponse = authenticatedRequest(
        '/rest/v1/profiles?id=eq.' . $user['id'],
        'PATCH',
        $profileData
    );

    // Check if profile update was successful
    if ($updateProfileResponse['statusCode'] !== 204) {
        // If profile doesn't exist, try to create it
        if ($updateProfileResponse['statusCode'] === 404) {
            $profileData['id'] = $user['id'];
            $createProfileResponse = authenticatedRequest(
                '/rest/v1/profiles',
                'POST',
                $profileData
            );

            if ($createProfileResponse['statusCode'] !== 201) {
                $errorMessage = 'Failed to create profile';

                if (isset($createProfileResponse['data']['error'])) {
                    $errorMessage = $createProfileResponse['data']['error_description'] ?? $createProfileResponse['data']['error'];
                }

                header('Location: ../profile.php?error=' . urlencode($errorMessage));
                exit;
            }
        } else {
            $errorMessage = 'Failed to update profile';

            if (isset($updateProfileResponse['data']['error'])) {
                $errorMessage = $updateProfileResponse['data']['error_description'] ?? $updateProfileResponse['data']['error'];
            }

            header('Location: ../profile.php?error=' . urlencode($errorMessage));
            exit;
        }
    }

    // Update session data
    $_SESSION['user']['user_metadata'] = $userData;

    // Redirect to profile page with success message
    header('Location: ../profile.php?success=Profile updated successfully');
    exit;
} else {
    // If not a POST request, redirect to profile page
    header('Location: ../profile.php');
    exit;
}
