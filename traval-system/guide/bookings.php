<?php
require_once '../config/database.php';
checkAccess('guide');

$page_title = 'My Assignments';
$user_id = $_SESSION['user_id'];

// ============================================
// GET GUIDE PROFILE (NEW)
// ============================================
$stmt = $pdo->prepare("SELECT * FROM guide_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$guide_profile = $stmt->fetch();

// ============================================
// GET ALL BOOKINGS ASSIGNED TO THIS GUIDE
// ============================================
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, 
                              p.name as package_name, p.destination, p.duration_days, p.duration_nights,
                              hp.hotel_name
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
                           b.start_date ASC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// ============================================
// GET SINGLE BOOKING VIEW
// ============================================
$view_booking = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, 
                                  p.name as package_name, p.destination, p.duration_days, p.duration_nights,
                                  hp.hotel_name
                           FROM bookings b 
                           JOIN users u ON b.customer_id = u.id 
                           JOIN packages p ON b.package_id = p.id 
                           LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id
                           WHERE b.id = ? AND b.guide_id = ?");
    $stmt->execute([$_GET['view'], $user_id]);
    $view_booking = $stmt->fetch();
}

include '../includes/header.php';
?>

<!-- ============================================
     GUIDE STATS CARD (NEW)
     ============================================ -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-route"></i></div>
        <div class="stat-info">
            <h3><?php echo count($bookings); ?></h3>
            <p>Total Assignments</p>
        </div>
        <div class="stat-trend">📋 All</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3>
                <?php 
                $confirmed_count = count(array_filter($bookings, function($b) { return $b['status'] == 'confirmed'; }));
                echo $confirmed_count;
                ?>
            </h3>
            <p>Active Tours</p>
        </div>
        <div class="stat-trend up">✅ Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-flag-checkered"></i></div>
        <div class="stat-info">
            <h3>
                <?php 
                $completed_count = count(array_filter($bookings, function($b) { return $b['status'] == 'completed'; }));
                echo $completed_count;
                ?>
            </h3>
            <p>Completed Tours</p>
        </div>
        <div class="stat-trend up">🏁 Done</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-star"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($guide_profile['rating'] ?? 0, 1); ?> / 5</h3>
            <p>Your Rating</p>
        </div>
        <div class="stat-trend">⭐ <?php echo $guide_profile['total_reviews'] ?? 0; ?> reviews</div>
    </div>
</div>

<!-- ============================================
     GUIDE QUICK ACTIONS (NEW)
     ============================================ -->
<div class="card" style="border-left:4px solid var(--primary); margin-bottom:24px;">
    <div class="card-header">
        <h3><i class="fas fa-tools"></i> Quick Actions</h3>
        <span style="font-size:13px; color:var(--gray);">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        </span>
    </div>
    <div class="card-body">
        <div style="display:flex; gap:15px; flex-wrap:wrap;">
            <a href="profile.php" class="btn btn-primary btn-sm">
                <i class="fas fa-user-edit"></i> Update Profile
            </a>
            <a href="reports.php" class="btn btn-info btn-sm" style="background:#3498db; color:#fff;">
                <i class="fas fa-chart-bar"></i> View Reports
            </a>
            <a href="#" onclick="window.print()" class="btn btn-success btn-sm">
                <i class="fas fa-print"></i> Print Schedule
            </a>
            <?php if ($guide_profile['availability'] == 'available'): ?>
                <span style="background:#d4f5e6; color:#0a5c4a; padding:6px 16px; border-radius:20px; font-size:13px; font-weight:600;">
                    <i class="fas fa-check-circle"></i> Available for Tours
                </span>
            <?php else: ?>
                <span style="background:#fee2e2; color:#dc2626; padding:6px 16px; border-radius:20px; font-size:13px; font-weight:600;">
                    <i class="fas fa-times-circle"></i> Currently Busy
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================
     VIEW SINGLE BOOKING
     ============================================ -->
<?php if ($view_booking): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-eye"></i> Booking Details - <?php echo htmlspecialchars($view_booking['booking_no']); ?></h3>
        <a href="bookings.php" class="btn btn-primary btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
            <!-- Customer Information -->
            <div style="background:var(--light);padding:15px;border-radius:10px;">
                <h4 style="color:var(--primary);">
                    <i class="fas fa-user-circle"></i> Customer Information
                </h4>
                <div><strong>Name:</strong> <?php echo htmlspecialchars($view_booking['customer_name']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($view_booking['customer_email']); ?></div>
                <div><strong>Phone:</strong> 
                    <a href="tel:<?php echo htmlspecialchars($view_booking['customer_phone']); ?>" style="color:var(--primary);">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($view_booking['customer_phone']); ?>
                    </a>
                </div>
                <!-- NEW: Quick Contact -->
                <div style="margin-top:8px;">
                    <a href="mailto:<?php echo htmlspecialchars($view_booking['customer_email']); ?>" class="btn btn-primary btn-sm" style="padding:4px 12px;">
                        <i class="fas fa-envelope"></i> Email
                    </a>
                    <a href="tel:<?php echo htmlspecialchars($view_booking['customer_phone']); ?>" class="btn btn-success btn-sm" style="padding:4px 12px;">
                        <i class="fas fa-phone"></i> Call
                    </a>
                </div>
            </div>
            <!-- Package Details -->
            <div style="background:var(--light);padding:15px;border-radius:10px;">
                <h4 style="color:var(--primary);"><i class="fas fa-box"></i> Package Details</h4>
                <div><strong>Package:</strong> <?php echo htmlspecialchars($view_booking['package_name']); ?></div>
                <div><strong>Destination:</strong> <?php echo htmlspecialchars($view_booking['destination']); ?></div>
                <div><strong>Duration:</strong> <?php echo $view_booking['duration_days']; ?> Days / <?php echo $view_booking['duration_nights']; ?> Nights</div>
            </div>
            <!-- Tour Details -->
            <div style="background:var(--light);padding:15px;border-radius:10px;">
                <h4 style="color:var(--primary);"><i class="fas fa-calendar-alt"></i> Tour Details</h4>
                <div><strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($view_booking['start_date'])); ?></div>
                <div><strong>End Date:</strong> <?php echo date('F d, Y', strtotime($view_booking['end_date'])); ?></div>
                <div><strong>Guests:</strong> <?php echo $view_booking['adults']; ?> Adults, <?php echo $view_booking['children']; ?> Children</div>
                <div><strong>Status:</strong> <span class="badge badge-<?php echo getStatusBadge($view_booking['status']); ?>"><?php echo ucfirst($view_booking['status']); ?></span></div>
            </div>
        </div>
        
        <?php if ($view_booking['hotel_name']): ?>
        <div style="background:var(--light);padding:15px;border-radius:10px;margin-top:15px;">
            <h4 style="color:var(--primary);"><i class="fas fa-hotel"></i> Accommodation</h4>
            <div><strong>Hotel:</strong> <?php echo htmlspecialchars($view_booking['hotel_name']); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($view_booking['special_requests']): ?>
        <div style="background:#fff3cd;padding:15px;border-radius:10px;margin-top:15px;">
            <h4 style="color:#856404;"><i class="fas fa-comment"></i> Special Requests</h4>
            <div><?php echo nl2br(htmlspecialchars($view_booking['special_requests'])); ?></div>
        </div>
        <?php endif; ?>
        
        <div style="margin-top:20px;">
            <a href="../generate_pdf.php?booking_id=<?php echo $view_booking['id']; ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Download Details
            </a>
            <a href="bookings.php" class="btn" style="background:#e2e8f0;">Back</a>
        </div>
    </div>
</div>

<?php else: ?>

<!-- ============================================
     LIST ALL ASSIGNMENTS - ENHANCED
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-route"></i> My Assignments</h3>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <span style="font-size:13px;color:var(--gray);">Total: <?php echo count($bookings); ?> bookings</span>
            <!-- NEW: Filter Buttons -->
            <div style="display:flex; gap:5px;">
                <button onclick="filterBookings('all')" class="btn btn-primary btn-sm" id="filterAll">All</button>
                <button onclick="filterBookings('confirmed')" class="btn btn-success btn-sm" id="filterConfirmed">✅ Active</button>
                <button onclick="filterBookings('pending')" class="btn btn-warning btn-sm" id="filterPending">⏳ Pending</button>
                <button onclick="filterBookings('completed')" class="btn btn-info btn-sm" id="filterCompleted">🏁 Done</button>
                <button onclick="filterBookings('cancelled')" class="btn btn-danger btn-sm" id="filterCancelled">❌ Cancelled</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($bookings)): ?>
            <p style="text-align:center;padding:60px;color:var(--gray);">
                <i class="fas fa-route" style="font-size:48px;display:block;margin-bottom:15px;"></i>
                You don't have any assignments yet.
            </p>
        <?php else: ?>
            <?php 
            $status_groups = [
                'confirmed' => ['label' => '✅ Confirmed', 'icon' => 'fa-check-circle', 'color' => 'success'],
                'pending' => ['label' => '⏳ Pending', 'icon' => 'fa-clock', 'color' => 'warning'],
                'completed' => ['label' => '🏁 Completed', 'icon' => 'fa-flag-checkered', 'color' => 'info'],
                'cancelled' => ['label' => '❌ Cancelled', 'icon' => 'fa-times-circle', 'color' => 'danger']
            ];
            
            foreach ($status_groups as $status => $info):
                $filtered = array_filter($bookings, function($b) use ($status) {
                    return $b['status'] == $status;
                });
                if (empty($filtered)) continue;
            ?>
            <div style="margin-bottom:20px;" class="booking-group" data-status="<?php echo $status; ?>">
                <h4 style="color:var(--<?php echo $info['color']; ?>);margin-bottom:10px;">
                    <i class="fas <?php echo $info['icon']; ?>"></i> <?php echo $info['label']; ?> 
                    <span style="font-size:13px;color:var(--gray);font-weight:normal;">(<?php echo count($filtered); ?>)</span>
                </h4>
                <?php foreach ($filtered as $b): ?>
                <div class="booking-card" style="border-left-color:var(--<?php echo $info['color']; ?>);">
                    <div class="booking-header">
                        <div>
                            <span class="booking-no"><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($b['booking_no']); ?></span>
                            <span class="badge badge-<?php echo getStatusBadge($b['status']); ?>"><?php echo ucfirst($b['status']); ?></span>
                            <!-- NEW: Days Left Badge -->
                            <?php if ($b['status'] == 'confirmed'): 
                                $days_left = ceil((strtotime($b['start_date']) - time()) / 86400);
                            ?>
                                <span class="badge <?php echo $days_left <= 3 ? 'badge-danger' : 'badge-info'; ?>" style="font-size:10px;">
                                    <?php echo $days_left > 0 ? $days_left . ' days left' : 'Today'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:13px;color:var(--gray);">
                            <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($b['start_date'])); ?>
                        </div>
                    </div>
                    
                    <div style="margin:8px 0;">
                        <strong><?php echo htmlspecialchars($b['package_name']); ?></strong>
                        <span style="font-size:13px;color:var(--gray);">- <?php echo htmlspecialchars($b['destination']); ?></span>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;font-size:13px;">
                        <div><strong>Customer:</strong> <?php echo htmlspecialchars($b['customer_name']); ?></div>
                        <div><strong>Contact:</strong> 
                            <a href="tel:<?php echo htmlspecialchars($b['customer_phone']); ?>" style="color:var(--primary);">
                                <?php echo htmlspecialchars($b['customer_phone']); ?>
                            </a>
                        </div>
                        <div><strong>Guests:</strong> <?php echo $b['adults']; ?> Adults, <?php echo $b['children']; ?> Children</div>
                        <?php if ($b['hotel_name']): ?>
                            <div><strong>Hotel:</strong> <?php echo htmlspecialchars($b['hotel_name']); ?></div>
                        <?php endif; ?>
                        <!-- NEW: Quick Contact Icons -->
                        <div style="display:flex; gap:8px; align-items:center;">
                            <a href="mailto:<?php echo htmlspecialchars($b['customer_email']); ?>" title="Email Customer" style="color:var(--info);">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="tel:<?php echo htmlspecialchars($b['customer_phone']); ?>" title="Call Customer" style="color:var(--success);">
                                <i class="fas fa-phone"></i>
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($b['special_requests']): ?>
                        <div class="special-requests" style="margin-top:8px;padding:8px 12px;background:#fff3cd;border-radius:8px;font-size:13px;">
                            <strong><i class="fas fa-comment"></i> Special Requests:</strong>
                            <?php echo nl2br(htmlspecialchars(substr($b['special_requests'], 0, 100))); ?>
                            <?php if (strlen($b['special_requests']) > 100): ?>...<?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="booking-actions">
                        <a href="?view=<?php echo $b['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="../generate_pdf.php?booking_id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <!-- NEW: Quick Action - Mark as Completed (for guide) -->
                        <?php if ($b['status'] == 'confirmed' && strtotime($b['start_date']) <= time()): ?>
                            <a href="#" onclick="return confirm('Mark this tour as completed?')" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Complete Tour
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT - FILTER & SEARCH
     ============================================ -->
<script>
// ============================================
// FILTER BOOKINGS BY STATUS
// ============================================
function filterBookings(status) {
    const groups = document.querySelectorAll('.booking-group');
    const buttons = document.querySelectorAll('.booking-group .btn');
    
    groups.forEach(group => {
        const groupStatus = group.dataset.status;
        if (status === 'all' || groupStatus === status) {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    });
    
    // Update active button
    const filterButtons = ['filterAll', 'filterConfirmed', 'filterPending', 'filterCompleted', 'filterCancelled'];
    filterButtons.forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.style.opacity = id === 'filter' + status.charAt(0).toUpperCase() + status.slice(1) ? '1' : '0.5';
        }
    });
}

// ============================================
// SEARCH ASSIGNMENTS
// ============================================
function searchAssignments() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const cards = document.querySelectorAll('.booking-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(filter) ? '' : 'none';
    });
}
</script>

<!-- ============================================
     SEARCH BAR (NEW)
     ============================================ -->
<div style="margin-bottom:16px;">
    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
        <div style="flex:1; min-width:200px; position:relative;">
            <i class="fas fa-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--gray);"></i>
            <input type="text" id="searchInput" placeholder="Search by customer, package, destination..." 
                   onkeyup="searchAssignments()"
                   style="width:100%; padding:10px 14px 10px 40px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.3s ease;">
        </div>
        <button onclick="document.getElementById('searchInput').value=''; searchAssignments();" 
                style="padding:10px 20px; background:#e2e8f0; border:none; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.3s ease;">
            <i class="fas fa-undo"></i> Reset
        </button>
    </div>
</div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>