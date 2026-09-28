<?php
require_once '../config/database.php';
checkAccess('guide');

$page_title = 'My Profile';
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get guide profile
$stmt = $pdo->prepare("SELECT * FROM guide_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// If no profile exists, create one
if (!$profile) {
    $stmt = $pdo->prepare("INSERT INTO guide_profiles (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $profile = [
        'experience_years' => 0,
        'languages' => '',
        'specialization' => '',
        'hourly_rate' => 0,
        'availability' => 'available'
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's just availability update
    if (isset($_POST['update_availability'])) {
        $stmt = $pdo->prepare("UPDATE guide_profiles SET availability = ? WHERE user_id = ?");
        $stmt->execute([$_POST['availability'], $user_id]);
        toast("Availability updated to " . ucfirst($_POST['availability']));
        redirect('dashboard.php');
    }
    
    // Full profile update
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $experience_years = $_POST['experience_years'];
    $languages = trim($_POST['languages']);
    $specialization = trim($_POST['specialization']);
    $hourly_rate = $_POST['hourly_rate'];
    $availability = $_POST['availability'];
    
    // Update user
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $user_id]);
    
    // Update profile
    $stmt = $pdo->prepare("UPDATE guide_profiles SET experience_years = ?, languages = ?, specialization = ?, hourly_rate = ?, availability = ? WHERE user_id = ?");
    $stmt->execute([$experience_years, $languages, $specialization, $hourly_rate, $availability, $user_id]);
    
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
    
    $_SESSION['user_name'] = $name;
    toast("Profile updated successfully!");
    redirect('profile.php');
}

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-edit"></i> Edit Profile</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <!-- Personal Information -->
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            
            <!-- Professional Information -->
            <div style="margin:25px 0;border-top:2px solid #edf2f7;padding-top:20px;">
                <h4 style="color:var(--primary);"><i class="fas fa-briefcase"></i> Professional Information</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Experience (Years)</label>
                        <input type="number" name="experience_years" class="form-control" value="<?php echo $profile['experience_years']; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Hourly Rate (LKR)</label>
                        <input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?php echo $profile['hourly_rate']; ?>" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Languages Spoken</label>
                    <input type="text" name="languages" class="form-control" value="<?php echo htmlspecialchars($profile['languages']); ?>" placeholder="English, Sinhala, Tamil">
                </div>
                
                <div class="form-group">
                    <label>Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($profile['specialization']); ?>" placeholder="Cultural, Wildlife, Adventure, etc.">
                </div>
                
                <div class="form-group">
                    <label>Availability Status</label>
                    <select name="availability" class="form-control">
                        <option value="available" <?php echo $profile['availability'] == 'available' ? 'selected' : ''; ?>>✅ Available</option>
                        <option value="busy" <?php echo $profile['availability'] == 'busy' ? 'selected' : ''; ?>>🔴 Busy</option>
                        <option value="off" <?php echo $profile['availability'] == 'off' ? 'selected' : ''; ?>>⛔ Off Duty</option>
                    </select>
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
        <h3><i class="fas fa-id-card"></i> Profile Preview</h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
            <div><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></div>
            <div><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></div>
            <div><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
            <div><strong>Experience:</strong> <?php echo $profile['experience_years']; ?> years</div>
            <div><strong>Languages:</strong> <?php echo htmlspecialchars($profile['languages'] ?: 'Not specified'); ?></div>
            <div><strong>Specialization:</strong> <?php echo htmlspecialchars($profile['specialization'] ?: 'Not specified'); ?></div>
            <div><strong>Hourly Rate:</strong> <?php echo formatCurrency($profile['hourly_rate']); ?></div>
            <div><strong>Status:</strong> <span class="badge badge-<?php echo getStatusBadge($profile['availability']); ?>"><?php echo ucfirst($profile['availability']); ?></span></div>
            <div><strong>Rating:</strong> <?php echo number_format($profile['rating'] ?? 0, 1); ?> ★ (<?php echo $profile['total_reviews'] ?? 0; ?> reviews)</div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>