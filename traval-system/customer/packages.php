<?php
require_once '../config/database.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    redirect('../login.php');
}

$page_title = 'Browse Packages';
$user_id = $_SESSION['user_id'];

// ============================================
// GET ALL ACTIVE PACKAGES
// ============================================
$packages = $pdo->query("SELECT * FROM packages WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();

// ============================================
// GET ACTIVE GUIDES & HOTELS FOR DROPDOWNS
// ============================================
$guides = $pdo->query("SELECT u.id, u.name, gp.experience_years, gp.rating, gp.hourly_rate, gp.availability 
                       FROM users u 
                       JOIN guide_profiles gp ON u.id = gp.user_id 
                       WHERE u.role = 'guide' AND u.status = 'active' AND gp.availability = 'available' 
                       ORDER BY gp.rating DESC")->fetchAll();

$hotels = $pdo->query("SELECT u.id, hp.hotel_name, hp.location, hp.stars, hp.price_per_night 
                       FROM users u 
                       JOIN hotel_profiles hp ON u.id = hp.user_id 
                       WHERE u.role = 'hotel' AND u.status = 'active' 
                       ORDER BY hp.stars DESC")->fetchAll();

// ============================================
// GET AVAILABLE VEHICLES (NEW)
// ============================================
$available_vehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' ORDER BY price_per_day ASC")->fetchAll();

// ============================================
// HANDLE BOOKING
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_package'])) {
    try {
        $package_id = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
        $adults = isset($_POST['adults']) ? (int)$_POST['adults'] : 1;
        $children = isset($_POST['children']) ? (int)$_POST['children'] : 0;
        $special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';
        
        $hotel_id = !empty($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : null;
        $hotel_name = isset($_POST['hotel_name']) ? $_POST['hotel_name'] : '';
        $room_type = isset($_POST['room_type']) ? $_POST['room_type'] : 'Standard';
        $room_count = isset($_POST['room_count']) ? (int)$_POST['room_count'] : 1;
        $guide_id = !empty($_POST['guide_id']) ? (int)$_POST['guide_id'] : null;
        
        // ============================================
        // VEHICLE BOOKING DATA (NEW)
        // ============================================
        $vehicle_id = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
        $pickup_location = isset($_POST['pickup_location']) ? trim($_POST['pickup_location']) : '';
        $dropoff_location = isset($_POST['dropoff_location']) ? trim($_POST['dropoff_location']) : '';
        $pickup_date_input = isset($_POST['pickup_date']) ? $_POST['pickup_date'] : '';
        $pickup_time = isset($_POST['pickup_time']) ? $_POST['pickup_time'] : '09:00';
        $vehicle_days = isset($_POST['vehicle_days']) ? (int)$_POST['vehicle_days'] : 1;
        $estimated_km = isset($_POST['estimated_km']) ? (int)$_POST['estimated_km'] : 0;
        $vehicle_notes = isset($_POST['vehicle_notes']) ? trim($_POST['vehicle_notes']) : '';
        
        $insurance_type = isset($_POST['insurance_type']) ? $_POST['insurance_type'] : 'None';
        $insurance_price = isset($_POST['insurance_price']) ? (float)$_POST['insurance_price'] : 0;
        $promo_code = isset($_POST['promo_code']) ? $_POST['promo_code'] : '';
        
        if ($package_id <= 0 || empty($start_date) || $adults < 1) {
            toast("Invalid booking data.", 'error');
            redirect('packages.php');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND status = 'active'");
        $stmt->execute([$package_id]);
        $package = $stmt->fetch();
        
        if (!$package) {
            toast("Package not available.", 'error');
            redirect('packages.php');
        }
        
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $package['duration_days'] . ' days'));
        $total_amount = ($package['price'] * $adults) + (($package['price'] / 2) * $children);
        
        $discount_amount = 0;
        if (!empty($promo_code)) {
            $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND status = 'active' AND valid_from <= CURDATE() AND valid_to >= CURDATE() AND used_count < usage_limit");
            $stmt->execute([$promo_code]);
            $promo = $stmt->fetch();
            if ($promo && $total_amount >= $promo['min_order_amount']) {
                if ($promo['discount_type'] == 'percentage') {
                    $discount_amount = ($total_amount * $promo['discount_value']) / 100;
                    if ($promo['max_discount'] && $discount_amount > $promo['max_discount']) {
                        $discount_amount = $promo['max_discount'];
                    }
                } else {
                    $discount_amount = $promo['discount_value'];
                }
                $stmt = $pdo->prepare("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?");
                $stmt->execute([$promo['id']]);
            }
        }
        
        $total_amount += $insurance_price;
        $total_amount -= $discount_amount;
        if ($total_amount < 0) $total_amount = 0;
        
        if (!function_exists('generateBookingNo')) {
            function generateBookingNo() {
                return 'BK' . date('Ymd') . rand(1000, 9999);
            }
        }
        $booking_no = generateBookingNo();
        
        $stmt = $pdo->prepare("INSERT INTO bookings (
            booking_no, customer_id, package_id, guide_id, hotel_id, 
            start_date, end_date, adults, children, total_amount, 
            paid_amount, special_requests, status, payment_status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $result = $stmt->execute([
            $booking_no, $user_id, $package_id, $guide_id, $hotel_id, 
            $start_date, $end_date, $adults, $children, $total_amount, 
            0, $special_requests, 'pending', 'unpaid'
        ]);
        
        if ($result) {
            $booking_id = $pdo->lastInsertId();
            
            // Hotel Booking
            if (!empty($hotel_id) || !empty($hotel_name)) {
                if (empty($hotel_name) && !empty($hotel_id)) {
                    $stmt = $pdo->prepare("SELECT hotel_name FROM hotel_profiles WHERE id = ?");
                    $stmt->execute([$hotel_id]);
                    $hotel_data = $stmt->fetch();
                    $hotel_name = $hotel_data['hotel_name'] ?? '';
                }
                try {
                    $stmt = $pdo->prepare("INSERT INTO hotel_bookings (booking_id, hotel_id, hotel_name, room_type, check_in_date, check_out_date, room_count, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$booking_id, $hotel_id, $hotel_name, $room_type, $start_date, $end_date, $room_count, $total_amount * 0.3]);
                } catch (Exception $e) {}
            }
            
            // ============================================
            // VEHICLE BOOKING - FULL DETAILS (NEW)
            // ============================================
            if (!empty($vehicle_id)) {
                $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND status = 'available'");
                $stmt->execute([$vehicle_id]);
                $vehicle = $stmt->fetch();
                
                if ($vehicle) {
                    $vehicle_total = ($vehicle['price_per_day'] * $vehicle_days) + ($vehicle['price_per_km'] * $estimated_km);
                    $pickup_datetime = $pickup_date_input . ' ' . $pickup_time;
                    $dropoff_date = date('Y-m-d H:i:s', strtotime($pickup_date_input . ' + ' . $vehicle_days . ' days'));
                    
                    try {
                        $stmt = $pdo->prepare("INSERT INTO vehicle_bookings 
                            (booking_id, vehicle_id, vehicle_type, pickup_location, dropoff_location, 
                             pickup_date, dropoff_date, days, pickup_time, total_price, status, 
                             driver_name, driver_phone, notes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
                        
                        $stmt->execute([
                            $booking_id,
                            $vehicle_id,
                            $vehicle['vehicle_name'] . ' (' . $vehicle['vehicle_type'] . ')',
                            $pickup_location,
                            $dropoff_location,
                            $pickup_datetime,
                            $dropoff_date,
                            $vehicle_days,
                            $pickup_time,
                            $vehicle_total,
                            $vehicle['driver_name'],
                            $vehicle['driver_phone'],
                            $vehicle_notes
                        ]);
                        
                        // Update vehicle status to booked
                        $stmt = $pdo->prepare("UPDATE vehicles SET status = 'booked' WHERE id = ?");
                        $stmt->execute([$vehicle_id]);
                        
                    } catch (Exception $e) {
                        error_log("Vehicle booking error: " . $e->getMessage());
                    }
                }
            }
            
            // Insurance
            if ($insurance_type != 'None') {
                try {
                    $stmt = $pdo->prepare("INSERT INTO insurance_requests (booking_id, insurance_type, coverage_amount, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$booking_id, $insurance_type, $total_amount * 2, $insurance_price]);
                } catch (Exception $e) {}
            }
            
            toast("✅ Booking created successfully! Booking ID: " . $booking_no);
            redirect('bookings.php');
        } else {
            toast("Failed to create booking.", 'error');
            redirect('packages.php');
        }
        
    } catch (Exception $e) {
        error_log("Booking error: " . $e->getMessage());
        toast("Error creating booking.", 'error');
        redirect('packages.php');
    }
}

include '../includes/header.php';
?>

<style>
/* ============================================ */
/* MODAL OVERLAY - PERFECT CENTER */
/* ============================================ */
.modal-overlay {
    display: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(5px);
    z-index: 99999 !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
    margin: 0 !important;
}

.modal-overlay.active {
    display: flex !important;
}

.modal-overlay.inactive {
    display: none !important;
}

.modal-box {
    background: #ffffff !important;
    border-radius: 20px !important;
    max-width: 650px !important;
    width: 100% !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
    animation: modalPop 0.3s ease !important;
    position: relative !important;
    margin: auto !important;
}

@keyframes modalPop {
    0% { opacity: 0; transform: scale(0.9) translateY(-20px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-head {
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

.modal-head h3 {
    font-size: 20px;
    font-weight: 700;
    color: #1a5a3a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-head h3 i { color: #27ae60; }

.modal-close {
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

.modal-close:hover {
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

.modal-body .form-group { margin-bottom: 16px; }

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

.modal-body .section-box {
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
}

.modal-body .section-box.primary { background: #f0fff4; border-left: 4px solid #27ae60; }
.modal-body .section-box.secondary { background: #fef9e7; border-left: 4px solid #f39c12; }
.modal-body .section-box.info { background: #ebf5fb; border-left: 4px solid #3498db; }
.modal-body .section-box.warning { background: #fef9e7; border-left: 4px solid #e67e22; }

.modal-body .section-box h4 {
    font-size: 14px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-body .section-box.primary h4 { color: #1a5a3a; }
.modal-body .section-box.secondary h4 { color: #b7950b; }

.modal-body .total-box {
    background: linear-gradient(135deg, #27ae60, #1a7a3a);
    color: white;
    padding: 18px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 16px;
}

.modal-body .total-box .amount {
    font-size: 32px;
    font-weight: 800;
}

.modal-body .total-box .label {
    font-size: 13px;
    opacity: 0.8;
}

.modal-body .promo-box { display: flex; gap: 10px; }
.modal-body .promo-box input { flex: 1; }

.modal-foot {
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

.btn-green {
    background: #27ae60;
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-green:hover {
    background: #1e8449;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
}

.btn-green:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-gray {
    background: #ecf0f1;
    color: #2c3e50;
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-gray:hover { background: #d5dbdb; }

/* Package Cards */
.packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.package-card {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.package-card.highlight {
    border: 2px solid #27ae60;
    box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.2);
}

.package-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    background: #f0f2f5;
}

.package-info { padding: 20px; }

.package-title {
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 8px 0;
}

.package-destination {
    font-size: 14px;
    color: #6c7a8a;
    margin-bottom: 10px;
}

.package-destination i { color: #27ae60; margin-right: 5px; }

.package-details {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #6c7a8a;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.package-details i { color: #27ae60; margin-right: 4px; }

.package-price {
    font-size: 22px;
    font-weight: 700;
    color: #27ae60;
    margin-bottom: 10px;
}

.package-price small {
    font-size: 13px;
    font-weight: 400;
    color: #6c7a8a;
}

.package-description {
    font-size: 14px;
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.package-actions { display: flex; gap: 10px; }

.btn-book-now {
    background: #27ae60;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    justify-content: center;
}

.btn-book-now:hover {
    background: #1e8449;
    transform: translateY(-2px);
}

.btn-view {
    background: #3498db;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-view:hover { background: #2e86c1; transform: translateY(-2px); }

.package-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }

.package-tag {
    padding: 2px 12px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}

.package-tag.code { background: #e2e8f0; color: #5d6d7e; }
.package-tag.easy { background: #d4f5e6; color: #27ae60; }
.package-tag.medium { background: #fff3cd; color: #856404; }
.package-tag.hard { background: #fee2e2; color: #dc2626; }

.package-badge {
    display: inline-block;
    background: #27ae60;
    color: #fff;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

/* Spinner */
.spinner-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.85);
    z-index: 999999;
    align-items: center;
    justify-content: center;
}

.spinner-overlay.show { display: flex; }

.spinner-box {
    background: white;
    padding: 40px 50px;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    text-align: center;
}

.spinner-box .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e0e7e9;
    border-top-color: #27ae60;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin { to { transform: rotate(360deg); } }

.spinner-box p { color: #2c3e50; font-weight: 600; margin: 0; }

/* Toast */
@keyframes slideIn {
    from { opacity: 0; transform: translateX(50px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes slideOut {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(50px); }
}

/* Vehicle Section */
#vehicleDetailsPreview { animation: fadeIn 0.3s ease; }
#vehicleBookingFields { animation: slideDown 0.3s ease; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Mobile */
@media (max-width: 768px) {
    .modal-box {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 16px;
    }
    .modal-body {
        max-height: calc(95vh - 130px);
        padding: 16px;
    }
    .modal-head { padding: 14px 16px; }
    .modal-head h3 { font-size: 17px; }
    .modal-foot { padding: 12px 16px; flex-direction: column; }
    .modal-foot .btn { width: 100%; justify-content: center; }
    .modal-body .form-row { grid-template-columns: 1fr; gap: 0; }
    .packages-grid { grid-template-columns: 1fr; }
    #vehicleDetailsPreview > div:first-child { grid-template-columns: 1fr !important; }
}
</style>

<!-- LOADING SPINNER -->
<div id="globalSpinner" class="spinner-overlay">
    <div class="spinner-box">
        <div class="spinner"></div>
        <p>Loading package details...</p>
    </div>
</div>

<!-- PAGE HEADER -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 style="font-size:24px; font-weight:700; color:#2c3e50;">
            <i class="fas fa-box" style="color:#27ae60;"></i> Browse Packages
        </h2>
        <p style="color:#6c7a8a; font-size:14px; margin-top:4px;">
            <?php echo count($packages); ?> packages available
        </p>
    </div>
</div>

<!-- PACKAGES GRID -->
<div class="packages-grid">
    <?php if (empty($packages)): ?>
        <p style="text-align:center; padding:60px; color:#6c7a8a; grid-column:1/-1;">
            <i class="fas fa-box-open" style="font-size:48px; display:block; margin-bottom:15px;"></i>
            No packages available.
        </p>
    <?php else: ?>
        <?php foreach ($packages as $p): ?>
        <div class="package-card" data-package-id="<?php echo $p['id']; ?>">
            <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=400&h=200&fit=crop&auto=format'); ?>" 
                 class="package-image" alt="<?php echo htmlspecialchars($p['name']); ?>"
                 onerror="this.src='https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=400&h=200&fit=crop&auto=format'">
            <div class="package-info">
                
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                    <h3 class="package-title"><?php echo htmlspecialchars($p['name']); ?></h3>
                    <span class="package-badge"><?php echo htmlspecialchars($p['category'] ?? 'Tour'); ?></span>
                </div>
                
                <div class="package-tags">
                    <?php if ($p['package_code']): ?>
                        <span class="package-tag code"><?php echo htmlspecialchars($p['package_code']); ?></span>
                    <?php endif; ?>
                    <?php if ($p['difficulty_level']): ?>
                        <span class="package-tag <?php echo strtolower($p['difficulty_level']); ?>">
                            <?php echo htmlspecialchars($p['difficulty_level']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="package-destination">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($p['destination']); ?>
                </div>
                
                <div class="package-details">
                    <span><i class="fas fa-calendar"></i> <?php echo $p['duration_days']; ?> Days</span>
                    <span><i class="fas fa-moon"></i> <?php echo $p['duration_nights']; ?> Nights</span>
                    <span><i class="fas fa-users"></i> Max <?php echo $p['max_people']; ?> pax</span>
                </div>
                
                <div class="package-price">
                    <?php echo formatCurrency($p['price']); ?> <small>/person</small>
                </div>
                
                <p class="package-description">
                    <?php echo htmlspecialchars(substr($p['description'] ?? 'No description available.', 0, 100)); ?>...
                </p>
                
                <div class="package-actions">
                    <button class="btn-book-now book-btn" data-id="<?php echo $p['id']; ?>" onclick="openBookingModal(<?php echo $p['id']; ?>, this)">
                        <i class="fas fa-calendar-check"></i> Book Now
                    </button>
                    <button class="btn-view" onclick='openDetailsModal(<?php echo json_encode($p); ?>)'>
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- BOOKING MODAL -->
<!-- ============================================ -->
<div id="bookingModal" class="modal-overlay inactive">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="fas fa-calendar-plus"></i> Book Your Package</h3>
            <button class="modal-close" onclick="closeBookingModal()">&times;</button>
        </div>
        <form method="POST" id="bookingForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" onsubmit="return validateBookingForm()">
            <input type="hidden" name="book_package" value="1">
            <input type="hidden" name="package_id" id="bookingPackageId">
            <div class="modal-body">
                
                <div style="background:#f0fff4; padding:12px 16px; border-radius:10px; margin-bottom:16px; text-align:center; border:2px dashed #27ae60;">
                    <strong id="selectedPackageName" style="color:#1a5a3a; font-size:16px;">Select a package</strong>
                </div>
                
                <div class="form-group">
                    <label>Start Date <span style="color:#e74c3c;">*</span></label>
                    <input type="date" name="start_date" id="bookingStartDate" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Adults <span style="color:#e74c3c;">*</span></label>
                        <input type="number" name="adults" id="bookingAdults" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label>Children (under 12)</label>
                        <input type="number" name="children" id="bookingChildren" class="form-control" min="0" value="0">
                    </div>
                </div>
                
                <!-- ============================================ -->
                <!-- TOUR GUIDE SECTION -->
                <!-- ============================================ -->
                <div class="section-box primary">
                    <h4><i class="fas fa-hiking" style="color:#27ae60;"></i> Tour Guide <span style="font-size:11px; font-weight:400; color:#7f8c8d;">(Optional)</span></h4>
                    <select name="guide_id" id="guideSelect" class="form-control">
                        <option value="">No Guide Needed</option>
                        <?php foreach ($guides as $g): ?>
                            <option value="<?php echo $g['id']; ?>">
                                <?php echo htmlspecialchars($g['name']); ?> 
                                ⭐ <?php echo number_format($g['rating'], 1); ?> 
                                (<?php echo $g['experience_years']; ?> yrs) 
                                - <?php echo formatCurrency($g['hourly_rate']); ?>/hr
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- ============================================ -->
                <!-- HOTEL SECTION -->
                <!-- ============================================ -->
                <div class="section-box secondary">
                    <h4><i class="fas fa-hotel" style="color:#f39c12;"></i> Hotel <span style="font-size:11px; font-weight:400; color:#7f8c8d;">(Optional)</span></h4>
                    <select name="hotel_id" id="hotelSelect" class="form-control">
                        <option value="">No Hotel Needed</option>
                        <?php foreach ($hotels as $h): ?>
                            <option value="<?php echo $h['id']; ?>">
                                <?php echo htmlspecialchars($h['hotel_name']); ?> 
                                <?php echo str_repeat('★', $h['stars']); ?> 
                                - <?php echo formatCurrency($h['price_per_night']); ?>/night
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-row" style="margin-top:10px;">
                        <div class="form-group">
                            <label>Room Type</label>
                            <select name="room_type" class="form-control">
                                <option value="Standard">Standard</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Suite">Suite</option>
                                <option value="Family">Family</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Room Count</label>
                            <input type="number" name="room_count" class="form-control" min="1" value="1">
                        </div>
                    </div>
                </div>
                
                <!-- ============================================ -->
                <!-- VEHICLE BOOKING SECTION - COMPLETE DETAILS -->
                <!-- ============================================ -->
                <div class="section-box info">
                    <h4><i class="fas fa-car" style="color:#3498db;"></i> Vehicle Booking <span style="font-size:11px; font-weight:400; color:#7f8c8d;">(Optional)</span></h4>
                    
                    <!-- Vehicle Selection Dropdown -->
                    <div class="form-group">
                        <label>Select Vehicle</label>
                        <select name="vehicle_id" id="vehicleSelect" class="form-control" onchange="updateVehicleDetails()">
                            <option value="">🚫 No Vehicle Needed</option>
                            <?php foreach ($available_vehicles as $v): ?>
                                <option value="<?php echo $v['id']; ?>" 
                                        data-price="<?php echo $v['price_per_day']; ?>"
                                        data-name="<?php echo htmlspecialchars($v['vehicle_name']); ?>"
                                        data-type="<?php echo htmlspecialchars($v['vehicle_type']); ?>"
                                        data-seats="<?php echo $v['seat_count']; ?>"
                                        data-driver="<?php echo htmlspecialchars($v['driver_name']); ?>"
                                        data-phone="<?php echo htmlspecialchars($v['driver_phone']); ?>"
                                        data-location="<?php echo htmlspecialchars($v['location']); ?>"
                                        data-price-km="<?php echo $v['price_per_km']; ?>">
                                    <?php echo htmlspecialchars($v['vehicle_name']); ?> 
                                    (<?php echo $v['vehicle_type']; ?>) 
                                    - <?php echo $v['seat_count']; ?> Seats 
                                    - LKR <?php echo number_format($v['price_per_day'], 2); ?>/day
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vehicle Details Preview -->
                    <div id="vehicleDetailsPreview" style="display:none; background:#ffffff; border-radius:10px; padding:14px; margin-bottom:14px; border:2px solid #e2e8f0;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px; margin-bottom:10px;">
                            <div style="padding:6px 0;"><strong style="color:#6c7a8a;">Vehicle:</strong> <span id="previewVehicleName" style="color:#2c3e50; font-weight:600;">-</span></div>
                            <div style="padding:6px 0;"><strong style="color:#6c7a8a;">Type:</strong> <span id="previewVehicleType" style="color:#2c3e50; font-weight:600;">-</span></div>
                            <div style="padding:6px 0;"><strong style="color:#6c7a8a;">Seats:</strong> <span id="previewVehicleSeats" style="color:#2c3e50; font-weight:600;">-</span></div>
                            <div style="padding:6px 0;"><strong style="color:#6c7a8a;">Driver:</strong> <span id="previewDriverName" style="color:#2c3e50; font-weight:600;">-</span></div>
                            <div style="padding:6px 0;"><strong style="color:#6c7a8a;">Contact:</strong> <span id="previewDriverPhone" style="color:#2c3e50; font-weight:600;">-</span></div>
                            <div style="padding:6px 0;"><strong style="color:#6c7a8a;">Location:</strong> <span id="previewVehicleLocation" style="color:#2c3e50; font-weight:600;">-</span></div>
                        </div>
                        <div style="padding-top:10px; border-top:2px dashed #e2e8f0;">
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:4px 0;">
                                <span style="font-size:12px; color:#6c7a8a;">Price Per Day:</span>
                                <span style="font-weight:700; color:#27ae60; font-size:15px;" id="previewVehiclePrice">LKR 0.00</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:4px 0;">
                                <span style="font-size:12px; color:#6c7a8a;">Price Per KM:</span>
                                <span style="font-weight:600; color:#3498db; font-size:13px;" id="previewVehiclePriceKm">LKR 0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pickup & Dropoff Details -->
                    <div id="vehicleBookingFields" style="display:none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Pickup Location <span style="color:#e74c3c;">*</span></label>
                                <input type="text" name="pickup_location" id="pickupLocation" class="form-control" placeholder="Enter pickup location">
                            </div>
                            <div class="form-group">
                                <label>Dropoff Location</label>
                                <input type="text" name="dropoff_location" id="dropoffLocation" class="form-control" placeholder="Enter dropoff location">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Pickup Date <span style="color:#e74c3c;">*</span></label>
                                <input type="date" name="pickup_date" id="pickupDate" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Pickup Time <span style="color:#e74c3c;">*</span></label>
                                <input type="time" name="pickup_time" id="pickupTime" class="form-control" value="09:00">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Number of Days <span style="color:#e74c3c;">*</span></label>
                                <input type="number" name="vehicle_days" id="vehicleDays" class="form-control" min="1" value="1" onchange="calculateVehicleTotal()" oninput="calculateVehicleTotal()">
                            </div>
                            <div class="form-group">
                                <label>Estimated KM</label>
                                <input type="number" name="estimated_km" id="estimatedKm" class="form-control" min="0" value="0" onchange="calculateVehicleTotal()" oninput="calculateVehicleTotal()">
                            </div>
                        </div>
                        
                        <!-- Vehicle Total Price -->
                        <div style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 14px; border-radius: 12px; text-align: center; margin-bottom: 14px;">
                            <div style="font-size: 12px; opacity: 0.9; margin-bottom:4px;">Vehicle Total</div>
                            <div style="font-size: 24px; font-weight: 800;" id="vehicleTotalDisplay">LKR 0.00</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Special Instructions</label>
                            <textarea name="vehicle_notes" class="form-control" rows="2" placeholder="Any special instructions for the driver..."></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- ============================================ -->
                <!-- TRAVEL INSURANCE SECTION -->
                <!-- ============================================ -->
                <div class="section-box warning">
                    <h4><i class="fas fa-shield-alt" style="color:#e67e22;"></i> Travel Insurance</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <select name="insurance_type" id="insuranceType" class="form-control">
                                <option value="None">No Insurance</option>
                                <option value="Standard">Standard (LKR 1,500)</option>
                                <option value="Premium">Premium (LKR 3,500)</option>
                                <option value="Comprehensive">Comprehensive (LKR 5,500)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="number" name="insurance_price" id="insurancePrice" class="form-control" value="0" readonly>
                        </div>
                    </div>
                </div>
                
                <!-- ============================================ -->
                <!-- PROMO CODE SECTION -->
                <!-- ============================================ -->
                <div class="section-box secondary">
                    <h4><i class="fas fa-ticket-alt" style="color:#f39c12;"></i> Promo Code</h4>
                    <div class="promo-box">
                        <input type="text" name="promo_code" id="promoCodeInput" class="form-control" placeholder="Enter promo code">
                        <button type="button" class="btn-green" onclick="applyPromoCode()" style="padding:10px 20px; white-space:nowrap;">
                            <i class="fas fa-check"></i> Apply
                        </button>
                    </div>
                    <div id="promoMessage" style="margin-top:8px; font-size:13px;"></div>
                </div>
                
                <!-- TOTAL AMOUNT -->
                <div class="total-box">
                    <div class="label">Total Amount</div>
                    <div class="amount" id="totalAmountDisplay">LKR 0.00</div>
                    <div style="font-size:12px; opacity:0.7;" id="discountDisplay"></div>
                </div>
                
                <div class="form-group">
                    <label>Special Requests</label>
                    <textarea name="special_requests" class="form-control" rows="2" placeholder="Any special requirements..."></textarea>
                </div>
                
            </div>
            <div class="modal-foot">
                <button type="button" class="btn-gray" onclick="closeBookingModal()">Cancel</button>
                <button type="submit" name="book_package" class="btn-green" id="confirmBookingBtn">
                    <i class="fas fa-check"></i> Confirm Booking
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- DETAILS MODAL -->
<!-- ============================================ -->
<div id="detailsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(6px); z-index:99998; align-items:center; justify-content:center; padding:20px;">
    <div style="max-width:600px; width:100%; background:#ffffff; border-radius:20px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="padding:18px 24px; border-bottom:3px solid #27ae60; display:flex; justify-content:space-between; align-items:center; background:#f0fff4; border-radius:20px 20px 0 0;">
            <h3 style="font-size:20px; font-weight:700; color:#1a5a3a; margin:0;">
                <i class="fas fa-info-circle" style="color:#27ae60; margin-right:10px;"></i> Package Details
            </h3>
            <button onclick="document.getElementById('detailsModal').style.display='none'; document.body.style.overflow='';" style="background:none; border:none; font-size:28px; cursor:pointer; color:#95a5a6; width:40px; height:40px; border-radius:50%;">&times;</button>
        </div>
        <div class="modal-body" id="detailsModalBody" style="background:#ffffff; padding:24px;"></div>
        <div style="padding:16px 24px; border-top:2px solid #f0fff4; display:flex; justify-content:flex-end; background:#fafcfc; border-radius:0 0 20px 20px;">
            <button onclick="document.getElementById('detailsModal').style.display='none'; document.body.style.overflow='';" class="btn-gray">Close</button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
var bookingState = {
    packageId: 0,
    packagePrice: 0,
    discountAmount: 0,
    isProcessing: false,
    isModalOpen: false
};

var selectedVehicle = {
    id: 0,
    name: '',
    type: '',
    pricePerDay: 0,
    pricePerKm: 0,
    seats: 0,
    driver: '',
    phone: '',
    location: ''
};

function showToast(message, type) {
    type = type || 'success';
    var existing = document.querySelector('.custom-toast');
    if (existing) existing.remove();
    
    var toast = document.createElement('div');
    toast.className = 'custom-toast';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 10px;
        z-index: 9999999;
        font-weight: 600;
        font-size: 14px;
        animation: slideIn 0.4s ease;
        max-width: 400px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        background: ${type === 'error' ? '#e74c3c' : '#27ae60'};
        color: white;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(function() {
        toast.style.animation = 'slideOut 0.4s ease forwards';
        setTimeout(function() { toast.remove(); }, 500);
    }, 4000);
}

// ============================================
// OPEN BOOKING MODAL
// ============================================
function openBookingModal(packageId, buttonElement) {
    if (bookingState.isProcessing) return;
    
    if (bookingState.isModalOpen) {
        closeBookingModal();
        setTimeout(function() { openBookingModal(packageId, buttonElement); }, 400);
        return;
    }
    
    bookingState.isProcessing = true;
    bookingState.packageId = packageId;
    
    var spinner = document.getElementById('globalSpinner');
    if (spinner) spinner.classList.add('show');
    
    var btn = buttonElement || document.querySelector('.book-btn[data-id="' + packageId + '"]');
    if (btn) {
        var originalHtml = btn.innerHTML;
        btn.setAttribute('data-original-html', originalHtml);
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        btn.disabled = true;
    }
    
    document.getElementById('bookingPackageId').value = packageId;
    document.getElementById('selectedPackageName').textContent = 'Loading package details...';
    
    fetch('get_package_details.php?id=' + packageId + '&t=' + Date.now())
        .then(function(response) { return response.json(); })
        .then(function(data) {
            bookingState.isProcessing = false;
            
            if (btn) {
                btn.innerHTML = btn.getAttribute('data-original-html') || '<i class="fas fa-calendar-check"></i> Book Now';
                btn.disabled = false;
            }
            
            if (spinner) spinner.classList.remove('show');
            
            if (data.success) {
                bookingState.packagePrice = parseFloat(data.price) || 0;
                
                document.getElementById('selectedPackageName').textContent = data.name + (data.destination ? ' - ' + data.destination : '');
                
                var today = new Date().toISOString().split('T')[0];
                document.getElementById('bookingStartDate').value = today;
                document.getElementById('bookingStartDate').min = today;
                
                document.getElementById('bookingAdults').value = 1;
                document.getElementById('bookingChildren').value = 0;
                document.getElementById('promoCodeInput').value = '';
                document.getElementById('promoMessage').innerHTML = '';
                document.getElementById('discountDisplay').textContent = '';
                bookingState.discountAmount = 0;
                
                // Reset vehicle selection
                document.getElementById('vehicleSelect').value = '';
                document.getElementById('vehicleDetailsPreview').style.display = 'none';
                document.getElementById('vehicleBookingFields').style.display = 'none';
                selectedVehicle = {
                    id: 0, name: '', type: '', pricePerDay: 0, pricePerKm: 0,
                    seats: 0, driver: '', phone: '', location: ''
                };
                document.getElementById('vehicleTotalDisplay').textContent = 'LKR 0.00';
                
                calculateTotal();
                
                var modal = document.getElementById('bookingModal');
                modal.classList.remove('inactive');
                modal.classList.add('active');
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                
                document.body.style.overflow = 'hidden';
                bookingState.isModalOpen = true;
                
                setTimeout(function() { 
                    document.getElementById('bookingStartDate').focus(); 
                }, 300);
                
            } else {
                showToast(data.message || 'Error loading package details.', 'error');
            }
        })
        .catch(function(error) {
            bookingState.isProcessing = false;
            if (btn) {
                btn.innerHTML = btn.getAttribute('data-original-html') || '<i class="fas fa-calendar-check"></i> Book Now';
                btn.disabled = false;
            }
            if (spinner) spinner.classList.remove('show');
            showToast('Error loading package. Please try again.', 'error');
        });
}

// ============================================
// CLOSE BOOKING MODAL
// ============================================
function closeBookingModal() {
    var modal = document.getElementById('bookingModal');
    modal.classList.remove('active');
    modal.classList.add('inactive');
    
    setTimeout(function() {
        modal.style.display = 'none';
    }, 300);
    
    document.body.style.overflow = '';
    bookingState.isModalOpen = false;
    bookingState.isProcessing = false;
}

// ============================================
// CALCULATE TOTAL
// ============================================
function calculateTotal() {
    var adults = parseInt(document.getElementById('bookingAdults').value) || 0;
    var children = parseInt(document.getElementById('bookingChildren').value) || 0;
    var insurance = parseFloat(document.getElementById('insurancePrice').value) || 0;
    var packagePrice = bookingState.packagePrice || 0;
    
    if (adults < 1) {
        document.getElementById('bookingAdults').value = 1;
        adults = 1;
    }
    
    var total = (packagePrice * adults) + ((packagePrice / 2) * children);
    total = total + insurance - bookingState.discountAmount;
    if (total < 0) total = 0;
    
    document.getElementById('totalAmountDisplay').textContent = 'LKR ' + total.toFixed(2);
    
    if (bookingState.discountAmount > 0) {
        document.getElementById('discountDisplay').textContent = 'Discount: LKR ' + bookingState.discountAmount.toFixed(2);
    } else {
        document.getElementById('discountDisplay').textContent = '';
    }
    
    return total;
}

// ============================================
// APPLY PROMO CODE
// ============================================
function applyPromoCode() {
    var code = document.getElementById('promoCodeInput').value.trim();
    var msg = document.getElementById('promoMessage');
    
    if (!code) {
        msg.innerHTML = '<span style="color:#e74c3c;">Please enter a promo code.</span>';
        return;
    }
    
    msg.innerHTML = '<span style="color:#f39c12;"><i class="fas fa-spinner fa-spin"></i> Validating...</span>';
    
    var adults = parseInt(document.getElementById('bookingAdults').value) || 0;
    var children = parseInt(document.getElementById('bookingChildren').value) || 0;
    var subtotal = (bookingState.packagePrice * adults) + ((bookingState.packagePrice / 2) * children);
    var insurance = parseFloat(document.getElementById('insurancePrice').value) || 0;
    subtotal += insurance;
    
    fetch('validate_promo.php?code=' + encodeURIComponent(code) + '&subtotal=' + subtotal)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                bookingState.discountAmount = parseFloat(data.discount) || 0;
                msg.innerHTML = '<span style="color:#27ae60;">✅ Promo applied! Discount: LKR ' + data.discount.toFixed(2) + '</span>';
                calculateTotal();
            } else {
                msg.innerHTML = '<span style="color:#e74c3c;">❌ ' + data.message + '</span>';
            }
        })
        .catch(function(error) {
            msg.innerHTML = '<span style="color:#e74c3c;">❌ Error applying promo code.</span>';
        });
}

// ============================================
// UPDATE INSURANCE PRICE
// ============================================
function updateInsurancePrice() {
    var type = document.getElementById('insuranceType').value;
    var prices = { 'None': 0, 'Standard': 1500, 'Premium': 3500, 'Comprehensive': 5500 };
    document.getElementById('insurancePrice').value = prices[type] || 0;
    calculateTotal();
}

// ============================================
// VEHICLE BOOKING FUNCTIONS
// ============================================
function updateVehicleDetails() {
    const select = document.getElementById('vehicleSelect');
    const selectedOption = select.options[select.selectedIndex];
    const vehicleId = select.value;
    
    const previewDiv = document.getElementById('vehicleDetailsPreview');
    const bookingFields = document.getElementById('vehicleBookingFields');
    
    if (!vehicleId) {
        previewDiv.style.display = 'none';
        bookingFields.style.display = 'none';
        
        selectedVehicle = {
            id: 0, name: '', type: '', pricePerDay: 0, pricePerKm: 0,
            seats: 0, driver: '', phone: '', location: ''
        };
        
        calculateVehicleTotal();
        return;
    }
    
    selectedVehicle = {
        id: parseInt(vehicleId),
        name: selectedOption.dataset.name || '',
        type: selectedOption.dataset.type || '',
        pricePerDay: parseFloat(selectedOption.dataset.price) || 0,
        pricePerKm: parseFloat(selectedOption.dataset.priceKm) || 0,
        seats: parseInt(selectedOption.dataset.seats) || 0,
        driver: selectedOption.dataset.driver || '',
        phone: selectedOption.dataset.phone || '',
        location: selectedOption.dataset.location || ''
    };
    
    document.getElementById('previewVehicleName').textContent = selectedVehicle.name;
    document.getElementById('previewVehicleType').textContent = selectedVehicle.type;
    document.getElementById('previewVehicleSeats').textContent = selectedVehicle.seats + ' Seats';
    document.getElementById('previewDriverName').textContent = selectedVehicle.driver || 'Not assigned';
    document.getElementById('previewDriverPhone').textContent = selectedVehicle.phone || 'Not available';
    document.getElementById('previewVehicleLocation').textContent = selectedVehicle.location || 'Not specified';
    document.getElementById('previewVehiclePrice').textContent = 'LKR ' + selectedVehicle.pricePerDay.toFixed(2);
    document.getElementById('previewVehiclePriceKm').textContent = 'LKR ' + selectedVehicle.pricePerKm.toFixed(2);
    
    previewDiv.style.display = 'block';
    bookingFields.style.display = 'block';
    
    if (selectedVehicle.location && !document.getElementById('pickupLocation').value) {
        document.getElementById('pickupLocation').value = selectedVehicle.location;
    }
    
    var bookingStartDate = document.getElementById('bookingStartDate');
    var pickupDate = document.getElementById('pickupDate');
    if (bookingStartDate && bookingStartDate.value && pickupDate) {
        pickupDate.value = bookingStartDate.value;
    } else if (pickupDate) {
        pickupDate.value = new Date().toISOString().split('T')[0];
    }
    
    calculateVehicleTotal();
}

function calculateVehicleTotal() {
    const daysInput = document.getElementById('vehicleDays');
    const kmInput = document.getElementById('estimatedKm');
    const display = document.getElementById('vehicleTotalDisplay');
    
    if (!daysInput || !kmInput || !display) return 0;
    
    const days = parseInt(daysInput.value) || 1;
    const km = parseInt(kmInput.value) || 0;
    
    const dayTotal = selectedVehicle.pricePerDay * days;
    const kmTotal = selectedVehicle.pricePerKm * km;
    const total = dayTotal + kmTotal;
    
    display.textContent = 'LKR ' + total.toFixed(2);
    
    return total;
}

// ============================================
// VALIDATE BOOKING FORM
// ============================================
function validateBookingForm() {
    var startDate = document.getElementById('bookingStartDate');
    var adults = document.getElementById('bookingAdults');
    var packageId = document.getElementById('bookingPackageId');
    var btn = document.getElementById('confirmBookingBtn');
    
    if (!startDate.value) {
        showToast('Please select a start date.', 'error');
        startDate.focus();
        return false;
    }
    
    if (parseInt(adults.value) < 1) {
        showToast('Please select at least 1 adult.', 'error');
        adults.focus();
        return false;
    }
    
    if (!packageId.value || parseInt(packageId.value) <= 0) {
        showToast('Invalid package selected.', 'error');
        return false;
    }
    
    // Validate vehicle details if vehicle selected
    if (selectedVehicle.id > 0) {
        var pickupLocation = document.getElementById('pickupLocation');
        var pickupDate = document.getElementById('pickupDate');
        
        if (!pickupLocation.value.trim()) {
            showToast('Please enter pickup location.', 'error');
            pickupLocation.focus();
            return false;
        }
        
        if (!pickupDate.value) {
            showToast('Please select pickup date.', 'error');
            pickupDate.focus();
            return false;
        }
    }
    
    if (bookingState.isProcessing) {
        return false;
    }
    
    bookingState.isProcessing = true;
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;
    }
    
    return true;
}

// ============================================
// OPEN DETAILS MODAL
// ============================================
function openDetailsModal(packageData) {
    var body = document.getElementById('detailsModalBody');
    var price = packageData.price || 0;
    
    body.innerHTML = `
        <div style="margin-bottom:20px;">
            <img src="${packageData.image_url || 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=600&h=200&fit=crop&auto=format'}" 
                 style="width:100%; height:180px; object-fit:cover; border-radius:12px; margin-bottom:15px;"
                 onerror="this.src='https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=600&h=200&fit=crop&auto=format'">
            <h3 style="font-size:20px; font-weight:700; color:#2c3e50;">${packageData.name || 'Package'}</h3>
            <p style="color:#6c7a8a; font-size:14px;">
                <i class="fas fa-map-marker-alt" style="color:#27ae60;"></i> ${packageData.destination || 'N/A'} 
                ${packageData.package_code ? '| <i class="fas fa-tag"></i> '+packageData.package_code : ''}
            </p>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px;">
            <div style="background:#f0fff4; padding:12px; border-radius:8px; text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#27ae60;">${packageData.duration_days || 0}</div>
                <div style="font-size:12px; color:#6c7a8a;">Days</div>
            </div>
            <div style="background:#f0fff4; padding:12px; border-radius:8px; text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#27ae60;">${packageData.duration_nights || 0}</div>
                <div style="font-size:12px; color:#6c7a8a;">Nights</div>
            </div>
            <div style="background:#f0fff4; padding:12px; border-radius:8px; text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#27ae60;">${packageData.max_people || 0}</div>
                <div style="font-size:12px; color:#6c7a8a;">Max People</div>
            </div>
            <div style="background:#f0fff4; padding:12px; border-radius:8px; text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#27ae60;">LKR ${price}</div>
                <div style="font-size:12px; color:#6c7a8a;">Per Person</div>
            </div>
        </div>
        ${packageData.description ? `
            <div style="margin-bottom:15px;">
                <h4 style="font-weight:600; margin-bottom:6px; color:#2c3e50;">
                    <i class="fas fa-align-left" style="color:#27ae60;"></i> Description
                </h4>
                <p style="font-size:14px; color:#4a5568; line-height:1.6;">${packageData.description}</p>
            </div>` : ''}
        ${packageData.included_services ? `
            <div style="margin-bottom:15px;">
                <h4 style="font-weight:600; margin-bottom:6px; color:#2c3e50;">
                    <i class="fas fa-check-circle" style="color:#27ae60;"></i> Included Services
                </h4>
                <p style="font-size:14px; color:#4a5568; line-height:1.6;">${packageData.included_services}</p>
            </div>` : ''}
        ${packageData.highlights ? `
            <div style="margin-bottom:15px;">
                <h4 style="font-weight:600; margin-bottom:6px; color:#2c3e50;">
                    <i class="fas fa-star" style="color:#f39c12;"></i> Highlights
                </h4>
                <p style="font-size:14px; color:#4a5568; line-height:1.6;">${packageData.highlights}</p>
            </div>` : ''}
        <div style="margin-top:15px; padding-top:15px; border-top:2px solid #f0fff4; text-align:center;">
            <button onclick="document.getElementById('detailsModal').style.display='none'; document.body.style.overflow=''; openBookingModal(${packageData.id})" 
                    class="btn-green" style="width:100%; justify-content:center;">
                <i class="fas fa-calendar-check"></i> Book This Package
            </button>
        </div>
    `;
    document.getElementById('detailsModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// ============================================
// EVENT LISTENERS
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('bookingAdults').addEventListener('change', function() {
        if (parseInt(this.value) < 1) this.value = 1;
        calculateTotal();
    });
    document.getElementById('bookingAdults').addEventListener('input', function() {
        if (parseInt(this.value) < 1) this.value = 1;
        calculateTotal();
    });
    document.getElementById('bookingChildren').addEventListener('change', function() {
        if (parseInt(this.value) < 0) this.value = 0;
        calculateTotal();
    });
    document.getElementById('bookingChildren').addEventListener('input', function() {
        if (parseInt(this.value) < 0) this.value = 0;
        calculateTotal();
    });
    document.getElementById('insuranceType').addEventListener('change', updateInsurancePrice);
    
    var vehicleSelect = document.getElementById('vehicleSelect');
    if (vehicleSelect) {
        vehicleSelect.addEventListener('change', updateVehicleDetails);
    }
    
    document.getElementById('bookingModal').addEventListener('click', function(e) {
        if (e.target === this) closeBookingModal();
    });
    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
    
    document.getElementById('promoCodeInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); applyPromoCode(); }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (bookingState.isModalOpen) closeBookingModal();
            var details = document.getElementById('detailsModal');
            if (details && details.style.display === 'flex') {
                details.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
    });
    
    document.querySelectorAll('.package-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.btn-book-now') || e.target.closest('.btn-view')) return;
            
            var packageId = this.getAttribute('data-package-id');
            if (packageId) {
                this.classList.add('highlight');
                setTimeout(function() { card.classList.remove('highlight'); }, 800);
                openBookingModal(parseInt(packageId));
            }
        });
    });
    
    bookingState.isProcessing = false;
    var confirmBtn = document.getElementById('confirmBookingBtn');
    if (confirmBtn) {
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm Booking';
        confirmBtn.disabled = false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>