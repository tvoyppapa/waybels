-- ===============================================
-- СОЗДАНИЕ ОТСУТСТВУЮЩИХ ТАБЛИЦ
-- ===============================================

USE aqum_db;

-- Создаем таблицу заданий (если не существует)
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

-- Создаем таблицу историй (если не существует)
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

-- Убеждаемся что есть username (не nickname)
ALTER TABLE users DROP COLUMN IF EXISTS nickname;
ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(50) NOT NULL UNIQUE AFTER name;
ALTER TABLE users ADD COLUMN IF NOT EXISTS theme VARCHAR(20) DEFAULT 'auto' AFTER bio;

-- Обновляем username для существующих пользователей (если NULL)
UPDATE users SET username = LOWER(SUBSTRING_INDEX(COALESCE(email, CONCAT('user', id)), '@', 1)) 
WHERE username IS NULL OR username = '';

-- Проверяем что все ОК
SELECT 'assignments' as table_name, COUNT(*) as count FROM assignments
UNION ALL
SELECT 'stories', COUNT(*) FROM stories
UNION ALL
SELECT 'users', COUNT(*) FROM users;

SELECT 'SUCCESS: All tables created!' as status;
