<?php
require_once '../config/database.php';
checkAccess('customer');

$page_title = 'My Profile';
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get customer profile
$stmt = $pdo->prepare("SELECT * FROM customer_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// If no profile exists, create one
if (!$profile) {
    $stmt = $pdo->prepare("INSERT INTO customer_profiles (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $profile = ['nic' => '', 'address' => '', 'date_of_birth' => '', 'nationality' => ''];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $nic = trim($_POST['nic']);
    $address = trim($_POST['address']);
    $date_of_birth = $_POST['date_of_birth'];
    $nationality = trim($_POST['nationality']);
    
    // Update user
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $user_id]);
    
    // Update profile
    $stmt = $pdo->prepare("UPDATE customer_profiles SET nic = ?, address = ?, date_of_birth = ?, nationality = ? WHERE user_id = ?");
    $stmt->execute([$nic, $address, $date_of_birth, $nationality, $user_id]);
    
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
    
    // Update session name
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
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label>NIC</label>
                    <input type="text" name="nic" class="form-control" value="<?php echo htmlspecialchars($profile['nic']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($profile['date_of_birth']); ?>">
                </div>
                <div class="form-group">
                    <label>Nationality</label>
                    <input type="text" name="nationality" class="form-control" value="<?php echo htmlspecialchars($profile['nationality']); ?>" placeholder="Sri Lankan">
                </div>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($profile['address']); ?></textarea>
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

<?php include '../includes/footer.php'; ?>