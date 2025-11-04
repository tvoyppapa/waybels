-- ===============================================
-- ДОБАВЛЕНИЕ USERNAME (простой способ)
-- ===============================================

USE aqum_db;

-- Шаг 1: Добавляем колонку username (nullable сначала)
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER name;

-- Шаг 2: Заполняем username для всех пользователей
UPDATE users SET username = CONCAT('user', id);

-- Шаг 3: Делаем NOT NULL и UNIQUE
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL;
CREATE UNIQUE INDEX unique_username ON users(username);

-- Шаг 4: Добавляем theme
ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT 'auto' AFTER bio;

-- Шаг 5: Удаляем nickname если есть
ALTER TABLE users DROP COLUMN nickname;

-- Проверяем
SELECT id, name, username, email FROM users;
