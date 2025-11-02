<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$theme = $user['theme'] ?? 'light';
$success = '';
$error = '';

// Обработка изменений
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Изменение темы
    if (isset($_POST['change_theme'])) {
        $new_theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
        
        $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
        $stmt->execute([$new_theme, $user_id]);
        
        $success = "Тема успешно изменена!";
        $theme = $new_theme;
        $user['theme'] = $new_theme;
    }
    
    // Изменение пароля
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $user_id]);
                    
                    $success = "Пароль успешно изменен!";
                } else {
                    $error = "Пароль должен быть минимум 6 символов";
                }
            } else {
                $error = "Пароли не совпадают";
            }
        } else {
            $error = "Неверный текущий пароль";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - WayBels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-full);
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 32px;
            transition: all var(--transition);
        }
        
        .back-btn:hover {
            background: var(--glass-bg-strong);
            transform: translateX(-4px);
        }
        
        .back-btn svg {
            width: 20px;
            height: 20px;
        }
        
        .page-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 32px;
        }
        
        .settings-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 32px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            background: var(--glass-bg-subtle);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            font-size: 15px;
            transition: all var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--glass-bg);
        }
        
        .theme-options {
            display: flex;
            gap: 16px;
            margin-top: 12px;
        }
        
        .theme-option {
            flex: 1;
            padding: 20px;
            border: 2px solid var(--glass-border);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all var(--transition);
            text-align: center;
        }
        
        .theme-option:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .theme-option.active {
            border-color: var(--primary);
            background: var(--gradient-glass);
        }
        
        .theme-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .btn-primary {
            padding: 12px 32px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(52, 199, 89, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background: rgba(255, 59, 48, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .info-text {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 6px;
        }
        
        .cache-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: var(--glass-bg-subtle);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition);
            margin-bottom: 12px;
        }
        
        .cache-btn:hover {
            background: var(--glass-bg);
            border-color: var(--primary);
        }
        
        .cache-btn-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .cache-btn svg {
            width: 20px;
            height: 20px;
            color: var(--primary);
        }
        
        .cache-btn-text h4 {
            margin: 0 0 4px 0;
            font-size: 15px;
        }
        
        .cache-btn-text p {
            margin: 0;
            font-size: 13px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Назад
        </a>
        
        <h1 class="page-title">⚙️ Настройки</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <!-- Тема -->
        <div class="settings-card">
            <h2 class="card-title">🎨 Внешний вид</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Тема оформления</label>
                    <div class="theme-options">
                        <label class="theme-option <?= $theme === 'light' ? 'active' : '' ?>">
                            <input type="radio" name="theme" value="light" style="display: none;" <?= $theme === 'light' ? 'checked' : '' ?>>
                            <div class="theme-icon">☀️</div>
                            <div>Светлая</div>
                        </label>
                        
                        <label class="theme-option <?= $theme === 'dark' ? 'active' : '' ?>">
                            <input type="radio" name="theme" value="dark" style="display: none;" <?= $theme === 'dark' ? 'checked' : '' ?>>
                            <div class="theme-icon">🌙</div>
                            <div>Темная</div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" name="change_theme" class="btn-primary">
                    Сохранить тему
                </button>
            </form>
        </div>
        
        <!-- Пароль -->
        <div class="settings-card">
            <h2 class="card-title">🔒 Безопасность</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Текущий пароль</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Новый пароль</label>
                    <input type="password" name="new_password" class="form-input" minlength="6" required>
                    <p class="info-text">Минимум 6 символов</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Подтвердите новый пароль</label>
                    <input type="password" name="confirm_password" class="form-input" minlength="6" required>
                </div>
                
                <button type="submit" name="change_password" class="btn-primary">
                    Изменить пароль
                </button>
            </form>
        </div>
        
        <!-- Дополнительно -->
        <div class="settings-card">
            <h2 class="card-title">🧹 Дополнительно</h2>
            
            <div class="cache-btn" onclick="clearCache()">
                <div class="cache-btn-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h18v18H3zM9 9l6 6m0-6l-6 6"/>
                    </svg>
                    <div class="cache-btn-text">
                        <h4>Очистить кэш</h4>
                        <p>Удалить временные данные приложения</p>
                    </div>
                </div>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </div>
            
            <p class="info-text">
                💡 Если приложение работает некорректно, попробуйте очистить кэш
            </p>
        </div>
    </div>
    
    <script>
        // Clear cache
        function clearCache() {
            if (confirm('Очистить кэш приложения?')) {
                localStorage.clear();
                sessionStorage.clear();
                
                // Clear Service Worker cache if exists
                if ('caches' in window) {
                    caches.keys().then(names => {
                        names.forEach(name => caches.delete(name));
                    });
                }
                
                alert('✅ Кэш очищен! Страница будет перезагружена.');
                window.location.reload(true);
            }
        }
        
        // Theme preview
        document.querySelectorAll('.theme-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.theme-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
