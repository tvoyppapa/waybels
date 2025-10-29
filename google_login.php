<?php
/**
 * Заглушка для Google OAuth
 * Для полноценной работы нужно настроить Google OAuth API
 */

session_start();
require_once 'helpers.php';

// Здесь должна быть интеграция с Google OAuth API
// Для этого нужно:
// 1. Зарегистрировать приложение в Google Cloud Console
// 2. Получить Client ID и Client Secret
// 3. Использовать библиотеку google/apiclient или аналогичную

// Временное сообщение
setFlashMessage('error', 'Google вход еще не настроен. Используйте обычную регистрацию.');
header("Location: index.php");
exit;

/*
// Пример интеграции (требует установки: composer require google/apiclient)

require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'db.php';

$client = new Google_Client();
$client->setClientId('YOUR_CLIENT_ID');
$client->setClientSecret('YOUR_CLIENT_SECRET');
$client->setRedirectUri('http://yoursite.com/google_login.php');
$client->addScope('email');
$client->addScope('profile');

if (!isset($_GET['code'])) {
    // Redirect to Google
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
} else {
    // Get token
    $client->authenticate($_GET['code']);
    $token = $client->getAccessToken();
    
    // Get user info
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    $email = $google_account_info->email;
    $name = $google_account_info->name;
    $google_id = $google_account_info->id;
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // User exists - login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $name;
    } else {
        // Create new user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$name, $email, password_hash(uniqid(), PASSWORD_BCRYPT)]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        
        // Save OAuth info
        $stmt = $pdo->prepare("INSERT INTO oauth_providers (user_id, provider, provider_user_id) VALUES (?, 'google', ?)");
        $stmt->execute([$_SESSION['user_id'], $google_id]);
    }
    
    header("Location: dashboard.html");
    exit;
}
*/
