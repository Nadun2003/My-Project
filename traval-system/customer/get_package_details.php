<?php
// get_package_details.php
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid package ID']);
    exit;
}

try {
    // Use prepared statement
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND status = 'active'");
    $stmt->execute([$id]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }
    
    // Return package data
    echo json_encode([
        'success' => true,
        'id' => $package['id'],
        'name' => $package['name'],
        'destination' => $package['destination'],
        'price' => $package['price'],
        'package_code' => $package['package_code'] ?? '',
        'category' => $package['category'] ?? 'General',
        'difficulty_level' => $package['difficulty_level'] ?? 'Easy',
        'duration_days' => $package['duration_days'] ?? 0,
        'duration_nights' => $package['duration_nights'] ?? 0,
        'max_people' => $package['max_people'] ?? 0,
        'image_url' => $package['image_url'] ?? '',
        'description' => $package['description'] ?? '',
        'included_services' => $package['included_services'] ?? '',
        'excluded_services' => $package['excluded_services'] ?? '',
        'highlights' => $package['highlights'] ?? '',
        'meeting_point' => $package['meeting_point'] ?? '',
        'best_season' => $package['best_season'] ?? ''
    ]);
    
} catch (PDOException $e) {
    error_log('get_package_details.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>