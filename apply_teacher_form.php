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
$theme = $user['theme'] ?? 'light';

// Проверяем, не подана ли уже заявка
$stmt = $pdo->prepare("SELECT * FROM teacher_applications WHERE user_id = ?");
$stmt->execute([$user_id]);
$existing_application = $stmt->fetch();

// Проверяем, не является ли уже репетитором
$stmt = $pdo->prepare("SELECT * FROM teacher_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$is_teacher = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $experience = trim($_POST['experience']);
    $education = trim($_POST['education']);
    $hourly_rate = (int)$_POST['hourly_rate'];
    $languages = $_POST['languages'] ?? [];
    
    if (empty($subject) || empty($description) || $hourly_rate < 100) {
        $error = "Заполните все обязательные поля";
    } else {
        if ($existing_application) {
            // Обновляем существующую заявку
            $stmt = $pdo->prepare("
                UPDATE teacher_applications 
                SET subject = ?, description = ?, experience = ?, education = ?, 
                    hourly_rate = ?, languages = ?, status = 'pending', updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $subject, $description, $experience, $education,
                $hourly_rate, json_encode($languages), $user_id
            ]);
            $success = "Заявка обновлена и отправлена на рассмотрение!";
        } else {
            // Создаем новую заявку
            $stmt = $pdo->prepare("
                INSERT INTO teacher_applications 
                (user_id, subject, description, experience, education, hourly_rate, languages, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $user_id, $subject, $description, $experience, $education,
                $hourly_rate, json_encode($languages)
            ]);
            $success = "Заявка успешно отправлена! Мы рассмотрим её в течение 24 часов.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Стать репетитором - WayBels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        
        .hero-card {
            background: var(--gradient-primary);
            border-radius: var(--radius-xl);
            padding: 48px 32px;
            text-align: center;
            color: white;
            margin-bottom: 32px;
        }
        
        .hero-card h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .hero-card p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .benefit-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
        }
        
        .benefit-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        
        .benefit-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .benefit-text {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .form-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 32px;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            background: var(--glass-bg-subtle);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            font-size: 15px;
            font-family: inherit;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .alert {
            padding: 16px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
        }
        
        .alert-success {
            background: rgba(52, 199, 89, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background: rgba(255, 59, 48, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .alert-info {
            background: rgba(0, 122, 255, 0.1);
            border: 1px solid var(--primary);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($is_teacher): ?>
            <div class="alert alert-info">
                <strong>Вы уже являетесь репетитором!</strong> Перейдите в <a href="profile.php">профиль</a> чтобы управлять своими уроками.
            </div>
        <?php elseif ($existing_application && $existing_application['status'] === 'pending'): ?>
            <div class="alert alert-info">
                <strong>Ваша заявка на рассмотрении</strong> Мы свяжемся с вами в течение 24 часов.
            </div>
        <?php endif; ?>
        
        <div class="hero-card">
            <h1>🎓 Станьте репетитором</h1>
            <p>Делитесь знаниями и зарабатывайте</p>
        </div>
        
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">💰</div>
                <div class="benefit-title">Высокий доход</div>
                <div class="benefit-text">Зарабатывайте от 500₽/час</div>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">📅</div>
                <div class="benefit-title">Гибкий график</div>
                <div class="benefit-text">Работайте когда удобно</div>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">🌍</div>
                <div class="benefit-title">Онлайн формат</div>
                <div class="benefit-text">Обучайте из любой точки мира</div>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="form-card">
            <h2 class="form-title">Заявка на репетиторство</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Предмет преподавания *</label>
                    <input type="text" name="subject" class="form-input" required 
                           value="<?= e($existing_application['subject'] ?? '') ?>"
                           placeholder="Например: Английский язык, Математика, Python">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание *</label>
                    <textarea name="description" class="form-textarea" required 
                              placeholder="Расскажите о вашей методике преподавания, целевой аудитории и особенностях обучения"><?= e($existing_application['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Опыт преподавания</label>
                    <textarea name="experience" class="form-textarea" 
                              placeholder="Где и как долго вы преподаете? Какие у вас достижения?"><?= e($existing_application['experience'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Образование</label>
                    <textarea name="education" class="form-textarea" 
                              placeholder="Ваше образование, сертификаты, дипломы"><?= e($existing_application['education'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Стоимость (₽/час) *</label>
                    <input type="number" name="hourly_rate" class="form-input" required min="100" step="50"
                           value="<?= e($existing_application['hourly_rate'] ?? 500) ?>"
                           placeholder="500">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Языки преподавания</label>
                    <select name="languages[]" class="form-select" multiple size="3">
                        <option value="ru">Русский</option>
                        <option value="en">English</option>
                        <option value="es">Español</option>
                        <option value="de">Deutsch</option>
                        <option value="fr">Français</option>
                    </select>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-top: 6px;">
                        Зажмите Ctrl (Cmd на Mac) для выбора нескольких
                    </p>
                </div>
                
                <button type="submit" class="btn-submit">
                    Отправить заявку
                </button>
            </form>
        </div>
    </div>
</body>
</html>
