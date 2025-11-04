# 🔍 ОТЛАДКА ПРОФИЛЕЙ

## 🚨 Проблемы:

1. `/@ivan` → редиректит на feed.php ❌
2. `/profile.php` → не редиректит на `/@username` ❌
3. Чистые URL не работают ❌

---

## 🔧 ДИАГНОСТИКА:

### Шаг 1: Открой debug_profile.php
```
http://ваш-домен/debug_profile.php
```

Смотри что показывает:

#### ✅ Если видишь "Колонка username НЕ СУЩЕСТВУЕТ":
**Выполни SQL:**
```sql
USE aqum_db;
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER name;
UPDATE users SET username = CONCAT('user', id);
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL;
CREATE UNIQUE INDEX unique_username ON users(username);
```

Или используй файл:
```bash
mysql -u root -p aqum_db < ADD_USERNAME_SIMPLE.sql
```

---

#### ✅ Если видишь ".htaccess НЕ НАЙДЕН":
Создай `.htaccess` в корне:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^@([A-Za-z0-9_]+)$ profile.php?username=$1 [L,QSA]
</IfModule>
```

---

#### ✅ Если видишь "mod_rewrite ВЫКЛЮЧЕН":
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

#### ✅ Проверь AllowOverride в Apache:
```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```

Добавь (если нет):
```apache
<Directory /var/www/html>
    AllowOverride All
</Directory>
```

Перезагрузи:
```bash
sudo systemctl restart apache2
```

---

### Шаг 2: После исправления

1. **Перезагрузи Apache:**
```bash
sudo systemctl restart apache2
```

2. **Очисти кеш браузера** (Ctrl+Shift+R)

3. **Открой снова:**
```
http://ваш-домен/debug_profile.php
```

4. **Нажми на:** `/@test`

5. **Смотри GET параметры** - должно быть:
```
Array
(
    [username] => test
)
```

---

### Шаг 3: Если GET пустой

Значит .htaccess НЕ работает.

**Проверь:**
1. Файл `.htaccess` в корне проекта? ✅
2. `mod_rewrite` включен? ✅
3. `AllowOverride All` в конфиге Apache? ✅
4. Apache перезагружен? ✅

---

### Шаг 4: Если всё работает

**Удали файл отладки:**
```bash
rm debug_profile.php
```

**Проверь профили:**
- `/@test` → профиль
- `/@ivan` → профиль
- `/profile.php` → твой профиль

---

## 📝 Логи Apache (если ничего не помогает):

```bash
sudo tail -f /var/log/apache2/error.log
```

Открой `/@test` и смотри что в логах.

---

**Пиши что показывает debug_profile.php!** 🔍
