<?php
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    redirect('../login.php');
}

$page_title = 'My Dashboard';
$user_id = $_SESSION['user_id'];

// ============================================
// GET CUSTOMER STATISTICS
// ============================================

// Total bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE customer_id = ?");
$stmt->execute([$user_id]);
$total_bookings = $stmt->fetch()['total'];

// Confirmed bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE customer_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$confirmed_bookings = $stmt->fetch()['total'];

// Pending bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE customer_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_bookings = $stmt->fetch()['total'];

// Total spent (confirmed + completed)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE customer_id = ? AND status IN ('confirmed', 'completed')");
$stmt->execute([$user_id]);
$total_spent = $stmt->fetch()['total'];

// Completed bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE customer_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_bookings = $stmt->fetch()['total'];

// Cancelled bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE customer_id = ? AND status = 'cancelled'");
$stmt->execute([$user_id]);
$cancelled_bookings = $stmt->fetch()['total'];

// ============================================
// GET USER DATA
// ============================================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM customer_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// ============================================
// GET RECENT BOOKINGS (WITH FEEDBACK CHECK)
// ============================================
$stmt = $pdo->prepare("SELECT b.*, p.name as package_name, p.image_url, p.destination,
                              g.name as guide_name, hp.hotel_name,
                              (SELECT COUNT(*) FROM feedback f WHERE f.booking_id = b.id AND f.customer_id = b.customer_id) as feedback_exists
                       FROM bookings b 
                       JOIN packages p ON b.package_id = p.id 
                       LEFT JOIN users g ON b.guide_id = g.id
                       LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id
                       WHERE b.customer_id = ? 
                       ORDER BY b.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_bookings = $stmt->fetchAll();

// ============================================
// GET UPCOMING TRIPS
// ============================================
$stmt = $pdo->prepare("SELECT b.*, p.name as package_name, p.destination
                       FROM bookings b 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.customer_id = ? 
                       AND b.status = 'confirmed' 
                       AND b.start_date >= CURDATE()
                       ORDER BY b.start_date ASC LIMIT 3");
$stmt->execute([$user_id]);
$upcoming_trips = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- ============================================
     STATS CARDS
     ============================================ -->
<div class="stats-grid">
    <!-- Total Bookings -->
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-suitcase"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_bookings; ?></h3>
            <p>Total Bookings</p>
        </div>
    </div>
    
    <!-- Confirmed Trips -->
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?php echo $confirmed_bookings; ?></h3>
            <p>Confirmed Trips</p>
        </div>
    </div>
    
    <!-- Pending Bookings -->
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo $pending_bookings; ?></h3>
            <p>Pending Bookings</p>
        </div>
    </div>
    
    <!-- Total Spent -->
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3><?php echo formatCurrency($total_spent); ?></h3>
            <p>Total Spent</p>
        </div>
    </div>
</div>

<!-- ============================================
     UPCOMING TRIPS SECTION
     ============================================ -->
<?php if (!empty($upcoming_trips)): ?>
<div class="card" style="border-left:4px solid var(--success);margin-bottom:24px;">
    <div class="card-header">
        <h3><i class="fas fa-calendar-alt" style="color:var(--success);"></i> Upcoming Trips</h3>
        <a href="bookings.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php foreach ($upcoming_trips as $trip): ?>
        <div class="booking-card" style="border-left-color:var(--success);">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;align-items:center;">
                <div>
                    <strong style="font-size:16px;"><?php echo htmlspecialchars($trip['package_name']); ?></strong>
                    <span style="font-size:13px;color:var(--gray);margin-left:10px;">
                        <i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($trip['booking_no']); ?>
                    </span>
                </div>
                <span style="font-size:13px;color:var(--success);font-weight:600;">
                    <i class="fas fa-calendar-check"></i> 
                    <?php echo date('M d, Y', strtotime($trip['start_date'])); ?>
                </span>
            </div>
            <div style="margin-top:5px;font-size:13px;color:var(--gray);">
                <i class="fas fa-map-marker-alt" style="color:var(--secondary);"></i> 
                <?php echo htmlspecialchars($trip['destination']); ?>
                <span style="margin-left:15px;">
                    <i class="fas fa-users" style="color:var(--primary);"></i> 
                    <?php echo $trip['adults'] + $trip['children']; ?> guests
                </span>
                <span style="margin-left:15px;">
                    <i class="fas fa-moon" style="color:var(--info);"></i> 
                    <?php 
                    $nights = ceil((strtotime($trip['end_date']) - strtotime($trip['start_date'])) / 86400);
                    echo $nights; ?> nights
                </span>
            </div>
            <div style="margin-top:10px;">
                <a href="bookings.php?view=<?php echo $trip['id']; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-eye"></i> View Details
                </a>
                <?php if ($trip['paid_amount'] < $trip['total_amount']): ?>
                    <a href="payments.php?booking_id=<?php echo $trip['id']; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-credit-card"></i> Pay Balance
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================
     QUICK ACTION CARDS (FIXED)
     ============================================ -->
<div class="packages-grid" style="margin-bottom: 30px; justify-content: center;">
    <!-- Browse Packages -->
    <div class="package-card" style="max-width: 320px;">
        <div class="package-info" style="text-align:center;padding:30px 20px;">
            <i class="fas fa-search" style="font-size:48px;color:var(--primary);margin-bottom:15px;display:block;"></i>
            <h3 style="font-size:18px;margin-bottom:8px;color:var(--dark);">Browse Packages</h3>
            <p style="margin:10px 0 20px;color:var(--gray);font-size:14px;line-height:1.5;">Explore our amazing tour packages</p>
            <a href="packages.php" class="btn btn-primary" style="width:100%;justify-content:center;padding:10px;">
                <i class="fas fa-arrow-right"></i> Browse Now
            </a>
        </div>
    </div>
    
    <!-- My Bookings -->
    <div class="package-card" style="max-width: 320px;">
        <div class="package-info" style="text-align:center;padding:30px 20px;">
            <i class="fas fa-calendar-check" style="font-size:48px;color:var(--primary);margin-bottom:15px;display:block;"></i>
            <h3 style="font-size:18px;margin-bottom:8px;color:var(--dark);">My Bookings</h3>
            <p style="margin:10px 0 20px;color:var(--gray);font-size:14px;line-height:1.5;">View and manage your bookings</p>
            <a href="bookings.php" class="btn btn-primary" style="width:100%;justify-content:center;padding:10px;">
                <i class="fas fa-arrow-right"></i> View Bookings
            </a>
        </div>
    </div>
    
    <!-- Make Payment -->
    <div class="package-card" style="max-width: 320px;">
        <div class="package-info" style="text-align:center;padding:30px 20px;">
            <i class="fas fa-credit-card" style="font-size:48px;color:var(--primary);margin-bottom:15px;display:block;"></i>
            <h3 style="font-size:18px;margin-bottom:8px;color:var(--dark);">Make Payment</h3>
            <p style="margin:10px 0 20px;color:var(--gray);font-size:14px;line-height:1.5;">Complete pending payments</p>
            <a href="payments.php" class="btn btn-primary" style="width:100%;justify-content:center;padding:10px;">
                <i class="fas fa-arrow-right"></i> Pay Now
            </a>
        </div>
    </div>
</div>

<!-- ============================================
     RECENT BOOKINGS TABLE
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Recent Bookings</h3>
        <a href="bookings.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recent_bookings)): ?>
            <div style="text-align:center;padding:60px;color:var(--gray);">
                <i class="fas fa-suitcase" style="font-size:48px;display:block;margin-bottom:15px;color:var(--primary);"></i>
                <p style="font-size:16px;font-weight:500;">No bookings yet</p>
                <p style="font-size:13px;margin-top:5px;">Start exploring our amazing packages!</p>
                <a href="packages.php" class="btn btn-primary" style="margin-top:15px;">
                    <i class="fas fa-search"></i> Browse Packages
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking No</th>
                            <th>Package</th>
                            <th>Destination</th>
                            <th>Start Date</th>
                            <th>Guests</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $b): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($b['booking_no']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($b['package_name']); ?></td>
                            <td>
                                <i class="fas fa-map-marker-alt" style="color:var(--secondary);font-size:11px;"></i>
                                <?php echo htmlspecialchars($b['destination']); ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($b['start_date'])); ?></td>
                            <td>
                                <i class="fas fa-user" style="font-size:11px;"></i>
                                <?php echo $b['adults'] + $b['children']; ?> pax
                            </td>
                            <td><?php echo formatCurrency($b['total_amount']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo getStatusBadge($b['status']); ?>">
                                    <?php echo ucfirst($b['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="bookings.php?view=<?php echo $b['id']; ?>" class="btn btn-primary btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($b['status'] === 'pending'): ?>
                                    <a href="payments.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-success btn-sm" title="Pay Now">
                                        <i class="fas fa-credit-card"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($b['status'] === 'completed' && $b['feedback_exists'] == 0): ?>
                                    <a href="feedback.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-warning btn-sm" title="Give Feedback">
                                        <i class="fas fa-star"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================
     QUICK STATS (Extra Info)
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-simple"></i> Quick Stats</h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;">
            <div style="background:#f0f7f5;padding:18px;border-radius:12px;text-align:center;border:1px solid #e8f0ed;">
                <div style="font-size:28px;font-weight:700;color:var(--primary);"><?php echo $total_bookings; ?></div>
                <div style="font-size:13px;color:var(--gray);margin-top:5px;">Total Bookings</div>
            </div>
            <div style="background:#f0fdf4;padding:18px;border-radius:12px;text-align:center;border:1px solid #dcfce7;">
                <div style="font-size:28px;font-weight:700;color:var(--success);"><?php echo $confirmed_bookings; ?></div>
                <div style="font-size:13px;color:var(--gray);margin-top:5px;">Confirmed</div>
            </div>
            <div style="background:#fffbeb;padding:18px;border-radius:12px;text-align:center;border:1px solid #fef3c7;">
                <div style="font-size:28px;font-weight:700;color:var(--warning);"><?php echo $pending_bookings; ?></div>
                <div style="font-size:13px;color:var(--gray);margin-top:5px;">Pending</div>
            </div>
            <div style="background:#eff6ff;padding:18px;border-radius:12px;text-align:center;border:1px solid #dbeafe;">
                <div style="font-size:28px;font-weight:700;color:var(--info);"><?php echo $completed_bookings; ?></div>
                <div style="font-size:13px;color:var(--gray);margin-top:5px;">Completed</div>
            </div>
            <?php if ($cancelled_bookings > 0): ?>
            <div style="background:#fef2f2;padding:18px;border-radius:12px;text-align:center;border:1px solid #fecaca;">
                <div style="font-size:28px;font-weight:700;color:var(--danger);"><?php echo $cancelled_bookings; ?></div>
                <div style="font-size:13px;color:var(--gray);margin-top:5px;">Cancelled</div>
            </div>
            <?php endif; ?>
            <div style="background:#fefce8;padding:18px;border-radius:12px;text-align:center;border:1px solid #fef9c3;">
                <div style="font-size:28px;font-weight:700;color:var(--secondary);"><?php echo formatCurrency($total_spent); ?></div>
                <div style="font-size:13px;color:var(--gray);margin-top:5px;">Total Spent</div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>