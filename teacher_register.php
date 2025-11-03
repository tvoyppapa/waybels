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
$currentStep = $_SESSION['teacher_reg_step'] ?? 1;

// ==================================
// ОБРАБОТКА ФОРМ
// ==================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // === ШАГ 1: ОСНОВНЫЕ ДАННЫЕ ===
    if (isset($_POST['step1'])) {
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
                        $_SESSION['teacher_data'] = [
                            'name' => mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8'),
                            'login' => $login,
                            'is_email' => $isEmail,
                            'password' => password_hash($password, PASSWORD_BCRYPT)
                        ];
                        $_SESSION['teacher_reg_step'] = 2;
                        $currentStep = 2;
                        $success = 'Отлично! Теперь о вашем преподавании';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
    
    // === ШАГ 2: ДАННЫЕ О ПРЕПОДАВАНИИ ===
    elseif (isset($_POST['step2'])) {
        $subject = trim($_POST['subject'] ?? '');
        $experience = (int)($_POST['experience'] ?? 0);
        $hourlyRate = (float)($_POST['hourly_rate'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        
        if (empty($subject) || empty($description)) {
            $error = 'Заполните все обязательные поля';
        } elseif (strlen($description) < 50) {
            $error = 'Описание должно быть минимум 50 символов';
        } else {
            $_SESSION['teacher_data']['subject'] = $subject;
            $_SESSION['teacher_data']['experience'] = $experience;
            $_SESSION['teacher_data']['hourly_rate'] = $hourlyRate;
            $_SESSION['teacher_data']['description'] = $description;
            $_SESSION['teacher_reg_step'] = 3;
            $currentStep = 3;
            $success = 'Последний шаг!';
        }
    }
    
    // === ШАГ 3: ИНТЕРЕСЫ И ЗАВЕРШЕНИЕ ===
    elseif (isset($_POST['step3'])) {
        $interests = $_POST['interests'] ?? [];
        
        if (empty($interests)) {
            $error = 'Выберите хотя бы один интерес';
        } else {
            try {
                $data = $_SESSION['teacher_data'];
                $interestsString = implode(',', $interests);
                
                $emailValue = $data['is_email'] ? $data['login'] : null;
                $phoneValue = !$data['is_email'] ? $data['login'] : null;
                
                // Создаем пользователя
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, password, interests, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'teacher', NOW())
                ");
                $stmt->execute([
                    $data['name'],
                    $emailValue,
                    $phoneValue,
                    $data['password'],
                    $interestsString
                ]);
                
                $userId = $pdo->lastInsertId();
                
                // Создаем профиль репетитора
                $shortDesc = mb_substr($data['description'], 0, 90);
                $stmt = $pdo->prepare("
                    INSERT INTO teacher_profiles 
                    (user_id, subject, description, short_description, experience_years, hourly_rate, status, is_approved) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', FALSE)
                ");
                $stmt->execute([
                    $userId,
                    $data['subject'],
                    $data['description'],
                    $shortDesc,
                    $data['experience'],
                    $data['hourly_rate']
                ]);
                
                // Логиним
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                unset($_SESSION['teacher_reg_step']);
                unset($_SESSION['teacher_data']);
                
                header('Location: teacher.php?id=' . $userId);
                exit;
            } catch (PDOException $e) {
                $error = 'Ошибка создания профиля: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация репетитора - <?php echo APP_NAME; ?></title>
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

            <?php if ($currentStep === 1): ?>
                <!-- ШАГ 1: ОСНОВНЫЕ ДАННЫЕ -->
                <form method="POST" class="auth-form active">
                    <h2>Регистрация репетитора</h2>
                    <p class="subtitle">Начните преподавать онлайн</p>
                    <div class="progress-steps">
                        <span class="step active">1</span>
                        <span class="step">2</span>
                        <span class="step">3</span>
                    </div>

                    <div class="form-group">
                        <input type="text" name="name" placeholder="Ваше имя" required>
                    </div>

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

                    <button type="submit" name="step1" class="btn btn-primary">Продолжить</button>
                    
                    <p class="auth-footer">
                        <a href="auth.php">← Вернуться к обычной регистрации</a>
                    </p>
                </form>

            <?php elseif ($currentStep === 2): ?>
                <!-- ШАГ 2: ДАННЫЕ О ПРЕПОДАВАНИИ -->
                <form method="POST" class="auth-form active">
                    <h2>О вашем преподавании</h2>
                    <p class="subtitle">Расскажите о своем опыте</p>
                    <div class="progress-steps">
                        <span class="step done">1</span>
                        <span class="step active">2</span>
                        <span class="step">3</span>
                    </div>

                    <div class="form-group">
                        <input type="text" name="subject" placeholder="Предмет (напр. Английский язык)" required>
                    </div>

                    <div class="form-group">
                        <input type="number" name="experience" min="0" max="50" placeholder="Опыт преподавания (лет)" required>
                    </div>

                    <div class="form-group">
                        <input type="number" name="hourly_rate" min="0" step="100" placeholder="Стоимость занятия (руб/час)" required>
                    </div>

                    <div class="form-group">
                        <textarea name="description" placeholder="Расскажите о себе и своем подходе к преподаванию (мин. 50 символов)" rows="5" required style="width: 100%; padding: 14px 18px; font-size: 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; resize: vertical;"></textarea>
                    </div>

                    <button type="submit" name="step2" class="btn btn-primary">Продолжить</button>
                </form>

            <?php elseif ($currentStep === 3): ?>
                <!-- ШАГ 3: ИНТЕРЕСЫ -->
                <form method="POST" class="auth-form active">
                    <h2>Выберите категории</h2>
                    <p class="subtitle">В каких областях вы преподаете</p>
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

                    <button type="submit" name="step3" class="btn btn-primary">Завершить регистрацию 🎉</button>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <script src="/js/auth.js"></script>
</body>
</html>
