<?php
/**
 * AQUM - Главная страница (Feed)
 * Уведомления + Задания + Посты преподавателей
 */

require_once 'config_aqum.php';
require_once 'db_aqum.php';
require_once 'helpers_aqum.php';

session_start();
requireAuth();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
updateUserLastSeen($user_id);

// Получаем количество непрочитанных сообщений для sidebar
$unread_count = getUnreadMessagesCount($user_id);

// Получаем уведомления (последние 5)
$stmt = $pdo->prepare(
    "SELECT * FROM notifications 
     WHERE user_id = ? AND is_read = 0 
     ORDER BY created_at DESC 
     LIMIT 5"
);
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Получаем задания для ученика (последние 5)
$assignments = [];
if ($user['role'] === 'user') {
    $stmt = $pdo->prepare(
        "SELECT a.*, u.name as teacher_name, l.title as lesson_title
         FROM assignments a
         JOIN users u ON a.teacher_id = u.id
         JOIN lessons l ON a.lesson_id = l.id
         WHERE a.student_id = ? AND a.status != 'completed'
         ORDER BY a.due_date ASC
         LIMIT 5"
    );
    $stmt->execute([$user_id]);
    $assignments = $stmt->fetchAll();
}

// Получаем посты по интересам пользователя
$interests = $user['interests'] ? explode(',', $user['interests']) : [];

$query = "
    SELECT p.*, u.name as author_name, u.username, u.avatar,
           EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as is_liked
    FROM posts p
    JOIN users u ON p.teacher_id = u.id
    WHERE p.is_published = 1
";

$params = [$user_id];

// Фильтр по интересам (если есть)
if (!empty($interests)) {
    $placeholders = implode(',', array_fill(0, count($interests), '?'));
    $query .= " AND (";
    foreach ($interests as $i => $interest) {
        if ($i > 0) $query .= " OR ";
        $query .= "FIND_IN_SET(?, u.interests)";
        $params[] = $interest;
    }
    $query .= ")";
}

$query .= " ORDER BY p.created_at DESC LIMIT " . POSTS_PER_PAGE;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Обработка лайков
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    $post_id = (int)$_POST['post_id'];
    
    $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    
    if ($stmt->fetch()) {
        // Убрать лайк
        $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count - 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        jsonSuccess('Лайк убран', ['liked' => false]);
    } else {
        // Поставить лайк
        $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
        
        $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        jsonSuccess('Лайк поставлен', ['liked' => true]);
    }
}

$page_title = "Главная - AQUM";
$current_page = 'feed';
?>
<?php include 'includes/header_aqum.php'; ?>

<div class="app-container">
    <?php include 'includes/sidebar_aqum.php'; ?>
    
    <main class="main-content">
        <div class="content-wrapper">
            
            <!-- Виджеты: Уведомления и Задания -->
            <?php if (!empty($notifications) || !empty($assignments)): ?>
            <div style="margin-bottom: 24px;">
                
                <!-- Уведомления -->
                <?php if (!empty($notifications)): ?>
                <div class="card" style="margin-bottom: 16px;">
                    <div class="card-header">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <h3 style="font-size: 18px; font-weight: 600; flex: 1;">Уведомления</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($notifications as $notif): ?>
                        <div style="padding: 12px 0; border-bottom: 1px solid var(--border-color); display: flex; gap: 12px; align-items: start;">
                            <div style="flex: 1;">
                                <strong style="color: var(--text-primary);"><?= e($notif['title']) ?></strong>
                                <p style="font-size: 14px; color: var(--text-secondary); margin-top: 4px;">
                                    <?= timeAgo($notif['created_at']) ?>
                                </p>
                            </div>
                            <?php if ($notif['link_url']): ?>
                            <a href="<?= e($notif['link_url']) ?>" class="btn-sm btn-ghost">Открыть</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Задания -->
                <?php if (!empty($assignments)): ?>
                <div class="card">
                    <div class="card-header">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <h3 style="font-size: 18px; font-weight: 600; flex: 1;">Домашние задания</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($assignments as $assignment): ?>
                        <div style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <strong style="color: var(--text-primary);"><?= e($assignment['title']) ?></strong>
                            <p style="font-size: 14px; color: var(--text-secondary); margin-top: 4px;">
                                Преподаватель: <?= e($assignment['teacher_name']) ?> • 
                                <?php if ($assignment['due_date']): ?>
                                    Срок: <?= date('d.m.Y', strtotime($assignment['due_date'])) ?>
                                <?php endif; ?>
                            </p>
                            <span class="badge badge-warning" style="margin-top: 8px; display: inline-flex;">
                                <?= match($assignment['status']) {
                                    'assigned' => 'Назначено',
                                    'submitted' => 'Отправлено',
                                    'reviewed' => 'Проверяется',
                                    default => $assignment['status']
                                } ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            <?php endif; ?>
            
            <!-- Заголовок ленты -->
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 20px; color: var(--text-primary);">
                Лента
            </h2>
            
            <!-- Посты -->
            <?php if (empty($posts)): ?>
                <div class="card" style="text-align: center; padding: 48px 24px;">
                    <p style="font-size: 18px; color: var(--text-secondary);">
                        Пока нет постов по вашим интересам
                    </p>
                    <a href="/search_aqum.php" class="btn btn-primary" style="margin-top: 16px; display: inline-flex;">
                        Найти преподавателей
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <div class="card glass-hover" style="margin-bottom: 20px;">
                    <!-- Шапка поста -->
                    <div class="card-header">
                        <?php if ($post['avatar']): ?>
                            <img src="<?= e($post['avatar']) ?>" alt="<?= e($post['author_name']) ?>" class="avatar avatar-md">
                        <?php else: ?>
                            <div class="avatar-initial avatar-md"><?= e(getInitial($post['author_name'])) ?></div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <a href="/profile_aqum.php?u=<?= e($post['username']) ?>" style="font-weight: 600; color: var(--text-primary); display: block;">
                                <?= e($post['author_name']) ?>
                            </a>
                            <span style="font-size: 13px; color: var(--text-secondary);">
                                <?= timeAgo($post['created_at']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Контент поста -->
                    <div class="card-body">
                        <?php if ($post['title']): ?>
                            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: var(--text-primary);">
                                <?= e($post['title']) ?>
                            </h3>
                        <?php endif; ?>
                        <div style="color: var(--text-primary); line-height: 1.6; white-space: pre-wrap;">
                            <?= nl2br(e($post['content'])) ?>
                        </div>
                    </div>
                    
                    <!-- Футер поста (лайки, комментарии) -->
                    <div class="card-footer">
                        <div style="display: flex; gap: 24px;">
                            <button class="btn-ghost like-btn <?= $post['is_liked'] ? 'liked' : '' ?>" 
                                    data-post-id="<?= $post['id'] ?>"
                                    style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="<?= $post['is_liked'] ? '#EF4444' : 'none' ?>" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                                <span class="like-count"><?= $post['likes_count'] ?></span>
                            </button>
                            
                            <button class="btn-ghost" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                <span><?= $post['comments_count'] ?></span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
    </main>
</div>

<script>
// Обработка лайков
document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const postId = this.dataset.postId;
        const likeCount = this.querySelector('.like-count');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `toggle_like=1&post_id=${postId}&csrf_token=<?= generateCsrfToken() ?>`
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Обновляем UI
                this.classList.toggle('liked', data.data.liked);
                const svg = this.querySelector('svg');
                svg.setAttribute('fill', data.data.liked ? '#EF4444' : 'none');
                
                // Обновляем счетчик
                const currentCount = parseInt(likeCount.textContent);
                likeCount.textContent = data.data.liked ? currentCount + 1 : currentCount - 1;
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});
</script>

<style>
.like-btn.liked {
    color: #EF4444;
}
</style>

<?php include 'includes/footer_aqum.php'; ?>
