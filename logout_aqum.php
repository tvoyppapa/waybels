<?php
/**
 * AQUM - Выход из системы
 */

session_start();

// Очистка сессии
$_SESSION = [];

// Удаление cookie сессии
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Уничтожение сессии
session_destroy();

// Редирект на страницу входа
header('Location: auth_aqum.php');
exit;
