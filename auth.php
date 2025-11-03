<?php
session_start();

// Если уже залогинен - на фид
if (isset($_SESSION['user_id']) && !isset($_SESSION['registration_step'])) {
    header('Location: feed.php');
    exit;
}

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

$error = '';
$success = '';
$currentStep = $_SESSION['registration_step'] ?? null;

// ==================================
// ОБРАБОТКА ФОРМ
// ==================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // === ВХОД ===
    if (isset($_POST['login'])) {
        $login = trim($_POST['login'] ?? '');  // email или телефон
        $password = $_POST['password'] ?? '';
        
        if (empty($login) || empty($password)) {
            $error = 'Заполните все поля';
        } else {
            try {
                // Ищем по email или телефону
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
                $stmt->execute([$login, $login]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // УСПЕХ!
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Обновляем last_seen
                    $pdo->prepare("UPDATE users SET last_seen_at = NOW() WHERE id = ?")->execute([$user['id']]);
                    
                    // Проверяем заполнены ли интересы
                    if (empty($user['interests'])) {
                        $_SESSION['registration_step'] = 'interests';
                        $_SESSION['show_onboarding'] = true;
                    } else {
                        header('Location: feed.php');
                        exit;
                    }
                } else {
                    $error = 'Неверный email/телефон или пароль';
                }
            } catch (PDOException $e) {
                $error = 'Ошибка входа: ' . $e->getMessage();
            }
        }
    }
    
    // === РЕГИСТРАЦИЯ ШАГ 1: Основные данные ===
    elseif (isset($_POST['register_step1'])) {
        $name = trim($_POST['name'] ?? '');
        $login = trim($_POST['login'] ?? '');  // email или телефон
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($name) || empty($login) || empty($password)) {
            $error = 'Заполните все поля';
        } elseif ($password !== $password_confirm) {
            $error = 'Пароли не совпадают';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль должен быть минимум 6 символов';
        } else {
            try {
                // Определяем это email или телефон
                $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
                
                // Проверяем существование
                if ($isEmail) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$login]);
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->execute([$login]);
                }
                
                if ($stmt->fetch()) {
                    $error = $isEmail ? 'Email уже зарегистрирован' : 'Телефон уже зарегистрирован';
                } else {
                    // Сохраняем данные в сессию для следующего шага
                    $_SESSION['registration_data'] = [
                        'name' => mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8'),
                        'login' => $login,
                        'is_email' => $isEmail,
                        'password' => password_hash($password, PASSWORD_BCRYPT)
                    ];
                    $_SESSION['registration_step'] = 'age';
                    $success = 'Отлично! Теперь укажите ваш возраст';
                }
            } catch (PDOException $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
    
    // === РЕГИСТРАЦИЯ ШАГ 2: Возраст ===
    elseif (isset($_POST['register_step2'])) {
        $age = (int)($_POST['age'] ?? 0);
        
        if ($age < 13 || $age > 100) {
            $error = 'Укажите корректный возраст (13-100)';
        } else {
            $_SESSION['registration_data']['age'] = $age;
            $_SESSION['registration_step'] = 'interests';
            $success = 'Отлично! Теперь выберите интересы';
        }
    }
    
    // === РЕГИСТРАЦИЯ ШАГ 3: Интересы ===
    elseif (isset($_POST['register_step3'])) {
        $interests = $_POST['interests'] ?? [];
        
        if (empty($interests)) {
            $error = 'Выберите хотя бы один интерес';
        } else {
            try {
                $regData = $_SESSION['registration_data'];
                $interestsString = implode(',', $interests);
                
                // Создаем пользователя
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, " . ($regData['is_email'] ? 'email' : 'phone') . ", password, age, interests, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'user', NOW())
                ");
                $stmt->execute([
                    $regData['name'],
                    $regData['login'],
                    $regData['password'],
                    $regData['age'],
                    $interestsString
                ]);
                
                $userId = $pdo->lastInsertId();
                
                // ЛОГИНИМ
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                unset($_SESSION['registration_step']);
                unset($_SESSION['registration_data']);
                
                header('Location: feed.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Ошибка создания аккаунта: ' . $e->getMessage();
            }
        }
    }
    
    // === ПРОПУСТИТЬ ИНТЕРЕСЫ (для первого входа) ===
    elseif (isset($_POST['skip_interests'])) {
        unset($_SESSION['registration_step']);
        unset($_SESSION['show_onboarding']);
        header('Location: feed.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Добро пожаловать</title>
    <link rel="stylesheet" href="/style/auth.css">
</head>
<body>
    <div class="auth-wrapper">
        <!-- Левая панель с лого и изображением -->
        <div class="auth-left">
            <div class="auth-left-content">
                <img src="/img/logo-white.svg" alt="<?php echo APP_NAME; ?>" class="auth-logo-img">
                <h1 class="auth-brand"><?php echo APP_NAME; ?></h1>
                <p class="auth-tagline"><?php echo APP_TAGLINE; ?></p>
                <div class="auth-illustration">
                    <div class="floating-card"></div>
                    <div class="floating-card"></div>
                    <div class="floating-card"></div>
                </div>
            </div>
        </div>

        <!-- Правая панель с формами -->
        <div class="auth-right">
            <div class="auth-container">
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠️</span>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">✅</span>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($currentStep === 'age'): ?>
                    <!-- ШАГ 2: ВОЗРАСТ -->
                    <form method="POST" class="auth-form">
                        <div class="auth-header">
                            <h2>Сколько вам лет?</h2>
                            <p class="auth-subtitle">Это поможет подобрать контент</p>
                            <div class="progress-dots">
                                <span class="dot completed"></span>
                                <span class="dot active"></span>
                                <span class="dot"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Возраст</label>
                            <input type="number" name="age" min="13" max="100" placeholder="Например: 25" required autofocus>
                        </div>

                        <button type="submit" name="register_step2" class="btn btn-primary">
                            Далее
                            <span class="btn-icon">→</span>
                        </button>
                    </form>

                <?php elseif ($currentStep === 'interests'): ?>
                    <!-- ШАГ 3: ИНТЕРЕСЫ -->
                    <form method="POST" class="auth-form">
                        <div class="auth-header">
                            <h2>Выберите интересы</h2>
                            <p class="auth-subtitle">Можно выбрать несколько или продолжить с одним</p>
                            <div class="progress-dots">
                                <span class="dot completed"></span>
                                <span class="dot completed"></span>
                                <span class="dot active"></span>
                            </div>
                        </div>

                        <div class="interests-grid">
                            <?php foreach (INTEREST_CATEGORIES as $key => $data): ?>
                                <label class="interest-card">
                                    <input type="checkbox" name="interests[]" value="<?php echo $key; ?>">
                                    <span class="interest-content">
                                        <span class="interest-icon"><?php echo $data['icon']; ?></span>
                                        <span class="interest-name"><?php echo $data['name']; ?></span>
                                        <span class="interest-check">✓</span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="register_step3" class="btn btn-primary">
                                Начать обучение
                                <span class="btn-icon">🚀</span>
                            </button>
                            <?php if (isset($_SESSION['show_onboarding'])): ?>
                                <button type="submit" name="skip_interests" class="btn btn-ghost">
                                    Пропустить
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>

                <?php elseif ($currentStep === null): ?>
                    <!-- ВХОД И РЕГИСТРАЦИЯ -->
                    
                    <!-- ФОРМА ВХОДА -->
                    <form method="POST" id="login-form" class="auth-form active">
                        <div class="auth-header">
                            <h2>Вход в <?php echo APP_NAME; ?></h2>
                            <p class="auth-subtitle">Рады видеть вас снова!</p>
                        </div>

                        <div class="form-group">
                            <label>Email или телефон</label>
                            <input type="text" name="login" placeholder="example@mail.com или +7..." required>
                        </div>

                        <div class="form-group">
                            <label>Пароль</label>
                            <input type="password" name="password" placeholder="Введите пароль" required>
                        </div>

                        <button type="submit" name="login" class="btn btn-primary">
                            Войти
                        </button>

                        <div class="divider">
                            <span>или</span>
                        </div>

                        <button type="button" class="btn btn-google" onclick="alert('Google OAuth скоро будет доступен!')">
                            <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/><path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707 0-.593.102-1.17.282-1.709V4.958H.957C.347 6.173 0 7.548 0 9c0 1.452.348 2.827.957 4.042l3.007-2.335z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
                            Войти через Google
                        </button>

                        <p class="auth-switch">
                            Нет аккаунта? <a href="#" onclick="showRegister(); return false;">Создать</a>
                        </p>
                    </form>

                    <!-- ФОРМА РЕГИСТРАЦИИ ШАГ 1 -->
                    <form method="POST" id="register-form" class="auth-form">
                        <div class="auth-header">
                            <h2>Создать аккаунт</h2>
                            <p class="auth-subtitle">Начните свое обучение сегодня</p>
                            <div class="progress-dots">
                                <span class="dot active"></span>
                                <span class="dot"></span>
                                <span class="dot"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Как вас зовут?</label>
                            <input type="text" name="name" id="register-name" placeholder="Иван Петров" required>
                        </div>

                        <div class="form-group">
                            <label>Email или телефон</label>
                            <input type="text" name="login" id="register-login" placeholder="example@mail.com или +7..." required>
                            <small class="form-hint">Можно использовать email или номер телефона</small>
                        </div>

                        <div class="form-group">
                            <label>Пароль</label>
                            <input type="password" name="password" placeholder="Минимум 6 символов" required>
                        </div>

                        <div class="form-group">
                            <label>Подтвердите пароль</label>
                            <input type="password" name="password_confirm" placeholder="Повторите пароль" required>
                        </div>

                        <button type="submit" name="register_step1" class="btn btn-primary">
                            Продолжить
                            <span class="btn-icon">→</span>
                        </button>

                        <div class="divider">
                            <span>или</span>
                        </div>

                        <button type="button" class="btn btn-google" onclick="alert('Google OAuth скоро будет доступен!')">
                            <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/><path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707 0-.593.102-1.17.282-1.709V4.958H.957C.347 6.173 0 7.548 0 9c0 1.452.348 2.827.957 4.042l3.007-2.335z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
                            Зарегистрироваться через Google
                        </button>

                        <p class="auth-switch">
                            Уже есть аккаунт? <a href="#" onclick="showLogin(); return false;">Войти</a>
                        </p>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="/js/auth.js"></script>
</body>
</html>
