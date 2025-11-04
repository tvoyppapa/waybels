-- ===============================================
-- Aqum — Схема базы данных и начальные данные
-- Полностью пересоздает базу данных aqum_db
-- ===============================================

-- Удаляем старую базу, если существует
DROP DATABASE IF EXISTS aqum_db;

-- Создаем новую базу и выбираем её
CREATE DATABASE aqum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aqum_db;

-- ===============================================
-- Таблица пользователей
-- ===============================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    google_id VARCHAR(255) NULL UNIQUE,
    apple_id VARCHAR(255) NULL UNIQUE,
    username VARCHAR(50) NULL UNIQUE,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL DEFAULT '/img/default-avatar.png',
    bio TEXT NULL,
    interests ENUM('languages', 'programming', 'design', 'marketing', 'growth', 'math', 'science', 'music', 'other') NULL,
    role ENUM('user', 'teacher', 'admin') DEFAULT 'user',
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,

    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_interests (interests)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Таблица связей с OAuth-провайдерами
-- ===============================================
CREATE TABLE oauth_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_user (provider, provider_user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Таблица для сброса паролей
-- ===============================================
CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- Профили репетиторов
-- ===============================================
CREATE TABLE teacher_profiles (
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
-- Контент/посты репетиторов
-- ===============================================
CREATE TABLE posts (
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
-- Избранные репетиторы
-- ===============================================
CREATE TABLE favorites (
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
-- Чаты между пользователями
-- ===============================================
CREATE TABLE chats (
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
-- Сообщения в чатах
-- ===============================================
CREATE TABLE messages (
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
-- Заявки на становление репетитором
-- ===============================================
CREATE TABLE teacher_applications (
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
-- Тестовые данные
-- ===============================================

-- Пользователи (пароль для всех: password)
INSERT INTO users (name, email, password, username, interests, role, is_verified, avatar) VALUES
('Иван Петров', 'ivan@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ivan_petrov', 'programming', 'user', TRUE, '/img/avatars/user1.jpg'),
('Анна Смирнова', 'anna@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'anna_teacher', 'languages', 'teacher', TRUE, '/img/avatars/teacher1.jpg'),
('Дмитрий Козлов', 'dmitry@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dmitry_math', 'math', 'teacher', TRUE, '/img/avatars/teacher2.jpg'),
('Елена Волкова', 'elena@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elena_design', 'design', 'teacher', TRUE, '/img/avatars/teacher3.jpg'),
('Сергей Новиков', 'sergey@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sergey_code', 'programming', 'teacher', TRUE, '/img/avatars/teacher4.jpg'),
('Мария Иванова', 'maria@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'maria_music', 'music', 'teacher', TRUE, '/img/avatars/teacher5.jpg'),
('Админ Aqum', 'admin@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aqum_admin', NULL, 'admin', TRUE, '/img/avatars/admin.jpg');

-- Профили репетиторов
INSERT INTO teacher_profiles (user_id, subject, description, experience_years, hourly_rate, video_url, total_lessons, rating, rating_count, is_approved, status) VALUES
(2, 'Английский язык', 'Преподаватель английского языка с 5-летним опытом. Специализируюсь на разговорной практике и подготовке к IELTS. Помогу преодолеть языковой барьер и говорить уверенно!', 5, 1500.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 156, 4.90, 47, TRUE, 'active'),
(3, 'Математика', 'Репетитор по математике для школьников 5-11 классов. Подготовка к ОГЭ и ЕГЭ. Объясняю сложное простым языком. Индивидуальный подход к каждому ученику.', 8, 2000.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 289, 4.95, 89, TRUE, 'active'),
(4, 'UX/UI Дизайн', 'Дизайнер с опытом работы в ведущих IT-компаниях. Научу создавать красивые и удобные интерфейсы. Figma, дизайн-системы, прототипирование.', 4, 1800.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 92, 4.85, 34, TRUE, 'active'),
(5, 'Python программирование', 'Full-stack разработчик. Обучаю Python с нуля до продвинутого уровня. Django, Flask, телеграм-боты и реальная практика на проектах.', 6, 2200.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 203, 4.92, 67, TRUE, 'active'),
(6, 'Фортепиано', 'Профессиональный музыкант и педагог. Обучение игре на фортепиано для детей и взрослых. Классика, джаз, современные композиции.', 10, 1600.00, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 345, 4.88, 123, TRUE, 'active');

-- Посты репетиторов
INSERT INTO posts (teacher_id, title, content, media_type, media_url, likes_count, views_count) VALUES
(2, '5 фраз для уверенного разговора на английском', 'Разбираем самые полезные фразы для small talk и первых встреч. Повторяем произношение и делаем практику.', 'video', 'https://example.com/video1.mp4', 234, 1523),
(3, 'Как решать квадратные уравнения за 2 минуты', 'Простой лайфхак для быстрого решения квадратных уравнений с запоминанием ключевых формул.', 'image', 'https://example.com/image1.jpg', 567, 3421),
(4, 'Топ-5 ошибок начинающих дизайнеров', 'Разбираем самые частые ошибки в UI-дизайне и показываем, как их избежать.', 'video', 'https://example.com/video2.mp4', 892, 4532),
(5, 'Создаем Telegram-бота за 15 минут', 'Пошаговая инструкция создания простого бота на Python и идеи для автоматизации рутины.', 'video', 'https://example.com/video3.mp4', 1234, 7821);

-- Избранные репетиторы
INSERT INTO favorites (user_id, teacher_id) VALUES
(1, 2),
(1, 4),
(1, 5);

-- Демонстрационные чаты и сообщения
INSERT INTO chats (user1_id, user2_id, last_message, last_message_at) VALUES
(1, 2, 'Спасибо за урок! Когда можем продолжить?', NOW() - INTERVAL 2 HOUR),
(1, 5, 'Готов обсудить программу обучения.', NOW() - INTERVAL 1 DAY);

INSERT INTO messages (chat_id, sender_id, message, is_read, created_at) VALUES
(1, 1, 'Здравствуйте! Хотел бы заняться spoken English.', FALSE, NOW() - INTERVAL 1 DAY),
(1, 2, 'Здравствуйте! Давайте начнем с определения целей.', TRUE, NOW() - INTERVAL 26 HOUR),
(1, 1, 'Спасибо за урок! Когда можем продолжить?', FALSE, NOW() - INTERVAL 2 HOUR),
(2, 1, 'Добрый день! Интересен курс по Python.', TRUE, NOW() - INTERVAL 2 DAY),
(2, 5, 'Отлично! Предлагаю начать с основ синтаксиса и простых проектов.', TRUE, NOW() - INTERVAL 45 HOUR);

-- Заявка на репетиторство в статусе ожидания
INSERT INTO teacher_applications (user_id, subject, description, experience_years, education, certificates, video_url, status) VALUES
(1, 'Frontend-разработка', '5 лет разрабатываю веб-интерфейсы на React и Vue. Помогаю освоить основы и перейти к реальным проектам.', 5, 'СПбГУ, Прикладная информатика', 'Google UX Certificate, Meta Frontend Developer', 'https://example.com/video4.mp4', 'pending');

