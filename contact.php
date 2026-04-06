<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "netcomm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_firstname = $is_logged_in ? $_SESSION['user_firstname'] : '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['service_id'])) {
        // Booking form submission
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['booking_redirect'] = true;
            $_SESSION['form_data'] = $_POST;  // Save form data
            header("Location: login.php");  // Redirect to login page
            exit();
        }
        $userId = $_POST['user_id'];
        $serviceId = $_POST['service_id'];
        $bookingDate = $_POST['booking_date'];
        $bookingTime = $_POST['booking_time'];
        $location = $_POST['location'];
        $contactPhone = $_POST['contact_phone'];
        $notes = $_POST['notes'];

        $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, booking_date, booking_time, location, contact_phone, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssss", $userId, $serviceId, $bookingDate, $bookingTime, $location, $contactPhone, $notes);
        
        if ($stmt->execute()) {
            $bookingSuccess = true;
        } else {
            $bookingError = "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['message'])) {
        // Chatbot message submission
        session_start();
        if (!isset($_SESSION['session_id'])) {
            $_SESSION['session_id'] = uniqid();
        }
        
        $message = $_POST['message'];
        $sessionId = $_SESSION['session_id'];
        $timestamp = date('Y-m-d H:i:s');
        
        // Save user message
        $stmt = $conn->prepare("INSERT INTO messages (session_id, sender, message_text, message_timestamp) VALUES (?, 'user', ?, ?)");
        $stmt->bind_param("sss", $sessionId, $message, $timestamp);
        $stmt->execute();
        $stmt->close();
        
        // Process message and generate response
        $response = generateChatbotResponse($message, $conn);
        
        // Save bot response
        $stmt = $conn->prepare("INSERT INTO messages (session_id, sender, message_text, message_timestamp) VALUES (?, 'system', ?, ?)");
        $stmt->bind_param("sss", $sessionId, $response, $timestamp);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['response' => $response]);
        exit;
    }
}

// Chatbot response generation
function generateChatbotResponse($message, $conn) {
    $message = strtolower(trim($message));
    
    // Common questions mapping
    $responses = [
        'hello' => 'Hi there! How can I assist you today?',
        'hi' => 'Hello! How can I help you?',
        'help' => 'I can help with product information, service bookings, and company details. What do you need?',
        'services' => 'We offer: ICT Technical Services, Hardware/Software Sales, Managed Services, Document Management, Security Solutions, CCTV, Access Control, Display Solutions, and Structured Cabling.',
        'products' => 'We sell laptops, desktops, phones, tablets, printers, network and security equipment from brands like Apple, HP, and more.',
        'contact' => 'You can reach us at:<br>- Phone: +268 2518 7891<br>- Email: info@netcomm.co.sz<br>- Address: NetComm House, Plot 833 1st Street, Matsapha, Eswatini',
        'hours' => 'Our business hours are:<br>Monday-Friday: 8:00 AM - 5:30 PM<br>Saturday: 9:00 AM - 1:00 PM',
        'location' => 'We\'re located at NetComm House, Plot 833 1st Street, Matsapha, Eswatini. <a href="https://maps.google.com/maps?q=Matsapha" target="_blank">View on Google Maps</a>',
        'booking' => 'To book a service, please visit our contact page or tell me which service you need help with.',
        'thank you' => 'You\'re welcome! Is there anything else I can help with?',
        'bye' => 'Goodbye! Feel free to chat again if you need more assistance.'
    ];
    
    // Check for direct matches
    foreach ($responses as $keyword => $response) {
        if (strpos($message, $keyword) !== false) {
            return $response;
        }
    }
    
    // Check for services
    if (preg_match('/(install|network|cctv|security|access control|cabling|service)/', $message)) {
        $result = $conn->query("SELECT service_name, service_description FROM services");
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = "<b>{$row['service_name']}:</b> {$row['service_description']}";
        }
        return "We offer these services:<br>" . implode('<br><br>', $services);
    }
    
    // Check for products
    if (preg_match('/(laptop|desktop|phone|tablet|printer|product|device)/', $message)) {
        $result = $conn->query("SELECT proName, proDescription, proPrice FROM products LIMIT 3");
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = "<b>{$row['proName']}:</b> {$row['proDescription']} (Price: E{$row['proPrice']})";
        }
        return "Here are some products we offer:<br>" . implode('<br><br>', $products) . 
               "<br><br>Visit our products page for more details.";
    }
    
    // Default response
    return "I'm not sure I understand. Could you rephrase? Here are things I can help with: 
            <br>- Service information
            <br>- Product details
            <br>- Company contact info
            <br>- Business hours
            <br>- Location directions";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - NetComm ICT Solutions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0c46f7;
            --accent-blue: #6d8cff;
            --dark-blue: #1a365d;
            --text-light: #4a5568;
            --white: #ffffff;
            --light-bg: #f0f8ff;
            --shadow: 0 10px 30px rgba(12, 70, 247, 0.15);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
            background-color: var(--white);
        }
        
        /* Navbar Styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            background: var(--white);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .logo img {
            height: 50px;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--dark-blue);
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-blue);
            transition: var(--transition);
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .nav-links a:hover {
            color: var(--primary-blue);
        }
        
        .nav-links a.active {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        .nav-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(12, 70, 247, 0.3);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Hero Section */
        .contact-hero {
            min-height: 60vh;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&auto=format&fit=crop&w=1740&q=80');
            background-size: cover;
            background-position: center;
            padding: 150px 5% 80px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .contact-hero-content {
            max-width: 800px;
            margin: 0 auto;
            z-index: 2;
            color: var(--white);
        }
        
        .breadcrumb {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .breadcrumb a {
            color: var(--accent-blue);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .breadcrumb a:hover {
            color: var(--white);
        }
        
        .breadcrumb span {
            margin: 0 10px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .contact-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .contact-hero h1 span {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .contact-hero p {
            font-size: 1.2rem;
            color: #e2e8f0;
            margin-bottom: 30px;
            line-height: 1.7;
            max-width: 700px;
            margin: 0 auto 30px;
        }
        
        /* Contact Details Section */
        .contact-details {
            padding: 80px 5%;
            background: var(--light-bg);
            text-align: center;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .section-title p {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .contact-card {
            background: var(--white);
            border-radius: 15px;
            padding: 40px 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            text-align: center;
        }
        
        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(12, 70, 247, 0.2);
        }
        
        .contact-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 30px;
            color: var(--white);
        }
        
        .contact-card h3 {
            font-size: 1.5rem;
            color: var(--dark-blue);
            margin-bottom: 15px;
        }
        
        .contact-card p {
            color: var(--text-light);
            margin-bottom: 15px;
            line-height: 1.7;
        }
        
        .contact-info {
            font-size: 1.1rem;
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        /* Booking Form Section */
        .booking-form {
            padding: 80px 5%;
            background: var(--white);
        }
        
        .booking-form-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .booking-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-blue));
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 15px 18px;
            border: 2px solid #e1e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
            background: #fafbfc;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(12, 70, 247, 0.1);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border: none;
            padding: 18px 40px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(12, 70, 247, 0.4);
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
        }
        
        /* FAQ Section */
        .faq-section {
            padding: 80px 5%;
            background: var(--light-bg);
        }
        
        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .faq-item {
            background: var(--white);
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .faq-question {
            padding: 25px;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-blue);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }
        
        .faq-question:hover {
            color: var(--primary-blue);
        }
        
        .faq-question::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            transition: var(--transition);
        }
        
        .faq-item.active .faq-question::after {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            padding: 0 25px 25px;
            color: var(--text-light);
            line-height: 1.7;
        }
        
        /* Map Section */
        .map-section {
            padding: 80px 5%;
            background: var(--white);
            text-align: center;
        }
        
        .map-container {
            max-width: 1000px;
            margin: 0 auto;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            height: 450px;
        }
        
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Chatbot Styles */
        .chat-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border: none;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(12, 70, 247, 0.3);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: var(--transition);
        }
        
        .chat-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(12, 70, 247, 0.4);
        }
        
        .chat-container {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: var(--transition);
        }
        
        .chat-container.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .chat-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-header h4 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .chat-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            line-height: 1.5;
        }
        
        .message.bot {
            background: #f1f5f9;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
        
        .message.user {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        
        .chat-footer {
            padding: 15px;
            border-top: 1px solid #e1e8f0;
            display: flex;
            gap: 10px;
        }
        
        .chat-footer input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e1e8f0;
            border-radius: 25px;
            outline: none;
        }
        
        .chat-footer button {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Footer */
        .footer {
            background: var(--dark-blue);
            color: var(--white);
            padding: 60px 5% 30px;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-col h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-col h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-blue);
        }
        
        .footer-col p, .footer-col li {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.8;
            margin-bottom: 15px;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            display: flex;
            align-items: center;
        }
        
        .footer-col ul li i {
            margin-right: 10px;
            color: var(--accent-blue);
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            transition: var(--transition);
        }
        
        .social-links a:hover {
            background: var(--accent-blue);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 40px;
            margin-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
        
        /* Messages */
        .alert {
            padding: 15px 20px;
            margin: 20px 5%;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .contact-hero h1 {
                font-size: 3rem;
            }
            
            .nav-links {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .contact-hero {
                padding: 130px 5% 60px;
            }
            
            .contact-hero h1 {
                font-size: 2.5rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .booking-form-container {
                padding: 30px 25px;
            }
            
            .chat-container {
                width: 300px;
                right: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .contact-hero h1 {
                font-size: 2rem;
            }
            
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .chat-container {
                width: calc(100% - 40px);
                right: 20px;
                left: 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <img src="images/NetcommLogo.jpg" alt="NetComm Logo">
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="services.php">Services</a>
            <a href="products.php">Products</a>
            <a href="aboutUs.php">About</a>
            <a href="contact.php" class="active">Contact</a>
        </div>
        <?php if ($is_logged_in): ?>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($user_firstname); ?>!</span>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="nav-btn">Get Started</a>
        <?php endif; ?>
    </nav>

    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="contact-hero-content">
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Contact Us</span>
            </div>
            <h1>Get in <span>Touch</span> With Us</h1>
            <p>We're here to help. Reach out to NetComm for any inquiries or support. Our team is ready to assist you with all your ICT needs.</p>
        </div>
    </section>

    <!-- Booking Success/Error Messages -->
    <?php if (isset($bookingSuccess)): ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i> Booking submitted successfully! We'll contact you shortly.
        </div>
    <?php elseif (isset($bookingError)): ?>
        <div class="alert error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $bookingError; ?>
        </div>
    <?php endif; ?>

    <!-- Contact Details Section -->
    <section class="contact-details">
        <div class="section-title">
            <h2>Contact Information</h2>
            <p>We're here to help. Reach out to NetComm for any inquiries or support.</p>
        </div>
        
        <div class="contact-grid">
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>Our Location</h3>
                <p>Visit our main office</p>
                <div class="contact-info">NetComm House, Plot 833 1st Street, Matsapha, Eswatini</div>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Email Us</h3>
                <p>Send us a message anytime</p>
                <div class="contact-info">helpdesk@netcomm.co.sz</div>
                <div class="contact-info">sales@netcomm.co.sz</div>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <h3>Call Us</h3>
                <p>Speak with our team</p>
                <div class="contact-info">+268 2518 7891/2</div>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Business Hours</h3>
                <p>We're available during these hours</p>
                <div class="contact-info">Mon-Fri: 8:00 AM - 5:30 PM</div>
                <div class="contact-info">Saturday: Closed</div>
            </div>
        </div>
    </section>

    <!-- Service Booking Form -->
    <section class="booking-form">
        <div class="section-title">
            <h2>Book a Service</h2>
            <p>Schedule an appointment for our services</p>
        </div>
        
        <div class="booking-form-container">
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>">
                
                <div class="form-group">
                    <label for="service">Service:</label>
                    <select name="service_id" id="service" required>
                        <option value="">Select a service</option>
                        <?php
                        $services = $conn->query("SELECT * FROM services");
                        while ($service = $services->fetch_assoc()):
                        ?>
                        <option value="<?= $service['service_id'] ?>"><?= $service['service_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Preferred Date:</label>
                        <input type="date" name="booking_date" id="date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">Preferred Time:</label>
                        <input type="time" name="booking_time" id="time" min="08:00" max="17:00" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" name="location" placeholder="Where should we come?" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Contact Phone:</label>
                        <input type="tel" name="contact_phone" placeholder="+268" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Special Requirements:</label>
                        <input type="text" name="notes" placeholder="Any special requirements?">
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Book Service</button>
            </form>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="section-title">
            <h2>Frequently Asked Questions</h2>
            <p>Find answers to common questions below</p>
        </div>
        
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question">What are your business hours?</div>
                <div class="faq-answer">
                    <p>Our offices are open Monday to Friday from 8:00 AM to 5:00 PM, and Saturdays we are closed.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Where are you located?</div>
                <div class="faq-answer">
                    <p>We are located at NetComm House, Plot 833 1st Street, Matsapha, Eswatini. Feel free to visit us at our main office for any inquiries.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">How can I request a service or support?</div>
                <div class="faq-answer">
                    <p>You can reach out to us via phone at +268 2518 7891, email at info@netcomm.co.sz, or chat with our virtual assistant by clicking the chat icon at the bottom right.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">What products and brands do you support?</div>
                <div class="faq-answer">
                    <p>NetComm is an authorized partner and reseller for leading brands including Apple, Microsoft, Cisco, HPE, VMWare, and more.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">Do you offer installation and maintenance?</div>
                <div class="faq-answer">
                    <p>Yes, we provide professional installation and ongoing maintenance for network, security, and IT infrastructure solutions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="section-title">
            <h2>Find Us</h2>
            <p>See our location on the map</p>
        </div>
        
        <div class="map-container">
            <!-- Google Maps embed for the Matsapha location -->
            <iframe src="https://maps.google.com/maps?q=Matsapha&t=&z=13&ie=UTF8&iwloc=&output=embed"></iframe>
        </div>
    </section>

    <!-- Floating Chatbot Button and Chat Window -->
    <button class="chat-btn" id="chatToggle"><i class="fas fa-robot"></i></button>
    <div class="chat-container" id="chatWindow">
        <div class="chat-header">
            <h4>Chat with NetComm</h4>
            <span class="close-btn" id="closeChat">&times;</span>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="message bot">
                <p>Hi there! How can we assist you today? Here are some things I can help with:</p>
                <ul>
                    <li>Service information</li>
                    <li>Product details</li>
                    <li>Company contact info</li>
                    <li>Business hours</li>
                    <li>Location directions</li>
                </ul>
            </div>
        </div>
        <div class="chat-footer">
            <input type="text" id="chatInput" placeholder="Type a message..." />
            <button id="sendMessage"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>About NetComm</h3>
                <p>With 26 years of independent operation and 49 years of collective experience, NetComm is Eswatini's leading provider of comprehensive ICT solutions.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/netcommswaziland/"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.linkedin.com/company/netcomm-swaziland/"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://www.google.com/search?ludocid=14681509048804047213&hl=en&q=Netcomm%20PO%20B%20ox%205871%20Mbabane%20H100&_ga=2.175258208.399588845.1523874275-1729130064.1498116632"><i class="fab fa-google"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Our Locations</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> NetComm House, Plot 833 1st Street, Matsapha</li>
                    <li><i class="fas fa-store"></i> Retail Outlet: Carters Gardens, Mbabane</li>
                    <li><i class="fas fa-users"></i> 48 Dedicated Staff Members</li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Contact Us</h3>
                <ul>
                    <li><i class="fas fa-phone"></i>  +268 2518 7891/2</li>
                    <li><i class="fas fa-envelope"></i> helpdesk@netcomm.co.sz</li>
                    <li><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</li>
                    <li><i class="fas fa-clock"></i> Weekend: Closed</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 NetComm. All Rights Reserved. BSN Technical Services t/a NetComm since 1999.</p>
        </div>
    </footer>

    <script>
        // Chatbot functionality
        document.addEventListener('DOMContentLoaded', function() {
            const chatToggle = document.getElementById('chatToggle');
            const chatWindow = document.getElementById('chatWindow');
            const closeChat = document.getElementById('closeChat');
            const chatInput = document.getElementById('chatInput');
            const sendMessage = document.getElementById('sendMessage');
            const chatBody = document.getElementById('chatBody');
            
            // Toggle chat window
            chatToggle.addEventListener('click', function() {
                chatWindow.classList.toggle('active');
            });
            
            closeChat.addEventListener('click', function() {
                chatWindow.classList.remove('active');
            });
            
            // Send message function
            function sendMessageToBot() {
                const message = chatInput.value.trim();
                if (!message) return;
                
                // Add user message to chat
                chatBody.innerHTML += `
                    <div class="message user">
                        <p>${message}</p>
                    </div>
                `;
                
                // Clear input
                chatInput.value = '';
                
                // Add loading indicator
                chatBody.innerHTML += `
                    <div class="message bot">
                        <p><i class="fas fa-circle-notch fa-spin"></i> Thinking...</p>
                    </div>
                `;
                
                // Scroll to bottom
                chatBody.scrollTop = chatBody.scrollHeight;
                
                // Send to server
                $.post('', {message: message}, function(response) {
                    // Remove loading indicator
                    const messages = chatBody.querySelectorAll('.message');
                    messages[messages.length - 1].remove();
                    
                    // Add bot response
                    chatBody.innerHTML += `
                        <div class="message bot">
                            <p>${response.response}</p>
                        </div>
                    `;
                    
                    // Scroll to bottom
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 'json');
            }
            
            // Send button click
            sendMessage.addEventListener('click', sendMessageToBot);
            
            // Enter key in input
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessageToBot();
                }
            });
            
            // FAQ functionality
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                question.addEventListener('click', () => {
                    item.classList.toggle('active');
                });
            });
            
            // Booking form validation
            const bookingForm = document.querySelector('.booking-form form');
            if (bookingForm) {
                bookingForm.addEventListener('submit', function(e) {
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        e.preventDefault();
                        alert('Please log in to book a service.');
                        // Save scroll position to return to form after login
                        sessionStorage.setItem('scrollPosition', window.pageYOffset);
                        sessionStorage.setItem('returnToBooking', true);
                        window.location.href = 'login.php';
                    <?php endif; ?>
                });
            }
            
            // Restore form data after login
            if (sessionStorage.getItem('returnToBooking')) {
                sessionStorage.removeItem('returnToBooking');
                const scrollPosition = sessionStorage.getItem('scrollPosition');
                if (scrollPosition) {
                    window.scrollTo(0, parseInt(scrollPosition));
                    sessionStorage.removeItem('scrollPosition');
                }
                
                // Restore form data if available
                <?php if (isset($_SESSION['form_data'])): ?>
                    const formData = <?= json_encode($_SESSION['form_data']) ?>;
                    Object.keys(formData).forEach(key => {
                        const element = document.querySelector(`[name="${key}"]`);
                        if (element) {
                            element.value = formData[key];
                        }
                    });
                    <?php unset($_SESSION['form_data']); ?>
                <?php endif; ?>
            }
        });
    </script>

    
    <script>
        
(function() {
    'use strict';

    // Device detection
    function getDeviceType() {
        const width = window.innerWidth;
        if (width <= 480) return 'mobile';
        if (width <= 768) return 'tablet';
        if (width <= 1024) return 'small-desktop';
        return 'desktop';
    }

    // Check if device is touch-enabled
    function isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }

    // Dynamic font size adjustment based on device
    function adjustFontSizes() {
        const deviceType = getDeviceType();
        const root = document.documentElement;

        // Remove existing responsive classes
        document.body.classList.remove('mobile-view', 'tablet-view', 'desktop-view', 'touch-device');

        // Add device-specific classes
        document.body.classList.add(`${deviceType}-view`);
        
        if (isTouchDevice()) {
            document.body.classList.add('touch-device');
        }

        // Font size adjustments based on device type
        const fontAdjustments = {
            mobile: {
                'h1': '1.8rem',
                'h2': '1.5rem',
                'h3': '1.3rem',
                'h4': '1.1rem',
                'h5': '1rem',
                'h6': '0.9rem',
                'p': '0.9rem',
                'body': '14px',
                '.navbar': '0.85rem',
                '.btn': '0.9rem',
                '.form-control': '1rem',
                'label': '0.85rem',
                '.footer': '0.8rem'
            },
            tablet: {
                'h1': '2.2rem',
                'h2': '1.8rem',
                'h3': '1.5rem',
                'h4': '1.3rem',
                'h5': '1.1rem',
                'h6': '1rem',
                'p': '1rem',
                'body': '15px',
                '.navbar': '0.9rem',
                '.btn': '1rem',
                '.form-control': '1.1rem',
                'label': '0.9rem',
                '.footer': '0.85rem'
            },
            'small-desktop': {
                'h1': '2.5rem',
                'h2': '2rem',
                'h3': '1.7rem',
                'h4': '1.4rem',
                'h5': '1.2rem',
                'h6': '1.1rem',
                'p': '1rem',
                'body': '16px',
                '.navbar': '1rem',
                '.btn': '1rem',
                '.form-control': '1rem',
                'label': '1rem',
                '.footer': '0.9rem'
            },
            desktop: {
                'h1': '2.8rem',
                'h2': '2.3rem',
                'h3': '1.9rem',
                'h4': '1.5rem',
                'h5': '1.3rem',
                'h6': '1.1rem',
                'p': '1rem',
                'body': '16px',
                '.navbar': '1rem',
                '.btn': '1rem',
                '.form-control': '1rem',
                'label': '1rem',
                '.footer': '0.9rem'
            }
        };

        // Apply font size adjustments
        const adjustments = fontAdjustments[deviceType];
        Object.keys(adjustments).forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.style.fontSize = adjustments[selector];
            });
        });
    }

    // Add responsive CSS dynamically
    function addResponsiveStyles() {
        const styleId = 'responsive-enhancement-styles';
        
        // Remove existing styles if they exist
        const existingStyles = document.getElementById(styleId);
        if (existingStyles) {
            existingStyles.remove();
        }

        const css = `
            <style id="${styleId}">
                /* Base responsive improvements */
                * {
                    box-sizing: border-box;
                }

                /* Mobile-first approach */
                @media screen and (max-width: 480px) {
                    .mobile-view {
                        font-size: 14px !important;
                        line-height: 1.4 !important;
                    }
                    
                    .mobile-view .container,
                    .mobile-view .signup-container,
                    .mobile-view .hero-container {
                        padding: 10px !important;
                        margin: 0 !important;
                    }
                    
                    .mobile-view .navbar {
                        flex-direction: column !important;
                        padding: 10px !important;
                        text-align: center !important;
                    }
                    
                    .mobile-view .nav-links {
                        flex-direction: column !important;
                        gap: 5px !important;
                        margin: 10px 0 !important;
                    }
                    
                    .mobile-view .nav-links a {
                        display: block !important;
                        padding: 8px 12px !important;
                        font-size: 0.85rem !important;
                    }
                    
                    .mobile-view .signup-content {
                        flex-direction: column !important;
                        gap: 20px !important;
                    }
                    
                    .mobile-view .signup-form {
                        width: 100% !important;
                        padding: 20px 15px !important;
                    }
                    
                    .mobile-view .form-row {
                        flex-direction: column !important;
                        gap: 15px !important;
                    }
                    
                    .mobile-view .form-control {
                        font-size: 16px !important; /* Prevents zoom on iOS */
                        padding: 12px 15px !important;
                    }
                    
                    .mobile-view .btn-signup,
                    .mobile-view .btn {
                        padding: 12px 20px !important;
                        font-size: 0.9rem !important;
                        width: 100% !important;
                    }
                    
                    .mobile-view .footer-grid {
                        grid-template-columns: 1fr !important;
                        gap: 20px !important;
                    }
                    
                    .mobile-view .benefits {
                        flex-direction: column !important;
                        gap: 15px !important;
                    }
                }

                /* Tablet styles */
                @media screen and (min-width: 481px) and (max-width: 768px) {
                    .tablet-view {
                        font-size: 15px !important;
                        line-height: 1.5 !important;
                    }
                    
                    .tablet-view .container,
                    .tablet-view .signup-container {
                        padding: 15px !important;
                    }
                    
                    .tablet-view .navbar {
                        padding: 15px 20px !important;
                    }
                    
                    .tablet-view .nav-links a {
                        font-size: 0.9rem !important;
                        padding: 8px 15px !important;
                    }
                    
                    .tablet-view .form-control {
                        font-size: 16px !important;
                        padding: 10px 15px !important;
                    }
                    
                    .tablet-view .signup-form {
                        padding: 30px 25px !important;
                    }
                    
                    .tablet-view .footer-grid {
                        grid-template-columns: repeat(2, 1fr) !important;
                        gap: 25px !important;
                    }
                }

                /* Touch device enhancements */
                .touch-device button,
                .touch-device .btn,
                .touch-device .nav-btn {
                    min-height: 44px !important; /* Apple's recommended touch target size */
                    min-width: 44px !important;
                }
                
                .touch-device input,
                .touch-device select,
                .touch-device textarea {
                    min-height: 44px !important;
                }
                
                .touch-device .nav-links a {
                    padding: 12px 16px !important;
                    margin: 2px !important;
                }

                /* Prevent text size adjust on orientation change */
                html {
                    -webkit-text-size-adjust: 100% !important;
                    -ms-text-size-adjust: 100% !important;
                    text-size-adjust: 100% !important;
                }

                /* Improve readability */
                body {
                    -webkit-font-smoothing: antialiased !important;
                    -moz-osx-font-smoothing: grayscale !important;
                }

                /* Better scrolling on mobile */
                @media screen and (max-width: 768px) {
                    body {
                        -webkit-overflow-scrolling: touch !important;
                        overflow-x: hidden !important;
                    }
                }

                /* Form improvements for mobile */
                @media screen and (max-width: 480px) {
                    .password-toggle {
                        right: 12px !important;
                        width: 30px !important;
                        height: 30px !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                    }
                    
                    .input-with-icon {
                        position: relative !important;
                    }
                    
                    .input-with-icon i {
                        left: 12px !important;
                        top: 50% !important;
                        transform: translateY(-50%) !important;
                    }
                    
                    .password-field .form-control {
                        padding-right: 45px !important;
                        padding-left: 45px !important;
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', css);
    }

    // Handle orientation changes
    function handleOrientationChange() {
        setTimeout(() => {
            adjustFontSizes();
        }, 100);
    }

    // Debounce function for resize events
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize responsive enhancements
    function initResponsiveEnhancements() {
        // Add responsive styles
        addResponsiveStyles();
        
        // Initial font adjustment
        adjustFontSizes();
        
        // Handle window resize with debouncing
        const debouncedResize = debounce(() => {
            adjustFontSizes();
        }, 250);
        
        window.addEventListener('resize', debouncedResize);
        
        // Handle orientation change
        window.addEventListener('orientationchange', handleOrientationChange);
        
        // Handle page visibility change (for when user returns to page)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                setTimeout(adjustFontSizes, 100);
            }
        });
        
        console.log('✅ Responsive text and mobile enhancements loaded');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initResponsiveEnhancements);
    } else {
        initResponsiveEnhancements();
    }

    // Expose utility functions globally
    window.ResponsiveUtils = {
        getDeviceType,
        isTouchDevice,
        adjustFontSizes
    };

})(); 
</script>
</body>
</html>