<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Получаем чаты пользователя
$stmt = $pdo->prepare("
    SELECT c.*, 
           u.id as contact_id,
           u.name as contact_name, 
           u.avatar as contact_avatar,
           c.last_message,
           c.last_message_at
    FROM chats c
    INNER JOIN users u ON (
        CASE 
            WHEN c.user1_id = ? THEN c.user2_id 
            ELSE c.user1_id 
        END = u.id
    )
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.last_message_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id]);
$chats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщения - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboard.css">
    <link rel="stylesheet" href="style/messages.css">
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Сообщения</h1>
            </div>
        </header>
        
        <div class="messages-container">
            <?php if (empty($chats)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <h2>Нет сообщений</h2>
                    <p>Начните общение с репетиторами</p>
                    <a href="dashboard.php" class="btn-primary">Найти репетитора</a>
                </div>
            <?php else: ?>
                <div class="chats-list">
                    <?php foreach ($chats as $chat): ?>
                        <a href="chat.php?id=<?= $chat['contact_id'] ?>" class="chat-item">
                            <img src="<?= e($chat['contact_avatar']) ?>" alt="<?= e($chat['contact_name']) ?>" class="chat-avatar">
                            
                            <div class="chat-info">
                                <div class="chat-header">
                                    <h3><?= e($chat['contact_name']) ?></h3>
                                    <span class="chat-time"><?= timeAgo($chat['last_message_at']) ?></span>
                                </div>
                                <p class="chat-last-message"><?= e(mb_substr($chat['last_message'] ?? '', 0, 50)) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/bottom_nav.php'; ?>
    
</body>
</html>
<?php
// Helper функция
function timeAgo($timestamp) {
    if (!$timestamp) return '';
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'только что';
    if ($diff < 3600) return floor($diff / 60) . ' мин назад';
    if ($diff < 86400) return floor($diff / 3600) . ' ч назад';
    if ($diff < 604800) return floor($diff / 86400) . ' д назад';
    return date('d.m.Y', $time);
}
?>
