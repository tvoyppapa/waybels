<?php
/**
 * ПРОСТАЯ РАБОЧАЯ АВТОРИЗАЦИЯ (без квиза)
 */
session_start();

// Если уже авторизован
if (isset($_SESSION['user_id'])) {
    header('Location: feed.php');
    exit;
}

require_once 'db.php';

$error = '';
$success = '';

// Обработка входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Заполните все поля";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Успешный вход
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Обновляем last_seen_at
                $stmt = $pdo->prepare("UPDATE users SET last_seen_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // РЕДИРЕКТ
                header('Location: feed.php');
                exit;
            } else {
                $error = "Неверный email или пароль";
            }
        } catch (PDOException $e) {
            $error = "Ошибка БД: " . $e->getMessage();
        }
    }
}

// Обработка регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Заполните все поля";
    } elseif (strlen($password) < 6) {
        $error = "Пароль минимум 6 символов";
    } else {
        // Форматируем имя
        $name = ucwords(strtolower($name));
        
        try {
            // Проверяем email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Email уже зарегистрирован";
            } else {
                // Создаем пользователя
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at, last_seen_at) VALUES (?, ?, ?, 'user', NOW(), NOW())");
                $stmt->execute([$name, $email, $hashedPassword]);
                
                // Автовход
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = 'user';
                
                // РЕДИРЕКТ
                header('Location: feed.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Ошибка регистрации: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - aqum</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-left">
        <div class="promo-content">
            <img src="img/logo-white.svg" alt="aqum" class="logo">
            <h1 style="color: white; font-size: 48px; font-weight: 800; margin-top: 24px;">aqum</h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 18px; margin-top: 12px;">Образовательная платформа</p>
        </div>
    </div>
    
    <div class="auth-right">
        <div class="form-container">
            <div class="form-header">
                <h2>Добро пожаловать</h2>
                <p class="welcome-subtitle">Войдите или зарегистрируйтесь</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Форма входа -->
            <form method="POST" action="auth_simple_working.php" id="login-form" class="auth-form">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="test@test.com" required>
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" placeholder="password" required>
                </div>
                
                <button type="submit" name="login" class="btn-primary">Войти</button>
                
                <p class="switch-text">
                    Нет аккаунта? <a href="#" id="show-register">Зарегистрироваться</a>
                </p>
            </form>

            <!-- Форма регистрации -->
            <form method="POST" action="auth_simple_working.php" id="register-form" class="auth-form" style="display:none;">
                <h3 class="form-title">Регистрация</h3>
                
                <div class="form-group">
                    <label>Имя *</label>
                    <input type="text" name="name" placeholder="Ваше имя" required minlength="2">
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                
                <div class="form-group">
                    <label>Пароль *</label>
                    <input type="password" name="password" placeholder="Минимум 6 символов" required minlength="6">
                </div>
                
                <button type="submit" name="register" class="btn-primary">Зарегистрироваться</button>
                
                <p class="switch-text">
                    Уже есть аккаунт? <a href="#" id="show-login">Войти</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script src="js/auth.js"></script>

</body>
</html>
