<?php
/**
 * AQUM - Скрипт автоматической установки базы данных
 * Запуск: php setup_aqum.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n";
echo "╔════════════════════════════════════════════╗\n";
echo "║   AQUM - Установка базы данных            ║\n";
echo "║   Экосистема для онлайн-репетиторов        ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

$host = "localhost";
$username = "root";
$password = "WayBels2553030App!";
$database = "aqum_db";

try {
    // Подключаемся без указания БД
    echo "🔌 Подключение к MySQL...\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Подключение успешно\n\n";
    
    // Удаляем старые БД
    echo "🗑️  Удаление старых баз данных...\n";
    $databases_to_drop = ['aqum_db', 'waybels_db', 'wibs'];
    foreach ($databases_to_drop as $db) {
        try {
            $pdo->exec("DROP DATABASE IF EXISTS $db");
            echo "   ✓ $db удалена\n";
        } catch (PDOException $e) {
            echo "   ⚠ $db не найдена или не удалена\n";
        }
    }
    echo "✅ Очистка завершена\n\n";
    
    // Читаем SQL файл
    echo "📄 Чтение schema из database_aqum.sql...\n";
    $sql = file_get_contents(__DIR__ . '/database_aqum.sql');
    
    if (!$sql) {
        die("❌ Ошибка: не удалось прочитать файл database_aqum.sql\n");
    }
    
    $sql_size = round(strlen($sql) / 1024, 2);
    echo "✅ Файл прочитан ($sql_size KB)\n\n";
    
    // Выполняем SQL
    echo "🔨 Выполнение SQL запросов...\n";
    
    // Разбиваем на отдельные запросы
    $statements = array_filter(
        explode(';', $sql),
        function($stmt) {
            $stmt = trim($stmt);
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) &&
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $count = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $count++;
            
            // Показываем прогресс каждые 10 запросов
            if ($count % 10 === 0) {
                echo "   ⏳ Выполнено $count запросов...\n";
            }
        } catch (PDOException $e) {
            $errors++;
            if (stripos($e->getMessage(), 'syntax error') === false) {
                echo "   ⚠️  Предупреждение: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✅ Выполнено $count SQL запросов";
    if ($errors > 0) {
        echo " ($errors предупреждений)";
    }
    echo "\n\n";
    
    // Проверяем создание БД
    $pdo->exec("USE $database");
    echo "✅ База данных '$database' создана и выбрана\n\n";
    
    // Проверяем таблицы
    echo "📊 Проверка созданных таблиц:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $total_records = 0;
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        $total_records += $count;
        
        $icon = $count > 0 ? '📦' : '📭';
        echo sprintf("   %s %-25s %3d записей\n", $icon, $table, $count);
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "   Всего таблиц: " . count($tables) . "\n";
    echo "   Всего записей: $total_records\n\n";
    
    // Показываем тестовые аккаунты
    echo "👥 Тестовые аккаунты:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $pdo->query("
        SELECT u.email, u.username, u.role, u.is_teacher, u.is_verified_teacher
        FROM users u
        ORDER BY 
            CASE u.role
                WHEN 'admin' THEN 1
                WHEN 'teacher' THEN 2
                WHEN 'user' THEN 3
            END,
            u.id
    ");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $role_icon = [
            'admin' => '🛡️',
            'teacher' => '👨‍🏫',
            'user' => '👤'
        ][$user['role']];
        
        $verified = $user['is_verified_teacher'] ? '✅' : '';
        
        echo sprintf(
            "   %s %-25s @%-15s %s %s\n",
            $role_icon,
            $user['email'],
            $user['username'],
            strtoupper($user['role']),
            $verified
        );
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "   Пароль для всех: password\n\n";
    
    // Показываем статистику по преподавателям
    echo "📈 Статистика преподавателей:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.name,
            u.username,
            tp.status,
            COUNT(DISTINCT lo.id) as orders_count,
            COUNT(DISTINCT lo.student_id) as students_count,
            AVG(r.rating) as avg_rating
        FROM users u
        LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
        LEFT JOIN lesson_orders lo ON u.id = lo.teacher_id AND lo.status IN ('paid','confirmed','completed')
        LEFT JOIN reviews r ON u.id = r.teacher_id
        WHERE u.is_teacher = 1
        GROUP BY u.id
        ORDER BY orders_count DESC
    ");
    $teachers = $stmt->fetchAll();
    
    foreach ($teachers as $teacher) {
        $rating = $teacher['avg_rating'] ? number_format($teacher['avg_rating'], 1) . '⭐' : 'Нет отзывов';
        echo sprintf(
            "   👨‍🏫 %-20s | Заказов: %2d | Учеников: %2d | Рейтинг: %s\n",
            substr($teacher['name'], 0, 20),
            $teacher['orders_count'],
            $teacher['students_count'],
            $rating
        );
    }
    
    echo "\n";
    echo "╔════════════════════════════════════════════╗\n";
    echo "║   🎉 УСТАНОВКА ЗАВЕРШЕНА УСПЕШНО!         ║\n";
    echo "╚════════════════════════════════════════════╝\n\n";
    
    echo "📌 Что дальше?\n";
    echo "   1. Откройте auth_aqum.php для входа/регистрации\n";
    echo "   2. Войдите с тестовым аккаунтом:\n";
    echo "      • student1@aqum.com / password (ученик)\n";
    echo "      • teacher1@aqum.com / password (учитель)\n";
    echo "      • admin@aqum.com / password (админ)\n";
    echo "   3. Или зарегистрируйте новый аккаунт\n\n";
    
    echo "🔗 Документация: см. файл AQUM_README.md\n";
    echo "🎨 Дизайн: Liquid Glass с брендовым цветом #7F2CDF\n";
    echo "🚀 Платформа готова к использованию!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "   Файл: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ ОШИБКА: " . $e->getMessage() . "\n\n";
    exit(1);
}
