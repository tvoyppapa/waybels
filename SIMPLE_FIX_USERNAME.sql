-- ===============================================
-- ПРОСТОЕ ИСПРАВЛЕНИЕ USERNAME (если уже есть пустые)
-- ===============================================

USE aqum_db;

-- Вариант 1: Если username уже существует с пустыми значениями

-- Убираем UNIQUE временно
ALTER TABLE users DROP INDEX IF EXISTS username;
ALTER TABLE users DROP INDEX IF EXISTS unique_username;

-- Заполняем пустые username
UPDATE users 
SET username = CONCAT('user', id)
WHERE username IS NULL OR username = '' OR username = ' ';

-- Делаем уникальными (добавляем номера к дубликатам)
UPDATE users u1
SET username = CONCAT(username, '_', id)
WHERE EXISTS (
    SELECT 1 FROM (SELECT username FROM users GROUP BY username HAVING COUNT(*) > 1) u2 
    WHERE u1.username = u2.username
);

-- Теперь добавляем UNIQUE
ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL;
ALTER TABLE users ADD UNIQUE KEY unique_username (username);

-- Проверяем
SELECT id, name, username, email FROM users;
