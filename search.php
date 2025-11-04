<?php
/**
 * AQUM - Поиск
 * Поиск преподавателей, курсов, постов
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

$query = $_GET['q'] ?? '';
$filter = $_GET['filter'] ?? 'all'; // all, teachers, posts, lessons

$results = ['teachers' => [], 'posts' => [], 'lessons' => []];

if (!empty($query)) {
    $searchTerm = "%{$query}%";
    
    // Поиск преподавателей
    if ($filter === 'all' || $filter === 'teachers') {
        $stmt = $pdo->prepare("
            SELECT u.*, tp.subjects, tp.hourly_rate, tp.rating, tp.reviews_count,
                   (SELECT COUNT(*) FROM lesson_orders WHERE teacher_id = u.id AND status IN ('paid','confirmed','completed')) as orders_count
            FROM users u
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE u.is_teacher = 1 
            AND u.is_verified_teacher = 1
            AND u.last_seen_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
            AND (u.name LIKE ? OR u.bio LIKE ? OR tp.subjects LIKE ?)
            ORDER BY tp.rating DESC, orders_count DESC
            LIMIT 20
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $results['teachers'] = $stmt->fetchAll();
    }
    
    // Поиск постов
    if ($filter === 'all' || $filter === 'posts') {
        $stmt = $pdo->prepare("
            SELECT p.*, u.name as author_name, u.username, u.avatar
            FROM posts p
            JOIN users u ON p.teacher_id = u.id
            WHERE p.title LIKE ? OR p.content LIKE ? OR p.tags LIKE ?
            ORDER BY p.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $results['posts'] = $stmt->fetchAll();
    }
    
    // Поиск уроков
    if ($filter === 'all' || $filter === 'lessons') {
        $stmt = $pdo->prepare("
            SELECT l.*, u.name as teacher_name, u.username, u.avatar, tp.subjects
            FROM lessons l
            JOIN users u ON l.teacher_id = u.id
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE l.title LIKE ? OR l.description LIKE ?
            ORDER BY l.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        $results['lessons'] = $stmt->fetchAll();
    }
}

$current_page = 'search';
?>
<?php include 'includes/header.php'; ?>
<title>Поиск - AQUM</title>
<link rel="stylesheet" href="/style/search.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="search-container">
            <!-- Заголовок и поиск -->
            <div class="search-header">
                <h1>Поиск</h1>
                <form method="GET" class="search-form">
                    <div class="search-input-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" name="q" value="<?= e($query) ?>" 
                               placeholder="Преподаватели, курсы, посты..." 
                               class="search-input" autofocus>
                        <?php if ($query): ?>
                        <a href="search.php" class="search-clear">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Фильтры -->
            <div class="search-filters">
                <a href="?q=<?= urlencode($query) ?>&filter=all" 
                   class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">Все</a>
                <a href="?q=<?= urlencode($query) ?>&filter=teachers" 
                   class="filter-btn <?= $filter === 'teachers' ? 'active' : '' ?>">Преподаватели</a>
                <a href="?q=<?= urlencode($query) ?>&filter=posts" 
                   class="filter-btn <?= $filter === 'posts' ? 'active' : '' ?>">Посты</a>
                <a href="?q=<?= urlencode($query) ?>&filter=lessons" 
                   class="filter-btn <?= $filter === 'lessons' ? 'active' : '' ?>">Уроки</a>
            </div>
            
            <!-- Результаты -->
            <div class="search-results">
                <?php if (empty($query)): ?>
                <!-- Популярные темы -->
                <div class="popular-section">
                    <h2>Популярные темы</h2>
                    <div class="topics-grid">
                        <?php foreach (SUBJECTS as $code => $subject): ?>
                        <a href="?q=<?= urlencode($subject['name']) ?>" class="topic-card">
                            <span class="topic-icon"><?= $subject['icon'] ?></span>
                            <span class="topic-name"><?= e($subject['name']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php elseif (empty($results['teachers']) && empty($results['posts']) && empty($results['lessons'])): ?>
                <!-- Нет результатов -->
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <h3>Ничего не найдено</h3>
                    <p>Попробуйте изменить запрос</p>
                </div>
                
                <?php else: ?>
                
                <!-- Преподаватели -->
                <?php if (!empty($results['teachers']) && ($filter === 'all' || $filter === 'teachers')): ?>
                <section class="results-section">
                    <h2>Преподаватели <span class="count"><?= count($results['teachers']) ?></span></h2>
                    <div class="teachers-grid">
                        <?php foreach ($results['teachers'] as $teacher): ?>
                        <a href="profile.php?u=<?= e($teacher['username']) ?>" class="teacher-card glass">
                            <?= getAvatar($teacher['avatar'], $teacher['name'], 64) ?>
                            <h3><?= e($teacher['name']) ?></h3>
                            <p class="teacher-subjects"><?= e(str_replace(',', ', ', $teacher['subjects'])) ?></p>
                            <div class="teacher-stats">
                                <?php if ($teacher['rating'] && $teacher['reviews_count'] >= 10): ?>
                                <span>⭐ <?= number_format($teacher['rating'], 1) ?></span>
                                <?php endif; ?>
                                <span>📚 <?= $teacher['orders_count'] ?> заказов</span>
                            </div>
                            <?php if ($teacher['hourly_rate']): ?>
                            <div class="teacher-price"><?= formatPrice($teacher['hourly_rate']) ?>/час</div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Посты -->
                <?php if (!empty($results['posts']) && ($filter === 'all' || $filter === 'posts')): ?>
                <section class="results-section">
                    <h2>Посты <span class="count"><?= count($results['posts']) ?></span></h2>
                    <div class="posts-list">
                        <?php foreach ($results['posts'] as $post): ?>
                        <article class="post-card glass">
                            <div class="post-header">
                                <?= getAvatar($post['avatar'], $post['author_name'], 40) ?>
                                <div>
                                    <a href="profile.php?u=<?= e($post['username']) ?>" class="post-author"><?= e($post['author_name']) ?></a>
                                    <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                                </div>
                            </div>
                            <h3><?= e($post['title']) ?></h3>
                            <p><?= e(truncate($post['content'], 150)) ?></p>
                            <div class="post-stats">
                                <span>👍 <?= $post['likes_count'] ?></span>
                                <span>💬 <?= $post['comments_count'] ?></span>
                                <span>👁️ <?= $post['views_count'] ?></span>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Уроки -->
                <?php if (!empty($results['lessons']) && ($filter === 'all' || $filter === 'lessons')): ?>
                <section class="results-section">
                    <h2>Уроки <span class="count"><?= count($results['lessons']) ?></span></h2>
                    <div class="lessons-grid">
                        <?php foreach ($results['lessons'] as $lesson): ?>
                        <div class="lesson-card glass">
                            <div class="lesson-header">
                                <?= getAvatar($lesson['avatar'], $lesson['teacher_name'], 40) ?>
                                <div>
                                    <a href="profile.php?u=<?= e($lesson['username']) ?>"><?= e($lesson['teacher_name']) ?></a>
                                    <span><?= e($lesson['subjects']) ?></span>
                                </div>
                            </div>
                            <h3><?= e($lesson['title']) ?></h3>
                            <p><?= e(truncate($lesson['description'], 120)) ?></p>
                            <div class="lesson-meta">
                                <span class="lesson-type"><?= $lesson['type'] === 'individual' ? '1:1' : 'Группа' ?></span>
                                <span class="lesson-price"><?= formatPrice($lesson['price']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
