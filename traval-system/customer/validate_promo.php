<?php
// validate_promo.php - API for promo code validation
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$subtotal = isset($_GET['subtotal']) ? (float)$_GET['subtotal'] : 0;

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a promo code']);
    exit;
}

try {
    // Use prepared statement
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND status = 'active' AND valid_from <= CURDATE() AND valid_to >= CURDATE() AND (usage_limit IS NULL OR used_count < usage_limit)");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code']);
        exit;
    }
    
    if ($promo['min_order_amount'] > 0 && $subtotal < $promo['min_order_amount']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Minimum order amount is LKR ' . number_format($promo['min_order_amount'], 2)
        ]);
        exit;
    }
    
    $discount = 0;
    if ($promo['discount_type'] == 'percentage') {
        $discount = ($subtotal * $promo['discount_value']) / 100;
        if ($promo['max_discount'] > 0 && $discount > $promo['max_discount']) {
            $discount = $promo['max_discount'];
        }
    } else {
        $discount = $promo['discount_value'];
    }
    
    echo json_encode([
        'success' => true,
        'discount' => $discount,
        'message' => 'Promo code applied successfully',
        'code' => $promo['code'],
        'discount_type' => $promo['discount_type'],
        'discount_value' => $promo['discount_value']
    ]);
    
} catch (PDOException $e) {
    error_log('validate_promo.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('validate_promo.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>