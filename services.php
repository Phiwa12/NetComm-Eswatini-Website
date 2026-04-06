<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'netcomm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check login status endpoint
if (isset($_GET['action']) && $_GET['action'] === 'check_login') {
    $response = [
        'logged_in' => isset($_SESSION['user_id'])
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_firstname = $is_logged_in ? $_SESSION['user_firstname'] : '';

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_service' && isset($_POST['service_id'])) {
    $response = array();
    
    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $response['success'] = false;
            $response['message'] = 'Please log in to book a service.';
            $response['redirect'] = 'login.php';
        } else {
            // Get service details
            $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ? AND is_active = 1");
            $stmt->execute([$_POST['service_id']]);
            $service = $stmt->fetch();
            
            if (!$service) {
                $response['success'] = false;
                $response['message'] = 'Service not found.';
            } else {
                // Create a pending booking
                $stmt = $pdo->prepare("INSERT INTO bookings (user_id, service_id, booking_date, booking_time, location, contact_phone, notes, estimated_cost, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                
                $booking_date = $_POST['booking_date'] ?? date('Y-m-d', strtotime('+1 day'));
                $booking_time = $_POST['booking_time'] ?? '09:00:00';
                $location = $_POST['location'] ?? '';
                $contact_phone = $_POST['contact_phone'] ?? '';
                $notes = $_POST['notes'] ?? '';
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $_POST['service_id'],
                    $booking_date,
                    $booking_time,
                    $location,
                    $contact_phone,
                    $notes,
                    $service['service_price']
                ]);
                
                $response['success'] = true;
                $response['message'] = 'Booking request submitted successfully! Our team will contact you shortly to confirm the details.';
                $response['booking_id'] = $pdo->lastInsertId();
            }
        }
    } catch(Exception $e) {
        $response['success'] = false;
        $response['message'] = 'An error occurred while processing your booking. Please try again.';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch services from database
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $services = [];
}

// Service images mapping (fallback)
$service_images = [
    'ICT Technical Services' => 'https://images.unsplash.com/photo-1558346490-a72e53ae2d4f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80',
    'Hardware and Software Sales' => 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80',
    'Managed Services' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80',
    'Electronic Document Management' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80',
    'Data Security Solutions' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwa90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80',
    'IP CCTV Solutions' => 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80',
    'Access Control Solutions' => 'https://images.unsplash.com/photo-1585079374502-415f8516dcc3?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
    'Display & Signage Solutions' => 'https://images.unsplash.com/photo-1593833210845-d9935371664e?q=80&w=387&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
    'Structured Cabling' => 'https://images.unsplash.com/photo-1512790182412-b19e6d62bc39?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80'
];

// Service features mapping
$service_features = [
    'ICT Technical Services' => [
        'Server maintenance & optimization',
        'Network troubleshooting', 
        'System diagnostics',
        '24/7 technical support'
    ],
    'Hardware and Software Sales' => [
        'Managed supply chain',
        'On-premise warehousing',
        'Authorized reseller of top brands',
        'Competitive pricing'
    ],
    'Managed Services' => [
        'Proactive monitoring',
        'Local helpdesk support',
        'Job-card system',
        'Performance reporting'
    ],
    'Electronic Document Management' => [
        'Cloud-based solutions',
        'Secure document storage',
        'Automated workflows',
        'Compliance management'
    ],
    'Data Security Solutions' => [
        'Enterprise-grade firewalls',
        'Encryption services',
        'Threat detection',
        'Security audits'
    ],
    'IP CCTV Solutions' => [
        '4K resolution cameras',
        'Night vision technology',
        'Remote monitoring',
        'Motion detection'
    ],
    'Access Control Solutions' => [
        'Biometric authentication',
        'Card-based access',
        'Time-based permissions',
        'Visitor management'
    ],
    'Display & Signage Solutions' => [
        'Interactive whiteboards',
        'Projection systems',
        'Digital signage',
        'Video wall solutions'
    ],
    'Structured Cabling' => [
        'Fiber optic installation',
        'Wireless LAN solutions',
        'Network testing',
        'Infrastructure design'
    ]
];

// Premium services
$premium_services = ['ICT Technical Services', 'Structured Cabling'];
$essential_services = ['Data Security Solutions'];

// Handle custom service booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_custom_service') {
    $response = array();
    
    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $response['success'] = false;
            $response['message'] = 'Please log in to book a custom service.';
            $response['redirect'] = 'login.php';
        } else {
            // Validate form data
            $problem_description = trim($_POST['problem_description']);
            $contact_phone = trim($_POST['contact_phone']);
            $urgency = $_POST['urgency'];
            
            if (empty($problem_description)) {
                $response['success'] = false;
                $response['message'] = 'Please describe your problem.';
            } else {
                // Handle file uploads
                $uploaded_files = [];
                $upload_dir = 'uploads/custom_requests/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (!empty($_FILES['attachments']['name'][0])) {
                    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_name = time() . '_' . basename($_FILES['attachments']['name'][$key]);
                            $file_path = $upload_dir . $file_name;
                            
                            if (move_uploaded_file($tmp_name, $file_path)) {
                                $uploaded_files[] = $file_path;
                            }
                        }
                    }
                }
                
                // Save to database
                $stmt = $pdo->prepare("INSERT INTO custom_service_requests (user_id, problem_description, contact_phone, urgency, attachments, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                
                $attachments_json = !empty($uploaded_files) ? json_encode($uploaded_files) : null;
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $problem_description,
                    $contact_phone,
                    $urgency,
                    $attachments_json
                ]);
                
                $request_id = $pdo->lastInsertId();
                
                $response['success'] = true;
                $response['message'] = 'Your custom service request has been submitted successfully! Our technicians will review your issue and contact you shortly.';
                $response['request_id'] = $request_id;
            }
        }
    } catch(Exception $e) {
        $response['success'] = false;
        $response['message'] = 'An error occurred while processing your request. Please try again.';
        error_log("Custom service booking error: " . $e->getMessage());
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - NetComm ICT Solutions</title>
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
        }
        
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
        }
        
        .nav-links a:hover {
            color: var(--primary-blue);
        }
        
        .nav-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
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
        .services-hero {
            min-height: 70vh;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1573164713714-d95e436ab8d6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1740&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            padding: 120px 5% 60px;
            position: relative;
            overflow: hidden;
        }
        
        .services-hero-content {
            max-width: 650px;
            color: var(--white);
            z-index: 2;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .breadcrumb a {
            color: #64b5f6;
            text-decoration: none;
        }
        
        .breadcrumb span {
            margin: 0 8px;
            opacity: 0.7;
        }
        
        .services-hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .services-hero p {
            font-size: 1.2rem;
            line-height: 1.6;
            opacity: 0.95;
            margin-bottom: 30px;
        }
        
        /* Section Styling */
        .section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px;
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
            margin: 0;
        }
        
        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .service-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(12, 70, 247, 0.2);
        }
        
        .service-image {
            width: 100%;
            height: 250px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .service-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .service-content {
            padding: 25px;
        }
        
        .service-content h3 {
            font-size: 1.5rem;
            color: var(--dark-blue);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .service-content p {
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .service-features {
            margin-bottom: 25px;
        }
        
        .service-features ul {
            list-style: none;
        }
        
        .service-features li {
            color: var(--text-light);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .service-features li i {
            color: var(--primary-blue);
        }
        
        .btn-service {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-service:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(12, 70, 247, 0.3);
        }
        
        /* Why Choose Section */
        .why-choose {
            background: var(--light-bg);
            padding: 80px 5%;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: var(--white);
        }
        
        .feature-card h3 {
            font-size: 1.3rem;
            color: var(--dark-blue);
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: var(--text-light);
        }
        
        /* Custom Service Section */
        .custom-service-section {
            padding: 80px 5%;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .custom-service-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .custom-service-content {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 0;
        }
        
        .custom-service-info {
            padding: 40px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
        }
        
        .custom-service-info h3 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .custom-service-info > p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .benefits-list {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .benefit-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .benefit-text h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .benefit-text p {
            opacity: 0.8;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .custom-service-form-container {
            padding: 40px;
        }
        
        .form-header {
            margin-bottom: 30px;
        }
        
        .form-header h3 {
            font-size: 1.5rem;
            color: var(--dark-blue);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .form-header p {
            color: var(--text-light);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-blue);
        }
        
        .form-group textarea,
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-group textarea:focus,
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(12, 70, 247, 0.2);
        }
        
        .file-upload-container {
            margin-top: 8px;
        }
        
        .file-upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-blue);
            background-color: #f8fafc;
        }
        
        .file-upload-area i {
            font-size: 2.5rem;
            color: #a0aec0;
            margin-bottom: 15px;
        }
        
        .file-upload-area p {
            font-weight: 500;
            margin-bottom: 8px;
            color: #4a5568;
        }
        
        .file-upload-area span {
            font-size: 0.85rem;
            color: #718096;
        }
        
        .file-list {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: #f7fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .file-icon {
            margin-right: 10px;
            color: #4a5568;
        }
        
        .file-info {
            flex: 1;
            font-size: 0.9rem;
        }
        
        .file-name {
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .file-size {
            font-size: 0.8rem;
            color: #718096;
        }
        
        .file-remove {
            color: #e53e3e;
            cursor: pointer;
            padding: 5px;
        }
        
        .btn-submit-custom {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(12, 70, 247, 0.3);
        }
        
        .btn-submit-custom:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 80px 5%;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            text-align: center;
        }
        
        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-cta-primary {
            padding: 12px 30px;
            background: var(--white);
            color: var(--primary-blue);
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-cta-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-cta-secondary {
            padding: 12px 30px;
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-cta-secondary:hover {
            background: var(--white);
            color: var(--primary-blue);
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
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .footer-col ul li i {
            color: var(--accent-blue);
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            transition: var(--transition);
        }
        
        .social-links a:hover {
            background: var(--primary-blue);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-group-modal {
            margin-bottom: 20px;
        }
        
        .form-group-modal label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-blue);
        }
        
        .form-group-modal input,
        .form-group-modal textarea,
        .form-group-modal select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-group-modal input:focus,
        .form-group-modal textarea:focus,
        .form-group-modal select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(12, 70, 247, 0.2);
        }
        
        .form-group-modal input[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(12, 70, 247, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            display: none;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .custom-service-content {
                grid-template-columns: 1fr;
            }
            
            .services-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px 5%;
            }
            
            .nav-links {
                margin: 20px 0;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .services-hero {
                text-align: center;
                padding: 100px 5% 60px;
            }
            
            .services-hero h1 {
                font-size: 2.3rem;
            }
            
            .section {
                padding: 60px 15px;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
        
        @media (max-width: 576px) {
            .services-hero h1 {
                font-size: 2rem;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .custom-service-info,
            .custom-service-form-container {
                padding: 20px;
            }
            
            .benefit-item {
                flex-direction: column;
                text-align: center;
            }
            
            .benefit-icon {
                align-self: center;
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
        <a href="contact.php">Contact</a>
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
<section class="services-hero">
    <div class="services-hero-content">
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <span>Our Services</span>
        </div>
        <h1>Comprehensive ICT Solutions</h1>
        <p>With 49 years of collective experience, NetComm delivers cutting-edge technology services to transform your business operations and drive success.</p>
    </div>
</section>

<!-- Services Grid Section -->
<section class="section" id="services">
    <div class="section-title">
        <h2>Our Service Portfolio</h2>
        <p>Explore our comprehensive range of ICT solutions tailored to your business needs</p>
    </div>
    <div class="services-grid">
        <?php foreach ($services as $service): ?>
        <div class="service-card">
            <div class="service-image" style="background-image: url('<?php echo $service_images[$service['service_name']] ?? 'https://images.unsplash.com/photo-1558346490-a72e53ae2d4f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80'; ?>');">
                <?php if (in_array($service['service_name'], $premium_services)): ?>
                <div class="service-badge">Premium</div>
                <?php elseif (in_array($service['service_name'], $essential_services)): ?>
                <div class="service-badge">Essential</div>
                <?php endif; ?>
            </div>
            <div class="service-content">
                <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                <p><?php echo htmlspecialchars($service['service_description']); ?></p>
                <?php if (isset($service_features[$service['service_name']])): ?>
                <div class="service-features">
                    <ul>
                        <?php foreach ($service_features[$service['service_name']] as $feature): ?>
                        <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <a href="#" class="btn-service" onclick="checkLoginAndOpenModal(<?php echo $service['service_id']; ?>, '<?php echo htmlspecialchars($service['service_name']); ?>', <?php echo $service['service_price']; ?>)">Book This Service</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Custom Service Section -->
<section class="custom-service-section" id="custom-service">
    <div class="section-title">
        <h2>Premium Remote Support</h2>
        <p>Can't find the service you need? Our technicians can help remotely</p>
    </div>
    
    <div class="custom-service-container">
        <div class="custom-service-content">
            <div class="custom-service-info">
                <h3>Get Expert Help Without On-Site Visits</h3>
                <p>Many technical issues can be resolved remotely, saving you time and money. Our certified technicians can diagnose and fix problems through our secure remote support system.</p>
                
                <div class="benefits-list">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Remote Diagnostics</h4>
                            <p>Our technicians can access your system securely to diagnose issues</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Faster Resolution</h4>
                            <p>Get help immediately without waiting for a technician to arrive</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Cost Effective</h4>
                            <p>Save on travel costs with our remote support options</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="custom-service-form-container">
                <div class="form-header">
                    <h3>Describe Your Issue</h3>
                    <p>Provide details and upload files to help us understand your problem</p>
                </div>
                
                <form id="customServiceForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="book_custom_service">
                    
                    <div class="form-group">
                        <label for="problem_description">Problem Description *</label>
                        <textarea id="problem_description" name="problem_description" rows="5" placeholder="Please describe your technical issue in detail. Include error messages, when the problem started, and what you've tried so far." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone *</label>
                        <input type="tel" id="contact_phone" name="contact_phone" placeholder="+268 xxx xxxx" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="urgency">Urgency Level</label>
                        <select id="urgency" name="urgency">
                            <option value="low">Low - Can wait a few days</option>
                            <option value="medium" selected>Medium - Need help within 24 hours</option>
                            <option value="high">High - Critical issue affecting operations</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="attachments">Attachments (Optional)</label>
                        <div class="file-upload-container">
                            <div class="file-upload-area" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag & drop files here or click to browse</p>
                                <span>Supports images, videos, documents (Max 10MB each)</span>
                            </div>
                            <input type="file" id="attachments" name="attachments[]" multiple accept="image/*,video/*,.pdf,.doc,.docx,.txt" style="display: none;">
                            <div id="fileList" class="file-list"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit-custom" id="submitCustomRequest">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Section -->
<section class="why-choose">
    <div class="section-title">
        <h2>Why Choose NetComm?</h2>
        <p>With 49 years of collective experience, we bring unparalleled expertise to every project</p>
    </div>
    <div class="features">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-medal"></i>
            </div>
            <h3>Industry Accreditations</h3>
            <p>Only authorized warranty and service center for HPE, Microsoft, VMWare, and more in Eswatini.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Expert Team</h3>
            <p>52 dedicated staff members including certified engineers and technical specialists.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-truck"></i>
            </div>
            <h3>Comprehensive Logistics</h3>
            <p>18 dedicated service vehicles with real-time tracking for prompt service delivery.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-content">
        <h2>Ready to Transform Your Business?</h2>
        <p>Join hundreds of satisfied clients who trust NetComm for their ICT solutions</p>
        <div class="cta-buttons">
            <a href="contact.php" class="btn-cta-primary">Get Started Now</a>
            <a href="contact.php" class="btn-cta-secondary">Contact Our Team</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-grid">
        <div class="footer-col">
            <h3>About NetComm</h3>
            <p>With 26 years of independent operation and 49 years of collective experience, NetComm is Eswatini's leading provider of comprehensive ICT solutions.</p>
            <div class="social-links">
                <a href="https://www.facebook.com/netcommswaziland/"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.linkedin.com/company/netcomm-swaziland/"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h3>Our Locations</h3>
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> NetComm House, Plot 833 1st Street, Matsapha</li>
                <li><i class="fas fa-store"></i> Retail Outlet: Carters Gardens, Mbabane</li>
                <li><i class="fas fa-users"></i> 52 Dedicated Staff Members</li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Contact Us</h3>
            <ul>
                <li><i class="fas fa-phone"></i> +268 2518 7891/2</li>
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

<!-- Booking Modal -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Book Service</h2>
        <div id="alertContainer"></div>
        <form id="bookingForm">
            <input type="hidden" id="serviceId" name="service_id">
            <input type="hidden" name="action" value="book_service">
            
            <div class="form-group-modal">
                <label>Service:</label>
                <input type="text" id="serviceName" readonly>
            </div>
            
            <div class="form-group-modal">
                <label>Estimated Cost:</label>
                <input type="text" id="servicePrice" readonly>
            </div>
            
            <div class="form-group-modal">
                <label for="booking_date">Preferred Date:</label>
                <input type="date" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            
            <div class="form-group-modal">
                <label for="booking_time">Preferred Time:</label>
                <select id="booking_time" name="booking_time" required>
                    <option value="09:00:00">9:00 AM</option>
                    <option value="10:00:00">10:00 AM</option>
                    <option value="11:00:00">11:00 AM</option>
                    <option value="14:00:00">2:00 PM</option>
                    <option value="15:00:00">3:00 PM</option>
                    <option value="16:00:00">4:00 PM</option>
                </select>
            </div>
            
            <div class="form-group-modal">
                <label for="location">Service Location:</label>
                <textarea id="location" name="location" placeholder="Enter the address where service is required" required></textarea>
            </div>
            
            <div class="form-group-modal">
                <label for="contact_phone">Contact Phone:</label>
                <input type="tel" id="contact_phone" name="contact_phone" placeholder="+268 xxxx xxxx" required>
            </div>
            
            <div class="form-group-modal">
                <label for="notes">Additional Notes:</label>
                <textarea id="notes" name="notes" placeholder="Any specific requirements or additional information"></textarea>
            </div>
            
            <button type="submit" class="btn-submit" id="submitBtn">Submit Booking Request</button>
        </form>
    </div>
</div>

<script>
// Navigation active state
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage) {
            link.style.color = '#007bff';
            link.style.fontWeight = 'bold';
            link.style.borderBottom = '2px solid #007bff';
            link.style.paddingBottom = '4px';
        }
    });
});

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        navbar.style.background = '#ffffff';
    } else {
        navbar.style.boxShadow = 'none';
        navbar.style.background = '#ffffff';
    }
});

// Modal functionality
const modal = document.getElementById('bookingModal');
const closeBtn = document.querySelector('.close');

function openBookingModal(serviceId, serviceName, servicePrice) {
    document.getElementById('serviceId').value = serviceId;
    document.getElementById('serviceName').value = serviceName;
    document.getElementById('servicePrice').value = 'E' + parseFloat(servicePrice).toFixed(2);
    
    // Clear previous alerts
    document.getElementById('alertContainer').innerHTML = '';
    
    // Reset form
    document.getElementById('bookingForm').reset();
    document.getElementById('serviceId').value = serviceId;
    
    modal.style.display = 'block';
}

closeBtn.onclick = function() {
    modal.style.display = 'none';
}

window.onclick = function(event) {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Check login status before opening modal
function checkLoginAndOpenModal(serviceId, serviceName, servicePrice) {
    fetch('services.php?action=check_login')
        .then(response => response.json())
        .then(data => {
            if (data.logged_in) {
                openBookingModal(serviceId, serviceName, servicePrice);
            } else {
                // Show login prompt
                if (confirm('You need to log in to book this service. Would you like to log in now?')) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                }
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
            alert('Unable to verify login status. Please try again.');
        });
}

// Form submission
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const alertContainer = document.getElementById('alertContainer');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    // Clear previous alerts
    alertContainer.innerHTML = '';
    
    // Collect form data
    const formData = new FormData(this);
    
    // Send AJAX request
    fetch('services.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alertContainer.innerHTML = `
                <div class="alert alert-success" style="display: block;">
                    <i class="fas fa-check-circle"></i> ${data.message}
                </div>
            `;
            
            // Reset form after successful submission
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('bookingForm').reset();
            }, 3000);
            
        } else {
            alertContainer.innerHTML = `
                <div class="alert alert-error" style="display: block;">
                    <i class="fas fa-exclamation-triangle"></i> ${data.message}
                </div>
            `;
            
            // If redirect is needed 
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alertContainer.innerHTML = `
            <div class="alert alert-error" style="display: block;">
                <i class="fas fa-exclamation-triangle"></i> An error occurred. Please try again.
            </div>
        `;
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Booking Request';
    });
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Custom service form functionality
document.addEventListener('DOMContentLoaded', function() {
    const customServiceForm = document.getElementById('customServiceForm');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('attachments');
    const fileList = document.getElementById('fileList');
    const submitBtn = document.getElementById('submitCustomRequest');
    let uploadedFiles = [];
    
    // File upload area click handler
    fileUploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Drag and drop functionality
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    
    fileUploadArea.addEventListener('dragleave', function() {
        this.classList.remove('drag-over');
    });
    
    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        if (e.dataTransfer.files.length > 0) {
            handleFiles(e.dataTransfer.files);
        }
    });
    
    // File input change handler
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFiles(this.files);
            this.value = ''; // Reset input to allow selecting same files again
        }
    });
    
    // Handle selected files
    function handleFiles(files) {
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Check file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert(`File ${file.name} is too large. Maximum size is 10MB.`);
                continue;
            }
            
            // Add to uploaded files array
            uploadedFiles.push(file);
            
            // Add to file list display
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            // Get file icon based on type
            let fileIcon = 'file';
            if (file.type.startsWith('image/')) fileIcon = 'file-image';
            else if (file.type.startsWith('video/')) fileIcon = 'file-video';
            else if (file.type === 'application/pdf') fileIcon = 'file-pdf';
            
            fileItem.innerHTML = `
                <div class="file-icon">
                    <i class="fas fa-${fileIcon}"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${formatFileSize(file.size)}</div>
                </div>
                <div class="file-remove" data-index="${uploadedFiles.length - 1}">
                    <i class="fas fa-times"></i>
                </div>
            `;
            
            fileList.appendChild(fileItem);
        }
        
        // Add event listeners to remove buttons
        document.querySelectorAll('.file-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                uploadedFiles.splice(index, 1);
                updateFileList();
            });
        });
    }
    
    // Update file list display
    function updateFileList() {
        fileList.innerHTML = '';
        
        uploadedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            let fileIcon = 'file';
            if (file.type.startsWith('image/')) fileIcon = 'file-image';
            else if (file.type.startsWith('video/')) fileIcon = 'file-video';
            else if (file.type === 'application/pdf') fileIcon = 'file-pdf';
            
            fileItem.innerHTML = `
                <div class="file-icon">
                    <i class="fas fa-${fileIcon}"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${formatFileSize(file.size)}</div>
                </div>
                <div class="file-remove" data-index="${index}">
                    <i class="fas fa-times"></i>
                </div>
            `;
            
            fileList.appendChild(fileItem);
        });
        
        // Re-add event listeners to remove buttons
        document.querySelectorAll('.file-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                uploadedFiles.splice(index, 1);
                updateFileList();
            });
        });
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Form submission
    customServiceForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Check login status first
        fetch('services.php?action=check_login')
            .then(response => response.json())
            .then(data => {
                if (!data.logged_in) {
                    if (confirm('You need to log in to submit a custom service request. Would you like to log in now?')) {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href + '#custom-service');
                    }
                    return;
                }
                
                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Create FormData object
                const formData = new FormData(this);
                
                // Append uploaded files
                uploadedFiles.forEach(file => {
                    formData.append('attachments[]', file);
                });
                
                // Send AJAX request
                fetch('services.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(data.message);
                        // Reset form
                        customServiceForm.reset();
                        uploadedFiles = [];
                        fileList.innerHTML = '';
                    } else {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            alert(data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
                });
            })
            .catch(error => {
                console.error('Error checking login status:', error);
                alert('Unable to verify login status. Please try again.');
            });
    });
});
</script>
<script>
        // Navigation active state
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop() || 'index.html';
            const navLinks = document.querySelectorAll('.nav-links a');
            
            navLinks.forEach(link => {
                const linkPage = link.getAttribute('href');
                if (linkPage === currentPage) {
                    link.style.color = '#007bff';
                    link.style.fontWeight = 'bold';
                    link.style.borderBottom = '2px solid #007bff';
                    link.style.paddingBottom = '4px';
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 2px 15px rgba(0, 0, 0, 0.1)';
                navbar.style.background = '#ffffff';
            } else {
                navbar.style.boxShadow = 'none';
                navbar.style.background = '#ffffff';
            }
        });

        // Timeline animation
        document.addEventListener('DOMContentLoaded', function() {
            const timelineItems = document.querySelectorAll('.timeline-content');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            timelineItems.forEach(item => {
                item.style.opacity = 0;
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(item);
            });
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