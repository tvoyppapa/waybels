<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

$theme = $user['theme'] ?? 'light';

// Категории (без "Все")
$categories = [
    'languages' => ['icon' => '🌍', 'name' => 'Языки'],
    'programming' => ['icon' => '💻', 'name' => 'IT'],
    'design' => ['icon' => '🎨', 'name' => 'Дизайн'],
    'marketing' => ['icon' => '📈', 'name' => 'Маркетинг'],
    'math' => ['icon' => '🔢', 'name' => 'Математика'],
    'music' => ['icon' => '🎵', 'name' => 'Музыка']
];

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'rating';

$query = "SELECT u.*, tp.* 
          FROM users u 
          INNER JOIN teacher_profiles tp ON u.id = tp.user_id 
          WHERE tp.is_approved = 1 AND tp.status = 'active'";

if ($category) {
    $query .= " AND u.interests = :category";
}

if ($search) {
    $query .= " AND (tp.subject LIKE :search OR tp.description LIKE :search OR u.name LIKE :search)";
}

switch ($sort) {
    case 'lessons': $query .= " ORDER BY tp.total_lessons DESC"; break;
    case 'newest': $query .= " ORDER BY tp.created_at DESC"; break;
    case 'price_low': $query .= " ORDER BY tp.hourly_rate ASC"; break;
    case 'price_high': $query .= " ORDER BY tp.hourly_rate DESC"; break;
    default: $query .= " ORDER BY tp.rating DESC, tp.rating_count DESC";
}

$stmt = $pdo->prepare($query);
if ($category) $stmt->bindValue(':category', $category);
if ($search) $stmt->bindValue(':search', "%$search%");
$stmt->execute();
$teachers = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT teacher_id FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorites = array_column($stmt->fetchAll(), 'teacher_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorite'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    if (in_array($teacher_id, $favorites)) {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND teacher_id = ?");
        $stmt->execute([$user_id, $teacher_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, teacher_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $teacher_id]);
    }
    echo json_encode(['success' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WayBels - Репетиторы</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        .layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* SIDEBAR СЛЕВА */
        .sidebar {
            width: 280px;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur-strong));
            -webkit-backdrop-filter: blur(var(--glass-blur-strong));
            border-right: 1px solid var(--glass-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }
        
        .sidebar-logo {
            padding: 24px;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .logo {
            display: inline-block;
            text-decoration: none;
        }
        
        .logo-img {
            width: 48px;
            height: 48px;
        }
        
        /* НАВИГАЦИЯ СВЕРХУ */
        .sidebar-nav {
            padding: 16px;
            flex: 1;
        }
        
        /* КАТЕГОРИИ ВНИЗУ */
        .sidebar-categories {
            padding: 16px;
            border-top: 1px solid var(--glass-border);
        }
        
        .categories-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            padding: 0 8px;
        }
        
        .category-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all var(--transition);
            margin-bottom: 4px;
        }
        
        .category-link:hover {
            background: var(--glass-bg-subtle);
            color: var(--text-primary);
        }
        
        .category-link.active {
            background: var(--gradient-glass);
            color: var(--primary);
            font-weight: 600;
        }
        
        .nav-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            padding: 0 8px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition);
            margin-bottom: 4px;
        }
        
        .nav-link:hover {
            background: var(--glass-bg-subtle);
            color: var(--text-primary);
        }
        
        .nav-link.active {
            background: var(--gradient-primary);
            color: white;
        }
        
        .nav-link svg {
            width: 20px;
            height: 20px;
        }
        
        /* MAIN CONTENT */
        .main-wrapper {
            margin-left: 280px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* HEADER СВЕРХУ */
        .header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur-strong));
            -webkit-backdrop-filter: blur(var(--glass-blur-strong));
            border-bottom: 1px solid var(--glass-border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        
        .search-box {
            flex: 1;
            max-width: 800px;
            position: relative;
        }
        
        .search-box svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: var(--text-tertiary);
            pointer-events: none;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border-radius: var(--radius-full);
            background: transparent;
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            font-size: 15px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-full);
            background-size: cover;
            background-position: center;
            border: 2px solid var(--glass-border);
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
            border-color: var(--primary);
        }
        
        .user-dropdown {
            position: absolute;
            top: 56px;
            right: 0;
            min-width: 220px;
            background: var(--glass-bg-strong);
            backdrop-filter: blur(var(--glass-blur-strong));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--glass-shadow-lg);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all var(--transition);
        }
        
        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-header {
            padding: 16px;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .dropdown-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .dropdown-email {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all var(--transition);
        }
        
        .dropdown-item:hover {
            background: var(--glass-bg-subtle);
        }
        
        .dropdown-item svg {
            width: 18px;
            height: 18px;
        }
        
        .dropdown-divider {
            height: 1px;
            background: var(--glass-border);
            margin: 4px 0;
        }
        
        /* CONTENT LAYOUT */
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
            padding: 24px 32px;
            flex: 1;
        }
        
        .teachers-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .section-title {
            font-size: 28px;
            font-weight: 700;
        }
        
        .sort-select {
            padding: 8px 32px 8px 12px;
            border-radius: var(--radius-sm);
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .teacher-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 20px;
            transition: all var(--transition);
            display: flex;
            gap: 20px;
        }
        
        .teacher-card:hover {
            transform: translateX(4px);
            box-shadow: var(--glass-shadow-lg);
        }
        
        .teacher-avatar {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-lg);
            object-fit: cover;
            border: 2px solid var(--glass-border);
        }
        
        .teacher-info {
            flex: 1;
        }
        
        .teacher-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .teacher-subject {
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .teacher-desc {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }
        
        .teacher-stats {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .teacher-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        
        .teacher-price {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .teacher-price span {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .btn-book-now {
            padding: 10px 24px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .calendar-sidebar {
            position: sticky;
            top: 90px;
            height: fit-content;
        }
        
        .calendar-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 24px;
        }
        
        .calendar-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .calendar-placeholder {
            text-align: center;
            padding: 32px 16px;
            color: var(--text-secondary);
        }
        
        @media (max-width: 1200px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
            .calendar-sidebar {
                position: static;
            }
        }
        
        /* Mobile Bottom Nav */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur-strong));
            -webkit-backdrop-filter: blur(var(--glass-blur-strong));
            border-top: 1px solid var(--glass-border);
            padding: 12px 0;
            z-index: 100;
        }
        
        .bottom-nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 12px;
            transition: all var(--transition);
        }
        
        .bottom-nav-item.active {
            color: var(--primary);
        }
        
        .bottom-nav-item svg {
            width: 24px;
            height: 24px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 200;
                transition: transform var(--transition);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-wrapper {
                margin-left: 0;
            }
            
            .content-wrapper {
                padding-bottom: 80px;
            }
            
            .bottom-nav {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- SIDEBAR СЛЕВА -->
        <aside class="sidebar" id="sidebar">
            <!-- Лого -->
            <div class="sidebar-logo">
                <a href="dashboard.php" class="logo">
                    <img src="img/logo.svg" alt="WayBels" class="logo-img">
                </a>
            </div>
            
            <!-- НАВИГАЦИЯ СВЕРХУ -->
            <nav class="sidebar-nav">
                <div class="nav-title">Меню</div>
                
                <a href="dashboard.php" class="nav-link active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                    <span>Главная</span>
                </a>
                
                <a href="messages.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span>Сообщения</span>
                </a>
            </nav>
            
            <!-- КАТЕГОРИИ ВНИЗУ -->
            <div class="sidebar-categories">
                <div class="categories-title">Категории</div>
                <?php foreach ($categories as $key => $cat): ?>
                    <a href="?category=<?= $key ?>" class="category-link <?= $category === $key ? 'active' : '' ?>">
                        <span><?= $cat['icon'] ?></span>
                        <span><?= $cat['name'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>
        
        <!-- MAIN CONTENT -->
        <div class="main-wrapper">
            <!-- HEADER СВЕРХУ -->
            <header class="header">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Поиск репетиторов..."
                        value="<?= e($search) ?>"
                        id="searchInput"
                    >
                </div>
                
                <div class="user-menu">
                    <div class="user-avatar" style="background-image: url('<?= e($user['avatar']) ?>')" onclick="toggleUserMenu()"></div>
                    
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <div class="dropdown-name"><?= e($user['name']) ?></div>
                            <div class="dropdown-email"><?= e($user['email']) ?></div>
                        </div>
                        
                        <a href="profile.php" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <span>Мой профиль</span>
                        </a>
                        
                        <a href="settings.php" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6m-13.36.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
                            </svg>
                            <span>Настройки</span>
                        </a>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="logout.php" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Выйти</span>
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- CONTENT -->
            <div class="content-wrapper">
                <div class="teachers-section">
                    <div class="section-header">
                        <h2 class="section-title">Репетиторы</h2>
                        <select class="sort-select" onchange="updateSort(this.value)">
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                            <option value="lessons" <?= $sort === 'lessons' ? 'selected' : '' ?>>По урокам</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>По цене (дешевле)</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>По цене (дороже)</option>
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
                        </select>
                    </div>
                    
                    <?php foreach ($teachers as $teacher): ?>
                        <div class="teacher-card">
                            <img src="<?= e($teacher['avatar']) ?>" alt="<?= e($teacher['name']) ?>" class="teacher-avatar">
                            
                            <div class="teacher-info">
                                <h3 class="teacher-name"><?= e($teacher['name']) ?></h3>
                                <div class="teacher-subject"><?= e($teacher['subject']) ?></div>
                                <p class="teacher-desc"><?= e(mb_substr($teacher['description'], 0, 120)) ?>...</p>
                                
                                <div class="teacher-stats">
                                    <span>⭐ <?= number_format($teacher['rating'], 1) ?> (<?= $teacher['rating_count'] ?>)</span>
                                    <span>📚 <?= $teacher['total_lessons'] ?> уроков</span>
                                    <span>⏱️ <?= $teacher['experience_years'] ?> лет</span>
                                </div>
                            </div>
                            
                            <div class="teacher-actions">
                                <div class="teacher-price">
                                    <?= number_format($teacher['hourly_rate'], 0) ?> ₽ <span>/ час</span>
                                </div>
                                <button class="btn-book-now" onclick="window.location='teacher.php?id=<?= $teacher['id'] ?>'">
                                    Подробнее
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <aside class="calendar-sidebar">
                    <div class="calendar-card">
                        <h3 class="calendar-title">📅 Ближайшие уроки</h3>
                        <div id="upcomingLessons"></div>
                    </div>
                    
                    <div class="calendar-card" style="margin-top: 16px;">
                        <h3 class="calendar-title">💡 Совет дня</h3>
                        <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.6;">
                            Попробуйте заниматься регулярно — это эффективнее, чем длинные, но редкие уроки!
                        </p>
                    </div>
                </aside>
            </div>
        </div>
        
        <!-- Mobile Bottom Navigation -->
        <nav class="bottom-nav">
            <div class="bottom-nav-items">
                <a href="dashboard.php" class="bottom-nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                    <span>Главная</span>
                </a>
                
                <a href="messages.php" class="bottom-nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span>Сообщения</span>
                </a>
                
                <a href="profile.php" class="bottom-nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Профиль</span>
                </a>
                
                <button class="bottom-nav-item" onclick="toggleSidebar()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                    <span>Меню</span>
                </button>
            </div>
        </nav>
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        function toggleUserMenu() {
            document.getElementById('userDropdown').classList.toggle('show');
        }
        
        document.addEventListener('click', function(e) {
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(e.target)) {
                document.getElementById('userDropdown').classList.remove('show');
            }
        });
        
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                params.set('search', e.target.value);
                window.location.href = '?' + params.toString();
            }, 500);
        });
        
        function updateSort(value) {
            const params = new URLSearchParams(window.location.search);
            params.set('sort', value);
            window.location.href = '?' + params.toString();
        }
    </script>
    <script src="js/calendar.js"></script>
</body>
</html>
