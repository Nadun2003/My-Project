<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Promo Code Management';

// Add Promo Code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $stmt = $pdo->prepare("INSERT INTO promo_codes (code, discount_type, discount_value, min_order_amount, max_discount, valid_from, valid_to, usage_limit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        strtoupper($_POST['code']),
        $_POST['discount_type'],
        $_POST['discount_value'],
        $_POST['min_order_amount'],
        $_POST['max_discount'],
        $_POST['valid_from'],
        $_POST['valid_to'],
        $_POST['usage_limit'],
        $_POST['status']
    ]);
    toast("Promo code added successfully!");
    redirect('promo_codes.php');
}

// Delete Promo Code
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM promo_codes WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    toast("Promo code deleted!", 'warning');
    redirect('promo_codes.php');
}

$promo_codes = $pdo->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-ticket-alt"></i> Promo Codes</h3>
        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Promo Code</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Valid</th><th>Used</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($promo_codes as $p): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['code']); ?></strong></td>
                        <td><?php echo ucfirst($p['discount_type']); ?></td>
                        <td><?php echo $p['discount_type'] == 'percentage' ? $p['discount_value'] . '%' : formatCurrency($p['discount_value']); ?></td>
                        <td><?php echo formatCurrency($p['min_order_amount']); ?></td>
                        <td><?php echo date('M d', strtotime($p['valid_from'])) . ' - ' . date('M d, Y', strtotime($p['valid_to'])); ?></td>
                        <td><?php echo $p['used_count'] . '/' . $p['usage_limit']; ?></td>
                        <td><span class="badge badge-<?php echo $p['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        <td>
                            <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this promo code?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Promo Modal -->
<div id="promoModal" class="modal">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header">
            <h3>Add Promo Code</h3>
            <button class="close-btn" onclick="closeModal('promoModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="SUMMER2026" required>
                    </div>
                    <div class="form-group">
                        <label>Discount Type</label>
                        <select name="discount_type" class="form-control">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed (LKR)</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Discount Value *</label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" placeholder="10" required>
                    </div>
                    <div class="form-group">
                        <label>Min Order Amount</label>
                        <input type="number" step="0.01" name="min_order_amount" class="form-control" placeholder="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Discount</label>
                        <input type="number" step="0.01" name="max_discount" class="form-control" placeholder="500">
                    </div>
                    <div class="form-group">
                        <label>Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-control" value="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Valid From</label>
                        <input type="date" name="valid_from" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Valid To</label>
                        <input type="date" name="valid_to" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('promoModal')">Cancel</button>
                <button type="submit" name="add_promo" class="btn btn-primary">Add Promo Code</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    openModal('promoModal');
}
</script>

<?php include '../includes/footer.php'; ?>