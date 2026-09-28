<?php
require_once '../config/database.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    redirect('../login.php');
}

$page_title = 'Book Vehicle';
$user_id = $_SESSION['user_id'];

// ============================================
// GET ALL AVAILABLE VEHICLES
// ============================================
$vehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' ORDER BY price_per_day ASC")->fetchAll();

// ============================================
// HANDLE VEHICLE BOOKING
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_vehicle'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $pickup_location = $_POST['pickup_location'];
    $dropoff_location = $_POST['dropoff_location'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_time = $_POST['pickup_time'];
    $days = $_POST['days'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND status = 'available'");
    $stmt->execute([$vehicle_id]);
    $vehicle = $stmt->fetch();
    
    if ($vehicle) {
        $total_price = $vehicle['price_per_day'] * $days;
        $dropoff_date = date('Y-m-d H:i:s', strtotime($pickup_date . ' + ' . $days . ' days'));
        $pickup_datetime = $pickup_date . ' ' . $pickup_time;
        
        $stmt = $pdo->prepare("INSERT INTO vehicle_bookings 
            (vehicle_id, vehicle_type, pickup_location, dropoff_location, pickup_date, dropoff_date, days, pickup_time, total_price, status, driver_name, driver_phone, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        
        $stmt->execute([
            $vehicle_id,
            $vehicle['vehicle_name'] . ' (' . $vehicle['vehicle_type'] . ')',
            $pickup_location,
            $dropoff_location,
            $pickup_datetime,
            $dropoff_date,
            $days,
            $pickup_time,
            $total_price,
            $vehicle['driver_name'],
            $vehicle['driver_phone'],
            $notes
        ]);
        
        $stmt = $pdo->prepare("UPDATE vehicles SET status = 'booked' WHERE id = ?");
        $stmt->execute([$vehicle_id]);
        
        toast("Vehicle booked successfully! Our team will contact you soon.");
        redirect('bookings.php');
    } else {
        toast("Vehicle not available.", 'error');
        redirect('vehicles.php');
    }
}

include '../includes/header.php';
?>

<style>
/* ============================================
   VEHICLE GRID
   ============================================ */
.vehicle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.vehicle-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid #f0f2f5;
}

.vehicle-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    border-color: #27ae60;
}

.vehicle-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.vehicle-card:hover .vehicle-image {
    transform: scale(1.03);
}

.vehicle-info {
    padding: 20px;
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

.vehicle-code-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    background: #e2e8f0;
    color: #5d6d7e;
}

.vehicle-detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6c7a8a;
}

.vehicle-detail-item i {
    color: #27ae60;
    width: 16px;
}

.btn-book {
    background: linear-gradient(135deg, #27ae60, #1e8449);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
}

.btn-book:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(39, 174, 96, 0.35);
}

/* ============================================
   BOOKING MODAL - CENTERED & TIGHT
   ============================================ */
.booking-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.booking-modal.show {
    display: flex !important;
}

.booking-modal-content {
    background: #fff;
    border-radius: 20px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.4s ease;
    margin: auto;
    position: relative;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: scale(0.92) translateY(30px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.booking-modal-content::-webkit-scrollbar {
    width: 5px;
}
.booking-modal-content::-webkit-scrollbar-track {
    background: #f0f2f5;
}
.booking-modal-content::-webkit-scrollbar-thumb {
    background: #27ae60;
    border-radius: 10px;
}

/* ============================================
   MODAL HEADER - TIGHTER
   ============================================ */
.booking-modal-header {
    padding: 14px 20px;
    border-bottom: 3px solid #27ae60;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f0fff4;
    border-radius: 20px 20px 0 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

.booking-modal-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1a5a3a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.booking-modal-header h3 i {
    color: #27ae60;
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 26px;
    cursor: pointer;
    color: #95a5a6;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.modal-close-btn:hover {
    color: #e74c3c;
    background: rgba(231, 76, 60, 0.08);
    transform: rotate(90deg);
}

/* ============================================
   MODAL BODY - TIGHTER SPACING
   ============================================ */
.booking-modal-body {
    padding: 16px 20px 20px 20px;
    max-height: calc(90vh - 160px);
    overflow-y: auto;
}

/* ============================================
   FORM ELEMENTS - TIGHTER
   ============================================ */
.form-group {
    margin-bottom: 12px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 12.5px;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fafcfc;
    color: #2c3e50;
    font-family: 'Poppins', sans-serif;
}

.form-control:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.1);
    background: #ffffff;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* ============================================
   MODAL FOOTER - TIGHTER
   ============================================ */
.booking-modal-footer {
    padding: 12px 20px;
    border-top: 2px solid #f0fff4;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #fafcfc;
    border-radius: 0 0 20px 20px;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
    .vehicle-grid {
        grid-template-columns: 1fr;
    }
    .booking-modal-content {
        margin: 10px;
        border-radius: 16px;
        max-height: 95vh;
    }
    .booking-modal-header {
        padding: 12px 16px;
    }
    .booking-modal-header h3 {
        font-size: 16px;
    }
    .booking-modal-body {
        padding: 12px 16px 16px 16px;
    }
    .booking-modal-footer {
        padding: 10px 16px;
        flex-direction: column;
    }
    .booking-modal-footer button {
        width: 100%;
        justify-content: center;
    }
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<!-- ============================================
     PAGE HEADER
     ============================================ -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 24px; font-weight: 700; color: var(--dark);">
            <i class="fas fa-car" style="color: var(--primary);"></i> Book a Vehicle
        </h2>
        <p style="color: var(--gray); font-size: 14px; margin-top: 4px;">
            <?php echo count($vehicles); ?> vehicles available for booking
        </p>
    </div>
</div>

<!-- ============================================
     VEHICLES GRID
     ============================================ -->
<?php if (empty($vehicles)): ?>
    <div class="card" style="padding: 60px; text-align: center;">
        <i class="fas fa-car" style="font-size: 64px; color: var(--primary-light); margin-bottom: 20px; display: block;"></i>
        <h3 style="font-size: 20px; color: var(--dark); margin-bottom: 10px;">No Vehicles Available</h3>
        <p style="color: var(--gray);">All vehicles are currently booked. Please check back later.</p>
    </div>
<?php else: ?>
    <div class="vehicle-grid">
        <?php foreach ($vehicles as $v): ?>
        <div class="vehicle-card">
            <img src="<?php echo htmlspecialchars($v['image_url'] ?: 'https://via.placeholder.com/400x200?text=Vehicle'); ?>" 
                 class="vehicle-image" alt="<?php echo htmlspecialchars($v['vehicle_name']); ?>">
            <div class="vehicle-info">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <h3 style="font-size: 17px; font-weight: 700; color: var(--dark); margin: 0;">
                        <?php echo htmlspecialchars($v['vehicle_name']); ?>
                    </h3>
                    <span class="vehicle-code-badge"><?php echo htmlspecialchars($v['vehicle_code']); ?></span>
                </div>
                
                <div style="display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap;">
                    <span class="vehicle-type-badge"><?php echo $v['vehicle_type']; ?></span>
                    <?php if ($v['brand'] && $v['model']): ?>
                        <span style="background: #e2e8f0; color: #5d6d7e; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                            <?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px;">
                    <div class="vehicle-detail-item">
                        <i class="fas fa-users"></i> <?php echo $v['seat_count']; ?> Seats
                    </div>
                    <div class="vehicle-detail-item">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($v['location']); ?>
                    </div>
                    <?php if ($v['driver_name']): ?>
                    <div class="vehicle-detail-item">
                        <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($v['driver_name']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($v['driver_phone']): ?>
                    <div class="vehicle-detail-item">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($v['driver_phone']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="color: #f5a623; font-size: 14px;">
                        <?php 
                        $rating = round($v['rating']);
                        for($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating ? '★' : '☆';
                        }
                        ?>
                    </span>
                    <span style="font-size: 13px; color: var(--gray);">
                        <?php echo number_format($v['rating'], 1); ?> (<?php echo $v['total_reviews']; ?> reviews)
                    </span>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid #edf2f7; margin-bottom: 14px;">
                    <div>
                        <span style="font-size: 24px; font-weight: 800; color: #27ae60;">
                            <?php echo formatCurrency($v['price_per_day']); ?>
                        </span>
                        <span style="font-size: 13px; color: var(--gray);">/day</span>
                    </div>
                    <?php if ($v['price_per_km'] > 0): ?>
                    <div style="font-size: 12px; color: var(--gray);">
                        + <?php echo formatCurrency($v['price_per_km']); ?>/km
                    </div>
                    <?php endif; ?>
                </div>
                
                <button class="btn-book" onclick="openBookingModal(<?php echo $v['id']; ?>, '<?php echo addslashes($v['vehicle_name']); ?>', <?php echo $v['price_per_day']; ?>)">
                    <i class="fas fa-calendar-check"></i> Book Now
                </button>
                
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============================================
     BOOKING MODAL - CENTERED
     ============================================ -->
<div id="vehicleBookingModal" class="booking-modal">
    <div class="booking-modal-content">
        
        <!-- Header -->
        <div class="booking-modal-header">
            <h3><i class="fas fa-car"></i> Book Vehicle</h3>
            <button class="modal-close-btn" onclick="closeVehicleModal()">&times;</button>
        </div>
        
        <!-- Form -->
        <form method="POST">
            <input type="hidden" name="vehicle_id" id="modalVehicleId">
            <div class="booking-modal-body">
                
                <!-- Vehicle Info -->
                <div style="background: #f0fff4; padding: 14px; border-radius: 12px; margin-bottom: 16px; text-align: center; border: 2px dashed #27ae60;">
                    <strong id="modalVehicleName" style="color: #1a5a3a; font-size: 16px; display: block; margin-bottom: 4px;"></strong>
                    <div style="font-size: 13px; color: #6c7a8a;">
                        Price: <span id="modalVehiclePrice" style="font-weight: 700; color: #27ae60; font-size: 15px;"></span> /day
                    </div>
                </div>
                
                <!-- Pickup -->
                <div class="form-group">
                    <label>Pickup Location <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="pickup_location" class="form-control" placeholder="Enter pickup location" required>
                </div>
                
                <!-- Dropoff -->
                <div class="form-group">
                    <label>Dropoff Location <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="dropoff_location" class="form-control" placeholder="Enter dropoff location" required>
                </div>
                
                <!-- Date & Time -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Pickup Date <span style="color: #e74c3c;">*</span></label>
                        <input type="date" name="pickup_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Pickup Time <span style="color: #e74c3c;">*</span></label>
                        <input type="time" name="pickup_time" class="form-control" value="09:00" required>
                    </div>
                </div>
                
                <!-- Days -->
                <div class="form-group">
                    <label>Number of Days <span style="color: #e74c3c;">*</span></label>
                    <input type="number" name="days" id="modalDays" class="form-control" min="1" value="1" required>
                </div>
                
                <!-- Total Price -->
                <div style="background: linear-gradient(135deg, #27ae60, #1a7a3a); color: white; padding: 14px; border-radius: 12px; text-align: center; margin-bottom: 14px;">
                    <div style="font-size: 13px; opacity: 0.9;">Total Price</div>
                    <div style="font-size: 28px; font-weight: 800;" id="modalTotalPrice">LKR 0.00</div>
                </div>
                
                <!-- Notes -->
                <div class="form-group">
                    <label>Special Requests / Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Any special requirements..."></textarea>
                </div>
                
                <!-- Info -->
                <div style="background: #fff3cd; padding: 12px; border-radius: 10px; font-size: 13px; color: #856404;">
                    <i class="fas fa-info-circle"></i> Our team will contact you within 24 hours to confirm your booking.
                </div>
                
            </div>
            
            <!-- Footer -->
            <div class="booking-modal-footer">
                <button type="button" onclick="closeVehicleModal()" style="padding: 10px 24px; background: #e2e8f0; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">
                    Cancel
                </button>
                <button type="submit" name="book_vehicle" style="padding: 10px 24px; background: #27ae60; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 14px;">
                    <i class="fas fa-check"></i> Confirm Booking
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var currentVehiclePrice = 0;

function openBookingModal(id, name, price) {
    document.getElementById('modalVehicleId').value = id;
    document.getElementById('modalVehicleName').textContent = name;
    document.getElementById('modalVehiclePrice').textContent = 'LKR ' + parseFloat(price).toFixed(2);
    currentVehiclePrice = parseFloat(price);
    document.getElementById('modalDays').value = 1;
    updateTotalPrice();
    
    var modal = document.getElementById('vehicleBookingModal');
    modal.classList.add('show');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeVehicleModal() {
    var modal = document.getElementById('vehicleBookingModal');
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function updateTotalPrice() {
    var days = parseInt(document.getElementById('modalDays').value) || 1;
    var total = currentVehiclePrice * days;
    document.getElementById('modalTotalPrice').textContent = 'LKR ' + total.toFixed(2);
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalDays').addEventListener('input', updateTotalPrice);
    document.getElementById('modalDays').addEventListener('change', updateTotalPrice);
    
    document.getElementById('vehicleBookingModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeVehicleModal();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVehicleModal();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>