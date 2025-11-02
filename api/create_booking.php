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
$teacher_id = (int)($_POST['teacher_id'] ?? 0);
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$duration = (int)($_POST['duration'] ?? 60);
$notes = trim($_POST['notes'] ?? '');

// Валидация
if (!$teacher_id || !$date || !$time) {
    echo json_encode(['error' => 'Заполните все обязательные поля']);
    exit;
}

// Проверяем что дата в будущем
if (strtotime($date) < strtotime('today')) {
    echo json_encode(['error' => 'Выберите будущую дату']);
    exit;
}

try {
    // Получаем цену репетитора
    $stmt = $pdo->prepare("SELECT hourly_rate FROM teacher_profiles WHERE user_id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        echo json_encode(['error' => 'Репетитор не найден']);
        exit;
    }
    
    // Рассчитываем цену
    $price = $teacher['hourly_rate'] * ($duration / 60);
    
    // Рассчитываем время окончания
    $end_time = date('H:i:s', strtotime($time) + ($duration * 60));
    
    // Создаем бронирование
    $stmt = $pdo->prepare("
        INSERT INTO bookings 
        (student_id, teacher_id, booking_date, start_time, end_time, price, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $user_id,
        $teacher_id,
        $date,
        $time,
        $end_time,
        $price,
        $notes
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // Создаем уведомление для репетитора
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, type, title, message, link) 
        VALUES (?, 'booking', 'Новая запись на урок', 'У вас новая запись на урок', '/calendar.php')
    ");
    $stmt->execute([$teacher_id]);
    
    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'price' => $price
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных']);
}
?>
