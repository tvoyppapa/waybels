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
    return strlen(trim($name)) >= 2;
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
