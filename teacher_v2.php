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

// Получаем данные пользователя (для темы)
$stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$theme = $current_user['theme'] ?? 'light';

// Получаем данные репетитора с username
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

// Проверяем, сохранен ли профиль
$stmt = $pdo->prepare("SELECT id FROM saved_profiles WHERE user_id = ? AND saved_user_id = ?");
$stmt->execute([$user_id, $teacher_id]);
$is_saved = $stmt->fetch() !== false;

// Проверяем, в избранном ли
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND teacher_id = ?");
$stmt->execute([$user_id, $teacher_id]);
$is_favorite = $stmt->fetch() !== false;

// Получаем посты репетитора
$stmt = $pdo->prepare("SELECT * FROM posts WHERE teacher_id = ? ORDER BY created_at DESC LIMIT 6");
$stmt->execute([$teacher_id]);
$posts = $stmt->fetchAll();

// Обработка сохранения профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    if ($is_saved) {
        $stmt = $pdo->prepare("DELETE FROM saved_profiles WHERE user_id = ? AND saved_user_id = ?");
        $stmt->execute([$user_id, $teacher_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO saved_profiles (user_id, saved_user_id) VALUES (?, ?)");
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
    <title><?= e($teacher['name']) ?> - WayBels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: var(--bg-gradient);
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        
        /* Header */
        .header-nav {
            margin-bottom: 32px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-full);
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition);
        }
        
        .back-btn:hover {
            background: var(--glass-bg-strong);
            transform: translateX(-4px);
        }
        
        .back-btn svg {
            width: 20px;
            height: 20px;
        }
        
        /* Profile Card */
        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur-strong));
            -webkit-backdrop-filter: blur(var(--glass-blur-strong));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--glass-shadow-lg);
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--gradient-primary);
        }
        
        .profile-header {
            display: flex;
            gap: 32px;
            margin-bottom: 32px;
        }
        
        .profile-avatar {
            position: relative;
        }
        
        .avatar-large {
            width: 180px;
            height: 180px;
            border-radius: var(--radius-xl);
            border: 4px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
            object-fit: cover;
        }
        
        .online-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            background: var(--success);
            border-radius: var(--radius-full);
            border: 4px solid var(--glass-bg);
            box-shadow: 0 0 0 2px var(--glass-border);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        
        .profile-profession {
            font-size: 20px;
            color: var(--primary);
            font-weight: 600;
            margin: 0 0 12px 0;
        }
        
        .profile-username {
            font-size: 16px;
            color: var(--text-secondary);
            margin: 0 0 24px 0;
        }
        
        .profile-bio {
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        .profile-stats {
            display: flex;
            gap: 32px;
            margin-bottom: 32px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .profile-actions {
            display: flex;
            gap: 16px;
        }
        
        .btn-message, .btn-book {
            flex: 1;
            padding: 16px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .btn-message {
            background: var(--gradient-primary);
            color: white;
            border: none;
        }
        
        .btn-message:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-book {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
        }
        
        .btn-book:hover {
            background: var(--glass-bg-strong);
            transform: translateY(-2px);
        }
        
        .btn-message svg, .btn-book svg {
            width: 20px;
            height: 20px;
        }
        
        .save-profile-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 48px;
            height: 48px;
            border-radius: var(--radius-full);
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .save-profile-btn:hover {
            background: var(--glass-bg-strong);
            transform: scale(1.1);
        }
        
        .save-profile-btn.saved {
            background: var(--gradient-primary);
            border-color: transparent;
        }
        
        .save-profile-btn svg {
            width: 22px;
            height: 22px;
            color: white;
        }
        
        /* Price Card */
        .price-card {
            background: var(--gradient-glass);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
            margin-top: 32px;
        }
        
        .price-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .price-value {
            font-size: 42px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .price-value span {
            font-size: 18px;
            color: var(--text-secondary);
        }
        
        /* About Section */
        .about-section {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 20px 0;
        }
        
        .about-text {
            font-size: 16px;
            line-height: 1.8;
            color: var(--text-secondary);
        }
        
        /* Posts Grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .post-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all var(--transition);
        }
        
        .post-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        
        .post-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .post-content {
            padding: 16px;
        }
        
        .post-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }
        
        .post-text {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0 0 12px 0;
        }
        
        .post-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--text-tertiary);
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-actions {
                flex-direction: column;
            }
            
            .profile-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-nav">
            <a href="dashboard.php" class="back-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Назад
            </a>
        </div>
        
        <!-- Profile Card -->
        <div class="profile-card">
            <button 
                class="save-profile-btn <?= $is_saved ? 'saved' : '' ?>" 
                onclick="toggleSave()"
                title="<?= $is_saved ? 'Сохранено' : 'Сохранить профиль' ?>"
            >
                <svg viewBox="0 0 24 24" fill="<?= $is_saved ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                </svg>
            </button>
            
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?= e($teacher['avatar']) ?>" alt="<?= e($teacher['name']) ?>" class="avatar-large">
                    <?php if ($teacher['is_online']): ?>
                        <div class="online-badge"></div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info">
                    <h1 class="profile-name"><?= e($teacher['name']) ?></h1>
                    <p class="profile-profession"><?= e($teacher['subject']) ?></p>
                    <?php if ($teacher['username']): ?>
                        <p class="profile-username">@<?= e($teacher['username']) ?></p>
                    <?php endif; ?>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?= number_format($teacher['rating'], 1) ?></div>
                            <div class="stat-label">Рейтинг (<?= $teacher['rating_count'] ?>)</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $teacher['total_lessons'] ?></div>
                            <div class="stat-label">Уроков</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $teacher['experience_years'] ?></div>
                            <div class="stat-label">Лет опыта</div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <a href="messages.php?user=<?= $teacher_id ?>" class="btn-message">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            Написать
                        </a>
                        <button class="btn-book" onclick="openBooking()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Записаться
                        </button>
                    </div>
                    
                    <div class="price-card">
                        <div class="price-label">Стоимость занятия</div>
                        <div class="price-value">
                            <?= number_format($teacher['hourly_rate'], 0) ?> ₽ <span>/ час</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- About Section -->
        <div class="about-section">
            <h2 class="section-title">О репетиторе</h2>
            <p class="about-text"><?= nl2br(e($teacher['description'])) ?></p>
        </div>
        
        <!-- Video Section -->
        <?php if ($teacher['video_url']): ?>
        <div class="about-section">
            <h2 class="section-title">Видео-презентация</h2>
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--radius-lg);">
                <iframe 
                    src="<?= e($teacher['video_url']) ?>" 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                    frameborder="0" 
                    allowfullscreen
                ></iframe>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Posts Section -->
        <?php if (!empty($posts)): ?>
        <div class="about-section">
            <h2 class="section-title">Материалы и посты</h2>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <?php if ($post['media_type'] === 'image' && $post['media_url']): ?>
                            <img src="<?= e($post['media_url']) ?>" alt="<?= e($post['title']) ?>" class="post-image">
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h3 class="post-title"><?= e($post['title']) ?></h3>
                            <p class="post-text"><?= e(mb_substr($post['content'], 0, 80)) ?>...</p>
                            
                            <div class="post-meta">
                                <span>❤️ <?= $post['likes_count'] ?></span>
                                <span>👁️ <?= $post['views_count'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Сохранить профиль
        function toggleSave() {
            const btn = document.querySelector('.save-profile-btn');
            
            fetch('teacher_v2.php?id=<?= $teacher_id ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'save_profile=1'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('saved');
                    btn.title = btn.classList.contains('saved') ? 'Сохранено' : 'Сохранить профиль';
                }
            });
        }
        
        // Открыть календарь бронирования
        function openBooking() {
            alert('Календарь бронирования - в разработке!');
            // TODO: Открыть модальное окно с календарем
        }
    </script>
</body>
</html>
