# ⚠️ ВАЖНО! Нужно импортировать базу данных!

## Проблема:
База данных `waybels_db` создана, но **таблиц в ней нет**!

---

## ✅ Решение (2 способа):

### Способ 1: Через phpMyAdmin (ПРОЩЕ)

1. Откройте **phpMyAdmin**
2. Выберите базу данных **waybels_db** слева
3. Перейдите на вкладку **"Импорт"**
4. Нажмите **"Выберите файл"**
5. Выберите файл: `/workspace/database_waybels.sql`
6. Нажмите **"Вперед"** внизу страницы
7. Готово! ✅

---

### Способ 2: Через командную строку

```bash
# Вариант A: Если у вас Linux/Mac
mysql -u root -p waybels_db < /workspace/database_waybels.sql

# Вариант B: Для Windows
mysql -u root -p waybels_db < C:\path\to\workspace\database_waybels.sql
```

Пароль: `WayBels2553030App!`

---

## 🔍 Проверка (после импорта):

Откройте в браузере:
```
http://localhost:8000/check_db.php
```

Должны увидеть:
```
✅ Подключено к БД: waybels_db

Таблицы в БД:
✅ users
✅ teacher_profiles
✅ posts
✅ favorites
✅ chats
✅ messages
✅ teacher_applications

Пользователей: 7
```

---

## 📋 Что будет после импорта:

### 7 таблиц:
- users (пользователи)
- teacher_profiles (профили репетиторов)
- posts (посты)
- favorites (избранное)
- chats (чаты)
- messages (сообщения)
- teacher_applications (заявки)

### Тестовые данные:
- 7 пользователей
- 5 репетиторов
- 4 поста

### Тестовые аккаунты:
```
Email: test@example.com
Пароль: password

Email: anna@example.com
Пароль: password

Email: admin@waybels.com
Пароль: password
```

---

## ⚡ Быстрый тест (после импорта):

1. Откройте: `http://localhost:8000/check_db.php`
2. Убедитесь, что таблицы есть
3. Откройте: `http://localhost:8000/auth.php`
4. Войдите: `test@example.com` / `password`
5. Должны попасть на dashboard! ✅

---

## 🐛 Если что-то пошло не так:

### Ошибка при импорте:
```
# Проверьте, существует ли БД
mysql -u root -p -e "SHOW DATABASES LIKE 'waybels_db';"

# Если нет - создайте
mysql -u root -p -e "CREATE DATABASE waybels_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Потом импортируйте заново
mysql -u root -p waybels_db < /workspace/database_waybels.sql
```

### Ошибка "Access denied":
Проверьте пароль в файле `db.php`

### Таблицы не появились:
```sql
-- Зайдите в MySQL
mysql -u root -p

-- Выберите БД
USE waybels_db;

-- Проверьте таблицы
SHOW TABLES;

-- Если пусто - импортируйте через source
SOURCE /workspace/database_waybels.sql;
```

---

## ✅ После импорта:

Все заработает:
- ✅ Регистрация
- ✅ Вход
- ✅ Dashboard
- ✅ Репетиторы
- ✅ Профиль

**Импортируйте БД и все заработает!** 🚀
