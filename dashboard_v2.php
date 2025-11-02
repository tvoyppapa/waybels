<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя (с новыми полями)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Обновляем статус онлайн
$stmt = $pdo->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

// Получаем тему пользователя
$theme = $user['theme'] ?? 'light';

// Получаем интересы пользователя для фильтрации
$user_interests = $user['interests'] ?? null;

// Поиск и фильтрация
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'rating'; // rating, lessons, newest

// Получаем репетиторов
$query = "SELECT u.*, tp.* 
          FROM users u 
          INNER JOIN teacher_profiles tp ON u.id = tp.user_id 
          WHERE tp.is_approved = 1 AND tp.status = 'active'";

// Фильтр по интересам
if ($user_interests) {
    $query .= " AND u.interests = :interests";
}

// Поиск
if ($search) {
    $query .= " AND (tp.subject LIKE :search OR tp.description LIKE :search OR u.name LIKE :search)";
}

// Сортировка
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
if ($user_interests) {
    $stmt->bindValue(':interests', $user_interests);
}
if ($search) {
    $searchTerm = "%$search%";
    $stmt->bindValue(':search', $searchTerm);
}
$stmt->execute();
$teachers = $stmt->fetchAll();

// Получаем избранных репетиторов пользователя
$stmt = $pdo->prepare("SELECT teacher_id FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorites = array_column($stmt->fetchAll(), 'teacher_id');

// Обработка добавления/удаления из избранного
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
    <title>WayBels - Найди своего репетитора</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <link rel="stylesheet" href="style/dashboard.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: var(--bg-gradient);
            min-height: 100vh;
        }
        
        .layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar с glass эффектом */
        .sidebar-glass {
            width: 280px;
            padding: 24px;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border-right: 1px solid var(--glass-border);
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-section img {
            width: 48px;
            height: 48px;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition);
        }
        
        .nav-link:hover {
            background: var(--glass-bg-subtle);
            color: var(--text-primary);
            transform: translateX(4px);
        }
        
        .nav-link.active {
            background: var(--gradient-glass);
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }
        
        .nav-link svg {
            width: 20px;
            height: 20px;
        }
        
        /* Main content */
        .main-wrapper {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
        }
        
        /* Header с glass */
        .header-glass {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 20px 24px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            box-shadow: var(--glass-shadow);
        }
        
        .header-title {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }
        
        .search-wrapper {
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        
        .search-wrapper svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: var(--text-tertiary);
        }
        
        .search-wrapper input {
            width: 100%;
            padding: 12px 16px 12px 48px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .theme-toggle {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .theme-toggle:hover {
            background: var(--glass-bg-strong);
            transform: scale(1.1);
        }
        
        .theme-toggle svg {
            width: 20px;
            height: 20px;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-avatar-btn {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-full);
            border: 2px solid var(--glass-border);
            background-size: cover;
            background-position: center;
            cursor: pointer;
            transition: all var(--transition);
            box-shadow: var(--shadow-sm);
        }
        
        .user-avatar-btn:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-md);
        }
        
        /* Filters */
        .filters-glass {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .filter-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-item label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .filter-item select {
            padding: 8px 32px 8px 12px;
            border-radius: var(--radius-sm);
            background: var(--glass-bg-subtle);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            font-weight: 500;
            cursor: pointer;
        }
        
        /* Teacher cards grid */
        .teachers-grid-glass {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            animation: fadeIn 0.6s ease-out;
        }
        
        .teacher-card-glass {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--glass-shadow);
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .teacher-card-glass::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity var(--transition);
        }
        
        .teacher-card-glass:hover {
            transform: translateY(-8px);
            box-shadow: var(--glass-shadow-lg);
            border-color: var(--primary);
        }
        
        .teacher-card-glass:hover::before {
            opacity: 1;
        }
        
        .card-header-glass {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .teacher-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-full);
            border: 3px solid var(--glass-border);
            box-shadow: var(--shadow-md);
            object-fit: cover;
        }
        
        .favorite-heart {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .favorite-heart:hover {
            background: var(--glass-bg-strong);
            transform: scale(1.1);
        }
        
        .favorite-heart.active {
            background: var(--gradient-primary);
            border-color: transparent;
        }
        
        .favorite-heart svg {
            width: 18px;
            height: 18px;
            color: white;
        }
        
        .teacher-info {
            flex: 1;
            margin-left: 16px;
        }
        
        .teacher-name-glass {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }
        
        .teacher-subject-glass {
            font-size: 14px;
            color: var(--primary);
            font-weight: 600;
            margin: 0 0 8px 0;
        }
        
        .teacher-description-glass {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 16px;
        }
        
        .teacher-stats-glass {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .stat-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--glass-bg-subtle);
            border-radius: var(--radius-full);
            font-size: 13px;
            font-weight: 600;
        }
        
        .stat-badge svg {
            width: 14px;
            height: 14px;
        }
        
        .teacher-price-glass {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
        }
        
        .teacher-price-glass span {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .card-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-view-glass {
            flex: 1;
            padding: 12px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .btn-view-glass:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .empty-state-glass {
            text-align: center;
            padding: 64px 24px;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
        }
        
        .empty-state-glass svg {
            width: 64px;
            height: 64px;
            color: var(--text-tertiary);
            margin-bottom: 16px;
        }
        
        @media (max-width: 968px) {
            .sidebar-glass {
                display: none;
            }
            
            .main-wrapper {
                padding: 16px;
            }
            
            .teachers-grid-glass {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar-glass">
            <div class="logo-section">
                <img src="img/logo-white.svg" alt="WayBels">
                <span class="logo-text">WayBels</span>
            </div>
            
            <nav class="nav-section">
                <a href="dashboard_v2.php" class="nav-link active">
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
                
                <a href="profile.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Профиль</span>
                </a>
                
                <a href="favorites.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    <span>Избранное</span>
                </a>
            </nav>
            
            <a href="logout.php" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                </svg>
                <span>Выйти</span>
            </a>
        </aside>
        
        <!-- Main Content -->
        <main class="main-wrapper">
            <!-- Header -->
            <header class="header-glass">
                <h1 class="header-title">Репетиторы</h1>
                
                <div class="search-wrapper">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input 
                        type="text" 
                        class="input-glass" 
                        placeholder="Поиск по предмету или имени..."
                        value="<?= e($search) ?>"
                        id="searchInput"
                    >
                </div>
                
                <div class="header-actions">
                    <button class="theme-toggle" onclick="toggleTheme()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                    </button>
                    
                    <div class="user-menu">
                        <a href="profile.php">
                            <div class="user-avatar-btn" style="background-image: url('<?= e($user['avatar']) ?>')"></div>
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- Filters -->
            <div class="filters-glass">
                <div class="filter-item">
                    <label>Сортировка:</label>
                    <select id="sortSelect" onchange="updateSort()">
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                        <option value="lessons" <?= $sort === 'lessons' ? 'selected' : '' ?>>По урокам</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
                    </select>
                </div>
                
                <?php if ($user_interests): ?>
                <div class="badge-glass badge-primary">
                    <?= e($user_interests) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Teachers Grid -->
            <div class="teachers-grid-glass">
                <?php if (empty($teachers)): ?>
                    <div class="empty-state-glass">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <h3>Репетиторы не найдены</h3>
                        <p>Попробуйте изменить фильтры поиска</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($teachers as $teacher): ?>
                        <div class="teacher-card-glass" data-teacher-id="<?= $teacher['id'] ?>">
                            <div class="card-header-glass">
                                <div style="display: flex; align-items: flex-start;">
                                    <img src="<?= e($teacher['avatar']) ?>" alt="<?= e($teacher['name']) ?>" class="teacher-avatar-large">
                                    <div class="teacher-info">
                                        <h3 class="teacher-name-glass"><?= e($teacher['name']) ?></h3>
                                        <p class="teacher-subject-glass"><?= e($teacher['subject']) ?></p>
                                    </div>
                                </div>
                                <button 
                                    class="favorite-heart <?= in_array($teacher['id'], $favorites) ? 'active' : '' ?>" 
                                    onclick="toggleFavorite(<?= $teacher['id'] ?>)"
                                >
                                    <svg viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <p class="teacher-description-glass"><?= e(mb_substr($teacher['description'], 0, 100)) ?>...</p>
                            
                            <div class="teacher-stats-glass">
                                <div class="stat-badge">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                    <span><?= number_format($teacher['rating'], 1) ?> (<?= $teacher['rating_count'] ?>)</span>
                                </div>
                                <div class="stat-badge">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <span><?= $teacher['total_lessons'] ?> уроков</span>
                                </div>
                            </div>
                            
                            <div class="teacher-price-glass">
                                <?= number_format($teacher['hourly_rate'], 0) ?> ₽ <span>/ час</span>
                            </div>
                            
                            <div class="card-actions">
                                <a href="teacher.php?id=<?= $teacher['id'] ?>" class="btn-view-glass">
                                    Подробнее
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Переключение темы
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.dataset.theme || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.dataset.theme = newTheme;
            localStorage.setItem('theme', newTheme);
            
            // Сохраняем в БД
            fetch('api/update_theme.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({theme: newTheme})
            });
        }
        
        // Избранное
        function toggleFavorite(teacherId) {
            const btn = document.querySelector(`[onclick="toggleFavorite(${teacherId})"]`);
            
            fetch('dashboard_v2.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `toggle_favorite=1&teacher_id=${teacherId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('active');
                }
            });
        }
        
        // Поиск
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                window.location.href = `?search=${encodeURIComponent(e.target.value)}`;
            }, 500);
        });
        
        // Сортировка
        function updateSort() {
            const sort = document.getElementById('sortSelect').value;
            window.location.href = `?sort=${sort}`;
        }
        
        // Загружаем сохраненную тему
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.dataset.theme = savedTheme;
        }
    </script>
</body>
</html>
