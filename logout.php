<?php
session_start();

// Include database connection
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Optional: Log logout activity
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $logout_time = date('Y-m-d H:i:s');
    
    
    
    try {
        $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, activity_type, activity_time) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, 'logout', $logout_time]);
    } catch (Exception $e) {
        // Handle logging error silently
        error_log("Logout logging error: " . $e->getMessage());
    }
    
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    unset($_COOKIE['remember_token']);
}

if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/');
    unset($_COOKIE['user_id']);
}

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page or homepage
header("Location: login.php?logout=success");
exit();
?>