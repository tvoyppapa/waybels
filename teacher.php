<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$teacher_id = $_GET['id'] ?? 0;

// Получаем данные репетитора
$stmt = $pdo->prepare("
    SELECT u.*, tp.* 
    FROM users u 
    INNER JOIN teacher_profiles tp ON u.id = tp.user_id 
    WHERE u.id = ? AND tp.is_approved = 1
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    redirect('dashboard.php');
}

// Проверяем, в избранном ли
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND teacher_id = ?");
$stmt->execute([$user_id, $teacher_id]);
$is_favorite = $stmt->fetch() !== false;

// Получаем посты репетитора
$stmt = $pdo->prepare("SELECT * FROM posts WHERE teacher_id = ? ORDER BY created_at DESC LIMIT 6");
$stmt->execute([$teacher_id]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($teacher['name']) ?> - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboard.css">
    <link rel="stylesheet" href="style/teacher.css">
</head>
<body>
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <a href="dashboard.php" class="back-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Назад
                </a>
            </div>
        </header>
        
        <!-- Профиль репетитора -->
        <div class="teacher-profile">
            <!-- Видео презентация -->
            <?php if ($teacher['video_url']): ?>
            <div class="teacher-video">
                <iframe 
                    src="<?= e($teacher['video_url']) ?>" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen
                ></iframe>
            </div>
            <?php endif; ?>
            
            <!-- Основная информация -->
            <div class="teacher-info-section">
                <div class="teacher-header">
                    <img src="<?= e($teacher['avatar']) ?>" alt="<?= e($teacher['name']) ?>" class="teacher-avatar-large">
                    
                    <div class="teacher-header-info">
                        <h1><?= e($teacher['name']) ?></h1>
                        <p class="teacher-subject-large"><?= e($teacher['subject']) ?></p>
                        
                        <div class="teacher-meta">
                            <div class="meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <strong><?= number_format($teacher['rating'], 1) ?></strong>
                                <span>(<?= $teacher['rating_count'] ?> отзывов)</span>
                            </div>
                            
                            <div class="meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <strong><?= $teacher['total_lessons'] ?></strong>
                                <span>уроков</span>
                            </div>
                            
                            <div class="meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <strong><?= $teacher['experience_years'] ?></strong>
                                <span>лет опыта</span>
                            </div>
                        </div>
                        
                        <div class="teacher-actions">
                            <button class="btn-primary" onclick="startChat(<?= $teacher_id ?>)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                Написать
                            </button>
                            
                            <button 
                                class="btn-favorite <?= $is_favorite ? 'active' : '' ?>" 
                                data-teacher-id="<?= $teacher_id ?>"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Цена -->
                <div class="price-card">
                    <div class="price-label">Стоимость занятия</div>
                    <div class="price-value"><?= number_format($teacher['hourly_rate'], 0) ?> ₽ / час</div>
                </div>
            </div>
            
            <!-- Описание -->
            <div class="teacher-section">
                <h2>О репетиторе</h2>
                <p class="teacher-description-full"><?= nl2br(e($teacher['description'])) ?></p>
            </div>
            
            <!-- Посты/Материалы -->
            <?php if (!empty($posts)): ?>
            <div class="teacher-section">
                <h2>Материалы и посты</h2>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                            <?php if ($post['media_type'] === 'image' && $post['media_url']): ?>
                                <img src="<?= e($post['media_url']) ?>" alt="<?= e($post['title']) ?>" class="post-image">
                            <?php endif; ?>
                            
                            <div class="post-content">
                                <h3><?= e($post['title']) ?></h3>
                                <p><?= e(mb_substr($post['content'], 0, 100)) ?>...</p>
                                
                                <div class="post-meta">
                                    <span><?= $post['likes_count'] ?> ❤️</span>
                                    <span><?= $post['views_count'] ?> 👁️</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Bottom Nav -->
    <?php include 'includes/bottom_nav.php'; ?>
    
    <script src="js/teacher.js"></script>
</body>
</html>
