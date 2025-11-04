-- ===============================================
-- ОБНОВЛЕНИЕ БД - УДАЛЕНИЕ NICKNAME
-- ===============================================

USE aqum_db;

-- Удаляем поле nickname (оно не используется, есть username)
ALTER TABLE users DROP COLUMN IF EXISTS nickname;

-- Убеждаемся что username NOT NULL для всех
UPDATE users SET username = LOWER(CONCAT('user', id)) WHERE username IS NULL OR username = '';

ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL UNIQUE;

-- ===============================================
-- ГОТОВО!
-- ===============================================
