<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$booking_id = $_GET['booking_id'] ?? 0;
$vehicle_booking_id = $_GET['vehicle_booking_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// ============================================
// GET VEHICLE BOOKING DATA - FULL DETAILS
// ============================================
if ($vehicle_booking_id > 0) {
    $sql = "SELECT vb.*, 
                   b.id as main_booking_id,
                   b.booking_no,
                   b.customer_id,
                   b.start_date,
                   b.end_date,
                   b.adults,
                   b.children,
                   b.total_amount as booking_total,
                   b.paid_amount,
                   b.payment_status,
                   b.special_requests,
                   b.status as booking_status,
                   b.created_at as booking_created,
                   u.name as customer_name,
                   u.email as customer_email,
                   u.phone as customer_phone,
                   p.name as package_name,
                   p.destination,
                   p.duration_days,
                   p.duration_nights,
                   p.category as package_category,
                   p.difficulty_level,
                   p.description as package_description,
                   p.included_services,
                   p.highlights,
                   p.meeting_point,
                   p.best_season,
                   g.name as guide_name,
                   g.email as guide_email,
                   g.phone as guide_phone,
                   hp.hotel_name,
                   hp.location as hotel_location,
                   hp.stars as hotel_stars,
                   hp.room_count as hotel_rooms,
                   hp.price_per_night as hotel_price,
                   hp.amenities as hotel_amenities,
                   hp.check_in_time,
                   hp.check_out_time,
                   v.vehicle_code,
                   v.vehicle_name,
                   v.vehicle_type as vehicle_category,
                   v.brand,
                   v.model,
                   v.year,
                   v.color,
                   v.seat_count,
                   v.price_per_day,
                   v.price_per_km,
                   v.image_url,
                   v.description as vehicle_description,
                   v.features as vehicle_features,
                   v.location as vehicle_location
            FROM vehicle_bookings vb
            LEFT JOIN bookings b ON vb.booking_id = b.id
            LEFT JOIN users u ON b.customer_id = u.id
            LEFT JOIN packages p ON b.package_id = p.id
            LEFT JOIN users g ON b.guide_id = g.id
            LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id
            LEFT JOIN vehicles v ON vb.vehicle_id = v.id
            WHERE vb.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$vehicle_booking_id]);
    $data = $stmt->fetch();
    
} elseif ($booking_id > 0) {
    $sql = "SELECT vb.*, 
                   b.id as main_booking_id,
                   b.booking_no,
                   b.customer_id,
                   b.start_date,
                   b.end_date,
                   b.adults,
                   b.children,
                   b.total_amount as booking_total,
                   b.paid_amount,
                   b.payment_status,
                   b.special_requests,
                   b.status as booking_status,
                   b.created_at as booking_created,
                   u.name as customer_name,
                   u.email as customer_email,
                   u.phone as customer_phone,
                   p.name as package_name,
                   p.destination,
                   p.duration_days,
                   p.duration_nights,
                   p.category as package_category,
                   p.difficulty_level,
                   p.description as package_description,
                   p.included_services,
                   p.highlights,
                   p.meeting_point,
                   p.best_season,
                   g.name as guide_name,
                   g.email as guide_email,
                   g.phone as guide_phone,
                   hp.hotel_name,
                   hp.location as hotel_location,
                   hp.stars as hotel_stars,
                   hp.room_count as hotel_rooms,
                   hp.price_per_night as hotel_price,
                   hp.amenities as hotel_amenities,
                   hp.check_in_time,
                   hp.check_out_time,
                   v.vehicle_code,
                   v.vehicle_name,
                   v.vehicle_type as vehicle_category,
                   v.brand,
                   v.model,
                   v.year,
                   v.color,
                   v.seat_count,
                   v.price_per_day,
                   v.price_per_km,
                   v.image_url,
                   v.description as vehicle_description,
                   v.features as vehicle_features,
                   v.location as vehicle_location
            FROM vehicle_bookings vb
            LEFT JOIN bookings b ON vb.booking_id = b.id
            LEFT JOIN users u ON b.customer_id = u.id
            LEFT JOIN packages p ON b.package_id = p.id
            LEFT JOIN users g ON b.guide_id = g.id
            LEFT JOIN hotel_profiles hp ON b.hotel_id = hp.id
            LEFT JOIN vehicles v ON vb.vehicle_id = v.id
            WHERE vb.booking_id = ?
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$booking_id]);
    $data = $stmt->fetch();
} else {
    die("Invalid request");
}

if (!$data) {
    die("Vehicle booking not found");
}

// ============================================
// CHECK ACCESS
// ============================================
$has_access = false;
if ($role === 'admin') {
    $has_access = true;
} elseif ($role === 'customer' && $data['customer_id'] == $user_id) {
    $has_access = true;
}

if (!$has_access) {
    die("Access denied");
}

header('Content-Type: text/html');
header('Content-Disposition: inline; filename="vehicle_booking_' . $data['booking_no'] . '.html"');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vehicle Booking Report - <?php echo htmlspecialchars($data['booking_no']); ?></title>
    <style>
        /* ============================================ */
        /* A4 PAPER SETUP */
        /* ============================================ */
        @page {
            size: A4;
            margin: 0;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif; 
            background: #e8edf2; 
            color: #2c3e50;
            line-height: 1.4;
            font-size: 11px;
            padding: 20px;
        }
        
        /* A4 PAPER */
        .a4-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }
        
        /* ============================================ */
        /* PRINT BUTTON */
        /* ============================================ */
        .print-toolbar {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            max-width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-print {
            background: linear-gradient(135deg, #0a5c4a, #0a8c6a);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(10, 92, 74, 0.3);
            transition: all 0.3s ease;
            font-family: inherit;
            margin: 0 5px;
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(10, 92, 74, 0.4);
        }
        .btn-print.blue {
            background: linear-gradient(135deg, #3498db, #2980b9);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        /* ============================================ */
        /* HEADER BANNER */
        /* ============================================ */
        .page-header {
            background: linear-gradient(135deg, #0a5c4a 0%, #0a8c6a 100%);
            color: #ffffff;
            padding: 20px 25px 15px 25px;
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: 30%;
            width: 120px;
            height: 120px;
            background: rgba(245,166,35,0.15);
            border-radius: 50%;
        }
        .header-top {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .brand-block {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-icon {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(10px);
        }
        .brand-text h1 {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.3px;
        }
        .brand-text h1 span { color: #f5a623; }
        .brand-text p {
            font-size: 10px;
            opacity: 0.85;
            margin-top: 2px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .report-badge {
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .header-bottom {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.15);
        }
        .booking-ref-big {
            font-size: 11px;
            opacity: 0.9;
        }
        .booking-ref-big strong {
            display: block;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .header-meta {
            text-align: right;
            font-size: 10px;
            opacity: 0.9;
        }
        .header-meta strong {
            display: block;
            font-size: 11px;
            color: #f5a623;
            font-weight: 700;
        }
        
        /* ============================================ */
        /* STATUS STRIP */
        /* ============================================ */
        .status-strip {
            background: #f0f7f5;
            padding: 10px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0a5c4a;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
        }
        .status-item .label {
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .status-item .value {
            color: #0a5c4a;
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-success { background: #d4f5e6; color: #0a7a42; }
        .badge-warning { background: #fff3cd; color: #b9770e; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-info { background: #d1ecf1; color: #1a5276; }
        
        /* ============================================ */
        /* CONTENT AREA */
        /* ============================================ */
        .page-content {
            padding: 18px 25px 20px 25px;
        }
        
        /* ============================================ */
        /* SECTION */
        /* ============================================ */
        .section {
            margin-bottom: 14px;
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(90deg, #0a5c4a 0%, #0a8c6a 60%, transparent 100%);
            color: #ffffff;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .section-header .icon {
            width: 20px;
            height: 20px;
            background: rgba(255,255,255,0.25);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }
        
        /* ============================================ */
        /* VEHICLE HERO CARD */
        /* ============================================ */
        .vehicle-hero {
            display: flex;
            gap: 14px;
            background: linear-gradient(135deg, #f0f9ff 0%, #ebf5fb 100%);
            border: 1.5px solid #3498db;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 14px;
            position: relative;
            overflow: hidden;
        }
        .vehicle-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #3498db;
        }
        .vehicle-image-box {
            width: 110px;
            height: 90px;
            border-radius: 8px;
            overflow: hidden;
            background: #d6eaf8;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.2);
        }
        .vehicle-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .vehicle-image-box .no-image {
            font-size: 40px;
            color: #3498db;
        }
        .vehicle-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .vehicle-name {
            font-size: 16px;
            font-weight: 800;
            color: #1a5276;
            margin-bottom: 3px;
            letter-spacing: -0.3px;
        }
        .vehicle-subtitle {
            font-size: 10px;
            color: #5d6d7e;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .vehicle-tags {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .v-tag {
            padding: 3px 9px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            background: #ffffff;
            color: #2980b9;
            border: 1px solid #aed6f1;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .v-tag.gold {
            background: #fef9e7;
            color: #b9770e;
            border-color: #fad7a0;
        }
        .v-tag.green {
            background: #d4f5e6;
            color: #0a7a42;
            border-color: #a9dfbf;
        }
        .vehicle-price-tag {
            position: absolute;
            top: 12px;
            right: 14px;
            background: linear-gradient(135deg, #27ae60, #1a7a3a);
            color: #ffffff;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            box-shadow: 0 3px 10px rgba(39, 174, 96, 0.3);
        }
        
        /* ============================================ */
        /* DETAILS TABLE */
        /* ============================================ */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #e0e7e9;
            border-radius: 6px;
            overflow: hidden;
        }
        .details-table td {
            padding: 7px 12px;
            border-bottom: 1px solid #e8edf0;
            font-size: 10.5px;
            vertical-align: top;
        }
        .details-table td:first-child {
            color: #7f8c8d;
            font-weight: 600;
            width: 32%;
            background: #fafcfc;
        }
        .details-table td:last-child {
            color: #2c3e50;
            font-weight: 700;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .details-table .highlight {
            color: #0a5c4a !important;
            font-weight: 800 !important;
        }
        .details-table .price {
            color: #27ae60 !important;
            font-weight: 800 !important;
        }
        
        /* ============================================ */
        /* GRID 2 COLUMN */
        /* ============================================ */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }
        
        /* ============================================ */
        /* INFO CARD */
        /* ============================================ */
        .info-card {
            background: #ffffff;
            border: 1px solid #e0e7e9;
            border-radius: 8px;
            overflow: hidden;
        }
        .info-card-header {
            background: #f0f7f5;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: 800;
            color: #0a5c4a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #0a5c4a;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .info-card-body {
            padding: 10px 12px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 10.5px;
            border-bottom: 1px dashed #f0f2f5;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-row .label {
            color: #7f8c8d;
            font-weight: 600;
        }
        .info-row .value {
            color: #2c3e50;
            font-weight: 700;
            text-align: right;
            max-width: 60%;
        }
        
        /* ============================================ */
        /* PRICE BOX */
        /* ============================================ */
        .price-breakdown {
            background: #f8fafc;
            border: 1px solid #e0e7e9;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed #dee2e6;
            font-size: 10.5px;
        }
        .price-row:last-child {
            border-bottom: none;
        }
        .price-row .label {
            color: #7f8c8d;
            font-weight: 600;
        }
        .price-row .value {
            color: #2c3e50;
            font-weight: 700;
        }
        
        /* ============================================ */
        /* TOTAL BOX */
        /* ============================================ */
        .total-box {
            background: linear-gradient(135deg, #0a5c4a 0%, #0a8c6a 100%);
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            box-shadow: 0 4px 15px rgba(10, 92, 74, 0.25);
            position: relative;
            overflow: hidden;
        }
        .total-box::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .total-box-content {
            position: relative;
            z-index: 1;
        }
        .total-label {
            font-size: 10px;
            opacity: 0.9;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .total-amount {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .total-icon {
            font-size: 36px;
            opacity: 0.3;
            position: relative;
            z-index: 1;
        }
        
        /* ============================================ */
        /* FEATURES PILLS */
        /* ============================================ */
        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 8px 0;
        }
        .feature-pill {
            background: #fef9e7;
            color: #b9770e;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9.5px;
            font-weight: 700;
            border: 1px solid #fad7a0;
        }
        
        /* ============================================ */
        /* DESCRIPTION BOX */
        /* ============================================ */
        .desc-box {
            background: #f8fafc;
            border-left: 3px solid #0a5c4a;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 10.5px;
            color: #4a5568;
            line-height: 1.6;
        }
        .desc-box strong {
            color: #0a5c4a;
            display: block;
            margin-bottom: 3px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* ============================================ */
        /* NOTES BOX */
        /* ============================================ */
        .notes-box {
            background: #fff9e6;
            border-left: 3px solid #f5a623;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 10.5px;
            color: #7d6608;
            line-height: 1.6;
        }
        .notes-box strong {
            color: #b9770e;
            display: block;
            margin-bottom: 3px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* ============================================ */
        /* PAYMENT BOX */
        /* ============================================ */
        .payment-box {
            background: #f8fafc;
            border: 1px solid #e0e7e9;
            border-radius: 8px;
            padding: 10px 14px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 10.5px;
            border-bottom: 1px dashed #dee2e6;
        }
        .payment-row:last-child {
            border-bottom: none;
        }
        .payment-row.total {
            background: #0a5c4a;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 6px;
            font-weight: 800;
            font-size: 11px;
        }
        .payment-row.balance {
            background: #fff3cd;
            color: #856404;
            padding: 7px 12px;
            border-radius: 6px;
            margin-top: 4px;
            font-weight: 700;
        }
        .payment-row .label { color: #7f8c8d; font-weight: 600; }
        .payment-row .value { color: #2c3e50; font-weight: 700; }
        .payment-row.total .label,
        .payment-row.total .value { color: #ffffff; }
        
        /* ============================================ */
        /* TIMELINE */
        /* ============================================ */
        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 10px 0;
            margin-bottom: 10px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e0e7e9;
            z-index: 0;
        }
        .timeline-step {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
        }
        .timeline-dot {
            width: 26px;
            height: 26px;
            background: #ffffff;
            border: 2px solid #e0e7e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 6px;
            font-size: 11px;
            color: #95a5a6;
        }
        .timeline-step.active .timeline-dot {
            background: #0a5c4a;
            border-color: #0a5c4a;
            color: #ffffff;
        }
        .timeline-step.completed .timeline-dot {
            background: #27ae60;
            border-color: #27ae60;
            color: #ffffff;
        }
        .timeline-label {
            font-size: 9px;
            font-weight: 700;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .timeline-step.active .timeline-label {
            color: #0a5c4a;
        }
        
        /* ============================================ */
        /* SIGNATURE */
        /* ============================================ */
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #dee2e6;
        }
        .signature-box {
            text-align: center;
            width: 40%;
        }
        .signature-line {
            border-bottom: 1px solid #2c3e50;
            margin-bottom: 4px;
            height: 30px;
        }
        .signature-label {
            font-size: 9px;
            color: #7f8c8d;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* ============================================ */
        /* PAGE FOOTER */
        /* ============================================ */
        .page-footer {
            background: #2c3e50;
            color: #ffffff;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9.5px;
        }
        .footer-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .footer-left .brand-mini {
            font-weight: 800;
            font-size: 12px;
        }
        .footer-left .brand-mini span { color: #f5a623; }
        .footer-right {
            text-align: right;
            opacity: 0.85;
        }
        
        /* ============================================ */
        /* WATERMARK */
        /* ============================================ */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 100px;
            font-weight: 900;
            color: rgba(10, 92, 74, 0.03);
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
            letter-spacing: 10px;
        }
        
        /* ============================================ */
        /* PRINT */
        /* ============================================ */
        @media print {
            body { 
                background: #ffffff; 
                padding: 0; 
                font-size: 11px;
            }
            .a4-page {
                width: 210mm;
                min-height: 297mm;
                box-shadow: none;
                margin: 0;
                page-break-after: avoid;
            }
            .print-toolbar { display: none; }
            .page-header { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
            .section-header {
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .total-box {
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .page-footer {
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .status-strip {
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .vehicle-price-tag {
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .section { break-inside: avoid; }
            .vehicle-hero { break-inside: avoid; }
            .grid-2 { break-inside: avoid; }
            .total-box { break-inside: avoid; }
        }
    </style>
</head>
<body>

    <!-- PRINT TOOLBAR -->
    <div class="print-toolbar">
        <button class="btn-print" onclick="window.print()">
            🖨️ Print / Save as PDF (A4)
        </button>
        <button class="btn-print blue" onclick="window.close()">
            ❌ Close
        </button>
    </div>

    <!-- ============================================ -->
    <!-- A4 PAGE -->
    <!-- ============================================ -->
    <div class="a4-page">
        
        <!-- WATERMARK -->
        <div class="watermark">PARADISE</div>
        
        <!-- ============================================ -->
        <!-- PAGE HEADER -->
        <!-- ============================================ -->
        <div class="page-header">
            <div class="header-top">
                <div class="brand-block">
                    <div class="brand-icon">🏝️</div>
                    <div class="brand-text">
                        <h1>Paradise<span>Travel</span></h1>
                        <p>Sri Lanka Tour Management</p>
                    </div>
                </div>
                <div class="report-badge">
                    📄 Vehicle Booking Report
                </div>
            </div>
            <div class="header-bottom">
                <div class="booking-ref-big">
                    Booking Reference
                    <strong><?php echo htmlspecialchars($data['booking_no']); ?></strong>
                </div>
                <div class="header-meta">
                    Generated On
                    <strong><?php echo date('F d, Y - H:i A'); ?></strong>
                </div>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- STATUS STRIP -->
        <!-- ============================================ -->
        <div class="status-strip">
            <div class="status-item">
                <span class="label">Booking Status:</span>
                <span class="badge badge-<?php 
                    echo $data['booking_status'] == 'confirmed' ? 'success' : 
                        ($data['booking_status'] == 'pending' ? 'warning' : 
                        ($data['booking_status'] == 'completed' ? 'info' : 'danger')); 
                ?>">
                    <?php echo ucfirst($data['booking_status']); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="label">Vehicle Status:</span>
                <span class="badge badge-<?php 
                    echo $data['status'] == 'confirmed' ? 'success' : 
                        ($data['status'] == 'pending' ? 'warning' : 
                        ($data['status'] == 'completed' ? 'info' : 'danger')); 
                ?>">
                    <?php echo ucfirst($data['status']); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="label">Payment:</span>
                <span class="badge badge-<?php echo $data['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($data['payment_status'] ?? 'unpaid'); ?>
                </span>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- PAGE CONTENT -->
        <!-- ============================================ -->
        <div class="page-content">
            
            <!-- ============================================ -->
            <!-- SECTION 1: VEHICLE HERO -->
            <!-- ============================================ -->
            <div class="section-header">
                <div class="icon">🚗</div>
                Vehicle Information
            </div>
            
            <div class="vehicle-hero">
                <div class="vehicle-image-box">
                    <?php if (!empty($data['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($data['image_url']); ?>" 
                             alt="Vehicle"
                             onerror="this.parentElement.innerHTML='<div class=\'no-image\'>🚗</div>'">
                    <?php else: ?>
                        <div class="no-image">🚗</div>
                    <?php endif; ?>
                </div>
                <div class="vehicle-info">
                    <div class="vehicle-name">
                        <?php echo htmlspecialchars($data['vehicle_name'] ?: ($data['vehicle_category'] ?: 'Vehicle')); ?>
                    </div>
                    <div class="vehicle-subtitle">
                        <?php 
                        $subtitle = trim(($data['brand'] ?? '') . ' ' . ($data['model'] ?? '') . ' ' . ($data['year'] ?? ''));
                        echo htmlspecialchars($subtitle ?: 'Standard Vehicle');
                        ?>
                    </div>
                    <div class="vehicle-tags">
                        <?php if (!empty($data['vehicle_code'])): ?>
                            <span class="v-tag">#<?php echo htmlspecialchars($data['vehicle_code']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($data['vehicle_category'])): ?>
                            <span class="v-tag"><?php echo htmlspecialchars($data['vehicle_category']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($data['seat_count'])): ?>
                            <span class="v-tag green">👥 <?php echo htmlspecialchars($data['seat_count']); ?> Seats</span>
                        <?php endif; ?>
                        <?php if (!empty($data['color'])): ?>
                            <span class="v-tag gold">🎨 <?php echo htmlspecialchars($data['color']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="vehicle-price-tag">
                    LKR <?php echo number_format($data['total_price'] ?? 0, 2); ?>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- SECTION 2: VEHICLE SPECIFICATIONS -->
            <!-- ============================================ -->
            <div class="grid-2">
                
                <!-- Vehicle Details Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        🔧 Vehicle Specifications
                    </div>
                    <div class="info-card-body">
                        <?php if (!empty($data['vehicle_code'])): ?>
                        <div class="info-row">
                            <span class="label">Vehicle Code</span>
                            <span class="value"><?php echo htmlspecialchars($data['vehicle_code']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['vehicle_category'])): ?>
                        <div class="info-row">
                            <span class="label">Type</span>
                            <span class="value"><?php echo htmlspecialchars($data['vehicle_category']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['brand'])): ?>
                        <div class="info-row">
                            <span class="label">Brand</span>
                            <span class="value"><?php echo htmlspecialchars($data['brand']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['model'])): ?>
                        <div class="info-row">
                            <span class="label">Model</span>
                            <span class="value"><?php echo htmlspecialchars($data['model']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['year'])): ?>
                        <div class="info-row">
                            <span class="label">Year</span>
                            <span class="value"><?php echo htmlspecialchars($data['year']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['color'])): ?>
                        <div class="info-row">
                            <span class="label">Color</span>
                            <span class="value"><?php echo htmlspecialchars($data['color']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['seat_count'])): ?>
                        <div class="info-row">
                            <span class="label">Seat Capacity</span>
                            <span class="value"><?php echo htmlspecialchars($data['seat_count']); ?> Seats</span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['vehicle_location'])): ?>
                        <div class="info-row">
                            <span class="label">Base Location</span>
                            <span class="value"><?php echo htmlspecialchars($data['vehicle_location']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Driver Information Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        👨‍✈️ Driver Information
                    </div>
                    <div class="info-card-body">
                        <?php if (!empty($data['driver_name'])): ?>
                        <div class="info-row">
                            <span class="label">Driver Name</span>
                            <span class="value"><?php echo htmlspecialchars($data['driver_name']); ?></span>
                        </div>
                        <?php else: ?>
                        <div class="info-row">
                            <span class="label">Driver Name</span>
                            <span class="value">Will be assigned</span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['driver_phone'])): ?>
                        <div class="info-row">
                            <span class="label">Contact</span>
                            <span class="value highlight"><?php echo htmlspecialchars($data['driver_phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['driver_license'])): ?>
                        <div class="info-row">
                            <span class="label">License No</span>
                            <span class="value"><?php echo htmlspecialchars($data['driver_license']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="label">Vehicle Status</span>
                            <span class="value">
                                <span class="badge badge-<?php 
                                    echo $data['status'] == 'confirmed' ? 'success' : 
                                        ($data['status'] == 'pending' ? 'warning' : 
                                        ($data['status'] == 'completed' ? 'info' : 'danger')); 
                                ?>">
                                    <?php echo ucfirst($data['status']); ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- ============================================ -->
            <!-- SECTION 3: TRIP DETAILS -->
            <!-- ============================================ -->
            <div class="section-header">
                <div class="icon">🗺️</div>
                Trip & Journey Details
            </div>
            
            <div class="grid-2">
                
                <!-- Pickup Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        📍 Pickup Information
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Pickup Location</span>
                            <span class="value"><?php echo htmlspecialchars($data['pickup_location'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Pickup Date</span>
                            <span class="value"><?php echo !empty($data['pickup_date']) ? date('F d, Y', strtotime($data['pickup_date'])) : 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Pickup Time</span>
                            <span class="value"><?php echo htmlspecialchars($data['pickup_time'] ?: 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Dropoff Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        🏁 Dropoff Information
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Dropoff Location</span>
                            <span class="value"><?php echo htmlspecialchars($data['dropoff_location'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Duration</span>
                            <span class="value highlight"><?php echo htmlspecialchars($data['days'] ?? 0); ?> Day(s)</span>
                        </div>
                        <?php if (!empty($data['dropoff_date'])): ?>
                        <div class="info-row">
                            <span class="label">Dropoff Date</span>
                            <span class="value"><?php echo date('F d, Y', strtotime($data['dropoff_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
            
            <!-- ============================================ -->
            <!-- SECTION 4: PACKAGE DETAILS -->
            <!-- ============================================ -->
            <?php if (!empty($data['package_name'])): ?>
            <div class="section-header">
                <div class="icon">📦</div>
                Package Information
            </div>
            
            <div class="grid-3">
                <div class="info-card">
                    <div class="info-card-header">📦 Package</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Name</span>
                            <span class="value"><?php echo htmlspecialchars($data['package_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Destination</span>
                            <span class="value"><?php echo htmlspecialchars($data['destination'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Category</span>
                            <span class="value"><?php echo htmlspecialchars($data['package_category'] ?? 'General'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-header">⏱️ Duration</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Days</span>
                            <span class="value"><?php echo htmlspecialchars($data['duration_days'] ?? 0); ?> Days</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Nights</span>
                            <span class="value"><?php echo htmlspecialchars($data['duration_nights'] ?? 0); ?> Nights</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Difficulty</span>
                            <span class="value"><?php echo htmlspecialchars($data['difficulty_level'] ?? 'Easy'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-header">👥 Travelers</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Adults</span>
                            <span class="value"><?php echo htmlspecialchars($data['adults'] ?? 0); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Children</span>
                            <span class="value"><?php echo htmlspecialchars($data['children'] ?? 0); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Total</span>
                            <span class="value"><?php echo ($data['adults'] ?? 0) + ($data['children'] ?? 0); ?> Persons</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ============================================ -->
            <!-- SECTION 5: GUIDE & HOTEL -->
            <!-- ============================================ -->
            <?php if (!empty($data['guide_name']) || !empty($data['hotel_name'])): ?>
            <div class="grid-2">
                
                <?php if (!empty($data['guide_name'])): ?>
                <div class="info-card">
                    <div class="info-card-header">
                        🧭 Assigned Tour Guide
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Guide Name</span>
                            <span class="value"><?php echo htmlspecialchars($data['guide_name']); ?></span>
                        </div>
                        <?php if (!empty($data['guide_email'])): ?>
                        <div class="info-row">
                            <span class="label">Email</span>
                            <span class="value"><?php echo htmlspecialchars($data['guide_email']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['guide_phone'])): ?>
                        <div class="info-row">
                            <span class="label">Phone</span>
                            <span class="value"><?php echo htmlspecialchars($data['guide_phone']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['hotel_name'])): ?>
                <div class="info-card">
                    <div class="info-card-header">
                        🏨 Accommodation
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Hotel</span>
                            <span class="value"><?php echo htmlspecialchars($data['hotel_name']); ?></span>
                        </div>
                        <?php if (!empty($data['hotel_location'])): ?>
                        <div class="info-row">
                            <span class="label">Location</span>
                            <span class="value"><?php echo htmlspecialchars($data['hotel_location']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($data['hotel_stars'])): ?>
                        <div class="info-row">
                            <span class="label">Rating</span>
                            <span class="value"><?php echo str_repeat('★', $data['hotel_stars']); ?> (<?php echo $data['hotel_stars']; ?> Star)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            <?php endif; ?>
            
            <!-- ============================================ -->
            <!-- SECTION 6: FEATURES -->
            <!-- ============================================ -->
            <?php if (!empty($data['vehicle_features'])): ?>
            <div class="section-header">
                <div class="icon">✨</div>
                Vehicle Features & Amenities
            </div>
            <div class="features-list">
                <?php 
                $features = explode(',', $data['vehicle_features']);
                foreach ($features as $feature): 
                    $feature = trim($feature);
                    if (!empty($feature)):
                ?>
                    <span class="feature-pill">✨ <?php echo htmlspecialchars($feature); ?></span>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
            <?php endif; ?>
            
            <!-- ============================================ -->
            <!-- SECTION 7: PRICE BREAKDOWN -->
            <!-- ============================================ -->
            <div class="section-header">
                <div class="icon">💰</div>
                Price Breakdown & Payment
            </div>
            
            <div class="grid-2">
                
                <div class="info-card">
                    <div class="info-card-header">
                        🧾 Cost Calculation
                    </div>
                    <div class="info-card-body">
                        <?php 
                        $rate_per_day = $data['price_per_day'] ?? 0;
                        $days_count = $data['days'] ?? 0;
                        $day_total = $rate_per_day * $days_count;
                        $rate_per_km = $data['price_per_km'] ?? 0;
                        ?>
                        <?php if ($rate_per_day > 0): ?>
                        <div class="info-row">
                            <span class="label">Rate Per Day</span>
                            <span class="value">LKR <?php echo number_format($rate_per_day, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($days_count > 0): ?>
                        <div class="info-row">
                            <span class="label">Number of Days</span>
                            <span class="value">× <?php echo $days_count; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($day_total > 0): ?>
                        <div class="info-row">
                            <span class="label">Day Charge</span>
                            <span class="value">LKR <?php echo number_format($day_total, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($rate_per_km > 0): ?>
                        <div class="info-row">
                            <span class="label">Rate Per KM</span>
                            <span class="value">LKR <?php echo number_format($rate_per_km, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row" style="border-top: 2px solid #0a5c4a; margin-top: 5px; padding-top: 8px;">
                            <span class="label" style="font-weight: 800; color: #0a5c4a;">Vehicle Total</span>
                            <span class="value price" style="font-size: 13px;">LKR <?php echo number_format($data['total_price'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-header">
                        💳 Payment Summary
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Booking Total</span>
                            <span class="value">LKR <?php echo number_format($data['booking_total'] ?? 0, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Paid Amount</span>
                            <span class="value" style="color: #27ae60;">LKR <?php echo number_format($data['paid_amount'] ?? 0, 2); ?></span>
                        </div>
                        <div class="info-row" style="background: #fff3cd; padding: 6px 8px; border-radius: 5px; margin-top: 5px;">
                            <span class="label" style="color: #856404; font-weight: 800;">Balance Due</span>
                            <span class="value" style="color: #856404;">LKR <?php echo number_format(($data['booking_total'] ?? 0) - ($data['paid_amount'] ?? 0), 2); ?></span>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- ============================================ -->
            <!-- SECTION 8: CUSTOMER INFO -->
            <!-- ============================================ -->
            <?php if (!empty($data['customer_name'])): ?>
            <div class="section-header">
                <div class="icon">👤</div>
                Customer Information
            </div>
            
            <div class="grid-2">
                <div class="info-card">
                    <div class="info-card-header">👤 Customer Details</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Name</span>
                            <span class="value"><?php echo htmlspecialchars($data['customer_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Email</span>
                            <span class="value"><?php echo htmlspecialchars($data['customer_email'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Phone</span>
                            <span class="value"><?php echo htmlspecialchars($data['customer_phone'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-header">📅 Booking Dates</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Booking Created</span>
                            <span class="value"><?php echo !empty($data['booking_created']) ? date('M d, Y', strtotime($data['booking_created'])) : 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Trip Start</span>
                            <span class="value"><?php echo !empty($data['start_date']) ? date('M d, Y', strtotime($data['start_date'])) : 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Trip End</span>
                            <span class="value"><?php echo !empty($data['end_date']) ? date('M d, Y', strtotime($data['end_date'])) : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ============================================ -->
            <!-- SECTION 9: DESCRIPTION & NOTES -->
            <!-- ============================================ -->
            <?php if (!empty($data['vehicle_description']) || !empty($data['notes'])): ?>
            <div class="grid-2">
                
                <?php if (!empty($data['vehicle_description'])): ?>
                <div class="desc-box">
                    <strong>📝 Vehicle Description</strong>
                    <?php echo nl2br(htmlspecialchars(substr($data['vehicle_description'], 0, 250))); ?>
                    <?php if (strlen($data['vehicle_description']) > 250): ?>...<?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['notes'])): ?>
                <div class="notes-box">
                    <strong>📋 Special Instructions</strong>
                    <?php echo nl2br(htmlspecialchars(substr($data['notes'], 0, 250))); ?>
                    <?php if (strlen($data['notes']) > 250): ?>...<?php endif; ?>
                </div>
                <?php endif; ?>
                
            </div>
            <?php endif; ?>
            
            <!-- ============================================ -->
            <!-- SECTION 10: GRAND TOTAL -->
            <!-- ============================================ -->
            <div class="total-box">
                <div class="total-box-content">
                    <div class="total-label">🚗 Total Vehicle Cost</div>
                    <div class="total-amount">
                        LKR <?php echo number_format($data['total_price'] ?? 0, 2); ?>
                    </div>
                </div>
                <div class="total-icon">💵</div>
            </div>
            
            <!-- ============================================ -->
            <!-- SIGNATURE AREA -->
            <!-- ============================================ -->
            <div class="signature-area">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Customer Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Authorized Signature</div>
                </div>
            </div>
            
        </div>
        
        <!-- ============================================ -->
        <!-- PAGE FOOTER -->
        <!-- ============================================ -->
        <div class="page-footer">
            <div class="footer-left">
                <span class="brand-mini">🏝️ Paradise<span>Travel</span></span>
                <span>| Sri Lanka Tour Management System</span>
            </div>
            <div class="footer-right">
                📧 info@paradisetravel.lk &nbsp;|&nbsp; 📞 +94 77 123 4567
                <br>
                <span style="opacity: 0.7;">© <?php echo date('Y'); ?> Paradise Island Travel. All rights reserved.</span>
            </div>
        </div>
        
    </div>

</body>
</html>