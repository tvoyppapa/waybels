<?php
/**
 * Страница авторизации и регистрации
 */
require_once 'auth_handler.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Вход - WayBels</title>
  
  <!-- ОТЛАДКА -->
  <style>
    .debug-box {
      position: fixed;
      top: 10px;
      right: 10px;
      background: #ff0;
      border: 2px solid #f00;
      padding: 10px;
      z-index: 9999;
      max-width: 300px;
      font-size: 12px;
    }
  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style/auth.css">
</head>
<body>

<div class="auth-container">
  
  <!-- ОТЛАДКА -->
  <div class="debug-box">
    <strong>ОТЛАДКА:</strong><br>
    <?php 
    echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "POST data: " . (empty($_POST) ? "ПУСТО" : "ЕСТЬ") . "<br>";
    if (!empty($_POST)) {
        echo "POST keys: " . implode(', ', array_keys($_POST)) . "<br>";
    }
    echo "Session interests: " . (isset($_SESSION['interests']) ? $_SESSION['interests'] : "НЕТ") . "<br>";
    echo "Error: " . ($error ?? "НЕТ") . "<br>";
    echo "Success: " . ($success ?? "НЕТ") . "<br>";
    ?>
  </div>
  
  <!-- Левая часть с логотипом -->
  <div class="auth-left">
    <div class="promo-content">
      <img src="img/logo-white.svg" alt="WayBels" class="logo">
    </div>
  </div>
  
  <!-- Правая часть -->
  <div class="auth-right">
    <?php if (!isset($_SESSION['interests'])): ?>
      <!-- Квиз выбора интересов -->
      <div class="quiz-container" id="quiz-section">
        <h2>Что бы вы хотели изучать?</h2>
        <p class="subtitle">Выберите направление, которое вам интересно</p>
        
        <?php if ($error): ?>
          <div class="alert alert-error" role="alert">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        
        <form method="POST" class="quiz-form" id="quiz-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
          
          <?php foreach (INTEREST_CATEGORIES as $key => $category): ?>
            <button 
              type="submit" 
              name="interests" 
              value="<?= htmlspecialchars($key) ?>"
              class="quiz-btn"
            >
              <span class="quiz-icon"><?= $category['icon'] ?></span>
              <span class="quiz-name"><?= htmlspecialchars($category['name']) ?></span>
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
          <?php if (isset($_SESSION['interests'])): ?>
              <p class="selected-interest">
                Выбрано: <strong><?= htmlspecialchars(INTEREST_CATEGORIES[$_SESSION['interests']]['name']) ?></strong>
                <a href="?reset=1" class="reset-interest" title="Изменить выбор">×</a>
              </p>
          <?php endif; ?>
        </div>

        <!-- Google вход -->
        <a href="google_login.php" class="google-btn">
          <svg class="google-icon" viewBox="0 0 24 24" width="20" height="20">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          Войти через Google
        </a>

        <div class="divider"><span>или</span></div>

        <!-- Сообщения об ошибках/успехе -->
        <?php if ($error): ?>
          <div class="alert alert-error" role="alert">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>

        <!-- Форма входа -->
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" id="login-form" class="auth-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
          
          <div class="form-group">
            <label for="login-email">Email</label>
            <input 
              type="email" 
              id="login-email"
              name="email" 
              placeholder="your@email.com" 
              value="<?= htmlspecialchars(old('email')) ?>"
              required
              autocomplete="email"
            >
          </div>
          
          <div class="form-group">
            <label for="login-password">Пароль</label>
            <input 
              type="password" 
              id="login-password"
              name="password" 
              placeholder="Введите пароль" 
              required
              autocomplete="current-password"
            >
          </div>
          
          <button type="submit" name="login" class="btn-primary">
            Войти
          </button>

          <p class="switch-text">
            Нет аккаунта? <a href="#" id="show-register">Зарегистрироваться</a>
          </p>
          <p class="switch-text">
            <a href="reset_password.php">Забыли пароль?</a>
          </p>
        </form>

        <!-- Форма регистрации -->
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" id="register-form" class="auth-form" style="display:none;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
          
          <h3 class="form-title">Регистрация</h3>
          
          <div class="form-group">
            <label for="register-name">Имя *</label>
            <input 
              type="text" 
              id="register-name"
              name="name" 
              placeholder="Введите ваше имя" 
              value="<?= htmlspecialchars(old('name')) ?>"
              required
              autocomplete="name"
              minlength="2"
            >
          </div>
          
          <div class="form-group">
            <label for="register-email">Email *</label>
            <input 
              type="email" 
              id="register-email"
              name="email" 
              placeholder="your@email.com" 
              value="<?= htmlspecialchars(old('email')) ?>"
              required
              autocomplete="email"
            >
          </div>
          
          <div class="form-group">
            <label for="register-password">Пароль *</label>
            <input 
              type="password" 
              id="register-password"
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
