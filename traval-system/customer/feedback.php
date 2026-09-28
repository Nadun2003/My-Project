<?php
require_once '../config/database.php';
checkAccess('customer');

$page_title = 'Give Feedback';
$user_id = $_SESSION['user_id'];

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $booking_id = $_POST['booking_id'];
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);
    
    // Check if booking belongs to this customer and is completed
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND customer_id = ? AND status = 'completed'");
    $stmt->execute([$booking_id, $user_id]);
    if ($stmt->fetch()) {
        // Check if feedback already exists
        $stmt = $pdo->prepare("SELECT id FROM feedback WHERE booking_id = ? AND customer_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        if ($stmt->fetch()) {
            toast("You have already submitted feedback for this booking.", 'warning');
        } else {
            $stmt = $pdo->prepare("INSERT INTO feedback (booking_id, customer_id, rating, comment) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$booking_id, $user_id, $rating, $comment])) {
                toast("Thank you for your feedback!");
                redirect('bookings.php');
            } else {
                toast("Failed to submit feedback. Please try again.", 'error');
            }
        }
    } else {
        toast("Invalid booking.", 'error');
    }
}

// Get completed bookings without feedback
$stmt = $pdo->prepare("SELECT b.*, p.name as package_name 
                       FROM bookings b 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.customer_id = ? AND b.status = 'completed' 
                       AND NOT EXISTS (SELECT 1 FROM feedback f WHERE f.booking_id = b.id AND f.customer_id = ?)
                       ORDER BY b.end_date DESC");
$stmt->execute([$user_id, $user_id]);
$completed_bookings = $stmt->fetchAll();

// Get feedback history
$stmt = $pdo->prepare("SELECT f.*, b.booking_no, p.name as package_name 
                       FROM feedback f 
                       JOIN bookings b ON f.booking_id = b.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE f.customer_id = ? 
                       ORDER BY f.created_at DESC");
$stmt->execute([$user_id]);
$feedback_history = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- Give Feedback -->
<?php if (!empty($completed_bookings)): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-star"></i> Give Feedback</h3>
        <span style="font-size:13px;color:var(--gray);"><?php echo count($completed_bookings); ?> bookings pending feedback</span>
    </div>
    <div class="card-body">
        <?php foreach ($completed_bookings as $b): ?>
        <div class="booking-card" style="background:#f0fdf4;border-left-color:var(--success);">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;">
                <div>
                    <strong><?php echo htmlspecialchars($b['booking_no']); ?></strong>
                    <span style="font-size:13px;color:var(--gray);">- <?php echo htmlspecialchars($b['package_name']); ?></span>
                </div>
                <span style="font-size:13px;color:var(--gray);">
                    <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($b['end_date'])); ?>
                </span>
            </div>
            <button class="btn btn-primary btn-sm" style="margin-top:10px;" onclick="openFeedbackModal(<?php echo $b['id']; ?>)">
                <i class="fas fa-star"></i> Rate & Review
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Feedback History -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> My Feedback History</h3>
    </div>
    <div class="card-body">
        <?php if (empty($feedback_history)): ?>
            <p style="text-align:center;padding:40px;color:var(--gray);">
                <i class="fas fa-star" style="font-size:48px;display:block;margin-bottom:15px;"></i>
                You haven't submitted any feedback yet.
            </p>
        <?php else: ?>
            <?php foreach ($feedback_history as $f): ?>
            <div class="booking-card" style="border-left-color:var(--secondary);">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;">
                    <div>
                        <strong><?php echo htmlspecialchars($f['booking_no']); ?></strong>
                        <span style="font-size:13px;color:var(--gray);">- <?php echo htmlspecialchars($f['package_name']); ?></span>
                    </div>
                    <div>
                        <?php for($i=1; $i<=5; $i++): ?>
                            <span style="color:<?php echo $i <= $f['rating'] ? '#f5a623' : '#e2e8f0'; ?>;">★</span>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if ($f['comment']): ?>
                    <div style="margin-top:8px;padding:10px;background:var(--light);border-radius:8px;font-size:14px;">
                        "<?php echo htmlspecialchars($f['comment']); ?>"
                    </div>
                <?php endif; ?>
                <div style="font-size:12px;color:var(--gray);margin-top:8px;">
                    <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($f['created_at'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-star"></i> Rate Your Experience</h3>
            <button class="close-btn" onclick="closeModal('feedbackModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="booking_id" id="feedbackBookingId">
            <div class="modal-body">
                <div class="form-group">
                    <label>Rating *</label>
                    <div style="display:flex;gap:8px;font-size:32px;">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <span class="star" data-value="<?php echo $i; ?>" 
                                  style="cursor:pointer;color:#e2e8f0;transition:all 0.2s;"
                                  onclick="setRating(<?php echo $i; ?>)">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="5">
                    <div style="font-size:13px;color:var(--gray);margin-top:5px;" id="ratingText">Excellent!</div>
                </div>
                <div class="form-group">
                    <label>Comment</label>
                    <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience with us..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('feedbackModal')">Cancel</button>
                <button type="submit" name="submit_feedback" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const ratingLabels = ['', 'Terrible', 'Poor', 'Average', 'Good', 'Excellent!'];

function setRating(val) {
    document.getElementById('ratingValue').value = val;
    document.getElementById('ratingText').textContent = ratingLabels[val];
    document.querySelectorAll('.star').forEach((star, index) => {
        star.style.color = index < val ? '#f5a623' : '#e2e8f0';
    });
}

function openFeedbackModal(id) {
    document.getElementById('feedbackBookingId').value = id;
    setRating(5);
    openModal('feedbackModal');
}

// Set default rating on load
setRating(5);
</script>

<?php include '../includes/footer.php'; ?>