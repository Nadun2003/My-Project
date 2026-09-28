<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Reports & Analytics';

// Monthly revenue data
$monthly_data = $pdo->query("SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as total 
                             FROM payments WHERE status = 'completed' 
                             GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll();

// Package popularity
$popular_packages = $pdo->query("SELECT p.name, COUNT(b.id) as bookings 
                                 FROM packages p 
                                 LEFT JOIN bookings b ON p.id = b.package_id 
                                 GROUP BY p.id ORDER BY bookings DESC LIMIT 5")->fetchAll();

// Booking status breakdown
$status_counts = $pdo->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status")->fetchAll();

include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon primary"><i class="fas fa-chart-line"></i></div><div class="stat-info"><h3><?php echo count($monthly_data); ?></h3><p>Months of Data</p></div></div>
    <div class="stat-card"><div class="stat-icon secondary"><i class="fas fa-box"></i></div><div class="stat-info"><h3><?php echo $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn(); ?></h3><p>Total Packages</p></div></div>
    <div class="stat-card"><div class="stat-icon success"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3><?php echo $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn(); ?></h3><p>Confirmed Bookings</p></div></div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Monthly Revenue</h3>
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="card-body">
        <?php if (empty($monthly_data)): ?>
            <p style="text-align:center;padding:30px;color:var(--gray);">No revenue data available.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Month</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php foreach ($monthly_data as $m): ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($m['month'] . '-01')); ?></td>
                            <td><?php echo formatCurrency($m['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--light);font-weight:bold;">
                            <td>Total</td>
                            <td><?php echo formatCurrency(array_sum(array_column($monthly_data, 'total'))); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-fire"></i> Most Popular Packages</h3>
    </div>
    <div class="card-body">
        <?php if (empty($popular_packages)): ?>
            <p style="text-align:center;padding:30px;color:var(--gray);">No package data available.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Package Name</th><th>Total Bookings</th></tr></thead>
                    <tbody>
                        <?php foreach ($popular_packages as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo $p['bookings']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-circle"></i> Booking Status Breakdown</h3>
    </div>
    <div class="card-body">
        <?php if (empty($status_counts)): ?>
            <p style="text-align:center;padding:30px;color:var(--gray);">No booking data available.</p>
        <?php else: ?>
            <div style="display:flex;flex-wrap:wrap;gap:20px;">
                <?php foreach ($status_counts as $s): ?>
                    <div style="background:var(--light);padding:15px 25px;border-radius:10px;flex:1;min-width:120px;text-align:center;">
                        <span class="badge badge-<?php echo getStatusBadge($s['status']); ?>" style="font-size:14px;padding:6px 16px;"><?php echo ucfirst($s['status']); ?></span>
                        <h3 style="margin-top:10px;font-size:28px;"><?php echo $s['count']; ?></h3>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>