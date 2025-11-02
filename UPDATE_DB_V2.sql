-- ===============================================
-- Обновление базы данных WayBels v2.0
-- ===============================================

USE wibs;

-- ===============================================
-- 1. Обновление таблицы users
-- ===============================================

-- Добавляем новые поля
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS username VARCHAR(50) UNIQUE AFTER email,
ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER avatar,
ADD COLUMN IF NOT EXISTS theme ENUM('light', 'dark') DEFAULT 'light' AFTER bio,
ADD COLUMN IF NOT EXISTS username_changed_at TIMESTAMP NULL AFTER theme,
ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00 AFTER username_changed_at,
ADD COLUMN IF NOT EXISTS is_online BOOLEAN DEFAULT FALSE AFTER balance,
ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL AFTER is_online;

-- Индексы для производительности
CREATE INDEX IF NOT EXISTS idx_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_is_online ON users(is_online);

-- ===============================================
-- 2. Настройки чата (обои, кастомизация)
-- ===============================================

CREATE TABLE IF NOT EXISTS chat_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    chat_id INT UNSIGNED NOT NULL,
    wallpaper VARCHAR(255) DEFAULT 'default',
    font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_chat (user_id, chat_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 3. Календарь доступности репетитора
-- ===============================================

CREATE TABLE IF NOT EXISTS teacher_availability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Понедельник, 6=Воскресенье',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_day (teacher_id, day_of_week),
    INDEX idx_available (teacher_id, is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 4. Бронирования уроков
-- ===============================================

CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    price DECIMAL(10,2) NULL,
    notes TEXT NULL,
    meeting_url VARCHAR(255) NULL COMMENT 'Ссылка на видеовстречу',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_date (teacher_id, booking_date),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_upcoming (booking_date, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 5. Бот Wibs (поддержка)
-- ===============================================

CREATE TABLE IF NOT EXISTS bot_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_from_user BOOLEAN DEFAULT TRUE COMMENT 'true=от пользователя, false=от бота',
    is_read BOOLEAN DEFAULT FALSE,
    message_type ENUM('text', 'auto_reply', 'support', 'notification') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 6. Достижения (геймификация)
-- ===============================================

CREATE TABLE IF NOT EXISTS achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) NOT NULL,
    type ENUM('lessons', 'rating', 'students', 'revenue', 'special') DEFAULT 'lessons',
    requirement INT UNSIGNED NOT NULL COMMENT 'Требование для получения',
    reward_points INT UNSIGNED DEFAULT 0 COMMENT 'Баллы за достижение',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 7. Обновление таблицы messages (статусы)
-- ===============================================

ALTER TABLE messages 
ADD COLUMN IF NOT EXISTS status ENUM('sent', 'delivered', 'read') DEFAULT 'sent' AFTER is_read,
ADD COLUMN IF NOT EXISTS file_url VARCHAR(255) NULL AFTER message,
ADD COLUMN IF NOT EXISTS file_type VARCHAR(50) NULL AFTER file_url;

CREATE INDEX IF NOT EXISTS idx_status ON messages(status);

-- ===============================================
-- 8. Сохраненные ссылки на профили
-- ===============================================

CREATE TABLE IF NOT EXISTS saved_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Кто сохранил',
    saved_user_id INT UNSIGNED NOT NULL COMMENT 'Чей профиль сохранен',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (saved_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_save (user_id, saved_user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 9. История изменений username
-- ===============================================

CREATE TABLE IF NOT EXISTS username_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    old_username VARCHAR(50) NULL,
    new_username VARCHAR(50) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- 10. Уведомления
-- ===============================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('booking', 'message', 'payment', 'achievement', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Тестовые данные
-- ===============================================

-- Добавляем username существующим пользователям (если есть)
UPDATE users SET username = CONCAT('user_', id) WHERE username IS NULL;

-- Создаем бота Wibs
INSERT INTO users (name, email, password, username, role, avatar) VALUES
('Wibs Bot', 'bot@wibs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'wibs_bot', 'admin', '/img/bot-avatar.png')
ON DUPLICATE KEY UPDATE name=name;

-- Добавляем базовые достижения
INSERT INTO achievements (name, description, icon, type, requirement, reward_points) VALUES
('Первый урок', 'Провели свой первый урок', '🎓', 'lessons', 1, 10),
('10 уроков', 'Провели 10 уроков', '📚', 'lessons', 10, 50),
('50 уроков', 'Провели 50 уроков', '🏆', 'lessons', 50, 200),
('100 уроков', 'Провели 100 уроков', '💎', 'lessons', 100, 500),
('Высокий рейтинг', 'Рейтинг выше 4.8', '⭐', 'rating', 48, 100),
('Первый ученик', 'Получили первого ученика', '👨‍🎓', 'students', 1, 20),
('10 учеников', 'У вас 10 активных учеников', '👥', 'students', 10, 100),
('Популярный репетитор', 'У вас 50 учеников', '🌟', 'students', 50, 500)
ON DUPLICATE KEY UPDATE name=name;

-- Проверка
SELECT 'База данных успешно обновлена!' as status;
SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'wibs' 
AND TABLE_NAME IN ('chat_settings', 'teacher_availability', 'bookings', 'bot_messages', 'achievements', 'notifications')
ORDER BY TABLE_NAME;
