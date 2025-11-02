# 🎨 План разработки WayBels v2.0 - Качественный продукт

## 🎯 Цель: Создать современную платформу с liquid glass дизайном и real-time функционалом

---

## 📋 Требования (из обсуждения)

### 1. Дизайн
- ✨ **Liquid Glass Effect** (glassmorphism) на всех элементах
- 🌓 **Темная/светлая тема** (переключение)
- 🎨 **Кастомные обои** в чатах
- 📱 **Адаптивность** под все устройства

### 2. Чат
- 💬 **Real-time** (WebSocket обязательно!)
- 🤖 **Бот Wibs** - автоответы и поддержка
- 🖼️ **Обои на выбор** для каждого чата
- 📎 **Отправка файлов**
- ✅ **Статусы сообщений** (отправлено/прочитано)

### 3. Профиль репетитора (при просмотре)
```
┌─────────────────────────────────────────┐
│  👤 Фото (большая)                       │
│  📝 Имя                                  │
│  💼 Профессия (что преподает)            │
│  🔗 @username                            │
│                                         │
│  [💬 Написать] [📅 Записаться]          │
│                                         │
│  🔗 (иконка сохранить ссылку справа)    │
└─────────────────────────────────────────┘
```

### 4. Личный кабинет (свой профиль)
```
┌─────────────────────────────────────────┐
│  👤 Аватарка        ☰ (бургер-меню)     │
│  📝 Имя                                  │
│  🔗 @username                            │
│  📄 Био                                  │
│                                         │
│  [✏️ Редактировать] [🔗 Поделиться]     │
└─────────────────────────────────────────┘

Бургер-меню:
├─ ⚙️ Настройки (пароль, тема)
├─ 📊 Статистика (ученики, баланс, достижения)
├─ 🎓 Стать репетитором (для обычных)
└─ 🚪 Выйти
```

### 5. Редактирование профиля
- 📸 Изменить аватарку
- ✏️ Изменить имя
- 🔤 Изменить username (раз в 7 дней)
- 📝 О себе
- 📚 Категория интересов

### 6. Календарь бронирования
- 📅 Визуальный календарь
- ⏰ Временные слоты
- ✅ Бронирование/отмена
- 🔔 Напоминания

---

## 🗄️ Обновления базы данных

### Добавим в таблицу `users`:
```sql
ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER email;
ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER avatar;
ALTER TABLE users ADD COLUMN theme ENUM('light', 'dark') DEFAULT 'light';
ALTER TABLE users ADD COLUMN username_changed_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN balance DECIMAL(10,2) DEFAULT 0.00;
```

### Новые таблицы:

#### 1. Настройки чата
```sql
CREATE TABLE chat_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    chat_id INT UNSIGNED NOT NULL,
    wallpaper VARCHAR(255) DEFAULT 'default',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_chat (user_id, chat_id)
);
```

#### 2. Календарь доступности
```sql
CREATE TABLE teacher_availability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0=Пн, 6=Вс
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_day (teacher_id, day_of_week)
);
```

#### 3. Бронирования
```sql
CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    price DECIMAL(10,2) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_date (teacher_id, booking_date),
    INDEX idx_student (student_id)
);
```

#### 4. Бот Wibs
```sql
CREATE TABLE bot_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_from_user BOOLEAN DEFAULT TRUE,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
);
```

#### 5. Достижения
```sql
CREATE TABLE achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) NOT NULL,
    type ENUM('lessons', 'rating', 'students', 'special') DEFAULT 'lessons',
    requirement INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
);
```

---

## 🎨 Дизайн система - Liquid Glass

### CSS переменные:
```css
:root {
  /* Glassmorphism */
  --glass-bg: rgba(255, 255, 255, 0.1);
  --glass-border: rgba(255, 255, 255, 0.2);
  --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  --glass-blur: 10px;
  
  /* Градиенты */
  --gradient-primary: linear-gradient(135deg, #7F2CDF 0%, #9851E8 100%);
  --gradient-glass: linear-gradient(135deg, 
    rgba(127, 44, 223, 0.1) 0%, 
    rgba(152, 81, 232, 0.1) 100%);
  
  /* Цвета */
  --primary: #7F2CDF;
  --secondary: #9851E8;
  --success: #34C759;
  --danger: #FF3B30;
  --warning: #FF9500;
}

[data-theme="dark"] {
  --glass-bg: rgba(0, 0, 0, 0.3);
  --glass-border: rgba(255, 255, 255, 0.1);
  --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.glass-card {
  background: var(--glass-bg);
  backdrop-filter: blur(var(--glass-blur));
  -webkit-backdrop-filter: blur(var(--glass-blur));
  border: 1px solid var(--glass-border);
  border-radius: 20px;
  box-shadow: var(--glass-shadow);
}
```

---

## 🛠️ Технологический стек

### Backend:
- PHP 7.4+ (текущий)
- MySQL 8.0
- **Node.js** для WebSocket сервера

### WebSocket:
- **Socket.io** или **Soketi** (бесплатная альтернатива Pusher)

### Frontend:
- Vanilla JavaScript (оставляем)
- **FullCalendar.js** для календаря
- **Socket.io-client** для real-time

### Хранилище:
- Локально (на старте)
- Потом Cloudflare R2 для файлов

---

## 📅 План разработки (4-6 недель)

### Неделя 1: База и дизайн
- [x] ✅ Обновление БД (новые поля и таблицы)
- [ ] 🎨 Создание glassmorphism дизайн-системы
- [ ] 🎨 Обновление всех существующих страниц
- [ ] 🌓 Реализация темной темы

### Неделя 2: Профили
- [ ] 👤 Новый дизайн профиля репетитора
- [ ] ✏️ Редактирование профиля
- [ ] 🔤 Username с ограничением изменения
- [ ] ☰ Бургер-меню с настройками
- [ ] 🔗 Кнопка "Поделиться профилем"

### Неделя 3: Real-time чат
- [ ] 🔧 Настройка Node.js + Socket.io
- [ ] 💬 Real-time чат (отправка/получение)
- [ ] 👥 Онлайн статусы
- [ ] ✅ Статусы сообщений
- [ ] 🤖 Бот Wibs (базовый)
- [ ] 🎨 Кастомные обои

### Неделя 4: Календарь и бронирование
- [ ] 📅 Интеграция FullCalendar.js
- [ ] ⏰ Установка доступности (репетитор)
- [ ] 📝 Бронирование урока (ученик)
- [ ] ✅ Подтверждение/отмена
- [ ] 🔔 Уведомления

### Неделя 5: Дополнительные функции
- [ ] 📎 Загрузка файлов в чат
- [ ] 🏆 Система достижений
- [ ] 📊 Статистика для репетиторов
- [ ] 💰 Баланс и транзакции (заготовка)

### Неделя 6: Тестирование и оптимизация
- [ ] 🐛 Исправление багов
- [ ] ⚡ Оптимизация производительности
- [ ] 📱 Тестирование на устройствах
- [ ] 📝 Документация

---

## 🎯 С чего начнем ПРЯМО СЕЙЧАС?

### Шаг 1: Обновляем БД
Создам SQL скрипт для добавления новых полей и таблиц

### Шаг 2: Создаем glassmorphism дизайн
Новый CSS файл с liquid glass эффектами

### Шаг 3: Обновляем dashboard
Применяем новый дизайн к существующим страницам

**Готов начать? Подтверди и я сразу приступаю!** 🚀
