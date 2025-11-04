<?php
/**
 * Универсальный поиск aqum
 * Поиск по репетиторам, постам, каналам и группам
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
$page_title = "Поиск";
$page_css = ['search.css'];
$page_js = ['search.js'];

// Параметры поиска
$q = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all'; // all, teachers, posts, channels
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'relevance'; // relevance, rating, price_asc, price_desc, newest

// Результаты поиска
$teachers = [];
$posts = [];
$channels = [];

if ($q) {
    // ===============================================
    // ПОИСК РЕПЕТИТОРОВ
    // ===============================================
    if ($type === 'all' || $type === 'teachers') {
        $teacher_query = "
            SELECT DISTINCT
                u.id,
                u.name,
                u.nickname,
                u.avatar,
                u.last_seen_at,
                tp.subject,
                tp.short_description,
                tp.experience_years,
                tp.hourly_rate,
                tp.rating,
                tp.rating_count,
                tp.total_lessons,
                tp.total_students,
                (
                    SELECT COUNT(*) FROM favorites f WHERE f.teacher_id = u.id AND f.user_id = ?
                ) as is_favorited,
                MATCH(tp.subject, tp.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
            FROM users u
            INNER JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE 
                tp.is_approved = 1 
                AND tp.status = 'active'
                -- Фильтр: не показываем репетиторов оффлайн >24 часов
                AND u.last_seen_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND (
                    u.name LIKE ? 
                    OR u.nickname LIKE ?
                    OR tp.subject LIKE ?
                    OR tp.description LIKE ?
                    OR MATCH(tp.subject, tp.description) AGAINST(? IN NATURAL LANGUAGE MODE)
                )
        ";
        
        $params = [$user_id, $q];
        $search_term = "%$q%";
        array_push($params, $search_term, $search_term, $search_term, $search_term, $q);
        
        // Фильтр по категории
        if ($category) {
            $teacher_query .= " AND u.interests = ?";
            $params[] = $category;
        }
        
        // Фильтр по цене
        if ($min_price) {
            $teacher_query .= " AND tp.hourly_rate >= ?";
            $params[] = $min_price;
        }
        if ($max_price) {
            $teacher_query .= " AND tp.hourly_rate <= ?";
            $params[] = $max_price;
        }
        
        // Сортировка
        switch ($sort) {
            case 'rating':
                // Показываем рейтинг только если >10 уроков
                $teacher_query .= " ORDER BY 
                    CASE WHEN tp.total_lessons > 10 THEN tp.rating ELSE 0 END DESC,
                    tp.total_lessons DESC";
                break;
            case 'price_asc':
                $teacher_query .= " ORDER BY tp.hourly_rate ASC";
                break;
            case 'price_desc':
                $teacher_query .= " ORDER BY tp.hourly_rate DESC";
                break;
            case 'newest':
                $teacher_query .= " ORDER BY tp.created_at DESC";
                break;
            case 'relevance':
            default:
                $teacher_query .= " ORDER BY relevance DESC, tp.rating DESC";
                break;
        }
        
        $teacher_query .= " LIMIT 20";
        
        $stmt = $pdo->prepare($teacher_query);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll();
    }
    
    // ===============================================
    // ПОИСК ПОСТОВ
    // ===============================================
    if ($type === 'all' || $type === 'posts') {
        $posts_query = "
            SELECT 
                p.*,
                u.name as author_name,
                u.nickname as author_nickname,
                u.avatar as author_avatar,
                tp.subject as author_subject,
                MATCH(p.title, p.content, p.tags) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
            FROM posts p
            INNER JOIN users u ON p.teacher_id = u.id
            INNER JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE 
                p.is_published = 1
                AND (
                    p.title LIKE ?
                    OR p.content LIKE ?
                    OR p.tags LIKE ?
                    OR MATCH(p.title, p.content, p.tags) AGAINST(? IN NATURAL LANGUAGE MODE)
                )
            ORDER BY relevance DESC, p.created_at DESC
            LIMIT 10
        ";
        
        $search_term = "%$q%";
        $stmt = $pdo->prepare($posts_query);
        $stmt->execute([$q, $search_term, $search_term, $search_term, $q]);
        $posts = $stmt->fetchAll();
    }
    
    // ===============================================
    // ПОИСК КАНАЛОВ
    // ===============================================
    if ($type === 'all' || $type === 'channels') {
        $channels_query = "
            SELECT 
                c.*,
                u.name as owner_name,
                u.avatar as owner_avatar,
                (
                    SELECT COUNT(*) FROM channel_subscribers cs 
                    WHERE cs.channel_id = c.id AND cs.user_id = ?
                ) as is_subscribed
            FROM channels c
            INNER JOIN users u ON c.owner_id = u.id
            WHERE 
                c.name LIKE ?
                OR c.description LIKE ?
            ORDER BY c.members_count DESC
            LIMIT 10
        ";
        
        $search_term = "%$q%";
        $stmt = $pdo->prepare($channels_query);
        $stmt->execute([$user_id, $search_term, $search_term]);
        $channels = $stmt->fetchAll();
    }
}

// ===============================================
// Smart Match - умный подбор репетитора
// ===============================================
$smart_match_teacher = null;
if (!$q && $type === 'all') {
    // Получаем интересы пользователя и историю взаимодействий
    $stmt = $pdo->prepare("SELECT interests FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_interests = $stmt->fetchColumn();
    
    if ($user_interests) {
        // Подбираем репетитора на основе интересов, бюджета и предыдущих взаимодействий
        $smart_query = "
            SELECT 
                u.*,
                tp.*,
                (
                    -- Рассчитываем score на основе множества факторов
                    (CASE WHEN u.interests = ? THEN 50 ELSE 0 END) +
                    (CASE WHEN tp.total_lessons > 50 THEN 20 ELSE 0 END) +
                    (CASE WHEN tp.rating > 4.5 AND tp.total_lessons > 10 THEN 15 ELSE 0 END) +
                    (CASE WHEN u.last_seen_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 10 ELSE 0 END) +
                    (CASE WHEN tp.hourly_rate BETWEEN 1000 AND 2500 THEN 5 ELSE 0 END)
                ) as match_score
            FROM users u
            INNER JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE 
                tp.is_approved = 1
                AND tp.status = 'active'
                AND u.last_seen_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND u.id NOT IN (
                    SELECT teacher_id FROM favorites WHERE user_id = ?
                )
            ORDER BY match_score DESC, tp.rating DESC
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($smart_query);
        $stmt->execute([$user_interests, $user_id]);
        $smart_match_teacher = $stmt->fetch();
    }
}

// Подключаем layout
require_once 'includes/layout.php';
?>

<!-- Поиск -->
<div class="search-container">
    
    <!-- Форма поиска -->
    <div class="search-header">
        <form method="GET" action="search.php" class="search-form-main">
            <div class="search-input-wrapper">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input 
                    type="text" 
                    name="q" 
                    class="search-input-main" 
                    placeholder="Поиск репетиторов, постов, каналов..." 
                    value="<?= e($q) ?>"
                    autofocus
                >
                <?php if ($q): ?>
                <a href="search.php" class="search-clear">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Фильтры -->
            <div class="search-filters">
                <select name="type" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Все</option>
                    <option value="teachers" <?= $type === 'teachers' ? 'selected' : '' ?>>Репетиторы</option>
                    <option value="posts" <?= $type === 'posts' ? 'selected' : '' ?>>Посты</option>
                    <option value="channels" <?= $type === 'channels' ? 'selected' : '' ?>>Каналы</option>
                </select>
                
                <?php if ($type === 'all' || $type === 'teachers'): ?>
                <select name="category" class="filter-select" onchange="this.form.submit()">
                    <option value="">Все категории</option>
                    <option value="languages" <?= $category === 'languages' ? 'selected' : '' ?>>🌍 Языки</option>
                    <option value="programming" <?= $category === 'programming' ? 'selected' : '' ?>>💻 Программирование</option>
                    <option value="design" <?= $category === 'design' ? 'selected' : '' ?>>🎨 Дизайн</option>
                    <option value="marketing" <?= $category === 'marketing' ? 'selected' : '' ?>>📈 Маркетинг</option>
                    <option value="growth" <?= $category === 'growth' ? 'selected' : '' ?>>🌿 Личностный рост</option>
                </select>
                
                <select name="sort" class="filter-select" onchange="this.form.submit()">
                    <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>По релевантности</option>
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Цена: по возрастанию</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цена: по убыванию</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
                </select>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Smart Match -->
    <?php if ($smart_match_teacher): ?>
    <div class="smart-match-card">
        <div class="smart-match-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            Smart Match
        </div>
        <h3>Мы подобрали для вас идеального репетитора!</h3>
        <div class="teacher-card featured">
            <?php include 'includes/teacher_card.php'; 
            $teacher = $smart_match_teacher;
            renderTeacherCard($teacher, $user_id);
            ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Результаты поиска -->
    <?php if ($q): ?>
    <div class="search-results">
        <div class="results-header">
            <h2>Результаты поиска: "<?= e($q) ?>"</h2>
        </div>
        
        <!-- Репетиторы -->
        <?php if (!empty($teachers)): ?>
        <section class="results-section">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Репетиторы (<?= count($teachers) ?>)
            </h3>
            <div class="teachers-grid">
                <?php foreach ($teachers as $teacher): ?>
                    <?php renderTeacherCard($teacher, $user_id); ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Посты -->
        <?php if (!empty($posts)): ?>
        <section class="results-section">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="9" y1="9" x2="15" y2="9"/>
                    <line x1="9" y1="13" x2="15" y2="13"/>
                </svg>
                Посты (<?= count($posts) ?>)
            </h3>
            <div class="posts-list-compact">
                <?php foreach ($posts as $post): ?>
                <a href="/feed.php#post-<?= $post['id'] ?>" class="post-item-compact">
                    <div class="post-item-header">
                        <img src="<?= e($post['author_avatar']) ?>" alt="" class="post-item-avatar">
                        <div>
                            <div class="post-item-author"><?= e($post['author_name']) ?></div>
                            <div class="post-item-subject"><?= e($post['author_subject']) ?></div>
                        </div>
                    </div>
                    <div class="post-item-title"><?= e($post['title']) ?></div>
                    <div class="post-item-excerpt"><?= e(mb_substr($post['content'], 0, 150)) ?>...</div>
                    <div class="post-item-meta">
                        ❤️ <?= $post['likes_count'] ?> • 💬 <?= $post['comments_count'] ?> • 👁️ <?= $post['views_count'] ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Каналы -->
        <?php if (!empty($channels)): ?>
        <section class="results-section">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                </svg>
                Каналы (<?= count($channels) ?>)
            </h3>
            <div class="channels-list">
                <?php foreach ($channels as $channel): ?>
                <div class="channel-card">
                    <img src="<?= e($channel['avatar'] ?: '/img/default-avatar.png') ?>" alt="" class="channel-avatar">
                    <div class="channel-info">
                        <h4><?= e($channel['name']) ?></h4>
                        <p><?= e($channel['description']) ?></p>
                        <div class="channel-meta">
                            <?= $channel['members_count'] ?> подписчиков
                        </div>
                    </div>
                    <button class="btn-subscribe <?= $channel['is_subscribed'] ? 'subscribed' : '' ?>">
                        <?= $channel['is_subscribed'] ? 'Подписан' : 'Подписаться' ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if (empty($teachers) && empty($posts) && empty($channels)): ?>
        <div class="no-results">
            <div class="no-results-icon">🔍</div>
            <h3>Ничего не найдено</h3>
            <p>Попробуйте изменить параметры поиска</p>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Подсказки по поиску -->
    <div class="search-hints">
        <h3>Популярные запросы</h3>
        <div class="hints-tags">
            <a href="?q=английский" class="hint-tag">английский</a>
            <a href="?q=программирование" class="hint-tag">программирование</a>
            <a href="?q=дизайн" class="hint-tag">дизайн</a>
            <a href="?q=python" class="hint-tag">python</a>
            <a href="?q=IELTS" class="hint-tag">IELTS</a>
            <a href="?q=маркетинг" class="hint-tag">маркетинг</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Функция отрисовки карточки репетитора
function renderTeacherCard($teacher, $user_id) {
    // Логика: показываем рейтинг только если проведено >10 уроков
    $show_rating = $teacher['total_lessons'] > 10;
    ?>
    <div class="teacher-card" data-teacher-id="<?= $teacher['id'] ?>">
        <div class="card-header">
            <img src="<?= e($teacher['avatar'] ?: '/img/default-avatar.png') ?>" alt="<?= e($teacher['name']) ?>" class="teacher-avatar">
            
            <?php if ($teacher['last_seen_at'] >= date('Y-m-d H:i:s', strtotime('-1 hour'))): ?>
            <span class="online-badge">● Онлайн</span>
            <?php endif; ?>
        </div>
        
        <div class="card-body">
            <h3 class="teacher-name"><?= e($teacher['name']) ?></h3>
            <?php if ($teacher['nickname']): ?>
            <p class="teacher-nickname">@<?= e($teacher['nickname']) ?></p>
            <?php endif; ?>
            
            <p class="teacher-subject"><?= e($teacher['subject']) ?></p>
            
            <?php if ($teacher['short_description']): ?>
            <p class="teacher-short-desc"><?= e($teacher['short_description']) ?></p>
            <?php endif; ?>
            
            <div class="teacher-stats">
                <?php if ($show_rating): ?>
                <div class="stat">
                    ⭐ <strong><?= number_format($teacher['rating'], 1) ?></strong>
                    <span class="stat-label">(<?= $teacher['rating_count'] ?>)</span>
                </div>
                <?php else: ?>
                <div class="stat">
                    <span class="stat-label">Новый репетитор</span>
                </div>
                <?php endif; ?>
                
                <div class="stat">
                    👥 <strong><?= $teacher['total_students'] ?></strong>
                    <span class="stat-label">учеников</span>
                </div>
                
                <div class="stat">
                    📚 <strong><?= $teacher['total_lessons'] ?></strong>
                    <span class="stat-label">уроков</span>
                </div>
            </div>
            
            <div class="teacher-price">
                <strong><?= number_format($teacher['hourly_rate'], 0) ?> ₽</strong> / час
            </div>
        </div>
        
        <div class="card-footer">
            <a href="/teacher.php?id=<?= $teacher['id'] ?>" class="btn-view">
                Подробнее
            </a>
        </div>
    </div>
    <?php
}

// Подключаем футер layout
require_once 'includes/layout_footer.php';
?>
