<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

echo "<h2>Тест регистрации</h2>";

// Проверяем структуру таблицы users
echo "<h3>Структура таблицы users:</h3>";
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Default</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Тестовая регистрация:</h3>";

// Пробуем зарегистрировать пользователя
try {
    $name = "Тест";
    $email = "test_" . time() . "@test.com";
    $password = password_hash("123456", PASSWORD_BCRYPT);
    
    // Проверяем, есть ли колонка interests
    $columns_list = array_column($columns, 'Field');
    
    if (in_array('interests', $columns_list)) {
        echo "✅ Колонка 'interests' есть<br>";
        $sql = "INSERT INTO users (name, email, password, interests, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $password, 'programming']);
    } else {
        echo "⚠️ Колонки 'interests' НЕТ! Используем упрощенную вставку<br>";
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $password]);
    }
    
    echo "✅ <strong style='color:green'>Регистрация успешна!</strong><br>";
    echo "Email: $email<br>";
    echo "ID: " . $pdo->lastInsertId() . "<br>";
    
} catch (Exception $e) {
    echo "❌ <strong style='color:red'>Ошибка:</strong> " . $e->getMessage();
}
?>
