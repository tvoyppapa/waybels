<?php
/**
 * AQUM - Сообщения
 * Чаты, сторис, диалоги
 */

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

session_start();
requireAuth();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
updateUserLastSeen($user_id);
$unread_count = getUnreadMessagesCount($user_id);

// Получаем список чатов пользователя
$stmt = $pdo->prepare("
    SELECT 
        c.id as channel_id,
        c.name as channel_name,
        c.type as channel_type,
        c.last_message_at,
        CASE 
            WHEN c.type = 'private' THEN (
                SELECT u.name 
                FROM channel_members cm2
                JOIN users u ON cm2.user_id = u.id
                WHERE cm2.channel_id = c.id AND cm2.user_id != ?
                LIMIT 1
            )
            ELSE c.name
        END as display_name,
        CASE 
            WHEN c.type = 'private' THEN (
                SELECT u.avatar
                FROM channel_members cm2
                JOIN users u ON cm2.user_id = u.id
                WHERE cm2.channel_id = c.id AND cm2.user_id != ?
                LIMIT 1
            )
            ELSE NULL
        END as avatar,
        (SELECT content FROM messages WHERE channel_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages m 
         LEFT JOIN message_reads mr ON m.id = mr.message_id AND mr.user_id = ?
         WHERE m.channel_id = c.id AND m.sender_id != ? AND mr.id IS NULL) as unread_messages
    FROM channels c
    JOIN channel_members cm ON c.id = cm.channel_id
    WHERE cm.user_id = ?
    ORDER BY c.last_message_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$chats = $stmt->fetchAll();

// Получаем активные сторис (только в разделе сообщений)
$stmt = $pdo->prepare("
    SELECT s.*, u.name, u.avatar, u.username,
           (SELECT COUNT(*) FROM story_views WHERE story_id = s.id AND user_id = ?) as viewed
    FROM stories s
    JOIN users u ON s.user_id = u.id
    WHERE s.expires_at > NOW()
    AND (
        s.user_id = ? 
        OR s.user_id IN (SELECT teacher_id FROM favorites WHERE user_id = ?)
        OR s.user_id IN (SELECT user_id FROM channel_members WHERE channel_id IN 
            (SELECT channel_id FROM channel_members WHERE user_id = ?))
    )
    GROUP BY s.user_id
    ORDER BY MAX(s.created_at) DESC
    LIMIT 10
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$stories = $stmt->fetchAll();

$current_page = 'messages';
?>
<?php include 'includes/header.php'; ?>
<title>Сообщения - AQUM</title>
<link rel="stylesheet" href="/style/messages.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="messages-container">
            <!-- Заголовок -->
            <div class="messages-header">
                <h1>Сообщения</h1>
                <button class="btn-icon" onclick="alert('Новое сообщение - в разработке')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </button>
            </div>
            
            <!-- Сторис -->
            <?php if (!empty($stories)): ?>
            <div class="stories-section">
                <div class="stories-container">
                    <!-- Моя история -->
                    <button class="story-item story-add" onclick="alert('Добавить сторис - в разработке')">
                        <?= getAvatar($user['avatar'], $user['name'], 56) ?>
                        <div class="story-add-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                        </div>
                        <span>Ваша история</span>
                    </button>
                    
                    <!-- Истории других -->
                    <?php foreach ($stories as $story): ?>
                    <button class="story-item <?= $story['viewed'] ? 'story-viewed' : '' ?>" 
                            onclick="alert('Просмотр сторис - в разработке')">
                        <?= getAvatar($story['avatar'], $story['name'], 56) ?>
                        <span><?= e($story['name']) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Поиск -->
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" placeholder="Поиск сообщений..." id="search-input">
            </div>
            
            <!-- Список чатов -->
            <div class="chats-list">
                <?php if (empty($chats)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <h3>Нет сообщений</h3>
                    <p>Начните общение с преподавателями</p>
                </div>
                <?php else: ?>
                    <?php foreach ($chats as $chat): ?>
                    <a href="chat.php?id=<?= $chat['channel_id'] ?>" class="chat-item">
                        <div class="chat-avatar">
                            <?= getAvatar($chat['avatar'], $chat['display_name'], 56) ?>
                            <?php if ($chat['unread_messages'] > 0): ?>
                            <span class="chat-badge"><?= $chat['unread_messages'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="chat-info">
                            <div class="chat-header">
                                <span class="chat-name"><?= e($chat['display_name']) ?></span>
                                <span class="chat-time"><?= timeAgo($chat['last_message_at']) ?></span>
                            </div>
                            <p class="chat-message <?= $chat['unread_messages'] > 0 ? 'chat-unread' : '' ?>">
                                <?= e(truncate($chat['last_message'] ?? 'Нет сообщений', 60)) ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Поиск по чатам
document.getElementById('search-input')?.addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    document.querySelectorAll('.chat-item').forEach(chat => {
        const name = chat.querySelector('.chat-name').textContent.toLowerCase();
        const message = chat.querySelector('.chat-message').textContent.toLowerCase();
        chat.style.display = (name.includes(query) || message.includes(query)) ? 'flex' : 'none';
    });
});
</script>

</body>
</html>
