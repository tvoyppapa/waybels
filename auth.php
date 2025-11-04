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

// Получаем ошибки из сессии и очищаем
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// ==================================
// ОБРАБОТКА ФОРМ
// ==================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // === ВХОД ===
    if (isset($_POST['login_submit'])) {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($login) || empty($password)) {
            $_SESSION['error'] = 'Заполните все поля';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
                $stmt->execute([$login, $login]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    $pdo->prepare("UPDATE users SET last_seen_at = NOW() WHERE id = ?")->execute([$user['id']]);
                    
                    header('Location: feed.php');
                    exit;
                } else {
                    $_SESSION['error'] = 'Неверный email/телефон или пароль';
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Ошибка: ' . $e->getMessage();
            }
        }
        
        header('Location: auth.php');
        exit;
    }
    
    // === РЕГИСТРАЦИЯ (ОДИН ШАГ) ===
    elseif (isset($_POST['register_submit'])) {
        $name = trim($_POST['name'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $loginType = $_POST['login_type'] ?? 'email';
        $password = $_POST['password'] ?? '';
        
        // Упрощенная валидация - ТОЛЬКО EMAIL
        if (empty($name) || empty($login) || empty($password)) {
            $_SESSION['error'] = 'Заполните все поля';
        } elseif (!preg_match('/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u', $name)) {
            $_SESSION['error'] = 'Имя может содержать только буквы';
        } elseif (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Введите корректный email';
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = 'Пароль минимум 6 символов';
        } else {
            try {
                // Проверка существования email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$login]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Email уже занят';
                }
                
                if (!isset($_SESSION['error'])) {
                    // СОЗДАЕМ ПОЛЬЗОВАТЕЛЯ
                    $formattedName = mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Генерируем уникальный username
                    $baseUsername = strtolower(preg_replace('/[^a-z0-9]/i', '', transliterate($name)));
                    $username = $baseUsername;
                    $counter = 1;
                    
                    // Проверяем уникальность
                    while (true) {
                        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $checkStmt->execute([$username]);
                        if (!$checkStmt->fetch()) {
                            break;
                        }
                        $username = $baseUsername . $counter;
                        $counter++;
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, username, email, password, role, created_at) 
                        VALUES (?, ?, ?, ?, 'user', NOW())
                    ");
                    $stmt->execute([
                        $formattedName,
                        $username,
                        $login, // используем $login (это email)
                        $hashedPassword
                    ]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    // ЛОГИНИМ
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $formattedName;
                    $_SESSION['user_email'] = $login;
                    $_SESSION['user_role'] = 'user';
                    
                    // Флаг что нужно выбрать интересы на feed.php
                    $_SESSION['needs_interests'] = true;
                    
                    header('Location: feed.php');
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Ошибка создания аккаунта: ' . $e->getMessage();
            }
        }
        
        header('Location: auth.php');
        exit;
    }
}
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
    <div class="auth-wrapper">
        <!-- ЛЕВАЯ ПАНЕЛЬ С BACKGROUND -->
        <div class="auth-left">
            <div class="auth-overlay">
                <h2>Начните свое обучение</h2>
                <p>Вы можете получить все, что хотите, если будете усердно работать, доверять процессу и придерживаться плана.</p>
            </div>
        </div>

        <!-- ПРАВАЯ ПАНЕЛЬ С ФОРМАМИ -->
        <div class="auth-right">
            <div class="auth-content">
                
                <div class="auth-logo">
                    <img src="/img/logo.svg" alt="<?php echo APP_NAME; ?>">
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- ВХОД -->
                <form method="POST" id="login-form" class="auth-form active">
                    <h2>Вход в аккаунт</h2>
                    <p class="subtitle">Введите свои данные для входа</p>

                    <div class="form-group">
                        <label>Email или номер телефона</label>
                        <input type="text" name="login" placeholder="Введите email или телефон" required>
                    </div>

                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" name="password" placeholder="Введите пароль" required>
                    </div>

                    <button type="submit" name="login_submit" class="btn btn-primary">Войти</button>
                
                    <div class="divider"><span>или войдите с помощью</span></div>
                    
                    <button type="button" class="btn btn-google" onclick="alert('Скоро!')">
                        <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/><path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707 0-.593.102-1.17.282-1.709V4.958H.957C.347 6.173 0 7.548 0 9c0 1.452.348 2.827.957 4.042l3.007-2.335z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
                        Войти через Google
                    </button>

                    <p class="auth-footer">
                        Нет аккаунта? <a href="#" onclick="showRegister(); return false;">Зарегистрироваться</a>
                    </p>
                </form>

                <!-- РЕГИСТРАЦИЯ (ОДИН ШАГ) -->
                <form method="POST" id="register-form" class="auth-form">
                    <h2>Создать аккаунт</h2>
                    <p class="subtitle">Заполните данные для регистрации</p>

                    <div class="form-group">
                        <label>Имя</label>
                        <input type="text" name="name" id="register-name" placeholder="Иван" required>
                    </div>

                    <div class="form-group">
                        <label id="login-label">
                            Email
                            <span class="input-type-toggle">
                                <a href="#" class="active" data-type="email">Email</a>
                                <span class="separator">|</span>
                                <a href="#" data-type="phone">Телефон</a>
                            </span>
                        </label>
                        <input type="email" name="login" id="register-login" placeholder="example@mail.com" required>
                        <input type="hidden" name="login_type" id="login-type" value="email">
                    </div>

                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" name="password" placeholder="Минимум 6 символов" required>
                    </div>

                    <button type="submit" name="register_submit" class="btn btn-primary">Создать аккаунт</button>
                    
                    <div class="divider"><span>или зарегистрируйтесь с помощью</span></div>
                    
                    <button type="button" class="btn btn-google" onclick="alert('Скоро!')">
                        <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/><path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707 0-.593.102-1.17.282-1.709V4.958H.957C.347 6.173 0 7.548 0 9c0 1.452.348 2.827.957 4.042l3.007-2.335z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
                        Регистрация через Google
                    </button>

                    <p class="auth-footer">
                        Есть аккаунт? <a href="#" onclick="showLogin(); return false;">Войти</a><br>
                        <small>Репетитор? <a href="teacher_register.php">Регистрация для репетиторов</a></small>
                    </p>
                </form>

            </div>
        </div>
    </div>

    <script src="/js/auth.js"></script>
</body>
</html>
