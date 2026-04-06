<?php
session_start();

// Database Configuration
class Database {
    private $host = "localhost";
    private $db_name = "netcomm";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();



// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_firstname = $is_logged_in ? $_SESSION['user_firstname'] : '';


// Get current session ID
$session_id = session_id();

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $quantity = (int)$quantity;
            
            if ($quantity > 0) {
                // Update or insert into cart
                $stmt = $conn->prepare("REPLACE INTO cart (session_id, product_id, quantity) 
                                       VALUES (:session_id, :product_id, :quantity)");
                $stmt->bindParam(':session_id', $session_id);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->execute();
                
                // Update session
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                // Remove from cart
                $stmt = $conn->prepare("DELETE FROM cart 
                                       WHERE session_id = :session_id 
                                       AND product_id = :product_id");
                $stmt->bindParam(':session_id', $session_id);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                
                unset($_SESSION['cart'][$product_id]);
            }
        }
        $_SESSION['cart_message'] = "Cart updated successfully!";
        header("Location: cart.php");
        exit;
    }
    
    if (isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        
        // Remove from database
        $stmt = $conn->prepare("DELETE FROM cart 
                               WHERE session_id = :session_id 
                               AND product_id = :product_id");
        $stmt->bindParam(':session_id', $session_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $_SESSION['cart_message'] = "Item removed from cart!";
        }
        header("Location: cart.php");
        exit;
    }
    
    if (isset($_POST['clear_cart'])) {
        // Clear database cart
        $stmt = $conn->prepare("DELETE FROM cart 
                               WHERE session_id = :session_id");
        $stmt->bindParam(':session_id', $session_id);
        $stmt->execute();
        
        unset($_SESSION['cart']);
        $_SESSION['cart_message'] = "Cart cleared successfully!";
        header("Location: cart.php");
        exit;
    }
    
    if (isset($_POST['checkout'])) {
        header("Location: checkout.php");
        exit;
    }
}

// Load cart from database
$stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.product_name, p.product_price, p.product_image 
                       FROM cart c
                       JOIN products p ON c.product_id = p.product_id
                       WHERE c.session_id = :session_id");
$stmt->bindParam(':session_id', $session_id);
$stmt->execute();
$db_cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update session cart with database data
$_SESSION['cart'] = [];
foreach ($db_cart_items as $item) {
    $_SESSION['cart'][$item['product_id']] = $item['quantity'];
}

// Get cart items with product details
$cart_items = [];
$subtotal = 0;
$shipping = 0;
$tax_rate = 0.14; // 14% VAT
$tax = 0;
$total = 0;

if (!empty($db_cart_items)) {
    foreach ($db_cart_items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $price = $item['product_price'];
        $item_total = $price * $quantity;
        
        $cart_items[] = [
            'product_id' => $product_id,
            'name' => $item['product_name'],
            'price' => $price,
            'quantity' => $quantity,
            'total' => $item_total,
            'image' => $item['product_image']
        ];
        
        $subtotal += $item_total;
    }
    
    // Calculate shipping (free for orders over E5000, otherwise E250)
    $shipping = $subtotal > 5000 ? 0 : 250;
    
    // Calculate tax
    $tax = $subtotal * $tax_rate;
    
    // Calculate total
    $total = $subtotal + $tax + $shipping;
}

// Get cart total quantity
$cart_total_quantity = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_total_quantity += $qty;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - NetComm ICT Solutions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reuse the same CSS variables from products page */
        :root {
            --primary-blue: #0066cc;
            --secondary-blue: #0099ff;
            --accent-blue: #00ccff;
            --dark-blue: #003366;
            --light-bg: #f8faff;
            --white: #ffffff;
            --text-dark: #333333;
            --text-light: #666666;
            --shadow: 0 8px 30px rgba(0, 102, 204, 0.15);
            --transition: all 0.4s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--white);
            color: var(--text-dark);
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        /* Navbar Styles */
        .navbar {
            background-color: var(--white);
            box-shadow: 0 4px 20px rgba(0, 102, 204, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }
        
        .navbar.scrolled {
            padding: 10px 5%;
            box-shadow: 0 5px 25px rgba(0, 102, 204, 0.2);
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--dark-blue);
        }
        
        .logo span {
            color: var(--primary-blue);
        }
        
        .logo img {
            height: 45px;
            margin-right: 10px;
            transition: var(--transition);
        }
        
        .navbar.scrolled .logo img {
            height: 40px;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 16px;
            position: relative;
            padding: 5px 0;
            transition: var(--transition);
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
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
        
        .nav-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 15px;
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.3);
        }
        
        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 102, 204, 0.4);
        }
        
        /* Cart Hero */
        .cart-hero {
            min-height: 30vh;
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            padding: 150px 5% 60px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .cart-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(0, 204, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 80% 70%, rgba(0, 102, 204, 0.1) 0%, transparent 20%);
        }
        
        .cart-hero-content {
            max-width: 800px;
            margin: 0 auto;
            z-index: 2;
            color: var(--white);
        }
        
        .cart-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .breadcrumb {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
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
        
        /* Cart Section */
        .cart-section {
            padding: 80px 5%;
            position: relative;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: var(--dark-blue);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-blue), var(--accent-blue));
            border-radius: 2px;
        }
        
        /* Cart Content */
        .cart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .cart-items {
            flex: 1;
            min-width: 300px;
        }
        
        .cart-summary {
            width: 350px;
            background: var(--light-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            align-self: flex-start;
            position: sticky;
            top: 100px;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .cart-table th {
            text-align: left;
            padding: 15px 10px;
            border-bottom: 2px solid var(--primary-blue);
            color: var(--dark-blue);
            font-weight: 600;
        }
        
        .cart-table td {
            padding: 20px 10px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
        }
        
        .cart-item {
            display: flex;
            align-items: center;
        }
        
        .cart-item-image {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--light-bg), #e6f0ff);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
        }
        
        .cart-item-image img {
            max-width: 80%;
            max-height: 80%;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }
        
        .cart-quantity {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--light-bg);
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        
        .quantity-btn:hover {
            background: var(--primary-blue);
            color: var(--white);
        }
        
        .quantity-input {
            width: 50px;
            height: 35px;
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 5px;
            text-align: center;
            margin: 0 10px;
            font-size: 1rem;
        }
        
        .remove-btn {
            background: none;
            border: none;
            color: #ff6b6b;
            cursor: pointer;
            font-size: 1.2rem;
            transition: var(--transition);
        }
        
        .remove-btn:hover {
            color: #ff0000;
            transform: scale(1.1);
        }
        
        .cart-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .cart-action-btn {
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 15px;
            border: 2px solid var(--primary-blue);
            background: transparent;
            color: var(--primary-blue);
        }
        
        .cart-action-btn:hover {
            transform: translateY(-3px);
        }
        
        .cart-action-btn.primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            border: none;
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.3);
        }
        
        .cart-action-btn.primary:hover {
            box-shadow: 0 6px 20px rgba(0, 102, 204, 0.4);
        }
        
        /* Summary Styles */
        .summary-title {
            font-size: 1.5rem;
            color: var(--dark-blue);
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0, 102, 204, 0.1);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
        }
        
        .summary-label {
            color: var(--text-light);
        }
        
        .summary-value {
            font-weight: 600;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            margin: 25px 0;
            padding: 15px 0;
            border-top: 2px solid rgba(0, 102, 204, 0.2);
            border-bottom: 2px solid rgba(0, 102, 204, 0.2);
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-blue);
        }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
            border: none;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.3);
        }
        
        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 102, 204, 0.4);
        }
        
        .checkout-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px;
            background: var(--light-bg);
            border-radius: 15px;
            margin: 0 auto;
            max-width: 600px;
        }
        
        .empty-cart i {
            font-size: 5rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-cart h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: var(--dark-blue);
        }
        
        .empty-cart p {
            margin-bottom: 30px;
            color: var(--text-light);
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
        
        /* Footer */
        .footer {
            background: var(--dark-blue);
            color: var(--white);
            padding: 80px 5% 40px;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(to right, var(--primary-blue), var(--accent-blue));
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-col h3 {
            font-size: 1.5rem;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--accent-blue);
            border-radius: 2px;
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
        
        /* Cart Icon */
        .cart-section {
            position: relative;
        }
        
        .cart-link {
            display: flex;
            align-items: center;
            color: var(--text-dark);
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
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .cart-hero h1 {
                font-size: 3rem;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px;
            }
            
            .nav-links {
                margin: 20px 0;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .cart-hero {
                padding: 130px 5% 40px;
            }
            
            .cart-hero h1 {
                font-size: 2.5rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .cart-container {
                flex-direction: column;
            }
            
            .cart-summary {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .nav-links {
                gap: 15px;
            }
            
            .cart-hero h1 {
                font-size: 2rem;
            }
            
            .cart-table th {
                display: none;
            }
            
            .cart-table td {
                display: block;
                padding: 10px 5px;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-item-image {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .cart-actions {
                flex-direction: column;
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

    <!-- Messages -->
    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="message success">
            <?= $_SESSION['cart_message'] ?>
        </div>
        <?php unset($_SESSION['cart_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?= $_SESSION['error_message'] ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Cart Hero Section -->
    <section class="cart-hero">
        <div class="cart-hero-content">
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <a href="products.php">Products</a>
                <span>/</span>
                <span>Shopping Cart</span>
            </div>
            <h1>Your Shopping Cart</h1>
        </div>
    </section>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="section-title">
            <h2>Review Your Order</h2>
            <p>Review and manage items in your shopping cart</p>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your Cart is Empty</h3>
                <p>Looks like you haven't added any products to your cart yet.</p>
                <a href="products.php" class="cart-action-btn primary">Browse Products</a>
            </div>
        <?php else: ?>
            <form method="post" action="cart.php">
                <div class="cart-container">
                    <div class="cart-items">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="cart-item">
                                                <div class="cart-item-image">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                                    <?php else: ?>
                                                        <i class="fas fa-image"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="cart-item-details">
                                                    <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                                    <div class="cart-item-price">E<?= number_format($item['price'], 2) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="cart-item-price">E<?= number_format($item['price'], 2) ?></td>
                                        <td>
                                            <div class="cart-quantity">
                                                <button type="button" class="quantity-btn minus" data-id="<?= $item['product_id'] ?>">-</button>
                                                <input type="number" name="quantity[<?= $item['product_id'] ?>]" 
                                                    value="<?= $item['quantity'] ?>" min="1" class="quantity-input" 
                                                    data-id="<?= $item['product_id'] ?>">
                                                <button type="button" class="quantity-btn plus" data-id="<?= $item['product_id'] ?>">+</button>
                                            </div>
                                        </td>
                                        <td class="cart-item-price">E<?= number_format($item['total'], 2) ?></td>
                                        <td>
                                            <button type="submit" name="remove_item" class="remove-btn" title="Remove item">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="cart-actions">
                            <button type="submit" name="update_cart" class="cart-action-btn">
                                <i class="fas fa-sync-alt"></i> Update Cart
                            </button>
                            <button type="submit" name="clear_cart" class="cart-action-btn">
                                <i class="fas fa-trash-alt"></i> Clear Cart
                            </button>
                            <a href="products.php" class="cart-action-btn">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                    
                    <div class="cart-summary">
                        <h3 class="summary-title">Order Summary</h3>
                        
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value">E<?= number_format($subtotal, 2) ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Shipping</span>
                            <span class="summary-value">E<?= number_format($shipping, 2) ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Tax (14% VAT)</span>
                            <span class="summary-value">E<?= number_format($tax, 2) ?></span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Total</span>
                            <span>E<?= number_format($total, 2) ?></span>
                        </div>
                        
                        <button type="submit" name="checkout" class="checkout-btn">
                            Proceed to Checkout <i class="fas fa-arrow-right"></i>
                        </button>
                        
                        <div style="margin-top: 20px; text-align: center; font-size: 0.9rem; color: var(--text-light);">
                            <p><i class="fas fa-shield-alt"></i> Secure Checkout</p>
                            <p>All transactions are encrypted and secure</p>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>About NetComm</h3>
                <p>With 24 years of independent operation and 47 years of collective experience, NetComm is Eswatini's leading provider of comprehensive ICT solutions.</p>
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
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Quantity buttons functionality
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
                let value = parseInt(input.value);
                
                if (this.classList.contains('minus')) {
                    if (value > 1) {
                        input.value = value - 1;
                    }
                } else if (this.classList.contains('plus')) {
                    input.value = value + 1;
                }
                
                // Trigger form update
                document.querySelector('button[name="update_cart"]').click();
            });
        });

        // Auto update when quantity changes
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelector('button[name="update_cart"]').click();
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
</body>
</html>