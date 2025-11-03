<?php
/**
 * Тест подключения и авторизации
 * Открой этот файл в браузере чтобы проверить
 */

echo "<h1>Тест системы aqum</h1>";

// 1. Тест подключения к БД
echo "<h2>1. Проверка подключения к БД</h2>";
try {
    require_once 'db.php';
    echo "✅ <strong>Подключение к БД успешно!</strong><br>";
    echo "База данных: aqum_db<br><br>";
} catch (Exception $e) {
    echo "❌ <strong>Ошибка подключения:</strong> " . $e->getMessage() . "<br><br>";
    die();
}

// 2. Проверка таблицы users
echo "<h2>2. Проверка таблицы users</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ <strong>Таблица users существует</strong><br>";
    echo "Пользователей в БД: " . $result['count'] . "<br><br>";
} catch (Exception $e) {
    echo "❌ <strong>Ошибка:</strong> " . $e->getMessage() . "<br>";
    echo "💡 <strong>Решение:</strong> Импортируй database_aqum_ready.sql<br><br>";
    die();
}

// 3. Проверка тестового пользователя
echo "<h2>3. Проверка тестового пользователя</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['test@test.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ <strong>Тестовый пользователь найден!</strong><br>";
        echo "Email: " . htmlspecialchars($user['email']) . "<br>";
        echo "Имя: " . htmlspecialchars($user['name']) . "<br>";
        echo "Роль: " . htmlspecialchars($user['role']) . "<br><br>";
    } else {
        echo "❌ <strong>Тестовый пользователь НЕ найден</strong><br>";
        echo "💡 <strong>Решение:</strong> Импортируй database_aqum_ready.sql<br><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Ошибка:</strong> " . $e->getMessage() . "<br><br>";
}

// 4. Проверка пароля
echo "<h2>4. Проверка пароля</h2>";
$testPassword = 'password';
if ($user) {
    if (password_verify($testPassword, $user['password'])) {
        echo "✅ <strong>Пароль 'password' работает!</strong><br><br>";
    } else {
        echo "❌ <strong>Пароль НЕ совпадает</strong><br><br>";
    }
}

// 5. Проверка таблицы posts
echo "<h2>5. Проверка таблицы posts</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $result = $stmt->fetch();
    echo "✅ <strong>Таблица posts существует</strong><br>";
    echo "Постов в БД: " . $result['count'] . "<br><br>";
} catch (Exception $e) {
    echo "⚠️ <strong>Таблица posts не найдена</strong><br>";
    echo "Это нормально если ты еще не импортировал полную БД<br><br>";
}

// 6. Проверка config.php
echo "<h2>6. Проверка config.php</h2>";
require_once 'config.php';
echo "APP_NAME: " . APP_NAME . "<br>";
echo "DASHBOARD_URL: " . DASHBOARD_URL . "<br><br>";

// ИТОГ
echo "<hr>";
echo "<h2>📊 ИТОГ</h2>";

if ($user && password_verify($testPassword, $user['password'])) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<strong style='color: #155724;'>✅ ВСЕ РАБОТАЕТ!</strong><br><br>";
    echo "Можешь войти через:<br>";
    echo "Email: <strong>test@test.com</strong><br>";
    echo "Пароль: <strong>password</strong><br><br>";
    echo "<a href='auth.php' style='background: #7F2CDF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Перейти к входу →</a>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<strong style='color: #721c24;'>❌ ЕСТЬ ПРОБЛЕМЫ</strong><br><br>";
    echo "Импортируй БД командой:<br>";
    echo "<code>mysql -u root -p &lt; database_aqum_ready.sql</code><br><br>";
    echo "Или через phpMyAdmin.";
    echo "</div>";
}

echo "<br><br>";
echo "<small>После проверки можешь удалить этот файл (TEST_AUTH.php)</small>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #f5f5f7;
}
h1 {
    color: #7F2CDF;
}
h2 {
    color: #333;
    margin-top: 30px;
}
code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>
