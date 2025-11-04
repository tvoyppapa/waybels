-- ===============================================
-- СУПЕР ПРОСТОЕ ИСПРАВЛЕНИЕ (без проверок)
-- ===============================================

USE aqum_db;

-- Удаляем все индексы username
ALTER TABLE users DROP INDEX username;
-- Игнорируем ошибку если индекса нет

ALTER TABLE users DROP INDEX unique_username;
-- Игнорируем ошибку если индекса нет

-- Заполняем username для ВСЕХ (перезапись)
UPDATE users SET username = CONCAT('user', id);

-- Теперь делаем NOT NULL и UNIQUE
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL;
ALTER TABLE users ADD UNIQUE KEY unique_username (username);

-- Добавляем theme (если ошибка - игнорируем)
ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT 'auto' AFTER bio;

-- Создаем assignments
CREATE TABLE IF NOT EXISTS assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    due_date DATETIME NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создаем stories
CREATE TABLE IF NOT EXISTS stories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    media_url VARCHAR(500) NOT NULL,
    media_type VARCHAR(20) DEFAULT 'image',
    caption TEXT NULL,
    views_count INT UNSIGNED DEFAULT 0,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Проверяем
SELECT id, name, username, email FROM users;
SELECT 'SUCCESS!' as status;
