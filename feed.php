<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Убираем флаг интересов если он есть
if (isset($_SESSION['needs_interests'])) {
    unset($_SESSION['needs_interests']);
}

// Получаем задания пользователя (с проверкой существования таблицы)
try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as teacher_name, u.username as teacher_username, u.avatar as teacher_avatar
        FROM assignments a
        JOIN users u ON a.teacher_id = u.id
        WHERE a.student_id = ?
        AND a.status != 'completed'
        ORDER BY a.due_date ASC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    // Таблица assignments не существует - создадим пустой массив
    $assignments = [];
}

// Получаем посты для ленты
$userInterests = !empty($current_user['interests']) ? explode(',', $current_user['interests']) : [];

if (!empty($userInterests)) {
    $placeholders = implode(',', array_fill(0, count($userInterests), '?'));
    $sql = "
        SELECT p.*, 
               u.name as author_name, 
               u.username as author_username,
               u.avatar as author_avatar,
               t.subject,
               EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p
        JOIN users u ON p.teacher_id = u.id
        LEFT JOIN teacher_profiles t ON u.id = t.user_id
        WHERE (p.tags REGEXP CONCAT('(',REPLACE(?, ',', '|'),')') OR u.interests REGEXP CONCAT('(',REPLACE(?, ',', '|'),')'))
        AND p.is_published = TRUE
        AND u.last_seen_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY p.created_at DESC
        LIMIT 20
    ";
    $stmt = $pdo->prepare($sql);
    $interestsPattern = implode('|', $userInterests);
    $stmt->execute([$user_id, $interestsPattern, $interestsPattern]);
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.name as author_name,
               u.username as author_username,
               u.avatar as author_avatar,
               t.subject,
               EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p
        JOIN users u ON p.teacher_id = u.id
        LEFT JOIN teacher_profiles t ON u.id = t.user_id
        WHERE p.is_published = TRUE
        AND u.last_seen_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
}

$posts = $stmt->fetchAll();

$page_title = 'Лента';
include 'includes/layout.php';
?>

<div class="feed-container">
    <!-- ЗАДАНИЯ (если есть) -->
    <?php if (!empty($assignments)): ?>
    <div class="assignments-section">
        <div class="section-header">
            <h3>📚 Ваши задания</h3>
            <span class="badge"><?= count($assignments) ?></span>
        </div>
        
        <div class="assignments-scroll">
            <?php foreach ($assignments as $assignment): ?>
            <div class="assignment-card">
                <div class="assignment-header">
                    <img src="<?= e($assignment['teacher_avatar']) ?>" alt="<?= e($assignment['teacher_name']) ?>" class="assignment-avatar">
                    <div>
                        <div class="assignment-teacher">@<?= e($assignment['teacher_username']) ?></div>
                        <div class="assignment-time"><?= $assignment['due_date'] ? 'До ' . date('d.m в H:i', strtotime($assignment['due_date'])) : 'Без срока' ?></div>
                    </div>
                </div>
                <div class="assignment-title"><?= e($assignment['title']) ?></div>
                <div class="assignment-desc"><?= e(mb_substr($assignment['description'], 0, 100)) ?>...</div>
                <button class="assignment-action">Приступить</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ЛЕНТА ПОСТОВ -->
    <div class="feed-posts">
        <?php if (empty($posts)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="9" y1="9" x2="15" y2="9"/>
            </svg>
            <h3>Пока нет постов</h3>
            <p>Подпишитесь на репетиторов чтобы видеть их контент</p>
        </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
            <article class="post" data-post-id="<?= $post['id'] ?>">
                <div class="post-header">
                    <a href="/@<?= e($post['author_username']) ?>" class="post-author">
                        <img src="<?= e($post['author_avatar']) ?>" alt="<?= e($post['author_name']) ?>" class="post-avatar">
                        <div class="post-author-info">
                            <div class="post-author-name"><?= e($post['author_name']) ?></div>
                            <div class="post-meta">@<?= e($post['author_username']) ?> · <?= timeAgo($post['created_at']) ?></div>
                        </div>
                    </a>
                    
                    <button class="post-menu-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/>
                        </svg>
                    </button>
                </div>
                
                <?php if ($post['title']): ?>
                <h3 class="post-title"><?= e($post['title']) ?></h3>
                <?php endif; ?>
                
                <div class="post-content"><?= nl2br(e($post['content'])) ?></div>
                
                <?php if ($post['media_urls']): ?>
                <div class="post-media">
                    <?php
                    $mediaUrls = explode(',', $post['media_urls']);
                    foreach ($mediaUrls as $mediaUrl):
                    ?>
                    <img src="<?= e(trim($mediaUrl)) ?>" alt="Media" class="post-image">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="post-actions">
                    <button class="action-btn like-btn <?= $post['user_liked'] ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>">
                        <svg viewBox="0 0 24 24" fill="<?= $post['user_liked'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span><?= $post['likes_count'] ?></span>
                    </button>
                    
                    <button class="action-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span><?= $post['comments_count'] ?></span>
                    </button>
                    
                    <button class="action-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/>
                        </svg>
                        <span>Поделиться</span>
                    </button>
                    
                    <button class="action-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
                        </svg>
                    </button>
                </div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<?php include 'includes/layout_footer.php'; ?>

<style>
/* FEED СТИЛИ */
.feed-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

/* ЗАДАНИЯ */
.assignments-section {
    background: var(--bg-primary);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid var(--border-color);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.section-header h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
}

.badge {
    background: #667eea;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.assignments-scroll {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 10px;
}

.assignments-scroll::-webkit-scrollbar {
    height: 6px;
}

.assignments-scroll::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 10px;
}

.assignment-card {
    min-width: 280px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 15px;
}

.assignment-header {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.assignment-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
}

.assignment-teacher {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

.assignment-time {
    font-size: 12px;
    color: var(--text-secondary);
}

.assignment-title {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text-primary);
}

.assignment-desc {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 12px;
    line-height: 1.4;
}

.assignment-action {
    width: 100%;
    padding: 8px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

/* ПОСТЫ */
.feed-posts {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.post {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px;
    transition: all 0.3s;
}

.post:hover {
    box-shadow: 0 4px 12px var(--shadow);
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.post-author {
    display: flex;
    gap: 12px;
    text-decoration: none;
    color: inherit;
}

.post-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
}

.post-author-info {
    display: flex;
    flex-direction: column;
}

.post-author-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
}

.post-meta {
    font-size: 13px;
    color: var(--text-secondary);
}

.post-menu-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 4px;
}

.post-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 10px;
    color: var(--text-primary);
}

.post-content {
    font-size: 15px;
    line-height: 1.6;
    color: var(--text-primary);
    margin-bottom: 15px;
}

.post-media {
    margin-bottom: 15px;
    border-radius: 12px;
    overflow: hidden;
}

.post-image {
    width: 100%;
    display: block;
}

.post-actions {
    display: flex;
    gap: 20px;
    padding-top: 12px;
    border-top: 1px solid var(--border-color);
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 14px;
    padding: 6px 10px;
    border-radius: 8px;
    transition: all 0.3s;
}

.action-btn:hover {
    background: var(--bg-secondary);
}

.action-btn svg {
    width: 20px;
    height: 20px;
}

.action-btn.liked {
    color: #e53e3e;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state svg {
    width: 64px;
    height: 64px;
    color: var(--text-tertiary);
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.empty-state p {
    font-size: 15px;
    color: var(--text-secondary);
    margin-bottom: 20px;
}

/* МОДАЛЬНОЕ ОКНО */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
}

.modal-content {
    background: var(--bg-primary);
    border-radius: 20px;
    padding: 30px;
    max-width: 500px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 32px;
    color: var(--text-secondary);
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.modal-close:hover {
    background: var(--bg-secondary);
}

.modal-content h2 {
    font-size: 24px;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.modal-subtitle {
    font-size: 15px;
    color: var(--text-secondary);
    margin-bottom: 25px;
}

.interests-modal-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 25px;
}

.interest-modal-item {
    cursor: pointer;
}

.interest-modal-item input {
    display: none;
}

.interest-modal-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 18px 12px;
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    transition: all 0.3s;
    min-height: 100px;
}

.interest-modal-item:hover .interest-modal-box {
    border-color: #667eea;
}

.interest-modal-item input:checked + .interest-modal-box {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: #667eea;
    color: white;
}

.interest-modal-box .icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.interest-modal-box .name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
}

.interest-modal-item input:checked + .interest-modal-box .name {
    color: white;
}

.modal-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

@media (max-width: 640px) {
    .feed-container {
        padding: 15px;
    }
    
    .assignments-scroll {
        gap: 10px;
    }
    
    .assignment-card {
        min-width: 240px;
    }
    
    .interests-modal-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Лайки
document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const postId = this.dataset.postId;
        const isLiked = this.classList.contains('liked');
        
        fetch('/api/like.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({post_id: postId, action: isLiked ? 'unlike' : 'like'})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.classList.toggle('liked');
                const countSpan = this.querySelector('span');
                countSpan.textContent = data.likes_count;
                
                const svg = this.querySelector('svg path');
                if (this.classList.contains('liked')) {
                    svg.setAttribute('fill', 'currentColor');
                } else {
                    svg.setAttribute('fill', 'none');
                }
            }
        });
    });
});
</script>
