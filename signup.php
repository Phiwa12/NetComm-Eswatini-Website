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
    <title>Sign Up - NetComm ICT Solutions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/signup.css" rel="stylesheet">
    <style>
        /* Loading spinner styles */
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn-signup:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
            display: none;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .form-group.has-error .form-control {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        /* Password toggle styling */
.password-field {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
    transition: color 0.3s ease;
    z-index: 10;
}

.password-toggle:hover {
    color: #333;
}

.password-field .form-control {
    padding-right: 45px; /* Make room for the toggle icon */
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
            <a href="services.php">Services</a>
            <a href="products.php">Products</a>
            <a href="aboutUs.php">About</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="nav-btn">
            <a href="index.php">Home</a>
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

    <!-- Signup Section -->
<section class="signup-container">
    <div class="signup-graphic"></div>
    <div class="signup-content">
        <div class="signup-info">
            <h1>Join NetComm Today</h1>
            <p>Create your account to access premium ICT solutions, exclusive offers, and personalized support.</p>
            
            <div class="benefits">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="benefit-text">
                        <h3>Premium Solutions</h3>
                        <p>Access cutting-edge technology tailored to your business</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="benefit-text">
                        <h3>24/7 Expert Support</h3>
                        <p>Our dedicated team is always ready to assist you</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="benefit-text">
                        <h3>Exclusive Offers</h3>
                        <p>Special discounts and early access to new products</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="signup-form">
            <h2>Create Account</h2>
            <p>Fill in your details to get started</p>
            
            <!-- Alert messages will be displayed here -->
            <div id="alertMessage" class="alert"></div>
            
            <form id="signupForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="firstName" name="firstName" class="form-control" placeholder="John" required>
                        </div>
                        <div class="error-message" id="firstNameError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="lastName" name="lastName" class="form-control" placeholder="Doe" required>
                        </div>
                        <div class="error-message" id="lastNameError"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="john.doe@example.com" required>
                    </div>
                    <div class="error-message" id="emailError"></div>
                </div>
                
                <div class="form-group">
                    <label for="company">Company (Optional)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-building"></i>
                        <input type="text" id="company" name="company" class="form-control" placeholder="Company Name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon password-field">
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                            <i class="fas fa-eye password-toggle" data-target="password"></i>
                        </div>
                        <div class="error-message" id="passwordError"></div>
                        <small style="color: #666; font-size: 0.8rem;">
                            Password must be at least 8 characters with uppercase, lowercase, and number
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="input-with-icon password-field">
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" placeholder="••••••••" required>
                            <i class="fas fa-eye password-toggle" data-target="confirmPassword"></i>
                        </div>
                        <div class="error-message" id="confirmPasswordError"></div>
                    </div>
                </div>
                
                <div class="terms">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                </div>
                
                <button type="submit" class="btn-signup" id="submitBtn">
                    <span id="btnText">Create Account</span>
                </button>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
            </form>
        </div>
    </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Get the current page filename
            const currentPage = window.location.pathname.split('/').pop() || 'index.html';
            
            // Get all navigation links
            const navLinks = document.querySelectorAll('.nav-links a');
            
            // Define styles for active link
            const activeStyles = {
                color: '#007bff',
                fontWeight: 'bold',
                borderBottom: '2px solid #007bff',
                paddingBottom: '4px'
            };
            
            // Loop through each nav link
            navLinks.forEach(link => {
                const linkPage = link.getAttribute('href');
                
                if (linkPage === currentPage) {
                    Object.assign(link.style, activeStyles);
                    link.classList.add('active');
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Form validation and submission
        const signupForm = document.getElementById('signupForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const alertMessage = document.getElementById('alertMessage');

        // Clear error messages
        function clearErrors() {
            const errorMessages = document.querySelectorAll('.error-message');
            const formGroups = document.querySelectorAll('.form-group');
            
            errorMessages.forEach(error => error.textContent = '');
            formGroups.forEach(group => group.classList.remove('has-error'));
        }

        // Display error message
        function showError(fieldId, message) {
            const errorElement = document.getElementById(fieldId + 'Error');
            const formGroup = document.getElementById(fieldId).closest('.form-group');
            
            if (errorElement) errorElement.textContent = message;
            if (formGroup) formGroup.classList.add('has-error');
        }

        // Show alert message
        function showAlert(message, type) {
            alertMessage.textContent = message;
            alertMessage.className = `alert alert-${type}`;
            alertMessage.style.display = 'block';
            
            // Scroll to alert
            alertMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    alertMessage.style.display = 'none';
                }, 5000);
            }
        }

        // Client-side validation
        function validateForm() {
            clearErrors();
            let isValid = true;

            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Validate first name
            if (firstName.length < 2 || firstName.length > 100) {
                showError('firstName', 'First name must be between 2 and 100 characters');
                isValid = false;
            }

            // Validate last name
            if (lastName.length < 2 || lastName.length > 100) {
                showError('lastName', 'Last name must be between 2 and 100 characters');
                isValid = false;
            }

            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('email', 'Please enter a valid email address');
                isValid = false;
            }

            // Validate password
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?_&]{8,}$/;
            if (!passwordRegex.test(password)) {
                showError('password', 'Password must be at least 8 characters with uppercase, lowercase, and number');
                isValid = false;
            }

            // Validate password confirmation
            if (password !== confirmPassword) {
                showError('confirmPassword', 'Passwords do not match');
                isValid = false;
            }

            return isValid;
        }

        // Form submission
        signupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }

            // Disable submit button and show loading
            submitBtn.disabled = true;
            btnText.innerHTML = '<span class="spinner"></span>Creating Account...';
            
            // Prepare form data
            const formData = new FormData(signupForm);

            try {
                const response = await fetch('signup_process.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    signupForm.reset();
                    
                    // Redirect to login page or dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php'; // or 'dashboard.html'
                    }, 2000);
                } else {
                    showAlert(result.message, 'error');
                }

            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again later.', 'error');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                btnText.textContent = 'Create Account';
            }
        });

        // Real-time password validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
            
            if (password && !passwordRegex.test(password)) {
                this.closest('.form-group').classList.add('has-error');
            } else {
                this.closest('.form-group').classList.remove('has-error');
            }
        });

        // Real-time password confirmation validation
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.closest('.form-group').classList.add('has-error');
                showError('confirmPassword', 'Passwords do not match');
            } else {
                this.closest('.form-group').classList.remove('has-error');
                document.getElementById('confirmPasswordError').textContent = '';
            }
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
    </script>

    <script>
// Password toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
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