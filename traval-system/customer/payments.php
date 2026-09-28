<?php
require_once '../config/database.php';
checkAccess('customer');

$page_title = 'My Payments';
$user_id = $_SESSION['user_id'];

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    $booking_id = $_POST['booking_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    
    // Get booking details
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND customer_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if ($booking && $amount > 0 && $amount <= ($booking['total_amount'] - $booking['paid_amount'])) {
        // Create payment
        $transaction_id = generateTransactionId();
        $stmt = $pdo->prepare("INSERT INTO payments (transaction_id, booking_id, amount, payment_method, status) VALUES (?, ?, ?, ?, 'completed')");
        if ($stmt->execute([$transaction_id, $booking_id, $amount, $payment_method])) {
            // Update booking
            $new_paid = $booking['paid_amount'] + $amount;
            $new_status = ($new_paid >= $booking['total_amount']) ? 'confirmed' : 'pending';
            $payment_status = ($new_paid >= $booking['total_amount']) ? 'paid' : 'partial';
            
            $stmt = $pdo->prepare("UPDATE bookings SET paid_amount = ?, status = ?, payment_status = ? WHERE id = ?");
            $stmt->execute([$new_paid, $new_status, $payment_status, $booking_id]);
            
            toast("Payment of " . formatCurrency($amount) . " completed successfully!");
            redirect('payments.php');
        } else {
            toast("Payment failed. Please try again.", 'error');
        }
    } else {
        toast("Invalid payment amount!", 'error');
    }
}

// Get all payments
$stmt = $pdo->prepare("SELECT p.*, b.booking_no, b.total_amount, b.paid_amount, b.status as booking_status
                       FROM payments p 
                       JOIN bookings b ON p.booking_id = b.id 
                       WHERE b.customer_id = ? 
                       ORDER BY p.payment_date DESC");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll();

// Get pending payments (bookings with balance)
$stmt = $pdo->prepare("SELECT b.*, p.name as package_name 
                       FROM bookings b 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.customer_id = ? AND b.paid_amount < b.total_amount AND b.status != 'cancelled'");
$stmt->execute([$user_id]);
$pending_payments = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-credit-card"></i></div>
        <div class="stat-info">
            <h3><?php echo count($payments); ?></h3>
            <p>Transactions</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3><?php echo formatCurrency(array_sum(array_column($payments, 'amount'))); ?></h3>
            <p>Total Paid</p>
        </div>
    </div>
</div>

<!-- Pending Payments -->
<?php if (!empty($pending_payments)): ?>
<div class="card" style="border-left:4px solid var(--warning);">
    <div class="card-header">
        <h3><i class="fas fa-clock"></i> Pending Payments</h3>
        <span style="font-size:13px;color:var(--gray);"><?php echo count($pending_payments); ?> bookings pending</span>
    </div>
    <div class="card-body">
        <?php foreach ($pending_payments as $p): 
            $balance = $p['total_amount'] - $p['paid_amount'];
        ?>
        <div class="booking-card" style="background:#fffbf0;">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;">
                <div>
                    <strong><?php echo htmlspecialchars($p['booking_no']); ?></strong>
                    <span style="font-size:13px;color:var(--gray);">- <?php echo htmlspecialchars($p['package_name']); ?></span>
                </div>
                <span class="badge badge-warning">Balance Due</span>
            </div>
            <div style="margin:10px 0;font-size:14px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;">
                    <div><strong>Total:</strong> <?php echo formatCurrency($p['total_amount']); ?></div>
                    <div><strong>Paid:</strong> <?php echo formatCurrency($p['paid_amount']); ?></div>
                    <div><strong>Balance:</strong> <?php echo formatCurrency($balance); ?></div>
                </div>
            </div>
            <button class="btn btn-primary" onclick="openPaymentModal(<?php echo $p['id']; ?>, <?php echo $balance; ?>)">
                <i class="fas fa-credit-card"></i> Pay Now
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Payment History -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Payment History</h3>
    </div>
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <p style="text-align:center;padding:40px;color:var(--gray);">
                <i class="fas fa-receipt" style="font-size:48px;display:block;margin-bottom:15px;"></i>
                No payment records found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Transaction ID</th><th>Booking No</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['transaction_id']); ?></td>
                            <td><?php echo htmlspecialchars($p['booking_no']); ?></td>
                            <td><?php echo formatCurrency($p['amount']); ?></td>
                            <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                            <td><span class="badge badge-success">Completed</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-credit-card"></i> Make Payment</h3>
            <button class="close-btn" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="booking_id" id="payBookingId">
            <div class="modal-body">
                <div class="form-group">
                    <label>Amount (LKR) *</label>
                    <input type="number" step="0.01" name="amount" id="payAmount" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Online Payment">Online Payment</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
                <div style="background:#fff3cd;padding:12px;border-radius:8px;font-size:13px;color:#856404;">
                    <i class="fas fa-info-circle"></i> 
                    After payment, your booking will be confirmed automatically.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('paymentModal')">Cancel</button>
                <button type="submit" name="make_payment" class="btn btn-success">
                    <i class="fas fa-check"></i> Confirm Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(id, amount) {
    document.getElementById('payBookingId').value = id;
    document.getElementById('payAmount').value = amount;
    openModal('paymentModal');
}
</script>

<?php include '../includes/footer.php'; ?>