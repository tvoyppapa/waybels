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
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($login) || empty($password)) {
            $error = 'Заполните все поля';
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
                    } else {
                        header('Location: feed.php');
                        exit;
                    }
                } else {
                    $error = 'Неверные данные для входа';
                }
            } catch (PDOException $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
    
    // === РЕГИСТРАЦИЯ ШАГ 1 ===
    elseif (isset($_POST['register_step1'])) {
        $name = trim($_POST['name'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $loginType = $_POST['login_type'] ?? 'email';
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($login) || empty($password)) {
            $error = 'Заполните все поля';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль минимум 6 символов';
        } else {
            try {
                $isEmail = ($loginType === 'email');
                
                if ($isEmail && !filter_var($login, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Неверный формат email';
                } else {
                    $checkField = $isEmail ? 'email' : 'phone';
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE $checkField = ?");
                    $stmt->execute([$login]);
                    
                    if ($stmt->fetch()) {
                        $error = $isEmail ? 'Email уже занят' : 'Телефон уже занят';
                    } else {
                        $_SESSION['registration_data'] = [
                            'name' => mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8'),
                            'login' => $login,
                            'is_email' => $isEmail,
                            'password' => password_hash($password, PASSWORD_BCRYPT)
                        ];
                        $_SESSION['registration_step'] = 'age';
                        $success = 'Отлично! Укажите ваш возраст';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
    
    // === РЕГИСТРАЦИЯ ШАГ 2: ВОЗРАСТ ===
    elseif (isset($_POST['register_step2'])) {
        $age = (int)($_POST['age'] ?? 0);
        
        if ($age < 13 || $age > 100) {
            $error = 'Возраст должен быть от 13 до 100';
        } else {
            $_SESSION['registration_data']['age'] = $age;
            $_SESSION['registration_step'] = 'interests';
            $success = 'Теперь выберите интересы';
        }
    }
    
    // === РЕГИСТРАЦИЯ ШАГ 3: ИНТЕРЕСЫ ===
    elseif (isset($_POST['register_step3'])) {
        $interests = $_POST['interests'] ?? [];
        
        if (empty($interests)) {
            $error = 'Выберите хотя бы один интерес';
        } else {
            try {
                $regData = $_SESSION['registration_data'];
                $interestsString = implode(',', $interests);
                
                $emailValue = $regData['is_email'] ? $regData['login'] : null;
                $phoneValue = !$regData['is_email'] ? $regData['login'] : null;
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, password, age, interests, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'user', NOW())
                ");
                $stmt->execute([
                    $regData['name'],
                    $emailValue,
                    $phoneValue,
                    $regData['password'],
                    $regData['age'],
                    $interestsString
                ]);
                
                $userId = $pdo->lastInsertId();
                
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
    
    // === ПРОПУСТИТЬ ИНТЕРЕСЫ ===
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
    <title><?php echo APP_NAME; ?> - Вход</title>
    <link rel="stylesheet" href="/style/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-logo">
                <img src="/img/logo.svg" alt="<?php echo APP_NAME; ?>">
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($currentStep === 'age'): ?>
                <!-- ШАГ 2: ВОЗРАСТ -->
                <form method="POST" class="auth-form">
                    <h2>Ваш возраст</h2>
                    <p class="subtitle">Это поможет персонализировать контент</p>
                    <div class="progress-steps">
                        <span class="step done">1</span>
                        <span class="step active">2</span>
                        <span class="step">3</span>
                    </div>

                    <div class="form-group">
                        <input type="number" name="age" min="13" max="100" placeholder="Возраст" required autofocus>
                    </div>

                    <button type="submit" name="register_step2" class="btn btn-primary">Продолжить</button>
                </form>

            <?php elseif ($currentStep === 'interests'): ?>
                <!-- ШАГ 3: ИНТЕРЕСЫ -->
                <form method="POST" class="auth-form">
                    <h2>Выберите интересы</h2>
                    <p class="subtitle">Можно выбрать несколько</p>
                    <div class="progress-steps">
                        <span class="step done">1</span>
                        <span class="step done">2</span>
                        <span class="step active">3</span>
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

                    <button type="submit" name="register_step3" class="btn btn-primary">Начать 🚀</button>
                    <?php if (isset($_SESSION['show_onboarding'])): ?>
                        <button type="submit" name="skip_interests" class="btn btn-link">Пропустить</button>
                    <?php endif; ?>
                </form>

            <?php else: ?>
                <!-- ВХОД -->
                <form method="POST" id="login-form" class="auth-form active">
                    <h2>Вход</h2>
                    <p class="subtitle">Рады видеть вас снова!</p>

                    <div class="form-group">
                        <input type="text" name="login" placeholder="Email или телефон" required>
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" placeholder="Пароль" required>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary">Войти</button>
                    
                    <div class="divider">или</div>
                    
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
                    <h2>Регистрация</h2>
                    <p class="subtitle">Начните обучение сегодня</p>
                    <div class="progress-steps">
                        <span class="step active">1</span>
                        <span class="step">2</span>
                        <span class="step">3</span>
                    </div>

                    <div class="form-group">
                        <input type="text" name="name" id="register-name" placeholder="Ваше имя" required>
                    </div>

                    <!-- ПЕРЕКЛЮЧАТЕЛЬ EMAIL/PHONE -->
                    <div class="login-type-switch">
                        <button type="button" class="switch-btn active" data-type="email">Email</button>
                        <button type="button" class="switch-btn" data-type="phone">Телефон</button>
                    </div>
                    <input type="hidden" name="login_type" id="login-type" value="email">

                    <div class="form-group">
                        <input type="text" name="login" id="register-login" placeholder="example@mail.com" required>
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" placeholder="Пароль (мин. 6 символов)" required>
                    </div>

                    <button type="submit" name="register_step1" class="btn btn-primary">Продолжить</button>
                    
                    <div class="divider">или</div>
                    
                    <button type="button" class="btn btn-google" onclick="alert('Скоро!')">
                        <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/><path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707 0-.593.102-1.17.282-1.709V4.958H.957C.347 6.173 0 7.548 0 9c0 1.452.348 2.827.957 4.042l3.007-2.335z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
                        Регистрация через Google
                    </button>

                    <p class="auth-footer">
                        Есть аккаунт? <a href="#" onclick="showLogin(); return false;">Войти</a><br>
                        Репетитор? <a href="teacher_register.php">Регистрация для репетиторов</a>
                    </p>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <script src="/js/auth.js"></script>
</body>
</html>
