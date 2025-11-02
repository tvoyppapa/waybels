<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$theme = $user['theme'] ?? 'light';

$success = '';
$error = '';

// Проверяем когда последний раз менялся username
$can_change_username = true;
if ($user['username_changed_at']) {
    $days_since_change = (time() - strtotime($user['username_changed_at'])) / (60 * 60 * 24);
    $can_change_username = $days_since_change >= 7;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $bio = trim($_POST['bio']);
    $interests = $_POST['interests'] ?? null;
    
    // Валидация
    if (empty($name)) {
        $error = "Имя обязательно";
    } elseif (strlen($username) < 3) {
        $error = "Username должен быть минимум 3 символа";
    } else {
        // Проверяем уникальность username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        
        if ($stmt->fetch()) {
            $error = "Этот username уже занят";
        } else {
            // Проверяем можно ли менять username
            if ($username !== $user['username'] && !$can_change_username) {
                $error = "Username можно менять раз в 7 дней";
            } else {
                // Обновляем профиль
                $query = "UPDATE users SET name = ?, bio = ?, interests = ?";
                $params = [$name, $bio, $interests];
                
                // Если username изменился
                if ($username !== $user['username']) {
                    $query .= ", username = ?, username_changed_at = NOW()";
                    $params[] = $username;
                    
                    // Сохраняем в историю
                    $stmt = $pdo->prepare("INSERT INTO username_history (user_id, old_username, new_username) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $user['username'], $username]);
                }
                
                $query .= " WHERE id = ?";
                $params[] = $user_id;
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                
                $success = "Профиль успешно обновлен!";
                
                // Обновляем данные
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать профиль - WayBels</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/glass.css">
    <style>
        .container {
            max-width: 700px;
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
            margin-bottom: 24px;
            transition: all var(--transition);
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 32px;
        }
        
        .edit-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 32px;
        }
        
        .avatar-upload {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: var(--radius-full);
            border: 4px solid var(--glass-border);
            object-fit: cover;
            margin: 0 auto 16px;
            display: block;
        }
        
        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .upload-btn:hover {
            background: var(--glass-bg-strong);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            background: var(--glass-bg-subtle);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            font-size: 15px;
            font-family: inherit;
        }
        
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-hint {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 6px;
        }
        
        .warning-hint {
            color: var(--warning);
        }
        
        .btn-save {
            width: 100%;
            padding: 14px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="profile.php" class="back-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Назад
        </a>
        
        <h1 class="page-title">✏️ Редактировать профиль</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="edit-card">
            <form method="POST" enctype="multipart/form-data">
                <!-- Avatar -->
                <div class="avatar-upload">
                    <img src="<?= e($user['avatar']) ?>" alt="Avatar" class="avatar-preview" id="avatarPreview">
                    <label class="upload-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        Изменить фото
                        <input type="file" name="avatar" accept="image/*" style="display: none;" onchange="previewAvatar(this)">
                    </label>
                </div>
                
                <!-- Name -->
                <div class="form-group">
                    <label class="form-label">Имя</label>
                    <input type="text" name="name" class="form-input" value="<?= e($user['name']) ?>" required>
                </div>
                
                <!-- Username -->
                <div class="form-group">
                    <label class="form-label">Имя пользователя</label>
                    <input type="text" name="username" class="form-input" value="<?= e($user['username']) ?>" required pattern="[a-zA-Z0-9_]{3,}" minlength="3">
                    <?php if ($can_change_username): ?>
                        <p class="form-hint">Только буквы, цифры и подчеркивание</p>
                    <?php else: ?>
                        <p class="form-hint warning-hint">⚠️ Можно изменить через <?= 7 - floor($days_since_change) ?> дн.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Bio -->
                <div class="form-group">
                    <label class="form-label">О себе</label>
                    <textarea name="bio" class="form-textarea" placeholder="Расскажите о себе..."><?= e($user['bio']) ?></textarea>
                    <p class="form-hint">Максимум 500 символов</p>
                </div>
                
                <!-- Interests -->
                <div class="form-group">
                    <label class="form-label">Категория интересов</label>
                    <select name="interests" class="form-input">
                        <option value="">Не выбрано</option>
                        <option value="languages" <?= $user['interests'] === 'languages' ? 'selected' : '' ?>>🌍 Языки</option>
                        <option value="programming" <?= $user['interests'] === 'programming' ? 'selected' : '' ?>>💻 Программирование</option>
                        <option value="design" <?= $user['interests'] === 'design' ? 'selected' : '' ?>>🎨 Дизайн</option>
                        <option value="marketing" <?= $user['interests'] === 'marketing' ? 'selected' : '' ?>>📈 Маркетинг</option>
                        <option value="math" <?= $user['interests'] === 'math' ? 'selected' : '' ?>>🔢 Математика</option>
                        <option value="music" <?= $user['interests'] === 'music' ? 'selected' : '' ?>>🎵 Музыка</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-save">
                    Сохранить изменения
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
