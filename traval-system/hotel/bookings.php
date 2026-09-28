<?php
require_once '../config/database.php';
checkAccess('hotel');

$page_title = 'Hotel Bookings';
$user_id = $_SESSION['user_id'];

// Get all bookings for this hotel
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email, u.phone, 
                              p.name as package_name, p.destination
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.hotel_id = ? 
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

// Get single booking view
$view_booking = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email, u.phone,
                                  p.name as package_name, p.destination, p.duration_days, p.duration_nights
                           FROM bookings b 
                           JOIN users u ON b.customer_id = u.id 
                           JOIN packages p ON b.package_id = p.id 
                           WHERE b.id = ? AND b.hotel_id = ?");
    $stmt->execute([$_GET['view'], $user_id]);
    $view_booking = $stmt->fetch();
}

include '../includes/header.php';
?>

<!-- View Single Booking -->
<?php if ($view_booking): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-eye"></i> Booking Details - <?php echo htmlspecialchars($view_booking['booking_no']); ?></h3>
        <a href="bookings.php" class="btn btn-primary btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
            <div style="background:var(--light);padding:15px;border-radius:10px;">
                <h4 style="color:var(--primary);">Customer Information</h4>
                <div><strong>Name:</strong> <?php echo htmlspecialchars($view_booking['customer_name']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($view_booking['email']); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($view_booking['phone']); ?></div>
            </div>
            <div style="background:var(--light);padding:15px;border-radius:10px;">
                <h4 style="color:var(--primary);">Package Details</h4>
                <div><strong>Package:</strong> <?php echo htmlspecialchars($view_booking['package_name']); ?></div>
                <div><strong>Destination:</strong> <?php echo htmlspecialchars($view_booking['destination']); ?></div>
                <div><strong>Duration:</strong> <?php echo $view_booking['duration_days']; ?> Days / <?php echo $view_booking['duration_nights']; ?> Nights</div>
            </div>
            <div style="background:var(--light);padding:15px;border-radius:10px;">
                <h4 style="color:var(--primary);">Stay Details</h4>
                <div><strong>Check-in:</strong> <?php echo date('F d, Y', strtotime($view_booking['start_date'])); ?></div>
                <div><strong>Check-out:</strong> <?php echo date('F d, Y', strtotime($view_booking['end_date'])); ?></div>
                <div><strong>Nights:</strong> <?php echo ceil((strtotime($view_booking['end_date']) - strtotime($view_booking['start_date'])) / 86400); ?> nights</div>
                <div><strong>Guests:</strong> <?php echo $view_booking['adults']; ?> Adults, <?php echo $view_booking['children']; ?> Children</div>
                <div><strong>Status:</strong> <span class="badge badge-<?php echo getStatusBadge($view_booking['status']); ?>"><?php echo ucfirst($view_booking['status']); ?></span></div>
            </div>
        </div>
        
        <?php if ($view_booking['special_requests']): ?>
        <div style="background:#fff3cd;padding:15px;border-radius:10px;margin-top:15px;">
            <h4 style="color:#856404;">Special Requests</h4>
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

<!-- List All Bookings -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-bed"></i> Hotel Bookings</h3>
        <span style="font-size:13px;color:var(--gray);">Total: <?php echo count($bookings); ?> bookings</span>
    </div>
    <div class="card-body">
        <?php if (empty($bookings)): ?>
            <p style="text-align:center;padding:60px;color:var(--gray);">
                <i class="fas fa-bed" style="font-size:48px;display:block;margin-bottom:15px;"></i>
                No bookings found for your hotel.
            </p>
        <?php else: ?>
            <?php 
            $status_groups = [
                'confirmed' => ['label' => 'Confirmed', 'icon' => 'fa-check-circle', 'color' => 'success'],
                'pending' => ['label' => 'Pending', 'icon' => 'fa-clock', 'color' => 'warning'],
                'completed' => ['label' => 'Completed', 'icon' => 'fa-flag-checkered', 'color' => 'info'],
                'cancelled' => ['label' => 'Cancelled', 'icon' => 'fa-times-circle', 'color' => 'danger']
            ];
            
            foreach ($status_groups as $status => $info):
                $filtered = array_filter($bookings, function($b) use ($status) {
                    return $b['status'] == $status;
                });
                if (empty($filtered)) continue;
            ?>
            <div style="margin-bottom:20px;">
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
                        <div><strong>Contact:</strong> <?php echo htmlspecialchars($b['phone']); ?></div>
                        <div><strong>Email:</strong> <?php echo htmlspecialchars($b['email']); ?></div>
                        <div><strong>Guests:</strong> <?php echo $b['adults']; ?> Adults, <?php echo $b['children']; ?> Children</div>
                        <div><strong>Check-in:</strong> <?php echo date('M d', strtotime($b['start_date'])); ?></div>
                        <div><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($b['end_date'])); ?></div>
                        <?php 
                        $nights = ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400);
                        ?>
                        <div><strong>Nights:</strong> <?php echo $nights; ?></div>
                        <div><strong>Total Amount:</strong> <?php echo formatCurrency($b['total_amount']); ?></div>
                        <?php 
                        $commission = $b['total_amount'] * 0.35;
                        ?>
                        <div><strong>Your Commission (35%):</strong> <?php echo formatCurrency($commission); ?></div>
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
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>