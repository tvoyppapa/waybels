<?php
/**
 * Скрипт для настройки базы данных WayBels
 * Удаляет старую БД aqum_db и создает новую waybels_db
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Параметры подключения
$host = "localhost";
$username = "root";
$password = "WayBels2553030App!";

echo "<h1>Настройка базы данных WayBels</h1>";
echo "<pre>";

try {
    // Подключаемся к MySQL без выбора БД
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Подключение к MySQL успешно\n\n";
    
    // Список БД для удаления
    $databasesToDrop = ['aqum_db', 'waybels_db'];
    
    foreach ($databasesToDrop as $dbName) {
        try {
            $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
            echo "✅ База данных '$dbName' удалена (если существовала)\n";
        } catch (PDOException $e) {
            echo "⚠️  Не удалось удалить '$dbName': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Создаем новую БД waybels_db
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `waybels_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ База данных 'waybels_db' создана\n\n";
    
    // Выбираем БД
    $pdo->exec("USE `waybels_db`");
    echo "✅ Переключились на БД 'waybels_db'\n\n";
    
    // Читаем SQL файл
    $sqlFile = __DIR__ . '/database_waybels.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Файл $sqlFile не найден!");
    }
    
    echo "📄 Читаем SQL файл: $sqlFile\n";
    $sql = file_get_contents($sqlFile);
    
    // Удаляем строки CREATE DATABASE и USE из SQL, так как мы уже создали БД
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE.*?;/i', '', $sql);
    
    // Разбиваем на отдельные запросы
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        function($query) {
            return !empty($query) && !preg_match('/^--/', $query);
        }
    );
    
    echo "📊 Выполняем " . count($queries) . " SQL запросов...\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $query) {
        if (empty(trim($query))) continue;
        
        try {
            $pdo->exec($query);
            $successCount++;
            
            // Показываем прогресс для больших операций
            if (preg_match('/CREATE TABLE/i', $query)) {
                if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $query, $matches)) {
                    echo "  ✅ Таблица '{$matches[1]}' создана\n";
                }
            } elseif (preg_match('/INSERT INTO/i', $query)) {
                if (preg_match('/INSERT INTO.*?`(\w+)`/i', $query, $matches)) {
                    echo "  ✅ Данные вставлены в '{$matches[1]}'\n";
                }
            }
        } catch (PDOException $e) {
            $errorCount++;
            echo "  ❌ Ошибка в запросе: " . substr($query, 0, 50) . "...\n";
            echo "     " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "📊 Итого:\n";
    echo "   ✅ Успешно: $successCount\n";
    echo "   ❌ Ошибок: $errorCount\n\n";
    
    // Проверяем созданные таблицы
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Созданные таблицы (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "   • $table ($count записей)\n";
    }
    
    echo "\n✅ База данных успешно настроена!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Ошибка PDO: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

echo "</pre>";
?>
