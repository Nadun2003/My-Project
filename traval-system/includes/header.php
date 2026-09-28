<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Paradise Island Travel'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../config/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <header class="header">
        <div style="display:flex;align-items:center;gap:16px;">
            <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-umbrella-beach"></i></div>
                <span class="logo-text">Paradise<span>Travel</span></span>
            </div>
        </div>
        
        <div class="header-user">
            <span class="user-name">
                <i class="fas fa-user-circle"></i> 
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>
            </span>
            <a href="../logout.php" class="logout-link" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <?php
            $role = $_SESSION['user_role'] ?? '';
            $current = basename($_SERVER['PHP_SELF']);
            
            // ============================================
            // ADMIN MENU
            // ============================================
            if ($role === 'admin') {
                echo '<div class="menu-section">Main</div>';
                echo '<li><a href="dashboard.php" class="' . ($current == 'dashboard.php' ? 'active' : '') . '"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>';
                
                echo '<div class="menu-section">Management</div>';
                echo '<li><a href="packages.php" class="' . ($current == 'packages.php' ? 'active' : '') . '"><i class="fas fa-box"></i> Packages</a></li>';
                echo '<li><a href="vehicles.php" class="' . ($current == 'vehicles.php' ? 'active' : '') . '"><i class="fas fa-car"></i> Vehicles</a></li>';
                echo '<li><a href="customers.php" class="' . ($current == 'customers.php' ? 'active' : '') . '"><i class="fas fa-users"></i> Customers</a></li>';
                echo '<li><a href="guides.php" class="' . ($current == 'guides.php' ? 'active' : '') . '"><i class="fas fa-hiking"></i> Guides</a></li>';
                echo '<li><a href="hotels.php" class="' . ($current == 'hotels.php' ? 'active' : '') . '"><i class="fas fa-hotel"></i> Hotels</a></li>';
                
                echo '<div class="menu-section">Operations</div>';
                echo '<li><a href="bookings.php" class="' . ($current == 'bookings.php' ? 'active' : '') . '"><i class="fas fa-calendar-check"></i> Bookings</a></li>';
                echo '<li><a href="payments.php" class="' . ($current == 'payments.php' ? 'active' : '') . '"><i class="fas fa-credit-card"></i> Payments</a></li>';
                echo '<li><a href="reports.php" class="' . ($current == 'reports.php' ? 'active' : '') . '"><i class="fas fa-chart-bar"></i> Reports</a></li>';
            
            // ============================================
            // CUSTOMER MENU (Vehicle නැහැ)
            // ============================================
            } elseif ($role === 'customer') {
                echo '<div class="menu-section">Dashboard</div>';
                echo '<li><a href="dashboard.php" class="' . ($current == 'dashboard.php' ? 'active' : '') . '"><i class="fas fa-tachometer-alt"></i> My Dashboard</a></li>';
                
                echo '<div class="menu-section">Travel</div>';
                echo '<li><a href="packages.php" class="' . ($current == 'packages.php' ? 'active' : '') . '"><i class="fas fa-box"></i> Browse Packages</a></li>';
                echo '<li><a href="bookings.php" class="' . ($current == 'bookings.php' ? 'active' : '') . '"><i class="fas fa-suitcase"></i> My Bookings</a></li>';
                echo '<li><a href="payments.php" class="' . ($current == 'payments.php' ? 'active' : '') . '"><i class="fas fa-credit-card"></i> Payments</a></li>';
                echo '<li><a href="feedback.php" class="' . ($current == 'feedback.php' ? 'active' : '') . '"><i class="fas fa-star"></i> Feedback</a></li>';
                
                echo '<div class="menu-section">Account</div>';
                echo '<li><a href="profile.php" class="' . ($current == 'profile.php' ? 'active' : '') . '"><i class="fas fa-user-edit"></i> My Profile</a></li>';
            
            // ============================================
            // GUIDE MENU
            // ============================================
            } elseif ($role === 'guide') {
                echo '<div class="menu-section">Dashboard</div>';
                echo '<li><a href="dashboard.php" class="' . ($current == 'dashboard.php' ? 'active' : '') . '"><i class="fas fa-tachometer-alt"></i> My Dashboard</a></li>';
                
                echo '<div class="menu-section">Work</div>';
                echo '<li><a href="bookings.php" class="' . ($current == 'bookings.php' ? 'active' : '') . '"><i class="fas fa-route"></i> My Assignments</a></li>';
                echo '<li><a href="reports.php" class="' . ($current == 'reports.php' ? 'active' : '') . '"><i class="fas fa-chart-bar"></i> Reports</a></li>';
                
                echo '<div class="menu-section">Account</div>';
                echo '<li><a href="profile.php" class="' . ($current == 'profile.php' ? 'active' : '') . '"><i class="fas fa-user-edit"></i> My Profile</a></li>';
            
            // ============================================
            // HOTEL MENU
            // ============================================
            } elseif ($role === 'hotel') {
                echo '<div class="menu-section">Dashboard</div>';
                echo '<li><a href="dashboard.php" class="' . ($current == 'dashboard.php' ? 'active' : '') . '"><i class="fas fa-tachometer-alt"></i> My Dashboard</a></li>';
                
                echo '<div class="menu-section">Operations</div>';
                echo '<li><a href="bookings.php" class="' . ($current == 'bookings.php' ? 'active' : '') . '"><i class="fas fa-bed"></i> Hotel Bookings</a></li>';
                echo '<li><a href="reports.php" class="' . ($current == 'reports.php' ? 'active' : '') . '"><i class="fas fa-chart-line"></i> Revenue Reports</a></li>';
                
                echo '<div class="menu-section">Account</div>';
                echo '<li><a href="profile.php" class="' . ($current == 'profile.php' ? 'active' : '') . '"><i class="fas fa-building"></i> Hotel Profile</a></li>';
            }
            ?>
        </ul>
    </aside>

    <div class="main-content">
        <div id="toast" class="toast-notification"></div>