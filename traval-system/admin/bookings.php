<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Booking Management';

// ============================================
// HANDLE STATUS UPDATE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['id']]);
    toast("Booking status updated to " . ucfirst($_POST['status']));
    redirect('bookings.php');
}

// ============================================
// HANDLE GUIDE ASSIGN
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_guide'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET guide_id = ? WHERE id = ?");
    $stmt->execute([$_POST['guide_id'], $_POST['booking_id']]);
    toast("Guide assigned successfully!");
    redirect('bookings.php');
}

// ============================================
// HANDLE HOTEL ASSIGN
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_hotel'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET hotel_id = ? WHERE id = ?");
    $stmt->execute([$_POST['hotel_id'], $_POST['booking_id']]);
    toast("Hotel assigned successfully!");
    redirect('bookings.php');
}

// ============================================
// GET GUIDES & HOTELS FOR DROPDOWNS
// ============================================
$guides = $pdo->query("SELECT u.id, u.name FROM users u JOIN guide_profiles gp ON u.id = gp.user_id WHERE u.role = 'guide' AND u.status = 'active'")->fetchAll();
$hotels = $pdo->query("SELECT u.id, hp.hotel_name FROM users u JOIN hotel_profiles hp ON u.id = hp.user_id WHERE u.role = 'hotel' AND u.status = 'active'")->fetchAll();

// ============================================
// GET ALL BOOKINGS WITH DETAILS
// ============================================
$bookings = $pdo->query("SELECT b.*, u.name as customer_name, p.name as package_name, g.name as guide_name, hp.hotel_name 
                         FROM bookings b 
                         JOIN users u ON b.customer_id = u.id 
                         JOIN packages p ON b.package_id = p.id 
                         LEFT JOIN users g ON b.guide_id = g.id 
                         LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id 
                         ORDER BY b.created_at DESC")->fetchAll();

// ============================================
// CALCULATE STATS
// ============================================
$total_bookings = count($bookings);
$pending_count = 0;
$confirmed_count = 0;
$cancelled_count = 0;
$completed_count = 0;

foreach ($bookings as $b) {
    if ($b['status'] == 'pending') $pending_count++;
    elseif ($b['status'] == 'confirmed') $confirmed_count++;
    elseif ($b['status'] == 'cancelled') $cancelled_count++;
    elseif ($b['status'] == 'completed') $completed_count++;
}

include '../includes/header.php';
?>

<!-- ============================================
     PAGE HEADER WITH STATS
     ============================================ -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:15px;">
    <div>
        <h2 style="font-size:24px; font-weight:700; color:var(--dark);">
            <i class="fas fa-calendar-check" style="color:var(--primary);"></i> Booking Management
        </h2>
        <p style="color:var(--gray); font-size:14px; margin-top:4px;">
            Manage all customer bookings, assign guides and hotels
        </p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <span style="background:#fff3cd; padding:6px 16px; border-radius:20px; font-size:13px; font-weight:600; color:#856404;">
            <i class="fas fa-clock"></i> <?php echo $pending_count; ?> Pending
        </span>
        <span style="background:#d4f5e6; padding:6px 16px; border-radius:20px; font-size:13px; font-weight:600; color:#0a5c4a;">
            <i class="fas fa-check-circle"></i> <?php echo $confirmed_count; ?> Confirmed
        </span>
        <span style="background:#fee2e2; padding:6px 16px; border-radius:20px; font-size:13px; font-weight:600; color:#dc2626;">
            <i class="fas fa-times-circle"></i> <?php echo $cancelled_count; ?> Cancelled
        </span>
        <span style="background:#d1ecf1; padding:6px 16px; border-radius:20px; font-size:13px; font-weight:600; color:#1a5276;">
            <i class="fas fa-flag-checkered"></i> <?php echo $completed_count; ?> Completed
        </span>
    </div>
</div>

<!-- ============================================
     SEARCH & FILTER BAR (UI Only - Advanced Look)
     ============================================ -->
<div style="background:var(--white); border-radius:var(--radius-lg); padding:16px 20px; margin-bottom:24px; box-shadow:var(--shadow); display:flex; gap:15px; flex-wrap:wrap; align-items:center; border:1px solid rgba(0,0,0,0.03);">
    <div style="flex:1; min-width:200px; position:relative;">
        <i class="fas fa-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--gray);"></i>
        <input type="text" id="searchInput" placeholder="Search by booking no, customer, package..." 
               style="width:100%; padding:10px 14px 10px 40px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; transition:all 0.3s ease; outline:none;">
    </div>
    <div>
        <select id="statusFilter" style="padding:10px 16px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; background:var(--white); outline:none; cursor:pointer;">
            <option value="all">📋 All Status</option>
            <option value="pending">⏳ Pending</option>
            <option value="confirmed">✅ Confirmed</option>
            <option value="cancelled">❌ Cancelled</option>
            <option value="completed">🏁 Completed</option>
        </select>
    </div>
    <button onclick="document.getElementById('searchInput').value=''; document.getElementById('statusFilter').value='all'; filterTable();" 
            style="padding:10px 20px; background:#e2e8f0; border:none; border-radius:10px; font-size:14px; font-weight:600; color:var(--dark); cursor:pointer; transition:all 0.3s ease;">
        <i class="fas fa-undo"></i> Reset
    </button>
</div>

<!-- ============================================
     BOOKINGS TABLE
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list" style="color:var(--primary);"></i> All Bookings</h3>
        <span style="font-size:14px; color:var(--gray); background:var(--light); padding:4px 16px; border-radius:20px;">
            Total: <?php echo $total_bookings; ?> bookings
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive" style="padding:0 4px;">
            <table class="data-table" id="bookingTable">
                <thead>
                    <tr>
                        <th style="min-width:120px;">Booking No</th>
                        <th style="min-width:120px;">Customer</th>
                        <th style="min-width:140px;">Package</th>
                        <th style="min-width:100px;">Guide</th>
                        <th style="min-width:100px;">Hotel</th>
                        <th style="min-width:100px;">Start Date</th>
                        <th style="min-width:90px;">Total</th>
                        <th style="min-width:90px;">Paid</th>
                        <th style="min-width:100px;">Status</th>
                        <th style="min-width:300px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr class="booking-row" data-status="<?php echo $b['status']; ?>" 
                        data-search="<?php echo strtolower($b['booking_no'] . ' ' . $b['customer_name'] . ' ' . $b['package_name']); ?>">
                        <td>
                            <strong style="color:var(--primary);"><?php echo htmlspecialchars($b['booking_no']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['package_name']); ?></td>
                        <td>
                            <?php if ($b['guide_name']): ?>
                                <span style="color:var(--success); font-weight:500;"><?php echo htmlspecialchars($b['guide_name']); ?></span>
                            <?php else: ?>
                                <span style="color:var(--gray); font-style:italic;">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($b['hotel_name']): ?>
                                <span style="color:var(--info); font-weight:500;"><?php echo htmlspecialchars($b['hotel_name']); ?></span>
                            <?php else: ?>
                                <span style="color:var(--gray); font-style:italic;">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($b['start_date'])); ?></td>
                        <td><strong><?php echo formatCurrency($b['total_amount']); ?></strong></td>
                        <td><?php echo formatCurrency($b['paid_amount']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo getStatusBadge($b['status']); ?>" style="font-size:11px;">
                                <?php echo ucfirst($b['status']); ?>
                            </span>
                        </td>
                        <td>
                            <!-- ===== ACTION FORMS ===== -->
                            <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                                
                                <!-- 1. Status Update -->
                                <form method="POST" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" 
                                            style="padding:4px 6px; border:2px solid #e2e8f0; border-radius:6px; font-size:11px; background:var(--white); cursor:pointer; outline:none; min-width:85px;">
                                        <option value="pending" <?php echo $b['status'] == 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                        <option value="confirmed" <?php echo $b['status'] == 'confirmed' ? 'selected' : ''; ?>>✅ Confirm</option>
                                        <option value="cancelled" <?php echo $b['status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Cancel</option>
                                        <option value="completed" <?php echo $b['status'] == 'completed' ? 'selected' : ''; ?>>🏁 Complete</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                
                                <!-- 2. Assign Guide -->
                                <form method="POST" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <select name="guide_id" onchange="this.form.submit()" 
                                            style="padding:4px 6px; border:2px solid #e2e8f0; border-radius:6px; font-size:11px; background:var(--white); cursor:pointer; outline:none; max-width:110px;">
                                        <option value="">👤 Guide</option>
                                        <?php foreach ($guides as $g): ?>
                                            <option value="<?php echo $g['id']; ?>" <?php echo $b['guide_id'] == $g['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($g['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="assign_guide" value="1">
                                </form>
                                
                                <!-- 3. Assign Hotel -->
                                <form method="POST" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <select name="hotel_id" onchange="this.form.submit()" 
                                            style="padding:4px 6px; border:2px solid #e2e8f0; border-radius:6px; font-size:11px; background:var(--white); cursor:pointer; outline:none; max-width:110px;">
                                        <option value="">🏨 Hotel</option>
                                        <?php foreach ($hotels as $h): ?>
                                            <option value="<?php echo $h['id']; ?>" <?php echo $b['hotel_id'] == $h['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($h['hotel_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="assign_hotel" value="1">
                                </form>
                                
                                <!-- 4. PDF Button -->
                                <a href="../generate_pdf.php?booking_id=<?php echo $b['id']; ?>" target="_blank" 
                                   class="btn btn-primary btn-sm" style="padding:4px 10px; font-size:11px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                                
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT FOR SEARCH & FILTER
     ============================================ -->
<script>
function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.booking-row');
    
    rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        const rowStatus = row.getAttribute('data-status') || '';
        
        const matchSearch = searchTerm === '' || searchData.includes(searchTerm);
        const matchStatus = statusFilter === 'all' || rowStatus === statusFilter;
        
        row.style.display = (matchSearch && matchStatus) ? '' : 'none';
    });
}

// Add event listeners
document.getElementById('searchInput').addEventListener('keyup', filterTable);
document.getElementById('statusFilter').addEventListener('change', filterTable);
</script>

<?php include '../includes/footer.php'; ?>