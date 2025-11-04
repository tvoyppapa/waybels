<?php
/**
 * Логика аутентификации и регистрации
 */

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

session_start();

// Инициализация переменных
$error = null;
$success = null;
$currentView = 'login';
$interestsSelected = isset($_SESSION['interests']);

if (isset($_SESSION['auth_view'])) {
    $currentView = $_SESSION['auth_view'];
    unset($_SESSION['auth_view']);
}

if (isset($_GET['view']) && in_array($_GET['view'], ['login', 'register'], true)) {
    $currentView = $_GET['view'];
}

// ОТЛАДКА
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST получен в auth_handler.php");
    error_log("POST data: " . print_r($_POST, true));
}

// Получаем flash сообщения
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'error') {
        $error = $flash['message'];
    } else {
        $success = $flash['message'];
    }
}

/**
 * Обработка выбора интересов (квиз)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interests'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Ошибка безопасности. Попробуйте еще раз.";
        $currentView = 'register';
    } else {
        $interests = $_POST['interests'];
        if (array_key_exists($interests, INTEREST_CATEGORIES)) {
            $_SESSION['interests'] = $interests;
            $_SESSION['auth_view'] = 'register';
            redirect($_SERVER['PHP_SELF']);
        } else {
            $error = "Неверная категория";
            $currentView = 'register';
        }
    }
}

/**
 * Обработка входа
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $currentView = 'login';
    // CSRF проверка
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Ошибка безопасности. Попробуйте еще раз.";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Валидация
        if (!validateEmail($email)) {
            $error = "Неверный формат email";
            saveOldInput(['email' => $email]);
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Успешный вход
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Очищаем старые данные
                    clearOldInput();
                    unset($_SESSION['interests']);
                    
                    // Регенерируем ID сессии для безопасности
                    session_regenerate_id(true);
                    
                    redirect('dashboard.php');
                } else {
                    $error = "Неверный email или пароль";
                    saveOldInput(['email' => $email]);
                }
            } catch (PDOException $e) {
                $error = "Ошибка входа. Попробуйте позже.";
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}

/**
 * Обработка регистрации
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $currentView = 'register';
    // CSRF проверка
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Ошибка безопасности. Попробуйте еще раз.";
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $interests = $_SESSION['interests'] ?? null;
        
        // Валидация
        $errors = [];
        
        if (!validateName($name)) {
            $errors[] = "Имя должно содержать минимум 2 символа";
        }
        
        if (!validateEmail($email)) {
            $errors[] = "Неверный формат email";
        }
        
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }

        if (!$interests || !array_key_exists($interests, INTEREST_CATEGORIES)) {
            $errors[] = "Выберите направление, которое вам интересно";
        }
        
        if (!empty($errors)) {
            $error = implode('. ', $errors);
            saveOldInput(['name' => $name, 'email' => $email]);
        } else {
            try {
                // Проверяем существование email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = "Такой email уже зарегистрирован";
                    saveOldInput(['name' => $name, 'email' => $email]);
                } else {
                    // Регистрация нового пользователя
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Проверяем, есть ли колонка interests в таблице
                    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'interests'");
                    $hasInterestsColumn = $stmt->fetch();
                    
                    if ($hasInterestsColumn) {
                        // Если есть колонка interests
                        $stmt = $pdo->prepare(
                            "INSERT INTO users (name, email, password, interests, role, created_at) 
                             VALUES (?, ?, ?, ?, 'user', NOW())"
                        );
                        $stmt->execute([$name, $email, $hashedPassword, $interests]);
                    } else {
                        // Если нет колонки interests (старая БД)
                        $stmt = $pdo->prepare(
                            "INSERT INTO users (name, email, password, role) 
                             VALUES (?, ?, ?, 'user')"
                        );
                        $stmt->execute([$name, $email, $hashedPassword]);
                    }
                    
                    // Автоматический вход после регистрации
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['user_interests'] = $interests;
                    
                    // Очищаем старые данные
                    clearOldInput();
                    unset($_SESSION['interests']);
                    
                    // Регенерируем ID сессии
                    session_regenerate_id(true);
                    
                    redirect('dashboard.php');
                }
            } catch (PDOException $e) {
                $error = "Ошибка регистрации. Попробуйте позже.";
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}

/**
 * Сброс интересов (для возврата к квизу)
 */
if (isset($_GET['reset'])) {
    unset($_SESSION['interests']);
    $_SESSION['auth_view'] = 'register';
    redirect($_SERVER['PHP_SELF']);
}

$interestsSelected = isset($_SESSION['interests']);
