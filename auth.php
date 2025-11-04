<?php
/**
 * AQUM - Авторизация и регистрация
 * Дизайн: Левая панель + Правая форма
 */

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

session_start();

if (isLoggedIn()) {
    redirect('feed.php');
}

$error = null;
$success = null;
$step = $_SESSION['reg_step'] ?? 'login';

$flash = getFlashMessage();
if ($flash) {
    $error = $flash['type'] === 'error' ? $flash['message'] : null;
    $success = $flash['type'] === 'success' ? $flash['message'] : null;
}

// Обработка входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Ошибка безопасности";
    } else {
        $login = trim($_POST['login']);
        $password = $_POST['password'];
        
        $user = validateEmail($login) ? getUserByEmail($login) : (validatePhone($login) ? getUserByPhone($login) : null);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            
            updateUserLastSeen($user['id']);
            logAuth('login', $user['id'], true);
            logAuthAttempt('login', getUserIP(), $user['id']);
            
            session_regenerate_id(true);
            clearOldInput();
            redirect('feed.php');
        } else {
            $error = "Неверный логин или пароль";
            saveOldInput(['login' => $login]);
        }
    }
}

// Регистрация шаг 1
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_step1'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Ошибка безопасности";
    } else {
        $name = trim($_POST['name']);
        $login_type = $_POST['login_type'];
        $login_value = trim($_POST['login_value']);
        $password = $_POST['password'];
        
        $errors = [];
        if (!validateName($name)) $errors[] = "Имя: минимум 2 символа";
        
        if ($login_type === 'email') {
            if (!validateEmail($login_value)) $errors[] = "Неверный email";
            if (getUserByEmail($login_value)) $errors[] = "Email занят";
        } else {
            if (!validatePhone($login_value)) $errors[] = "Неверный телефон";
            if (getUserByPhone($login_value)) $errors[] = "Телефон занят";
        }
        
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) $errors = array_merge($errors, $passwordErrors);
        
        if (!checkIpLimit(getUserIP())) $errors[] = "Лимит регистраций с IP";
        
        if (!empty($errors)) {
            $error = implode('. ', $errors);
            saveOldInput(['name' => $name, 'login_value' => $login_value, 'login_type' => $login_type]);
        } else {
            $_SESSION['reg_data'] = ['name' => $name, 'login_type' => $login_type, 'login_value' => $login_value, 'password' => $password];
            $_SESSION['reg_step'] = 'verify';
            $step = 'verify';
            $success = "Введите код 1111";
        }
    }
}

// Регистрация шаг 2
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Ошибка безопасности";
    } else {
        $code = $_POST['code1'].$_POST['code2'].$_POST['code3'].$_POST['code4'];
        if ($code === VERIFICATION_CODE) {
            $_SESSION['reg_step'] = 'interests';
            $step = 'interests';
            $success = "Код подтвержден!";
        } else {
            $error = "Неверный код (используйте 1111)";
        }
    }
}

// Регистрация шаг 3
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_interests'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Ошибка безопасности";
    } else {
        $interests = $_POST['interests'] ?? [];
        if (empty($interests)) {
            $error = "Выберите минимум 1 интерес";
        } else {
            $regData = $_SESSION['reg_data'];
            $hashedPassword = password_hash($regData['password'], PASSWORD_BCRYPT);
            $username = generateUsername($regData['name']);
            $interestsStr = implode(',', $interests);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, username, email, phone, password, interests, role, created_at) VALUES (?, ?, ?, ?, ?, ?, 'user', NOW())");
                $stmt->execute([
                    $regData['name'],
                    $username,
                    $regData['login_type'] === 'email' ? $regData['login_value'] : null,
                    $regData['login_type'] === 'phone' ? $regData['login_value'] : null,
                    $hashedPassword,
                    $interestsStr
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO settings (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                
                $tasks = ['complete_profile', 'find_tutor', 'attach_card', 'book_first_lesson'];
                $stmt = $pdo->prepare("INSERT INTO onboarding_tasks (user_id, task_key) VALUES (?, ?)");
                foreach ($tasks as $task) $stmt->execute([$user_id, $task]);
                
                logAuthAttempt('register', getUserIP(), $user_id);
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_name'] = $regData['name'];
                
                unset($_SESSION['reg_data'], $_SESSION['reg_step']);
                session_regenerate_id(true);
                
                setFlashMessage('success', 'Добро пожаловать в AQUM! 🎉');
                redirect('feed.php');
            } catch (PDOException $e) {
                $error = "Ошибка регистрации";
                logError("Registration error: " . $e->getMessage());
            }
        }
    }
}

$currentStep = match($step) { 'login' => 0, 'register' => 1, 'verify' => 2, 'interests' => 3, default => 0 };
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - AQUM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style/main.css">
    <link rel="stylesheet" href="/style/auth.css">
</head>
<body>

<div class="auth-page">
    <!-- Левая панель (60%) -->
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="auth-left-logo">
                <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="60" cy="60" r="55" fill="white" opacity="0.1"/>
                    <circle cx="60" cy="60" r="45" fill="white" opacity="0.2"/>
                    <path d="M60 30L72 50H48L60 30Z" fill="white"/>
                    <path d="M60 55L48 75H72L60 55Z" fill="white" opacity="0.8"/>
                    <circle cx="60" cy="60" r="5" fill="white"/>
                </svg>
            </div>
            
            <h1 class="auth-left-title">AQUM</h1>
            <p class="auth-left-subtitle">Экосистема для онлайн-репетиторов</p>
            
            <div class="auth-left-features">
                <div class="auth-left-feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                    <span>Найдите лучших преподавателей</span>
                </div>
                <div class="auth-left-feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span>Общайтесь и учитесь онлайн</span>
                </div>
                <div class="auth-left-feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span>Отслеживайте свой прогресс</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Правая панель (40%) -->
    <div class="auth-right">
        <div class="auth-panel">
            <!-- Логотип для mobile -->
            <div class="auth-panel-logo">
                <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="brandGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#7F2CDF"/>
                            <stop offset="100%" style="stop-color:#9B5CF9"/>
                        </linearGradient>
                    </defs>
                    <circle cx="32" cy="32" r="28" fill="url(#brandGradient)"/>
                    <path d="M32 18L38 30H26L32 18Z" fill="white" opacity="0.9"/>
                    <path d="M32 34L26 46H38L32 34Z" fill="white" opacity="0.7"/>
                </svg>
            </div>
            
            <h2 class="auth-panel-title"><?= $step === 'verify' ? 'Подтверждение' : ($step === 'interests' ? 'Ваши интересы' : 'Добро пожаловать') ?></h2>
            <p class="auth-panel-subtitle"><?= $step === 'verify' ? 'Введите код подтверждения' : ($step === 'interests' ? 'Выберите что вас интересует' : 'Войдите или создайте аккаунт') ?></p>
            
            <?php if (in_array($step, ['register', 'verify', 'interests'])): ?>
            <div class="auth-progress">
                <div class="auth-progress-step <?= $currentStep >= 1 ? 'completed' : '' ?> <?= $currentStep === 1 ? 'active' : '' ?>"></div>
                <div class="auth-progress-step <?= $currentStep >= 2 ? 'completed' : '' ?> <?= $currentStep === 2 ? 'active' : '' ?>"></div>
                <div class="auth-progress-step <?= $currentStep >= 3 ? 'completed' : '' ?> <?= $currentStep === 3 ? 'active' : '' ?>"></div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?><div class="auth-alert auth-alert-error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="auth-alert auth-alert-success"><?= e($success) ?></div><?php endif; ?>
            
            <?php if ($step === 'login' || $step === 'register'): ?>
            <div class="auth-tabs">
                <button class="auth-tab <?= $step === 'login' ? 'active' : '' ?>" data-tab="login">Войти</button>
                <button class="auth-tab <?= $step === 'register' ? 'active' : '' ?>" data-tab="register">Регистрация</button>
            </div>
            <?php endif; ?>
            
            <!-- Форма входа -->
            <form method="POST" class="auth-form <?= $step === 'login' ? 'active' : '' ?>" id="login-form">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <div class="auth-form-group">
                    <label class="auth-form-label">Email или телефон</label>
                    <input type="text" name="login" class="auth-form-input" placeholder="your@email.com или +79991234567" value="<?= e(old('login')) ?>" required autofocus>
                </div>
                <div class="auth-form-group">
                    <label class="auth-form-label">Пароль</label>
                    <input type="password" name="password" class="auth-form-input" placeholder="Введите пароль" required>
                </div>
                <button type="submit" name="login" class="auth-btn auth-btn-primary">Войти</button>
                <div class="auth-links"><a href="#" onclick="alert('В разработке'); return false;">Забыли пароль?</a></div>
            </form>
            
            <!-- Форма регистрации шаг 1 -->
            <form method="POST" class="auth-form <?= $step === 'register' ? 'active' : '' ?>" id="register-form">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <div class="auth-switcher">
                    <button type="button" class="auth-switcher-btn active" data-type="email">Email</button>
                    <button type="button" class="auth-switcher-btn" data-type="phone">Телефон</button>
                </div>
                <input type="hidden" name="login_type" id="login_type" value="email">
                <div class="auth-form-group">
                    <label class="auth-form-label">Имя</label>
                    <input type="text" name="name" class="auth-form-input" placeholder="Ваше имя" value="<?= e(old('name')) ?>" required>
                </div>
                <div class="auth-form-group">
                    <label class="auth-form-label" id="login-label">Email</label>
                    <input type="text" name="login_value" id="login_value" class="auth-form-input" placeholder="your@email.com" value="<?= e(old('login_value')) ?>" required>
                </div>
                <div class="auth-form-group">
                    <label class="auth-form-label">Пароль</label>
                    <input type="password" name="password" class="auth-form-input" placeholder="Минимум 6 символов" required minlength="6">
                </div>
                <button type="submit" name="register_step1" class="auth-btn auth-btn-primary">Продолжить</button>
            </form>
            
            <!-- Форма верификации шаг 2 -->
            <form method="POST" class="auth-form <?= $step === 'verify' ? 'active' : '' ?>" id="verify-form">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <p style="text-align: center; color: var(--text-secondary); font-size: 14px; margin-bottom: 24px;">DEV: используйте код <strong>1111</strong></p>
                <div class="code-input-container">
                    <input type="text" name="code1" class="code-input" maxlength="1" required autofocus>
                    <input type="text" name="code2" class="code-input" maxlength="1" required>
                    <input type="text" name="code3" class="code-input" maxlength="1" required>
                    <input type="text" name="code4" class="code-input" maxlength="1" required>
                </div>
                <button type="submit" name="verify_code" class="auth-btn auth-btn-primary">Подтвердить</button>
                <div class="auth-links"><a href="?reset=1">← Вернуться</a></div>
            </form>
            
            <!-- Форма интересов шаг 3 -->
            <form method="POST" class="auth-form <?= $step === 'interests' ? 'active' : '' ?>" id="interests-form">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <div class="interests-grid">
                    <?php foreach (SUBJECTS as $code => $subject): ?>
                    <label class="interest-chip">
                        <input type="checkbox" name="interests[]" value="<?= e($code) ?>" style="display:none;">
                        <span class="interest-icon"><?= $subject['icon'] ?></span>
                        <span class="interest-name"><?= e($subject['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="select_interests" class="auth-btn auth-btn-primary">Завершить</button>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const target = this.dataset.tab;
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
        document.getElementById(target + '-form').classList.add('active');
    });
});

document.querySelectorAll('.auth-switcher-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const type = this.dataset.type;
        document.querySelectorAll('.auth-switcher-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('login_type').value = type;
        const input = document.getElementById('login_value');
        const label = document.getElementById('login-label');
        if (type === 'email') {
            input.type = 'email';
            input.placeholder = 'your@email.com';
            label.textContent = 'Email';
        } else {
            input.type = 'tel';
            input.placeholder = '+79991234567';
            label.textContent = 'Телефон';
        }
    });
});

document.querySelectorAll('.code-input').forEach((input, index, inputs) => {
    input.addEventListener('input', function() {
        if (this.value.length === 1 && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value === '' && index > 0) {
            inputs[index - 1].focus();
        }
    });
});

document.querySelectorAll('.interest-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        const checkbox = this.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        this.classList.toggle('selected', checkbox.checked);
    });
});

if (new URLSearchParams(window.location.search).get('reset') === '1') {
    fetch('?clear_session=1').then(() => window.location.href = 'auth.php');
}
</script>

</body>
</html>

<?php
if (isset($_GET['clear_session'])) {
    unset($_SESSION['reg_data'], $_SESSION['reg_step']);
    exit('ok');
}
?>
