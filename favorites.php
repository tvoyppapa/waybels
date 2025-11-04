<?php
/**
 * AQUM - API для избранного
 * AJAX endpoint для добавления/удаления преподавателей в избранное
 */

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

session_start();
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Метод не поддерживается');
}

if (!isset($_POST['toggle']) || !isset($_POST['teacher_id'])) {
    jsonError('Недостаточно данных');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError('Ошибка безопасности');
}

$user_id = $_SESSION['user_id'];
$teacher_id = (int)$_POST['teacher_id'];

// Проверяем, что teacher_id валиден
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_teacher = 1 AND is_verified_teacher = 1");
$stmt->execute([$teacher_id]);
if (!$stmt->fetch()) {
    jsonError('Преподаватель не найден');
}

try {
    // Проверяем, есть ли уже в избранном
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND teacher_id = ?");
    $stmt->execute([$user_id, $teacher_id]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Удаляем из избранного
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND teacher_id = ?");
        $stmt->execute([$user_id, $teacher_id]);
        $is_favorite = false;
    } else {
        // Добавляем в избранное
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, teacher_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $teacher_id]);
        $is_favorite = true;
    }
    
    jsonSuccess($is_favorite ? 'Добавлено в избранное' : 'Удалено из избранного', [
        'is_favorite' => $is_favorite
    ]);
    
} catch (PDOException $e) {
    logError("Favorites error: " . $e->getMessage());
    jsonError('Ошибка сервера');
}
