<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Guide Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'guide'");
        $stmt->execute([$_POST['id']]);
        toast("Guide deleted!", 'warning');
        redirect('guides.php');
    } elseif (isset($_POST['save'])) {
        if ($_POST['id']) {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, status=? WHERE id=? AND role='guide'");
            $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['status'], $_POST['id']]);
            $stmt = $pdo->prepare("UPDATE guide_profiles SET experience_years=?, languages=?, specialization=?, hourly_rate=?, availability=? WHERE user_id=?");
            $stmt->execute([$_POST['experience_years'], $_POST['languages'], $_POST['specialization'], $_POST['hourly_rate'], $_POST['availability'], $_POST['id']]);
            toast("Guide updated!");
        } else {
            $password = password_hash('Guide@123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, 'guide', ?)");
            $stmt->execute([$_POST['name'], $_POST['email'], $password, $_POST['phone'], $_POST['status']]);
            $id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO guide_profiles (user_id, experience_years, languages, specialization, hourly_rate, availability) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $_POST['experience_years'], $_POST['languages'], $_POST['specialization'], $_POST['hourly_rate'], $_POST['availability']]);
            toast("Guide added! Default password: Guide@123");
        }
        redirect('guides.php');
    }
}

$guides = $pdo->query("SELECT u.*, gp.experience_years, gp.languages, gp.specialization, gp.hourly_rate, gp.rating, gp.availability 
                       FROM users u 
                       LEFT JOIN guide_profiles gp ON u.id = gp.user_id 
                       WHERE u.role = 'guide' 
                       ORDER BY u.created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-hiking"></i> Tour Guides</h3>
        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Guide</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Experience</th><th>Languages</th><th>Rate/Hr</th><th>Rating</th><th>Availability</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($guides as $g): ?>
                    <tr>
                        <td><?php echo $g['id']; ?></td>
                        <td><?php echo htmlspecialchars($g['name']); ?></td>
                        <td><?php echo htmlspecialchars($g['email']); ?></td>
                        <td><?php echo htmlspecialchars($g['phone']); ?></td>
                        <td><?php echo $g['experience_years']; ?> yrs</td>
                        <td><?php echo htmlspecialchars($g['languages']); ?></td>
                        <td><?php echo formatCurrency($g['hourly_rate']); ?></td>
                        <td><?php echo $g['rating']; ?> ★</td>
                        <td><span class="badge badge-<?php echo getStatusBadge($g['availability']); ?>"><?php echo ucfirst($g['availability']); ?></span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick='editGuide(<?php echo json_encode($g); ?>)'><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this guide?')">
                                <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
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
<div id="guideModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Guide</h3>
            <button class="close-btn" onclick="closeModal('guideModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="id" id="guideId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>Name *</label><input type="text" name="name" id="gName" class="form-control" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" id="gEmail" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" id="gPhone" class="form-control"></div>
                    <div class="form-group"><label>Experience (Years)</label><input type="number" name="experience_years" id="gExp" class="form-control" min="0"></div>
                </div>
                <div class="form-group"><label>Languages</label><input type="text" name="languages" id="gLang" class="form-control" placeholder="English, Sinhala, Tamil"></div>
                <div class="form-group"><label>Specialization</label><input type="text" name="specialization" id="gSpec" class="form-control" placeholder="Cultural, Wildlife, Adventure"></div>
                <div class="form-row">
                    <div class="form-group"><label>Hourly Rate (LKR)</label><input type="number" step="0.01" name="hourly_rate" id="gRate" class="form-control"></div>
                    <div class="form-group"><label>Availability</label>
                        <select name="availability" id="gAvail" class="form-control">
                            <option value="available">Available</option>
                            <option value="busy">Busy</option>
                            <option value="off">Off Duty</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status" id="gStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('guideModal')">Cancel</button>
                <button type="submit" name="save" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Guide';
    document.getElementById('guideId').value = '';
    document.getElementById('gName').value = '';
    document.getElementById('gEmail').value = '';
    document.getElementById('gPhone').value = '';
    document.getElementById('gExp').value = '';
    document.getElementById('gLang').value = '';
    document.getElementById('gSpec').value = '';
    document.getElementById('gRate').value = '';
    document.getElementById('gAvail').value = 'available';
    document.getElementById('gStatus').value = 'active';
    openModal('guideModal');
}

function editGuide(g) {
    document.getElementById('modalTitle').innerText = 'Edit Guide';
    document.getElementById('guideId').value = g.id;
    document.getElementById('gName').value = g.name;
    document.getElementById('gEmail').value = g.email;
    document.getElementById('gPhone').value = g.phone || '';
    document.getElementById('gExp').value = g.experience_years;
    document.getElementById('gLang').value = g.languages || '';
    document.getElementById('gSpec').value = g.specialization || '';
    document.getElementById('gRate').value = g.hourly_rate;
    document.getElementById('gAvail').value = g.availability;
    document.getElementById('gStatus').value = g.status;
    openModal('guideModal');
}
</script>

<?php include '../includes/footer.php'; ?>