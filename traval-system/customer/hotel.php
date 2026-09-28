<?php
require_once 'config/database.php';

// ============================================
// CHECK USER ROLE - HOTEL
// ============================================
if (!isLoggedIn() || $_SESSION['user_role'] !== 'hotel') {
    redirect('login.php');
}

$page_title = 'Hotel Dashboard';
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'dashboard';

// ============================================
// GET HOTEL PROFILE
// ============================================
$stmt = $pdo->prepare("SELECT * FROM hotel_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    $stmt = $pdo->prepare("INSERT INTO hotel_profiles (user_id, hotel_name) VALUES (?, ?)");
    $stmt->execute([$user_id, $_SESSION['user_name']]);
    $profile = [
        'hotel_name' => $_SESSION['user_name'],
        'location' => '',
        'stars' => 3,
        'room_count' => 0,
        'price_per_night' => 0,
        'amenities' => '',
        'check_in_time' => '',
        'check_out_time' => ''
    ];
}

// ============================================
// HANDLE PROFILE UPDATE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $hotel_name = trim($_POST['hotel_name']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $stars = $_POST['stars'];
    $room_count = $_POST['room_count'];
    $price_per_night = $_POST['price_per_night'];
    $amenities = trim($_POST['amenities']);
    $check_in_time = trim($_POST['check_in_time']);
    $check_out_time = trim($_POST['check_out_time']);
    
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$hotel_name, $phone, $user_id]);
    
    $stmt = $pdo->prepare("UPDATE hotel_profiles SET hotel_name = ?, location = ?, stars = ?, room_count = ?, price_per_night = ?, amenities = ?, check_in_time = ?, check_out_time = ? WHERE user_id = ?");
    $stmt->execute([$hotel_name, $location, $stars, $room_count, $price_per_night, $amenities, $check_in_time, $check_out_time, $user_id]);
    
    if (!empty($_POST['new_password']) && !empty($_POST['confirm_password']) && $_POST['new_password'] === $_POST['confirm_password']) {
        $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
    }
    
    $_SESSION['user_name'] = $hotel_name;
    toast("Profile updated!");
    redirect('hotel.php?action=profile');
}

// ============================================
// GET STATISTICS
// ============================================
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE hotel_id = ?");
$stmt->execute([$user_id]);
$total_bookings = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE hotel_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$confirmed = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE hotel_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM bookings WHERE hotel_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$total_amount = $stmt->fetch()['total'] ?? 0;
$revenue = $total_amount * 0.35;

// ============================================
// GET RECENT BOOKINGS
// ============================================
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email, u.phone,
                              p.name as package_name
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.hotel_id = ? 
                       ORDER BY b.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent = $stmt->fetchAll();

// ============================================
// GET ALL BOOKINGS
// ============================================
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email, u.phone,
                              p.name as package_name, p.destination
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.hotel_id = ? 
                       ORDER BY b.start_date DESC");
$stmt->execute([$user_id]);
$all_bookings = $stmt->fetchAll();

// ============================================
// GET CONFIRMED BOOKINGS (Reports)
// ============================================
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name 
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       WHERE b.hotel_id = ? AND b.status = 'confirmed'
                       ORDER BY b.start_date DESC");
$stmt->execute([$user_id]);
$revenue_bookings = $stmt->fetchAll();

$total_revenue = 0;
$total_nights = 0;
$total_guests = 0;
foreach ($revenue_bookings as $b) {
    $nights = ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400);
    $total_nights += $nights;
    $total_guests += ($b['adults'] + $b['children']);
    $total_revenue += ($b['total_amount'] * 0.35);
}

include 'includes/header.php';
?>

<!-- ============================================
     HOTEL DASHBOARD - ACTION BASED VIEW
     ============================================ -->

<?php if ($action === 'dashboard'): ?>

<!-- WELCOME CARD -->
<div class="welcome-card">
    <div class="welcome-content">
        <div>
            <h1><?php echo getGreeting(); ?>, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 🏨</h1>
            <p>Manage your hotel bookings and revenue</p>
            <div style="margin-top:10px;">
                <span class="badge badge-success" style="font-size:13px; padding:6px 16px;"><?php echo str_repeat('★', $profile['stars']); ?> <?php echo $profile['stars']; ?> Star Hotel</span>
                <span class="badge badge-info" style="font-size:13px; padding:6px 16px; margin-left:10px;">🏠 <?php echo $profile['room_count']; ?> Rooms</span>
            </div>
        </div>
        <div class="welcome-icon"><i class="fas fa-hotel"></i></div>
    </div>
</div>

<!-- STATS CARDS -->
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon primary"><i class="fas fa-bed"></i></div><div class="stat-info"><h3><?php echo $total_bookings; ?></h3><p>Total Bookings</p></div><div class="stat-trend">📋 All</div></div>
    <div class="stat-card"><div class="stat-icon success"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3><?php echo $confirmed; ?></h3><p>Confirmed Bookings</p></div><div class="stat-trend up">✅ Active</div></div>
    <div class="stat-card"><div class="stat-icon info"><i class="fas fa-flag-checkered"></i></div><div class="stat-info"><h3><?php echo $completed; ?></h3><p>Completed Bookings</p></div><div class="stat-trend up">🏁 Done</div></div>
    <div class="stat-card"><div class="stat-icon secondary"><i class="fas fa-dollar-sign"></i></div><div class="stat-info"><h3><?php echo formatCurrency($revenue); ?></h3><p>Revenue (35% Commission)</p></div><div class="stat-trend">💰 Total</div></div>
</div>

<!-- HOTEL INFO -->
<div class="card" style="border-left:4px solid var(--primary); margin-bottom:24px;">
    <div class="card-header">
        <h3><i class="fas fa-hotel"></i> Hotel Information</h3>
        <a href="hotel.php?action=profile" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
            <div><strong>Name:</strong> <?php echo htmlspecialchars($profile['hotel_name']); ?></div>
            <div><strong>Location:</strong> <?php echo htmlspecialchars($profile['location'] ?: 'Not specified'); ?></div>
            <div><strong>Stars:</strong> <?php echo str_repeat('★', $profile['stars']); ?></div>
            <div><strong>Rooms:</strong> <?php echo $profile['room_count'] ?: 'Not specified'; ?></div>
            <div><strong>Price/Night:</strong> <?php echo $profile['price_per_night'] ? formatCurrency($profile['price_per_night']) : 'Not specified'; ?></div>
            <div><strong>Check-in:</strong> <?php echo htmlspecialchars($profile['check_in_time'] ?: 'Not specified'); ?></div>
            <div><strong>Check-out:</strong> <?php echo htmlspecialchars($profile['check_out_time'] ?: 'Not specified'); ?></div>
        </div>
        <?php if ($profile['amenities']): ?><div style="margin-top:10px;"><strong>Amenities:</strong> <?php echo htmlspecialchars($profile['amenities']); ?></div><?php endif; ?>
    </div>
</div>

<!-- RECENT BOOKINGS -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clock"></i> Recent Bookings</h3>
        <a href="hotel.php?action=bookings" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recent)): ?>
            <div style="text-align:center; padding:40px; color:var(--gray);"><i class="fas fa-bed" style="font-size:48px; display:block; margin-bottom:15px;"></i><p>No bookings yet.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Booking No</th><th>Customer</th><th>Package</th><th>Check In</th><th>Guests</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $b): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['booking_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['package_name']); ?></td>
                            <td><?php echo date('M d', strtotime($b['start_date'])); ?></td>
                            <td><?php echo $b['adults'] + $b['children']; ?> pax</td>
                            <td><span class="badge badge-<?php echo getStatusBadge($b['status']); ?>"><?php echo ucfirst($b['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- QUICK STATS -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-chart-simple"></i> Performance Summary</h3></div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--primary);"><?php echo $total_bookings; ?></div><div class="stat-label">Total Bookings</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--success);"><?php echo $confirmed; ?></div><div class="stat-label">Confirmed</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--secondary);"><?php echo $profile['stars']; ?>★</div><div class="stat-label">Star Rating</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--info);"><?php echo $profile['room_count'] ?: 0; ?></div><div class="stat-label">Rooms</div></div>
        </div>
    </div>
</div>

<?php elseif ($action === 'bookings'): ?>

<!-- ============================================
     HOTEL BOOKINGS
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-bed"></i> Hotel Bookings</h3>
        <span style="font-size:13px; color:var(--gray);">Total: <?php echo count($all_bookings); ?> bookings</span>
    </div>
    <div class="card-body">
        <?php if (empty($all_bookings)): ?>
            <div style="text-align:center; padding:60px; color:var(--gray);"><i class="fas fa-bed" style="font-size:48px; display:block; margin-bottom:15px;"></i><p>No bookings found.</p></div>
        <?php else: ?>
            <?php foreach ($all_bookings as $b): ?>
            <div class="booking-card" style="border-left-color:<?php echo $b['status'] == 'confirmed' ? 'var(--success)' : ($b['status'] == 'pending' ? 'var(--warning)' : ($b['status'] == 'completed' ? 'var(--info)' : 'var(--danger)')); ?>;">
                <div class="booking-header">
                    <div>
                        <span class="booking-no"><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($b['booking_no']); ?></span>
                        <span class="badge badge-<?php echo getStatusBadge($b['status']); ?>"><?php echo ucfirst($b['status']); ?></span>
                    </div>
                    <div style="font-size:13px; color:var(--gray);"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($b['start_date'])); ?></div>
                </div>
                <div style="margin:8px 0;"><strong><?php echo htmlspecialchars($b['package_name']); ?></strong> <span style="font-size:13px; color:var(--gray);">- <?php echo htmlspecialchars($b['destination']); ?></span></div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:8px; font-size:13px;">
                    <div><strong>Customer:</strong> <?php echo htmlspecialchars($b['customer_name']); ?></div>
                    <div><strong>Contact:</strong> <?php echo htmlspecialchars($b['phone']); ?></div>
                    <div><strong>Guests:</strong> <?php echo $b['adults']; ?> Adults, <?php echo $b['children']; ?> Children</div>
                    <div><strong>Check-in:</strong> <?php echo date('M d', strtotime($b['start_date'])); ?></div>
                    <div><strong>Nights:</strong> <?php echo ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400); ?></div>
                </div>
                <div class="booking-actions">
                    <a href="hotel.php?action=view_booking&id=<?php echo $b['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View</a>
                    <a href="generate_pdf.php?booking_id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'view_booking'): 
    $booking_id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email, u.phone,
                                  p.name as package_name, p.destination, p.duration_days, p.duration_nights
                           FROM bookings b 
                           JOIN users u ON b.customer_id = u.id 
                           JOIN packages p ON b.package_id = p.id 
                           WHERE b.id = ? AND b.hotel_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $view = $stmt->fetch();
    if (!$view) { redirect('hotel.php?action=bookings'); }
?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-eye"></i> Booking Details - <?php echo htmlspecialchars($view['booking_no']); ?></h3>
        <a href="hotel.php?action=bookings" class="btn btn-primary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px;">
            <div style="background:var(--light); padding:15px; border-radius:10px;">
                <h4 style="color:var(--primary);">👤 Customer</h4>
                <div><strong>Name:</strong> <?php echo htmlspecialchars($view['customer_name']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($view['email']); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($view['phone']); ?></div>
            </div>
            <div style="background:var(--light); padding:15px; border-radius:10px;">
                <h4 style="color:var(--primary);">📦 Package</h4>
                <div><strong>Package:</strong> <?php echo htmlspecialchars($view['package_name']); ?></div>
                <div><strong>Destination:</strong> <?php echo htmlspecialchars($view['destination']); ?></div>
            </div>
            <div style="background:var(--light); padding:15px; border-radius:10px;">
                <h4 style="color:var(--primary);">🗓️ Stay Details</h4>
                <div><strong>Check-in:</strong> <?php echo date('F d, Y', strtotime($view['start_date'])); ?></div>
                <div><strong>Check-out:</strong> <?php echo date('F d, Y', strtotime($view['end_date'])); ?></div>
                <div><strong>Guests:</strong> <?php echo $view['adults']; ?> Adults, <?php echo $view['children']; ?> Children</div>
                <div><strong>Status:</strong> <span class="badge badge-<?php echo getStatusBadge($view['status']); ?>"><?php echo ucfirst($view['status']); ?></span></div>
            </div>
        </div>
        <?php if ($view['special_requests']): ?>
        <div style="background:#fff3cd; padding:15px; border-radius:10px; margin-top:15px;">
            <h4 style="color:#856404;">📝 Special Requests</h4>
            <div><?php echo nl2br(htmlspecialchars($view['special_requests'])); ?></div>
        </div>
        <?php endif; ?>
        <div style="margin-top:20px;">
            <a href="generate_pdf.php?booking_id=<?php echo $view['id']; ?>" target="_blank" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Download PDF</a>
            <a href="hotel.php?action=bookings" class="btn" style="background:#e2e8f0;">Back</a>
        </div>
    </div>
</div>

<?php elseif ($action === 'reports'): ?>
<!-- ============================================
     REVENUE REPORTS
     ============================================ -->
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon primary"><i class="fas fa-dollar-sign"></i></div><div class="stat-info"><h3><?php echo formatCurrency($total_revenue); ?></h3><p>Total Revenue (35%)</p></div></div>
    <div class="stat-card"><div class="stat-icon success"><i class="fas fa-bed"></i></div><div class="stat-info"><h3><?php echo count($revenue_bookings); ?></h3><p>Confirmed Bookings</p></div></div>
    <div class="stat-card"><div class="stat-icon info"><i class="fas fa-moon"></i></div><div class="stat-info"><h3><?php echo $total_nights; ?></h3><p>Total Room Nights</p></div></div>
    <div class="stat-card"><div class="stat-icon secondary"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?php echo $total_guests; ?></h3><p>Total Guests</p></div></div>
</div>

<!-- HOTEL INFO -->
<div class="card" style="border-left:4px solid var(--primary); margin-bottom:20px;">
    <div class="card-header"><h3><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($profile['hotel_name']); ?></h3></div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px; font-size:14px;">
            <div><strong>Location:</strong> <?php echo htmlspecialchars($profile['location'] ?: 'Not specified'); ?></div>
            <div><strong>Rating:</strong> <?php echo str_repeat('★', $profile['stars']); ?></div>
            <div><strong>Rooms:</strong> <?php echo $profile['room_count'] ?: 'N/A'; ?></div>
            <div><strong>Price/Night:</strong> <?php echo $profile['price_per_night'] ? formatCurrency($profile['price_per_night']) : 'N/A'; ?></div>
        </div>
    </div>
</div>

<!-- REVENUE REPORT -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-line"></i> Revenue Report</h3>
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="card-body">
        <?php if (empty($revenue_bookings)): ?>
            <p style="text-align:center; padding:40px; color:var(--gray);"><i class="fas fa-chart-line" style="font-size:48px; display:block; margin-bottom:15px;"></i>No booking data available.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Booking No</th><th>Customer</th><th>Check In</th><th>Check Out</th><th>Nights</th><th>Guests</th><th>Booking Amount</th><th>Commission (35%)</th></tr></thead>
                    <tbody>
                        <?php foreach ($revenue_bookings as $b): 
                            $nights = ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400);
                            $commission = $b['total_amount'] * 0.35;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['booking_no']); ?></td>
                            <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($b['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($b['end_date'])); ?></td>
                            <td><?php echo $nights; ?></td>
                            <td><?php echo $b['adults'] + $b['children']; ?></td>
                            <td><?php echo formatCurrency($b['total_amount']); ?></td>
                            <td><?php echo formatCurrency($commission); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--light); font-weight:bold; border-top:2px solid var(--primary);"><td colspan="7" style="text-align:right;">Total Commission:</td><td><?php echo formatCurrency($total_revenue); ?></td></tr>
                        <tr style="background:var(--primary-light); font-weight:bold;"><td colspan="7" style="text-align:right;">Total Bookings:</td><td><?php echo count($revenue_bookings); ?></td></tr>
                        <tr style="background:var(--primary-light); font-weight:bold;"><td colspan="7" style="text-align:right;">Total Room Nights:</td><td><?php echo $total_nights; ?></td></tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'profile'): ?>
<!-- ============================================
     HOTEL PROFILE
     ============================================ -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-building"></i> Hotel Profile</h3></div>
    <div class="card-body">
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Hotel Name *</label><input type="text" name="hotel_name" class="form-control" value="<?php echo htmlspecialchars($profile['hotel_name']); ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>"></div>
                <div class="form-group"><label>Location</label><input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($profile['location']); ?>" placeholder="City, Area"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Star Rating</label>
                    <select name="stars" class="form-control">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $profile['stars'] == $i ? 'selected' : ''; ?>><?php echo str_repeat('★', $i); ?> <?php echo $i; ?> Star<?php echo $i>1?'s':''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group"><label>Number of Rooms</label><input type="number" name="room_count" class="form-control" value="<?php echo $profile['room_count']; ?>" min="0"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Price Per Night (LKR)</label><input type="number" step="0.01" name="price_per_night" class="form-control" value="<?php echo $profile['price_per_night']; ?>" min="0"></div>
                <div class="form-group"><label>Check-in Time</label><input type="text" name="check_in_time" class="form-control" value="<?php echo htmlspecialchars($profile['check_in_time']); ?>" placeholder="2:00 PM"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Check-out Time</label><input type="text" name="check_out_time" class="form-control" value="<?php echo htmlspecialchars($profile['check_out_time']); ?>" placeholder="12:00 PM"></div>
            </div>
            <div class="form-group"><label>Amenities</label><textarea name="amenities" class="form-control" rows="3" placeholder="WiFi, Swimming Pool, Restaurant, Parking, Spa, Gym"><?php echo htmlspecialchars($profile['amenities']); ?></textarea></div>
            
            <div style="margin:25px 0; border-top:2px solid var(--gray-light); padding-top:20px;">
                <h4 style="color:var(--primary);"><i class="fas fa-key"></i> Change Password</h4>
                <p style="font-size:13px; color:var(--gray); margin-bottom:15px;">Leave blank to keep current</p>
                <div class="form-row">
                    <div class="form-group"><label>New Password</label><input type="password" name="new_password" class="form-control" placeholder="Enter new password" minlength="6"></div>
                    <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password"></div>
                </div>
            </div>
            
            <input type="hidden" name="update_profile" value="1">
            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                <a href="hotel.php?action=dashboard" class="btn" style="background:#e2e8f0;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- PROFILE PREVIEW -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-id-card"></i> Hotel Preview</h3></div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
            <div><strong>Hotel:</strong> <?php echo htmlspecialchars($profile['hotel_name']); ?></div>
            <div><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></div>
            <div><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
            <div><strong>Location:</strong> <?php echo htmlspecialchars($profile['location'] ?: 'Not specified'); ?></div>
            <div><strong>Rating:</strong> <?php echo str_repeat('★', $profile['stars']); ?></div>
            <div><strong>Rooms:</strong> <?php echo $profile['room_count'] ?: 'Not specified'; ?></div>
            <div><strong>Price/Night:</strong> <?php echo $profile['price_per_night'] ? formatCurrency($profile['price_per_night']) : 'Not specified'; ?></div>
            <div><strong>Check-in:</strong> <?php echo htmlspecialchars($profile['check_in_time'] ?: 'Not specified'); ?></div>
            <div><strong>Check-out:</strong> <?php echo htmlspecialchars($profile['check_out_time'] ?: 'Not specified'); ?></div>
        </div>
        <?php if ($profile['amenities']): ?><div style="margin-top:10px;"><strong>Amenities:</strong> <?php echo htmlspecialchars($profile['amenities']); ?></div><?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>