<?php
/**
 * Вспомогательные функции
 */

/**
 * Генерация CSRF токена
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Проверка CSRF токена
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Валидация email
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Валидация пароля (минимум 6 символов)
 */
function validatePassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }
    
    return $errors;
}

/**
 * Валидация имени
 */
function validateName(string $name): bool {
    $name = trim($name);
    
    // Минимум 2 символа
    if (strlen($name) < 2) {
        return false;
    }
    
    // Только буквы, пробелы и дефисы (без цифр, подчеркиваний, точек)
    if (!preg_match('/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u', $name)) {
        return false;
    }
    
    return true;
}

/**
 * Форматирование имени (первая буква заглавная)
 */
function formatName(string $name): string {
    $name = trim($name);
    
    // Разбиваем на слова
    $words = preg_split('/\s+/u', $name);
    
    // Делаем первую букву каждого слова заглавной
    $words = array_map(function($word) {
        return mb_convert_case(mb_strtolower($word), MB_CASE_TITLE, 'UTF-8');
    }, $words);
    
    return implode(' ', $words);
}

/**
 * Безопасный вывод HTML
 */
function e(?string $string): string {
    return $string ? htmlspecialchars($string, ENT_QUOTES, 'UTF-8') : '';
}

/**
 * Редирект
 */
function redirect(string $url): void {
    // Если headers уже отправлены, используем JavaScript
    if (headers_sent()) {
        echo "<script>window.location.href='$url';</script>";
        echo "<meta http-equiv='refresh' content='0;url=$url'>";
        exit;
    }
    
    header("Location: $url");
    exit;
}

/**
 * Получение старых значений формы после ошибки
 */
function old(string $key, string $default = ''): string {
    return $_SESSION['old'][$key] ?? $default;
}

/**
 * Сохранение старых значений формы
 */
function saveOldInput(array $data): void {
    $_SESSION['old'] = $data;
}

/**
 * Очистка старых значений
 */
function clearOldInput(): void {
    unset($_SESSION['old']);
}

/**
 * Установка flash сообщения
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Получение flash сообщения
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Форматирование времени "назад" (time ago)
 */
function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'только что';
    }
    
    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' ' . pluralize($minutes, 'минута', 'минуты', 'минут') . ' назад';
    }
    
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . pluralize($hours, 'час', 'часа', 'часов') . ' назад';
    }
    
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ' . pluralize($days, 'день', 'дня', 'дней') . ' назад';
    }
    
    if ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' ' . pluralize($weeks, 'неделя', 'недели', 'недель') . ' назад';
    }
    
    if ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' ' . pluralize($months, 'месяц', 'месяца', 'месяцев') . ' назад';
    }
    
    $years = floor($diff / 31536000);
    return $years . ' ' . pluralize($years, 'год', 'года', 'лет') . ' назад';
}

/**
 * Плюрализация русских слов
 */
function pluralize(int $number, string $one, string $few, string $many): string {
    $mod10 = $number % 10;
    $mod100 = $number % 100;
    
    if ($mod10 === 1 && $mod100 !== 11) {
        return $one;
    }
    
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
        return $few;
    }
    
    return $many;
}
