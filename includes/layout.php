<?php
/**
 * Главный шаблон страницы aqum
 * 
 * Использование:
 * 
 * // В начале страницы
 * $page_title = "Заголовок страницы";
 * $page_css = ['custom.css']; // Опционально - дополнительные стили
 * $page_js = ['custom.js']; // Опционально - дополнительные скрипты
 * $compact = false; // true для компактного меню (только иконки)
 * $hide_nav = false; // true для скрытия нижнего меню на мобильных
 * $show_back = false; // true для кнопки "Назад"
 * $back_url = '/'; // URL для кнопки "Назад"
 * 
 * // Запуск рендера хедера
 * ob_start();
 * require_once 'includes/layout_header.php';
 * 
 * // Ваш контент здесь
 * ?>
 * <div class="your-content">
 *   ...
 * </div>
 * <?php
 * 
 * // Запуск рендера футера
 * require_once 'includes/layout_footer.php';
 */

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

// Подключение зависимостей
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

// Параметры по умолчанию
$page_title = $page_title ?? 'aqum';
$page_css = $page_css ?? [];
$page_js = $page_js ?? [];
$compact = $compact ?? false;
$hide_nav = $hide_nav ?? false;
$show_back = $show_back ?? false;
$back_url = $back_url ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - aqum</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style/dashboard.css">
    <?php foreach ($page_css as $css): ?>
    <link rel="stylesheet" href="/style/<?= e($css) ?>">
    <?php endforeach; ?>
</head>
<body>
    
    <!-- Боковое меню (Desktop) -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Основной контент -->
    <main class="main-content">
        <!-- Хедер с профилем -->
        <?php include __DIR__ . '/header.php'; ?>
        
        <!-- Контент страницы -->
        <div class="page-content">
