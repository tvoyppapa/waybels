<?php
/**
 * AQUM - Страница авторизации и регистрации
 * Многоэтапная регистрация: 
 * 1) Имя + Email/Телефон + Пароль
 * 2) Код подтверждения 1111
 * 3) Выбор интересов
 */

require_once 'config_aqum.php';
require_once 'db_aqum.php';
require_once 'helpers_aqum.php';

session_start();

// Редирект если уже авторизован
if (isLoggedIn()) {
    redirect('feed_aqum.php');
}

$error = null;
$success = null;
$step = $_SESSION['reg_step'] ?? 'login'; // login, register, verify, interests

// Flash сообщения
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'error') {
        $error = $flash['message'];
    } else {
        $success = $flash['message'];
    }
}

/**
 * ═══════════════════════════════════════════
 * ОБРАБОТКА ВХОДА
 * ═══════════════════════════════════════════
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Ошибка безопасности. Попробуйте еще раз.";
    } else {
        $login = trim($_POST['login']); // email или phone
        $password = $_POST['password'];
        
        // Определяем тип логина
        $user = null;
        if (validateEmail($login)) {
            $user = getUserByEmail($login);
        } elseif (validatePhone($login)) {
            $user = getUserByPhone($login);
        }
        
        if ($user && password_verify($password, $user['password'])) {
            // Успешный вход
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            
            // Обновляем last_seen
            updateUserLastSeen($user['id']);
            
            // Логируем
            logAuth('login', $user['id'], true);
            logAuthAttempt('login', getUserIP(), $user['id']);
            
            // Очистка
            session_regenerate_id(true);
            clearOldInput();
            
            redirect('feed_aqum.php');
        } else {
            $error = "Неверный email/телефон или пароль";
            logAuth('login', 0, false);
            saveOldInput(['login' => $login]);
        }
    }
}

/**
 * ═══════════════════════════════════════════
 * РЕГИСТРАЦИЯ ШАГ 1: Основные данные
 * ═══════════════════════════════════════════
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_step1'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Ошибка безопасности.";
    } else {
        $name = trim($_POST['name']);
        $login_type = $_POST['login_type']; // email or phone
        $login_value = trim($_POST['login_value']);
        $password = $_POST['password'];
        
        $errors = [];
        
        // Валидация
        if (!validateName($name)) {
            $errors[] = "Имя должно содержать минимум 2 символа";
        }
        
        if ($login_type === 'email') {
            if (!validateEmail($login_value)) {
                $errors[] = "Неверный формат email";
            }
            if (getUserByEmail($login_value)) {
                $errors[] = "Такой email уже зарегистрирован";
            }
        } else {
            if (!validatePhone($login_value)) {
                $errors[] = "Неверный формат телефона (используйте +7...)";
            }
            if (getUserByPhone($login_value)) {
                $errors[] = "Такой телефон уже зарегистрирован";
            }
        }
        
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }
        
        // Антифрод
        if (!checkIpLimit(getUserIP())) {
            $errors[] = "Превышен лимит регистраций с вашего IP";
        }
        
        if (!empty($errors)) {
            $error = implode('. ', $errors);
            saveOldInput(['name' => $name, 'login_value' => $login_value, 'login_type' => $login_type]);
        } else {
            // Сохраняем данные в сессию
            $_SESSION['reg_data'] = [
                'name' => $name,
                'login_type' => $login_type,
                'login_value' => $login_value,
                'password' => $password
            ];
            $_SESSION['reg_step'] = 'verify';
            $step = 'verify';
            $success = "Введите код подтверждения";
        }
    }
}

/**
 * ═══════════════════════════════════════════
 * РЕГИСТРАЦИЯ ШАГ 2: Проверка кода
 * ═══════════════════════════════════════════
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Ошибка безопасности.";
    } else {
        $code = $_POST['code1'] . $_POST['code2'] . $_POST['code3'] . $_POST['code4'];
        
        // DEV: код всегда 1111
        if ($code === VERIFICATION_CODE) {
            $_SESSION['reg_step'] = 'interests';
            $step = 'interests';
            $success = "Код подтвержден! Выберите интересы.";
        } else {
            $error = "Неверный код подтверждения. Попробуйте 1111.";
        }
    }
}

/**
 * ═══════════════════════════════════════════
 * РЕГИСТРАЦИЯ ШАГ 3: Выбор интересов
 * ═══════════════════════════════════════════
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_interests'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Ошибка безопасности.";
    } else {
        $interests = $_POST['interests'] ?? [];
        
        if (empty($interests)) {
            $error = "Выберите хотя бы один интерес";
        } else {
            // Создаем пользователя
            $regData = $_SESSION['reg_data'];
            $hashedPassword = password_hash($regData['password'], PASSWORD_BCRYPT);
            $username = generateUsername($regData['name']);
            $interestsStr = implode(',', $interests);
            
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, username, email, phone, password, interests, role, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'user', NOW())"
                );
                
                $email = $regData['login_type'] === 'email' ? $regData['login_value'] : null;
                $phone = $regData['login_type'] === 'phone' ? $regData['login_value'] : null;
                
                $stmt->execute([
                    $regData['name'],
                    $username,
                    $email,
                    $phone,
                    $hashedPassword,
                    $interestsStr
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // Создаем настройки пользователя
                $stmt = $pdo->prepare("INSERT INTO settings (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                
                // Создаем онбординг задачи
                $tasks = ['complete_profile', 'find_tutor', 'attach_card', 'book_first_lesson'];
                $stmt = $pdo->prepare("INSERT INTO onboarding_tasks (user_id, task_key) VALUES (?, ?)");
                foreach ($tasks as $task) {
                    $stmt->execute([$user_id, $task]);
                }
                
                // Логируем
                logAuthAttempt('register', getUserIP(), $user_id);
                
                // Автоматический вход
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_name'] = $regData['name'];
                
                // Очистка
                unset($_SESSION['reg_data'], $_SESSION['reg_step']);
                session_regenerate_id(true);
                
                setFlashMessage('success', 'Добро пожаловать в AQUM! 🎉');
                redirect('feed_aqum.php');
                
            } catch (PDOException $e) {
                $error = "Ошибка регистрации. Попробуйте позже.";
                logError("Registration error: " . $e->getMessage());
            }
        }
    }
}

// Определяем текущий шаг для progress bar
$currentStep = match($step) {
    'login' => 0,
    'register' => 1,
    'verify' => 2,
    'interests' => 3,
    default => 0
};

$page_title = "Вход - AQUM";
$additional_css = ['/style/auth_aqum.css'];
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style/aqum_main.css">
    <link rel="stylesheet" href="/style/auth_aqum.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-box">
        <!-- Логотип -->
        <div class="auth-logo">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="brandGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#7F2CDF;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#9B5CF9;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <circle cx="32" cy="32" r="28" fill="url(#brandGradient)"/>
                <path d="M32 18L38 30H26L32 18Z" fill="white" opacity="0.9"/>
                <path d="M32 34L26 46H38L32 34Z" fill="white" opacity="0.7"/>
            </svg>
        </div>
        
        <h1 class="auth-title">AQUM</h1>
        <p class="auth-subtitle">Экосистема для онлайн-репетиторов</p>
        
        <!-- Progress Bar (только для регистрации) -->
        <?php if (in_array($step, ['register', 'verify', 'interests'])): ?>
        <div class="auth-progress">
            <div class="auth-progress-step <?= $currentStep >= 1 ? 'completed' : '' ?> <?= $currentStep === 1 ? 'active' : '' ?>"></div>
            <div class="auth-progress-step <?= $currentStep >= 2 ? 'completed' : '' ?> <?= $currentStep === 2 ? 'active' : '' ?>"></div>
            <div class="auth-progress-step <?= $currentStep >= 3 ? 'completed' : '' ?> <?= $currentStep === 3 ? 'active' : '' ?>"></div>
        </div>
        <?php endif; ?>
        
        <!-- Алерты -->
        <?php if ($error): ?>
            <div class="auth-alert auth-alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="auth-alert auth-alert-success">
                <?= e($success) ?>
            </div>
        <?php endif; ?>
        
        <!-- Табы Войти/Регистрация (только на главном экране) -->
        <?php if ($step === 'login' || $step === 'register'): ?>
        <div class="auth-tabs">
            <button class="auth-tab <?= $step === 'login' ? 'active' : '' ?>" data-tab="login">
                Войти
            </button>
            <button class="auth-tab <?= $step === 'register' ? 'active' : '' ?>" data-tab="register">
                Регистрация
            </button>
        </div>
        <?php endif; ?>
        
        <!-- ФОРМА ВХОДА -->
        <form method="POST" class="auth-form <?= $step === 'login' ? 'active' : '' ?>" id="login-form">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            
            <div class="auth-form-group">
                <label class="auth-form-label">Email или телефон</label>
                <input 
                    type="text" 
                    name="login" 
                    class="auth-form-input"
                    placeholder="your@email.com или +79991234567"
                    value="<?= e(old('login')) ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="auth-form-group">
                <label class="auth-form-label">Пароль</label>
                <input 
                    type="password" 
                    name="password" 
                    class="auth-form-input"
                    placeholder="Введите пароль"
                    required
                >
            </div>
            
            <button type="submit" name="login" class="auth-btn auth-btn-primary">
                Войти
            </button>
            
            <div class="auth-links">
                <a href="#" onclick="alert('Функция в разработке'); return false;">Забыли пароль?</a>
            </div>
        </form>
        
        <!-- ФОРМА РЕГИСТРАЦИИ ШАГ 1 -->
        <form method="POST" class="auth-form <?= $step === 'register' ? 'active' : '' ?>" id="register-form">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            
            <!-- Переключатель Email/Телефон -->
            <div class="auth-switcher">
                <button type="button" class="auth-switcher-btn active" data-type="email">
                    Email
                </button>
                <button type="button" class="auth-switcher-btn" data-type="phone">
                    Телефон
                </button>
            </div>
            
            <input type="hidden" name="login_type" id="login_type" value="email">
            
            <div class="auth-form-group">
                <label class="auth-form-label">Имя</label>
                <input 
                    type="text" 
                    name="name" 
                    class="auth-form-input"
                    placeholder="Ваше имя"
                    value="<?= e(old('name')) ?>"
                    required
                >
            </div>
            
            <div class="auth-form-group">
                <label class="auth-form-label" id="login-label">Email</label>
                <input 
                    type="text" 
                    name="login_value" 
                    id="login_value"
                    class="auth-form-input"
                    placeholder="your@email.com"
                    value="<?= e(old('login_value')) ?>"
                    required
                >
            </div>
            
            <div class="auth-form-group">
                <label class="auth-form-label">Пароль</label>
                <input 
                    type="password" 
                    name="password" 
                    class="auth-form-input"
                    placeholder="Минимум 6 символов"
                    required
                    minlength="6"
                >
            </div>
            
            <button type="submit" name="register_step1" class="auth-btn auth-btn-primary">
                Продолжить
            </button>
        </form>
        
        <!-- ФОРМА ВЕРИФИКАЦИИ (ШАГ 2) -->
        <form method="POST" class="auth-form <?= $step === 'verify' ? 'active' : '' ?>" id="verify-form">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            
            <h3 class="auth-subtitle">Введите код подтверждения</h3>
            <p class="text-muted text-center" style="font-size: 14px; margin-bottom: 24px;">
                DEV режим: используйте код <strong>1111</strong>
            </p>
            
            <div class="code-input-container">
                <input type="text" name="code1" class="code-input" maxlength="1" required autofocus>
                <input type="text" name="code2" class="code-input" maxlength="1" required>
                <input type="text" name="code3" class="code-input" maxlength="1" required>
                <input type="text" name="code4" class="code-input" maxlength="1" required>
            </div>
            
            <button type="submit" name="verify_code" class="auth-btn auth-btn-primary">
                Подтвердить
            </button>
            
            <div class="auth-links">
                <a href="?reset=1">← Вернуться назад</a>
            </div>
        </form>
        
        <!-- ФОРМА ВЫБОРА ИНТЕРЕСОВ (ШАГ 3) -->
        <form method="POST" class="auth-form <?= $step === 'interests' ? 'active' : '' ?>" id="interests-form">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            
            <h3 class="auth-subtitle">Что вас интересует?</h3>
            <p class="text-muted text-center" style="font-size: 14px; margin-bottom: 16px;">
                Выберите одну или несколько категорий
            </p>
            
            <div class="interests-grid">
                <?php foreach (SUBJECTS as $code => $subject): ?>
                <label class="interest-chip">
                    <input type="checkbox" name="interests[]" value="<?= e($code) ?>" style="display:none;">
                    <span class="interest-icon"><?= $subject['icon'] ?></span>
                    <span class="interest-name"><?= e($subject['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" name="select_interests" class="auth-btn auth-btn-primary">
                Завершить регистрацию
            </button>
        </form>
        
    </div>
</div>

<script>
// Переключение табов Войти/Регистрация
document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const target = this.dataset.tab;
        
        // Обновляем активные табы
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Показываем нужную форму
        document.querySelectorAll('.auth-form').forEach(form => {
            form.classList.remove('active');
        });
        document.getElementById(target + '-form').classList.add('active');
        
        // Обновляем URL
        const url = new URL(window.location);
        url.searchParams.set('tab', target);
        window.history.pushState({}, '', url);
    });
});

// Переключатель Email/Телефон
document.querySelectorAll('.auth-switcher-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const type = this.dataset.type;
        
        // Обновляем кнопки
        document.querySelectorAll('.auth-switcher-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Обновляем hidden input
        document.getElementById('login_type').value = type;
        
        // Обновляем placeholder и label
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

// Автофокус на код подтверждения
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

// Выбор интересов
document.querySelectorAll('.interest-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        const checkbox = this.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        this.classList.toggle('selected', checkbox.checked);
    });
});

// Сброс регистрации
const resetParam = new URLSearchParams(window.location.search).get('reset');
if (resetParam === '1') {
    fetch('?clear_session=1')
        .then(() => window.location.href = 'auth_aqum.php');
}
</script>

</body>
</html>

<?php
// Сброс сессии регистрации
if (isset($_GET['clear_session'])) {
    unset($_SESSION['reg_data'], $_SESSION['reg_step']);
    exit('ok');
}
?>
