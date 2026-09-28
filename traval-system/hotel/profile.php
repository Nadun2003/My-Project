<?php
require_once '../config/database.php';
checkAccess('hotel');

$page_title = 'Hotel Profile';
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get hotel profile
$stmt = $pdo->prepare("SELECT * FROM hotel_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// If no profile exists, create one
if (!$profile) {
    $stmt = $pdo->prepare("INSERT INTO hotel_profiles (user_id, hotel_name) VALUES (?, ?)");
    $stmt->execute([$user_id, $user['name']]);
    $profile = [
        'hotel_name' => $user['name'],
        'location' => '',
        'stars' => 3,
        'room_count' => 0,
        'price_per_night' => 0,
        'amenities' => '',
        'check_in_time' => '',
        'check_out_time' => ''
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotel_name = trim($_POST['hotel_name']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $stars = $_POST['stars'];
    $room_count = $_POST['room_count'];
    $price_per_night = $_POST['price_per_night'];
    $amenities = trim($_POST['amenities']);
    $check_in_time = trim($_POST['check_in_time']);
    $check_out_time = trim($_POST['check_out_time']);
    
    // Update user
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$hotel_name, $phone, $user_id]);
    
    // Update profile
    $stmt = $pdo->prepare("UPDATE hotel_profiles SET 
                           hotel_name = ?, 
                           location = ?, 
                           stars = ?, 
                           room_count = ?, 
                           price_per_night = ?, 
                           amenities = ?, 
                           check_in_time = ?, 
                           check_out_time = ? 
                           WHERE user_id = ?");
    $stmt->execute([$hotel_name, $location, $stars, $room_count, $price_per_night, $amenities, $check_in_time, $check_out_time, $user_id]);
    
    // Update password if provided
    if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password'] && strlen($_POST['new_password']) >= 6) {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            toast("Password updated successfully!");
        } elseif (strlen($_POST['new_password']) < 6) {
            toast("Password must be at least 6 characters.", 'error');
        } else {
            toast("Passwords do not match.", 'error');
        }
    }
    
    $_SESSION['user_name'] = $hotel_name;
    toast("Profile updated successfully!");
    redirect('profile.php');
}

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-building"></i> Hotel Profile</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <!-- Hotel Information -->
            <div class="form-row">
                <div class="form-group">
                    <label>Hotel Name *</label>
                    <input type="text" name="hotel_name" class="form-control" value="<?php echo htmlspecialchars($profile['hotel_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($profile['location']); ?>" placeholder="City, Area">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Star Rating</label>
                    <select name="stars" class="form-control">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $profile['stars'] == $i ? 'selected' : ''; ?>>
                                <?php echo str_repeat('★', $i); ?> <?php echo $i; ?> Star<?php echo $i>1?'s':''; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Number of Rooms</label>
                    <input type="number" name="room_count" class="form-control" value="<?php echo $profile['room_count']; ?>" min="0">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Price Per Night (LKR)</label>
                    <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?php echo $profile['price_per_night']; ?>" min="0">
                </div>
                <div class="form-group">
                    <label>Check-in Time</label>
                    <input type="text" name="check_in_time" class="form-control" value="<?php echo htmlspecialchars($profile['check_in_time']); ?>" placeholder="2:00 PM">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Check-out Time</label>
                    <input type="text" name="check_out_time" class="form-control" value="<?php echo htmlspecialchars($profile['check_out_time']); ?>" placeholder="12:00 PM">
                </div>
            </div>
            
            <div class="form-group">
                <label>Amenities</label>
                <textarea name="amenities" class="form-control" rows="3" placeholder="WiFi, Swimming Pool, Restaurant, Parking, Spa, Gym, etc."><?php echo htmlspecialchars($profile['amenities']); ?></textarea>
                <div style="font-size:12px;color:var(--gray);margin-top:5px;">
                    <i class="fas fa-info-circle"></i> Separate amenities with commas
                </div>
            </div>
            
            <!-- Password Change Section -->
            <div style="margin:25px 0;border-top:2px solid #edf2f7;padding-top:20px;">
                <h4 style="color:var(--primary);"><i class="fas fa-key"></i> Change Password</h4>
                <p style="font-size:13px;color:var(--gray);margin-bottom:15px;">Leave blank to keep current password</p>
                <div class="form-row">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password" minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                    </div>
                </div>
            </div>
            
            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                <a href="dashboard.php" class="btn" style="background:#e2e8f0;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Profile Preview -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-id-card"></i> Hotel Preview</h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
            <div><strong>Hotel Name:</strong> <?php echo htmlspecialchars($profile['hotel_name']); ?></div>
            <div><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></div>
            <div><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
            <div><strong>Location:</strong> <?php echo htmlspecialchars($profile['location'] ?: 'Not specified'); ?></div>
            <div><strong>Rating:</strong> <?php echo str_repeat('★', $profile['stars']); ?></div>
            <div><strong>Rooms:</strong> <?php echo $profile['room_count'] ?: 'Not specified'; ?></div>
            <div><strong>Price/Night:</strong> <?php echo $profile['price_per_night'] ? formatCurrency($profile['price_per_night']) : 'Not specified'; ?></div>
            <div><strong>Check-in:</strong> <?php echo htmlspecialchars($profile['check_in_time'] ?: 'Not specified'); ?></div>
            <div><strong>Check-out:</strong> <?php echo htmlspecialchars($profile['check_out_time'] ?: 'Not specified'); ?></div>
        </div>
        <?php if ($profile['amenities']): ?>
            <div style="margin-top:10px;"><strong>Amenities:</strong> <?php echo htmlspecialchars($profile['amenities']); ?></div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>