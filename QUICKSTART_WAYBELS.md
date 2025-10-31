# ⚡ WayBels - Быстрый старт

## 🚀 Запуск за 3 шага

### Шаг 1: Импорт БД (1 минута)

```bash
mysql -u root -p
```

```sql
source /workspace/database_waybels.sql
```

### Шаг 2: Запуск сервера (10 секунд)

```bash
cd /workspace
php -S localhost:8000
```

### Шаг 3: Открыть браузер

```
http://localhost:8000
```

**Войти:**
- Email: `test@example.com`
- Пароль: `password`

---

## 🎯 Что работает прямо сейчас

### ✅ Для учеников:
1. **Регистрация** → Выбор интереса → Автовход
2. **Лента репетиторов** → Фильтр по интересу
3. **Поиск** → По предмету или имени
4. **Добавить в избранное** → ❤️ (AJAX, без перезагрузки)
5. **Детальный просмотр** → Видео + информация
6. **Профиль** → Редактирование, смена пароля
7. **Заявка на репетитора** → Форма с валидацией

### ✅ Для репетиторов:
- Профиль с видео
- Статистика (рейтинг, уроки)
- Посты и материалы
- Цена за урок

---

## 📱 Тестирование

### Тестовые аккаунты:

**Ученик:**
```
test@example.com / password
```

**Репетиторы:**
```
anna@example.com / password (Английский)
dmitry@example.com / password (Математика)
elena@example.com / password (Дизайн)
sergey@example.com / password (Python)
maria@example.com / password (Музыка)
```

**Админ:**
```
admin@waybels.com / password
```

---

## 🎨 Дизайн

### Цвета:
- Primary: `#7F2CDF`
- Шрифт: `Inter`
- Адаптивный: Desktop + Mobile

### Навигация:

**Desktop (слева):**
```
┌─────────────┐
│ 📱 WayBels  │
├─────────────┤
│ 🏠 Главная  │
│ 💬 Сообщения│
│ 👤 Профиль  │
│ ❤️ Избранное│
│ 🚪 Выход    │
└─────────────┘
```

**Mobile (снизу, glass effect):**
```
┌───┬───┬───┬───┐
│🏠 │💬 │❤️ │👤 │
└───┴───┴───┴───┘
```

---

## 📊 Структура БД

### 7 таблиц:
1. `users` - Пользователи
2. `teacher_profiles` - Профили репетиторов
3. `posts` - Посты
4. `favorites` - Избранное
5. `chats` - Чаты
6. `messages` - Сообщения
7. `teacher_applications` - Заявки

---

## 🔧 Возможные проблемы

### 1. "Ошибка подключения к БД"
```bash
# Проверьте MySQL
sudo systemctl status mysql

# Проверьте пароль в db.php
$password = "WayBels2553030App!";
```

### 2. "Headers already sent"
```bash
# Удалите пробелы до <?php
# Сохраните файлы в UTF-8 без BOM
```

### 3. CSS не загружается
```bash
# Проверьте пути
ls -la /workspace/style/
```

---

## 📝 Основные файлы

### PHP (16 файлов):
```
index.php            - Вход/регистрация
dashboard.php        - Лента репетиторов ⭐
teacher.php          - Детальная страница
profile.php          - Профиль
messages.php         - Сообщения
favorites.php        - Избранное
apply_teacher.php    - Заявка
logout.php           - Выход
db.php              - БД подключение
helpers.php         - Функции
```

### CSS (5 файлов):
```
style/dashboard.css  - Основные стили ⭐
style/auth.css       - Аутентификация
style/teacher.css    - Страница репетитора
style/profile.css    - Профиль
style/messages.css   - Сообщения
```

### JS (4 файла):
```
js/dashboard.js      - Лента ⭐
js/auth.js          - Аутентификация
js/teacher.js       - Страница репетитора
js/profile.js       - Профиль
```

---

## ✨ Основные функции

### Поиск и фильтрация:
```javascript
// Поиск работает автоматически с debounce
// Сортировка: рейтинг, уроки, новые
```

### Избранное (AJAX):
```javascript
// Клик на ❤️ → запрос без перезагрузки
// Уведомление о добавлении/удалении
```

### Валидация:
```javascript
// Email: формат проверяется
// Пароль: минимум 6 символов
// Описание: 100-500 символов
```

---

## 🎯 Workflow

### Новый ученик:
1. Регистрация → Выбор интереса
2. → Лента с фильтром по интересу
3. → Просмотр репетиторов
4. → Добавление в избранное
5. → Написать сообщение
6. → Бронирование урока (будущее)

### Стать репетитором:
1. Профиль → "Стать репетитором"
2. → Заполнение заявки
3. → Ожидание одобрения админом
4. → Создание контента
5. → Получение заявок от учеников

---

## 📚 Документация

### Полная:
- `WAYBELS_README.md` - Основная
- `INSTALLATION.md` - Установка
- `PROJECT_SUMMARY.md` - Сводка

### Быстрая:
- `QUICKSTART_WAYBELS.md` - Этот файл

---

## 🔥 Фишки проекта

1. ✅ **Glass morphism** на мобильном меню
2. ✅ **AJAX избранное** без перезагрузки
3. ✅ **Адаптивный дизайн** Desktop + Mobile
4. ✅ **Плавные анимации** всех элементов
5. ✅ **Валидация** на сервере и клиенте
6. ✅ **CSRF защита** всех форм
7. ✅ **Красивые карточки** с hover эффектами
8. ✅ **Фильтр по интересам** автоматически

---

## 🚀 Деплой

### Для локальной разработки:
```bash
php -S localhost:8000
```

### Для продакшена:
1. Настроить Apache/Nginx
2. Изменить пароль БД
3. Отключить display_errors
4. Включить HTTPS
5. Настроить логирование

---

## 💡 Подсказки

### Изменить цвета:
```css
/* /style/dashboard.css */
:root {
  --primary: #YOUR_COLOR;
}
```

### Добавить категорию:
```php
/* /config.php */
'math' => ['icon' => '➗', 'name' => 'Математика']
```

### Создать нового тестового репетитора:
```sql
INSERT INTO users (name, email, password, role, interests)
VALUES ('Имя', 'email@test.com', '$2y$10$...', 'teacher', 'math');

INSERT INTO teacher_profiles (user_id, subject, description, ...)
VALUES (LAST_INSERT_ID(), 'Математика', 'Описание...', ...);
```

---

## 📞 Помощь

### Логи:
```bash
tail -f /var/log/apache2/error.log
```

### Отладка:
```php
<?php
var_dump($variable);
print_r($_SESSION);
echo '<pre>'; print_r($data); echo '</pre>';
?>
```

### SQL:
```sql
-- Проверить пользователей
SELECT * FROM users;

-- Проверить репетиторов
SELECT * FROM teacher_profiles WHERE is_approved = 1;

-- Проверить избранное
SELECT * FROM favorites WHERE user_id = 1;
```

---

## ✅ Checklist перед запуском

- [ ] MySQL запущен
- [ ] База `waybels_db` создана
- [ ] Таблицы импортированы (7 штук)
- [ ] Тестовые данные есть (SELECT * FROM users)
- [ ] PHP 7.4+ установлен (php -v)
- [ ] Папка `img/avatars` создана
- [ ] Файл `db.php` настроен
- [ ] Сервер запущен (php -S localhost:8000)
- [ ] Браузер открыт (http://localhost:8000)
- [ ] Можно войти (test@example.com)

---

## 🎉 Готово!

**Платформа WayBels запущена и готова к работе!**

Откройте `http://localhost:8000` и начните тестирование! 🚀

---

**WayBels - Connecting Students with Great Teachers!** 🎓✨
