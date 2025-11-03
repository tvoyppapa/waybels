<?php
/**
 * Отладка авторизации
 * Покажет что происходит при входе
 */

session_start();

echo "<h1>🔍 Отладка авторизации aqum</h1>";

// Показать данные сессии
echo "<h2>Данные сессии:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Показать POST данные если есть
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST данные:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}

// Форма для быстрого теста
?>

<h2>Быстрый тест входа:</h2>
<form method="POST" action="auth_handler.php" style="background: #f5f5f5; padding: 20px; border-radius: 10px; max-width: 400px;">
    <p><strong>Тестовый аккаунт:</strong></p>
    <p>Email: test@test.com<br>Пароль: password</p>
    
    <div style="margin-bottom: 15px;">
        <label>Email:</label><br>
        <input type="email" name="email" value="test@test.com" style="width: 100%; padding: 8px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label>Пароль:</label><br>
        <input type="password" name="password" value="password" style="width: 100%; padding: 8px;" required>
    </div>
    
    <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
    
    <button type="submit" name="login" style="background: #7F2CDF; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        Войти
    </button>
</form>

<hr style="margin: 30px 0;">

<h2>Проверка подключения:</h2>
<?php
try {
    require_once 'db.php';
    
    echo "✅ БД подключена: aqum_db<br>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['test@test.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Пользователь найден: " . htmlspecialchars($user['name']) . "<br>";
        echo "✅ Email: " . htmlspecialchars($user['email']) . "<br>";
        
        // Проверка пароля
        if (password_verify('password', $user['password'])) {
            echo "✅ Пароль 'password' правильный<br>";
        } else {
            echo "❌ Пароль НЕ совпадает<br>";
        }
    } else {
        echo "❌ Пользователь test@test.com НЕ найден<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>

<hr style="margin: 30px 0;">

<h2>Действия:</h2>
<p>
    <a href="auth.php" style="background: #7F2CDF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
        Перейти к auth.php →
    </a>
</p>

<p>
    <a href="feed.php" style="background: #34C759; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
        Перейти к feed.php →
    </a>
</p>

<p>
    <a href="?clear_session=1" style="background: #FF3B30; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
        Очистить сессию
    </a>
</p>

<?php
if (isset($_GET['clear_session'])) {
    session_destroy();
    echo "<script>alert('Сессия очищена!'); window.location.href='DEBUG_AUTH.php';</script>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: #f9f9f9;
}
h1 { color: #7F2CDF; }
h2 { color: #333; margin-top: 30px; }
pre {
    background: white;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #7F2CDF;
    overflow-x: auto;
}
</style>
