<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Admin Dashboard';

// ============================================
// GET STATISTICS
// ============================================
$stats = [
    'customers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    'guides' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guide'")->fetchColumn(),
    'hotels' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'hotel'")->fetchColumn(),
    'packages' => $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn(),
    'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'revenue' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn(),
];

// ============================================
// GET RECENT BOOKINGS
// ============================================
$recent = $pdo->query("SELECT b.*, u.name as customer_name, p.name as package_name 
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       ORDER BY b.created_at DESC LIMIT 8")->fetchAll();

include '../includes/header.php';
?>

<!-- ============================================
     WELCOME CARD
     ============================================ -->
<div class="welcome-card">
    <div class="welcome-content">
        <div>
            <h1><?php echo getGreeting(); ?>, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 👑</h1>
            <p>Manage your travel platform from one place</p>
            <div style="margin-top:10px;">
                <span class="badge badge-primary" style="font-size:13px; padding:6px 16px;">
                    <i class="fas fa-shield-alt"></i> Administrator Access
                </span>
            </div>
        </div>
        <div class="welcome-icon">
            <i class="fas fa-crown"></i>
        </div>
    </div>
</div>

<!-- ============================================
     STATS CARDS
     ============================================ -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $stats['customers']; ?></h3>
            <p>Customers</p>
        </div>
        <div class="stat-trend">👤 Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-hiking"></i></div>
        <div class="stat-info">
            <h3><?php echo $stats['guides']; ?></h3>
            <p>Guides</p>
        </div>
        <div class="stat-trend">🧭 Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-hotel"></i></div>
        <div class="stat-info">
            <h3><?php echo $stats['hotels']; ?></h3>
            <p>Hotels</p>
        </div>
        <div class="stat-trend">🏨 Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-box"></i></div>
        <div class="stat-info">
            <h3><?php echo $stats['packages']; ?></h3>
            <p>Packages</p>
        </div>
        <div class="stat-trend">📦 Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?php echo $stats['bookings']; ?></h3>
            <p>Bookings</p>
        </div>
        <div class="stat-trend">📋 Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3><?php echo formatCurrency($stats['revenue']); ?></h3>
            <p>Revenue</p>
        </div>
        <div class="stat-trend">💰 Total</div>
    </div>
</div>

<!-- ============================================
     RECENT BOOKINGS
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Recent Bookings</h3>
        <a href="bookings.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recent)): ?>
            <p style="text-align:center; padding:30px; color:var(--gray);">No bookings yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Booking No</th><th>Customer</th><th>Package</th><th>Date</th><th>Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $b): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['booking_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($b['package_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($b['created_at'])); ?></td>
                            <td><?php echo formatCurrency($b['total_amount']); ?></td>
                            <td><span class="badge badge-<?php echo getStatusBadge($b['status']); ?>"><?php echo ucfirst($b['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================
     QUICK STATS
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-simple"></i> Quick Stats</h3>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
            <div class="quick-stat-item">
                <div class="stat-number" style="color:var(--primary);"><?php echo $stats['customers']; ?></div>
                <div class="stat-label">Customers</div>
            </div>
            <div class="quick-stat-item">
                <div class="stat-number" style="color:var(--success);"><?php echo $stats['guides']; ?></div>
                <div class="stat-label">Guides</div>
            </div>
            <div class="quick-stat-item">
                <div class="stat-number" style="color:var(--info);"><?php echo $stats['hotels']; ?></div>
                <div class="stat-label">Hotels</div>
            </div>
            <div class="quick-stat-item">
                <div class="stat-number" style="color:var(--secondary);"><?php echo $stats['packages']; ?></div>
                <div class="stat-label">Packages</div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>