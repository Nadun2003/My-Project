<?php
/**
 * ============================================
 * PARADISE ISLAND TRAVEL - DATABASE CONFIGURATION
 * Higher National Diploma in Information Technology
 * Project: Web-Based Travel Management System
 * ============================================
 */

// ============================================
// ERROR REPORTING & TIMEZONE (Development Mode)
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Colombo');

// ============================================
// DATABASE CONNECTION
// ============================================
$host = 'localhost';
$dbname = 'paradise_travel_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    // Professional error handling
    die("⚠️ Database Connection Failed: " . $e->getMessage());
}

// ============================================
// SESSION START
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// APPLICATION CONSTANTS
// ============================================
define('APP_NAME', 'Paradise Island Travel');
define('APP_URL', 'http://localhost/traval-system');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// ============================================
// CORE HELPER FUNCTIONS
// ============================================

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to a specific URL
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Set a toast/session notification
 * @param string $message
 * @param string $type (success|error|warning)
 */
function toast($message, $type = 'success') {
    $_SESSION['toast'] = ['message' => $message, 'type' => $type];
}

/**
 * Generate unique booking number
 * Format: TRV + YYYYMMDD + 3-digit random
 * @return string
 */
function generateBookingNo() {
    return 'TRV' . date('Ymd') . rand(100, 999);
}

/**
 * Generate unique transaction ID
 * Format: TXN + YYYYMMDD + 4-digit random
 * @return string
 */
function generateTransactionId() {
    return 'TXN' . date('Ymd') . rand(1000, 9999);
}

/**
 * Format currency in Sri Lankan Rupees
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return 'LKR ' . number_format($amount, 2);
}

/**
 * Get Bootstrap badge class based on status
 * @param string $status
 * @return string (badge class)
 */
function getStatusBadge($status) {
    $colors = [
        'pending'    => 'warning',
        'confirmed'  => 'success',
        'cancelled'  => 'danger',
        'completed'  => 'info',
        'active'     => 'success',
        'inactive'   => 'danger',
        'available'  => 'success',
        'busy'       => 'warning',
        'off'        => 'danger',
        'paid'       => 'success',
        'partial'    => 'warning'
    ];
    return $colors[$status] ?? 'secondary';
}

/**
 * Check if user has specific role access
 * @param string $required_role (admin|customer|guide|hotel)
 */
function checkAccess($required_role) {
    if (!isLoggedIn()) {
        redirect('../login.php');
    }
    if ($_SESSION['user_role'] !== $required_role) {
        redirect('../login.php');
    }
}

/**
 * Get user data by ID
 * @param int $user_id
 * @return array|false
 */
function getUserData($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// ============================================
// ADVANCED HELPER FUNCTIONS (New Additions)
// ============================================

/**
 * Sanitize input data (XSS Protection)
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get current user's role
 * @return string|null
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if current user has a specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return getUserRole() === $role;
}

/**
 * Check if current user is Admin
 * @return bool
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if current user is Customer
 * @return bool
 */
function isCustomer() {
    return hasRole('customer');
}

/**
 * Check if current user is Guide
 * @return bool
 */
function isGuide() {
    return hasRole('guide');
}

/**
 * Check if current user is Hotel
 * @return bool
 */
function isHotel() {
    return hasRole('hotel');
}

/**
 * Format date with custom format
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Get time ago string (e.g., "2 hours ago")
 * @param string $timestamp
 * @return string
 */
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;

    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "Yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}

/**
 * Get greeting based on current time
 * @return string
 */
function getGreeting() {
    $hour = date('H');
    if ($hour < 12) {
        return 'Good Morning 🌅';
    } elseif ($hour < 17) {
        return 'Good Afternoon ☀️';
    } elseif ($hour < 21) {
        return 'Good Evening 🌇';
    } else {
        return 'Good Night 🌙';
    }
}

/**
 * Generate CSRF Token for form protection
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user's profile image URL
 * @param int $user_id
 * @return string
 */
function getProfileImage($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    if ($result && !empty($result['profile_image'])) {
        return APP_URL . '/uploads/' . $result['profile_image'];
    }
    return APP_URL . '/assets/images/default-avatar.png';
}

/**
 * Get total count from any table (with optional where clause)
 * @param string $table
 * @param string $where (optional)
 * @param array $params (optional)
 * @return int
 */
function getCount($table, $where = '', $params = []) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM $table";
    if (!empty($where)) {
        $sql .= " WHERE $where";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Get current year for footer
 * @return string
 */
function getCurrentYear() {
    return date('Y');
}

/**
 * Debug function (development only)
 * @param mixed $data
 */
function debug($data) {
    echo '<pre style="background:#f4f4f4;padding:15px;border-radius:8px;font-size:13px;max-height:400px;overflow:auto;">';
    print_r($data);
    echo '</pre>';
}

// ============================================
// CSRF TOKEN INITIALIZATION
// ============================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>