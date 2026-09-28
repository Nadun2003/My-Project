<?php
require_once '../config/database.php';
checkAccess('hotel');

$page_title = 'Hotel Dashboard';
$user_id = $_SESSION['user_id'];

// Get hotel profile
$stmt = $pdo->prepare("SELECT * FROM hotel_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// If no profile exists, create one
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

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE hotel_id = ?");
$stmt->execute([$user_id]);
$total_bookings = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE hotel_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$confirmed_bookings = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE hotel_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_bookings = $stmt->fetch()['total'];

// Calculate revenue (35% commission)
$stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM bookings WHERE hotel_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$total_amount = $stmt->fetch()['total'] ?? 0;
$revenue = $total_amount * 0.35;

// Get recent bookings
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email, u.phone, p.name as package_name 
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.hotel_id = ? 
                       ORDER BY b.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_bookings = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-bed"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_bookings; ?></h3>
            <p>Total Bookings</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?php echo $confirmed_bookings; ?></h3>
            <p>Confirmed Bookings</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-flag-checkered"></i></div>
        <div class="stat-info">
            <h3><?php echo $completed_bookings; ?></h3>
            <p>Completed Bookings</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3><?php echo formatCurrency($revenue); ?></h3>
            <p>Est. Revenue (35% Commission)</p>
        </div>
    </div>
</div>

<!-- Hotel Info Card -->
<div class="card" style="border-left:4px solid var(--primary);">
    <div class="card-header">
        <h3><i class="fas fa-hotel"></i> Hotel Information</h3>
        <a href="profile.php" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
            <div><strong>Hotel Name:</strong> <?php echo htmlspecialchars($profile['hotel_name']); ?></div>
            <div><strong>Location:</strong> <?php echo htmlspecialchars($profile['location'] ?: 'Not specified'); ?></div>
            <div><strong>Stars:</strong> <?php echo str_repeat('★', $profile['stars']); ?></div>
            <div><strong>Rooms:</strong> <?php echo $profile['room_count'] ?: 'Not specified'; ?></div>
            <div><strong>Price/Night:</strong> <?php echo $profile['price_per_night'] ? formatCurrency($profile['price_per_night']) : 'Not specified'; ?></div>
            <div><strong>Check-in:</strong> <?php echo htmlspecialchars($profile['check_in_time'] ?: 'Not specified'); ?></div>
            <div><strong>Check-out:</strong> <?php echo htmlspecialchars($profile['check_out_time'] ?: 'Not specified'); ?></div>
        </div>
        <?php if ($profile['amenities']): ?>
            <div style="margin-top:10px;"><strong>Amenities:</strong> <?php echo htmlspecialchars($profile['amenities']); ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clock"></i> Recent Bookings</h3>
        <a href="bookings.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recent_bookings)): ?>
            <p style="text-align:center;padding:40px;color:var(--gray);">
                <i class="fas fa-bed" style="font-size:48px;display:block;margin-bottom:15px;"></i>
                No bookings yet. Check back later!
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Booking No</th><th>Customer</th><th>Package</th><th>Check In</th><th>Check Out</th><th>Guests</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $b): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['booking_no']); ?></td>
                            <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['package_name']); ?></td>
                            <td><?php echo date('M d', strtotime($b['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($b['end_date'])); ?></td>
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

<!-- Quick Stats -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-simple"></i> Performance Summary</h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;">
            <div style="background:var(--light);padding:15px;border-radius:10px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--primary);"><?php echo $total_bookings; ?></div>
                <div style="font-size:13px;color:var(--gray);">Total Bookings</div>
            </div>
            <div style="background:var(--light);padding:15px;border-radius:10px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--success);"><?php echo $confirmed_bookings; ?></div>
                <div style="font-size:13px;color:var(--gray);">Confirmed</div>
            </div>
            <div style="background:var(--light);padding:15px;border-radius:10px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--secondary);"><?php echo $profile['stars']; ?>★</div>
                <div style="font-size:13px;color:var(--gray);">Star Rating</div>
            </div>
            <div style="background:var(--light);padding:15px;border-radius:10px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--info);"><?php echo $profile['room_count'] ?: 0; ?></div>
                <div style="font-size:13px;color:var(--gray);">Rooms</div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>