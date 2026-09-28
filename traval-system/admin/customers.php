<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Customer Management';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
        $stmt->execute([$_POST['id']]);
        toast("Customer deleted!", 'warning');
        redirect('customers.php');
    } elseif (isset($_POST['save'])) {
        if ($_POST['id']) {
            // Update existing customer
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, status=? WHERE id=? AND role='customer'");
            $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['status'], $_POST['id']]);
            $stmt = $pdo->prepare("UPDATE customer_profiles SET nic=?, address=?, date_of_birth=?, nationality=? WHERE user_id=?");
            $stmt->execute([$_POST['nic'], $_POST['address'], $_POST['date_of_birth'], $_POST['nationality'], $_POST['id']]);
            toast("Customer updated!");
        } else {
            // Add new customer
            $password = password_hash('Customer@123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, 'customer', ?)");
            $stmt->execute([$_POST['name'], $_POST['email'], $password, $_POST['phone'], $_POST['status']]);
            $id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO customer_profiles (user_id, nic, address, date_of_birth, nationality) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $_POST['nic'], $_POST['address'], $_POST['date_of_birth'], $_POST['nationality']]);
            toast("Customer added! Default password: Customer@123");
        }
        redirect('customers.php');
    }
}

// Get all customers
$customers = $pdo->query("SELECT u.*, cp.nic, cp.address, cp.date_of_birth, cp.nationality 
                         FROM users u 
                         LEFT JOIN customer_profiles cp ON u.id = cp.user_id 
                         WHERE u.role = 'customer' 
                         ORDER BY u.created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-users"></i> Customers</h3>
        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Customer</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>NIC</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><?php echo htmlspecialchars($c['email']); ?></td>
                        <td><?php echo htmlspecialchars($c['phone']); ?></td>
                        <td><?php echo htmlspecialchars($c['nic']); ?></td>
                        <td><span class="badge badge-<?php echo getStatusBadge($c['status']); ?>"><?php echo ucfirst($c['status']); ?></span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick='editCustomer(<?php echo json_encode($c); ?>)'><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this customer?')">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Customer</h3>
            <button class="close-btn" onclick="closeModal('customerModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="id" id="custId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>Full Name *</label><input type="text" name="name" id="custName" class="form-control" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" id="custEmail" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" id="custPhone" class="form-control"></div>
                    <div class="form-group"><label>NIC</label><input type="text" name="nic" id="custNic" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" id="custDob" class="form-control"></div>
                    <div class="form-group"><label>Nationality</label><input type="text" name="nationality" id="custNationality" class="form-control"></div>
                </div>
                <div class="form-group"><label>Address</label><textarea name="address" id="custAddress" class="form-control" rows="2"></textarea></div>
                <div class="form-group"><label>Status</label>
                    <select name="status" id="custStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('customerModal')">Cancel</button>
                <button type="submit" name="save" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Customer';
    document.getElementById('custId').value = '';
    document.getElementById('custName').value = '';
    document.getElementById('custEmail').value = '';
    document.getElementById('custPhone').value = '';
    document.getElementById('custNic').value = '';
    document.getElementById('custAddress').value = '';
    document.getElementById('custDob').value = '';
    document.getElementById('custNationality').value = '';
    document.getElementById('custStatus').value = 'active';
    openModal('customerModal');
}

function editCustomer(c) {
    document.getElementById('modalTitle').innerText = 'Edit Customer';
    document.getElementById('custId').value = c.id;
    document.getElementById('custName').value = c.name;
    document.getElementById('custEmail').value = c.email;
    document.getElementById('custPhone').value = c.phone || '';
    document.getElementById('custNic').value = c.nic || '';
    document.getElementById('custAddress').value = c.address || '';
    document.getElementById('custDob').value = c.date_of_birth || '';
    document.getElementById('custNationality').value = c.nationality || '';
    document.getElementById('custStatus').value = c.status;
    openModal('customerModal');
}
</script>

<?php include '../includes/footer.php'; ?>