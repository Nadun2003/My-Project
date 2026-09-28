<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Package Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO packages (name, destination, duration_days, duration_nights, price, description, included_services, max_people, image_url, package_code, category, difficulty_level, meeting_point, best_season, languages, highlights, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'], 
            $_POST['destination'], 
            $_POST['duration_days'], 
            $_POST['duration_nights'], 
            $_POST['price'], 
            $_POST['description'], 
            $_POST['included_services'], 
            $_POST['max_people'], 
            $_POST['image_url'],
            $_POST['package_code'],
            $_POST['category'],
            $_POST['difficulty_level'],
            $_POST['meeting_point'],
            $_POST['best_season'],
            $_POST['languages'],
            $_POST['highlights'],
            $_POST['status'], 
            $_SESSION['user_id']
        ]);
        toast("Package added successfully!");
        redirect('packages.php');
    } elseif (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE packages SET name=?, destination=?, duration_days=?, duration_nights=?, price=?, description=?, included_services=?, max_people=?, image_url=?, package_code=?, category=?, difficulty_level=?, meeting_point=?, best_season=?, languages=?, highlights=?, status=? WHERE id=?");
        $stmt->execute([
            $_POST['name'], 
            $_POST['destination'], 
            $_POST['duration_days'], 
            $_POST['duration_nights'], 
            $_POST['price'], 
            $_POST['description'], 
            $_POST['included_services'], 
            $_POST['max_people'], 
            $_POST['image_url'],
            $_POST['package_code'],
            $_POST['category'],
            $_POST['difficulty_level'],
            $_POST['meeting_point'],
            $_POST['best_season'],
            $_POST['languages'],
            $_POST['highlights'],
            $_POST['status'], 
            $_POST['id']
        ]);
        toast("Package updated successfully!");
        redirect('packages.php');
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id=?");
        $stmt->execute([$_POST['id']]);
        toast("Package deleted!", 'warning');
        redirect('packages.php');
    }
}

$packages = $pdo->query("SELECT * FROM packages ORDER BY created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<style>
/* ============================================
   MODAL STYLES - PERFECT CENTER
   ============================================ */
.modal {
    display: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    z-index: 99999 !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
    margin: 0 !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
}

.modal.show {
    display: flex !important;
}

.modal-content {
    background: #ffffff !important;
    border-radius: 20px !important;
    max-width: 650px !important;
    width: 100% !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
    animation: modalPop 0.3s ease !important;
    margin: auto !important;
    position: relative !important;
    top: auto !important;
    left: auto !important;
    transform: none !important;
}

@keyframes modalPop {
    0% {
        opacity: 0;
        transform: scale(0.9) translateY(-30px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal-header {
    padding: 18px 24px;
    border-bottom: 3px solid #2ecc71;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f0fff4;
    border-radius: 20px 20px 0 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

.modal-header h3 {
    font-size: 20px;
    font-weight: 700;
    color: #1a5a3a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-header h3 i {
    color: #27ae60;
}

.close-btn {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #95a5a6;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.close-btn:hover {
    color: #e74c3c;
    background: rgba(231, 76, 60, 0.08);
    transform: rotate(90deg);
}

.modal-body {
    padding: 24px;
    background: #ffffff;
    max-height: calc(90vh - 140px);
    overflow-y: auto;
}

.modal-body .form-group {
    margin-bottom: 16px;
}

.modal-body .form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 14px;
    color: #2c3e50;
}

.modal-body .form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e0e7e9;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fafcfc;
    color: #2c3e50;
    box-sizing: border-box;
}

.modal-body .form-control:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
}

.modal-body .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 2px solid #f0fff4;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #fafcfc;
    border-radius: 0 0 20px 20px;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

/* Mobile */
@media (max-width: 768px) {
    .modal {
        padding: 10px !important;
    }
    .modal-content {
        max-width: 100% !important;
        max-height: 95vh !important;
        border-radius: 16px !important;
        margin: 0 auto !important;
    }
    .modal-body {
        max-height: calc(95vh - 130px);
        padding: 16px;
    }
    .modal-header { padding: 14px 16px; }
    .modal-header h3 { font-size: 17px; }
    .modal-footer { padding: 12px 16px; flex-direction: column; }
    .modal-footer .btn { width: 100%; justify-content: center; }
    .modal-body .form-row { grid-template-columns: 1fr; gap: 0; }
}

/* Scrollbar */
.modal-content::-webkit-scrollbar {
    width: 6px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f0f2f5;
    border-radius: 10px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: #27ae60;
    border-radius: 10px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #1e8449;
}
</style>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-box"></i> Tour Packages</h3>
        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Package</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Image</th><th>Code</th><th>Name</th><th>Category</th><th>Duration</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($packages as $p): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/50x50?text=Package'); ?>" width="50" height="50" style="object-fit:cover;border-radius:8px;"></td>
                        <td><span style="background:#e2e8f0; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600;"><?php echo htmlspecialchars($p['package_code'] ?? 'N/A'); ?></span></td>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><span style="background:#e8f5f0; padding:2px 10px; border-radius:20px; font-size:11px; color:#0a5c4a;"><?php echo htmlspecialchars($p['category'] ?? 'General'); ?></span></td>
                        <td><?php echo $p['duration_days']; ?>D / <?php echo $p['duration_nights']; ?>N</td>
                        <td><?php echo formatCurrency($p['price']); ?></td>
                        <td><span class="badge badge-<?php echo getStatusBadge($p['status']); ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick='editPackage(<?php echo json_encode($p); ?>)'><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this package?')">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
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

<!-- ============================================
     PACKAGE MODAL - PERFECT CENTER
     ============================================ -->
<div id="packageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-box"></i> Add Package</h3>
            <button class="close-btn" onclick="closeModal('packageModal')">&times;</button>
        </div>
        <form method="POST" id="packageForm">
            <input type="hidden" name="id" id="pkgId">
            <div class="modal-body">
                <!-- Basic Info -->
                <div class="form-row">
                    <div class="form-group"><label>Name *</label><input type="text" name="name" id="pkgName" class="form-control" required></div>
                    <div class="form-group"><label>Destination *</label><input type="text" name="destination" id="pkgDest" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Package Code</label><input type="text" name="package_code" id="pkgCode" class="form-control" placeholder="PKG-001"></div>
                    <div class="form-group"><label>Category</label>
                        <select name="category" id="pkgCategory" class="form-control">
                            <option value="Adventure">🌄 Adventure</option>
                            <option value="Cultural">🏛️ Cultural</option>
                            <option value="Beach">🏖️ Beach</option>
                            <option value="Wildlife">🐘 Wildlife</option>
                            <option value="Hill Country">⛰️ Hill Country</option>
                            <option value="Spiritual">🕉️ Spiritual</option>
                            <option value="General">🌟 General</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Days *</label><input type="number" name="duration_days" id="pkgDays" class="form-control" required min="1"></div>
                    <div class="form-group"><label>Nights *</label><input type="number" name="duration_nights" id="pkgNights" class="form-control" required min="0"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Price (LKR) *</label><input type="number" step="0.01" name="price" id="pkgPrice" class="form-control" required></div>
                    <div class="form-group"><label>Max People *</label><input type="number" name="max_people" id="pkgMax" class="form-control" required min="1"></div>
                </div>
                
                <!-- New Fields -->
                <div class="form-row">
                    <div class="form-group"><label>Difficulty Level</label>
                        <select name="difficulty_level" id="pkgDifficulty" class="form-control">
                            <option value="Easy">🟢 Easy</option>
                            <option value="Medium">🟡 Medium</option>
                            <option value="Hard">🔴 Hard</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Meeting Point</label>
                        <input type="text" name="meeting_point" id="pkgMeeting" class="form-control" placeholder="Colombo Fort, Kandy City Center">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Best Season</label>
                        <input type="text" name="best_season" id="pkgSeason" class="form-control" placeholder="January - March">
                    </div>
                    <div class="form-group"><label>Languages</label>
                        <input type="text" name="languages" id="pkgLanguages" class="form-control" placeholder="English, Sinhala, Tamil">
                    </div>
                </div>
                <div class="form-group"><label>Highlights</label>
                    <textarea name="highlights" id="pkgHighlights" class="form-control" rows="2" placeholder="Sigiriya Rock, Temple of Tooth, etc."></textarea>
                </div>
                
                <!-- Old Fields -->
                <div class="form-group"><label>Image URL</label><input type="text" name="image_url" id="pkgImage" class="form-control" placeholder="https://images.unsplash.com/..."></div>
                <div class="form-group"><label>Included Services</label><textarea name="included_services" id="pkgInc" class="form-control" rows="2" placeholder="Hotel, Transport, Meals, Guide"></textarea></div>
                <div class="form-group"><label>Description *</label><textarea name="description" id="pkgDesc" class="form-control" rows="3" required></textarea></div>
                
                <div class="form-group" id="statusGroup" style="display:none;">
                    <label>Status</label>
                    <select name="status" id="pkgStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background:#e2e8f0; color:#2c3e50; border:none; padding:10px 24px; border-radius:10px; font-weight:600; cursor:pointer;" onclick="closeModal('packageModal')">Cancel</button>
                <button type="submit" name="add" id="submitBtn" class="btn btn-primary" style="background:#27ae60; color:white; border:none; padding:10px 24px; border-radius:10px; font-weight:600; cursor:pointer;">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT - FIXED
     ============================================ -->
<script>
// ============================================
// OPEN MODAL - PERFECT CENTER
// ============================================
function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        setTimeout(function() {
            modal.classList.add('show');
        }, 10);
        document.body.style.overflow = 'hidden';
    }
}

// ============================================
// CLOSE MODAL
// ============================================
function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        setTimeout(function() {
            modal.style.display = 'none';
            modal.style.alignItems = '';
            modal.style.justifyContent = '';
        }, 300);
        document.body.style.overflow = '';
    }
}

// ============================================
// OPEN ADD MODAL
// ============================================
function openAddModal() {
    console.log('Opening Add Package Modal...');
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box"></i> Add Package';
    document.getElementById('pkgId').value = '';
    document.getElementById('pkgName').value = '';
    document.getElementById('pkgDest').value = '';
    document.getElementById('pkgDays').value = '';
    document.getElementById('pkgNights').value = '';
    document.getElementById('pkgPrice').value = '';
    document.getElementById('pkgMax').value = '';
    document.getElementById('pkgImage').value = '';
    document.getElementById('pkgInc').value = '';
    document.getElementById('pkgDesc').value = '';
    document.getElementById('pkgCode').value = '';
    document.getElementById('pkgCategory').value = 'General';
    document.getElementById('pkgDifficulty').value = 'Easy';
    document.getElementById('pkgMeeting').value = '';
    document.getElementById('pkgSeason').value = '';
    document.getElementById('pkgLanguages').value = '';
    document.getElementById('pkgHighlights').value = '';
    
    document.getElementById('statusGroup').style.display = 'none';
    document.getElementById('submitBtn').name = 'add';
    document.getElementById('submitBtn').innerHTML = 'Save';
    
    // Open modal - PERFECT CENTER
    var modal = document.getElementById('packageModal');
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    setTimeout(function() {
        modal.classList.add('show');
    }, 10);
    document.body.style.overflow = 'hidden';
    
    // Focus on first input
    setTimeout(function() {
        document.getElementById('pkgName').focus();
    }, 400);
}

// ============================================
// EDIT PACKAGE
// ============================================
function editPackage(p) {
    console.log('Editing Package:', p);
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Package';
    document.getElementById('pkgId').value = p.id;
    document.getElementById('pkgName').value = p.name;
    document.getElementById('pkgDest').value = p.destination;
    document.getElementById('pkgDays').value = p.duration_days;
    document.getElementById('pkgNights').value = p.duration_nights;
    document.getElementById('pkgPrice').value = p.price;
    document.getElementById('pkgMax').value = p.max_people;
    document.getElementById('pkgImage').value = p.image_url || '';
    document.getElementById('pkgInc').value = p.included_services || '';
    document.getElementById('pkgDesc').value = p.description;
    document.getElementById('pkgCode').value = p.package_code || '';
    document.getElementById('pkgCategory').value = p.category || 'General';
    document.getElementById('pkgDifficulty').value = p.difficulty_level || 'Easy';
    document.getElementById('pkgMeeting').value = p.meeting_point || '';
    document.getElementById('pkgSeason').value = p.best_season || '';
    document.getElementById('pkgLanguages').value = p.languages || '';
    document.getElementById('pkgHighlights').value = p.highlights || '';
    
    document.getElementById('pkgStatus').value = p.status || 'active';
    document.getElementById('statusGroup').style.display = 'block';
    document.getElementById('submitBtn').name = 'edit';
    document.getElementById('submitBtn').innerHTML = 'Update';
    
    // Open modal - PERFECT CENTER
    var modal = document.getElementById('packageModal');
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    setTimeout(function() {
        modal.classList.add('show');
    }, 10);
    document.body.style.overflow = 'hidden';
}

// ============================================
// CLOSE ON OVERLAY CLICK
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('packageModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal('packageModal');
            }
        });
    }
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var modal = document.getElementById('packageModal');
            if (modal && modal.classList.contains('show')) {
                closeModal('packageModal');
            }
        }
    });
    
    console.log('Package Management page loaded');
});

// ============================================
// FORM SUBMIT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('packageForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submitting...');
            return true;
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>