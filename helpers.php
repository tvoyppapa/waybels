<?php
/**
 * AQUM - Вспомогательные функции
 */

/**
 * ═══════════════════════════════════════════
 * CSRF ЗАЩИТА
 * ═══════════════════════════════════════════
 */

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ═══════════════════════════════════════════
 * ВАЛИДАЦИЯ
 * ═══════════════════════════════════════════
 */

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone(string $phone): bool {
    // Простая проверка: начинается с + и содержит 10-15 цифр
    return preg_match('/^\+\d{10,15}$/', $phone);
}

function validatePassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }
    
    return $errors;
}

function validateName(string $name): bool {
    return strlen(trim($name)) >= 2;
}

function validateUsername(string $username): bool {
    // Только латиница, цифры, подчеркивание, 3-50 символов
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

/**
 * ═══════════════════════════════════════════
 * БЕЗОПАСНОСТЬ
 * ═══════════════════════════════════════════
 */

function e(?string $string): string {
    return $string ? htmlspecialchars($string, ENT_QUOTES, 'UTF-8') : '';
}

function sanitizeInput(string $input): string {
    return trim(strip_tags($input));
}

/**
 * ═══════════════════════════════════════════
 * РЕДИРЕКТЫ И FLASH СООБЩЕНИЯ
 * ═══════════════════════════════════════════
 */

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * ═══════════════════════════════════════════
 * РАБОТА С ФОРМАМИ
 * ═══════════════════════════════════════════
 */

function old(string $key, string $default = ''): string {
    return $_SESSION['old'][$key] ?? $default;
}

function saveOldInput(array $data): void {
    $_SESSION['old'] = $data;
}

function clearOldInput(): void {
    unset($_SESSION['old']);
}

/**
 * ═══════════════════════════════════════════
 * АВАТАРЫ И ИНИЦИАЛЫ
 * ═══════════════════════════════════════════
 */

function getAvatar(?string $avatarUrl, string $name, int $size = 100): string {
    if ($avatarUrl) {
        return sprintf(
            '<img src="%s" alt="%s" class="avatar" width="%d" height="%d">',
            e($avatarUrl),
            e($name),
            $size,
            $size
        );
    }
    
    // Генерируем инициал
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
    $color = BRAND_COLOR;
    
    return sprintf(
        '<div class="avatar-initial" style="width:%dpx;height:%dpx;background:%s;">
            <span>%s</span>
        </div>',
        $size,
        $size,
        $color,
        e($initial)
    );
}

function getInitial(string $name): string {
    return mb_strtoupper(mb_substr($name, 0, 1));
}

/**
 * ═══════════════════════════════════════════
 * ФОРМАТИРОВАНИЕ
 * ═══════════════════════════════════════════
 */

function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return "только что";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . " " . plural($minutes, 'минута', 'минуты', 'минут') . " назад";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " " . plural($hours, 'час', 'часа', 'часов') . " назад";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " " . plural($days, 'день', 'дня', 'дней') . " назад";
    } else {
        return date('d.m.Y', $timestamp);
    }
}

function plural(int $number, string $one, string $two, string $five): string {
    $n = abs($number);
    $n %= 100;
    if ($n >= 5 && $n <= 20) {
        return $five;
    }
    $n %= 10;
    if ($n === 1) {
        return $one;
    }
    if ($n >= 2 && $n <= 4) {
        return $two;
    }
    return $five;
}

function formatPrice(float $price, string $currency = DEFAULT_CURRENCY): string {
    $symbol = CURRENCIES[$currency]['symbol'] ?? '$';
    return number_format($price, 0, ',', ' ') . ' ' . $symbol;
}

function truncateText(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * ═══════════════════════════════════════════
 * ПРОВЕРКА АВТОРИЗАЦИИ
 * ═══════════════════════════════════════════
 */

function requireAuth(): void {
    if (!isset($_SESSION['user_id'])) {
        redirect('auth_aqum.php');
    }
}

function requireRole(string $role): void {
    requireAuth();
    if ($_SESSION['user_role'] !== $role) {
        http_response_code(403);
        die("Доступ запрещен");
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isTeacher(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher';
}

/**
 * ═══════════════════════════════════════════
 * IP И АНТИФРОД
 * ═══════════════════════════════════════════
 */

function getUserIP(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * ═══════════════════════════════════════════
 * ОНБОРДИНГ
 * ═══════════════════════════════════════════
 */

function completeOnboardingTask(PDO $pdo, int $userId, string $taskKey): void {
    $stmt = $pdo->prepare(
        "INSERT INTO onboarding_tasks (user_id, task_key, is_done, done_at)
         VALUES (?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE is_done = 1, done_at = NOW()"
    );
    $stmt->execute([$userId, $taskKey]);
}

function getOnboardingTasks(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        "SELECT task_key, is_done, done_at
         FROM onboarding_tasks
         WHERE user_id = ?"
    );
    $stmt->execute([$userId]);
    
    $tasks = [];
    while ($row = $stmt->fetch()) {
        $tasks[$row['task_key']] = $row;
    }
    
    return $tasks;
}

/**
 * ═══════════════════════════════════════════
 * УВЕДОМЛЕНИЯ
 * ═══════════════════════════════════════════
 */

function createNotification(
    PDO $pdo, 
    int $userId, 
    string $type, 
    string $title, 
    ?array $payload = null,
    ?string $linkUrl = null
): void {
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, type, title, payload, link_url)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $userId,
        $type,
        $title,
        $payload ? json_encode($payload) : null,
        $linkUrl
    ]);
}

/**
 * ═══════════════════════════════════════════
 * JSON RESPONSES
 * ═══════════════════════════════════════════
 */

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonSuccess(string $message = 'Success', array $data = []): void {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

function jsonError(string $message, int $status = 400, array $errors = []): void {
    jsonResponse([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $status);
}

/**
 * ═══════════════════════════════════════════
 * ЛОГИРОВАНИЕ
 * ═══════════════════════════════════════════
 */

function logError(string $message, array $context = []): void {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    error_log($logMessage, 3, ERROR_LOG);
}

function logAuth(string $action, int $userId, bool $success): void {
    $timestamp = date('Y-m-d H:i:s');
    $ip = getUserIP();
    $status = $success ? 'SUCCESS' : 'FAILED';
    $logMessage = "[$timestamp] $action - User ID: $userId - IP: $ip - Status: $status\n";
    error_log($logMessage, 3, AUTH_LOG);
}

/**
 * ═══════════════════════════════════════════
 * РАЗНОЕ
 * ═══════════════════════════════════════════
 */

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function generateUsername(string $name): string {
    // Транслитерация имени
    $transliterated = transliterate($name);
    // Удаляем все кроме букв, цифр и подчеркивания
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $transliterated);
    // Добавляем случайное число
    $username .= rand(100, 999);
    return strtolower($username);
}

function transliterate(string $text): string {
    $converter = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
    
    $text = mb_strtolower($text);
    $text = strtr($text, $converter);
    return $text;
}

function debug($data): void {
    if (DEBUG_MODE) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}
