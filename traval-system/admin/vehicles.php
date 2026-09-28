<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Vehicle Management';

// ============================================
// HANDLE ADD/EDIT/DELETE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO vehicles (vehicle_code, vehicle_name, vehicle_type, brand, model, year, color, seat_count, price_per_day, price_per_km, driver_name, driver_phone, driver_license, location, image_url, description, features, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['vehicle_code'], $_POST['vehicle_name'], $_POST['vehicle_type'],
            $_POST['brand'], $_POST['model'], $_POST['year'], $_POST['color'],
            $_POST['seat_count'], $_POST['price_per_day'], $_POST['price_per_km'],
            $_POST['driver_name'], $_POST['driver_phone'], $_POST['driver_license'],
            $_POST['location'], $_POST['image_url'], $_POST['description'],
            $_POST['features'], $_POST['status']
        ]);
        toast("Vehicle added successfully!");
        redirect('vehicles.php');
    } elseif (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE vehicles SET vehicle_code=?, vehicle_name=?, vehicle_type=?, brand=?, model=?, year=?, color=?, seat_count=?, price_per_day=?, price_per_km=?, driver_name=?, driver_phone=?, driver_license=?, location=?, image_url=?, description=?, features=?, status=? WHERE id=?");
        $stmt->execute([
            $_POST['vehicle_code'], $_POST['vehicle_name'], $_POST['vehicle_type'],
            $_POST['brand'], $_POST['model'], $_POST['year'], $_POST['color'],
            $_POST['seat_count'], $_POST['price_per_day'], $_POST['price_per_km'],
            $_POST['driver_name'], $_POST['driver_phone'], $_POST['driver_license'],
            $_POST['location'], $_POST['image_url'], $_POST['description'],
            $_POST['features'], $_POST['status'], $_POST['id']
        ]);
        toast("Vehicle updated successfully!");
        redirect('vehicles.php');
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id=?");
        $stmt->execute([$_POST['id']]);
        toast("Vehicle deleted!", 'warning');
        redirect('vehicles.php');
    }
}

$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY created_at DESC")->fetchAll();

// ============================================
// GET VEHICLE BOOKINGS (Admin View)
// ============================================
$vehicle_bookings = $pdo->query("SELECT vb.*, u.name as customer_name 
                                 FROM vehicle_bookings vb 
                                 LEFT JOIN bookings b ON vb.booking_id = b.id 
                                 LEFT JOIN users u ON b.customer_id = u.id 
                                 ORDER BY vb.created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<style>
.vehicle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    margin-top: 20px;
}
.vehicle-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #f0f2f5;
}
.vehicle-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    border-color: #0a5c4a;
}
.vehicle-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
}
.vehicle-info {
    padding: 18px;
}
.vehicle-type-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: #e8f5f0;
    color: #0a5c4a;
}
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.status-available { background: #d4f5e6; color: #27ae60; }
.status-booked { background: #fff3cd; color: #f39c12; }
.status-maintenance { background: #fee2e2; color: #e74c3c; }
.status-inactive { background: #e2e8f0; color: #7f8c8d; }

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(6px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal.show {
    display: flex !important;
}
.modal-content {
    background: #fff;
    border-radius: 20px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.4s ease;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: scale(0.92) translateY(30px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.modal-header {
    padding: 16px 24px;
    border-bottom: 2px solid #e8f5f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fcfb;
    border-radius: 20px 20px 0 0;
    position: sticky;
    top: 0;
    z-index: 10;
}
.modal-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}
.close-btn {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #7f8c8d;
    width: 36px;
    height: 36px;
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
    padding: 20px 24px;
    max-height: calc(90vh - 160px);
    overflow-y: auto;
}
.modal-footer {
    padding: 14px 24px;
    border-top: 1px solid #edf2f7;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #f8f9fa;
    border-radius: 0 0 20px 20px;
    position: sticky;
    bottom: 0;
    z-index: 10;
}
</style>

<!-- PAGE HEADER -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 style="font-size:24px; font-weight:700; color:var(--dark);">
            <i class="fas fa-car" style="color:var(--primary);"></i> Vehicle Management
        </h2>
        <p style="color:var(--gray); font-size:14px; margin-top:4px;">
            <?php echo count($vehicles); ?> vehicles registered
        </p>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus"></i> Add Vehicle
    </button>
</div>

<!-- VEHICLES GRID -->
<div class="vehicle-grid">
    <?php if (empty($vehicles)): ?>
        <p style="text-align:center; padding:60px; color:var(--gray); grid-column:1/-1;">
            <i class="fas fa-car" style="font-size:48px; display:block; margin-bottom:15px;"></i>
            No vehicles available. Click "Add Vehicle" to get started.
        </p>
    <?php else: ?>
        <?php foreach ($vehicles as $v): ?>
        <div class="vehicle-card">
            <img src="<?php echo htmlspecialchars($v['image_url'] ?: 'https://via.placeholder.com/400x200?text=Vehicle'); ?>" 
                 class="vehicle-image" alt="<?php echo htmlspecialchars($v['vehicle_name']); ?>">
            <div class="vehicle-info">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                    <h3 style="font-size:16px; font-weight:700; color:var(--dark); margin:0;">
                        <?php echo htmlspecialchars($v['vehicle_name']); ?>
                    </h3>
                    <span class="status-badge status-<?php echo $v['status']; ?>">
                        <?php echo ucfirst($v['status']); ?>
                    </span>
                </div>
                
                <div style="display:flex; gap:6px; margin-bottom:10px; flex-wrap:wrap;">
                    <span class="vehicle-type-badge"><?php echo $v['vehicle_type']; ?></span>
                    <span style="background:#e2e8f0; color:#5d6d7e; padding:4px 10px; border-radius:20px; font-size:10px; font-weight:600;">
                        <?php echo htmlspecialchars($v['vehicle_code']); ?>
                    </span>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; font-size:12px; color:var(--gray); margin-bottom:10px;">
                    <div><i class="fas fa-users" style="color:var(--primary);"></i> <?php echo $v['seat_count']; ?> Seats</div>
                    <div><i class="fas fa-map-marker-alt" style="color:var(--secondary);"></i> <?php echo htmlspecialchars($v['location']); ?></div>
                    <div><i class="fas fa-user" style="color:var(--info);"></i> <?php echo htmlspecialchars($v['driver_name']); ?></div>
                    <div><i class="fas fa-phone" style="color:var(--success);"></i> <?php echo htmlspecialchars($v['driver_phone']); ?></div>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid #edf2f7; margin-bottom:12px;">
                    <div>
                        <span style="font-size:18px; font-weight:700; color:var(--primary);">
                            <?php echo formatCurrency($v['price_per_day']); ?>
                        </span>
                        <span style="font-size:11px; color:var(--gray);">/day</span>
                    </div>
                    <div style="font-size:12px; color:var(--warning);">
                        ⭐ <?php echo number_format($v['rating'], 1); ?>
                    </div>
                </div>
                
                <div style="display:flex; gap:8px;">
                    <button class="btn btn-primary btn-sm" style="flex:1; justify-content:center;" 
                            onclick='editVehicle(<?php echo json_encode($v); ?>)'>
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this vehicle?')">
                        <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                        <button type="submit" name="delete" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- VEHICLE BOOKINGS LIST -->
<div class="card" style="margin-top:30px;">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Vehicle Bookings</h3>
        <span style="font-size:13px; color:var(--gray);"><?php echo count($vehicle_bookings); ?> bookings</span>
    </div>
    <div class="card-body">
        <?php if (empty($vehicle_bookings)): ?>
            <p style="text-align:center; padding:30px; color:var(--gray);">No vehicle bookings yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vehicle</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Date</th>
                            <th>Days</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicle_bookings as $vb): ?>
                        <tr>
                            <td>#<?php echo $vb['id']; ?></td>
                            <td><?php echo htmlspecialchars($vb['vehicle_type']); ?></td>
                            <td><?php echo htmlspecialchars($vb['pickup_location']); ?></td>
                            <td><?php echo htmlspecialchars($vb['dropoff_location'] ?? '-'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($vb['pickup_date'])); ?></td>
                            <td><?php echo $vb['days']; ?></td>
                            <td><?php echo formatCurrency($vb['total_price']); ?></td>
                            <td><span class="badge badge-<?php echo getStatusBadge($vb['status']); ?>"><?php echo ucfirst($vb['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- VEHICLE MODAL -->
<div id="vehicleModal" class="modal">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-car"></i> Add Vehicle</h3>
            <button class="close-btn" onclick="closeModal('vehicleModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="id" id="vehId">
            <div class="modal-body">
                
                <div class="form-row">
                    <div class="form-group"><label>Vehicle Code *</label><input type="text" name="vehicle_code" id="vehCode" class="form-control" placeholder="VEH-001" required></div>
                    <div class="form-group"><label>Vehicle Name *</label><input type="text" name="vehicle_name" id="vehName" class="form-control" placeholder="Toyota Prius" required></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Vehicle Type *</label>
                        <select name="vehicle_type" id="vehType" class="form-control" required>
                            <option value="Car">🚗 Car</option>
                            <option value="SUV">🚙 SUV</option>
                            <option value="Van">🚐 Van</option>
                            <option value="Bus">🚌 Bus</option>
                            <option value="Motorbike">🏍️ Motorbike</option>
                            <option value="Tuk Tuk">🛺 Tuk Tuk</option>
                            <option value="Luxury Car">✨ Luxury Car</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Brand</label><input type="text" name="brand" id="vehBrand" class="form-control" placeholder="Toyota"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group"><label>Model</label><input type="text" name="model" id="vehModel" class="form-control" placeholder="Prius 2022"></div>
                    <div class="form-group"><label>Year</label><input type="number" name="year" id="vehYear" class="form-control" placeholder="2022" min="1990" max="2030"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group"><label>Color</label><input type="text" name="color" id="vehColor" class="form-control" placeholder="White"></div>
                    <div class="form-group"><label>Seat Count</label><input type="number" name="seat_count" id="vehSeats" class="form-control" value="4" min="1"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group"><label>Price Per Day (LKR) *</label><input type="number" step="0.01" name="price_per_day" id="vehPriceDay" class="form-control" required></div>
                    <div class="form-group"><label>Price Per Km (LKR)</label><input type="number" step="0.01" name="price_per_km" id="vehPriceKm" class="form-control" value="0"></div>
                </div>
                
                <div style="background:#f0f7f5; padding:12px; border-radius:10px; margin-bottom:16px; border-left:4px solid var(--primary);">
                    <h4 style="color:var(--primary); font-size:14px; margin-bottom:10px;"><i class="fas fa-user-tie"></i> Driver Information</h4>
                    <div class="form-row">
                        <div class="form-group"><label>Driver Name</label><input type="text" name="driver_name" id="vehDriver" class="form-control" placeholder="Saman Silva"></div>
                        <div class="form-group"><label>Driver Phone</label><input type="text" name="driver_phone" id="vehDriverPhone" class="form-control" placeholder="0771234567"></div>
                    </div>
                    <div class="form-group"><label>Driver License</label><input type="text" name="driver_license" id="vehDriverLicense" class="form-control" placeholder="B1234567"></div>
                </div>
                
                <div class="form-group"><label>Location *</label><input type="text" name="location" id="vehLocation" class="form-control" placeholder="Colombo" required></div>
                <div class="form-group"><label>Image URL</label><input type="text" name="image_url" id="vehImage" class="form-control" placeholder="https://images.unsplash.com/..."></div>
                <div class="form-group"><label>Description</label><textarea name="description" id="vehDesc" class="form-control" rows="2"></textarea></div>
                <div class="form-group"><label>Features (comma separated)</label><textarea name="features" id="vehFeatures" class="form-control" rows="2" placeholder="AC, GPS, Bluetooth"></textarea></div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="vehStatus" class="form-control">
                        <option value="available">✅ Available</option>
                        <option value="booked">📅 Booked</option>
                        <option value="maintenance">🔧 Maintenance</option>
                        <option value="inactive">❌ Inactive</option>
                    </select>
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background:#e2e8f0;" onclick="closeModal('vehicleModal')">Cancel</button>
                <button type="submit" name="add" id="submitBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Vehicle
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-car"></i> Add Vehicle';
    document.getElementById('vehId').value = '';
    document.getElementById('vehCode').value = '';
    document.getElementById('vehName').value = '';
    document.getElementById('vehType').value = 'Car';
    document.getElementById('vehBrand').value = '';
    document.getElementById('vehModel').value = '';
    document.getElementById('vehYear').value = '';
    document.getElementById('vehColor').value = '';
    document.getElementById('vehSeats').value = '4';
    document.getElementById('vehPriceDay').value = '';
    document.getElementById('vehPriceKm').value = '0';
    document.getElementById('vehDriver').value = '';
    document.getElementById('vehDriverPhone').value = '';
    document.getElementById('vehDriverLicense').value = '';
    document.getElementById('vehLocation').value = '';
    document.getElementById('vehImage').value = '';
    document.getElementById('vehDesc').value = '';
    document.getElementById('vehFeatures').value = '';
    document.getElementById('vehStatus').value = 'available';
    document.getElementById('submitBtn').name = 'add';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save Vehicle';
    openModal('vehicleModal');
}

function editVehicle(v) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Vehicle';
    document.getElementById('vehId').value = v.id;
    document.getElementById('vehCode').value = v.vehicle_code;
    document.getElementById('vehName').value = v.vehicle_name;
    document.getElementById('vehType').value = v.vehicle_type;
    document.getElementById('vehBrand').value = v.brand || '';
    document.getElementById('vehModel').value = v.model || '';
    document.getElementById('vehYear').value = v.year || '';
    document.getElementById('vehColor').value = v.color || '';
    document.getElementById('vehSeats').value = v.seat_count;
    document.getElementById('vehPriceDay').value = v.price_per_day;
    document.getElementById('vehPriceKm').value = v.price_per_km;
    document.getElementById('vehDriver').value = v.driver_name || '';
    document.getElementById('vehDriverPhone').value = v.driver_phone || '';
    document.getElementById('vehDriverLicense').value = v.driver_license || '';
    document.getElementById('vehLocation').value = v.location || '';
    document.getElementById('vehImage').value = v.image_url || '';
    document.getElementById('vehDesc').value = v.description || '';
    document.getElementById('vehFeatures').value = v.features || '';
    document.getElementById('vehStatus').value = v.status;
    document.getElementById('submitBtn').name = 'edit';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Vehicle';
    openModal('vehicleModal');
}

function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(function(m) {
            m.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>