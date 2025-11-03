-- Простая рабочая БД для aqum
-- Импорт: mysql -u root -p < SIMPLE_DB.sql

DROP DATABASE IF EXISTS aqum_db;
CREATE DATABASE aqum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aqum_db;

-- Таблица пользователей
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    nickname VARCHAR(50) NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    interests ENUM('languages', 'programming', 'design', 'marketing', 'growth') NULL,
    role ENUM('user', 'teacher', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT '/img/default-avatar.png',
    bio TEXT NULL,
    last_seen_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Профили репетиторов
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
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Посты
CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    content TEXT NOT NULL,
    media_type ENUM('none', 'image', 'video', 'mixed') DEFAULT 'none',
    media_urls JSON NULL,
    tags VARCHAR(500) NULL,
    likes_count INT UNSIGNED DEFAULT 0,
    comments_count INT UNSIGNED DEFAULT 0,
    views_count INT UNSIGNED DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    published_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Лайки
CREATE TABLE post_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Избранное
CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Взаимодействия
CREATE TABLE user_interactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    interaction_type ENUM('view', 'like', 'comment', 'share', 'search') NOT NULL,
    target_type ENUM('post', 'teacher', 'channel', 'group') NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ТЕСТОВЫЕ ДАННЫЕ
-- Пароль для всех: password

INSERT INTO users (name, nickname, email, password, interests, role, bio, last_seen_at) VALUES
('Тест Юзер', 'test', 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'user', 'Тестовый аккаунт', NOW()),
('Иван Петров', 'ivan_teacher', 'ivan@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'languages', 'teacher', 'Преподаватель английского', NOW()),
('Мария Смирнова', 'maria_prog', 'maria@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'teacher', 'Full-stack разработчик', NOW()),
('Админ', 'admin', 'admin@aqum.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'programming', 'admin', 'Администратор', NOW());

-- Репетиторы
INSERT INTO teacher_profiles (user_id, subject, description, short_description, experience_years, hourly_rate, rating, rating_count, total_lessons, total_students, status, is_approved) VALUES
(2, 'Английский язык', 'Помогу освоить английский с нуля или подготовиться к экзаменам.', 'Разговариваем с первого урока!', 10, 1500, 4.95, 87, 250, 45, 'active', TRUE),
(3, 'Программирование', 'Обучаю веб-разработке: HTML, CSS, JavaScript, React, Node.js.', 'Веб-разработка от А до Я', 5, 2000, 4.88, 56, 180, 32, 'active', TRUE);

-- Посты
INSERT INTO posts (teacher_id, title, content, tags, likes_count, comments_count, views_count) VALUES
(2, '5 фраз для начала разговора', 'Сегодня разберем самые полезные фразы:\n\n1. How are you doing?\n2. What''s up?\n3. Nice to meet you!', 'английский,фразы', 45, 12, 320),
(3, 'Основы React', 'React - это JavaScript библиотека для создания пользовательских интерфейсов.', 'программирование,react', 67, 23, 580);

-- ГОТОВО!
-- Тестовый логин: test@test.com
-- Пароль: password
