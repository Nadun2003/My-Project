<?php
require_once '../config/database.php';
checkAccess('hotel');

$page_title = 'Revenue Reports';
$user_id = $_SESSION['user_id'];

// Get hotel profile
$stmt = $pdo->prepare("SELECT * FROM hotel_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Get all confirmed bookings
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name 
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       WHERE b.hotel_id = ? AND b.status = 'confirmed'
                       ORDER BY b.start_date DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// Calculate totals
$total_revenue = 0;
$total_nights = 0;
$total_guests = 0;

foreach ($bookings as $b) {
    $nights = ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400);
    $total_nights += $nights;
    $total_guests += ($b['adults'] + $b['children']);
    $total_revenue += ($b['total_amount'] * 0.35);
}

// Monthly breakdown
$monthly_data = $pdo->prepare("SELECT DATE_FORMAT(start_date, '%Y-%m') as month, 
                                      COUNT(*) as count, 
                                      SUM(total_amount) as total 
                               FROM bookings 
                               WHERE hotel_id = ? AND status = 'confirmed'
                               GROUP BY month 
                               ORDER BY month DESC LIMIT 12");
$monthly_data->execute([$user_id]);
$monthly = $monthly_data->fetchAll();

include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3><?php echo formatCurrency($total_revenue); ?></h3>
            <p>Total Revenue (35% Commission)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-bed"></i></div>
        <div class="stat-info">
            <h3><?php echo count($bookings); ?></h3>
            <p>Confirmed Bookings</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-moon"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_nights; ?></h3>
            <p>Total Room Nights</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_guests; ?></h3>
            <p>Total Guests</p>
        </div>
    </div>
</div>

<!-- Hotel Info -->
<div class="card" style="border-left:4px solid var(--primary);margin-bottom:20px;">
    <div class="card-header">
        <h3><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($profile['hotel_name']); ?></h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;font-size:14px;">
            <div><strong>Location:</strong> <?php echo htmlspecialchars($profile['location'] ?: 'Not specified'); ?></div>
            <div><strong>Rating:</strong> <?php echo str_repeat('★', $profile['stars']); ?></div>
            <div><strong>Rooms:</strong> <?php echo $profile['room_count'] ?: 'N/A'; ?></div>
            <div><strong>Price/Night:</strong> <?php echo $profile['price_per_night'] ? formatCurrency($profile['price_per_night']) : 'N/A'; ?></div>
        </div>
    </div>
</div>

<!-- Monthly Breakdown -->
<?php if (!empty($monthly)): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Monthly Breakdown</h3>
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Month</th><th>Bookings</th><th>Total Amount</th><th>Your Commission (35%)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly as $m): ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($m['month'] . '-01')); ?></td>
                        <td><?php echo $m['count']; ?></td>
                        <td><?php echo formatCurrency($m['total']); ?></td>
                        <td><?php echo formatCurrency($m['total'] * 0.35); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--light);font-weight:bold;">
                        <td>Total</td>
                        <td><?php echo array_sum(array_column($monthly, 'count')); ?></td>
                        <td><?php echo formatCurrency(array_sum(array_column($monthly, 'total'))); ?></td>
                        <td><?php echo formatCurrency(array_sum(array_column($monthly, 'total')) * 0.35); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Revenue Report -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-line"></i> Revenue Report</h3>
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="card-body">
        <?php if (empty($bookings)): ?>
            <p style="text-align:center;padding:40px;color:var(--gray);">
                <i class="fas fa-chart-line" style="font-size:48px;display:block;margin-bottom:15px;"></i>
                No booking data available yet.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking No</th>
                            <th>Customer</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Nights</th>
                            <th>Guests</th>
                            <th>Booking Amount</th>
                            <th>Commission (35%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): 
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
                        <tr style="background:var(--light);font-weight:bold;border-top:2px solid var(--primary);">
                            <td colspan="7" style="text-align:right;">Total Commission:</td>
                            <td><?php echo formatCurrency($total_revenue); ?></td>
                        </tr>
                        <tr style="background:var(--primary-light);font-weight:bold;">
                            <td colspan="7" style="text-align:right;">Total Bookings:</td>
                            <td><?php echo count($bookings); ?></td>
                        </tr>
                        <tr style="background:var(--primary-light);font-weight:bold;">
                            <td colspan="7" style="text-align:right;">Total Room Nights:</td>
                            <td><?php echo $total_nights; ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>