<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$success = null;
$error = null;

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Обновление профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Ошибка безопасности";
    } else {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio']);
        
        // Валидация
        if (validateName($name) && validateEmail($email)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, username = ?, email = ?, phone = ?, bio = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $username, $email, $phone, $bio, $user_id]);
                
                $success = "Профиль успешно обновлен";
                
                // Обновляем данные
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Ошибка обновления профиля";
            }
        } else {
            $error = "Проверьте правильность данных";
        }
    }
}

// Смена пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Ошибка безопасности";
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $password_errors = validatePassword($new_password);
                if (empty($password_errors)) {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $user_id]);
                    $success = "Пароль успешно изменен";
                } else {
                    $error = implode('. ', $password_errors);
                }
            } else {
                $error = "Пароли не совпадают";
            }
        } else {
            $error = "Неверный текущий пароль";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboard.css">
    <link rel="stylesheet" href="style/profile.css">
</head>
<body>
    
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Мой профиль</h1>
            </div>
        </header>
        
        <div class="profile-container">
            <!-- Аватар -->
            <div class="profile-avatar-section">
                <div class="avatar-wrapper">
                    <img src="<?= e($user['avatar']) ?>" alt="<?= e($user['name']) ?>" id="avatar-preview">
                    <button class="avatar-edit-btn" onclick="document.getElementById('avatar-upload').click()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                    </button>
                    <input type="file" id="avatar-upload" accept="image/*" style="display: none;">
                </div>
                <p class="avatar-hint">Нажмите, чтобы изменить фото</p>
            </div>
            
            <!-- Сообщения -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <!-- Основная информация -->
            <div class="profile-section">
                <h2>Основная информация</h2>
                <form method="POST" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Имя</label>
                            <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Никнейм</label>
                            <input type="text" id="username" name="username" value="<?= e($user['username']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Телефон</label>
                            <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">О себе</label>
                        <textarea id="bio" name="bio" rows="3"><?= e($user['bio']) ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn-primary">
                        Сохранить изменения
                    </button>
                </form>
            </div>
            
            <!-- Смена пароля -->
            <div class="profile-section">
                <h2>Безопасность</h2>
                <form method="POST" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    
                    <div class="form-group">
                        <label for="current_password">Текущий пароль</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Новый пароль</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-secondary">
                        Изменить пароль
                    </button>
                </form>
            </div>
            
            <!-- Заявка на репетитора -->
            <?php if ($user['role'] === 'user'): ?>
            <div class="profile-section">
                <h2>Стать репетитором</h2>
                <p class="section-description">Хотите делиться знаниями и зарабатывать? Подайте заявку на становление репетитором.</p>
                <a href="apply_teacher.php" class="btn-primary">
                    Подать заявку
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/bottom_nav.php'; ?>
    
    <script src="js/profile.js"></script>
</body>
</html>
