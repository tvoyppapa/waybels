<?php
/**
 * Логика аутентификации и регистрации
 */

// ОТЛАДКА - удалить в продакшене
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

session_start();

// Инициализация переменных
$error = null;
$success = null;
$currentView = 'quiz'; // quiz, login, register

// Определяем текущий вид
if (isset($_SESSION['interests'])) {
    $currentView = 'login';
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
    } else {
        $interests = $_POST['interests'];
        if (array_key_exists($interests, INTEREST_CATEGORIES)) {
            $_SESSION['interests'] = $interests;
            $currentView = 'login';
            redirect($_SERVER['PHP_SELF']);
        } else {
            $error = "Неверная категория";
        }
    }
}

/**
 * Обработка входа
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
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
    redirect($_SERVER['PHP_SELF']);
}
