<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Обновляем статус онлайн
$stmt = $pdo->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

$theme = $user['theme'] ?? 'light';

// Категории для быстрого поиска
$categories = [
    'all' => ['icon' => '🌟', 'name' => 'Все'],
    'languages' => ['icon' => '🌍', 'name' => 'Языки'],
    'programming' => ['icon' => '💻', 'name' => 'IT'],
    'design' => ['icon' => '🎨', 'name' => 'Дизайн'],
    'marketing' => ['icon' => '📈', 'name' => 'Маркетинг'],
    'math' => ['icon' => '🔢', 'name' => 'Математика'],
    'music' => ['icon' => '🎵', 'name' => 'Музыка']
];

// Выбранная категория
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'rating';

// Получаем репетиторов
$query = "SELECT u.*, tp.* 
          FROM users u 
          INNER JOIN teacher_profiles tp ON u.id = tp.user_id 
          WHERE tp.is_approved = 1 AND tp.status = 'active'";

if ($category !== 'all') {
    $query .= " AND u.interests = :category";
}

if ($search) {
    $query .= " AND (tp.subject LIKE :search OR tp.description LIKE :search OR u.name LIKE :search)";
}

switch ($sort) {
    case 'lessons':
        $query .= " ORDER BY tp.total_lessons DESC";
        break;
    case 'newest':
        $query .= " ORDER BY tp.created_at DESC";
        break;
    case 'rating':
    default:
        $query .= " ORDER BY tp.rating DESC, tp.rating_count DESC";
        break;
}

$stmt = $pdo->prepare($query);
if ($category !== 'all') {
    $stmt->bindValue(':category', $category);
}
if ($search) {
    $searchTerm = "%$search%";
    $stmt->bindValue(':search', $searchTerm);
}
$stmt->execute();
$teachers = $stmt->fetchAll();

// Получаем избранных
$stmt = $pdo->prepare("SELECT teacher_id FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorites = array_column($stmt->fetchAll(), 'teacher_id');

// AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_favorite'])) {
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
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WayBels - Репетиторы</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Header */
        .header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur-strong));
            -webkit-backdrop-filter: blur(var(--glass-blur-strong));
            border-bottom: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
        }
        
        .header-top {
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 32px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .logo-img {
            width: 40px;
            height: 40px;
        }
        
        .logo-text {
            font-size: 22px;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Категории */
        .categories {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 10px 20px;
            border-radius: var(--radius-full);
            background: transparent;
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .category-btn:hover {
            background: var(--glass-bg-subtle);
            transform: translateY(-2px);
        }
        
        .category-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
        }
        
        /* Search */
        .search-box {
            flex: 1;
            max-width: 400px;
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
            background: var(--glass-bg-subtle);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            font-size: 15px;
            transition: all var(--transition);
        }
        
        .search-input:focus {
            outline: none;
            background: var(--glass-bg);
            border-color: var(--primary);
        }
        
        /* User Menu */
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
            -webkit-backdrop-filter: blur(var(--glass-blur-strong));
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
        
        /* Main Layout */
        .main-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
            padding: 24px 32px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Teachers List */
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
            font-size: 24px;
            font-weight: 700;
        }
        
        .sort-select {
            padding: 8px 32px 8px 12px;
            border-radius: var(--radius-sm);
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            font-size: 14px;
            cursor: pointer;
        }
        
        .teacher-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
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
            flex-shrink: 0;
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
            line-height: 1.5;
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
            margin-bottom: 8px;
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
        
        /* Calendar Sidebar */
        .calendar-sidebar {
            position: sticky;
            top: 90px;
            height: fit-content;
        }
        
        .calendar-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
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
        
        .calendar-placeholder svg {
            width: 64px;
            height: 64px;
            margin-bottom: 12px;
            opacity: 0.5;
        }
        
        @media (max-width: 1200px) {
            .main-container {
                grid-template-columns: 1fr;
            }
            
            .calendar-sidebar {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                padding: 16px;
                gap: 16px;
            }
            
            .categories {
                width: 100%;
                overflow-x: auto;
                flex-wrap: nowrap;
            }
            
            .search-box {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-top">
            <div class="header-left">
                <!-- Logo -->
                <a href="dashboard_new.php" class="logo">
                    <img src="img/logo-white.svg" alt="WayBels" class="logo-img">
                    <span class="logo-text">WayBels</span>
                </a>
                
                <!-- Категории -->
                <nav class="categories">
                    <?php foreach ($categories as $key => $cat): ?>
                        <a href="?category=<?= $key ?>" class="category-btn <?= $category === $key ? 'active' : '' ?>">
                            <span><?= $cat['icon'] ?></span>
                            <span><?= $cat['name'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            
            <div style="display: flex; align-items: center; gap: 16px;">
                <!-- Поиск -->
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
                
                <!-- User Menu -->
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
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <div class="main-container">
        <!-- Teachers List -->
        <div class="teachers-section">
            <div class="section-header">
                <h2 class="section-title">Репетиторы</h2>
                <select class="sort-select" onchange="updateSort(this.value)">
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                    <option value="lessons" <?= $sort === 'lessons' ? 'selected' : '' ?>>По урокам</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
                </select>
            </div>
            
            <?php if (empty($teachers)): ?>
                <div class="teacher-card">
                    <p style="padding: 20px; text-align: center; color: var(--text-secondary);">Репетиторы не найдены</p>
                </div>
            <?php else: ?>
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
                            <button class="btn-book-now" onclick="openBooking(<?= $teacher['id'] ?>)">
                                Записаться
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Calendar Sidebar -->
        <aside class="calendar-sidebar">
            <div class="calendar-card">
                <h3 class="calendar-title">📅 Расписание</h3>
                <div class="calendar-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <p>Календарь в разработке</p>
                    <p style="font-size: 13px; margin-top: 8px;">Скоро здесь будет отображаться расписание и свободные слоты</p>
                </div>
            </div>
        </aside>
    </div>
    
    <script>
        // User menu toggle
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(e.target)) {
                document.getElementById('userDropdown').classList.remove('show');
            }
        });
        
        // Search
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                params.set('search', e.target.value);
                window.location.href = '?' + params.toString();
            }, 500);
        });
        
        // Sort
        function updateSort(value) {
            const params = new URLSearchParams(window.location.search);
            params.set('sort', value);
            window.location.href = '?' + params.toString();
        }
        
        // Booking
        function openBooking(teacherId) {
            alert('Календарь бронирования - в разработке!');
            // TODO: Открыть модальное окно с календарем
        }
    </script>
</body>
</html>
