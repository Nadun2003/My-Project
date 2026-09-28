<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin') redirect('admin/dashboard.php');
    elseif ($role === 'customer') redirect('customer/dashboard.php');
    elseif ($role === 'guide') redirect('guide/dashboard.php');
    elseif ($role === 'hotel') redirect('hotel/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND status = 'active'");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        if ($role === 'admin') redirect('admin/dashboard.php');
        elseif ($role === 'customer') redirect('customer/dashboard.php');
        elseif ($role === 'guide') redirect('guide/dashboard.php');
        elseif ($role === 'hotel') redirect('hotel/dashboard.php');
    } else {
        $error = 'Invalid email, password, or role. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Paradise Island Travel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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
            position: relative;
            overflow: hidden;
        }

        /* ===== BACKGROUND VIDEO - MP4 from Pexels ===== */
        #bgVideo {
            position: fixed;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translateX(-50%) translateY(-50%);
            z-index: -2;
            object-fit: cover;
        }

        /* ===== DARK OVERLAY ===== */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: -1;
        }

        /* ============================================
           LOGIN CONTAINER
           ============================================ */
        .login-container {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            position: relative;
            z-index: 1;
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ============================================
           LEFT SIDE - WELCOME
           ============================================ */
        .login-left {
            background: linear-gradient(135deg, rgba(10, 92, 74, 0.92) 0%, rgba(6, 63, 50, 0.92) 100%);
            padding: 50px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            min-height: 450px;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .login-left::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }

        .login-left .brand {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-left .brand i {
            color: #f5a623;
            font-size: 36px;
        }

        .login-left .brand span {
            color: #f5a623;
        }

        .login-left h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            line-height: 1.2;
        }

        .login-left p {
            opacity: 0.85;
            line-height: 1.8;
            position: relative;
            z-index: 1;
            font-size: 14px;
        }

        .login-left .features {
            list-style: none;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }

        .login-left .features li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            opacity: 0.9;
            transition: all 0.3s ease;
        }

        .login-left .features li:hover {
            transform: translateX(6px);
            opacity: 1;
        }

        .login-left .features li i {
            color: #f5a623;
            font-size: 18px;
            width: 24px;
        }

        /* ============================================
           RIGHT SIDE - LOGIN FORM
           ============================================ */
        .login-right {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(12px);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right .form-header {
            margin-bottom: 28px;
        }

        .login-right .form-header h3 {
            font-size: 26px;
            font-weight: 700;
            color: #0a5c4a;
            margin-bottom: 4px;
        }

        .login-right .form-header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
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
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }

        .role-btn:hover {
            border-color: #0a5c4a;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(10, 92, 74, 0.08);
        }

        .role-btn.active {
            background: #0a5c4a;
            border-color: #0a5c4a;
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
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 13px;
            color: #2c3e50;
        }

        .form-group label i {
            color: #0a5c4a;
            margin-right: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0a5c4a;
            box-shadow: 0 0 0 4px rgba(10, 92, 74, 0.1);
        }

        /* ============================================
           BUTTON
           ============================================ */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0a5c4a, #084b3c);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 6px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(10, 92, 74, 0.35);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        /* ============================================
           ERROR MESSAGE
           ============================================ */
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
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

        /* ============================================
           REGISTER LINK
           ============================================ */
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #7f8c8d;
        }

        .register-link a {
            color: #0a5c4a;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: #f5a623;
        }

        /* ============================================
           LOADING ANIMATION
           ============================================ */
        .btn-login.loading {
            opacity: 0.8;
            pointer-events: none;
        }

        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 992px) {
            .login-container {
                max-width: 550px;
                grid-template-columns: 1fr;
            }

            .login-left {
                padding: 30px 30px;
                min-height: auto;
                border-radius: 24px 24px 0 0;
            }

            .login-left .brand {
                font-size: 28px;
            }

            .login-left h2 {
                font-size: 22px;
            }

            .login-left .features {
                display: none;
            }

            .login-right {
                padding: 30px 28px;
                border-radius: 0 0 24px 24px;
            }

            .role-selector {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .login-left {
                padding: 20px;
            }

            .login-left .brand {
                font-size: 22px;
            }

            .login-left h2 {
                font-size: 18px;
            }

            .login-left p {
                font-size: 12px;
            }

            .login-right {
                padding: 20px;
            }

            .login-right .form-header h3 {
                font-size: 20px;
            }

            .role-selector {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
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

        /* ============================================
           MOBILE - VIDEO වෙනුවට IMAGE
           ============================================ */
        @media (max-width: 768px) {
            #bgVideo {
                display: none;
            }

            body {
                background: url('https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=1920&q=80') no-repeat center center/cover;
            }
        }
    </style>
</head>
<body>

    <!-- ============================================
         BACKGROUND VIDEO - MP4 from Pexels
         ============================================ -->
    <video 
        id="bgVideo"
        autoplay
        muted
        loop
        playsinline
    >
        <source src="https://www.pexels.com/download/video/8970224/" type="video/mp4">
        <!-- Fallback: if video fails, display nothing (overlay covers it) -->
    </video>

    <div class="login-container">
        <!-- ===== LEFT SIDE ===== -->
        <div class="login-left">
            <div class="brand">
                <i class="fas fa-umbrella-beach"></i>
                Paradise<span>Travel</span>
            </div>
            <h2>Your Journey Begins Here</h2>
            <p>Explore the beauty of Sri Lanka with our premium travel experiences. Book your dream vacation today.</p>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> Easy Package Booking</li>
                <li><i class="fas fa-check-circle"></i> Professional Tour Guides</li>
                <li><i class="fas fa-check-circle"></i> Quality Hotel Partners</li>
                <li><i class="fas fa-check-circle"></i> Secure Online Payments</li>
                <li><i class="fas fa-check-circle"></i> 24/7 Customer Support</li>
            </ul>
        </div>

        <!-- ===== RIGHT SIDE ===== -->
        <div class="login-right">
            <div class="form-header">
                <h3>Welcome Back 👋</h3>
                <p class="subtitle">Sign in to continue your travel journey</p>
            </div>

            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
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

                <!-- Email -->
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="spinner"></span>
                    <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Sign In</span>
                </button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // ROLE SELECTOR
        // ============================================
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selectedRole').value = this.dataset.role;
            });
        });

        // ============================================
        // BUTTON LOADING STATE
        // ============================================
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.querySelector('.btn-text').textContent = 'Signing In...';
        });

        // ============================================
        // KEYBOARD SHORTCUT
        // ============================================
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeInput = document.activeElement;
                if (activeInput && (activeInput.tagName === 'INPUT')) {
                    document.getElementById('loginForm').submit();
                }
            }
        });

        // ============================================
        // ENSURE VIDEO PLAYS (user interaction not required for autoplay with muted)
        // ============================================
        // The video has autoplay, muted, loop – it will start automatically.
        // No extra action needed.
    </script>

</body>
</html>