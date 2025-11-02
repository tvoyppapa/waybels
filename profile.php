<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$theme = $user['theme'] ?? 'light';

// Проверяем, является ли пользователь репетитором
$stmt = $pdo->prepare("SELECT * FROM teacher_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$teacher_profile = $stmt->fetch();
$is_teacher = ($teacher_profile !== false);

// Получаем статистику
if ($is_teacher) {
    // Для репетитора
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_students FROM bookings WHERE teacher_id = ? AND status = 'completed' GROUP BY student_id");
    $stmt->execute([$user_id]);
    $stats_students = $stmt->rowCount();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_lessons FROM bookings WHERE teacher_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $stats_lessons = $stmt->fetch()['total_lessons'] ?? 0;
    
    $stats = [
        'students' => $stats_students,
        'lessons' => $stats_lessons,
        'rating' => $teacher_profile['rating'] ?? 0,
        'balance' => $user['balance'] ?? 0
    ];
} else {
    // Для ученика
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_lessons FROM bookings WHERE student_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $stats_lessons = $stmt->fetch()['total_lessons'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_teachers FROM bookings WHERE student_id = ? AND status = 'completed' GROUP BY teacher_id");
    $stmt->execute([$user_id]);
    $stats_teachers = $stmt->rowCount();
    
    $stats = [
        'lessons' => $stats_lessons,
        'teachers' => $stats_teachers,
        'balance' => $user['balance'] ?? 0
    ];
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - WayBels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <style>
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-full);
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 24px;
            transition: all var(--transition);
        }
        
        .back-btn:hover {
            background: var(--glass-bg-strong);
            transform: translateX(-4px);
        }
        
        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 40px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: var(--radius-full);
            border: 4px solid var(--glass-border);
            object-fit: cover;
            margin: 0 auto 20px;
        }
        
        .profile-name {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .profile-username {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }
        
        .profile-bio {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        .profile-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .btn-edit, .btn-share {
            padding: 12px 32px;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-edit {
            background: var(--gradient-primary);
            color: white;
            border: none;
        }
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-share {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
        }
        
        .btn-share:hover {
            background: var(--glass-bg-strong);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .section-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 32px;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .become-teacher-btn {
            width: 100%;
            padding: 20px;
            background: var(--gradient-glass);
            border: 2px dashed var(--primary);
            border-radius: var(--radius-lg);
            text-align: center;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .become-teacher-btn:hover {
            background: var(--gradient-glass-hover);
            transform: translateY(-2px);
        }
        
        .become-teacher-btn h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .become-teacher-btn p {
            font-size: 14px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Назад
        </a>
        
        <!-- Profile Card -->
        <div class="profile-card">
            <img src="<?= e($user['avatar']) ?>" alt="<?= e($user['name']) ?>" class="profile-avatar-large">
            
            <h1 class="profile-name"><?= e($user['name']) ?></h1>
            
            <?php if ($user['username']): ?>
                <p class="profile-username">@<?= e($user['username']) ?></p>
            <?php endif; ?>
            
            <?php if ($user['bio']): ?>
                <p class="profile-bio"><?= nl2br(e($user['bio'])) ?></p>
            <?php endif; ?>
            
            <div class="profile-actions">
                <a href="edit_profile.php" class="btn-edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Редактировать
                </a>
                <button class="btn-share" onclick="shareProfile()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="18" cy="5" r="3"/>
                        <circle cx="6" cy="12" r="3"/>
                        <circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                    </svg>
                    Поделиться
                </button>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <?php if ($is_teacher): ?>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?= $stats['students'] ?></div>
                    <div class="stat-label">Учеников</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-value"><?= $stats['lessons'] ?></div>
                    <div class="stat-label">Уроков проведено</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-value"><?= number_format($stats['rating'], 1) ?></div>
                    <div class="stat-label">Рейтинг</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value"><?= number_format($stats['balance'], 0) ?> ₽</div>
                    <div class="stat-label">Баланс</div>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-value"><?= $stats['lessons'] ?></div>
                    <div class="stat-label">Уроков пройдено</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-value"><?= $stats['teachers'] ?></div>
                    <div class="stat-label">Репетиторов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value"><?= number_format($stats['balance'], 0) ?> ₽</div>
                    <div class="stat-label">Баланс</div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!$is_teacher): ?>
        <!-- Стать репетитором -->
        <div class="section-card">
            <div class="become-teacher-btn" onclick="window.location='apply_teacher_form.php'">
                <h3>🎓 Стать репетитором</h3>
                <p>Делитесь знаниями и зарабатывайте</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function shareProfile() {
            const profileUrl = window.location.origin + '/profile.php?user=<?= $user_id ?>';
            
            if (navigator.share) {
                navigator.share({
                    title: '<?= e($user['name']) ?> - WayBels',
                    text: 'Мой профиль на WayBels',
                    url: profileUrl
                });
            } else {
                navigator.clipboard.writeText(profileUrl);
                alert('✅ Ссылка скопирована в буфер обмена!');
            }
        }
    </script>
</body>
</html>
