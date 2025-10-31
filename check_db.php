<?php
require_once 'db.php';

echo "<h2>Проверка подключения к БД</h2>";

try {
    // Проверяем подключение
    $stmt = $pdo->query("SELECT DATABASE()");
    $db = $stmt->fetchColumn();
    echo "✅ Подключено к БД: <strong>$db</strong><br><br>";
    
    // Проверяем таблицы
    echo "<h3>Таблицы в БД:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ <strong style='color:red'>ТАБЛИЦ НЕТ!</strong><br><br>";
        echo "Нужно импортировать: <code>database_waybels.sql</code><br>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>✅ $table</li>";
        }
        echo "</ul>";
        
        // Проверяем пользователей
        echo "<h3>Пользователи в БД:</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetchColumn();
        echo "Всего пользователей: <strong>$count</strong><br>";
        
        if ($count > 0) {
            $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 5");
            $users = $stmt->fetchAll();
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Имя</th><th>Email</th><th>Роль</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['name']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['role']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ <strong style='color:red'>Ошибка:</strong> " . $e->getMessage();
}
?>
