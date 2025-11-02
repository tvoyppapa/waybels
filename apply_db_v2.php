<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 Обновление базы данных WayBels v2.0...\n\n";

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
$sqlFile = __DIR__ . '/UPDATE_DB_V2.sql';
if (!file_exists($sqlFile)) {
    die("❌ Файл UPDATE_DB_V2.sql не найден\n");
}

$sql = file_get_contents($sqlFile);

// Удаляем USE wibs;
$sql = preg_replace('/USE\s+wibs;/i', '', $sql);

// Разделяем на отдельные запросы
$queries = array_filter(
    array_map('trim', explode(';', $sql)),
    function($query) {
        $query = trim($query);
        return !empty($query) && 
               !preg_match('/^--/', $query) && 
               !preg_match('/^\/\*/', $query);
    }
);

echo "📋 Найдено " . count($queries) . " SQL запросов\n\n";

$executed = 0;
$errors = 0;
$skipped = 0;

foreach ($queries as $index => $query) {
    if (empty(trim($query))) continue;
    
    try {
        $pdo->exec($query);
        $executed++;
        
        // Выводим первые слова запроса
        $preview = substr(preg_replace('/\s+/', ' ', trim($query)), 0, 70);
        echo "✅ [" . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . "] " . $preview . "...\n";
        
    } catch (PDOException $e) {
        // Некоторые ошибки нормальны (например, колонка уже существует)
        if (strpos($e->getMessage(), 'Duplicate column') !== false ||
            strpos($e->getMessage(), 'already exists') !== false) {
            $skipped++;
            $preview = substr(preg_replace('/\s+/', ' ', trim($query)), 0, 70);
            echo "⚠️  [" . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . "] " . $preview . "... (уже существует)\n";
        } else {
            $errors++;
            $preview = substr(preg_replace('/\s+/', ' ', trim($query)), 0, 70);
            echo "❌ [" . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . "] " . $preview . "...\n";
            echo "   Ошибка: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Выполнено успешно: $executed\n";
echo "⚠️  Пропущено (уже есть): $skipped\n";
echo "❌ Ошибок: $errors\n";
echo str_repeat("=", 70) . "\n\n";

// Проверяем созданные таблицы
echo "📊 Проверка новых таблиц:\n\n";

$newTables = [
    'chat_settings',
    'teacher_availability',
    'bookings',
    'bot_messages',
    'achievements',
    'user_achievements',
    'saved_profiles',
    'username_history',
    'notifications'
];

$tableStatus = [];
foreach ($newTables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $tableStatus[$table] = [
            'exists' => true,
            'count' => $result['count']
        ];
        echo "✅ $table: " . $result['count'] . " записей\n";
    } catch (PDOException $e) {
        $tableStatus[$table] = [
            'exists' => false,
            'count' => 0
        ];
        echo "❌ $table: не существует или ошибка доступа\n";
    }
}

// Проверяем новые поля в users
echo "\n📊 Проверка новых полей в таблице users:\n\n";

$newFields = ['username', 'bio', 'theme', 'username_changed_at', 'balance', 'is_online', 'last_seen'];

try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($newFields as $field) {
        if (in_array($field, $columns)) {
            echo "✅ $field - добавлено\n";
        } else {
            echo "❌ $field - отсутствует\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Ошибка проверки: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "🎉 Обновление базы данных завершено!\n";
echo str_repeat("=", 70) . "\n\n";

// Статистика
echo "📈 Статистика базы данных:\n\n";
try {
    $stats = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'teacher_profiles' => $pdo->query("SELECT COUNT(*) FROM teacher_profiles")->fetchColumn(),
        'messages' => $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
        'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'achievements' => $pdo->query("SELECT COUNT(*) FROM achievements")->fetchColumn(),
    ];
    
    foreach ($stats as $table => $count) {
        echo sprintf("%-20s: %s\n", ucfirst($table), $count);
    }
} catch (PDOException $e) {
    echo "⚠️ Не удалось получить статистику\n";
}

echo "\n✨ Готово! Переходим к обновлению дизайна...\n";
?>
