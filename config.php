<?php
/**
 * Конфигурация приложения
 */

// Настройки отображения ошибок (для разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Настройки сессии (только если сессия еще не запущена)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
}

// Константы приложения
define('APP_NAME', 'aqum');
define('APP_TAGLINE', 'Образовательная платформа нового поколения');

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'aqum_db');
define('DB_USER', 'root');
define('DB_PASS', 'WayBels2553030App!');
define('DB_CHARSET', 'utf8mb4');

// Доступные категории интересов
define('INTEREST_CATEGORIES', [
    'languages' => ['icon' => '🌍', 'name' => 'Языки'],
    'programming' => ['icon' => '💻', 'name' => 'Программирование'],
    'design' => ['icon' => '🎨', 'name' => 'Дизайн'],
    'marketing' => ['icon' => '📈', 'name' => 'Маркетинг'],
    'growth' => ['icon' => '🌿', 'name' => 'Личностный рост']
]);

// URL для редиректа после входа
define('DASHBOARD_URL', 'feed.php');
