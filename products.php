<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'netcomm';
$username = 'netcomm1';
$password = 'Wr06Ma0a026208';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Define current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

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

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Please log in to add items to your cart";
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = :id");
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Save to session cart
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        // Save to database cart
        try {
            // Check if product already in cart
            $checkStmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
            $checkStmt->bindParam(':user_id', $user_id);
            $checkStmt->bindParam(':product_id', $product_id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Update existing cart item
                $updateStmt = $pdo->prepare("UPDATE cart SET quantity = quantity + :quantity 
                                              WHERE user_id = :user_id AND product_id = :product_id");
                $updateStmt->bindParam(':quantity', $quantity);
                $updateStmt->bindParam(':user_id', $user_id);
                $updateStmt->bindParam(':product_id', $product_id);
                $updateStmt->execute();
            } else {
                // Insert new cart item
                $insertStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) 
                                             VALUES (:user_id, :product_id, :quantity)");
                $insertStmt->bindParam(':user_id', $user_id);
                $insertStmt->bindParam(':product_id', $product_id);
                $insertStmt->bindParam(':quantity', $quantity);
                $insertStmt->execute();
            }
        } catch(PDOException $e) {
            // Log error but don't break functionality
            error_log("Cart database error: " . $e->getMessage());
        }

        $_SESSION['cart_message'] = "Product added to cart successfully!";
    } else {
        $_SESSION['error_message'] = "Invalid product!";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get Cart Total Quantity
$cart_total_quantity = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_total_quantity += $qty;
    }
}

// Get Categories with hierarchy
$grouped_by_parent = [];
$stmt = $pdo->query("SELECT * FROM categories ORDER BY parent_category_id, category_name");
$raw_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($raw_categories as $cat) {
    $parent_id = $cat['parent_category_id'] ?? 0;
    $grouped_by_parent[$parent_id][] = $cat;
}

$top_categories = $grouped_by_parent[0] ?? [];
foreach ($top_categories as &$top_cat) {
    $cat_id = $top_cat['category_id'];
    $top_cat['children'] = $grouped_by_parent[$cat_id] ?? [];
}
unset($top_cat);

// Get all products
$all_products = [];
try {
    $stmt = $pdo->query("SELECT * FROM products");
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Product fetch error: " . $e->getMessage());
}

// Group products by category name
$products_by_category = [];
foreach ($all_products as $product) {
    $category = $product['category'];
    if (!isset($products_by_category[$category])) {
        $products_by_category[$category] = [];
    }
    $products_by_category[$category][] = $product;
}

// Get Accessories
function getAccessories($product_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM accessories WHERE product_id = :pid");
    $stmt->bindParam(':pid', $product_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get Product Images
function getProductImages($product_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :pid ORDER BY is_primary DESC");
    $stmt->bindParam(':pid', $product_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle custom product request form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_custom_request'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $product_type = $_POST['product_type'] ?? '';
    $specifications = $_POST['specifications'] ?? '';
    $intended_use = $_POST['usage'] ?? '';
    $budget = $_POST['budget'] ?? '';
    $timeframe = $_POST['timeframe'] ?? '';
    
    // Basic validation
    if (!empty($name) && (!empty($email) || !empty($phone)) && !empty($specifications)) {
        // Save to database
        try {
            $stmt = $pdo->prepare("INSERT INTO custom_requests (name, email, phone, product_type, specifications, intended_use, budget, timeframe, submitted_at) 
                       VALUES (:name, :email, :phone, :product_type, :specifications, :intended_use, :budget, :timeframe, NOW())");

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':product_type', $product_type);
            $stmt->bindParam(':specifications', $specifications);
            $stmt->bindParam(':intended_use', $intended_use);
            $stmt->bindParam(':budget', $budget);
            $stmt->bindParam(':timeframe', $timeframe);
            $stmt->execute();
            
            $_SESSION['custom_request_success'] = "Your request has been submitted successfully! Our team will contact you shortly.";
        } catch(PDOException $e) {
            error_log("Custom request error: " . $e->getMessage());
            $_SESSION['custom_request_error'] = "There was an error submitting your request. Please try again or contact us directly.";
        }
    } else {
        $_SESSION['custom_request_error'] = "Please fill in all required fields (name, contact information, and specifications).";
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "#custom-request");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - NetComm ICT Solutions</title>
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
        
        .cart-section {
            position: relative;
            margin-left: 20px;
        }
        
        .cart-link {
            display: flex;
            align-items: center;
            color: var(--dark-blue);
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .cart-count {
            background: var(--primary-blue);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        /* Hero Section */
        .products-hero {
            min-height: 60vh;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1499951360447-b19be8fe80f5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1740&q=80');
            background-size: cover;
            background-position: center;
            padding: 150px 5% 80px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .products-hero-content {
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
        
        .products-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .products-hero h1 span {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .products-hero p {
            font-size: 1.2rem;
            color: #e2e8f0;
            margin-bottom: 30px;
            line-height: 1.7;
            max-width: 700px;
            margin: 0 auto 30px;
        }
        
        /* Category Navigation */
        .category-nav {
            position: sticky;
            top: 90px;
            background: var(--white);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            z-index: 900;
            padding: 15px 5%;
        }
        
        .category-list {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .category-item {
            padding: 10px 20px;
            border-radius: 30px;
            background: transparent;
            color: var(--text-light);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .category-item:hover, .category-item.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border-color: var(--primary-blue);
        }
        
        /* Product Sections */
        .product-section {
            padding: 80px 5%;
            position: relative;
            background: var(--light-bg);
        }
        
        .product-section:nth-child(even) {
            background: var(--white);
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
        
        .subcategory-title {
            font-size: 1.8rem;
            color: var(--dark-blue);
            margin: 40px 0 20px;
            padding-left: 20px;
            position: relative;
        }
        
        .subcategory-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 30px;
            width: 6px;
            background: var(--primary-blue);
            border-radius: 3px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .product-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(12, 70, 247, 0.2);
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-blue);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .product-badge.bestseller {
            background: #ff6b35;
            left: 15px;
            top: 50px;
        }
        
        .product-image {
            height: 220px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, var(--light-bg), #e6f0ff);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-image img {
            max-width: 80%;
            max-height: 80%;
            transition: var(--transition);
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .product-content {
            padding: 25px;
        }
        
        .product-content h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--dark-blue);
        }
        
        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 15px;
        }
        
        .product-specs {
            margin-bottom: 20px;
        }
        
        .product-specs li {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 0.9rem;
        }
        
        .product-specs li i {
            color: var(--primary-blue);
            margin-right: 10px;
            font-size: 0.8rem;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-product {
            flex: 1;
            min-width: 120px;
            text-align: center;
            background: transparent;
            color: var(--primary-blue);
            border: 2px solid var(--primary-blue);
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-product:hover {
            background: var(--primary-blue);
            color: var(--white);
            transform: translateY(-3px);
        }
        
        .btn-specs {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
        }
        
        .btn-specs:hover {
            box-shadow: 0 4px 15px rgba(12, 70, 247, 0.3);
        }
        
        /* Accessories Panel */
        .accessories-panel {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        
        .accessories-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--dark-blue);
            display: flex;
            align-items: center;
        }
        
        .accessories-title i {
            margin-right: 10px;
            color: var(--primary-blue);
        }
        
        .accessories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .accessory-item {
            background: var(--white);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        
        .accessory-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(12, 70, 247, 0.1);
        }
        
        .accessory-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--light-bg), var(--white));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
            color: var(--primary-blue);
        }
        
        .accessory-item h4 {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .accessory-price {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-blue);
        }
        
        /* Custom Request Section */
        .custom-request-section {
            padding: 80px 5%;
            background: var(--light-bg);
        }
        
        .request-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .request-option {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .request-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(12, 70, 247, 0.2);
        }
        
        .option-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: var(--white);
        }
        
        .request-option h3 {
            font-size: 1.5rem;
            color: var(--dark-blue);
            margin-bottom: 15px;
        }
        
        .request-option p {
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        .contact-info {
            background: rgba(12, 70, 247, 0.08);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .contact-info p {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .btn-option {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            font-size: 1rem;
        }
        
        .btn-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(12, 70, 247, 0.3);
        }
        
        .custom-request-form {
            margin-top: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }
        
        .form-group.full-width {
            flex: 0 0 100%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(12, 70, 247, 0.1);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(12, 70, 247, 0.3);
        }
        
        /* Software Solutions Section */
        .software-solutions-section {
            padding: 80px 5%;
            background: var(--white);
        }
        
        .software-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto 50px;
        }
        
        .software-category {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .software-category:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(12, 70, 247, 0.2);
        }
        
        .category-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: var(--white);
            padding: 25px;
            display: flex;
            align-items: center;
        }
        
        .category-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.8rem;
        }
        
        .category-header h3 {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .category-content {
            padding: 25px;
        }
        
        .category-content p {
            color: var(--text-light);
            margin-bottom: 15px;
        }
        
        .category-content ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        
        .category-content li {
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        
        .m365-option {
            margin-bottom: 20px;
        }
        
        .m365-option h4 {
            color: var(--primary-blue);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .btn-software {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            font-size: 0.95rem;
            margin-top: 15px;
        }
        
        .btn-software:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(12, 70, 247, 0.3);
        }
        
        .software-cta {
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            color: var(--white);
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .cta-content h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .cta-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 25px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn-cta {
            background: var(--white);
            color: var(--primary-blue);
            border: none;
            padding: 15px 35px;
            border-radius: 30px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1.1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-cta:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            background: var(--accent-blue);
            color: var(--white);
        }
        
        /* Warranty Section */
        .warranty-section {
            padding: 80px 5%;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .warranty-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .warranty-content h2 {
            font-size: 2.8rem;
            margin-bottom: 20px;
        }
        
        .warranty-content p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 40px;
            max-width: 600px;
            margin: 0 auto 40px;
        }
        
        .warranty-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .warranty-feature {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(5px);
        }
        
        .warranty-feature i {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--accent-blue);
        }
        
        .warranty-feature h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
        }
        
        .warranty-feature p {
            font-size: 1rem;
            opacity: 0.9;
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
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .products-hero h1 {
                font-size: 3rem;
            }
            
            .nav-links {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .products-hero {
                padding: 130px 5% 60px;
            }
            
            .products-hero h1 {
                font-size: 2.5rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .category-list {
                gap: 10px;
            }
            
            .category-item {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            
            .request-options {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .software-categories {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .products-hero h1 {
                font-size: 2rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .category-nav {
                top: 70px;
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
            <a href="index.php" class="<?= $currentPage == 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="services.php" class="<?= $currentPage == 'services.php' ? 'active' : '' ?>">Services</a>
            <a href="products.php" class="<?= $currentPage == 'products.php' ? 'active' : '' ?>">Products</a>
            <a href="aboutUs.php" class="<?= $currentPage == 'aboutUs.php' ? 'active' : '' ?>">About</a>
            <a href="contact.php" class="<?= $currentPage == 'contact.php' ? 'active' : '' ?>">Contact</a>
        </div>
        <?php if ($is_logged_in): ?>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($user_firstname); ?>!</span>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
            
            <div class="cart-section">
                <a href="cart.php" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?= $cart_total_quantity ?></span>
                </a>
            </div>
        <?php else: ?>
            <a href="login.php" class="nav-btn">Get Started</a>
        <?php endif; ?>
    </nav>

    <!-- Products Hero Section -->
    <section class="products-hero">
        <div class="products-hero-content">
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Our Products</span>
            </div>
            <h1>Premium <span>Technology Solutions</span></h1>
            <p>Explore our extensive range of cutting-edge hardware, software, and accessories from the world's leading technology brands.</p>
        </div>
    </section>

    <!-- Category Navigation -->
    <div class="category-nav">
        <div class="category-list">
            <?php foreach ($top_categories as $index => $category): ?>
                <div class="category-item <?= $index === 0 ? 'active' : '' ?>" 
                     onclick="showCategory('category_<?= $category['category_id'] ?>')">
                    <?= htmlspecialchars($category['category_name']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Products Sections -->
    <div id="products">
        <?php foreach ($top_categories as $top_category): ?>
            <section class="product-section" id="category_<?= $top_category['category_id'] ?>">
                <div class="section-title">
                    <h2><?= htmlspecialchars($top_category['category_name']) ?></h2>
                    <p><?= htmlspecialchars($top_category['category_description'] ?? '') ?></p>
                </div>
                
                <!-- Products directly under top-level category -->
                <?php 
                $category_name = $top_category['category_name'];
                $products_in_category = $products_by_category[$category_name] ?? [];
                ?>
                <?php if (!empty($products_in_category)): ?>
                    <h3 class="subcategory-title"><?= htmlspecialchars($category_name) ?></h3>
                    <div class="products-grid">
                        <?php foreach ($products_in_category as $product): ?>
                            <?php include 'product_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Child categories -->
                <?php foreach ($top_category['children'] as $child): ?>
                    <?php 
                    $child_category_name = $child['category_name'];
                    $child_products = $products_by_category[$child_category_name] ?? [];
                    ?>
                    <h3 class="subcategory-title"><?= htmlspecialchars($child_category_name) ?></h3>
                    <div class="products-grid">
                        <?php if (!empty($child_products)): ?>
                            <?php foreach ($child_products as $product): ?>
                                <?php include 'product_card.php'; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 20px; color: #ccc;"></i>
                                <p>No products available in this category yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>

    <!-- Custom Request Section -->
    <section class="custom-request-section" id="custom-request">
        <div class="section-title">
            <h2>Need Something Special?</h2>
            <p>Can't find what you're looking for? We can source specialized equipment for your unique requirements.</p>
        </div>
        
        <div class="request-options">
            <div class="request-option" id="call-option">
                <div class="option-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h3>Speak Directly with Our Experts</h3>
                <p>Get personalized recommendations and technical advice from our experienced team.</p>
                <div class="contact-info">
                    <p><strong>Sales Team:</strong> +268 2518 7891/2</p>
                    <p><strong>Helpdesk:</strong> helpdesk@netcomm.co.sz</p>
                    <p><strong>Hours:</strong> Mon-Fri, 8:00 AM - 5:00 PM</p>
                </div>
                <button class="btn-option" onclick="window.location.href='tel:+26825187891'">
                    Call Now
                </button>
            </div>
            
            <div class="request-option" id="form-option">
                <div class="option-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Submit Your Requirements</h3>
                <p>Tell us what you need and we'll find the perfect solution for you.</p>
                
                <form class="custom-request-form" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="product_type">Product Type/Category</label>
                            <select id="product_type" name="product_type">
                                <option value="">Select a category</option>
                                <option value="Laptop">Laptop/Computer</option>
                                <option value="Server">Server/Networking</option>
                                <option value="Peripheral">Peripheral/Accessory</option>
                                <option value="Software">Software</option>
                                <option value="Display">Display/Monitor</option>
                                <option value="Storage">Storage Solution</option>
                                <option value="Security">Security System</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="specifications">Required Specifications *</label>
                        <textarea id="specifications" name="specifications" rows="4" placeholder="Please describe what you need, including any specific technical requirements, brands, or features..." required></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="usage">Intended Use/Purpose</label>
                        <textarea id="usage" name="usage" rows="2" placeholder="How will you use this product? (e.g., gaming, business applications, graphic design, etc.)"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="budget">Budget Range (if any)</label>
                            <select id="budget" name="budget">
                                <option value="">Not specified</option>
                                <option value="Under E1000">Under E1000</option>
                                <option value="E1000 - E5000">E1000 - E5000</option>
                                <option value="E5000 - E10000">E5000 - E10000</option>
                                <option value="E10000 - E20000">E10000 - E20000</option>
                                <option value="Over E20000">Over E20000</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="timeframe">Required Timeframe</label>
                            <select id="timeframe" name="timeframe">
                                <option value="">Not urgent</option>
                                <option value="Within a week">Within a week</option>
                                <option valueWithin 2 weeks">Within 2 weeks</option>
                                <option value="Within a month">Within a month</option>
                                <option value="More than a month">More than a month</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit_custom_request" class="btn-submit">
                        Submit Request
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Software Solutions Section -->
    <section class="software-solutions-section">
        <div class="section-title">
            <h2>Software Solutions & Services</h2>
            <p>Comprehensive software packages and expert implementation services for businesses and organizations</p>
        </div>

        <div class="software-categories">
            <!-- Antivirus Solutions -->
            <div class="software-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Security Solutions</h3>
                </div>
                <div class="category-content">
                    <p>Protect your systems with industry-leading antivirus and security software:</p>
                    <ul>
                        <li>Enterprise-grade endpoint protection</li>
                        <li>Network security solutions</li>
                        <li>Data encryption tools</li>
                        <li>Email security gateways</li>
                        <li>Multi-device protection plans</li>
                    </ul>
                    <p>We recommend and implement security solutions tailored to your specific needs.</p>
                    <button class="btn-software" onclick="scrollToCustomRequest('Security Software')">Request Security Consultation</button>
                </div>
            </div>

            <!-- Microsoft 365 Solutions -->
            <div class="software-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3>Microsoft 365 Solutions</h3>
                </div>
                <div class="category-content">
                    <div class="m365-option">
                        <h4>For NGOs & Non-Profit Organizations</h4>
                        <p>Special discounted rates for qualified organizations:</p>
                        <ul>
                            <li>Email hosting with custom domain</li>
                            <li>Collaboration tools (Teams, SharePoint)</li>
                            <li>Cloud storage solutions</li>
                            <li>Office applications</li>
                            <li>Technical support included</li>
                        </ul>
                    </div>
                    <div class="m365-option">
                        <h4>For Commercial Businesses</h4>
                        <p>Comprehensive packages for businesses of all sizes:</p>
                        <ul>
                            <li>Business Premium packages</li>
                            <li>Enterprise-grade security</li>
                            <li>Advanced compliance tools</li>
                            <li>24/7 technical support</li>
                            <li>Seamless migration services</li>
                        </ul>
                    </div>
                    <button class="btn-software" onclick="scrollToCustomRequest('Microsoft 365')">Get Microsoft 365 Quote</button>
                </div>
            </div>

            <!-- Migration Services -->
            <div class="software-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>System Migration & Upgrades</h3>
                </div>
                <div class="category-content">
                    <p>Transition smoothly from legacy systems to modern, secure platforms:</p>
                    <ul>
                        <li>Legacy software migration</li>
                        <li>Operating system upgrades</li>
                        <li>Data migration services</li>
                        <li>Application compatibility testing</li>
                        <li>Training and documentation</li>
                    </ul>
                    <p>Our experts ensure minimal disruption during your transition.</p>
                    <button class="btn-software" onclick="scrollToCustomRequest('System Migration')">Discuss Migration Options</button>
                </div>
            </div>

            <!-- Custom Software Solutions -->
            <div class="software-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>Custom Software Solutions</h3>
                </div>
                <div class="category-content">
                    <p>Need specialized software? We provide:</p>
                    <ul>
                        <li>Software recommendations based on your needs</li>
                        <li>Custom configuration and setup</li>
                        <li>Integration with existing systems</li>
                        <li>Training for your team</li>
                        <li>Ongoing support and maintenance</li>
                    </ul>
                    <p>From accounting software to specialized industry applications, we'll find the right solution.</p>
                    <button class="btn-software" onclick="scrollToCustomRequest('Custom Software')">Get Software Advice</button>
                </div>
            </div>
        </div>

        <div class="software-cta">
            <div class="cta-content">
                <h3>Not Sure What You Need?</h3>
                <p>Our software specialists can assess your requirements and recommend the best solutions for your organization.</p>
                <button class="btn-cta" onclick="scrollToCustomRequest('Software Consultation')">Request Free Software Consultation</button>
            </div>
        </div>
    </section>

    <!-- Warranty Section -->
    <section class="warranty-section">
        <div class="warranty-content">
            <h2>Premium Warranty Protection</h2>
            <p>All NetComm products come with comprehensive warranty coverage and support options</p>
            
            <div class="warranty-features">
                <div class="warranty-feature">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Extended Coverage</h3>
                    <p>Up to 3 years of comprehensive hardware protection</p>
                </div>
                
                <div class="warranty-feature">
                    <i class="fas fa-tools"></i>
                    <h3>On-site Service</h3>
                    <p>Next business day on-site repairs for business customers</p>
                </div>
                
                <div class="warranty-feature">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock technical assistance from our experts</p>
                </div>
                
                <div class="warranty-feature">
                    <i class="fas fa-sync-alt"></i>
                    <h3>Accidental Damage</h3>
                    <p>Optional coverage for drops, spills, and other accidents</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
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

        // Show category function
        function showCategory(categoryId) {
            // Scroll to category
            document.getElementById(categoryId).scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Update active category
            document.querySelectorAll('.category-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }

        // Product details functions
        function showSpecs(productId, productName, specs) {
            let specsText = `Product Specifications for: ${productName}\n\n`;
            
            if (specs && Object.keys(specs).length > 0) {
                for (const [key, value] of Object.entries(specs)) {
                    specsText += `${key.charAt(0).toUpperCase() + key.slice(1)}: ${value}\n`;
                }
            } else {
                specsText += "Detailed specifications available upon request.";
            }
            
            alert(specsText);
        }
        
        function showWarranty(productName, warrantyPeriod = 12) {
            alert(`Warranty Information for: ${productName}\n\nStandard warranty: ${warrantyPeriod} months\nExtended options available\nOn-site service available\n24/7 technical support\n\nContact us for more details.`);
        }
        
        // Toggle accessories panel
        function toggleAccessories(button) {
            const panel = button.closest('.product-content').querySelector('.accessories-panel');
            if (panel) {
                panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
                button.textContent = panel.style.display === 'block' ? 'Hide Accessories' : 'Accessories';
            } else {
                alert('No accessories available for this product.');
            }
        }

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

        // Auto-hide messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => {
                        message.remove();
                    }, 300);
                }, 5000);
            });
        });

        // NEW: Check login status before adding to cart
        function addToCart(productId, quantity = 1) {
            fetch('products.php?action=check_login')
                .then(response => response.json())
                .then(data => {
                    if (data.logged_in) {
                        // Submit the form
                        document.getElementById(`add-to-cart-form-${productId}`).submit();
                    } else {
                        // Show login prompt
                        if (confirm('You need to log in to add items to your cart. Would you like to log in now?')) {
                            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking login status:', error);
                    alert('Unable to verify login status. Please try again.');
                });
        }

        // Function to scroll to custom request form and pre-fill the product type
        function scrollToCustomRequest(productType) {
            // Scroll to the custom request section
            document.getElementById('custom-request').scrollIntoView({ behavior: 'smooth' });
            
            // Set the product type in the form (after a short delay to allow for scrolling)
            setTimeout(function() {
                const productTypeField = document.getElementById('product_type');
                if (productTypeField) {
                    // Find the option that matches our product type
                    for (let i = 0; i < productTypeField.options.length; i++) {
                        if (productTypeField.options[i].text === productType) {
                            productTypeField.selectedIndex = i;
                            break;
                        }
                    }
                    
                    // If we didn't find an exact match, set to "Other" and specify in the specifications
                    if (productTypeField.value !== productType) {
                        productTypeField.value = "Other";
                        
                        // Add text to specifications field
                        const specsField = document.getElementById('specifications');
                        if (specsField) {
                            specsField.value = "Interested in: " + productType + "\n\n" + specsField.value;
                        }
                    }
                }
            }, 800);
        }
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