<?php
/**
 * Веб-интерфейс для настройки базы данных WayBels
 * Откройте этот файл в браузере для автоматической настройки БД
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Параметры подключения
$host = "localhost";
$username = "root";
$password = "WayBels2553030App!";

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройка БД WayBels</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f7;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #7F2CDF;
            margin-top: 0;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #7F2CDF;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #7F2CDF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #6A1FC9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #7F2CDF;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Настройка базы данных WayBels</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
            echo "<pre>";
            
            try {
                // Подключаемся к MySQL без выбора БД
                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                echo "<span class='success'>✅ Подключение к MySQL успешно</span>\n\n";
                
                // Список БД для удаления
                $databasesToDrop = ['aqum_db', 'waybels_db'];
                
                foreach ($databasesToDrop as $dbName) {
                    try {
                        $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
                        echo "<span class='info'>✅ База данных '$dbName' удалена (если существовала)</span>\n";
                    } catch (PDOException $e) {
                        echo "<span class='warning'>⚠️  Не удалось удалить '$dbName': " . $e->getMessage() . "</span>\n";
                    }
                }
                
                echo "\n";
                
                // Создаем новую БД waybels_db
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `waybels_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<span class='success'>✅ База данных 'waybels_db' создана</span>\n\n";
                
                // Выбираем БД
                $pdo->exec("USE `waybels_db`");
                echo "<span class='success'>✅ Переключились на БД 'waybels_db'</span>\n\n";
                
                // Читаем SQL файл
                $sqlFile = __DIR__ . '/setup_database.sql';
                
                if (!file_exists($sqlFile)) {
                    throw new Exception("Файл $sqlFile не найден!");
                }
                
                echo "<span class='info'>📄 Читаем SQL файл: $sqlFile</span>\n";
                $sql = file_get_contents($sqlFile);
                
                // Удаляем строки CREATE DATABASE и USE из SQL, так как мы уже создали БД
                $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
                $sql = preg_replace('/USE.*?;/i', '', $sql);
                $sql = preg_replace('/DROP DATABASE.*?;/i', '', $sql);
                
                // Разбиваем на отдельные запросы
                $queries = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($query) {
                        return !empty($query) && !preg_match('/^--/', $query) && strlen(trim($query)) > 10;
                    }
                );
                
                echo "<span class='info'>📊 Выполняем " . count($queries) . " SQL запросов...</span>\n\n";
                
                $successCount = 0;
                $errorCount = 0;
                $createdTables = [];
                
                foreach ($queries as $query) {
                    if (empty(trim($query))) continue;
                    
                    try {
                        $pdo->exec($query);
                        $successCount++;
                        
                        // Определяем тип операции
                        if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $query, $matches)) {
                            $createdTables[] = $matches[1];
                            echo "<span class='success'>  ✅ Таблица '{$matches[1]}' создана</span>\n";
                        } elseif (preg_match('/INSERT INTO.*?`(\w+)`/i', $query, $matches)) {
                            echo "<span class='info'>  📝 Данные вставлены в '{$matches[1]}'</span>\n";
                        }
                    } catch (PDOException $e) {
                        $errorCount++;
                        $queryPreview = substr($query, 0, 80);
                        echo "<span class='error'>  ❌ Ошибка: " . $e->getMessage() . "</span>\n";
                        echo "<span class='info'>     Запрос: $queryPreview...</span>\n";
                    }
                }
                
                echo "\n";
                echo "<span class='success'>📊 Итого:</span>\n";
                echo "<span class='success'>   ✅ Успешно: $successCount</span>\n";
                if ($errorCount > 0) {
                    echo "<span class='error'>   ❌ Ошибок: $errorCount</span>\n";
                }
                echo "\n";
                
                // Проверяем созданные таблицы
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo "<span class='success'>📋 Созданные таблицы (" . count($tables) . "):</span>\n";
                echo "<table>";
                echo "<tr><th>Таблица</th><th>Записей</th></tr>";
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                    $count = $stmt->fetchColumn();
                    echo "<tr><td>$table</td><td>$count</td></tr>";
                }
                echo "</table>";
                
                echo "\n<span class='success'>✅ База данных успешно настроена!</span>\n";
                echo "</pre>";
                
                echo "<p><a href='check_db.php' class='btn'>Проверить подключение</a></p>";
                
            } catch (PDOException $e) {
                echo "<span class='error'>❌ Ошибка PDO: " . $e->getMessage() . "</span>\n";
                echo "</pre>";
            } catch (Exception $e) {
                echo "<span class='error'>❌ Ошибка: " . $e->getMessage() . "</span>\n";
                echo "</pre>";
            }
        } else {
            // Показываем форму
            ?>
            <p>Этот скрипт выполнит следующее:</p>
            <ul>
                <li>❌ Удалит старую базу данных <code>aqum_db</code> (если существует)</li>
                <li>❌ Удалит старую базу данных <code>waybels_db</code> (если существует)</li>
                <li>✅ Создаст новую базу данных <code>waybels_db</code></li>
                <li>✅ Создаст все необходимые таблицы</li>
                <li>✅ Загрузит тестовые данные</li>
            </ul>
            
            <form method="POST">
                <button type="submit" name="setup" style="padding: 15px 30px; font-size: 16px; background: #7F2CDF; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    🚀 Настроить базу данных
                </button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h2>📋 Текущее состояние</h2>
            <?php
            try {
                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Проверяем существующие БД
                $stmt = $pdo->query("SHOW DATABASES");
                $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo "<p><strong>Существующие базы данных:</strong></p>";
                echo "<ul>";
                foreach ($databases as $db) {
                    if (in_array($db, ['aqum_db', 'waybels_db', 'wibs'])) {
                        $marker = in_array($db, ['aqum_db']) ? '❌' : '✅';
                        echo "<li>$marker <code>$db</code></li>";
                    }
                }
                echo "</ul>";
                
                // Проверяем waybels_db
                if (in_array('waybels_db', $databases)) {
                    $pdo->exec("USE `waybels_db`");
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (count($tables) > 0) {
                        echo "<p><strong>Таблицы в waybels_db:</strong></p>";
                        echo "<ul>";
                        foreach ($tables as $table) {
                            echo "<li>✅ <code>$table</code></li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p class='warning'>⚠️ База данных waybels_db существует, но таблиц нет!</p>";
                    }
                } else {
                    echo "<p class='info'>ℹ️ База данных waybels_db не существует. Нажмите кнопку выше для создания.</p>";
                }
                
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Ошибка подключения: " . $e->getMessage() . "</p>";
                echo "<p>Проверьте параметры подключения в этом файле.</p>";
            }
            ?>
            
            <hr style="margin: 30px 0;">
            <p><a href="check_db.php" class="btn">Проверить подключение</a></p>
            <?php
        }
        ?>
    </div>
</body>
</html>
