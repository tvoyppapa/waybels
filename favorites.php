<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Получаем избранных репетиторов
$stmt = $pdo->prepare("
    SELECT u.*, tp.*, f.created_at as favorited_at
    FROM favorites f
    INNER JOIN users u ON f.teacher_id = u.id
    INNER JOIN teacher_profiles tp ON u.id = tp.user_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Избранное - WayBels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboard.css">
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Избранное</h1>
            </div>
        </header>
        
        <div class="teachers-grid">
            <?php if (empty($favorites)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    <h2>Нет избранных</h2>
                    <p>Добавьте репетиторов в избранное</p>
                    <a href="dashboard.php" class="btn-primary">Найти репетитора</a>
                </div>
            <?php else: ?>
                <?php foreach ($favorites as $teacher): ?>
                    <div class="teacher-card">
                        <div class="card-header">
                            <img src="<?= e($teacher['avatar']) ?>" alt="<?= e($teacher['name']) ?>" class="teacher-avatar">
                            <button class="favorite-btn active" data-teacher-id="<?= $teacher['id'] ?>">
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
                            <a href="teacher.php?id=<?= $teacher['id'] ?>" class="btn-view">Подробнее</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/bottom_nav.php'; ?>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
