<?php
// Supabase Configuration
define('SUPABASE_URL', 'https://affbpwmxqlfevucykwmo.supabase.co'); // Replace with your Supabase URL
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFmZmJwd214cWxmZXZ1Y3lrd21vIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDU1MDE2NDQsImV4cCI6MjA2MTA3NzY0NH0.ykzcTaRB2Rv1MV0CoL8PDU4wTi-uBT6ASU8rk4Qphd0'); // Replace with your Supabase anon key

// Session Configuration
session_start();

/**
 * Make a request to Supabase API
 *
 * @param string $endpoint The API endpoint
 * @param string $method The HTTP method (GET, POST, etc.)
 * @param array $data The data to send (for POST, PUT, etc.)
 * @param string $jwt Optional JWT token for authenticated requests
 * @return array The response data and status code
 */
function supabaseRequest($endpoint, $method = 'GET', $data = null, $jwt = null) {
    $url = SUPABASE_URL . $endpoint;

    // Log request details
    error_log("Supabase Request: $method $url");
    if ($data) {
        error_log("Request data: " . json_encode($data));
    }

    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=minimal'
    ];

    // Use JWT token if provided, otherwise use anon key
    if ($jwt) {
        $headers[] = 'Authorization: Bearer ' . $jwt;
        error_log("Using JWT token for authentication");
    } else {
        $headers[] = 'Authorization: Bearer ' . SUPABASE_KEY;
        error_log("Using anon key for authentication");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            error_log("POST/PUT data: $jsonData");
        }
    }

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for cURL errors
    if ($response === false) {
        error_log("cURL error: " . curl_error($ch));
    }

    curl_close($ch);

    // Log response
    error_log("Supabase Response status: $statusCode");
    if ($response) {
        error_log("Response data: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''));
    }

    return [
        'data' => json_decode($response, true),
        'statusCode' => $statusCode
    ];
}

/**
 * Make an authenticated request to Supabase API using the current user's JWT
 *
 * @param string $endpoint The API endpoint
 * @param string $method The HTTP method (GET, POST, etc.)
 * @param array $data The data to send (for POST, PUT, etc.)
 * @return array The response data and status code
 */
function authenticatedRequest($endpoint, $method = 'GET', $data = null) {
    if (!isAuthenticated()) {
        error_log("authenticatedRequest: User not authenticated");
        return [
            'data' => ['error' => 'Not authenticated'],
            'statusCode' => 401
        ];
    }

    // Log authentication details
    error_log("authenticatedRequest: User authenticated, using access token");
    error_log("authenticatedRequest: User ID: " . $_SESSION['user']['id']);
    error_log("authenticatedRequest: User role: " . ($_SESSION['user']['user_metadata']['role'] ?? 'not set'));

    // Check if token exists
    if (!isset($_SESSION['access_token'])) {
        error_log("authenticatedRequest: Access token not found in session");
        return [
            'data' => ['error' => 'Access token not found'],
            'statusCode' => 401
        ];
    }

    return supabaseRequest($endpoint, $method, $data, $_SESSION['access_token']);
}

/**
 * Sign up a new user
 *
 * @param string $email User's email
 * @param string $password User's password
 * @param array $userData Additional user data
 * @return array Response from Supabase
 */
function signUp($email, $password, $userData = []) {
    $data = [
        'email' => $email,
        'password' => $password,
        'data' => $userData
    ];

    return supabaseRequest('/auth/v1/signup', 'POST', $data);
}

/**
 * Sign in a user
 *
 * @param string $email User's email
 * @param string $password User's password
 * @return array Response from Supabase
 */
function signIn($email, $password) {
    $data = [
        'email' => $email,
        'password' => $password
    ];

    return supabaseRequest('/auth/v1/token?grant_type=password', 'POST', $data);
}

/**
 * Sign out the current user
 *
 * @param string $jwt The JWT token
 * @return array Response from Supabase
 */
function signOut($jwt) {
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . $jwt,
        'Content-Type: application/json'
    ];

    $url = SUPABASE_URL . '/auth/v1/logout';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'data' => json_decode($response, true),
        'statusCode' => $statusCode
    ];
}

/**
 * Check if user is authenticated
 *
 * @return bool True if user is authenticated, false otherwise
 */
function isAuthenticated() {
    return isset($_SESSION['user']) && isset($_SESSION['access_token']);
}

/**
 * Get the current authenticated user
 *
 * @return array|null The user data or null if not authenticated
 */
function getCurrentUser() {
    return isAuthenticated() ? $_SESSION['user'] : null;
}

/**
 * Check if the current user has a specific role
 *
 * @param string|array $roles Role or array of roles to check
 * @return bool True if user has any of the specified roles
 */
function hasRole($roles) {
    if (!isAuthenticated()) {
        error_log("hasRole check - User not authenticated");
        return false;
    }

    // Get user data
    $user = getCurrentUser();

    // Get user role from metadata or default to 'user'
    $userRole = $user['user_metadata']['role'] ?? 'user';

    // Debug log
    if (is_string($roles)) {
        error_log("hasRole check - User ID: " . $user['id'] . ", User Role: " . $userRole . ", Checking for role: " . $roles);
    } else if (is_array($roles)) {
        error_log("hasRole check - User ID: " . $user['id'] . ", User Role: " . $userRole . ", Checking for roles: " . implode(', ', $roles));
    }

    // If checking for a single role
    if (is_string($roles)) {
        return $userRole === $roles;
    }

    // If checking for multiple roles
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }

    return false;
}

/**
 * Check if the current user is an admin
 *
 * @return bool True if user is an admin or superadmin
 */
function isAdmin() {
    // Add debugging
    if (isAuthenticated()) {
        $user = getCurrentUser();
        $role = $user['user_metadata']['role'] ?? 'user';
        error_log("isAdmin check - User ID: " . $user['id'] . ", Role: " . $role);
    } else {
        error_log("isAdmin check - User not authenticated");
    }

    return hasRole(['admin', 'superadmin']);
}
