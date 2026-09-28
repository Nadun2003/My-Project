<?php
require_once '../config/database.php';
checkAccess('guide');

$page_title = 'Guide Dashboard';
$user_id = $_SESSION['user_id'];

// Get guide profile
$stmt = $pdo->prepare("SELECT * FROM guide_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// If no profile exists, create one
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

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE guide_id = ?");
$stmt->execute([$user_id]);
$total_assignments = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE guide_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$confirmed = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE guide_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed = $stmt->fetch()['total'];

// Get upcoming assignments
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, 
                              p.name as package_name, p.destination
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.guide_id = ? AND b.start_date >= CURDATE() AND b.status = 'confirmed'
                       ORDER BY b.start_date ASC LIMIT 5");
$stmt->execute([$user_id]);
$upcoming = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-route"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_assignments; ?></h3>
            <p>Total Assignments</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?php echo $confirmed; ?></h3>
            <p>Confirmed Tours</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-flag-checkered"></i></div>
        <div class="stat-info">
            <h3><?php echo $completed; ?></h3>
            <p>Completed Tours</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-star"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($profile['rating'] ?? 0, 1); ?> / 5</h3>
            <p>Rating (<?php echo $profile['total_reviews']; ?> reviews)</p>
        </div>
    </div>
</div>

<!-- Quick Status Update -->
<div class="card" style="border-left:4px solid var(--primary);">
    <div class="card-header">
        <h3><i class="fas fa-toggle-on"></i> Availability Status</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="profile.php" style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;">
            <input type="hidden" name="update_availability" value="1">
            <select name="availability" class="form-control" style="width:auto;min-width:150px;">
                <option value="available" <?php echo ($profile['availability'] ?? 'available') == 'available' ? 'selected' : ''; ?>>✅ Available</option>
                <option value="busy" <?php echo ($profile['availability'] ?? 'available') == 'busy' ? 'selected' : ''; ?>>🔴 Busy</option>
                <option value="off" <?php echo ($profile['availability'] ?? 'available') == 'off' ? 'selected' : ''; ?>>⛔ Off Duty</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Update Status</button>
            <span style="font-size:13px;color:var(--gray);">Current: <strong><?php echo ucfirst($profile['availability'] ?? 'available'); ?></strong></span>
        </form>
    </div>
</div>

<!-- Upcoming Assignments -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar"></i> Upcoming Assignments</h3>
        <a href="bookings.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($upcoming)): ?>
            <p style="text-align:center;padding:40px;color:var(--gray);">
                <i class="fas fa-calendar-check" style="font-size:48px;display:block;margin-bottom:15px;"></i>
                No upcoming assignments. Check back later!
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Booking No</th><th>Customer</th><th>Package</th><th>Destination</th><th>Start Date</th><th>Guests</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $b): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['booking_no']); ?></td>
                            <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['package_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['destination']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($b['start_date'])); ?></td>
                            <td><?php echo $b['adults'] + $b['children']; ?> pax</td>
                            <td><a href="bookings.php?view=<?php echo $b['id']; ?>" class="btn btn-primary btn-sm">View</a></td>
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
                <div style="font-size:28px;font-weight:700;color:var(--primary);"><?php echo $total_assignments; ?></div>
                <div style="font-size:13px;color:var(--gray);">Total Tours</div>
            </div>
            <div style="background:var(--light);padding:15px;border-radius:10px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--success);"><?php echo $confirmed; ?></div>
                <div style="font-size:13px;color:var(--gray);">Active Tours</div>
            </div>
            <div style="background:var(--light);padding:15px;border-radius:10px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--info);"><?php echo $profile['experience_years'] ?? 0; ?></div>
                <div style="font-size:13px;color:var(--gray);">Years Experience</div>
            </div>
            <div style="background:var(--light);padding:15px;border-radius:10px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--secondary);"><?php echo formatCurrency($profile['hourly_rate'] ?? 0); ?></div>
                <div style="font-size:13px;color:var(--gray);">Hourly Rate</div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>