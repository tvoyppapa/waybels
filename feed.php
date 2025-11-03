<?php
/**
 * Лента aqum - Главная страница
 * Персонализированная лента с постами от репетиторов
 */

session_start();
require_once 'db.php';
require_once 'helpers.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Параметры страницы
$page_title = "Лента";
$page_css = ['feed.css'];
$page_js = ['feed.js'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Получаем интересы и взаимодействия пользователя для персонализации
$user_interests = $user['interests'] ?? null;

// Параметры пагинации
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// ===============================================
// АЛГОРИТМ РЕКОМЕНДАЦИЙ
// ===============================================
/**
 * Алгоритм формирования персонализированной ленты:
 * 1. Посты от репетиторов по интересам пользователя (вес 40%)
 * 2. Популярные посты (много лайков/просмотров) (вес 30%)
 * 3. Посты от избранных репетиторов (вес 20%)
 * 4. Новые посты (вес 10%)
 */

$query = "
    SELECT DISTINCT
        p.*,
        u.name as author_name,
        u.avatar as author_avatar,
        u.nickname as author_nickname,
        tp.subject as author_subject,
        tp.rating as author_rating,
        (
            SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id AND pl.user_id = ?
        ) as user_liked,
        (
            CASE 
                -- Посты по интересам пользователя
                WHEN u.interests = ? THEN 40
                -- Популярные посты (лайки + просмотры)
                WHEN p.likes_count > 50 OR p.views_count > 500 THEN 30
                -- Посты от избранных репетиторов
                WHEN EXISTS (
                    SELECT 1 FROM favorites f 
                    WHERE f.user_id = ? AND f.teacher_id = p.teacher_id
                ) THEN 20
                -- Новые посты (за последние 24 часа)
                WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 10
                ELSE 5
            END
        ) as relevance_score
    FROM posts p
    INNER JOIN users u ON p.teacher_id = u.id
    INNER JOIN teacher_profiles tp ON u.id = tp.user_id
    WHERE 
        p.is_published = 1 
        AND p.published_at <= NOW()
        AND tp.status = 'active'
        AND tp.is_approved = 1
        -- Исключаем репетиторов, неактивных более 24 часов
        AND u.last_seen_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY 
        relevance_score DESC,
        p.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($query);
$stmt->execute([
    $user_id, 
    $user_interests, 
    $user_id, 
    $limit, 
    $offset
]);
$posts = $stmt->fetchAll();

// Получаем общее количество постов для пагинации
$count_query = "
    SELECT COUNT(DISTINCT p.id)
    FROM posts p
    INNER JOIN users u ON p.teacher_id = u.id
    INNER JOIN teacher_profiles tp ON u.id = tp.user_id
    WHERE 
        p.is_published = 1 
        AND p.published_at <= NOW()
        AND tp.status = 'active'
        AND tp.is_approved = 1
        AND u.last_seen_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
";
$total_posts = $pdo->query($count_query)->fetchColumn();
$total_pages = ceil($total_posts / $limit);

// Обработка AJAX запросов
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'like') {
        $post_id = (int)$_POST['post_id'];
        
        // Проверяем, лайкнул ли уже пользователь
        $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        if ($stmt->fetch()) {
            // Удаляем лайк
            $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);
            
            // Уменьшаем счетчик
            $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count - 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            
            // Удаляем взаимодействие
            $stmt = $pdo->prepare("DELETE FROM user_interactions WHERE user_id = ? AND interaction_type = 'like' AND target_type = 'post' AND target_id = ?");
            $stmt->execute([$user_id, $post_id]);
            
            echo json_encode(['success' => true, 'liked' => false]);
        } else {
            // Добавляем лайк
            $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $user_id]);
            
            // Увеличиваем счетчик
            $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            
            // Записываем взаимодействие для алгоритма
            $stmt = $pdo->prepare("
                INSERT INTO user_interactions (user_id, interaction_type, target_type, target_id) 
                VALUES (?, 'like', 'post', ?)
            ");
            $stmt->execute([$user_id, $post_id]);
            
            echo json_encode(['success' => true, 'liked' => true]);
        }
    }
    
    exit;
}

// Подключаем layout
require_once 'includes/layout.php';
?>

<!-- Лента постов -->
<div class="feed-container">
    
    <?php if (empty($posts)): ?>
    <!-- Пустое состояние -->
    <div class="empty-feed">
        <div class="empty-feed-icon">📚</div>
        <h2>Пока здесь тихо...</h2>
        <p>Подпишитесь на репетиторов, чтобы видеть их посты в ленте</p>
        <a href="/search.php" class="btn-primary">Найти репетиторов</a>
    </div>
    
    <?php else: ?>
    
    <!-- Посты -->
    <div class="posts-feed">
        <?php foreach ($posts as $post): ?>
        <article class="post-card" data-post-id="<?= $post['id'] ?>">
            <!-- Хедер поста -->
            <div class="post-header">
                <a href="/teacher.php?id=<?= $post['teacher_id'] ?>" class="post-author">
                    <?php if ($post['author_avatar']): ?>
                    <img src="<?= e($post['author_avatar']) ?>" alt="<?= e($post['author_name']) ?>" class="author-avatar">
                    <?php else: ?>
                    <div class="author-avatar author-avatar-placeholder">
                        <?= strtoupper(mb_substr($post['author_name'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="author-info">
                        <div class="author-name">
                            <?= e($post['author_name']) ?>
                            <?php if ($post['author_nickname']): ?>
                            <span class="author-nickname">@<?= e($post['author_nickname']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="author-meta">
                            <span class="author-subject"><?= e($post['author_subject']) ?></span>
                            <span class="post-date"><?= timeAgo($post['created_at']) ?></span>
                        </div>
                    </div>
                </a>
                
                <button class="post-menu-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"/>
                        <circle cx="19" cy="12" r="1"/>
                        <circle cx="5" cy="12" r="1"/>
                    </svg>
                </button>
            </div>
            
            <!-- Контент поста -->
            <div class="post-content">
                <?php if ($post['title']): ?>
                <h3 class="post-title"><?= e($post['title']) ?></h3>
                <?php endif; ?>
                
                <div class="post-text"><?= nl2br(e($post['content'])) ?></div>
                
                <?php if ($post['media_type'] !== 'none' && $post['media_urls']): ?>
                <div class="post-media">
                    <?php
                    $media_urls = json_decode($post['media_urls'], true);
                    if ($post['media_type'] === 'image' && is_array($media_urls)):
                    ?>
                        <div class="post-images">
                            <?php foreach ($media_urls as $url): ?>
                            <img src="<?= e($url) ?>" alt="Post media" class="post-image">
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($post['media_type'] === 'video'): ?>
                        <video controls class="post-video">
                            <source src="<?= e($media_urls[0]) ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($post['tags']): ?>
                <div class="post-tags">
                    <?php foreach (explode(',', $post['tags']) as $tag): ?>
                    <a href="/search.php?q=<?= urlencode(trim($tag)) ?>" class="post-tag">
                        #<?= e(trim($tag)) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Футер поста (лайки, комменты) -->
            <div class="post-footer">
                <div class="post-stats">
                    <button class="post-action <?= $post['user_liked'] ? 'active' : '' ?>" data-action="like">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span class="likes-count"><?= $post['likes_count'] ?></span>
                    </button>
                    
                    <button class="post-action" data-action="comment">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span><?= $post['comments_count'] ?></span>
                    </button>
                    
                    <button class="post-action" data-action="share">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="18" cy="5" r="3"/>
                            <circle cx="6" cy="12" r="3"/>
                            <circle cx="18" cy="19" r="3"/>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                        </svg>
                    </button>
                </div>
                
                <div class="post-views">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span><?= $post['views_count'] ?></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    
    <!-- Пагинация -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="pagination-btn">← Назад</a>
        <?php endif; ?>
        
        <span class="pagination-info">Страница <?= $page ?> из <?= $total_pages ?></span>
        
        <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>" class="pagination-btn">Вперед →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<?php
// Подключаем футер layout
require_once 'includes/layout_footer.php';
?>
