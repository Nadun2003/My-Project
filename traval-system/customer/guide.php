<?php
require_once 'config/database.php';

// ============================================
// CHECK USER ROLE - GUIDE
// ============================================
if (!isLoggedIn() || $_SESSION['user_role'] !== 'guide') {
    redirect('login.php');
}

$page_title = 'Guide Dashboard';
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'dashboard';

// ============================================
// GET GUIDE PROFILE
// ============================================
$stmt = $pdo->prepare("SELECT * FROM guide_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    $stmt = $pdo->prepare("INSERT INTO guide_profiles (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $profile = [
        'experience_years' => 0,
        'languages' => '',
        'specialization' => '',
        'hourly_rate' => 0,
        'rating' => 0,
        'total_reviews' => 0,
        'availability' => 'available'
    ];
}

// ============================================
// HANDLE AVAILABILITY UPDATE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    $stmt = $pdo->prepare("UPDATE guide_profiles SET availability = ? WHERE user_id = ?");
    $stmt->execute([$_POST['availability'], $user_id]);
    toast("Availability updated to " . ucfirst($_POST['availability']));
    redirect('guide.php?action=dashboard');
}

// ============================================
// HANDLE PROFILE UPDATE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $experience_years = $_POST['experience_years'];
    $languages = trim($_POST['languages']);
    $specialization = trim($_POST['specialization']);
    $hourly_rate = $_POST['hourly_rate'];
    $availability = $_POST['availability'];
    
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $user_id]);
    
    $stmt = $pdo->prepare("UPDATE guide_profiles SET experience_years = ?, languages = ?, specialization = ?, hourly_rate = ?, availability = ? WHERE user_id = ?");
    $stmt->execute([$experience_years, $languages, $specialization, $hourly_rate, $availability, $user_id]);
    
    if (!empty($_POST['new_password']) && !empty($_POST['confirm_password']) && $_POST['new_password'] === $_POST['confirm_password']) {
        $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
    }
    
    $_SESSION['user_name'] = $name;
    toast("Profile updated!");
    redirect('guide.php?action=profile');
}

// ============================================
// GET STATISTICS
// ============================================
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE guide_id = ?");
$stmt->execute([$user_id]);
$total_assignments = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE guide_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$confirmed = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE guide_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT SUM(adults + children) as total FROM bookings WHERE guide_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_guests = $stmt->fetch()['total'] ?? 0;

// ============================================
// GET UPCOMING ASSIGNMENTS
// ============================================
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email, u.phone,
                              p.name as package_name, p.destination
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.guide_id = ? AND b.start_date >= CURDATE() AND b.status = 'confirmed'
                       ORDER BY b.start_date ASC LIMIT 5");
$stmt->execute([$user_id]);
$upcoming = $stmt->fetchAll();

// ============================================
// GET ALL ASSIGNMENTS
// ============================================
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email, u.phone,
                              p.name as package_name, p.destination,
                              hp.hotel_name
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id
                       WHERE b.guide_id = ? 
                       ORDER BY b.start_date DESC");
$stmt->execute([$user_id]);
$all_bookings = $stmt->fetchAll();

// ============================================
// GET COMPLETED TOURS (Reports)
// ============================================
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name,
                              p.name as package_name, p.destination
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.guide_id = ? AND b.status = 'completed'
                       ORDER BY b.end_date DESC");
$stmt->execute([$user_id]);
$completed_tours = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- ============================================
     GUIDE DASHBOARD - ACTION BASED VIEW
     ============================================ -->

<?php if ($action === 'dashboard'): ?>

<!-- WELCOME CARD -->
<div class="welcome-card">
    <div class="welcome-content">
        <div>
            <h1><?php echo getGreeting(); ?>, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 🧭</h1>
            <p>Manage your tour assignments and guide services</p>
            <div style="margin-top:10px;">
                <span class="badge badge-<?php echo $profile['availability'] == 'available' ? 'success' : ($profile['availability'] == 'busy' ? 'warning' : 'danger'); ?>" style="font-size:13px; padding:6px 16px;">
                    <?php echo ucfirst($profile['availability']); ?>
                </span>
                <span class="badge badge-info" style="font-size:13px; padding:6px 16px; margin-left:10px;">
                    ⭐ <?php echo number_format($profile['rating'] ?? 0, 1); ?> / 5 (<?php echo $profile['total_reviews'] ?? 0; ?> reviews)
                </span>
            </div>
        </div>
        <div class="welcome-icon"><i class="fas fa-hiking"></i></div>
    </div>
</div>

<!-- STATS CARDS -->
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon primary"><i class="fas fa-route"></i></div><div class="stat-info"><h3><?php echo $total_assignments; ?></h3><p>Total Assignments</p></div><div class="stat-trend">📋 All</div></div>
    <div class="stat-card"><div class="stat-icon success"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3><?php echo $confirmed; ?></h3><p>Confirmed Tours</p></div><div class="stat-trend up">✅ Active</div></div>
    <div class="stat-card"><div class="stat-icon info"><i class="fas fa-flag-checkered"></i></div><div class="stat-info"><h3><?php echo $completed; ?></h3><p>Completed Tours</p></div><div class="stat-trend up">🏁 Done</div></div>
    <div class="stat-card"><div class="stat-icon secondary"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?php echo $total_guests; ?></h3><p>Guests Served</p></div><div class="stat-trend">👤 Total</div></div>
</div>

<!-- AVAILABILITY UPDATE -->
<div class="card" style="border-left:4px solid var(--primary); margin-bottom:24px;">
    <div class="card-header">
        <h3><i class="fas fa-toggle-on"></i> Availability Status</h3>
    </div>
    <div class="card-body">
        <form method="POST" style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
            <input type="hidden" name="update_availability" value="1">
            <select name="availability" class="form-control" style="width:auto; min-width:180px;">
                <option value="available" <?php echo ($profile['availability'] ?? 'available') == 'available' ? 'selected' : ''; ?>>✅ Available for Tours</option>
                <option value="busy" <?php echo ($profile['availability'] ?? 'available') == 'busy' ? 'selected' : ''; ?>>🔴 Busy - Not Available</option>
                <option value="off" <?php echo ($profile['availability'] ?? 'available') == 'off' ? 'selected' : ''; ?>>⛔ Off Duty</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Update Status</button>
            <span style="font-size:13px; color:var(--gray);">Current: <strong style="color:var(--primary);"><?php echo ucfirst($profile['availability'] ?? 'available'); ?></strong></span>
        </form>
    </div>
</div>

<!-- UPCOMING ASSIGNMENTS -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar"></i> Upcoming Assignments</h3>
        <a href="guide.php?action=bookings" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($upcoming)): ?>
            <div style="text-align:center; padding:40px; color:var(--gray);">
                <i class="fas fa-calendar-check" style="font-size:48px; display:block; margin-bottom:15px;"></i>
                <p>No upcoming assignments.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Booking No</th><th>Customer</th><th>Package</th><th>Start Date</th><th>Guests</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($upcoming as $b): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['booking_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['package_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($b['start_date'])); ?></td>
                            <td><?php echo $b['adults'] + $b['children']; ?> pax</td>
                            <td><a href="guide.php?action=view_booking&id=<?php echo $b['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- PERFORMANCE SUMMARY -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-simple"></i> Performance Summary</h3>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:15px;">
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--primary);"><?php echo $profile['experience_years'] ?? 0; ?></div><div class="stat-label">Years Experience</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--secondary);"><?php echo formatCurrency($profile['hourly_rate'] ?? 0); ?></div><div class="stat-label">Hourly Rate</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--info);"><?php echo htmlspecialchars($profile['languages'] ?: '-'); ?></div><div class="stat-label">Languages</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--success);"><?php echo htmlspecialchars($profile['specialization'] ?: '-'); ?></div><div class="stat-label">Specialization</div></div>
        </div>
    </div>
</div>

<?php elseif ($action === 'bookings'): ?>

<!-- ============================================
     MY ASSIGNMENTS
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-route"></i> My Assignments</h3>
        <span style="font-size:13px; color:var(--gray);">Total: <?php echo count($all_bookings); ?> bookings</span>
    </div>
    <div class="card-body">
        <?php if (empty($all_bookings)): ?>
            <div style="text-align:center; padding:60px; color:var(--gray);">
                <i class="fas fa-route" style="font-size:48px; display:block; margin-bottom:15px;"></i>
                <p>You don't have any assignments yet.</p>
            </div>
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
                    <?php if ($b['hotel_name']): ?><div><strong>Hotel:</strong> <?php echo htmlspecialchars($b['hotel_name']); ?></div><?php endif; ?>
                </div>
                <div class="booking-actions">
                    <a href="guide.php?action=view_booking&id=<?php echo $b['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View</a>
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
                                  p.name as package_name, p.destination, p.duration_days, p.duration_nights,
                                  hp.hotel_name
                           FROM bookings b 
                           JOIN users u ON b.customer_id = u.id 
                           JOIN packages p ON b.package_id = p.id 
                           LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id
                           WHERE b.id = ? AND b.guide_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $view = $stmt->fetch();
    if (!$view) { redirect('guide.php?action=bookings'); }
?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-eye"></i> Booking Details - <?php echo htmlspecialchars($view['booking_no']); ?></h3>
        <a href="guide.php?action=bookings" class="btn btn-primary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
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
                <div><strong>Duration:</strong> <?php echo $view['duration_days']; ?>D / <?php echo $view['duration_nights']; ?>N</div>
            </div>
            <div style="background:var(--light); padding:15px; border-radius:10px;">
                <h4 style="color:var(--primary);">🗓️ Tour</h4>
                <div><strong>Start:</strong> <?php echo date('F d, Y', strtotime($view['start_date'])); ?></div>
                <div><strong>End:</strong> <?php echo date('F d, Y', strtotime($view['end_date'])); ?></div>
                <div><strong>Guests:</strong> <?php echo $view['adults']; ?> Adults, <?php echo $view['children']; ?> Children</div>
                <div><strong>Status:</strong> <span class="badge badge-<?php echo getStatusBadge($view['status']); ?>"><?php echo ucfirst($view['status']); ?></span></div>
            </div>
        </div>
        <?php if ($view['hotel_name']): ?>
        <div style="background:var(--light); padding:15px; border-radius:10px; margin-top:15px;">
            <h4 style="color:var(--primary);">🏨 Accommodation</h4>
            <div><strong>Hotel:</strong> <?php echo htmlspecialchars($view['hotel_name']); ?></div>
        </div>
        <?php endif; ?>
        <?php if ($view['special_requests']): ?>
        <div style="background:#fff3cd; padding:15px; border-radius:10px; margin-top:15px;">
            <h4 style="color:#856404;">📝 Special Requests</h4>
            <div><?php echo nl2br(htmlspecialchars($view['special_requests'])); ?></div>
        </div>
        <?php endif; ?>
        <div style="margin-top:20px;">
            <a href="generate_pdf.php?booking_id=<?php echo $view['id']; ?>" target="_blank" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Download PDF</a>
            <a href="guide.php?action=bookings" class="btn" style="background:#e2e8f0;">Back</a>
        </div>
    </div>
</div>

<?php elseif ($action === 'reports'): ?>
<!-- ============================================
     REPORTS
     ============================================ -->
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon success"><i class="fas fa-flag-checkered"></i></div><div class="stat-info"><h3><?php echo count($completed_tours); ?></h3><p>Completed Tours</p></div></div>
    <div class="stat-card"><div class="stat-icon primary"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?php echo $total_guests; ?></h3><p>Total Guests Served</p></div></div>
    <div class="stat-card"><div class="stat-icon secondary"><i class="fas fa-star"></i></div><div class="stat-info"><h3><?php echo number_format($profile['rating'] ?? 0, 1); ?> / 5</h3><p>Rating (<?php echo $profile['total_reviews'] ?? 0; ?> reviews)</p></div></div>
    <div class="stat-card"><div class="stat-icon info"><i class="fas fa-clock"></i></div><div class="stat-info"><h3><?php echo count($upcoming); ?></h3><p>Upcoming Tours</p></div></div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Tour History Report</h3>
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="card-body">
        <?php if (empty($completed_tours)): ?>
            <p style="text-align:center; padding:40px; color:var(--gray);"><i class="fas fa-flag-checkered" style="font-size:48px; display:block; margin-bottom:15px;"></i>No completed tours yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Booking No</th><th>Customer</th><th>Package</th><th>Tour Date</th><th>Guests</th></tr></thead>
                    <tbody>
                        <?php foreach ($completed_tours as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['booking_no']); ?></td>
                            <td><?php echo htmlspecialchars($t['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['package_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($t['start_date'])); ?></td>
                            <td><?php echo $t['adults'] + $t['children']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr style="background:var(--light); font-weight:bold;"><td colspan="4" style="text-align:right;">Total Tours:</td><td><?php echo count($completed_tours); ?></td></tr></tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- PERFORMANCE SUMMARY -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-chart-simple"></i> Performance Summary</h3></div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:15px;">
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--primary);"><?php echo $profile['experience_years'] ?? 0; ?></div><div class="stat-label">Years Experience</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--secondary);"><?php echo formatCurrency($profile['hourly_rate'] ?? 0); ?></div><div class="stat-label">Hourly Rate</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--info);"><?php echo htmlspecialchars($profile['languages'] ?: '-'); ?></div><div class="stat-label">Languages</div></div>
            <div class="quick-stat-item"><div class="stat-number" style="color:var(--success);"><?php echo htmlspecialchars($profile['specialization'] ?: '-'); ?></div><div class="stat-label">Specialization</div></div>
        </div>
    </div>
</div>

<?php elseif ($action === 'profile'): ?>
<!-- ============================================
     PROFILE
     ============================================ -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-user-edit"></i> Edit Profile</h3></div>
    <div class="card-body">
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Full Name *</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
            </div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>"></div>
            
            <div style="margin:25px 0; border-top:2px solid var(--gray-light); padding-top:20px;">
                <h4 style="color:var(--primary);"><i class="fas fa-briefcase"></i> Professional Information</h4>
                <div class="form-row">
                    <div class="form-group"><label>Experience (Years)</label><input type="number" name="experience_years" class="form-control" value="<?php echo $profile['experience_years']; ?>" min="0"></div>
                    <div class="form-group"><label>Hourly Rate (LKR)</label><input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?php echo $profile['hourly_rate']; ?>" min="0"></div>
                </div>
                <div class="form-group"><label>Languages Spoken</label><input type="text" name="languages" class="form-control" value="<?php echo htmlspecialchars($profile['languages']); ?>" placeholder="English, Sinhala, Tamil"></div>
                <div class="form-group"><label>Specialization</label><input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($profile['specialization']); ?>" placeholder="Cultural, Wildlife, Adventure"></div>
                <div class="form-group"><label>Availability Status</label>
                    <select name="availability" class="form-control">
                        <option value="available" <?php echo $profile['availability'] == 'available' ? 'selected' : ''; ?>>✅ Available</option>
                        <option value="busy" <?php echo $profile['availability'] == 'busy' ? 'selected' : ''; ?>>🔴 Busy</option>
                        <option value="off" <?php echo $profile['availability'] == 'off' ? 'selected' : ''; ?>>⛔ Off Duty</option>
                    </select>
                </div>
            </div>
            
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
                <a href="guide.php?action=dashboard" class="btn" style="background:#e2e8f0;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- PROFILE PREVIEW -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-id-card"></i> Profile Preview</h3></div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
            <div><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></div>
            <div><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></div>
            <div><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
            <div><strong>Experience:</strong> <?php echo $profile['experience_years']; ?> years</div>
            <div><strong>Languages:</strong> <?php echo htmlspecialchars($profile['languages'] ?: 'Not specified'); ?></div>
            <div><strong>Specialization:</strong> <?php echo htmlspecialchars($profile['specialization'] ?: 'Not specified'); ?></div>
            <div><strong>Hourly Rate:</strong> <?php echo formatCurrency($profile['hourly_rate']); ?></div>
            <div><strong>Status:</strong> <span class="badge badge-<?php echo getStatusBadge($profile['availability']); ?>"><?php echo ucfirst($profile['availability']); ?></span></div>
            <div><strong>Rating:</strong> <?php echo number_format($profile['rating'] ?? 0, 1); ?> ⭐ (<?php echo $profile['total_reviews'] ?? 0; ?> reviews)</div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>