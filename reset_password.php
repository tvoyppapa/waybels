<?php
/**
 * Заглушка для сброса пароля
 */

session_start();
require_once 'helpers.php';

setFlashMessage('error', 'Функция восстановления пароля еще не реализована. Обратитесь к администратору.');
header("Location: index.php");
exit;

/*
// Пример реализации сброса пароля:

require_once 'config.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (validateEmail($email)) {
        // Проверяем существование пользователя
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            // Генерируем токен
            $token = bin2hex(random_bytes(32));
            
            // Сохраняем токен
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
            $stmt->execute([$email, $token]);
            
            // Отправляем email (требует настройки почты)
            $resetLink = "http://yoursite.com/reset_password.php?token=$token";
            // mail($email, "Сброс пароля", "Перейдите по ссылке: $resetLink");
            
            setFlashMessage('success', 'Ссылка для сброса пароля отправлена на email');
        } else {
            setFlashMessage('error', 'Пользователь с таким email не найден');
        }
    } else {
        setFlashMessage('error', 'Неверный формат email');
    }
    
    header("Location: index.php");
    exit;
}
*/
