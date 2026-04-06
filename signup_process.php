<?php
session_start();
header('Content-Type: application/json');

// Include database and user class
include_once 'config/database.php';
include_once 'classes/user.php';

// Helper functions
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
}

// Response function
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Check if required fields are present
$required_fields = ['firstName', 'lastName', 'email', 'password', 'confirmPassword'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        sendResponse(false, 'Please fill in all required fields');
    }
}

// Validate and sanitize inputs
$firstName = validateInput($_POST['firstName']);
$lastName = validateInput($_POST['lastName']);
$email = strtolower(validateInput($_POST['email'])); // Normalize to lowercase
$company = isset($_POST['company']) ? validateInput($_POST['company']) : '';
$password = $_POST['password'];
$confirmPassword = $_POST['confirmPassword'];

// Validation checks
if (strlen($firstName) < 2 || strlen($firstName) > 100) {
    sendResponse(false, 'First name must be between 2 and 100 characters');
}

if (strlen($lastName) < 2 || strlen($lastName) > 100) {
    sendResponse(false, 'Last name must be between 2 and 100 characters');
}

if (!validateEmail($email)) {
    sendResponse(false, 'Please enter a valid email address');
}

if ($password !== $confirmPassword) {
    sendResponse(false, 'Passwords do not match');
}

if (!validatePassword($password)) {
    sendResponse(false, 'Password must be at least 8 characters with uppercase, lowercase, and number');
}

// Database operations
try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        sendResponse(false, 'Database connection failed');
    }

    $user = new User($db);
    $user->firstname = $firstName;
    $user->lastname = $lastName;
    $user->email = $email;
    $user->password = $password;
    $user->phone = '';
    $user->user_type = 'customer';
    $user->is_active = 1;
    $user->email_verified = 0; 

    // Check if email already exists
    if ($user->emailExists()) {
        sendResponse(false, 'An account with this email address already exists');
    }

    // Create the user
    if ($user->create()) {
        // For testing only - remove in production
        if ($user->email_verified == 0) {
            error_log("New user created but not verified: $email");
        }

        // Set session variables
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;

        sendResponse(true, 'Account created successfully! Welcome to NetComm.', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $firstName . ' ' . $lastName
        ]);

    } else {
        sendResponse(false, 'Unable to create account. Please try again.');
    }

} catch (PDOException $e) {
    error_log("Signup error: " . $e->getMessage());
    sendResponse(false, 'Database error. Please try again later.');
} catch (Exception $e) {
    error_log("System error: " . $e->getMessage());
    sendResponse(false, 'System error. Please contact support.');
}
?>



