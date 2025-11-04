<?php
/**
 * AQUM - Подключение к базе данных
 */

require_once __DIR__ . '/config.php';

try {
    // Используем aqum_db
    $dsn = "mysql:host=localhost;dbname=aqum_db;charset=utf8mb4";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    
    // Логирование успешного подключения (только в режиме отладки)
    if (DEBUG_MODE) {
        error_log("[AQUM DB] Подключение к базе данных успешно: " . DB_NAME);
    }
    
} catch (PDOException $e) {
    // Логирование ошибки
    $error_message = sprintf(
        "[AQUM DB ERROR] %s - %s:%d\n%s",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    
    error_log($error_message);
    
    // В продакшене не показываем детали ошибки
    if (DEBUG_MODE) {
        die("Ошибка подключения к БД: " . $e->getMessage());
    } else {
        die("Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.");
    }
}

/**
 * Вспомогательные функции для работы с БД
 */

/**
 * Получить пользователя по ID
 */
function getUserById(int $userId): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Получить пользователя по username
 */
function getUserByUsername(string $username): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

/**
 * Получить пользователя по email
 */
function getUserByEmail(string $email): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

/**
 * Получить пользователя по телефону
 */
function getUserByPhone(string $phone): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetch() ?: null;
}

/**
 * Проверка, доступен ли преподаватель (онлайн за последние 48 часов)
 */
function isTeacherAvailable(array $user): bool {
    if (!$user['is_teacher'] || !$user['is_verified_teacher']) {
        return false;
    }
    
    $lastSeen = strtotime($user['last_seen_at']);
    $threshold = time() - (ONLINE_THRESHOLD_HOURS * 3600);
    
    return $lastSeen >= $threshold;
}

/**
 * Получить количество заказов преподавателя (метрика)
 */
function getTeacherOrdersCount(int $teacherId): int {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as cnt 
         FROM lesson_orders 
         WHERE teacher_id = ? AND status IN ('paid','confirmed','completed')"
    );
    $stmt->execute([$teacherId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Получить количество уникальных учеников преподавателя (метрика)
 */
function getTeacherStudentsCount(int $teacherId): int {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT student_id) as cnt 
         FROM lesson_orders 
         WHERE teacher_id = ? AND status IN ('paid','confirmed','completed')"
    );
    $stmt->execute([$teacherId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Получить средний рейтинг преподавателя
 */
function getTeacherRating(int $teacherId): ?float {
    global $pdo;
    
    // Проверяем, достаточно ли уроков для показа рейтинга
    $ordersCount = getTeacherOrdersCount($teacherId);
    if ($ordersCount < MIN_LESSONS_FOR_RATING) {
        return null;
    }
    
    $stmt = $pdo->prepare(
        "SELECT AVG(rating) as avg_rating, COUNT(*) as reviews_count
         FROM reviews 
         WHERE teacher_id = ?"
    );
    $stmt->execute([$teacherId]);
    $result = $stmt->fetch();
    
    if ($result && $result['reviews_count'] > 0) {
        return round($result['avg_rating'], 2);
    }
    
    return null;
}

/**
 * Получить количество непрочитанных сообщений пользователя
 */
function getUnreadMessagesCount(int $userId): int {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT SUM(unread_count) as total
         FROM channel_members 
         WHERE user_id = ?"
    );
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Обновить время последней активности пользователя
 */
function updateUserLastSeen(int $userId): void {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET last_seen_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
}

/**
 * Логирование попытки входа/регистрации (для антифрода)
 */
function logAuthAttempt(string $action, string $ip, ?int $userId = null): void {
    global $pdo;
    $stmt = $pdo->prepare(
        "INSERT INTO ip_guard (ip, action, user_id, user_agent) 
         VALUES (INET6_ATON(?), ?, ?, ?)"
    );
    $stmt->execute([
        $ip,
        $action,
        $userId,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

/**
 * Проверка лимита аккаунтов с одного IP
 */
function checkIpLimit(string $ip): bool {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT user_id) as cnt
         FROM ip_guard
         WHERE ip = INET6_ATON(?) AND action = 'register'
         AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $stmt->execute([$ip]);
    $count = (int)$stmt->fetchColumn();
    
    return $count < MAX_ACCOUNTS_PER_IP;
}
