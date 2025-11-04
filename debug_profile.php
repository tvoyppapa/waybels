<?php
session_start();
require_once 'config.php';
require_once 'db.php';

echo '<h1>🔍 ОТЛАДКА ПРОФИЛЯ</h1>';

echo '<h2>1. GET параметры:</h2>';
echo '<pre>';
print_r($_GET);
echo '</pre>';

echo '<h2>2. REQUEST_URI:</h2>';
echo '<pre>' . $_SERVER['REQUEST_URI'] . '</pre>';

echo '<h2>3. Структура таблицы users:</h2>';
try {
    $stmt = $pdo->query("DESCRIBE users");
    echo '<pre>';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    echo '</pre>';
} catch (Exception $e) {
    echo '<pre>ОШИБКА: ' . $e->getMessage() . '</pre>';
}

echo '<h2>4. Все пользователи:</h2>';
try {
    $stmt = $pdo->query("SELECT id, name, email FROM users LIMIT 5");
    echo '<table border="1" style="border-collapse: collapse;">';
    echo '<tr><th>ID</th><th>Name</th><th>Email</th></tr>';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} catch (Exception $e) {
    echo '<pre>ОШИБКА: ' . $e->getMessage() . '</pre>';
}

echo '<h2>5. Проверка username колонки:</h2>';
try {
    $stmt = $pdo->query("SELECT id, name, username, email FROM users LIMIT 5");
    echo '<table border="1" style="border-collapse: collapse;">';
    echo '<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th></tr>';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['username'] ?? 'NULL') . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} catch (Exception $e) {
    echo '<pre style="color: red;">ОШИБКА username: ' . $e->getMessage() . '</pre>';
    echo '<p style="color: red; font-weight: bold;">❌ Колонка username НЕ СУЩЕСТВУЕТ!</p>';
    echo '<p>Выполни SQL:</p>';
    echo '<pre style="background: #f0f0f0; padding: 10px;">
USE aqum_db;
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER name;
UPDATE users SET username = CONCAT(\'user\', id);
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL;
CREATE UNIQUE INDEX unique_username ON users(username);
</pre>';
}

echo '<h2>6. Тестовые ссылки:</h2>';
echo '<ul>';
echo '<li><a href="/@test">/@test</a></li>';
echo '<li><a href="/@ivan">/@ivan</a></li>';
echo '<li><a href="/profile.php">profile.php (мой профиль)</a></li>';
echo '</ul>';

echo '<h2>7. Проверка .htaccess:</h2>';
if (file_exists('.htaccess')) {
    echo '<p style="color: green;">✅ .htaccess существует</p>';
    echo '<pre>' . htmlspecialchars(file_get_contents('.htaccess')) . '</pre>';
} else {
    echo '<p style="color: red;">❌ .htaccess НЕ НАЙДЕН!</p>';
}

echo '<h2>8. Проверка mod_rewrite:</h2>';
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo '<p style="color: green;">✅ mod_rewrite ВКЛЮЧЕН</p>';
    } else {
        echo '<p style="color: red;">❌ mod_rewrite ВЫКЛЮЧЕН!</p>';
        echo '<p>Включи командой: <code>sudo a2enmod rewrite && sudo systemctl restart apache2</code></p>';
    }
} else {
    echo '<p style="color: orange;">⚠️ Не могу проверить (функция apache_get_modules недоступна)</p>';
}
