<?php
require_once '../config/database.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$booking_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, start_date, adults, children, special_requests 
                       FROM bookings 
                       WHERE id = ? AND customer_id = ? AND status = 'pending'");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if ($booking) {
    echo json_encode([
        'success' => true,
        'id' => $booking['id'],
        'start_date' => $booking['start_date'],
        'adults' => $booking['adults'],
        'children' => $booking['children'],
        'special_requests' => $booking['special_requests']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}
?>