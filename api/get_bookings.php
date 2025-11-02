<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Получаем предстоящие уроки пользователя
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            u.name as teacher_name,
            u.avatar as teacher_avatar,
            tp.subject
        FROM bookings b
        LEFT JOIN users u ON b.teacher_id = u.id
        LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
        WHERE b.student_id = ?
        AND b.booking_date >= CURDATE()
        AND b.status IN ('pending', 'confirmed')
        ORDER BY b.booking_date ASC, b.start_time ASC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>
