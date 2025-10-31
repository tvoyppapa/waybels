<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Подключение к БД
$host = "localhost";
$dbname = "wibs";
$username = "root";
$password = "WayBels2553030App!";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}

$error = '';
$debug = '';

// ВХОД
if (isset($_POST['login'])) {
    $debug .= "Обработка ВХОДА<br>";
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $debug .= "Email: $email<br>";
    
    $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $debug .= "Пользователь найден: " . $user['name'] . "<br>";
        
        if (password_verify($password, $user['password'])) {
            $debug .= "Пароль верный!<br>";
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            $debug .= "Сессия установлена. Редирект...<br>";
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Неверный пароль";
            $debug .= "Пароль неверный<br>";
        }
    } else {
        $error = "Пользователь не найден";
        $debug .= "Пользователь не найден в БД<br>";
    }
}

// РЕГИСТРАЦИЯ
if (isset($_POST['register'])) {
    $debug .= "Обработка РЕГИСТРАЦИИ<br>";
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $debug .= "Имя: $name<br>";
    $debug .= "Email: $email<br>";
    
    // Проверка существования
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $error = "Email уже зарегистрирован";
        $debug .= "Email уже существует<br>";
    } else {
        $debug .= "Email свободен, регистрируем...<br>";
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$name, $email, $hashedPassword]);
        
        $user_id = $pdo->lastInsertId();
        $debug .= "Пользователь создан ID: $user_id<br>";
        
        // Автоматический вход
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        
        $debug .= "Сессия установлена. Редирект...<br>";
        
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - WayBels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/auth.css">
</head>
<body>

<!-- ОТЛАДКА -->
<?php if ($debug): ?>
<div style="position: fixed; top: 10px; right: 10px; background: yellow; border: 3px solid red; padding: 15px; z-index: 9999; max-width: 400px; font-size: 14px; font-family: monospace;">
    <strong>🔍 ОТЛАДКА:</strong><br>
    <?= $debug ?>
    <hr>
    POST: <?= $_SERVER['REQUEST_METHOD'] === 'POST' ? 'ДА' : 'НЕТ' ?><br>
    POST data: <?= !empty($_POST) ? 'ЕСТЬ (' . count($_POST) . ')' : 'ПУСТО' ?><br>
    Session user_id: <?= $_SESSION['user_id'] ?? 'НЕТ' ?>
</div>
<?php endif; ?>

<div class="auth-container">
    <!-- Левая часть -->
    <div class="auth-left">
        <div class="promo-content">
            <img src="img/logo-white.svg" alt="WayBels" class="logo">
        </div>
    </div>
    
    <!-- Правая часть -->
    <div class="auth-right">
        <div class="form-container">
            <h2>Добро пожаловать</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <!-- Вкладки -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <button onclick="showLogin()" id="tab-login" style="flex: 1; padding: 12px; background: #7F2CDF; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Вход
                </button>
                <button onclick="showRegister()" id="tab-register" style="flex: 1; padding: 12px; background: #f0f0f0; color: #333; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Регистрация
                </button>
            </div>
            
            <!-- Форма входа -->
            <form method="POST" action="" id="login-form" style="display: block;">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px;">
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px;">
                </div>
                
                <button type="submit" name="login" style="width: 100%; padding: 14px; background: #7F2CDF; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px;">
                    Войти
                </button>
            </form>
            
            <!-- Форма регистрации -->
            <form method="POST" action="" id="register-form" style="display: none;">
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="name" required minlength="2" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px;">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px;">
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required minlength="6" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px;">
                </div>
                
                <button type="submit" name="register" style="width: 100%; padding: 14px; background: #7F2CDF; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px;">
                    Зарегистрироваться
                </button>
            </form>
            
            <p class="switch-text" style="text-align: center; margin-top: 20px;">
                <a href="index.php">← На главную</a>
            </p>
        </div>
    </div>
</div>

<script>
function showLogin() {
    document.getElementById('login-form').style.display = 'block';
    document.getElementById('register-form').style.display = 'none';
    document.getElementById('tab-login').style.background = '#7F2CDF';
    document.getElementById('tab-login').style.color = 'white';
    document.getElementById('tab-register').style.background = '#f0f0f0';
    document.getElementById('tab-register').style.color = '#333';
}

function showRegister() {
    document.getElementById('login-form').style.display = 'none';
    document.getElementById('register-form').style.display = 'block';
    document.getElementById('tab-login').style.background = '#f0f0f0';
    document.getElementById('tab-login').style.color = '#333';
    document.getElementById('tab-register').style.background = '#7F2CDF';
    document.getElementById('tab-register').style.color = 'white';
}
</script>

</body>
</html>
