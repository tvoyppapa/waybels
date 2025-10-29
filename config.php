<?php
/**
 * Конфигурация приложения
 */

// Настройки отображения ошибок (для разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Константы приложения
define('APP_NAME', 'Wibs');
define('APP_TAGLINE', 'Пространство, где знание превращается в опыт');

// Настройки базы данных (настройте под свою БД)
define('DB_HOST', 'localhost');
define('DB_NAME', 'wibs');
define('DB_USER', 'root');
define('DB_PASS', '');
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
define('DASHBOARD_URL', 'dashboard.html');
