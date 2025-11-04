<?php
/**
 * Dashboard - редирект на feed.php
 * Оставлен для совместимости со старыми ссылками
 */

session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Редирект на новую главную страницу (ленту)
header('Location: feed.php');
exit;
?>
