<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 Применение SQL скрипта к базе данных wibs...\n\n";

// Подключение к БД
$host = "localhost";
$dbname = "wibs";
$username = "root";
$password = "WayBels2553030App!";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Подключение к базе данных установлено\n\n";
} catch (PDOException $e) {
    die("❌ Ошибка подключения: " . $e->getMessage() . "\n");
}

// Читаем SQL файл
$sqlFile = __DIR__ . '/ADD_TEACHER_TABLES.sql';
$sql = file_get_contents($sqlFile);

// Удаляем USE wibs;
$sql = preg_replace('/USE\s+wibs;/i', '', $sql);

// Разделяем на отдельные запросы
$queries = array_filter(
    array_map('trim', explode(';', $sql)),
    function($query) {
        return !empty($query) && !preg_match('/^--/', $query);
    }
);

echo "📋 Найдено " . count($queries) . " SQL запросов\n\n";

$executed = 0;
$errors = 0;

foreach ($queries as $index => $query) {
    if (empty(trim($query))) continue;
    
    try {
        $pdo->exec($query);
        $executed++;
        
        // Выводим первые слова запроса для отладки
        $preview = substr(preg_replace('/\s+/', ' ', trim($query)), 0, 60);
        echo "✅ Запрос " . ($index + 1) . ": " . $preview . "...\n";
        
    } catch (PDOException $e) {
        $errors++;
        $preview = substr(preg_replace('/\s+/', ' ', trim($query)), 0, 60);
        echo "⚠️  Запрос " . ($index + 1) . ": " . $preview . "...\n";
        echo "   Ошибка: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Выполнено успешно: $executed\n";
echo "⚠️  Ошибок/предупреждений: $errors\n";
echo str_repeat("=", 60) . "\n\n";

// Проверяем созданные таблицы
echo "📊 Проверка таблиц:\n\n";

$tables = ['teacher_profiles', 'posts', 'favorites', 'chats', 'messages', 'teacher_applications'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ $table: " . $result['count'] . " записей\n";
    } catch (PDOException $e) {
        echo "❌ $table: таблица не существует или ошибка доступа\n";
    }
}

echo "\n🎉 Готово! Таблицы успешно созданы.\n";
?>
