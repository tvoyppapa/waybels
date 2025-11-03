<?php
session_start();

// Если уже залогинен - на фид
if (isset($_SESSION['user_id'])) {
    header('Location: feed.php');
    exit;
}

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

$error = '';
$success = '';

// ==================================
// ОБРАБОТКА ФОРМ
// ==================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // === ВХОД ===
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Заполните все поля';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // УСПЕХ!
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Обновляем last_seen
                    $pdo->prepare("UPDATE users SET last_seen_at = NOW() WHERE id = ?")->execute([$user['id']]);
                    
                    // РЕДИРЕКТ
                    header('Location: feed.php');
                    exit;
                } else {
                    $error = 'Неверный email или пароль';
                }
            } catch (PDOException $e) {
                $error = 'Ошибка входа: ' . $e->getMessage();
            }
        }
    }
    
    // === РЕГИСТРАЦИЯ ===
    elseif (isset($_POST['register'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Заполните все поля';
        } elseif ($password !== $password_confirm) {
            $error = 'Пароли не совпадают';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль должен быть минимум 6 символов';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Неверный формат email';
        } else {
            try {
                // Проверяем email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = 'Email уже зарегистрирован';
                } else {
                    // Форматируем имя
                    $name = mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
                    
                    // Создаем пользователя
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, role, created_at) 
                        VALUES (?, ?, ?, 'user', NOW())
                    ");
                    $stmt->execute([$name, $email, $hashedPassword]);
                    
                    // Показываем интересы
                    $_SESSION['show_interests'] = true;
                    $_SESSION['temp_user_id'] = $pdo->lastInsertId();
                    
                    $success = 'Регистрация успешна! Выберите интересы';
                }
            } catch (PDOException $e) {
                $error = 'Ошибка регистрации: ' . $e->getMessage();
            }
        }
    }
    
    // === ИНТЕРЕСЫ ===
    elseif (isset($_POST['save_interests'])) {
        $interest = $_POST['interest'] ?? '';
        $userId = $_SESSION['temp_user_id'] ?? null;
        
        if ($userId && $interest) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET interests = ? WHERE id = ?");
                $stmt->execute([$interest, $userId]);
                
                // ЛОГИНИМ
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                unset($_SESSION['show_interests']);
                unset($_SESSION['temp_user_id']);
                
                header('Location: feed.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Ошибка сохранения интересов';
            }
        }
    }
}

$showInterests = isset($_SESSION['show_interests']) && $_SESSION['show_interests'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Вход</title>
    <link rel="stylesheet" href="/style/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-logo">
            <img src="/img/logo.svg" alt="<?php echo APP_NAME; ?>">
            <h1><?php echo APP_NAME; ?></h1>
            <p><?php echo APP_TAGLINE; ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($showInterests): ?>
            <!-- ИНТЕРЕСЫ -->
            <form method="POST" class="auth-form">
                <h2>Выберите ваши интересы</h2>
                <div class="interests-grid">
                    <?php foreach (INTEREST_CATEGORIES as $key => $label): ?>
                        <label class="interest-card">
                            <input type="radio" name="interest" value="<?php echo $key; ?>" required>
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="save_interests" class="btn btn-primary">Продолжить</button>
            </form>
        <?php else: ?>
            <!-- ВХОД -->
            <form method="POST" id="login-form" class="auth-form active">
                <h2>Вход</h2>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary">Войти</button>
                <p class="auth-switch">
                    Нет аккаунта? <a href="#" onclick="showRegister(); return false;">Регистрация</a>
                </p>
            </form>

            <!-- РЕГИСТРАЦИЯ -->
            <form method="POST" id="register-form" class="auth-form" style="display: none;">
                <h2>Регистрация</h2>
                <div class="form-group">
                    <input type="text" name="name" id="register-name" placeholder="Имя Фамилия" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль (минимум 6 символов)" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password_confirm" placeholder="Повторите пароль" required>
                </div>
                <button type="submit" name="register" class="btn btn-primary">Зарегистрироваться</button>
                <p class="auth-switch">
                    Уже есть аккаунт? <a href="#" onclick="showLogin(); return false;">Вход</a>
                </p>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function showRegister() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'block';
        }
        
        function showLogin() {
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('login-form').style.display = 'block';
        }
        
        // Очистка имени от цифр
        const nameInput = document.getElementById('register-name');
        if (nameInput) {
            nameInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[0-9_\.@#$%^&*()+=\[\]{};:'",<>?\/\\|`~]/g, '');
            });
        }
    </script>
</body>
</html>
