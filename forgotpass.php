<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $reset_code = rand(100000, 999999); // 6-digit code

    // Save code to database
    $stmt = $pdo->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
    $stmt->execute([$reset_code, $email]);

    // Send email
    $subject = "NetComm Password Reset Code";
    $message = "Hello,\n\nYour password reset code is: $reset_code\n\nIf you didn't request this, please ignore the email.";
    $headers = "From: support@netcomm.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8";

    if (mail($email, $subject, $message, $headers)) {
        header("Location: forgotPasswordPage.php?message=Reset code sent to your email.");
    } else {
        header("Location: forgotPasswordPage.php?message=Failed to send email. Try again.");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-color: #0047ab;
            font-family: 'Segoe UI', sans-serif;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .forgot-container {
            background-color: white;
            color: #0047ab;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0, 71, 171, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .forgot-container h2 {
            margin-bottom: 20px;
        }

        .forgot-container input[type="email"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .forgot-container button {
            background-color: #0047ab;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .forgot-container button:hover {
            background-color: #003a91;
        }

        .message {
            margin-top: 15px;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h2>Forgot Your Password?</h2>
        <form method="POST" action="forgotPassword.php">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Code</button>
        </form>
        <?php if (isset($_GET['message'])): ?>
            <div class="message"><?= htmlspecialchars($_GET['message']) ?></div>
        <?php endif; ?>
    </div>
</body>
</html>

