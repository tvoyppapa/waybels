<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Если пользователь уже авторизован - редирект на dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Подключение к БД
$host = "localhost";
$dbname = "wibs";
$username = "root";
$password = "WayBels2553030App!";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

$error = '';
$success = '';

// ВХОД
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Пожалуйста, заполните все поля";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Обновляем last_login_at
            $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Неверный email или пароль";
        }
    }
}

// РЕГИСТРАЦИЯ
if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Пожалуйста, заполните все поля";
    } elseif (strlen($name) < 2) {
        $error = "Имя должно содержать минимум 2 символа";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некорректный email адрес";
    } elseif (strlen($password) < 6) {
        $error = "Пароль должен содержать минимум 6 символов";
    } elseif ($password !== $password_confirm) {
        $error = "Пароли не совпадают";
    } else {
        // Проверка существования email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Пользователь с таким email уже зарегистрирован";
        } else {
            // Создание нового пользователя
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, avatar) VALUES (?, ?, ?, 'user', '/img/default-avatar.png')");
            $stmt->execute([$name, $email, $hashedPassword]);
            
            $user_id = $pdo->lastInsertId();
            
            // Автоматический вход после регистрации
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход / Регистрация - WayBels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/auth.css">
</head>
<body>

<div class="auth-container">
    <!-- Левая часть (Промо) -->
    <div class="auth-left">
        <div class="promo-content">
            <img src="img/logo-white.svg" alt="WayBels" class="logo">
            <div class="promo">
                <h1>WayBels</h1>
                <p>Пространство, где знание превращается в опыт</p>
            </div>
            
            <div class="features">
                <div class="feature-item">
                    <span class="feature-icon">✨</span>
                    <span>Найдите идеального репетитора</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🎯</span>
                    <span>Учитесь в удобном темпе</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🏆</span>
                    <span>Достигайте целей быстрее</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Правая часть (Форма) -->
    <div class="auth-right">
        <div class="form-container">
            <div class="form-header">
                <h2 id="form-title">Добро пожаловать</h2>
                <p class="welcome-subtitle" id="form-subtitle">Войдите, чтобы продолжить</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Переключатель форм -->
            <div style="display: flex; gap: 12px; margin-bottom: 24px; border-radius: 10px; background: #f5f5f7; padding: 4px;">
                <button onclick="showLogin()" id="tab-login" class="tab-button active" style="flex: 1; padding: 12px; background: white; color: #7F2CDF; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    Вход
                </button>
                <button onclick="showRegister()" id="tab-register" class="tab-button" style="flex: 1; padding: 12px; background: transparent; color: #6e6e73; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                    Регистрация
                </button>
            </div>
            
            <!-- Форма входа -->
            <form method="POST" action="" id="login-form" class="auth-form" style="display: block;">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input 
                        type="email" 
                        id="login-email" 
                        name="email" 
                        placeholder="your@email.com"
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
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Войти
                </button>
            </form>
            
            <!-- Форма регистрации -->
            <form method="POST" action="" id="register-form" class="auth-form" style="display: none;">
                <div class="form-group">
                    <label for="register-name">Имя</label>
                    <input 
                        type="text" 
                        id="register-name" 
                        name="name" 
                        placeholder="Ваше имя"
                        required 
                        minlength="2"
                        autocomplete="name"
                    >
                </div>
                
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input 
                        type="email" 
                        id="register-email" 
                        name="email" 
                        placeholder="your@email.com"
                        required 
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="register-password">Пароль</label>
                    <input 
                        type="password" 
                        id="register-password" 
                        name="password" 
                        placeholder="Минимум 6 символов"
                        required 
                        minlength="6"
                        autocomplete="new-password"
                    >
                    <span class="form-hint">Минимум 6 символов</span>
                </div>
                
                <div class="form-group">
                    <label for="register-password-confirm">Подтвердите пароль</label>
                    <input 
                        type="password" 
                        id="register-password-confirm" 
                        name="password_confirm" 
                        placeholder="Повторите пароль"
                        required 
                        minlength="6"
                        autocomplete="new-password"
                    >
                </div>
                
                <button type="submit" name="register" class="btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                    Зарегистрироваться
                </button>
            </form>
            
            <p class="switch-text">
                <a href="index.php">← Вернуться на главную</a>
            </p>
        </div>
    </div>
</div>

<script>
function showLogin() {
    // Показываем форму входа
    document.getElementById('login-form').style.display = 'block';
    document.getElementById('register-form').style.display = 'none';
    
    // Обновляем заголовки
    document.getElementById('form-title').textContent = 'Добро пожаловать';
    document.getElementById('form-subtitle').textContent = 'Войдите, чтобы продолжить';
    
    // Обновляем стили кнопок
    const loginTab = document.getElementById('tab-login');
    const registerTab = document.getElementById('tab-register');
    
    loginTab.style.background = 'white';
    loginTab.style.color = '#7F2CDF';
    loginTab.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
    
    registerTab.style.background = 'transparent';
    registerTab.style.color = '#6e6e73';
    registerTab.style.boxShadow = 'none';
}

function showRegister() {
    // Показываем форму регистрации
    document.getElementById('login-form').style.display = 'none';
    document.getElementById('register-form').style.display = 'block';
    
    // Обновляем заголовки
    document.getElementById('form-title').textContent = 'Создайте аккаунт';
    document.getElementById('form-subtitle').textContent = 'Присоединяйтесь к нам';
    
    // Обновляем стили кнопок
    const loginTab = document.getElementById('tab-login');
    const registerTab = document.getElementById('tab-register');
    
    loginTab.style.background = 'transparent';
    loginTab.style.color = '#6e6e73';
    loginTab.style.boxShadow = 'none';
    
    registerTab.style.background = 'white';
    registerTab.style.color = '#7F2CDF';
    registerTab.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
}

// Валидация формы регистрации
const registerForm = document.getElementById('register-form');
if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
        const password = document.getElementById('register-password').value;
        const passwordConfirm = document.getElementById('register-password-confirm').value;
        
        if (password !== passwordConfirm) {
            e.preventDefault();
            alert('Пароли не совпадают');
            return false;
        }
    });
}
</script>

</body>
</html>
