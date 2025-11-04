<?php
/**
 * AQUM - Профиль пользователя
 * Просмотр профиля студента или преподавателя
 */

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

session_start();
requireAuth();

$current_user_id = $_SESSION['user_id'];
$current_user = getUserById($current_user_id);
updateUserLastSeen($current_user_id);
$unread_count = getUnreadMessagesCount($current_user_id);

// Получаем username из URL
$username = $_GET['u'] ?? $current_user['username'];
$profile_user = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$profile_user->execute([$username]);
$profile_user = $profile_user->fetch();

if (!$profile_user) {
    setFlashMessage('error', 'Пользователь не найден');
    redirect('feed.php');
}

$is_own_profile = ($profile_user['id'] === $current_user_id);
$is_teacher = $profile_user['is_teacher'] && $profile_user['is_verified_teacher'];

// Получаем данные учителя
$teacher_profile = null;
if ($is_teacher) {
    $stmt = $pdo->prepare("SELECT * FROM teacher_profiles WHERE user_id = ?");
    $stmt->execute([$profile_user['id']]);
    $teacher_profile = $stmt->fetch();
}

// Статистика
$stats = [
    'posts' => $pdo->query("SELECT COUNT(*) FROM posts WHERE teacher_id = {$profile_user['id']}")->fetchColumn(),
    'followers' => 0, // TODO: реализовать подписки
    'orders' => getTeacherOrdersCount($profile_user['id']),
    'students' => getTeacherStudentsCount($profile_user['id']),
];

// Проверяем, в избранном ли
$is_favorite = false;
if ($is_teacher) {
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND teacher_id = ?");
    $stmt->execute([$current_user_id, $profile_user['id']]);
    $is_favorite = $stmt->fetch() !== false;
}

// Посты пользователя
$posts = [];
if ($is_teacher) {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p
        WHERE p.teacher_id = ?
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$current_user_id, $profile_user['id']]);
    $posts = $stmt->fetchAll();
}

// Отзывы (если учитель)
$reviews = [];
if ($is_teacher) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name, u.avatar
        FROM reviews r
        JOIN users u ON r.student_id = u.id
        WHERE r.teacher_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$profile_user['id']]);
    $reviews = $stmt->fetchAll();
}

// Рейтинг (только если >= 10 заказов)
$rating = null;
if ($is_teacher && $stats['orders'] >= 10 && $teacher_profile) {
    $rating = $teacher_profile['rating'];
}

$current_page = 'profile';
?>
<?php include 'includes/header.php'; ?>
<title><?= e($profile_user['name']) ?> - AQUM</title>
<link rel="stylesheet" href="/style/profile.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="profile-container">
            <!-- Заголовок профиля -->
            <div class="profile-header glass">
                <div class="profile-avatar-large">
                    <?= getAvatar($profile_user['avatar'], $profile_user['name'], 120) ?>
                </div>
                <div class="profile-info">
                    <h1><?= e($profile_user['name']) ?></h1>
                    <p class="profile-username">@<?= e($profile_user['username']) ?></p>
                    <?php if ($profile_user['bio']): ?>
                    <p class="profile-bio"><?= e($profile_user['bio']) ?></p>
                    <?php endif; ?>
                    
                    <!-- Статистика -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?= $stats['posts'] ?></span>
                            <span class="stat-label">Постов</span>
                        </div>
                        <?php if ($is_teacher): ?>
                        <div class="stat-item">
                            <span class="stat-value"><?= $stats['students'] ?></span>
                            <span class="stat-label">Учеников</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $stats['orders'] ?></span>
                            <span class="stat-label">Заказов</span>
                        </div>
                        <?php if ($rating): ?>
                        <div class="stat-item">
                            <span class="stat-value">⭐ <?= number_format($rating, 1) ?></span>
                            <span class="stat-label">Рейтинг</span>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Кнопки действий -->
                    <?php if (!$is_own_profile): ?>
                    <div class="profile-actions">
                        <a href="messages.php?user=<?= $profile_user['id'] ?>" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            Написать
                        </a>
                        <?php if ($is_teacher): ?>
                        <button class="btn btn-secondary" onclick="alert('Купить урок - в разработке')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"/>
                                <circle cx="20" cy="21" r="1"/>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            </svg>
                            Купить урок
                        </button>
                        <button class="btn-icon <?= $is_favorite ? 'active' : '' ?>" id="favorite-btn" data-teacher="<?= $profile_user['id'] ?>">
                            <svg viewBox="0 0 24 24" fill="<?= $is_favorite ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="profile-actions">
                        <a href="settings.php" class="btn btn-secondary">Редактировать профиль</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Табы -->
            <div class="profile-tabs">
                <button class="tab-btn active" data-tab="posts">Посты</button>
                <?php if ($is_teacher): ?>
                <button class="tab-btn" data-tab="courses">Курсы</button>
                <button class="tab-btn" data-tab="reviews">Отзывы</button>
                <?php endif; ?>
            </div>
            
            <!-- Контент табов -->
            <div class="profile-content">
                <!-- Посты -->
                <div class="tab-content active" id="posts-tab">
                    <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <h3>Нет постов</h3>
                        <p><?= $is_own_profile ? 'Создайте свой первый пост' : 'Пользователь еще не создал ни одного поста' ?></p>
                    </div>
                    <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                        <article class="post-card glass">
                            <h3><?= e($post['title']) ?></h3>
                            <p><?= e(truncate($post['content'], 200)) ?></p>
                            <div class="post-meta">
                                <span><?= timeAgo($post['created_at']) ?></span>
                                <div class="post-actions">
                                    <button class="post-action like-btn <?= $post['user_liked'] ? 'active' : '' ?>" data-post="<?= $post['id'] ?>">
                                        <svg viewBox="0 0 24 24" fill="<?= $post['user_liked'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                                            <path d="M7 22V11M2 13v6c0 1.1.9 2 2 2h1M17 11V2l-5 9h6l-5 9"/>
                                        </svg>
                                        <span><?= $post['likes_count'] ?></span>
                                    </button>
                                    <span class="post-action">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <?= $post['comments_count'] ?>
                                    </span>
                                    <span class="post-action">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <?= $post['views_count'] ?>
                                    </span>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Курсы -->
                <?php if ($is_teacher): ?>
                <div class="tab-content" id="courses-tab">
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                        <h3>Курсы</h3>
                        <p>Раздел в разработке</p>
                    </div>
                </div>
                
                <!-- Отзывы -->
                <div class="tab-content" id="reviews-tab">
                    <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <h3>Нет отзывов</h3>
                        <p>Пока нет отзывов от студентов</p>
                    </div>
                    <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card glass">
                            <div class="review-header">
                                <?= getAvatar($review['avatar'], $review['name'], 40) ?>
                                <div>
                                    <strong><?= e($review['name']) ?></strong>
                                    <div class="review-rating">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                        <span class="star <?= $i < $review['rating'] ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span class="review-date"><?= timeAgo($review['created_at']) ?></span>
                            </div>
                            <p><?= e($review['comment']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Переключение табов
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(tab + '-tab').classList.add('active');
    });
});

// Лайки постов
document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const postId = this.dataset.post;
        fetch('feed.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `toggle_like=1&post_id=${postId}&csrf_token=<?= generateCsrfToken() ?>`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.classList.toggle('active');
                const svg = this.querySelector('svg');
                svg.setAttribute('fill', data.data.liked ? 'currentColor' : 'none');
                this.querySelector('span').textContent = data.data.likes_count;
            }
        });
    });
});

// Избранное
document.getElementById('favorite-btn')?.addEventListener('click', function() {
    const teacherId = this.dataset.teacher;
    fetch('favorites.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `toggle=1&teacher_id=${teacherId}&csrf_token=<?= generateCsrfToken() ?>`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            this.classList.toggle('active');
            const svg = this.querySelector('svg');
            svg.setAttribute('fill', data.data.is_favorite ? 'currentColor' : 'none');
        }
    });
});
</script>

</body>
</html>
