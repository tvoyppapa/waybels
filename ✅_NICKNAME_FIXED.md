# ✅ NICKNAME ИСПРАВЛЕН!

## Проблема:
```sql
INSERT INTO users (name, nickname, email, ...) VALUES ...
```
**Ошибка:** `#1054 - Неизвестный столбец 'nickname' в 'INSERT INTO'`

## Решение:
Заменил `nickname` на `username` во всех SQL файлах!

## ✅ Исправленные файлы:
1. **RESET_DATABASE.sql** - главный файл структуры
2. **QUICK_INSERT_USERS.sql** - новый файл для быстрой вставки

## 🚀 Используй этот SQL:

```sql
USE aqum_db;

-- Пароль для всех: password
INSERT INTO users (name, username, email, phone, password, age, interests, role, last_seen_at) VALUES
('Тест Юзер', 'test', 'test@test.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 25, 'programming,design', 'user', NOW()),
('Иван Петров', 'ivan', 'ivan@aqum.com', '+79991234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 32, 'languages,teaching', 'teacher', NOW()),
('Мария Смирнова', 'maria', 'maria@aqum.com', '+79997654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 28, 'programming,teaching', 'teacher', NOW()),
('Админ', 'admin', 'admin@aqum.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 30, 'programming,management', 'admin', NOW());

-- Проверяем
SELECT id, name, username, email, role FROM users;
```

## 📝 Или используй файл:
```bash
mysql -u root -p aqum_db < QUICK_INSERT_USERS.sql
```

## 🎯 Тестовые аккаунты:

| Email | Username | Пароль | Роль |
|-------|----------|--------|------|
| test@test.com | test | password | user |
| ivan@aqum.com | ivan | password | teacher |
| maria@aqum.com | maria | password | teacher |
| admin@aqum.com | admin | password | admin |

## 🔗 Профили:
- `/@test` - Тест Юзер
- `/@ivan` - Иван Петров (репетитор)
- `/@maria` - Мария Смирнова (репетитор)
- `/@admin` - Админ

---

**Все исправлено!** Теперь `username` используется везде, `nickname` удален!
