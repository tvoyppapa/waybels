<?php
/**
 * Прямая страница регистрации (без квиза)
 */
session_start();
require_once 'db.php';
require_once 'helpers.php';

$error = null;
$success = null;

// Обработка регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // CSRF проверка
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Ошибка безопасности. Попробуйте еще раз.";
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Валидация
        $errors = [];
        
        if (!validateName($name)) {
            $errors[] = "Имя должно содержать минимум 2 символа";
        }
        
        if (!validateEmail($email)) {
            $errors[] = "Неверный формат email";
        }
        
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }
        
        if (!empty($errors)) {
            $error = implode('. ', $errors);
            saveOldInput(['name' => $name, 'email' => $email]);
        } else {
            try {
                // Проверяем существование email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = "Такой email уже зарегистрирован";
                    saveOldInput(['name' => $name, 'email' => $email]);
                } else {
                    // Регистрация нового пользователя
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (name, email, password, role, created_at) 
                         VALUES (?, ?, ?, 'user', NOW())"
                    );
                    $stmt->execute([$name, $email, $hashedPassword]);
                    
                    // Автоматический вход после регистрации
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'user';
                    
                    // Очищаем старые данные
                    clearOldInput();
                    
                    // Регенерируем ID сессии
                    session_regenerate_id(true);
                    
                    redirect('dashboard.php');
                }
            } catch (PDOException $e) {
                $error = "Ошибка регистрации. Попробуйте позже.";
                error_log("Registration error: " . $e->getMessage());
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
    <title>Регистрация - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/auth.css">
</head>
<body>

<div class="auth-container">
    <!-- Левая часть -->
    <div class="auth-left">
        <div class="promo-content">
            <img src="img/logo.svg" alt="<?= APP_NAME ?>" class="logo">
            <div class="promo">
                <h1><?= APP_NAME ?></h1>
                <p>Пространство, где знание превращается в опыт</p>
            </div>
            
            <div class="features">
                <div class="feature-item">
                    <span class="feature-icon">✨</span>
                    <span>Персонализированное обучение</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🎯</span>
                    <span>Достигайте целей быстрее</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🏆</span>
                    <span>Отслеживайте прогресс</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Правая часть -->
    <div class="auth-right">
        <div class="form-container">
            <h2>Регистрация</h2>
            <p class="welcome-subtitle">Создайте аккаунт, чтобы найти репетитора</p>
            
            <!-- Google вход -->
            <a href="google_login.php" class="google-btn">
                <svg class="google-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Регистрация через Google
            </a>
            
            <div class="divider"><span>или</span></div>
            
            <!-- Сообщения -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            
            <!-- Форма регистрации -->
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                
                <div class="form-group">
                    <label for="name">Имя *</label>
                    <input 
                        type="text" 
                        id="name"
                        name="name" 
                        placeholder="Введите ваше имя" 
                        value="<?= e(old('name')) ?>"
                        required
                        autocomplete="name"
                        minlength="2"
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="your@email.com" 
                        value="<?= e(old('email')) ?>"
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль *</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="Минимум 6 символов" 
                        required
                        autocomplete="new-password"
                        minlength="6"
                    >
                    <small class="form-hint">Минимум 6 символов</small>
                </div>
                
                <button type="submit" name="register" class="btn-primary">
                    <span>Зарегистрироваться</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
                
                <p class="switch-text">
                    Уже есть аккаунт? <a href="auth.php">Войти</a>
                </p>
                <p class="switch-text">
                    <a href="index.php">← На главную</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script src="js/auth.js"></script>

</body>
</html>
