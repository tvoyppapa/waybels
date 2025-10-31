<?php
/**
 * Выход из системы
 */

session_start();

// Уничтожаем все данные сессии
$_SESSION = array();

// Удаляем cookie сессии
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную страницу
header("Location: index.php");
exit;
