<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$theme = $user['theme'] ?? 'light';

// Обновляем онлайн статус
$stmt = $pdo->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

// Получаем чаты пользователя
$stmt = $pdo->prepare("
    SELECT c.*, 
           CASE 
               WHEN c.user1_id = ? THEN u2.id
               ELSE u1.id
           END as other_user_id,
           CASE 
               WHEN c.user1_id = ? THEN u2.name
               ELSE u1.name
           END as other_user_name,
           CASE 
               WHEN c.user1_id = ? THEN u2.avatar
               ELSE u1.avatar
           END as other_user_avatar,
           CASE 
               WHEN c.user1_id = ? THEN u2.is_online
               ELSE u1.is_online
           END as other_user_online
    FROM chats c
    LEFT JOIN users u1 ON c.user1_id = u1.id
    LEFT JOIN users u2 ON c.user2_id = u2.id
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.last_message_at DESC NULLS LAST
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$chats = $stmt->fetchAll();

// Добавляем бота Wibs в начало списка, если его еще нет в чатах
$bot_exists = false;
foreach ($chats as $chat) {
    if ($chat['other_user_id'] == 0 || strtolower($chat['other_user_name']) === 'wibs bot') {
        $bot_exists = true;
        break;
    }
}

if (!$bot_exists) {
    // Получаем ID бота
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'wibs_bot' LIMIT 1");
    $stmt->execute();
    $bot = $stmt->fetch();
    
    if ($bot) {
        $bot_chat = [
            'id' => 0,
            'other_user_id' => $bot['id'],
            'other_user_name' => 'Wibs Bot',
            'other_user_avatar' => '/img/bot-avatar.png',
            'other_user_online' => true,
            'last_message' => 'Здравствуйте! Чем могу помочь?',
            'last_message_at' => date('Y-m-d H:i:s')
        ];
        array_unshift($chats, $bot_chat);
    }
}

// Выбранный чат
$selected_chat_id = $_GET['chat'] ?? ($chats[0]['id'] ?? 0);
$selected_user_id = $_GET['user'] ?? ($chats[0]['other_user_id'] ?? 0);

// Получаем сообщения выбранного чата
$messages = [];
if ($selected_chat_id) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name, u.avatar as sender_avatar
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.chat_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$selected_chat_id]);
    $messages = $stmt->fetchAll();
}

// AJAX: Отправка сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $chat_id = (int)$_POST['chat_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (chat_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$chat_id, $user_id, $message]);
        
        // Обновляем последнее сообщение в чате
        $stmt = $pdo->prepare("UPDATE chats SET last_message = ?, last_message_at = NOW() WHERE id = ?");
        $stmt->execute([$message, $chat_id]);
        
        echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
    }
    exit;
}

// AJAX: Получение новых сообщений
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_messages'])) {
    $chat_id = (int)$_GET['chat_id'];
    $last_id = (int)($_GET['last_id'] ?? 0);
    
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name, u.avatar as sender_avatar
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.chat_id = ? AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$chat_id, $last_id]);
    $new_messages = $stmt->fetchAll();
    
    echo json_encode(['messages' => $new_messages]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщения - WayBels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        .layout {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur-strong));
            border-right: 1px solid var(--glass-border);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-logo {
            padding: 20px;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .logo-img {
            width: 40px;
            height: 40px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin: 4px 12px;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition);
        }
        
        .nav-link:hover {
            background: var(--glass-bg-subtle);
        }
        
        .nav-link.active {
            background: var(--gradient-primary);
            color: white;
        }
        
        .nav-link svg {
            width: 20px;
            height: 20px;
        }
        
        /* Chat List */
        .chat-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px 0;
        }
        
        .chat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            cursor: pointer;
            transition: all var(--transition);
            border-left: 3px solid transparent;
        }
        
        .chat-item:hover {
            background: var(--glass-bg-subtle);
        }
        
        .chat-item.active {
            background: var(--gradient-glass);
            border-left-color: var(--primary);
        }
        
        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-full);
            background-size: cover;
            background-position: center;
            border: 2px solid var(--glass-border);
            position: relative;
            flex-shrink: 0;
        }
        
        .online-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: var(--success);
            border: 2px solid var(--glass-bg);
            border-radius: 50%;
        }
        
        .chat-info {
            flex: 1;
            min-width: 0;
        }
        
        .chat-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .chat-last-message {
            font-size: 13px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Chat Window */
        .chat-window {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-secondary);
        }
        
        .chat-header {
            padding: 20px 32px;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur-strong));
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .chat-header-avatar {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-full);
            background-size: cover;
            border: 2px solid var(--glass-border);
        }
        
        .chat-header-text h2 {
            font-size: 18px;
            margin-bottom: 2px;
        }
        
        .chat-header-status {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .chat-header-actions {
            display: flex;
            gap: 8px;
        }
        
        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: var(--glass-bg-subtle);
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .icon-btn:hover {
            background: var(--glass-bg);
            transform: scale(1.05);
        }
        
        .icon-btn svg {
            width: 20px;
            height: 20px;
        }
        
        /* Messages */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 24px 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .message {
            display: flex;
            gap: 12px;
            max-width: 70%;
        }
        
        .message.own {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            background-size: cover;
            flex-shrink: 0;
        }
        
        .message-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .message-bubble {
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            font-size: 15px;
            line-height: 1.5;
        }
        
        .message.own .message-bubble {
            background: var(--gradient-primary);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message:not(.own) .message-bubble {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 11px;
            color: var(--text-tertiary);
            padding: 0 16px;
        }
        
        /* Input */
        .chat-input-container {
            padding: 20px 32px;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur-strong));
            border-top: 1px solid var(--glass-border);
        }
        
        .chat-input-wrapper {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            background: var(--glass-bg-subtle);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            font-size: 15px;
            resize: none;
            max-height: 120px;
            font-family: inherit;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-full);
            background: var(--gradient-primary);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
            flex-shrink: 0;
        }
        
        .send-btn:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-md);
        }
        
        .send-btn svg {
            width: 20px;
            height: 20px;
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 60px;
                z-index: 100;
                transform: translateX(-100%);
                transition: transform var(--transition);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .chat-window {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="chatSidebar">
            <div class="sidebar-logo">
                <a href="dashboard.php">
                    <img src="img/logo.svg" alt="WayBels" class="logo-img">
                </a>
            </div>
            
            <div style="padding: 12px 16px;">
                <a href="dashboard.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <span>Назад</span>
                </a>
            </div>
            
            <!-- Chat List -->
            <div class="chat-list">
                <?php foreach ($chats as $chat): ?>
                    <div class="chat-item <?= $selected_chat_id == $chat['id'] ? 'active' : '' ?>" 
                         onclick="selectChat(<?= $chat['id'] ?>, <?= $chat['other_user_id'] ?>)">
                        <div class="chat-avatar" style="background-image: url('<?= e($chat['other_user_avatar']) ?>')">
                            <?php if ($chat['other_user_online']): ?>
                                <div class="online-dot"></div>
                            <?php endif; ?>
                        </div>
                        <div class="chat-info">
                            <div class="chat-name"><?= e($chat['other_user_name']) ?></div>
                            <div class="chat-last-message"><?= e($chat['last_message'] ?? 'Начните диалог') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
        
        <!-- Chat Window -->
        <main class="chat-window">
            <?php if (!empty($chats) && $selected_chat_id): ?>
                <?php 
                $current_chat = null;
                foreach ($chats as $chat) {
                    if ($chat['id'] == $selected_chat_id) {
                        $current_chat = $chat;
                        break;
                    }
                }
                ?>
                
                <?php if ($current_chat): ?>
                    <!-- Header -->
                    <div class="chat-header">
                        <div class="chat-header-info">
                            <div class="chat-header-avatar" style="background-image: url('<?= e($current_chat['other_user_avatar']) ?>')"></div>
                            <div class="chat-header-text">
                                <h2><?= e($current_chat['other_user_name']) ?></h2>
                                <div class="chat-header-status">
                                    <?= $current_chat['other_user_online'] ? '🟢 онлайн' : 'был(а) недавно' ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-header-actions">
                            <button class="icon-btn" title="Настройки чата">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="1"/>
                                    <circle cx="12" cy="5" r="1"/>
                                    <circle cx="12" cy="19" r="1"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Messages -->
                    <div class="messages-container" id="messagesContainer">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?= $msg['sender_id'] == $user_id ? 'own' : '' ?>" data-id="<?= $msg['id'] ?>">
                                <div class="message-avatar" style="background-image: url('<?= e($msg['sender_avatar']) ?>')"></div>
                                <div class="message-content">
                                    <div class="message-bubble">
                                        <?= nl2br(e($msg['message'])) ?>
                                    </div>
                                    <div class="message-time">
                                        <?= date('H:i', strtotime($msg['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Input -->
                    <div class="chat-input-container">
                        <div class="chat-input-wrapper">
                            <textarea 
                                class="chat-input" 
                                id="messageInput" 
                                placeholder="Напишите сообщение..."
                                rows="1"
                                onkeydown="handleKeyPress(event)"
                            ></textarea>
                            <button class="send-btn" onclick="sendMessage()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <h3>Нет сообщений</h3>
                    <p>Начните диалог с репетитором</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        const chatId = <?= $selected_chat_id ?>;
        let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
        
        function selectChat(chatId, userId) {
            window.location.href = `?chat=${chatId}&user=${userId}`;
        }
        
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            fetch('messages.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `send_message=1&chat_id=${chatId}&message=${encodeURIComponent(message)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadNewMessages();
                }
            });
        }
        
        function handleKeyPress(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        }
        
        function loadNewMessages() {
            if (!chatId) return;
            
            fetch(`messages.php?get_messages=1&chat_id=${chatId}&last_id=${lastMessageId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        const container = document.getElementById('messagesContainer');
                        
                        data.messages.forEach(msg => {
                            lastMessageId = msg.id;
                            
                            const isOwn = msg.sender_id == <?= $user_id ?>;
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `message ${isOwn ? 'own' : ''}`;
                            messageDiv.innerHTML = `
                                <div class="message-avatar" style="background-image: url('${msg.sender_avatar}')"></div>
                                <div class="message-content">
                                    <div class="message-bubble">${msg.message}</div>
                                    <div class="message-time">${new Date(msg.created_at).toLocaleTimeString('ru', {hour: '2-digit', minute: '2-digit'})}</div>
                                </div>
                            `;
                            
                            container.appendChild(messageDiv);
                        });
                        
                        container.scrollTop = container.scrollHeight;
                    }
                });
        }
        
        // Автообновление сообщений каждые 2 секунды
        setInterval(loadNewMessages, 2000);
        
        // Скролл вниз при загрузке
        const container = document.getElementById('messagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
        
        // Auto-resize textarea
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }
    </script>
</body>
</html>
