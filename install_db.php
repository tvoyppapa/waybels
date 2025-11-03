<?php
/**
 * Установка БД через браузер
 */

$host = "localhost";
$username = "root";
$password = "WayBels2553030App!";

echo "<h1>Установка БД aqum</h1>";

try {
    // Подключаемся БЕЗ выбора БД
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Подключение к MySQL успешно</p>";
    
    // Удаляем старые БД
    echo "<h2>Удаление старых баз данных...</h2>";
    $pdo->exec("DROP DATABASE IF EXISTS aqum_db");
    $pdo->exec("DROP DATABASE IF EXISTS wibs");
    $pdo->exec("DROP DATABASE IF EXISTS waybels_db");
    echo "<p>✅ Старые БД удалены</p>";
    
    // Создаем новую БД
    echo "<h2>Создание aqum_db...</h2>";
    $pdo->exec("CREATE DATABASE aqum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ База данных aqum_db создана</p>";
    
    // Выбираем БД
    $pdo->exec("USE aqum_db");
    
    // Создаем таблицы
    echo "<h2>Создание таблиц...</h2>";
    
    // users
    $pdo->exec("
        CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            nickname VARCHAR(50) NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            interests VARCHAR(50) NULL,
            role VARCHAR(20) DEFAULT 'user',
            avatar VARCHAR(255) DEFAULT '/img/default-avatar.png',
            bio TEXT NULL,
            last_seen_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Таблица users создана</p>";
    
    // teacher_profiles
    $pdo->exec("
        CREATE TABLE teacher_profiles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            subject VARCHAR(100) NOT NULL,
            description TEXT NULL,
            short_description VARCHAR(90) NULL,
            experience_years INT UNSIGNED DEFAULT 0,
            hourly_rate DECIMAL(10, 2) DEFAULT 0.00,
            rating DECIMAL(3, 2) DEFAULT 0.00,
            rating_count INT UNSIGNED DEFAULT 0,
            total_lessons INT UNSIGNED DEFAULT 0,
            total_students INT UNSIGNED DEFAULT 0,
            video_url VARCHAR(500) NULL,
            is_approved BOOLEAN DEFAULT FALSE,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Таблица teacher_profiles создана</p>";
    
    // posts
    $pdo->exec("
        CREATE TABLE posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NULL,
            content TEXT NOT NULL,
            media_type VARCHAR(20) DEFAULT 'none',
            media_urls TEXT NULL,
            tags VARCHAR(500) NULL,
            likes_count INT UNSIGNED DEFAULT 0,
            comments_count INT UNSIGNED DEFAULT 0,
            views_count INT UNSIGNED DEFAULT 0,
            is_published BOOLEAN DEFAULT TRUE,
            published_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Таблица posts создана</p>";
    
    // post_likes
    $pdo->exec("
        CREATE TABLE post_likes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_like (post_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Таблица post_likes создана</p>";
    
    // favorites
    $pdo->exec("
        CREATE TABLE favorites (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            teacher_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_favorite (user_id, teacher_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Таблица favorites создана</p>";
    
    // user_interactions
    $pdo->exec("
        CREATE TABLE user_interactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            interaction_type VARCHAR(20) NOT NULL,
            target_type VARCHAR(20) NOT NULL,
            target_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Таблица user_interactions создана</p>";
    
    // Вставляем тестовых пользователей
    echo "<h2>Добавление тестовых данных...</h2>";
    
    $pdo->exec("
        INSERT INTO users (name, nickname, email, password, interests, role, last_seen_at) VALUES
        ('Тест Юзер', 'test', 'test@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'user', NOW()),
        ('Иван Петров', 'ivan', 'ivan@aqum.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'languages', 'teacher', NOW()),
        ('Мария Смирнова', 'maria', 'maria@aqum.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'teacher', NOW()),
        ('Админ', 'admin', 'admin@aqum.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'admin', NOW())
    ");
    echo "<p>✅ Пользователи добавлены</p>";
    
    // Репетиторы
    $pdo->exec("
        INSERT INTO teacher_profiles (user_id, subject, description, short_description, experience_years, hourly_rate, rating, rating_count, total_lessons, total_students, status, is_approved) VALUES
        (2, 'Английский язык', 'Преподаватель английского', 'Разговариваем с первого урока!', 10, 1500, 4.95, 87, 250, 45, 'active', TRUE),
        (3, 'Программирование', 'Full-stack разработчик', 'Веб-разработка от А до Я', 5, 2000, 4.88, 56, 180, 32, 'active', TRUE)
    ");
    echo "<p>✅ Репетиторы добавлены</p>";
    
    // Посты
    $pdo->exec("
        INSERT INTO posts (teacher_id, title, content, tags, likes_count, comments_count, views_count) VALUES
        (2, '5 фраз для разговора', 'How are you doing?', 'английский', 45, 12, 320),
        (3, 'Основы React', 'React - библиотека для UI', 'react', 67, 23, 580)
    ");
    echo "<p>✅ Посты добавлены</p>";
    
    // Проверка
    echo "<h2>Проверка...</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>✅ Пользователей в БД: <strong>" . $result['count'] . "</strong></p>";
    
    $stmt = $pdo->query("SELECT * FROM users WHERE email='test@test.com'");
    $user = $stmt->fetch();
    if ($user) {
        echo "<p>✅ Тестовый пользователь найден: <strong>" . htmlspecialchars($user['name']) . "</strong></p>";
    }
    
    echo "<hr style='margin: 40px 0;'>";
    echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 30px; border-radius: 10px; text-align: center;'>";
    echo "<h1 style='color: #28a745; margin: 0 0 20px 0;'>🎉 БАЗА ДАННЫХ УСТАНОВЛЕНА!</h1>";
    echo "<p style='font-size: 18px; margin-bottom: 30px;'>Теперь можно войти в систему</p>";
    echo "<div style='background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
    echo "<strong>Тестовый аккаунт:</strong><br>";
    echo "Email: <code>test@test.com</code><br>";
    echo "Пароль: <code>password</code>";
    echo "</div>";
    echo "<a href='auth.php' style='background: #7F2CDF; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-size: 18px; font-weight: bold; display: inline-block;'>→ ПЕРЕЙТИ К ВХОДУ</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 10px;'>";
    echo "<h2 style='color: #dc3545;'>❌ ОШИБКА</h2>";
    echo "<p><strong>Не удалось установить БД:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p><strong>Проверь:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL запущен</li>";
    echo "<li>Пароль правильный: WayBels2553030App!</li>";
    echo "<li>Пользователь root имеет права</li>";
    echo "</ul>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: #f9f9f9;
}
h1 { color: #7F2CDF; }
h2 { color: #333; margin-top: 30px; }
code {
    background: #f4f4f4;
    padding: 3px 8px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>
