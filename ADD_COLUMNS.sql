-- Добавление недостающих колонок в старую БД wibs

USE wibs;

-- Добавляем колонку interests если её нет
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS interests VARCHAR(50) NULL AFTER password;

-- Добавляем колонку username если её нет
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS username VARCHAR(50) NULL AFTER email;

-- Добавляем колонку avatar если её нет
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) NULL DEFAULT '/img/default-avatar.png' AFTER username;

-- Добавляем колонку created_at если её нет
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER role;

-- Добавляем колонку updated_at если её нет
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Проверяем результат
DESCRIBE users;
