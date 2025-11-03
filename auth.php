<?php
/**
 * Страница авторизации aqum
 */
session_start();

// Если уже авторизован - редирект на ленту
if (isset($_SESSION['user_id'])) {
    header('Location: feed.php');
    exit;
}

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

$error = '';
$success = '';

// Сброс выбора интересов
if (isset($_GET['reset'])) {
    unset($_SESSION['interests']);
    header('Location: auth.php');
    exit;
}

// === ОБРАБОТКА КВИЗА (выбор интересов) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interests'])) {
    $interests = $_POST['interests'];
    if (array_key_exists($interests, INTEREST_CATEGORIES)) {
        $_SESSION['interests'] = $interests;
        header('Location: auth.php');
        exit;
    }
}

// === ОБРАБОТКА ВХОДА ===
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
                // Вход успешен
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Обновляем last_seen_at
                $stmt = $pdo->prepare("UPDATE users SET last_seen_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Очищаем interests
                unset($_SESSION['interests']);
                
                // РЕДИРЕКТ НА ЛЕНТУ
                header('Location: feed.php');
                exit;
            } else {
                $error = "Неверный email или пароль";
            }
        } catch (PDOException $e) {
            $error = "Ошибка входа";
        }
    }
}

// === ОБРАБОТКА РЕГИСТРАЦИИ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $interests = $_SESSION['interests'] ?? null;
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Заполните все поля";
    } elseif (strlen($password) < 6) {
        $error = "Пароль минимум 6 символов";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Неверный формат email";
    } else {
        // Форматируем имя
        $name = ucwords(strtolower($name));
        
        try {
            // Проверяем существование email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Email уже зарегистрирован";
            } else {
                // Создаем пользователя
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                if ($interests) {
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, interests, role, created_at, last_seen_at) VALUES (?, ?, ?, ?, 'user', NOW(), NOW())");
                    $stmt->execute([$name, $email, $hashedPassword, $interests]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at, last_seen_at) VALUES (?, ?, ?, 'user', NOW(), NOW())");
                    $stmt->execute([$name, $email, $hashedPassword]);
                }
                
                // Автовход после регистрации
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = 'user';
                
                // Очищаем interests
                unset($_SESSION['interests']);
                
                // РЕДИРЕКТ НА ЛЕНТУ
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style/auth.css">
</head>
<body>

<div class="auth-container">
  
  <!-- Левая часть -->
  <div class="auth-left">
    <div class="promo-content">
      <img src="img/logo-white.svg" alt="aqum" class="logo">
      <h1 style="color: white; font-size: 48px; font-weight: 800; margin-top: 24px; letter-spacing: -1px;">aqum</h1>
      <p style="color: rgba(255,255,255,0.9); font-size: 18px; margin-top: 12px;">Образовательная платформа нового поколения</p>
    </div>
  </div>
  
  <!-- Правая часть -->
  <div class="auth-right">
    <?php if (!isset($_SESSION['interests'])): ?>
      <!-- Квиз выбора интересов -->
      <div class="quiz-container">
        <h2>Что бы вы хотели изучать?</h2>
        <p class="subtitle">Выберите направление</p>
        
        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="auth.php" class="quiz-form">
          <?php foreach (INTEREST_CATEGORIES as $key => $category): ?>
            <button type="submit" name="interests" value="<?= $key ?>" class="quiz-btn">
              <span class="quiz-icon"><?= $category['icon'] ?></span>
              <span class="quiz-name"><?= $category['name'] ?></span>
            </button>
          <?php endforeach; ?>
        </form>
        
        <p class="switch-text" style="margin-top: 24px;">
          <a href="index.php">← На главную</a>
        </p>
      </div>

    <?php else: ?>
      <!-- Форма входа/регистрации -->
      <div class="form-container">
        <div class="form-header">
          <h2>Добро пожаловать</h2>
          <p class="welcome-subtitle">Войдите или зарегистрируйтесь</p>
          <p class="selected-interest">
            Выбрано: <strong><?= INTEREST_CATEGORIES[$_SESSION['interests']]['name'] ?></strong>
            <a href="auth.php?reset=1" class="reset-interest">×</a>
          </p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Форма входа -->
        <form method="POST" action="auth.php" id="login-form" class="auth-form">
          <div class="form-group">
            <label for="login-email">Email</label>
            <input type="email" id="login-email" name="email" placeholder="test@test.com" required autocomplete="email">
          </div>
          
          <div class="form-group">
            <label for="login-password">Пароль</label>
            <input type="password" id="login-password" name="password" placeholder="password" required autocomplete="current-password">
          </div>
          
          <button type="submit" name="login" class="btn-primary">Войти</button>

          <p class="switch-text">
            Нет аккаунта? <a href="#" id="show-register">Зарегистрироваться</a>
          </p>
        </form>

        <!-- Форма регистрации -->
        <form method="POST" action="auth.php" id="register-form" class="auth-form" style="display:none;">
          <h3 class="form-title">Регистрация</h3>
          
          <div class="form-group">
            <label for="register-name">Имя *</label>
            <input type="text" id="register-name" name="name" placeholder="Ваше имя" required minlength="2">
          </div>
          
          <div class="form-group">
            <label for="register-email">Email *</label>
            <input type="email" id="register-email" name="email" placeholder="your@email.com" required>
          </div>
          
          <div class="form-group">
            <label for="register-password">Пароль *</label>
            <input type="password" id="register-password" name="password" placeholder="Минимум 6 символов" required minlength="6">
            <small class="form-hint">Минимум 6 символов</small>
          </div>
          
          <button type="submit" name="register" class="btn-primary">
            <span>Зарегистрироваться</span>
          </button>
          
          <p class="switch-text">
            Уже есть аккаунт? <a href="#" id="show-login">Войти</a>
          </p>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="js/auth.js"></script>

</body>
</html>
