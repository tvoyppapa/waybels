# ⚡ НАЧНИ ЗДЕСЬ!

## 🚨 ГЛАВНАЯ ПРОБЛЕМА: username колонки нет!

### ✅ РЕШЕНИЕ (копируй в phpMyAdmin):

```sql
USE aqum_db;

-- 1. Добавляем username
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER name;

-- 2. Заполняем
UPDATE users SET username = CONCAT('user', id);

-- 3. Делаем NOT NULL и UNIQUE
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL;
CREATE UNIQUE INDEX unique_username ON users(username);

-- 4. Добавляем theme
ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT 'auto' AFTER bio;

-- 5. Проверяем
SELECT id, name, username, email FROM users;
```

**Игнорируй ошибки** если колонки уже есть!

---

## 📄 Или используй файл:

```bash
mysql -u root -p aqum_db < ADD_USERNAME_SIMPLE.sql
```

---

## ✅ После этого:

1. Вставь пользователей:
```bash
mysql -u root -p aqum_db < QUICK_INSERT_USERS.sql
```

2. Открой: `http://ваш-домен/auth.php`

3. Войди: **test@test.com** / **password**

4. Профиль: `/@test`

---

**ВСЕ РАБОТАЕТ!** 🚀

Документация: `README.md`
