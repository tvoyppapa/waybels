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

// Получаем ошибки из сессии и очищаем
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$currentStep = $_SESSION['registration_step'] ?? null;

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
                    
                    if (empty($user['interests'])) {
                        $_SESSION['registration_step'] = 'interests';
                        $_SESSION['show_onboarding'] = true;
                    }
                    
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
    
    // === РЕГИСТРАЦИЯ ШАГ 1: Основные данные → СРАЗУ НА ИНТЕРЕСЫ ===
    elseif (isset($_POST['register_step1'])) {
        $name = trim($_POST['name'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $loginType = $_POST['login_type'] ?? 'email';
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($login) || empty($password)) {
            $_SESSION['error'] = 'Заполните все поля';
        } elseif (!preg_match('/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u', $name)) {
            $_SESSION['error'] = 'Имя может содержать только буквы';
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = 'Пароль минимум 6 символов';
        } else {
            try {
                $isEmail = ($loginType === 'email');
                
                if ($isEmail) {
                    if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
                        $_SESSION['error'] = 'Неверный формат email';
                    } else {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$login]);
                        if ($stmt->fetch()) {
                            $_SESSION['error'] = 'Email уже занят';
                        }
                    }
                } else {
                    $cleanPhone = preg_replace('/[^\d+]/', '', $login);
                    if (strlen($cleanPhone) < 11) {
                        $_SESSION['error'] = 'Введите корректный номер телефона';
                    } else {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                        $stmt->execute([$cleanPhone]);
                        if ($stmt->fetch()) {
                            $_SESSION['error'] = 'Телефон уже занят';
                        }
                    }
                }
                
                if (!isset($_SESSION['error'])) {
                    $_SESSION['registration_data'] = [
                        'name' => mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8'),
                        'login' => $isEmail ? $login : $cleanPhone,
                        'is_email' => $isEmail,
                        'password' => password_hash($password, PASSWORD_BCRYPT)
                    ];
                    $_SESSION['registration_step'] = 'interests';
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Ошибка: ' . $e->getMessage();
            }
        }
        
        header('Location: auth.php');
        exit;
    }
    
    // === РЕГИСТРАЦИЯ ШАГ 2: ИНТЕРЕСЫ ===
    elseif (isset($_POST['register_step2'])) {
        $interests = $_POST['interests'] ?? [];
        
        if (empty($interests)) {
            $_SESSION['error'] = 'Выберите хотя бы один интерес';
            header('Location: auth.php');
            exit;
        }
        
        try {
            $regData = $_SESSION['registration_data'];
            $interestsString = implode(',', $interests);
            
            $emailValue = $regData['is_email'] ? $regData['login'] : null;
            $phoneValue = !$regData['is_email'] ? $regData['login'] : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password, interests, role, created_at) 
                VALUES (?, ?, ?, ?, ?, 'user', NOW())
            ");
            $stmt->execute([
                $regData['name'],
                $emailValue,
                $phoneValue,
                $regData['password'],
                $interestsString
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Логиним
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $regData['name'];
            $_SESSION['user_email'] = $emailValue;
            $_SESSION['user_role'] = 'user';
            
            unset($_SESSION['registration_step']);
            unset($_SESSION['registration_data']);
            
            header('Location: feed.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Ошибка создания аккаунта: ' . $e->getMessage();
            header('Location: auth.php');
            exit;
        }
    }
    
    // === ПРОПУСТИТЬ ИНТЕРЕСЫ ===
    elseif (isset($_POST['skip_interests'])) {
        unset($_SESSION['registration_step']);
        unset($_SESSION['show_onboarding']);
        header('Location: feed.php');
        exit;
    }
}

// Обновляем текущий шаг
$currentStep = $_SESSION['registration_step'] ?? null;
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

                <?php if ($currentStep === 'interests'): ?>
                    <!-- ШАГ 2: ИНТЕРЕСЫ -->
                    <form method="POST" class="auth-form">
                        <h2>Выберите интересы</h2>
                        <p class="subtitle">Это поможет подобрать лучший контент для вас</p>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 100%;"></div>
                        </div>

                        <div class="interests-grid">
                            <?php foreach (INTEREST_CATEGORIES as $key => $data): ?>
                                <label class="interest-item">
                                    <input type="checkbox" name="interests[]" value="<?php echo $key; ?>">
                                    <span class="interest-box">
                                        <span class="icon"><?php echo $data['icon']; ?></span>
                                        <span class="name"><?php echo $data['name']; ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" name="register_step2" class="btn btn-primary">Начать 🚀</button>
                        <?php if (isset($_SESSION['show_onboarding'])): ?>
                            <button type="submit" name="skip_interests" class="btn btn-link">Пропустить</button>
                        <?php endif; ?>
                    </form>

                <?php else: ?>
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

                    <!-- РЕГИСТРАЦИЯ ШАГ 1 -->
                    <form method="POST" id="register-form" class="auth-form">
                        <h2>Создать аккаунт</h2>
                        <p class="subtitle">Заполните данные для регистрации</p>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 50%;"></div>
                        </div>

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

                        <button type="submit" name="register_step1" class="btn btn-primary">Продолжить</button>
                        
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
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="/js/auth.js"></script>
</body>
</html>
