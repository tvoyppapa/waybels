-- ===============================================
-- Добавление таблиц для функционала репетиторов
-- ===============================================

USE wibs;

-- ===============================================
-- Таблица профилей репетиторов
-- ===============================================
CREATE TABLE IF NOT EXISTS teacher_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    subject VARCHAR(100) NOT NULL,
    description TEXT NULL,
    experience_years INT UNSIGNED DEFAULT 0,
    hourly_rate DECIMAL(10, 2) NULL,
    video_url VARCHAR(255) NULL,
    total_lessons INT UNSIGNED DEFAULT 0,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    rating_count INT UNSIGNED DEFAULT 0,
    is_approved BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'active', 'inactive', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_subject (subject),
    INDEX idx_status (status),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Таблица постов (контент от репетиторов)
-- ===============================================
CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    media_type ENUM('image', 'video', 'none') DEFAULT 'none',
    media_url VARCHAR(255) NULL,
    likes_count INT UNSIGNED DEFAULT 0,
    views_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Таблица избранного (лайки репетиторов)
-- ===============================================
CREATE TABLE IF NOT EXISTS favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, teacher_id),
    INDEX idx_user_id (user_id),
    INDEX idx_teacher_id (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Таблица чатов
-- ===============================================
CREATE TABLE IF NOT EXISTS chats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user1_id INT UNSIGNED NOT NULL,
    user2_id INT UNSIGNED NOT NULL,
    last_message TEXT NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_chat (user1_id, user2_id),
    INDEX idx_user1_id (user1_id),
    INDEX idx_user2_id (user2_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Таблица сообщений
-- ===============================================
CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat_id (chat_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Таблица заявок на репетиторство
-- ===============================================
CREATE TABLE IF NOT EXISTS teacher_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    subject VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    experience_years INT UNSIGNED DEFAULT 0,
    education TEXT NULL,
    certificates TEXT NULL,
    video_url VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Вставка тестовых данных
-- ===============================================

-- Получаем ID существующих пользователей для создания тестовых репетиторов
-- Создаем тестовых репетиторов, если их еще нет
INSERT INTO users (name, email, password, interests, role, avatar) VALUES
('Анна Смирнова', 'anna@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'languages', 'user', '/img/default-avatar.png'),
('Дмитрий Козлов', 'dmitry@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'user', '/img/default-avatar.png'),
('Елена Волкова', 'elena@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'design', 'user', '/img/default-avatar.png'),
('Сергей Новиков', 'sergey@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'user', '/img/default-avatar.png'),
('Мария Иванова', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'user', '/img/default-avatar.png')
ON DUPLICATE KEY UPDATE name=name;

-- Пароль для всех: password

-- Создаем профили репетиторов (используем ID от 2 до 6, предполагая что ID 1 - это первый пользователь)
INSERT INTO teacher_profiles (user_id, subject, description, experience_years, hourly_rate, video_url, total_lessons, rating, rating_count, is_approved, status) VALUES
(2, 'Английский язык', 'Преподаватель английского языка с 5-летним опытом. Специализируюсь на разговорной практике и подготовке к IELTS. Работала в международных компаниях, помогу преодолеть языковой барьер!', 5, 1500.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 156, 4.90, 47, TRUE, 'active'),
(3, 'Математика', 'Репетитор по математике для школьников 5-11 классов. Подготовка к ОГЭ и ЕГЭ. Объясняю сложное простым языком. Индивидуальный подход к каждому ученику.', 8, 2000.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 289, 4.95, 89, TRUE, 'active'),
(4, 'UX/UI Дизайн', 'Дизайнер с опытом работы в крупных IT-компаниях. Научу создавать красивые и функциональные интерфейсы. Figma, Adobe XD, принципы дизайна.', 4, 1800.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 92, 4.85, 34, TRUE, 'active'),
(5, 'Python программирование', 'Full-stack разработчик. Обучаю Python с нуля до уверенного уровня. Django, Flask, машинное обучение. Практический подход - делаем реальные проекты!', 6, 2200.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 203, 4.92, 67, TRUE, 'active'),
(6, 'Фортепиано', 'Профессиональный музыкант и педагог. Обучение игре на фортепиано для детей и взрослых. Классическая музыка, джаз, современные композиции.', 10, 1600.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 345, 4.88, 123, TRUE, 'active')
ON DUPLICATE KEY UPDATE subject=subject;

-- Тестовые посты
INSERT INTO posts (teacher_id, title, content, media_type, media_url, likes_count, views_count) VALUES
(2, '5 фраз для начала разговора на английском', 'Сегодня разберем самые полезные фразы для small talk...', 'video', 'https://example.com/video1.mp4', 234, 1523),
(3, 'Как решать квадратные уравнения за 2 минуты', 'Простой лайфхак для быстрого решения квадратных уравнений', 'image', 'https://example.com/image1.jpg', 567, 3421),
(4, 'Топ-5 ошибок начинающих дизайнеров', 'Разбираю самые частые ошибки в UI дизайне...', 'video', 'https://example.com/video2.mp4', 892, 4532),
(5, 'Создаем Telegram бота за 15 минут', 'Пошаговая инструкция создания простого бота на Python', 'video', 'https://example.com/video3.mp4', 1234, 7821);

-- Проверка успешного создания
SELECT 'Таблицы успешно созданы!' as status;
SELECT COUNT(*) as teacher_profiles_count FROM teacher_profiles;
SELECT COUNT(*) as posts_count FROM posts;
