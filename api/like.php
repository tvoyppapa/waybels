<?php
session_start();
header('Content-Type: application/json');

require_once '../config.php';
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$post_id = $data['post_id'] ?? null;
$action = $data['action'] ?? 'like';

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Post ID required']);
    exit;
}

try {
    if ($action === 'like') {
        // Добавляем лайк
        $stmt = $pdo->prepare("INSERT IGNORE INTO post_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$post_id, $user_id]);
        
        // Обновляем счетчик
        $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
    } else {
        // Убираем лайк
        $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        // Обновляем счетчик
        $stmt = $pdo->prepare("UPDATE posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?");
        $stmt->execute([$post_id]);
    }
    
    // Получаем новое количество лайков
    $stmt = $pdo->prepare("SELECT likes_count FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'likes_count' => $post['likes_count'] ?? 0
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
