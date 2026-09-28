<?php
require_once 'config/database.php';

if (isLoggedIn()) redirect('login.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    
    // Role-specific fields
    $nic = trim($_POST['nic'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $nationality = trim($_POST['nationality'] ?? '');
    
    $experience_years = $_POST['experience_years'] ?? 0;
    $languages = trim($_POST['languages'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $hourly_rate = $_POST['hourly_rate'] ?? 0;
    
    $hotel_name = trim($_POST['hotel_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $stars = $_POST['stars'] ?? 3;
    $room_count = $_POST['room_count'] ?? 0;
    $price_per_night = $_POST['price_per_night'] ?? 0;
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
            if ($stmt->execute([$name, $email, $hashed, $phone, $role])) {
                $user_id = $pdo->lastInsertId();
                
                // Role-specific profile insertion
                if ($role === 'customer') {
                    $stmt = $pdo->prepare("INSERT INTO customer_profiles (user_id, nic, address, date_of_birth, nationality) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $nic, $address, $date_of_birth, $nationality]);
                } elseif ($role === 'guide') {
                    $stmt = $pdo->prepare("INSERT INTO guide_profiles (user_id, experience_years, languages, specialization, hourly_rate) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $experience_years, $languages, $specialization, $hourly_rate]);
                } elseif ($role === 'hotel') {
                    $stmt = $pdo->prepare("INSERT INTO hotel_profiles (user_id, hotel_name, location, stars, room_count, price_per_night) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $hotel_name, $location, $stars, $room_count, $price_per_night]);
                } elseif ($role === 'admin') {
                    // Admin doesn't need extra profile
                }
                
                $success = 'Registration successful! Please login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Paradise Island Travel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ============================================
           ROOT VARIABLES
           ============================================ */
        :root {
            --primary: #0a5c4a;
            --primary-dark: #084b3c;
            --primary-light: #e8f5f0;
            --secondary: #f5a623;
            --danger: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --info: #3498db;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --gray: #7f8c8d;
            --white: #ffffff;
            --shadow: 0 4px 15px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
            --radius: 12px;
            --radius-lg: 20px;
            --transition: all 0.3s ease;
        }

        /* ============================================
           RESET & BASE
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: url('https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=1920&q=80') no-repeat center center/cover;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 0;
        }

        /* ============================================
           REGISTER CONTAINER
           ============================================ */
        .register-container {
            width: 100%;
            max-width: 580px;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-lg);
            padding: 40px 35px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            position: relative;
            z-index: 1;
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ============================================
           HEADER
           ============================================ */
        .register-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .register-header .brand {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .register-header .brand i {
            color: var(--secondary);
            font-size: 30px;
        }

        .register-header .brand span {
            color: var(--secondary);
        }

        .register-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .register-header p {
            color: var(--gray);
            font-size: 14px;
            margin-top: 4px;
        }

        /* ============================================
           ROLE SELECTOR
           ============================================ */
        .role-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 24px;
        }

        .role-btn {
            text-align: center;
            padding: 12px 8px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            background: white;
        }

        .role-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(10, 92, 74, 0.08);
        }

        .role-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(10, 92, 74, 0.25);
        }

        .role-btn i {
            font-size: 22px;
            display: block;
            margin-bottom: 4px;
        }

        .role-btn span {
            font-size: 11px;
            font-weight: 500;
        }

        /* ============================================
           FORM ELEMENTS
           ============================================ */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 13px;
            color: var(--dark);
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 6px;
        }

        .form-group label .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius);
            font-size: 14px;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(10, 92, 74, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }

        /* ============================================
           ROLE-SPECIFIC FIELDS (Hidden by default)
           ============================================ */
        .role-fields {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .role-fields.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .role-fields .section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            margin: 16px 0 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .role-fields .section-title i {
            font-size: 16px;
        }

        /* ============================================
           BUTTON
           ============================================ */
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(10, 92, 74, 0.35);
        }

        .btn-register:active {
            transform: scale(0.98);
        }

        .btn-register.loading {
            opacity: 0.8;
            pointer-events: none;
        }

        .btn-register .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-register.loading .spinner {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ============================================
           MESSAGES
           ============================================ */
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 18px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #dc2626;
        }

        .error i {
            font-size: 18px;
        }

        .success {
            background: #d4f5e6;
            color: var(--success);
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 18px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid var(--success);
        }

        .success i {
            font-size: 18px;
        }

        /* ============================================
           LOGIN LINK
           ============================================ */
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: var(--gray);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .login-link a:hover {
            color: var(--secondary);
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 600px) {
            .register-container {
                padding: 24px 18px;
            }

            .role-selector {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .register-header .brand {
                font-size: 22px;
            }

            .register-header h2 {
                font-size: 20px;
            }

            .role-btn {
                padding: 10px 6px;
            }

            .role-btn i {
                font-size: 18px;
            }

            .role-btn span {
                font-size: 10px;
            }
        }

        @media (max-width: 400px) {
            .register-container {
                padding: 18px 12px;
            }

            .role-selector {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
        }
    </style>
</head>
<body>

    <div class="register-container">
        <!-- ===== HEADER ===== -->
        <div class="register-header">
            <div class="brand">
                <i class="fas fa-umbrella-beach"></i>
                Paradise<span>Travel</span>
            </div>
            <h2>Create Account</h2>
            <p>Join us and start your journey in Sri Lanka</p>
        </div>

        <!-- ===== MESSAGES ===== -->
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <!-- ===== FORM ===== -->
        <form method="POST" id="registerForm">
            <!-- Role Selector -->
            <div class="role-selector">
                <div class="role-btn active" data-role="customer">
                    <i class="fas fa-user"></i>
                    <span>Customer</span>
                </div>
                <div class="role-btn" data-role="guide">
                    <i class="fas fa-hiking"></i>
                    <span>Guide</span>
                </div>
                <div class="role-btn" data-role="hotel">
                    <i class="fas fa-hotel"></i>
                    <span>Hotel</span>
                </div>
                <div class="role-btn" data-role="admin">
                    <i class="fas fa-crown"></i>
                    <span>Admin</span>
                </div>
            </div>

            <input type="hidden" name="role" id="selectedRole" value="customer">

            <!-- ===== COMMON FIELDS ===== -->
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="0771234567">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                </div>
            </div>

            <!-- ============================================
                 CUSTOMER FIELDS
                 ============================================ -->
            <div class="role-fields active" id="customerFields">
                <div class="section-title"><i class="fas fa-id-card"></i> Customer Details</div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> NIC</label>
                        <input type="text" name="nic" class="form-control" placeholder="982345678V">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-flag"></i> Nationality</label>
                        <input type="text" name="nationality" class="form-control" placeholder="Sri Lankan">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-home"></i> Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="No.15, Galle Road, Colombo 03"></textarea>
                </div>
            </div>

            <!-- ============================================
                 GUIDE FIELDS
                 ============================================ -->
            <div class="role-fields" id="guideFields">
                <div class="section-title"><i class="fas fa-hiking"></i> Guide Details</div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> Experience (Years)</label>
                        <input type="number" name="experience_years" class="form-control" min="0" placeholder="5">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-language"></i> Languages</label>
                        <input type="text" name="languages" class="form-control" placeholder="English, Sinhala, Tamil">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Specialization</label>
                        <input type="text" name="specialization" class="form-control" placeholder="Cultural, Wildlife, Adventure">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-dollar-sign"></i> Hourly Rate (LKR)</label>
                        <input type="number" step="0.01" name="hourly_rate" class="form-control" placeholder="2500">
                    </div>
                </div>
            </div>

            <!-- ============================================
                 HOTEL FIELDS
                 ============================================ -->
            <div class="role-fields" id="hotelFields">
                <div class="section-title"><i class="fas fa-hotel"></i> Hotel Details</div>
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Hotel Name</label>
                    <input type="text" name="hotel_name" class="form-control" placeholder="Sigiriya Rock View Hotel">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" name="location" class="form-control" placeholder="Sigiriya, Sri Lanka">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-star"></i> Star Rating</label>
                        <select name="stars" class="form-control">
                            <option value="1">1 Star</option>
                            <option value="2">2 Star</option>
                            <option value="3" selected>3 Star</option>
                            <option value="4">4 Star</option>
                            <option value="5">5 Star</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-bed"></i> Room Count</label>
                        <input type="number" name="room_count" class="form-control" min="0" placeholder="25">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-dollar-sign"></i> Price Per Night (LKR)</label>
                    <input type="number" step="0.01" name="price_per_night" class="form-control" placeholder="15000">
                </div>
            </div>

            <!-- ============================================
                 ADMIN FIELDS (No extra fields)
                 ============================================ -->
            <div class="role-fields" id="adminFields">
                <div class="section-title"><i class="fas fa-crown"></i> Admin Details</div>
                <div style="background:var(--primary-light); padding:14px; border-radius:var(--radius); text-align:center; font-size:13px; color:var(--primary);">
                    <i class="fas fa-info-circle"></i> Admin accounts require approval. You will be contacted via email.
                </div>
            </div>

            <!-- ===== SUBMIT ===== -->
            <button type="submit" class="btn-register" id="registerBtn">
                <span class="spinner"></span>
                <span class="btn-text"><i class="fas fa-user-plus"></i> Create Account</span>
            </button>
        </form>

        <!-- ===== LOGIN LINK ===== -->
        <div class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

    <script>
        // ============================================
        // ROLE SELECTOR
        // ============================================
        const roleBtns = document.querySelectorAll('.role-btn');
        const roleFields = {
            customer: document.getElementById('customerFields'),
            guide: document.getElementById('guideFields'),
            hotel: document.getElementById('hotelFields'),
            admin: document.getElementById('adminFields')
        };

        roleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                roleBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Get selected role
                const role = this.dataset.role;
                document.getElementById('selectedRole').value = role;

                // Show/hide role-specific fields
                Object.keys(roleFields).forEach(key => {
                    if (key === role) {
                        roleFields[key].classList.add('active');
                    } else {
                        roleFields[key].classList.remove('active');
                    }
                });
            });
        });

        // ============================================
        // BUTTON LOADING STATE
        // ============================================
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('registerBtn');
            btn.classList.add('loading');
            btn.querySelector('.btn-text').textContent = 'Creating Account...';
        });

        // ============================================
        // KEYBOARD SHORTCUT - Enter Key
        // ============================================
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeInput = document.activeElement;
                if (activeInput && (activeInput.tagName === 'INPUT' || activeInput.tagName === 'TEXTAREA' || activeInput.tagName === 'SELECT')) {
                    document.getElementById('registerForm').submit();
                }
            }
        });

        // ============================================
        // PASSWORD VALIDATION
        // ============================================
        const passwordInput = document.querySelector('input[name="password"]');
        const confirmInput = document.querySelector('input[name="confirm_password"]');

        confirmInput.addEventListener('input', function() {
            if (this.value.length > 0 && this.value !== passwordInput.value) {
                this.style.borderColor = '#e74c3c';
                this.style.boxShadow = '0 0 0 4px rgba(231, 76, 60, 0.1)';
            } else if (this.value.length > 0 && this.value === passwordInput.value) {
                this.style.borderColor = '#27ae60';
                this.style.boxShadow = '0 0 0 4px rgba(39, 174, 96, 0.1)';
            } else {
                this.style.borderColor = '#e2e8f0';
                this.style.boxShadow = 'none';
            }
        });

        passwordInput.addEventListener('input', function() {
            if (confirmInput.value.length > 0) {
                if (this.value === confirmInput.value) {
                    confirmInput.style.borderColor = '#27ae60';
                    confirmInput.style.boxShadow = '0 0 0 4px rgba(39, 174, 96, 0.1)';
                } else {
                    confirmInput.style.borderColor = '#e74c3c';
                    confirmInput.style.boxShadow = '0 0 0 4px rgba(231, 76, 60, 0.1)';
                }
            }
        });
    </script>

</body>
</html>