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
    <title>Guide Login - Paradise Island Travel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- PDF Generation Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; position: relative; overflow: hidden; background: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=80') no-repeat center center/cover; }
        #bgVideo { position: fixed; top: 50%; left: 50%; min-width: 100%; min-height: 100%; width: auto; height: auto; transform: translateX(-50%) translateY(-50%); z-index: -2; object-fit: cover; }
        body::before { content: ''; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.55); z-index: -1; }
        
        .login-container { width: 100%; max-width: 1100px; display: grid; grid-template-columns: 1fr 1fr; border-radius: 24px; overflow: hidden; box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4); position: relative; z-index: 1; animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(40px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        
        .login-left { background: linear-gradient(135deg, rgba(10, 92, 74, 0.92) 0%, rgba(6, 63, 50, 0.92) 100%); padding: 50px 40px; color: white; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; min-height: 450px; }
        .login-left::before { content: ''; position: absolute; top: -30%; right: -20%; width: 400px; height: 400px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; }
        .login-left::after { content: ''; position: absolute; bottom: -40%; left: -10%; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.03); border-radius: 50%; }
        .login-left .brand { font-size: 32px; font-weight: 800; margin-bottom: 20px; position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; }
        .login-left .brand i { color: #f5a623; font-size: 36px; }
        .login-left .brand span { color: #f5a623; }
        .login-left h2 { font-size: 28px; font-weight: 700; margin-bottom: 10px; position: relative; z-index: 1; line-height: 1.2; }
        .login-left p { opacity: 0.85; line-height: 1.8; position: relative; z-index: 1; font-size: 14px; }
        .login-left .features { list-style: none; margin-top: 30px; position: relative; z-index: 1; }
        .login-left .features li { margin-bottom: 12px; display: flex; align-items: center; gap: 12px; font-size: 14px; opacity: 0.9; transition: all 0.3s ease; }
        .login-left .features li:hover { transform: translateX(6px); opacity: 1; }
        .login-left .features li i { color: #f5a623; font-size: 18px; width: 24px; }
        
        .login-right { background: rgba(255, 255, 255, 0.97); backdrop-filter: blur(12px); padding: 50px 40px; display: flex; flex-direction: column; justify-content: center; }
        .login-right .form-header { margin-bottom: 28px; }
        .login-right .form-header h3 { font-size: 26px; font-weight: 700; color: #0a5c4a; margin-bottom: 4px; }
        .login-right .form-header .subtitle { color: #7f8c8d; font-size: 14px; }
        
        .role-selector { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 24px; }
        .role-btn { text-align: center; padding: 12px 8px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: white; }
        .role-btn.active { background: #0a5c4a; border-color: #0a5c4a; color: white; transform: translateY(-2px); box-shadow: 0 4px 20px rgba(10, 92, 74, 0.25); }
        .role-btn.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; filter: grayscale(0.8); }
        .role-btn i { font-size: 22px; display: block; margin-bottom: 4px; }
        .role-btn span { font-size: 11px; font-weight: 500; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 6px; font-size: 13px; color: #2c3e50; }
        .form-group label i { color: #0a5c4a; margin-right: 6px; }
        .form-group input { width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: all 0.3s ease; font-family: 'Poppins', sans-serif; }
        .form-group input:focus { outline: none; border-color: #0a5c4a; box-shadow: 0 0 0 4px rgba(10, 92, 74, 0.1); }
        
        .btn-login { width: 100%; padding: 14px; background: linear-gradient(135deg, #0a5c4a, #084b3c); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 6px; }
        .btn-login:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(10, 92, 74, 0.35); }
        .btn-login:active { transform: scale(0.98); }
        
        .error { background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 13px; display: flex; align-items: center; gap: 10px; border-left: 4px solid #dc2626; }
        .error i { font-size: 18px; }
        
        .register-link { text-align: center; margin-top: 20px; font-size: 13px; color: #7f8c8d; }
        .register-link a { color: #0a5c4a; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .register-link a:hover { color: #f5a623; }
        
        .btn-login.loading { opacity: 0.8; pointer-events: none; }
        .btn-login .spinner { display: none; width: 20px; height: 20px; border: 2px solid rgba(255, 255, 255, 0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; }
        .btn-login.loading .spinner { display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* PDF Download Button */
        .pdf-actions { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
        .btn-pdf { padding: 10px 20px; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px; }
        .btn-pdf-download { background: #dc2626; color: white; }
        .btn-pdf-download:hover { background: #b91c1c; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3); }
        .btn-pdf-preview { background: #0a5c4a; color: white; }
        .btn-pdf-preview:hover { background: #084b3c; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(10, 92, 74, 0.3); }
        
        /* Booking Cards */
        .booking-section { margin-top: 24px; border-top: 2px solid #e2e8f0; padding-top: 24px; }
        .booking-section-title { font-size: 16px; font-weight: 700; color: #0a5c4a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .booking-card { background: white; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; border: 1px solid #e2e8f0; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .booking-card:hover { border-color: #0a5c4a; box-shadow: 0 4px 20px rgba(10, 92, 74, 0.1); transform: translateY(-2px); }
        .booking-card .status { display: inline-block; padding: 2px 12px; border-radius: 50px; font-size: 11px; font-weight: 600; }
        .booking-card .status.confirmed { background: #dcfce7; color: #16a34a; }
        .booking-card .status.pending { background: #fef3c7; color: #d97706; }
        .booking-card .status.completed { background: #dbeafe; color: #2563eb; }
        .booking-card .booking-id { font-size: 13px; font-weight: 700; color: #0a5c4a; }
        .booking-card .booking-title { font-size: 15px; font-weight: 600; color: #1a202c; margin: 4px 0; }
        .booking-card .booking-details { font-size: 13px; color: #7f8c8d; display: flex; gap: 20px; flex-wrap: wrap; }
        .booking-card .booking-details i { color: #0a5c4a; width: 18px; }
        .booking-card .booking-actions { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .booking-card .btn-sm { padding: 6px 14px; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 6px; text-decoration: none; }
        .booking-card .btn-sm.btn-details { background: #e2e8f0; color: #1a202c; }
        .booking-card .btn-sm.btn-details:hover { background: #cbd5e1; }
        .booking-card .btn-sm.btn-pdf-card { background: #dc2626; color: white; }
        .booking-card .btn-sm.btn-pdf-card:hover { background: #b91c1c; transform: translateY(-2px); }
        .booking-card .btn-sm.btn-complete { background: #0a5c4a; color: white; }
        .booking-card .btn-sm.btn-complete:hover { background: #084b3c; transform: translateY(-2px); }
        .booking-card .days-left { position: absolute; top: 16px; right: 20px; font-size: 12px; font-weight: 600; padding: 2px 12px; border-radius: 50px; }
        .booking-card .days-left.today { background: #fee2e2; color: #dc2626; }
        .booking-card .days-left.soon { background: #fef3c7; color: #d97706; }
        .booking-card .days-left.later { background: #dcfce7; color: #16a34a; }
        
        /* PDF Modal */
        .pdf-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); z-index: 9999; justify-content: center; align-items: center; padding: 20px; }
        .pdf-modal.active { display: flex; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .pdf-modal-content { background: white; border-radius: 20px; max-width: 900px; width: 100%; max-height: 90vh; overflow: hidden; position: relative; }
        .pdf-modal-header { padding: 20px 24px; background: #0a5c4a; color: white; display: flex; justify-content: space-between; align-items: center; border-radius: 20px 20px 0 0; }
        .pdf-modal-header h3 { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .pdf-modal-close { background: rgba(255,255,255,0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; font-size: 20px; cursor: pointer; transition: all 0.3s ease; }
        .pdf-modal-close:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
        .pdf-modal-body { padding: 24px; max-height: calc(90vh - 80px); overflow-y: auto; }
        .pdf-preview-content { background: #f8fafc; padding: 30px; border-radius: 12px; border: 2px dashed #e2e8f0; }
        
        /* Toast */
        .toast { position: fixed; bottom: 30px; right: 30px; background: #0a5c4a; color: white; padding: 16px 24px; border-radius: 12px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 500; box-shadow: 0 8px 30px rgba(0,0,0,0.2); transform: translateY(120px); opacity: 0; transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); z-index: 10000; display: flex; align-items: center; gap: 12px; }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.error { background: #dc2626; }
        .toast i { font-size: 20px; }
        
        @media (max-width: 992px) { 
            .login-container { max-width: 550px; grid-template-columns: 1fr; } 
            .login-left { padding: 30px 30px; min-height: auto; border-radius: 24px 24px 0 0; } 
            .login-left .brand { font-size: 28px; } 
            .login-left h2 { font-size: 22px; } 
            .login-left .features { display: none; } 
            .login-right { padding: 30px 28px; border-radius: 0 0 24px 24px; } 
            .role-selector { grid-template-columns: repeat(2, 1fr); } 
            .booking-card .booking-details { flex-direction: column; gap: 4px; }
            .booking-card .days-left { position: static; display: inline-block; margin-left: 10px; }
        }
        
        @media (max-width: 480px) { 
            .login-left { padding: 20px; } 
            .login-left .brand { font-size: 22px; } 
            .login-left h2 { font-size: 18px; } 
            .login-left p { font-size: 12px; } 
            .login-right { padding: 20px; } 
            .login-right .form-header h3 { font-size: 20px; } 
            .role-selector { grid-template-columns: repeat(2, 1fr); gap: 8px; } 
            .role-btn { padding: 10px 6px; } 
            .role-btn i { font-size: 18px; } 
            .role-btn span { font-size: 10px; }
            .pdf-actions { flex-direction: column; }
            .booking-card .booking-actions { flex-direction: column; }
            .booking-card .btn-sm { width: 100%; justify-content: center; }
        }
        
        @media (max-width: 768px) { 
            #bgVideo { display: none; } 
            body { background: url('https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=1920&q=80') no-repeat center center/cover; } 
        }
    </style>
</head>
<body>

    <video id="bgVideo" autoplay muted loop playsinline>
        <source src="https://www.pexels.com/download/video/8970224/" type="video/mp4">
    </video>

    <!-- Toast Notification -->
    <div class="toast" id="toast"><i class="fas fa-check-circle"></i> <span id="toastMessage">PDF Downloaded Successfully!</span></div>

    <!-- PDF Preview Modal -->
    <div class="pdf-modal" id="pdfModal">
        <div class="pdf-modal-content">
            <div class="pdf-modal-header">
                <h3><i class="fas fa-file-pdf"></i> <span id="pdfModalTitle">PDF Preview</span></h3>
                <button class="pdf-modal-close" onclick="closePdfModal()">&times;</button>
            </div>
            <div class="pdf-modal-body" id="pdfPreviewBody">
                <!-- Dynamic content will be inserted here -->
            </div>
        </div>
    </div>

    <div class="login-container" id="loginContainer">
        <div class="login-left">
            <div class="brand"><i class="fas fa-umbrella-beach"></i> Paradise<span>Travel</span></div>
            <h2>Your Journey Begins Here</h2>
            <p>Explore the beauty of Sri Lanka with our premium travel experiences.</p>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> Easy Package Booking</li>
                <li><i class="fas fa-check-circle"></i> Professional Tour Guides</li>
                <li><i class="fas fa-check-circle"></i> Quality Hotel Partners</li>
                <li><i class="fas fa-check-circle"></i> Secure Online Payments</li>
                <li><i class="fas fa-check-circle"></i> 24/7 Customer Support</li>
            </ul>
        </div>

        <div class="login-right">
            <div class="form-header">
                <h3>🧭 Guide Login</h3>
                <p class="subtitle">Sign in to your guide account</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="role-selector">
                    <div class="role-btn disabled" data-role="customer"><i class="fas fa-user"></i><span>Customer</span></div>
                    <div class="role-btn active" data-role="guide"><i class="fas fa-hiking"></i><span>Guide</span></div>
                    <div class="role-btn disabled" data-role="hotel"><i class="fas fa-hotel"></i><span>Hotel</span></div>
                    <div class="role-btn disabled" data-role="admin"><i class="fas fa-crown"></i><span>Admin</span></div>
                </div>

                <input type="hidden" name="role" value="guide">

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" id="loginEmail" required placeholder="Enter your email" value="guide@paradise.com">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="loginPassword" required placeholder="Enter your password" value="password123">
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="spinner"></span>
                    <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Sign In</span>
                </button>
            </form>

            <!-- PDF Generation Actions -->
            <div class="pdf-actions">
                <button class="btn-pdf btn-pdf-download" onclick="generatePDF('download')">
                    <i class="fas fa-file-pdf"></i> Download PDF Guide
                </button>
                <button class="btn-pdf btn-pdf-preview" onclick="generatePDF('preview')">
                    <i class="fas fa-eye"></i> Preview PDF
                </button>
            </div>

            <!-- ============================================================ -->
            <!-- BOOKING CARDS SECTION - FIXED VERSION                        -->
            <!-- ============================================================ -->
            <div class="booking-section">
                <div class="booking-section-title">
                    <i class="fas fa-ticket-alt"></i> Your Bookings
                </div>

                <!-- Booking Card 1 -->
                <div class="booking-card" id="booking1">
                    <span class="booking-id">TRV20260717281</span>
                    <span class="status confirmed"><i class="fas fa-check-circle"></i> CONFIRMED</span>
                    <span class="days-left soon">2 DAYS LEFT</span>
                    <div class="booking-title">🌿 Spice Garden Experience - Matale, Kandy, Gampola</div>
                    <div class="booking-details">
                        <span><i class="fas fa-user"></i> Customer: sithum</span>
                        <span><i class="fas fa-phone"></i> Contact: 0761723130</span>
                        <span><i class="fas fa-calendar-alt"></i> 2026-07-19</span>
                    </div>
                    <div class="booking-actions">
                        <button class="btn-sm btn-details" onclick="alert('📋 Booking Details\n\nID: TRV20260717281\nTour: Spice Garden Experience\nCustomer: sithum\nContact: 0761723130\nDate: 2026-07-19\nStatus: CONFIRMED\nDays Left: 2 DAYS LEFT')">
                            <i class="fas fa-info-circle"></i> View Details
                        </button>
                        <button class="btn-sm btn-pdf-card" onclick="generateBookingPDF(
                            'TRV20260717281',
                            '🌿 Spice Garden Experience - Matale, Kandy, Gampola',
                            'sithum',
                            '0761723130',
                            '2026-07-19',
                            'CONFIRMED',
                            '2 DAYS LEFT'
                        )">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>

                <!-- Booking Card 2 -->
                <div class="booking-card" id="booking2">
                    <span class="booking-id">TRV20260718401</span>
                    <span class="status confirmed"><i class="fas fa-check-circle"></i> CONFIRMED</span>
                    <span class="days-left today">TODAY</span>
                    <div class="booking-title">🧗 Rock Climbing Adventure - Sigiriya, Pidurangala, Knuckles</div>
                    <div class="booking-details">
                        <span><i class="fas fa-user"></i> Customer: sithum</span>
                        <span><i class="fas fa-phone"></i> Contact: 0761723130</span>
                        <span><i class="fas fa-calendar-alt"></i> 2026-07-21</span>
                    </div>
                    <div class="booking-actions">
                        <button class="btn-sm btn-details" onclick="alert('📋 Booking Details\n\nID: TRV20260718401\nTour: Rock Climbing Adventure\nCustomer: sithum\nContact: 0761723130\nDate: 2026-07-21\nStatus: CONFIRMED\nDays Left: TODAY')">
                            <i class="fas fa-info-circle"></i> View Details
                        </button>
                        <button class="btn-sm btn-pdf-card" onclick="generateBookingPDF(
                            'TRV20260718401',
                            '🧗 Rock Climbing Adventure - Sigiriya, Pidurangala, Knuckles',
                            'sithum',
                            '0761723130',
                            '2026-07-21',
                            'CONFIRMED',
                            'TODAY'
                        )">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="btn-sm btn-complete" onclick="alert('✅ Tour Completed Successfully!\n\nBooking ID: TRV20260718401\nTour: Rock Climbing Adventure')">
                            <i class="fas fa-check-double"></i> Complete Tour
                        </button>
                    </div>
                </div>
            </div>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // LOGIN FORM SUBMISSION
        // ============================================================
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.querySelector('.btn-text').textContent = 'Signing In...';
        });

        // ============================================================
        // GENERATE BOOKING PDF - FIXED VERSION
        // ============================================================
        function generateBookingPDF(bookingId, title, customer, contact, date, status, daysLeft) {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                
                // Colors
                const primaryColor = [10, 92, 74];
                const accentColor = [245, 166, 35];
                const statusColors = status === 'CONFIRMED' ? [22, 163, 74] : [217, 119, 6];
                
                // ============================================================
                // HEADER with Gradient
                // ============================================================
                doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.rect(0, 0, pageWidth, 45, 'F');
                
                // Decorative accent line
                doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
                doc.rect(0, 43, pageWidth, 2, 'F');
                
                // Brand
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(22);
                doc.setFont('helvetica', 'bold');
                doc.text('🌴 Paradise Travel', 20, 28);
                
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('Booking Confirmation', pageWidth - 20, 28, { align: 'right' });
                
                // Date
                const now = new Date();
                const dateStr = now.toLocaleDateString('en-US', { 
                    year: 'numeric', month: 'long', day: 'numeric' 
                });
                doc.setFontSize(9);
                doc.setTextColor(200, 220, 210);
                doc.text(`Printed: ${dateStr}`, pageWidth - 20, 36, { align: 'right' });
                
                let yPos = 55;
                
                // ============================================================
                // BOOKING TITLE
                // ============================================================
                doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setFontSize(18);
                doc.setFont('helvetica', 'bold');
                doc.text('📋 Booking Details', 20, yPos);
                
                yPos += 8;
                doc.setDrawColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setLineWidth(0.5);
                doc.line(20, yPos, pageWidth - 20, yPos);
                
                yPos += 12;
                
                // ============================================================
                // STATUS BADGE
                // ============================================================
                // Status box
                doc.setFillColor(statusColors[0], statusColors[1], statusColors[2]);
                doc.roundedRect(pageWidth - 70, yPos - 6, 55, 12, 2, 2, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(9);
                doc.setFont('helvetica', 'bold');
                doc.text('✓ ' + status, pageWidth - 42, yPos + 3, { align: 'center' });
                
                // ============================================================
                // BOOKING INFO CARDS
                // ============================================================
                const bookingInfo = [
                    { label: 'Booking ID', value: bookingId },
                    { label: 'Tour Title', value: title },
                    { label: 'Customer', value: customer },
                    { label: 'Contact', value: contact },
                    { label: 'Date', value: date },
                    { label: 'Status', value: daysLeft }
                ];
                
                doc.setTextColor(50, 50, 50);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                
                bookingInfo.forEach((info, index) => {
                    const x = 20 + (index % 2) * ((pageWidth - 40) / 2);
                    const y = yPos + Math.floor(index / 2) * 14;
                    
                    // Background for each info item
                    doc.setFillColor(248, 250, 252);
                    doc.roundedRect(x, y - 4, (pageWidth - 40) / 2 - 8, 12, 2, 2, 'F');
                    
                    doc.setTextColor(120, 120, 120);
                    doc.setFont('helvetica', 'bold');
                    doc.text(info.label + ':', x + 4, y + 5);
                    
                    doc.setTextColor(30, 30, 30);
                    doc.setFont('helvetica', 'normal');
                    const maxWidth = (pageWidth - 40) / 2 - 20;
                    const valueText = info.value.length > 30 ? info.value.substring(0, 30) + '...' : info.value;
                    doc.text(valueText, x + 50, y + 5);
                });
                
                yPos += 60;
                
                // ============================================================
                // ITINERARY / TOUR DETAILS
                // ============================================================
                doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('🗺️ Tour Itinerary', 20, yPos);
                
                yPos += 8;
                doc.setDrawColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setLineWidth(0.3);
                doc.line(20, yPos, pageWidth - 20, yPos);
                
                yPos += 10;
                
                const itinerary = [
                    { time: '09:00 AM', activity: 'Pickup from hotel / meeting point' },
                    { time: '10:00 AM', activity: 'Start of tour experience' },
                    { time: '12:30 PM', activity: 'Lunch break (included)' },
                    { time: '02:00 PM', activity: 'Continue exploration' },
                    { time: '05:00 PM', activity: 'Return to hotel / end of tour' }
                ];
                
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(60, 60, 60);
                
                itinerary.forEach((item) => {
                    doc.setFillColor(245, 247, 250);
                    doc.roundedRect(22, yPos - 3, pageWidth - 44, 10, 2, 2, 'F');
                    
                    doc.setTextColor(10, 92, 74);
                    doc.setFont('helvetica', 'bold');
                    doc.text(item.time, 28, yPos + 5);
                    
                    doc.setTextColor(60, 60, 60);
                    doc.setFont('helvetica', 'normal');
                    doc.text(item.activity, 60, yPos + 5);
                    
                    yPos += 14;
                });
                
                yPos += 8;
                
                // ============================================================
                // IMPORTANT NOTES
                // ============================================================
                doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setFontSize(12);
                doc.setFont('helvetica', 'bold');
                doc.text('📝 Important Notes', 20, yPos);
                
                yPos += 8;
                doc.setDrawColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setLineWidth(0.3);
                doc.line(20, yPos, pageWidth - 20, yPos);
                
                yPos += 10;
                
                const notes = [
                    '• Please arrive 15 minutes before the scheduled start time',
                    '• Bring comfortable clothing and appropriate footwear',
                    '• Contact your guide for any changes or special requests',
                    '• Cancellation policy: 24 hours notice required'
                ];
                
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(80, 80, 80);
                
                notes.forEach((note) => {
                    doc.text(note, 25, yPos + 4);
                    yPos += 10;
                });
                
                // ============================================================
                // FOOTER
                // ============================================================
                doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.rect(0, pageHeight - 20, pageWidth, 20, 'F');
                
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                doc.text('© ' + now.getFullYear() + ' Paradise Island Travel. All rights reserved.', pageWidth / 2, pageHeight - 8, { align: 'center' });
                
                doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
                doc.rect(0, pageHeight - 20, pageWidth, 1.5, 'F');
                
                // ============================================================
                // SAVE PDF
                // ============================================================
                const fileName = `Booking_${bookingId}_${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}.pdf`;
                doc.save(fileName);
                showToast(`✅ PDF Downloaded: ${bookingId}`, 'success');
                
            } catch (error) {
                console.error('PDF Generation Error:', error);
                showToast('❌ Error generating PDF. Please try again.', 'error');
            }
        }

        // ============================================================
        // GENERATE GUIDE LOGIN PDF
        // ============================================================
        function generatePDF(mode) {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                
                const primaryColor = [10, 92, 74];
                const accentColor = [245, 166, 35];
                const textColor = [30, 30, 30];
                
                // Header
                doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.rect(0, 0, pageWidth, 45, 'F');
                doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
                doc.rect(0, 43, pageWidth, 2, 'F');
                
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(24);
                doc.setFont('helvetica', 'bold');
                doc.text('🌴 Paradise Travel', 20, 28);
                
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('Guide Login Documentation', pageWidth - 20, 28, { align: 'right' });
                
                const now = new Date();
                const dateStr = now.toLocaleDateString('en-US', { 
                    year: 'numeric', month: 'long', day: 'numeric' 
                });
                doc.setFontSize(9);
                doc.setTextColor(200, 220, 210);
                doc.text(`Generated: ${dateStr}`, pageWidth - 20, 36, { align: 'right' });
                
                let yPos = 55;
                
                // Title
                doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setFontSize(18);
                doc.setFont('helvetica', 'bold');
                doc.text('🧭 Guide Login Information', 20, yPos);
                
                yPos += 8;
                doc.setDrawColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setLineWidth(0.5);
                doc.line(20, yPos, pageWidth - 20, yPos);
                
                yPos += 10;
                
                // Login Details Card
                doc.setFillColor(245, 247, 250);
                doc.roundedRect(20, yPos, pageWidth - 40, 55, 3, 3, 'F');
                doc.setDrawColor(200, 200, 200);
                doc.setLineWidth(0.3);
                doc.roundedRect(20, yPos, pageWidth - 40, 55, 3, 3, 'S');
                
                doc.setTextColor(textColor[0], textColor[1], textColor[2]);
                doc.setFontSize(11);
                doc.setFont('helvetica', 'bold');
                doc.text('🔑 Login Credentials', 30, yPos + 10);
                
                const email = document.getElementById('loginEmail').value || 'guide@paradise.com';
                const password = document.getElementById('loginPassword').value || '••••••••';
                
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(10);
                doc.setTextColor(80, 80, 80);
                doc.text(`📧 Email: ${email}`, 30, yPos + 25);
                doc.text(`🔒 Password: ${password}`, 30, yPos + 38);
                doc.text(`👤 Role: Guide`, 30, yPos + 50);
                
                yPos += 65;
                
                // Guide Features
                doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('📋 Guide Features', 20, yPos);
                
                yPos += 8;
                doc.setDrawColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setLineWidth(0.3);
                doc.line(20, yPos, pageWidth - 20, yPos);
                
                yPos += 10;
                
                const features = [
                    { icon: '🌍', text: 'Manage Tour Packages' },
                    { icon: '👥', text: 'View Customer Bookings' },
                    { icon: '📅', text: 'Schedule Management' },
                    { icon: '💰', text: 'Earnings & Payments' },
                    { icon: '📱', text: 'Mobile-Friendly Dashboard' },
                    { icon: '🔄', text: 'Real-time Updates' }
                ];
                
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(50, 50, 50);
                
                let col1X = 25;
                let col2X = pageWidth / 2 + 10;
                let rowY = yPos;
                
                features.forEach((feature, index) => {
                    const col = index % 2 === 0 ? col1X : col2X;
                    const row = Math.floor(index / 2);
                    const y = rowY + (row * 10);
                    doc.text(`${feature.icon} ${feature.text}`, col, y);
                });
                
                yPos += 45;
                
                // System Status
                doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setFontSize(13);
                doc.setFont('helvetica', 'bold');
                doc.text('📊 System Status', 20, yPos);
                
                yPos += 8;
                doc.setDrawColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setLineWidth(0.3);
                doc.line(20, yPos, pageWidth - 20, yPos);
                
                yPos += 10;
                
                const statusItems = [
                    { label: 'Server Status', status: '✅ Online', color: [10, 92, 74] },
                    { label: 'Database Connection', status: '✅ Connected', color: [10, 92, 74] },
                    { label: 'Authentication', status: '✅ Secure', color: [10, 92, 74] },
                    { label: 'Session Management', status: '✅ Active', color: [10, 92, 74] }
                ];
                
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                
                statusItems.forEach((item, index) => {
                    const x = 25 + (index % 2) * ((pageWidth - 50) / 2);
                    const y = yPos + Math.floor(index / 2) * 12;
                    
                    doc.setTextColor(100, 100, 100);
                    doc.text(item.label + ':', x, y);
                    doc.setTextColor(item.color[0], item.color[1], item.color[2]);
                    doc.text(item.status, x + 55, y);
                });
                
                yPos += 30;
                
                // Quick Tips
                doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.setFontSize(12);
                doc.setFont('helvetica', 'bold');
                doc.text('💡 Quick Tips', 20, yPos);
                
                yPos += 8;
                
                const tips = [
                    '• Use your registered email and password to login',
                    '• Guide dashboard provides full tour management',
                    '• Update your availability status regularly',
                    '• Check bookings and customer messages daily'
                ];
                
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(60, 60, 60);
                
                tips.forEach((tip) => {
                    doc.text(tip, 25, yPos + 5);
                    yPos += 8;
                });
                
                yPos += 10;
                
                // Footer
                doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                doc.rect(0, pageHeight - 20, pageWidth, 20, 'F');
                
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                doc.text('© ' + now.getFullYear() + ' Paradise Island Travel. All rights reserved.', pageWidth / 2, pageHeight - 8, { align: 'center' });
                
                doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
                doc.rect(0, pageHeight - 20, pageWidth, 1.5, 'F');
                
                if (mode === 'download') {
                    const fileName = `Guide_Login_${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}.pdf`;
                    doc.save(fileName);
                    showToast('✅ PDF Downloaded Successfully!', 'success');
                } else if (mode === 'preview') {
                    const pdfOutput = doc.output('datauristring');
                    showPdfPreview(pdfOutput, email, dateStr);
                }
                
            } catch (error) {
                console.error('PDF Generation Error:', error);
                showToast('❌ Error generating PDF. Please try again.', 'error');
            }
        }

        // ============================================================
        // PDF PREVIEW
        // ============================================================
        function showPdfPreview(pdfDataUrl, email, dateStr) {
            const modal = document.getElementById('pdfModal');
            const body = document.getElementById('pdfPreviewBody');
            document.getElementById('pdfModalTitle').textContent = 'PDF Preview - Guide Login';
            
            body.innerHTML = `
                <div class="pdf-preview-content">
                    <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #e2e8f0;padding-bottom:16px;margin-bottom:20px;">
                        <div style="font-size:20px;font-weight:700;color:#0a5c4a;"><i class="fas fa-file-pdf" style="color:#dc2626;"></i> Guide Login PDF</div>
                        <span style="color:#7f8c8d;font-size:13px;"><i class="far fa-calendar-alt"></i> ${dateStr}</span>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                        <div style="background:white;padding:14px 18px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                            <label style="display:block;font-size:11px;color:#7f8c8d;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Document Type</label>
                            <div style="font-size:15px;font-weight:600;color:#1a202c;margin-top:4px;">Guide Login Documentation</div>
                        </div>
                        <div style="background:white;padding:14px 18px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                            <label style="display:block;font-size:11px;color:#7f8c8d;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Role</label>
                            <div style="font-size:15px;font-weight:600;color:#1a202c;margin-top:4px;"><span style="display:inline-block;background:#0a5c4a;color:white;padding:2px 16px;border-radius:50px;font-size:13px;font-weight:600;">🧭 Guide</span></div>
                        </div>
                        <div style="background:white;padding:14px 18px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                            <label style="display:block;font-size:11px;color:#7f8c8d;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Email Address</label>
                            <div style="font-size:15px;font-weight:600;color:#1a202c;margin-top:4px;font-weight:500;color:#0a5c4a;">${email}</div>
                        </div>
                        <div style="background:white;padding:14px 18px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                            <label style="display:block;font-size:11px;color:#7f8c8d;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Generated</label>
                            <div style="font-size:15px;font-weight:600;color:#1a202c;margin-top:4px;">${dateStr}</div>
                        </div>
                    </div>
                    
                    <div style="margin-top:16px;">
                        <h4 style="font-size:14px;color:#0a5c4a;margin-bottom:10px;"><i class="fas fa-list-check"></i> Guide Features</h4>
                        <ul style="list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:0;">
                            <li style="padding:8px 12px;background:white;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#0a5c4a;"></i> Manage Tour Packages</li>
                            <li style="padding:8px 12px;background:white;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#0a5c4a;"></i> View Customer Bookings</li>
                            <li style="padding:8px 12px;background:white;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#0a5c4a;"></i> Schedule Management</li>
                            <li style="padding:8px 12px;background:white;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#0a5c4a;"></i> Earnings & Payments</li>
                            <li style="padding:8px 12px;background:white;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#0a5c4a;"></i> Mobile-Friendly Dashboard</li>
                            <li style="padding:8px 12px;background:white;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;"><i class="fas fa-check-circle" style="color:#0a5c4a;"></i> Real-time Updates</li>
                        </ul>
                    </div>
                    
                    <div style="margin-top:20px;padding:16px;background:#f0fdf4;border-radius:10px;border-left:4px solid #0a5c4a;">
                        <p style="font-size:13px;color:#1a202c;margin:0;">
                            <i class="fas fa-info-circle" style="color:#0a5c4a;"></i>
                            <strong>Tip:</strong> This PDF provides a complete guide to the guide login portal. 
                            Download it for offline reference.
                        </p>
                    </div>
                    
                    <div style="margin-top:20px;text-align:center;">
                        <button onclick="generatePDF('download')" style="padding:12px 32px;background:#dc2626;color:white;border:none;border-radius:10px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                        <button onclick="window.open('${pdfDataUrl}', '_blank')" style="padding:12px 32px;background:#0a5c4a;color:white;border:none;border-radius:10px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;margin-left:10px;">
                            <i class="fas fa-external-link-alt"></i> Open PDF
                        </button>
                    </div>
                </div>
            `;
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // ============================================================
        // CLOSE PDF MODAL
        // ============================================================
        function closePdfModal() {
            document.getElementById('pdfModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        document.getElementById('pdfModal').addEventListener('click', function(e) {
            if (e.target === this) closePdfModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePdfModal();
        });

        // ============================================================
        // TOAST NOTIFICATION
        // ============================================================
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toastMessage');
            toastMsg.textContent = message;
            toast.className = 'toast show';
            if (type === 'error') {
                toast.classList.add('error');
            } else {
                toast.classList.remove('error');
            }
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }

        // ============================================================
        // KEYBOARD SHORTCUT: Ctrl+P
        // ============================================================
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                generatePDF('download');
            }
        });

        console.log('✅ PDF Generator Loaded Successfully!');
        console.log('📄 Click "PDF" on any booking card to generate a PDF.');
        console.log('⌨️ Press Ctrl+P to download the guide PDF.');
    </script>

</body>
</html>