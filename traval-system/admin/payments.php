<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Payment Management';

$payments = $pdo->query("SELECT p.*, b.booking_no, u.name as customer_name 
                         FROM payments p 
                         JOIN bookings b ON p.booking_id = b.id 
                         JOIN users u ON b.customer_id = u.id 
                         ORDER BY p.payment_date DESC")->fetchAll();

$total = array_sum(array_column($payments, 'amount'));

include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon primary"><i class="fas fa-dollar-sign"></i></div><div class="stat-info"><h3><?php echo formatCurrency($total); ?></h3><p>Total Collections</p></div></div>
    <div class="stat-card"><div class="stat-icon secondary"><i class="fas fa-receipt"></i></div><div class="stat-info"><h3><?php echo count($payments); ?></h3><p>Transactions</p></div></div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-credit-card"></i> Payment Records</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Transaction ID</th><th>Booking No</th><th>Customer</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['transaction_id']); ?></td>
                        <td><?php echo htmlspecialchars($p['booking_no']); ?></td>
                        <td><?php echo htmlspecialchars($p['customer_name']); ?></td>
                        <td><?php echo formatCurrency($p['amount']); ?></td>
                        <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                        <td><span class="badge badge-success">Completed</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>