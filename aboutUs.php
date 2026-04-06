<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');   

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

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_firstname = $is_logged_in ? $_SESSION['user_firstname'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About NetComm - ICT Solutions Leader</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>

       
        :root {
            --primary-blue: #0066cc;
            --accent-blue: #00ccff;
            --dark-blue: #003366;
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
        .hero-about {
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1500&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white);
            padding: 120px 5% 80px;
        }
        
        .hero-about-content h1 {
            font-size: 4.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .hero-about-content p {
            font-size: 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Tagline Section */
        .tagline-section {
            padding: 80px 5%;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            text-align: center;
        }
        
        .tagline-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .tagline-main {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 30px;
        }
        
        .tagline-sub {
            font-size: 1.8rem;
            font-weight: 600;
            font-style: italic;
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
        
        /* Facilities Section */
        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .facility-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .facility-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 102, 204, 0.25);
        }
        
        .facility-image {
            width: 100%;
            height: 250px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        
        .facility-content {
            padding: 25px;
        }
        
        .facility-content h3 {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .facility-content p {
            color: var(--text-light);
            line-height: 1.6;
        }
        
        /* Core Values Section */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        
        .value-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
        }
        
        .value-card:hover {
            transform: translateY(-5px);
        }
        
        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
            color: var(--white);
        }
        
        .value-card h3 {
            font-size: 1.3rem;
            color: var(--dark-blue);
            margin-bottom: 15px;
        }
        
        .value-card p {
            color: var(--text-light);
        }
        
        /* History Section */
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background: linear-gradient(to bottom, var(--primary-blue), var(--accent-blue));
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }
        
        .timeline-item:nth-child(odd) {
            left: 0;
        }
        
        .timeline-item:nth-child(even) {
            left: 50%;
        }
        
        .timeline-content {
            padding: 20px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            position: relative;
        }
        
        .timeline-year {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .timeline-content h3 {
            font-size: 1.3rem;
            color: var(--dark-blue);
            margin-bottom: 10px;
        }
        
        /* Team Section */
        .team-section {
            background: var(--light-bg);
            padding: 80px 5%;
        }
        
        .team-content {
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }
        
        .team-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 40px 0;
        }
        
        .team-stat {
            text-align: center;
        }
        
        .team-stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 10px;
        }
        
        .team-stat-label {
            font-size: 1.1rem;
            color: var(--text-light);
        }
        
        /* Careers Section */
        .careers-section {
            padding: 80px 5%;
            text-align: center;
        }
        
        .careers-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .no-jobs-message {
            background: var(--white);
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-top: 40px;
        }
        
        .no-jobs-message i {
            font-size: 4rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
        }
        
        .no-jobs-message h3 {
            font-size: 1.8rem;
            color: var(--dark-blue);
            margin-bottom: 15px;
        }
        
        /* Partners Section */
        .partners-section {
            background: var(--light-bg);
            padding: 80px 5%;
        }
        
        .partners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        
        .partner-item {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 120px;
            transition: var(--transition);
        }
        
        .partner-item:hover {
            transform: translateY(-5px);
        }
        
        .partner-item img {
            max-width: 100%;
            max-height: 80px;
            object-fit: contain;
        }
        
        /* Purpose Section */
        .purpose-section {
            padding: 80px 5%;
            text-align: center;
        }
        
        .purpose-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }
        
        .purpose-card {
            background: var(--white);
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }
        
        .purpose-card h3 {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin-bottom: 20px;
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
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-about-content h1 {
                font-size: 3.5rem;
            }
            
            .timeline::after {
                left: 31px;
            }
            
            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }
            
            .timeline-item:nth-child(even) {
                left: 0;
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
            
            .hero-about-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-about-content p {
                font-size: 1.2rem;
            }
            
            .tagline-main {
                font-size: 2rem;
            }
            
            .tagline-sub {
                font-size: 1.5rem;
            }
            
            .section {
                padding: 60px 15px;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .team-stats {
                flex-direction: column;
                gap: 20px;
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
    <section class="hero-about">
        <div class="hero-about-content">
            <h1>OUR COMPANY</h1>
            <p>With 26 years of excellence and 49 years of collective experience, NetComm is Eswatini's leading ICT solutions provider.</p>
        </div>
    </section>

    <!-- Tagline Section -->
    <section class="tagline-section">
        <div class="tagline-container">
            <div class="tagline-main">Your Number 1 ICT Services Provider.</div>
            <div class="tagline-sub">You Want IT? We Got IT.</div>
        </div>
    </section>

    <!-- Facilities Section -->
    <section class="section" id="facilities">
        <div class="section-title">
            <h2>Our Facilities</h2>
            <p>Strategic locations to serve you better</p>
        </div>
        <div class="facilities-grid">
            <div class="facility-card">
                <div class="facility-image" style="background-image: url('https://images.unsplash.com/photo-1497366811353-6870744d04b2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1500&q=80');"></div>
                <div class="facility-content">
                    <h3>NetComm House, Matsapha</h3>
                    <p>Our main headquarters with corporate offices, technical support center, and showroom. This is where our team of experts develops cutting-edge ICT solutions.</p>
                </div>
            </div>
            <div class="facility-card">
                <div class="facility-image" style="background-image: url('https://images.unsplash.com/photo-1554118811-1e0d58224f24?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1500&q=80');"></div>
                <div class="facility-content">
                    <h3>NetComm Coffee Shop</h3>
                    <p>The perfect place to unwind, grab a cup of premium coffee, enjoy a delicious lunch, or take home freshly prepared meals.</p>
                </div>
            </div>
            <div class="facility-card">
                <div class="facility-image" style="background-image: url('https://images.unsplash.com/photo-1593640408182-31c70c8268f5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1500&q=80');"></div>
                <div class="facility-content">
                    <h3>iShop, Mbabane</h3>
                    <p>Our premium Apple experience store where you can explore the latest Apple products, get expert advice, and access authorized repair services.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values Section -->
    <section class="section" style="background-color: #f8f9fa;">
        <div class="section-title">
            <h2>Our Core Values</h2>
            <p>The principles that guide everything we do</p>
        </div>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <h3>Excellence</h3>
                <p>We strive for the highest quality in everything we do, delivering premium ICT solutions with unmatched quality.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3>Innovation</h3>
                <p>We embrace cutting-edge technology and creative solutions to help our clients stay ahead in the digital landscape.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Partnership</h3>
                <p>We build lasting relationships based on trust and reliability with our clients and partners.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Customer Focus</h3>
                <p>Our team is dedicated to understanding and exceeding our clients' expectations through personalized service.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Reliability</h3>
                <p>With 49 years of collective experience, we provide dependable solutions and support you can count on.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3>Growth</h3>
                <p>We continuously evolve our services and expertise to meet the changing needs of businesses across Eswatini.</p>
            </div>
        </div>
    </section>

    <!-- History Section -->
    <section class="section" id="history">
        <div class="section-title">
            <h2>Our Story</h2>
            <p>From Business Systems & Networks to NetComm - A Legacy of Innovation</p>
        </div>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">1976</div>
                    <h3>The Beginning</h3>
                    <p>Business Systems and Networks (BSN) started trading in Swaziland, laying the foundation for what would become a technology legacy.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">1999</div>
                    <h3>NetComm is Established</h3>
                    <p>BSN Technical Services t/a NetComm started trading on 2nd January 1999 as an independent entity, bringing together decades of expertise.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">2005</div>
                    <h3>Strategic Partnerships</h3>
                    <p>Became the first authorized partner for major brands like HP, Microsoft, and Cisco in Eswatini.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">2015</div>
                    <h3>Expansion & Growth</h3>
                    <p>Opened our Mbabane retail outlet specializing in Apple products and expanded our Matsapha headquarters.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">2025</div>
                    <h3>Market Leadership</h3>
                    <p>With 52 dedicated staff members and comprehensive ICT solutions, we solidified our position as Eswatini's premier technology provider.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="team-content">
            <div class="section-title">
                <h2>Our Team</h2>
                <p>52 dedicated professionals going above and beyond for our clients</p>
            </div>
            <p>At NetComm, our greatest asset is our people. With 52 dedicated staff members, we are committed to delivering exceptional service and creating opportunities for Emaswati. Our team brings together diverse expertise in technology, customer service, and business solutions to help our clients thrive in the digital age.</p>
            
            <div class="team-stats">
                <div class="team-stat">
                    <div class="team-stat-number">52</div>
                    <div class="team-stat-label">Dedicated Staff</div>
                </div>
                <div class="team-stat">
                    <div class="team-stat-number">49</div>
                    <div class="team-stat-label">Years of Collective Experience</div>
                </div>
                <div class="team-stat">
                    <div class="team-stat-number">18</div>
                    <div class="team-stat-label">Service Vehicles</div>
                </div>
            </div>
            
            <p>We believe in investing in our people and our community. Through training programs, career development opportunities, and community initiatives, we're proud to contribute to the growth and development of Eswatini's technology sector.</p>
        </div>
    </section>

    <!-- Careers Section -->
    <section class="careers-section">
        <div class="careers-content">
            <div class="section-title">
                <h2>Careers at NetComm</h2>
                <p>Join our team of technology innovators</p>
            </div>
            <p>At NetComm, we're always looking for talented individuals who are passionate about technology and customer service. While we currently don't have any open positions, we encourage you to check back regularly as we're growing and new opportunities arise frequently.</p>
            
            <div class="no-jobs-message">
                <i class="fas fa-briefcase"></i>
                <h3>No Current Openings</h3>
                <p>We currently don't have any job openings, but please check back later as we're always growing and may have opportunities that match your skills.</p>
            </div>
        </div>
    </section>

    <!-- Partners Section -->
    <section class="partners-section">
        <div class="section-title">
            <h2>Our Partners</h2>
            <p>Collaborating with industry leaders to bring you the best solutions</p>
        </div>
        <p>NetComm has established strategic partnerships with the world's leading technology brands. These partnerships allow us to offer our clients cutting-edge solutions, authorized support, and access to the latest innovations in the ICT industry.</p>
        
        <div class="partners-grid">
            <div class="partner-item">
                <img src="images/micro.png" alt="Microsoft">
            </div>
            <div class="partner-item">
                <img src="images/hp.png" alt="HP">
            </div>
            <div class="partner-item">
                <img src="images/cisco.png" alt="Cisco">
            </div>
            <div class="partner-item">
                <img src="images/apple.png" alt="Apple">
            </div>
            <div class="partner-item">
                <img src="images/dell.png" alt="Dell">
            </div>
            <div class="partner-item">
                <img src="images/acer.png" alt="Acer">
            </div>
            <div class="partner-item">
                <img src="images/sonic.png" alt="SonicWall">
            </div>
            <div class="partner-item">
                <img src="images/ubiquity.png" alt="Ubiquiti">
            </div>
        </div>
    </section>

    <!-- Purpose & Vision Section -->
    <section class="purpose-section">
        <div class="section-title">
            <h2>Our Purpose & Vision</h2>
            <p>Driving digital transformation in Eswatini</p>
        </div>
        
        <div class="purpose-grid">
            <div class="purpose-card">
                <h3>Our Purpose</h3>
                <p>To empower businesses and individuals in Eswatini with cutting-edge ICT solutions that drive growth, innovation, and digital transformation. We are committed to delivering exceptional value to our clients while creating opportunities for Emaswati in the technology sector.</p>
            </div>
            <div class="purpose-card">
                <h3>Our Vision</h3>
                <p>To be the leading provider of comprehensive ICT solutions in Eswatini, recognized for our innovation, reliability, and commitment to customer success. We aim to be the partner of choice for businesses seeking to leverage technology for growth and competitive advantage.</p>
            </div>
            <div class="purpose-card">
                <h3>Our Mission</h3>
                <p>To deliver world-class technology solutions and services that meet the evolving needs of our clients. Through our expertise, partnerships, and commitment to excellence, we strive to help businesses across Eswatini achieve their goals and thrive in the digital economy.</p>
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