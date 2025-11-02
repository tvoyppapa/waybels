<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "🔧 Обновление базы данных WayBels v2.0 (ИСПРАВЛЕННАЯ ВЕРСИЯ)...\n\n";

// Подключение к БД
$host = "localhost";
$dbname = "wibs";
$username = "root";
$password = "WayBels2553030App!";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Важно! Включаем буферизацию запросов
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    echo "✅ Подключение к базе данных установлено\n\n";
} catch (PDOException $e) {
    die("❌ Ошибка подключения: " . $e->getMessage() . "\n");
}

// Читаем исправленный SQL файл
$sqlFile = __DIR__ . '/UPDATE_DB_V2_FIXED.sql';
if (!file_exists($sqlFile)) {
    die("❌ Файл UPDATE_DB_V2_FIXED.sql не найден\n");
}

$sql = file_get_contents($sqlFile);

// Удаляем комментарии и USE wibs;
$sql = preg_replace('/USE\s+wibs;/i', '', $sql);
$sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// Разделяем на отдельные запросы по точке с запятой
$queries = array_filter(
    array_map('trim', explode(';', $sql)),
    function($query) {
        return !empty(trim($query));
    }
);

echo "📋 Найдено " . count($queries) . " SQL запросов\n\n";

$executed = 0;
$errors = 0;
$skipped = 0;

foreach ($queries as $index => $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    try {
        $pdo->exec($query);
        $executed++;
        
        // Выводим первые слова запроса
        $preview = substr(preg_replace('/\s+/', ' ', $query), 0, 70);
        echo "✅ [" . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . "] " . $preview . "...\n";
        
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        
        // Некоторые ошибки нормальны
        if (strpos($errorMsg, 'Duplicate column') !== false ||
            strpos($errorMsg, 'already exists') !== false ||
            strpos($errorMsg, '1060') !== false) {
            $skipped++;
            $preview = substr(preg_replace('/\s+/', ' ', $query), 0, 70);
            echo "⚠️  [" . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . "] " . $preview . "... (уже существует)\n";
        } else {
            $errors++;
            $preview = substr(preg_replace('/\s+/', ' ', $query), 0, 70);
            echo "❌ [" . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . "] " . $preview . "...\n";
            echo "   Ошибка: " . $errorMsg . "\n\n";
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

$successCount = 0;
foreach ($newTables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ $table: " . $result['count'] . " записей\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "❌ $table: не существует\n";
    }
}

// Проверяем новые поля в users
echo "\n📊 Проверка новых полей в таблице users:\n\n";

$newFields = ['username', 'bio', 'theme', 'username_changed_at', 'balance', 'is_online', 'last_seen'];

try {
    $stmt = $pdo->query("DESCRIBE users");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columns = array_column($rows, 'Field');
    
    $fieldsCount = 0;
    foreach ($newFields as $field) {
        if (in_array($field, $columns)) {
            echo "✅ $field - добавлено\n";
            $fieldsCount++;
        } else {
            echo "❌ $field - отсутствует\n";
        }
    }
    
    echo "\n✅ Добавлено полей: $fieldsCount / " . count($newFields) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Ошибка проверки: " . $e->getMessage() . "\n";
}

// Проверяем поля в messages
echo "\n📊 Проверка полей в таблице messages:\n\n";

$messageFields = ['status', 'file_url', 'file_type'];

try {
    $stmt = $pdo->query("DESCRIBE messages");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columns = array_column($rows, 'Field');
    
    foreach ($messageFields as $field) {
        if (in_array($field, $columns)) {
            echo "✅ $field - добавлено\n";
        } else {
            echo "❌ $field - отсутствует\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Таблица messages не найдена\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "🎉 Обновление базы данных завершено!\n";
echo str_repeat("=", 70) . "\n\n";

// Статистика
echo "📈 Статистика базы данных:\n\n";
try {
    $tables = ['users', 'teacher_profiles', 'messages', 'bookings', 'achievements'];
    
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo sprintf("%-25s: %d\n", ucfirst($table), $count);
        } catch (PDOException $e) {
            echo sprintf("%-25s: не существует\n", ucfirst($table));
        }
    }
} catch (PDOException $e) {
    echo "⚠️ Не удалось получить статистику\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
if ($errors == 0 && $successCount >= 5) {
    echo "✨ УСПЕХ! База данных готова к использованию!\n";
    echo "🎨 Переходим к обновлению дизайна...\n";
} else {
    echo "⚠️  Есть проблемы. Проверьте ошибки выше.\n";
}
echo str_repeat("=", 70) . "\n";

echo "</pre>";
?>
