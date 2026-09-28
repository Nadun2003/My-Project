<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$page_title = '';

// ==================== ROLE BASED PAGE TITLE ====================
if ($role === 'customer') {
    $page_title = 'My Bookings';
} elseif ($role === 'guide') {
    $page_title = 'My Assignments';
} elseif ($role === 'hotel') {
    $page_title = 'Hotel Bookings';
} else {
    redirect('../login.php');
}

// ==================== DELETE ====================
if (isset($_GET['delete'])) {
    $booking_id = $_GET['delete'];
    
    // Check if user is customer (only customer can delete their own bookings)
    if ($role === 'customer') {
        $stmt = $pdo->prepare("DELETE FROM payments WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND customer_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            toast("Booking deleted successfully!", 'warning');
        } else {
            toast("Cannot delete this booking.", 'error');
        }
    } else {
        toast("You don't have permission to delete bookings.", 'error');
    }
    redirect('bookings.php');
}

// ==================== CANCEL (Customer Only) ====================
if (isset($_GET['cancel']) && $role === 'customer') {
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status = 'pending'");
    $stmt->execute([$_GET['cancel'], $user_id]);
    toast("Booking cancelled!", 'warning');
    redirect('bookings.php');
}

// ==================== EDIT (Customer Only) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking']) && $role === 'customer') {
    $booking_id = $_POST['booking_id'];
    $start_date = $_POST['start_date'];
    $adults = $_POST['adults'];
    $children = $_POST['children'];
    $special_requests = $_POST['special_requests'];
    
    if (empty($start_date) || $start_date < date('Y-m-d')) {
        toast("Select a valid future date.", 'error');
        redirect('bookings.php');
    }
    if ($adults < 1) {
        toast("At least 1 adult required.", 'error');
        redirect('bookings.php');
    }
    
    $stmt = $pdo->prepare("SELECT b.*, p.duration_days, p.price 
                           FROM bookings b 
                           JOIN packages p ON b.package_id = p.id 
                           WHERE b.id = ? AND b.customer_id = ? AND b.status = 'pending'");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $booking['duration_days'] . ' days'));
        $total_amount = ($booking['price'] * $adults) + (($booking['price'] / 2) * $children);
        
        $stmt = $pdo->prepare("UPDATE bookings SET 
                               start_date = ?, end_date = ?, adults = ?, children = ?, 
                               total_amount = ?, special_requests = ? WHERE id = ?");
        $stmt->execute([$start_date, $end_date, $adults, $children, $total_amount, $special_requests, $booking_id]);
        toast("Booking updated!");
    } else {
        toast("Cannot edit this booking.", 'error');
    }
    redirect('bookings.php');
}

// ============================================
// GET BOOKINGS BASED ON ROLE
// ============================================
$sql = "";
$params = [];

if ($role === 'customer') {
    $sql = "SELECT b.*, p.name as package_name, p.image_url, p.destination,
                   g.name as guide_name, hp.hotel_name,
                   (SELECT COUNT(*) FROM feedback f WHERE f.booking_id = b.id AND f.customer_id = b.customer_id) as feedback_exists
            FROM bookings b 
            JOIN packages p ON b.package_id = p.id 
            LEFT JOIN users g ON b.guide_id = g.id
            LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id
            WHERE b.customer_id = ? 
            ORDER BY b.created_at DESC";
    $params = [$user_id];
    
} elseif ($role === 'guide') {
    $sql = "SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                   p.name as package_name, p.destination, p.duration_days, p.duration_nights,
                   hp.hotel_name,
                   (SELECT COUNT(*) FROM feedback f WHERE f.booking_id = b.id) as feedback_exists
            FROM bookings b 
            JOIN users u ON b.customer_id = u.id 
            JOIN packages p ON b.package_id = p.id 
            LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id
            WHERE b.guide_id = ? 
            ORDER BY 
                CASE 
                    WHEN b.status = 'confirmed' AND b.start_date >= CURDATE() THEN 1
                    WHEN b.status = 'confirmed' AND b.start_date < CURDATE() THEN 2
                    WHEN b.status = 'pending' THEN 3
                    WHEN b.status = 'completed' THEN 4
                    WHEN b.status = 'cancelled' THEN 5
                END,
                b.start_date ASC";
    $params = [$user_id];
    
} elseif ($role === 'hotel') {
    $sql = "SELECT b.*, u.name as customer_name, u.email, u.phone,
                   p.name as package_name, p.destination,
                   g.name as guide_name,
                   (SELECT COUNT(*) FROM feedback f WHERE f.booking_id = b.id) as feedback_exists
            FROM bookings b 
            JOIN users u ON b.customer_id = u.id 
            JOIN packages p ON b.package_id = p.id 
            LEFT JOIN users g ON b.guide_id = g.id
            WHERE b.hotel_id = ? 
            ORDER BY 
                CASE 
                    WHEN b.status = 'confirmed' AND b.start_date >= CURDATE() THEN 1
                    WHEN b.status = 'confirmed' AND b.start_date < CURDATE() THEN 2
                    WHEN b.status = 'pending' THEN 3
                    WHEN b.status = 'completed' THEN 4
                    WHEN b.status = 'cancelled' THEN 5
                END,
                b.start_date ASC";
    $params = [$user_id];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- ============================================
     STYLES FOR DELETE BUTTON
     ============================================ -->
<style>
/* Delete Button Styles */
.btn-delete {
    background: #dc3545 !important;
    color: #fff !important;
    border: none !important;
    padding: 6px 14px !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    text-decoration: none !important;
}

.btn-delete:hover {
    background: #c82333 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3) !important;
}

.btn-delete i {
    font-size: 12px !important;
}

/* Delete Modal */
.delete-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    z-index: 999999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.delete-modal-overlay.show {
    display: flex;
}

.delete-modal-box {
    background: #ffffff;
    border-radius: 20px;
    max-width: 450px;
    width: 100%;
    padding: 30px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalPop 0.3s ease;
}

@keyframes modalPop {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.delete-modal-box .icon {
    font-size: 60px;
    color: #dc3545;
    margin-bottom: 15px;
}

.delete-modal-box h3 {
    font-size: 22px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 10px;
}

.delete-modal-box p {
    font-size: 14px;
    color: #6c7a8a;
    line-height: 1.6;
    margin-bottom: 25px;
}

.delete-modal-box .warning-text {
    background: #fff3cd;
    color: #856404;
    padding: 12px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 20px;
    text-align: left;
}

.delete-modal-box .btn-group {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.delete-modal-box .btn-group .btn-cancel {
    background: #e2e8f0;
    color: #2c3e50;
    border: none;
    padding: 10px 28px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.delete-modal-box .btn-group .btn-cancel:hover {
    background: #cbd5e0;
}

.delete-modal-box .btn-group .btn-confirm-delete {
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 28px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.delete-modal-box .btn-group .btn-confirm-delete:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

/* Booking Card Actions - Add spacing */
.booking-actions {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    margin-top: 12px !important;
    padding-top: 12px !important;
    border-top: 1px solid #e8edf0 !important;
}

.booking-actions .btn {
    font-size: 12px !important;
    padding: 6px 14px !important;
    border-radius: 8px !important;
}
</style>

<!-- ===== PAGE HEADER ===== -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
        <h2 style="font-size:24px; font-weight:700; color:var(--dark);">
            <?php if ($role === 'customer'): ?>
                <i class="fas fa-suitcase" style="color:var(--primary);"></i> My Bookings
            <?php elseif ($role === 'guide'): ?>
                <i class="fas fa-route" style="color:var(--primary);"></i> My Assignments
            <?php elseif ($role === 'hotel'): ?>
                <i class="fas fa-bed" style="color:var(--primary);"></i> Hotel Bookings
            <?php endif; ?>
        </h2>
        <p style="color:var(--gray); font-size:14px; margin-top:4px;">
            <?php echo count($bookings); ?> bookings found
        </p>
    </div>
    <?php if ($role === 'customer'): ?>
        <a href="packages.php" class="btn btn-primary" style="padding:10px 24px;">
            <i class="fas fa-plus"></i> New Booking
        </a>
    <?php endif; ?>
</div>

<!-- ============================================
     BOOKINGS LIST
     ============================================ -->
<?php if (empty($bookings)): ?>
<div class="card" style="padding:60px 20px; text-align:center;">
    <div style="max-width:400px; margin:0 auto;">
        <div style="font-size:80px; color:var(--primary-light); margin-bottom:20px;">
            <?php if ($role === 'customer'): ?>
                <i class="fas fa-suitcase"></i>
            <?php elseif ($role === 'guide'): ?>
                <i class="fas fa-route"></i>
            <?php elseif ($role === 'hotel'): ?>
                <i class="fas fa-bed"></i>
            <?php endif; ?>
        </div>
        <h3 style="font-size:22px; font-weight:700; color:var(--dark); margin-bottom:10px;">
            <?php if ($role === 'customer'): ?>
                No bookings yet.
            <?php elseif ($role === 'guide'): ?>
                No assignments yet.
            <?php elseif ($role === 'hotel'): ?>
                No hotel bookings yet.
            <?php endif; ?>
        </h3>
        <p style="color:var(--gray); font-size:15px; margin-bottom:25px;">
            <?php if ($role === 'customer'): ?>
                Start exploring our amazing tour packages!
            <?php elseif ($role === 'guide'): ?>
                You will be assigned to tours soon.
            <?php elseif ($role === 'hotel'): ?>
                Bookings will appear here when customers reserve your hotel.
            <?php endif; ?>
        </p>
        <?php if ($role === 'customer'): ?>
            <a href="packages.php" class="btn btn-primary" style="padding:12px 32px;">
                <i class="fas fa-search"></i> Browse Packages
            </a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>

<?php foreach ($bookings as $b): ?>
<div class="booking-card">
    
    <!-- ===== CARD HEADER ===== -->
    <div class="booking-header">
        <div>
            <span class="booking-no">
                <i class="fas fa-ticket-alt" style="color:var(--primary);"></i> 
                <?php echo htmlspecialchars($b['booking_no']); ?>
            </span>
            <span class="badge badge-<?php echo getStatusBadge($b['status']); ?>">
                <?php echo ucfirst($b['status']); ?>
            </span>
            <?php if ($role === 'customer'): ?>
                <span class="badge badge-<?php echo $b['payment_status'] == 'paid' ? 'success' : ($b['payment_status'] == 'partial' ? 'warning' : 'secondary'); ?>">
                    <?php echo ucfirst($b['payment_status']); ?>
                </span>
            <?php endif; ?>
        </div>
        <div style="font-size:13px; color:var(--gray);">
            <i class="fas fa-calendar"></i> 
            <?php echo date('M d, Y', strtotime($b['created_at'])); ?>
        </div>
    </div>
    
    <!-- ===== CARD BODY (Role-based) ===== -->
    <?php if ($role === 'customer'): ?>
        <!-- CUSTOMER VIEW -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:8px 16px; margin:10px 0; font-size:13px;">
            <div><strong>Package:</strong> <?php echo htmlspecialchars($b['package_name']); ?></div>
            <div><strong>Destination:</strong> <i class="fas fa-map-marker-alt" style="color:var(--secondary);"></i> <?php echo htmlspecialchars($b['destination']); ?></div>
            <div><strong>Dates:</strong> <?php echo date('M d', strtotime($b['start_date'])); ?> - <?php echo date('M d, Y', strtotime($b['end_date'])); ?></div>
            <div><strong>Guests:</strong> <?php echo $b['adults']; ?> Adults, <?php echo $b['children']; ?> Children</div>
            <div><strong>Total:</strong> <?php echo formatCurrency($b['total_amount']); ?></div>
            <div><strong>Paid:</strong> <?php echo formatCurrency($b['paid_amount']); ?></div>
            <?php if ($b['guide_name']): ?>
            <div><strong>Guide:</strong> <?php echo htmlspecialchars($b['guide_name']); ?></div>
            <?php endif; ?>
            <?php if ($b['hotel_name']): ?>
            <div><strong>Hotel:</strong> <?php echo htmlspecialchars($b['hotel_name']); ?></div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($role === 'guide'): ?>
        <!-- GUIDE VIEW -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:8px 16px; margin:10px 0; font-size:13px;">
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($b['customer_name']); ?></div>
            <div><strong>Contact:</strong> <?php echo htmlspecialchars($b['customer_phone']); ?></div>
            <div><strong>Package:</strong> <?php echo htmlspecialchars($b['package_name']); ?></div>
            <div><strong>Destination:</strong> <i class="fas fa-map-marker-alt" style="color:var(--secondary);"></i> <?php echo htmlspecialchars($b['destination']); ?></div>
            <div><strong>Dates:</strong> <?php echo date('M d', strtotime($b['start_date'])); ?> - <?php echo date('M d, Y', strtotime($b['end_date'])); ?></div>
            <div><strong>Guests:</strong> <?php echo $b['adults']; ?> Adults, <?php echo $b['children']; ?> Children</div>
            <?php if ($b['hotel_name']): ?>
            <div><strong>Hotel:</strong> <?php echo htmlspecialchars($b['hotel_name']); ?></div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($role === 'hotel'): ?>
        <!-- HOTEL VIEW -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:8px 16px; margin:10px 0; font-size:13px;">
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($b['customer_name']); ?></div>
            <div><strong>Contact:</strong> <?php echo htmlspecialchars($b['phone']); ?></div>
            <div><strong>Package:</strong> <?php echo htmlspecialchars($b['package_name']); ?></div>
            <div><strong>Destination:</strong> <i class="fas fa-map-marker-alt" style="color:var(--secondary);"></i> <?php echo htmlspecialchars($b['destination']); ?></div>
            <div><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($b['start_date'])); ?></div>
            <div><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($b['end_date'])); ?></div>
            <?php 
            $nights = ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400);
            ?>
            <div><strong>Nights:</strong> <?php echo $nights; ?></div>
            <div><strong>Guests:</strong> <?php echo $b['adults']; ?> Adults, <?php echo $b['children']; ?> Children</div>
            <?php if ($b['guide_name']): ?>
            <div><strong>Guide:</strong> <?php echo htmlspecialchars($b['guide_name']); ?></div>
            <?php endif; ?>
            <div><strong>Total Amount:</strong> <?php echo formatCurrency($b['total_amount']); ?></div>
            <?php 
            $commission = $b['total_amount'] * 0.35;
            ?>
            <div><strong>Your Commission (35%):</strong> <?php echo formatCurrency($commission); ?></div>
        </div>
        
    <?php endif; ?>
    
    <!-- ===== SPECIAL REQUESTS ===== -->
    <?php if ($b['special_requests']): ?>
    <div class="special-requests">
        <strong><i class="fas fa-comment"></i> Special Requests:</strong>
        <?php echo nl2br(htmlspecialchars($b['special_requests'])); ?>
    </div>
    <?php endif; ?>
    
    <!-- ===== ACTIONS (Role-based) ===== -->
    <div class="booking-actions">
        <!-- PDF (Common for all) -->
        <a href="../generate_pdf.php?booking_id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-primary btn-sm">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        
        <!-- CUSTOMER ACTIONS -->
        <?php if ($role === 'customer'): ?>
            
            <!-- Edit (Pending Only) -->
            <?php if ($b['status'] === 'pending'): ?>
                <button onclick="openEditModal(<?php echo $b['id']; ?>)" 
                        class="btn btn-info btn-sm" 
                        style="background:#3498db; color:#fff; border:none; padding:5px 12px; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600;">
                    <i class="fas fa-edit"></i> Edit
                </button>
            <?php endif; ?>
            
            <!-- Pay Now -->
            <?php if ($b['status'] === 'pending' || ($b['status'] === 'confirmed' && $b['paid_amount'] < $b['total_amount'])): ?>
                <a href="payments.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-credit-card"></i> Pay Now
                </a>
            <?php endif; ?>
            
            <!-- Cancel (Pending Only) -->
            <?php if ($b['status'] === 'pending'): ?>
                <a href="?cancel=<?php echo $b['id']; ?>" class="btn btn-warning btn-sm" 
                   onclick="return confirm('Cancel this booking?')">
                    <i class="fas fa-times"></i> Cancel
                </a>
            <?php endif; ?>
            
            <!-- ============================================
                 DELETE BUTTON - NEW
                 ============================================ -->
            <?php if ($b['status'] === 'pending' || $b['status'] === 'confirmed'): ?>
                <button onclick="openDeleteModal(<?php echo $b['id']; ?>, '<?php echo htmlspecialchars($b['booking_no']); ?>')" 
                        class="btn-delete">
                    <i class="fas fa-trash"></i> Delete
                </button>
            <?php endif; ?>
            
            <!-- Feedback (Completed Only) -->
            <?php if ($b['status'] === 'completed' && $b['feedback_exists'] == 0): ?>
                <a href="feedback.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-warning btn-sm">
                    <i class="fas fa-star"></i> Give Feedback
                </a>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <!-- GUIDE ACTIONS -->
        <?php if ($role === 'guide'): ?>
            <!-- No extra actions for guide - just view -->
        <?php endif; ?>
        
        <!-- HOTEL ACTIONS -->
        <?php if ($role === 'hotel'): ?>
            <!-- No extra actions for hotel - just view -->
        <?php endif; ?>
        
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ============================================
     DELETE CONFIRMATION MODAL
     ============================================ -->
<div id="deleteModal" class="delete-modal-overlay">
    <div class="delete-modal-box">
        <div class="icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Delete Booking</h3>
        <p>Are you sure you want to delete this booking?</p>
        
        <div class="warning-text">
            <i class="fas fa-info-circle"></i> 
            <strong>Warning:</strong> This action cannot be undone!
            <br>
            <span id="deleteBookingInfo">Booking #BK001</span>
        </div>
        
        <div class="btn-group">
            <button class="btn-cancel" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <a href="#" id="deleteConfirmLink" class="btn-confirm-delete">
                <i class="fas fa-trash"></i> Yes, Delete
            </a>
        </div>
    </div>
</div>

<!-- ============================================
     EDIT MODAL (Customer Only)
     ============================================ -->
<?php if ($role === 'customer'): ?>
<div id="editModal" class="modal">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit" style="color:var(--primary);"></i> Edit Booking</h3>
            <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="booking_id" id="editBookingId">
            <input type="hidden" name="update_booking" value="1">
            <div class="modal-body">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" id="editStartDate" class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Adults *</label>
                        <input type="number" name="adults" id="editAdults" class="form-control" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Children (under 12)</label>
                        <input type="number" name="children" id="editChildren" class="form-control" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Special Requests</label>
                    <textarea name="special_requests" id="editRequests" class="form-control" rows="3"></textarea>
                </div>
                <div style="background:#fff3cd;padding:12px;border-radius:8px;font-size:13px;color:#856404;">
                    <i class="fas fa-info-circle"></i> Changing date/guests will recalculate total.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background:#e2e8f0;" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(bookingId) {
    fetch('get_booking_data.php?id=' + bookingId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editBookingId').value = data.id;
                document.getElementById('editStartDate').value = data.start_date;
                document.getElementById('editAdults').value = data.adults;
                document.getElementById('editChildren').value = data.children;
                document.getElementById('editRequests').value = data.special_requests || '';
                openModal('editModal');
            } else {
                alert('Error loading booking data.');
            }
        })
        .catch(error => {
            alert('Could not load booking data.');
        });
}
</script>
<?php endif; ?>

<!-- ============================================
     DELETE MODAL JAVASCRIPT
     ============================================ -->
<script>
// ============================================
// DELETE MODAL FUNCTIONS
// ============================================
function openDeleteModal(bookingId, bookingNo) {
    var modal = document.getElementById('deleteModal');
    var info = document.getElementById('deleteBookingInfo');
    var link = document.getElementById('deleteConfirmLink');
    
    // Set booking info
    info.innerHTML = 'Booking #' + bookingNo;
    
    // Set delete link
    link.href = '?delete=' + bookingId;
    
    // Show modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    var modal = document.getElementById('deleteModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var modal = document.getElementById('deleteModal');
        if (modal.classList.contains('show')) {
            closeDeleteModal();
        }
        var editModal = document.getElementById('editModal');
        if (editModal && editModal.style.display === 'flex') {
            closeModal('editModal');
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>