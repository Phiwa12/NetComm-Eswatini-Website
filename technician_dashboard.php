<?php
session_start();

require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Check if connection was successful
if (!$pdo) {
    die("Database connection failed. Please check your database configuration.");
}

// Check if user is logged in and is admin/technician
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'technician')) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$user_firstname = $_SESSION['user_firstname'];

// Get statistics for dashboard
try {
    // Service request stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM custom_service_requests
    ");
    $stmt->execute();
    $service_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Product request stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM custom_requests");
    $stmt->execute();
    $product_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Technician stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM technicians WHERE is_active = 1");
    $stmt->execute();
    $technician_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get service requests
    $stmt = $pdo->prepare("
        SELECT csr.*, u.firstname, u.lastname, u.email, u.phone,
               sa.status as assignment_status, sa.assigned_at, sa.completed_at,
               t.user_id as technician_id, u2.firstname as tech_firstname, u2.lastname as tech_lastname
        FROM custom_service_requests csr
        LEFT JOIN users u ON csr.user_id = u.id
        LEFT JOIN service_assignments sa ON csr.id = sa.service_request_id
        LEFT JOIN technicians t ON sa.technician_id = t.id
        LEFT JOIN users u2 ON t.user_id = u2.id
        ORDER BY csr.created_at DESC
    ");
    $stmt->execute();
    $service_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get product requests
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               pa.status as assignment_status, pa.assigned_at, pa.completed_at,
               u.firstname as assigned_firstname, u.lastname as assigned_lastname
        FROM custom_requests cr
        LEFT JOIN product_assignments pa ON cr.id = pa.product_request_id
        LEFT JOIN users u ON pa.assigned_to = u.id
        ORDER BY cr.submitted_at DESC
    ");
    $stmt->execute();
    $product_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get technicians
    $stmt = $pdo->prepare("
        SELECT t.*, u.firstname, u.lastname, u.email, u.phone,
               COUNT(sa.id) as active_assignments
        FROM technicians t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN service_assignments sa ON t.id = sa.technician_id AND sa.status IN ('assigned', 'in_progress')
        WHERE t.is_active = 1
        GROUP BY t.id
    ");
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $service_requests = [];
    $product_requests = [];
    $technicians = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_service'])) {
        $service_request_id = $_POST['service_request_id'];
        $technician_id = $_POST['technician_id'];
        $notes = $_POST['notes'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO service_assignments (service_request_id, technician_id, assigned_by, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$service_request_id, $technician_id, $user_id, $notes]);
            
            // Update service request status
            $stmt = $pdo->prepare("
                UPDATE custom_service_requests SET status = 'in_progress' WHERE id = ?
            ");
            $stmt->execute([$service_request_id]);
            
            // Add to status history
            $stmt = $pdo->prepare("
                INSERT INTO status_history (assignment_id, assignment_type, status, changed_by, notes)
                VALUES (?, 'service', 'assigned', ?, ?)
            ");
            $stmt->execute([$pdo->lastInsertId(), $user_id, "Assigned to technician"]);
            
            header("Location: technician_dashboard.php?message=Service assigned successfully");
            exit();
        } catch (PDOException $e) {
            error_log("Error assigning service: " . $e->getMessage());
            $error = "Failed to assign service. Please try again.";
        }
    }
    
    if (isset($_POST['update_status'])) {
        $assignment_id = $_POST['assignment_id'];
        $assignment_type = $_POST['assignment_type'];
        $status = $_POST['status'];
        $notes = $_POST['notes'];
        
        try {
            if ($assignment_type === 'service') {
                $stmt = $pdo->prepare("
                    UPDATE service_assignments 
                    SET status = ?, notes = CONCAT(IFNULL(notes, ''), '\n', ?)
                    WHERE id = ?
                ");
                $stmt->execute([$status, $notes, $assignment_id]);
                
                if ($status === 'completed') {
                    $stmt = $pdo->prepare("
                        UPDATE service_assignments SET completed_at = NOW() WHERE id = ?
                    ");
                    $stmt->execute([$assignment_id]);
                    
                    $stmt = $pdo->prepare("
                        UPDATE custom_service_requests SET status = 'resolved' 
                        WHERE id = (SELECT service_request_id FROM service_assignments WHERE id = ?)
                    ");
                    $stmt->execute([$assignment_id]);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE product_assignments 
                    SET status = ?, notes = CONCAT(IFNULL(notes, ''), '\n', ?)
                    WHERE id = ?
                ");
                $stmt->execute([$status, $notes, $assignment_id]);
                
                if ($status === 'completed') {
                    $stmt = $pdo->prepare("
                        UPDATE product_assignments SET completed_at = NOW() WHERE id = ?
                    ");
                    $stmt->execute([$assignment_id]);
                }
            }
            
            // Add to status history
            $stmt = $pdo->prepare("
                INSERT INTO status_history (assignment_id, assignment_type, status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$assignment_id, $assignment_type, $status, $user_id, $notes]);
            
            header("Location: technician_dashboard.php?message=Status updated successfully");
            exit();
        } catch (PDOException $e) {
            error_log("Error updating status: " . $e->getMessage());
            $error = "Failed to update status. Please try again.";
        }
    }
    
    if (isset($_POST['assign_product'])) {
        $product_request_id = $_POST['product_request_id'];
        $assigned_to = $_POST['assigned_to'];
        $notes = $_POST['notes'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO product_assignments (product_request_id, assigned_to, assigned_by, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$product_request_id, $assigned_to, $user_id, $notes]);
            
            // Add to status history
            $stmt = $pdo->prepare("
                INSERT INTO status_history (assignment_id, assignment_type, status, changed_by, notes)
                VALUES (?, 'product', 'assigned', ?, ?)
            ");
            $stmt->execute([$pdo->lastInsertId(), $user_id, "Assigned to sales person"]);
            
            header("Location: technician_dashboard.php?message=Product request assigned successfully");
            exit();
        } catch (PDOException $e) {
            error_log("Error assigning product request: " . $e->getMessage());
            $error = "Failed to assign product request. Please try again.";
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetComm - Technician Dashboard</title>
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
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
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
            background-color: #f5f7fa;
            overflow-x: hidden;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: var(--dark-blue);
            color: var(--white);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 100;
        }
        
        .logo-container {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo img {
            height: 40px;
        }
        
        .logo-text {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .menu-item {
            margin-bottom: 5px;
        }
        
        .menu-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        
        .menu-link:hover, .menu-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            border-left-color: var(--primary-blue);
        }
        
        .menu-link i {
            margin-right: 12px;
            font-size: 18px;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--dark-blue);
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
        }
        
        /* Dashboard Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(12, 70, 247, 0.2);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .bg-primary {
            background: rgba(12, 70, 247, 0.1);
            color: var(--primary-blue);
        }
        
        .bg-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .bg-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .bg-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: var(--white);
            padding: 10px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .tab-btn {
            padding: 10px 20px;
            border-radius: 8px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            color: var(--text-light);
        }
        
        .tab-btn.active {
            background: var(--primary-blue);
            color: var(--white);
        }
        
        /* Table Styles */
        .card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.2rem;
            color: var(--dark-blue);
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: #f8fafc;
            color: var(--text-light);
            font-weight: 600;
        }
        
        .table tr:hover {
            background-color: #f1f5f9;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .status-in-progress {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
        }
        
        .status-resolved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .status-delayed {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .status-assigned {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            transition: var(--transition);
            margin-right: 5px;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            color: var(--white);
        }
        
        .btn-success {
            background: var(--success);
            color: var(--white);
        }
        
        .btn-warning {
            background: var(--warning);
            color: var(--white);
        }
        
        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-blue);
            color: var(--primary-blue);
        }
        
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--white);
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.2rem;
            color: var(--dark-blue);
            font-weight: 600;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-blue);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(12, 70, 247, 0.1);
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .logo-text, .menu-text {
                display: none;
            }
            
            .menu-link i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .table th, .table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo-container">
                <div class="logo">
                    <img src="images/NetcommLogo.jpg" alt="NetComm Logo">
                    <span class="logo-text">NetComm</span>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="#" class="menu-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <i class="fas fa-tools"></i>
                        <span class="menu-text">Service Requests</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <i class="fas fa-laptop"></i>
                        <span class="menu-text">Product Requests</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <i class="fas fa-users"></i>
                        <span class="menu-text">Technicians</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="menu-text">Reports</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="index.php" class="menu-link">
                        <i class="fas fa-home"></i>
                        <span class="menu-text">Back to Home</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="logout.php" class="menu-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="menu-text">Logout</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Technician Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user_firstname, 0, 1)); ?></div>
                    <div>
                        <div><?php echo htmlspecialchars($user_firstname); ?></div>
                        <small><?php echo ucfirst($user_type); ?></small>
                    </div>
                </div>
            </div>
            
            <!-- Display Messages -->
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-value"><?php echo $service_stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Service Requests</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $service_stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $service_stats['resolved'] ?? 0; ?></div>
                    <div class="stat-label">Completed Requests</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-danger">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $service_stats['cancelled'] ?? 0; ?></div>
                    <div class="stat-label">Cancelled Requests</div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" data-tab="service-requests">Service Requests</button>
                <button class="tab-btn" data-tab="product-requests">Product Requests</button>
                <button class="tab-btn" data-tab="technicians">Technicians</button>
            </div>
            
            <!-- Service Requests Table -->
            <div class="card" id="service-requests-tab">
                <div class="card-header">
                    <h2 class="card-title">Service Requests</h2>
                    <button class="action-btn btn-primary" id="add-service-btn">
                        <i class="fas fa-plus"></i> New Request
                    </button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Description</th>
                                <th>Urgency</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_requests as $request): ?>
                            <tr>
                                <td>#SR-<?php echo $request['id']; ?></td>
                                <td><?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?></td>
                                <td><?php echo htmlspecialchars(substr($request['problem_description'], 0, 50)) . '...'; ?></td>
                                <td><?php echo ucfirst($request['urgency']); ?></td>
                                <td>
                                    <?php 
                                    $status = $request['assignment_status'] ?? $request['status'];
                                    $status_class = 'status-' . str_replace('_', '-', $status);
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($request['tech_firstname'])): ?>
                                        <?php echo htmlspecialchars($request['tech_firstname'] . ' ' . $request['tech_lastname']); ?>
                                    <?php else: ?>
                                        Not Assigned
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn btn-outline view-details" data-id="<?php echo $request['id']; ?>">View</button>
                                    <?php if (empty($request['assignment_status'])): ?>
                                        <button class="action-btn btn-primary assign-service" data-id="<?php echo $request['id']; ?>">Assign</button>
                                    <?php else: ?>
                                        <button class="action-btn btn-warning update-status" data-id="<?php echo $request['id']; ?>" data-type="service">Update</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Product Requests Table  -->
            <div class="card" id="product-requests-tab" style="display: none;">
                <div class="card-header">
                    <h2 class="card-title">Product Requests</h2>
                    <button class="action-btn btn-primary" id="add-product-btn">
                        <i class="fas fa-plus"></i> New Request
                    </button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Product Type</th>
                                <th>Specifications</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product_requests as $request): ?>
                            <tr>
                                <td>#PR-<?php echo $request['id']; ?></td>
                                <td><?php echo htmlspecialchars($request['name']); ?></td>
                                <td><?php echo htmlspecialchars($request['product_type']); ?></td>
                                <td><?php echo htmlspecialchars(substr($request['specifications'], 0, 30)) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($request['budget']); ?></td>
                                <td>
                                    <?php 
                                    $status = $request['assignment_status'] ?? 'pending';
                                    $status_class = 'status-' . str_replace('_', '-', $status);
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($request['assigned_firstname'])): ?>
                                        <?php echo htmlspecialchars($request['assigned_firstname'] . ' ' . $request['assigned_lastname']); ?>
                                    <?php else: ?>
                                        Not Assigned
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($request['submitted_at'])); ?></td>
                                <td>
                                    <button class="action-btn btn-outline view-product-details" data-id="<?php echo $request['id']; ?>">View</button>
                                    <?php if (empty($request['assignment_status'])): ?>
                                        <button class="action-btn btn-primary assign-product" data-id="<?php echo $request['id']; ?>">Assign</button>
                                    <?php else: ?>
                                        <button class="action-btn btn-warning update-status" data-id="<?php echo $request['id']; ?>" data-type="product">Update</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Technicians Table  -->
            <div class="card" id="technicians-tab" style="display: none;">
                <div class="card-header">
                    <h2 class="card-title">Technicians</h2>
                    <button class="action-btn btn-primary" id="add-technician-btn">
                        <i class="fas fa-plus"></i> Add Technician
                    </button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>Active Assignments</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($technicians as $tech): ?>
                            <tr>
                                <td>#T-<?php echo $tech['id']; ?></td>
                                <td><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($tech['email']); ?></td>
                                <td><?php echo htmlspecialchars($tech['specialization']); ?></td>
                                <td><?php echo $tech['active_assignments']; ?></td>
                                <td>
                                    <span class="status-badge status-resolved">
                                        <?php echo $tech['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn btn-outline">View</button>
                                    <button class="action-btn btn-primary assign-task" data-id="<?php echo $tech['id']; ?>">Assign Task</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Assign Service Request Modal -->
    <div class="modal" id="assign-service-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Assign Service Request</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="service_request_id" id="service_request_id">
                    <div class="form-group">
                        <label class="form-label">Technician</label>
                        <select class="form-control" name="technician_id" required>
                            <option value="">Select Technician</option>
                            <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>">
                                <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Add any special instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="action-btn btn-outline" id="close-service-modal">Cancel</button>
                    <button type="submit" class="action-btn btn-primary" name="assign_service">Assign</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Assign Product Request Modal -->
    <div class="modal" id="assign-product-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Assign Product Request</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="product_request_id" id="product_request_id">
                    <div class="form-group">
                        <label class="form-label">Assign To</label>
                        <select class="form-control" name="assigned_to" required>
                            <option value="">Select Sales Person</option>
                            <?php 
                            // Get sales team members
                            $stmt = $pdo->prepare("SELECT id, firstname, lastname FROM users WHERE user_type = 'admin' OR user_type = 'technician'");
                            $stmt->execute();
                            $sales_team = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($sales_team as $person): 
                            ?>
                            <option value="<?php echo $person['id']; ?>">
                                <?php echo htmlspecialchars($person['firstname'] . ' ' . $person['lastname']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Add any special instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="action-btn btn-outline" id="close-product-modal">Cancel</button>
                    <button type="submit" class="action-btn btn-primary" name="assign_product">Assign</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal" id="update-status-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Update Status</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" id="assignment_id">
                    <input type="hidden" name="assignment_type" id="assignment_type">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="delayed">Delayed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Add status update notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="action-btn btn-outline" id="close-status-modal">Cancel</button>
                    <button type="submit" class="action-btn btn-primary" name="update_status">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabs = {
                'service-requests': document.getElementById('service-requests-tab'),
                'product-requests': document.getElementById('product-requests-tab'),
                'technicians': document.getElementById('technicians-tab')
            };
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    
                    // Update active tab button
                    tabBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show selected tab, hide others
                    Object.keys(tabs).forEach(key => {
                        if (key === tabName) {
                            tabs[key].style.display = 'block';
                        } else {
                            tabs[key].style.display = 'none';
                        }
                    });
                });
            });
            
            // Modal functionality
            const assignServiceModal = document.getElementById('assign-service-modal');
            const assignProductModal = document.getElementById('assign-product-modal');
            const updateStatusModal = document.getElementById('update-status-modal');
            const closeBtns = document.querySelectorAll('.close-btn');
            const cancelBtns = document.querySelectorAll('[id$="-modal"]');
            
            // Service assignment
            const assignServiceBtns = document.querySelectorAll('.assign-service');
            assignServiceBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-id');
                    document.getElementById('service_request_id').value = requestId;
                    assignServiceModal.style.display = 'flex';
                });
            });
            
            // Product assignment
            const assignProductBtns = document.querySelectorAll('.assign-product');
            assignProductBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-id');
                    document.getElementById('product_request_id').value = requestId;
                    assignProductModal.style.display = 'flex';
                });
            });
            
            // Status update
            const updateStatusBtns = document.querySelectorAll('.update-status');
            updateStatusBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-id');
                    const requestType = this.getAttribute('data-type');
                    document.getElementById('assignment_id').value = requestId;
                    document.getElementById('assignment_type').value = requestType;
                    updateStatusModal.style.display = 'flex';
                });
            });
            
            // Close modals
            function closeModals() {
                assignServiceModal.style.display = 'none';
                assignProductModal.style.display = 'none';
                updateStatusModal.style.display = 'none';
            }
            
            closeBtns.forEach(btn => {
                btn.addEventListener('click', closeModals);
            });
            
            cancelBtns.forEach(btn => {
                btn.addEventListener('click', closeModals);
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === assignServiceModal || 
                    event.target === assignProductModal || 
                    event.target === updateStatusModal) {
                    closeModals();
                }
            });
            
            // Simulate counting animation for stats
            const statValues = document.querySelectorAll('.stat-value');
            const targetValues = [
                <?php echo $service_stats['total'] ?? 0; ?>,
                <?php echo $service_stats['pending'] ?? 0; ?>,
                <?php echo $service_stats['resolved'] ?? 0; ?>,
                <?php echo $service_stats['cancelled'] ?? 0; ?>
            ];
            const durations = [2000, 1500, 1800, 1200];
            
            statValues.forEach((element, index) => {
                animateCounter(element, targetValues[index], durations[index]);
            });
            
            function animateCounter(element, targetValue, duration) {
                let startTime = null;
                const startValue = 0;
                
                function updateCounter(timestamp) {
                    if (!startTime) startTime = timestamp;
                    const progress = Math.min((timestamp - startTime) / duration, 1);
                    
                    const currentValue = Math.floor(progress * targetValue);
                    element.textContent = currentValue;
                    
                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    } else {
                        element.textContent = targetValue;
                    }
                }
                
                requestAnimationFrame(updateCounter);
            }
        });
    </script>
</body>
</html>