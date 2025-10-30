<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

// Проверяем, не подана ли уже заявка
$stmt = $pdo->prepare("SELECT * FROM teacher_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$existing_application = $stmt->fetch();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Ошибка безопасности";
    } else {
        $subject = trim($_POST['subject']);
        $description = trim($_POST['description']);
        $experience_years = (int)$_POST['experience_years'];
        $education = trim($_POST['education']);
        $certificates = trim($_POST['certificates']);
        $video_url = trim($_POST['video_url']);
        
        if (strlen($description) < 100) {
            $error = "Описание должно быть не менее 100 символов";
        } elseif (strlen($description) > 500) {
            $error = "Описание не должно превышать 500 символов";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO teacher_applications 
                    (user_id, subject, description, experience_years, education, certificates, video_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $subject, $description, $experience_years, $education, $certificates, $video_url]);
                
                $success = "Заявка успешно отправлена! Мы рассмотрим её в ближайшее время.";
                
                // Обновляем данные
                $stmt = $pdo->prepare("SELECT * FROM teacher_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$user_id]);
                $existing_application = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Ошибка отправки заявки. Попробуйте позже.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Стать репетитором - WayBels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboard.css">
    <link rel="stylesheet" href="style/profile.css">
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <a href="profile.php" class="back-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Назад
                </a>
                <h1>Стать репетитором</h1>
            </div>
        </header>
        
        <div class="profile-container">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if ($existing_application && $existing_application['status'] === 'pending'): ?>
                <div class="alert alert-info">
                    <h3>Ваша заявка находится на рассмотрении</h3>
                    <p>Мы свяжемся с вами в ближайшее время.</p>
                    <p><strong>Дата подачи:</strong> <?= date('d.m.Y H:i', strtotime($existing_application['created_at'])) ?></p>
                </div>
            <?php elseif ($existing_application && $existing_application['status'] === 'rejected'): ?>
                <div class="alert alert-error">
                    <h3>Ваша заявка отклонена</h3>
                    <?php if ($existing_application['admin_comment']): ?>
                        <p><strong>Комментарий:</strong> <?= e($existing_application['admin_comment']) ?></p>
                    <?php endif; ?>
                    <p>Вы можете подать заявку повторно.</p>
                </div>
            <?php endif; ?>
            
            <div class="profile-section">
                <h2>Заявка на репетиторство</h2>
                <p class="section-description">Заполните форму, чтобы стать репетитором на нашей платформе.</p>
                
                <form method="POST" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    
                    <div class="form-group">
                        <label for="subject">Предмет преподавания *</label>
                        <input type="text" id="subject" name="subject" placeholder="Например: Математика, Английский язык" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">О себе и методике преподавания * (100-500 символов)</label>
                        <textarea id="description" name="description" rows="5" maxlength="500" required 
                                  placeholder="Расскажите о своем опыте, подходе к обучению и чем вы можете помочь ученикам..."></textarea>
                        <small id="char-count">0 / 500</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="experience_years">Опыт преподавания (лет) *</label>
                        <input type="number" id="experience_years" name="experience_years" min="0" max="50" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="education">Образование *</label>
                        <textarea id="education" name="education" rows="3" required 
                                  placeholder="Укажите ваше образование, университет, специальность..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="certificates">Сертификаты и достижения</label>
                        <textarea id="certificates" name="certificates" rows="3" 
                                  placeholder="Опишите ваши сертификаты, награды, дополнительное обучение..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="video_url">Ссылка на видео-презентацию</label>
                        <input type="url" id="video_url" name="video_url" 
                               placeholder="YouTube или другая ссылка на видео">
                        <small>Рекомендуем добавить короткое видео о себе (2-3 минуты)</small>
                    </div>
                    
                    <button type="submit" name="submit_application" class="btn-primary">
                        Отправить заявку
                    </button>
                </form>
            </div>
        </div>
    </main>
    
    <?php include 'includes/bottom_nav.php'; ?>
    
    <script>
    // Счетчик символов
    const description = document.getElementById('description');
    const charCount = document.getElementById('char-count');
    
    description.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = `${length} / 500`;
        
        if (length < 100) {
            charCount.style.color = '#FF3B30';
        } else {
            charCount.style.color = '#34C759';
        }
    });
    </script>
</body>
</html>
