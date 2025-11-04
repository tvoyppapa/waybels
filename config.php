<?php
/**
 * AQUM - Конфигурация приложения
 * Экосистема для онлайн-репетиторов
 */

// Настройки отображения ошибок (для разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Настройки сессии (безопасность)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Константы приложения
define('APP_NAME', 'AQUM');
define('APP_TAGLINE', 'Экосистема для онлайн-репетиторов');
define('APP_VERSION', '1.0.0');

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'aqum_db');
define('DB_USER', 'root');
define('DB_PASS', 'WayBels2553030App!');
define('DB_CHARSET', 'utf8mb4');

// Брендовые цвета
define('BRAND_COLOR', '#7F2CDF');
define('BRAND_COLOR_LIGHT', '#9B5CF9');
define('BRAND_GRADIENT', 'linear-gradient(135deg, #7F2CDF 0%, #9B5CF9 100%)');

// Доступные предметы (синхронизировано с БД)
define('SUBJECTS', [
    'english' => ['name' => 'Английский язык', 'icon' => '🇬🇧'],
    'math' => ['name' => 'Математика', 'icon' => '🔢'],
    'programming' => ['name' => 'Программирование', 'icon' => '💻'],
    'design' => ['name' => 'Дизайн', 'icon' => '🎨'],
    'music' => ['name' => 'Музыка', 'icon' => '🎵'],
    'physics' => ['name' => 'Физика', 'icon' => '⚛️'],
    'chemistry' => ['name' => 'Химия', 'icon' => '🧪'],
    'biology' => ['name' => 'Биология', 'icon' => '🧬']
]);

// Темы оформления
define('THEMES', ['auto', 'dark', 'light']);
define('DEFAULT_THEME', 'dark');

// Роли пользователей
define('ROLES', ['user', 'teacher', 'admin']);

// Статусы верификации преподавателя
define('TEACHER_STATUSES', [
    'draft' => 'Черновик',
    'submitted' => 'На проверке',
    'approved' => 'Одобрен',
    'rejected' => 'Отклонен'
]);

// Код подтверждения (DEV - заменить на реальную отправку)
define('VERIFICATION_CODE', '1111');

// Настройки онлайн-статуса
define('ONLINE_THRESHOLD_HOURS', 48); // Скрывать преподавателя, если оффлайн > 48 часов

// Минимальное количество уроков для показа рейтинга
define('MIN_LESSONS_FOR_RATING', 10);

// Настройки пагинации
define('POSTS_PER_PAGE', 20);
define('TEACHERS_PER_PAGE', 20);
define('MESSAGES_PER_PAGE', 50);

// URL после успешного входа
define('DASHBOARD_URL', 'feed.php');

// Пути к файлам
define('UPLOADS_DIR', __DIR__ . '/uploads');
define('AVATARS_DIR', UPLOADS_DIR . '/avatars');
define('DOCUMENTS_DIR', UPLOADS_DIR . '/documents');

// Максимальные размеры загрузок
define('MAX_AVATAR_SIZE', 5 * 1024 * 1024); // 5 MB
define('MAX_DOCUMENT_SIZE', 10 * 1024 * 1024); // 10 MB

// Настройки антифрод
define('MAX_ACCOUNTS_PER_IP', 5);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT_MINUTES', 15);

// Валюты
define('DEFAULT_CURRENCY', 'RUB');
define('CURRENCIES', [
    'RUB' => ['symbol' => '₽', 'name' => 'Рубли'],
    'USD' => ['symbol' => '$', 'name' => 'Доллары'],
    'EUR' => ['symbol' => '€', 'name' => 'Евро']
]);

// Комиссия платформы (%)
define('PLATFORM_COMMISSION', 15);

// Минимальная сумма для вывода средств
define('MIN_WITHDRAWAL_AMOUNT', 1000);

// Таймзоны
define('DEFAULT_TIMEZONE', 'Europe/Moscow');
date_default_timezone_set(DEFAULT_TIMEZONE);

// Локали
define('DEFAULT_LOCALE', 'ru');
define('AVAILABLE_LOCALES', ['ru', 'en']);

// Email настройки (для будущей реализации)
define('MAIL_FROM', 'noreply@aqum.com');
define('MAIL_FROM_NAME', 'AQUM Platform');

// Длительность сторис (часы)
define('STORY_DURATION_HOURS', 24);

// WebRTC настройки (для будущей реализации)
define('WEBRTC_ENABLED', false);
define('WEBRTC_STUN_SERVER', 'stun:stun.l.google.com:19302');

// API ключи (для будущей реализации)
// define('STRIPE_PUBLIC_KEY', '');
// define('STRIPE_SECRET_KEY', '');
// define('YOOKASSA_SHOP_ID', '');
// define('YOOKASSA_SECRET_KEY', '');

// Режим отладки
define('DEBUG_MODE', true);

// Логирование
define('LOG_DIR', __DIR__ . '/logs');
define('ERROR_LOG', LOG_DIR . '/errors.log');
define('AUTH_LOG', LOG_DIR . '/auth.log');

// Создаем необходимые директории
$directories = [UPLOADS_DIR, AVATARS_DIR, DOCUMENTS_DIR, LOG_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
