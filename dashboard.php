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
        // Удаляем из избранного
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND teacher_id = ?");
        $stmt->execute([$user_id, $teacher_id]);
        $favorites = array_diff($favorites, [$teacher_id]);
    } else {
        // Добавляем в избранное
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, teacher_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $teacher_id]);
        $favorites[] = $teacher_id;
    }
    
    echo json_encode(['success' => true, 'favorited' => !in_array($teacher_id, $favorites)]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Найди своего репетитора</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboard.css">
</head>
<body>
    
    <!-- Боковое меню (Desktop) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="img/logo-white.svg" alt="<?= APP_NAME ?>" class="sidebar-logo">
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Главная</span>
            </a>
            
            <a href="messages.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span>Сообщения</span>
            </a>
            
            <a href="profile.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Профиль</span>
            </a>

            <a href="favorites.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span>Избранное</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Выйти</span>
            </a>
        </div>
    </aside>
    
    <!-- Основной контент -->
    <main class="main-content">
        <!-- Хедер с поиском -->
        <header class="header">
            <div class="header-left">
                <img src="img/logo-white.svg" alt="<?= APP_NAME ?>" class="mobile-logo">
                <h1>Репетиторы</h1>
            </div>
            
            <div class="header-search">
                <form method="GET" action="" class="search-form">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Поиск по предмету или имени..." 
                        value="<?= e($search) ?>"
                    >
                </form>
            </div>
            
            <div class="header-right">
                <div class="user-avatar" style="background-image: url('<?= e($user['avatar']) ?>')"></div>
            </div>
        </header>
        
        <!-- Фильтры и сортировка -->
        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Сортировка:</label>
                <select name="sort" id="sort-select" class="filter-select">
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                    <option value="lessons" <?= $sort === 'lessons' ? 'selected' : '' ?>>По количеству уроков</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
                </select>
            </div>
            
            <?php if ($user_interests): ?>
            <div class="active-filter">
                <span>Фильтр: <?= e($user_interests) ?></span>
                <a href="?reset_filter=1">×</a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Лента репетиторов -->
        <div class="teachers-grid">
            <?php if (empty($teachers)): ?>
                <div class="empty-state">
                    <p>Репетиторы не найдены</p>
                </div>
            <?php else: ?>
                <?php foreach ($teachers as $teacher): ?>
                    <div class="teacher-card" data-teacher-id="<?= $teacher['id'] ?>">
                        <div class="card-header">
                            <img src="<?= e($teacher['avatar']) ?>" alt="<?= e($teacher['name']) ?>" class="teacher-avatar">
                            <button 
                                class="favorite-btn <?= in_array($teacher['id'], $favorites) ? 'active' : '' ?>" 
                                data-teacher-id="<?= $teacher['id'] ?>"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="card-body">
                            <h3 class="teacher-name"><?= e($teacher['name']) ?></h3>
                            <p class="teacher-subject"><?= e($teacher['subject']) ?></p>
                            <p class="teacher-description"><?= e(mb_substr($teacher['description'], 0, 100)) ?>...</p>
                            
                            <div class="teacher-stats">
                                <div class="stat">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                    <span><?= number_format($teacher['rating'], 1) ?> (<?= $teacher['rating_count'] ?>)</span>
                                </div>
                                <div class="stat">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <span><?= $teacher['total_lessons'] ?> уроков</span>
                                </div>
                            </div>
                            
                            <div class="teacher-price">
                                <strong><?= number_format($teacher['hourly_rate'], 0) ?> ₽</strong> / час
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <a href="teacher.php?id=<?= $teacher['id'] ?>" class="btn-view">
                                Подробнее
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Нижнее меню (Mobile) -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="bottom-nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Главная</span>
        </a>
        
        <a href="messages.php" class="bottom-nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span>Сообщения</span>
        </a>
        
        <a href="favorites.php" class="bottom-nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            <span>Избранное</span>
        </a>
        
        <a href="profile.php" class="bottom-nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Профиль</span>
        </a>
    </nav>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
