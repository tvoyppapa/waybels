<?php
session_start();

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

// Определяем чей профиль смотрим
$username = $_GET['user'] ?? null;
$current_user_id = $_SESSION['user_id'] ?? null;

if (!$current_user_id) {
    header('Location: auth.php');
    exit;
}

// Если передан user ID, получаем username
if ($username && is_numeric($username)) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch();
    if ($result) {
        header('Location: /@' . $result['username']);
        exit;
    }
}

// Если не указан username, показываем свой профиль
if (!$username) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $result = $stmt->fetch();
    if ($result) {
        header('Location: /@' . $result['username']);
        exit;
    }
}

// Получаем данные пользователя по username
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: feed.php');
    exit;
}

$is_own_profile = ($user['id'] == $current_user_id);

// Получаем текущего пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user = $stmt->fetch();

// Проверяем является ли пользователь репетитором
$stmt = $pdo->prepare("SELECT * FROM teacher_profiles WHERE user_id = ?");
$stmt->execute([$user['id']]);
$teacher_profile = $stmt->fetch();

// Получаем посты пользователя
$stmt = $pdo->prepare("
    SELECT p.*,
           EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    WHERE p.teacher_id = ?
    AND p.is_published = TRUE
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->execute([$current_user_id, $user['id']]);
$posts = $stmt->fetchAll();

// Обработка обновления профиля
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, bio = ? WHERE id = ?");
        $stmt->execute([$name, $bio, $current_user_id]);
        
        $_SESSION['success'] = 'Профиль обновлен';
        header('Location: /@' . $username);
        exit;
    }
}

$page_title = '@' . $user['username'];
include 'includes/layout.php';
?>

<div class="profile-container">
    <!-- HEADER ПРОФИЛЯ -->
    <div class="profile-header">
        <div class="profile-avatar-section">
            <img src="<?= e($user['avatar']) ?>" alt="<?= e($user['name']) ?>" class="profile-avatar">
        </div>
        
        <div class="profile-info-section">
            <div class="profile-name-row">
                <div>
                    <h1 class="profile-name"><?= e($user['name']) ?></h1>
                    <div class="profile-username">@<?= e($user['username']) ?></div>
                </div>
                
                <?php if ($is_own_profile): ?>
                    <button onclick="toggleEditMode()" class="btn btn-secondary">Редактировать</button>
                <?php else: ?>
                    <button class="btn btn-primary">Подписаться</button>
                <?php endif; ?>
            </div>
            
            <?php if ($teacher_profile): ?>
            <div class="profile-badge teacher-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
                Репетитор · <?= e($teacher_profile['subject']) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($user['bio']): ?>
            <div class="profile-bio"><?= nl2br(e($user['bio'])) ?></div>
            <?php endif; ?>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= count($posts) ?></span>
                    <span class="stat-label">постов</span>
                </div>
                
                <?php if ($teacher_profile): ?>
                <div class="stat-item">
                    <span class="stat-value"><?= $teacher_profile['rating'] ?></span>
                    <span class="stat-label">рейтинг</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $teacher_profile['total_students'] ?></span>
                    <span class="stat-label">учеников</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $teacher_profile['hourly_rate'] ?> ₽</span>
                    <span class="stat-label">в час</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ТАБЫ -->
    <div class="profile-tabs">
        <button class="profile-tab active" data-tab="posts">Посты</button>
        <?php if ($teacher_profile): ?>
        <button class="profile-tab" data-tab="courses">Курсы</button>
        <button class="profile-tab" data-tab="reviews">Отзывы</button>
        <?php endif; ?>
    </div>
    
    <!-- КОНТЕНТ -->
    <div class="profile-content">
        <div class="tab-content active" id="posts-tab">
            <?php if (empty($posts)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="9" y1="9" x2="15" y2="9"/>
                </svg>
                <p>Пока нет постов</p>
            </div>
            <?php else: ?>
            <div class="profile-posts">
                <?php foreach ($posts as $post): ?>
                <article class="post" data-post-id="<?= $post['id'] ?>">
                    <div class="post-header">
                        <div class="post-author">
                            <img src="<?= e($user['avatar']) ?>" alt="<?= e($user['name']) ?>" class="post-avatar">
                            <div class="post-author-info">
                                <div class="post-author-name"><?= e($user['name']) ?></div>
                                <div class="post-meta">@<?= e($user['username']) ?> · <?= timeAgo($post['created_at']) ?></div>
                            </div>
                        </div>
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
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО РЕДАКТИРОВАНИЯ -->
<?php if ($is_own_profile): ?>
<div class="modal-overlay" id="editModal" style="display: none;">
    <div class="modal-content">
        <button class="modal-close" onclick="toggleEditMode()">×</button>
        
        <h2>Редактировать профиль</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Имя</label>
                <input type="text" name="name" value="<?= e($user['name']) ?>" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>О себе</label>
                <textarea name="bio" class="form-control" rows="4"><?= e($user['bio']) ?></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="submit" name="update_profile" class="btn btn-primary">Сохранить</button>
                <button type="button" onclick="toggleEditMode()" class="btn btn-secondary">Отмена</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/layout_footer.php'; ?>

<style>
.profile-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 20px;
}

.profile-avatar-section {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid var(--border-color);
    object-fit: cover;
}

.profile-info-section {
    text-align: center;
}

.profile-name-row {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.profile-name {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: var(--text-primary);
}

.profile-username {
    font-size: 16px;
    color: var(--text-secondary);
}

.profile-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 15px;
}

.profile-badge svg {
    width: 16px;
    height: 16px;
}

.profile-bio {
    font-size: 15px;
    line-height: 1.6;
    color: var(--text-primary);
    margin-bottom: 20px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.profile-stats {
    display: flex;
    justify-content: center;
    gap: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
}

.stat-label {
    font-size: 13px;
    color: var(--text-secondary);
}

.profile-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--border-color);
}

.profile-tab {
    padding: 12px 20px;
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.3s;
}

.profile-tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.profile-content {
    min-height: 400px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.profile-posts {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.post {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px;
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
}

.post-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
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

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state svg {
    width: 48px;
    height: 48px;
    color: var(--text-tertiary);
    margin-bottom: 15px;
}

.empty-state p {
    font-size: 15px;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .profile-container {
        padding: 15px;
    }
    
    .profile-header {
        padding: 20px;
    }
    
    .profile-stats {
        gap: 20px;
    }
}
</style>

<script>
function toggleEditMode() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
    }
}

// Табы
document.querySelectorAll('.profile-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        this.classList.add('active');
        const tabId = this.dataset.tab + '-tab';
        document.getElementById(tabId)?.classList.add('active');
    });
});

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
