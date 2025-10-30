# 🔥 ПРОБЛЕМА НАЙДЕНА!

## ❌ У вас СТАРАЯ база данных!

```
Подключено к БД: wibs  ← СТАРАЯ!
Таблицы: courses, oauth_providers, password_resets, user_progress, users
Пользователей: 3
```

## ✅ Нужна НОВАЯ база данных:

```
Должно быть: waybels_db  ← НОВАЯ!
Таблицы: users, teacher_profiles, posts, favorites, chats, messages, teacher_applications
Пользователей: 7
```

---

## 🚀 РЕШЕНИЕ (выберите один способ):

### Способ 1: Импортировать в существующую БД wibs

```sql
-- Откройте phpMyAdmin
-- Выберите базу wibs
-- Импорт → database_waybels.sql
-- Нажмите "Вперед"
```

---

### Способ 2: Создать новую БД waybels_db (РЕКОМЕНДУЮ)

**Через phpMyAdmin:**
1. Откройте phpMyAdmin
2. Нажмите "Создать базу данных" (New)
3. Имя: `waybels_db`
4. Кодировка: `utf8mb4_unicode_ci`
5. Создать
6. Выберите новую БД `waybels_db`
7. Импорт → Выберите файл → `database_waybels.sql`
8. Вперед
9. Готово! ✅

**Или через командную строку:**
```bash
mysql -u root -p

# Введите пароль: WayBels2553030App!

CREATE DATABASE waybels_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE waybels_db;
SOURCE /var/www/waybels/database_waybels.sql;
exit;
```

---

## 📝 После импорта проверьте:

```
http://localhost:8000/check_db.php
```

Должно быть:
```
✅ Подключено к БД: waybels_db  ← ПРАВИЛЬНО!

Таблицы:
✅ users
✅ teacher_profiles  ← НОВАЯ!
✅ posts  ← НОВАЯ!
✅ favorites  ← НОВАЯ!
✅ chats  ← НОВАЯ!
✅ messages  ← НОВАЯ!
✅ teacher_applications  ← НОВАЯ!

Пользователей: 7  ← ПРАВИЛЬНО!
```

---

## 🎯 Тестовые аккаунты (после импорта):

```
test@example.com / password
anna@example.com / password (репетитор английского)
dmitry@example.com / password (репетитор математики)
elena@example.com / password (репетитор дизайна)
sergey@example.com / password (репетитор Python)
maria@example.com / password (репетитор музыки)
admin@waybels.com / password
```

---

## ⚡ Быстрая проверка после импорта:

1. Откройте: http://localhost:8000/check_db.php
   → Должно быть 7 таблиц и 7 пользователей

2. Откройте: http://localhost:8000/register.php
   → Зарегистрируйте нового пользователя

3. Откройте: http://localhost:8000/auth.php
   → Войдите: test@example.com / password

4. Должны попасть на: http://localhost:8000/dashboard.php
   → Увидите 5 репетиторов! ✅

---

## 📍 Где файл для импорта:

```
/var/www/waybels/database_waybels.sql
```

Или найдите в папке проекта файл `database_waybels.sql`

---

## ✨ После импорта все заработает!

- ✅ Регистрация
- ✅ Вход
- ✅ Dashboard с репетиторами
- ✅ Профиль
- ✅ Избранное
- ✅ Сообщения

**Импортируйте database_waybels.sql прямо сейчас!** 🚀
