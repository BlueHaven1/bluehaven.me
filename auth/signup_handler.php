<?php
require_once 'config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate input
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        header('Location: ../signup.php?error=Please fill in all fields');
        exit;
    }

    if ($password !== $confirmPassword) {
        header('Location: ../signup.php?error=Passwords do not match');
        exit;
    }

    if (strlen($password) < 8) {
        header('Location: ../signup.php?error=Password must be at least 8 characters long');
        exit;
    }

    // Additional user data
    $userData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'full_name' => $firstName . ' ' . $lastName
    ];

    // Attempt to sign up
    $response = signUp($email, $password, $userData);

    // Debug response
    error_log('Supabase Response: ' . print_r($response, true));

    // Check if sign up was successful
    if ($response['statusCode'] === 200 && isset($response['data']['user']['id'])) {
        // Redirect to login page with success message
        header('Location: ../login.php?success=Account created successfully. Please sign in.');
        exit;
    } else if ($response['statusCode'] === 200) {
        // The signup was successful but the response structure is different than expected
        header('Location: ../login.php?success=Account created successfully. Please sign in.');
        exit;
    } else {
        // Handle error
        $errorMessage = 'Failed to create account';

        if (isset($response['data']['error'])) {
            $errorMessage = $response['data']['error_description'] ?? $response['data']['error'];
        } else {
            // If no specific error message, show the response for debugging
            $errorMessage = 'Error: ' . print_r($response, true);
        }

        header('Location: ../signup.php?error=' . urlencode($errorMessage));
        exit;
    }
} else {
    // If not a POST request, redirect to signup page
    header('Location: ../signup.php');
    exit;
}
