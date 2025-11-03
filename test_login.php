<?php
/**
 * ПРЯМОЙ ТЕСТ ВХОДА (без форм и редиректов)
 */
session_start();

require_once 'db.php';
require_once 'helpers.php';

echo "<h1>Тест входа aqum</h1>";

// Очистка сессии
if (isset($_GET['clear'])) {
    session_destroy();
    session_start();
    echo "<p style='color: green;'>✅ Сессия очищена!</p>";
}

// Тестовые данные
$test_email = 'test@test.com';
$test_password = 'password';

echo "<h2>Шаг 1: Проверка пользователя в БД</h2>";

try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Пользователь найден:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Имя: " . htmlspecialchars($user['name']) . "<br>";
        echo "Email: " . htmlspecialchars($user['email']) . "<br>";
        echo "Роль: " . $user['role'] . "<br><br>";
    } else {
        echo "❌ Пользователь НЕ найден!<br>";
        die("Импортируй SIMPLE_DB.sql");
    }
} catch (Exception $e) {
    die("❌ Ошибка БД: " . $e->getMessage());
}

echo "<h2>Шаг 2: Проверка пароля</h2>";

if (password_verify($test_password, $user['password'])) {
    echo "✅ Пароль правильный!<br><br>";
} else {
    echo "❌ Пароль НЕ совпадает!<br>";
    die();
}

echo "<h2>Шаг 3: Тест авторизации</h2>";

if (isset($_POST['test_login'])) {
    // Эмулируем авторизацию
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    
    echo "<p style='color: green; font-size: 20px;'>✅ АВТОРИЗАЦИЯ УСПЕШНА!</p>";
    echo "<p>Сессия установлена:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<p><a href='feed.php' style='background: #7F2CDF; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;'>→ Перейти на feed.php</a></p>";
    
} else {
    // Показываем форму
    ?>
    <form method="POST" style="background: #f5f5f5; padding: 30px; border-radius: 10px; max-width: 500px;">
        <p><strong>Нажми кнопку для авторизации:</strong></p>
        <p>Email: <?= $test_email ?><br>Пароль: <?= $test_password ?></p>
        
        <button type="submit" name="test_login" style="background: #7F2CDF; color: white; padding: 15px 40px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold;">
            🔐 ВОЙТИ В СИСТЕМУ
        </button>
    </form>
    <?php
}
?>

<hr style="margin: 40px 0;">

<h2>Текущие данные сессии:</h2>
<pre style="background: white; padding: 15px; border-radius: 5px; border-left: 3px solid #7F2CDF;">
<?php print_r($_SESSION); ?>
</pre>

<hr>

<p>
    <a href="?clear=1" style="background: #FF3B30; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🗑️ Очистить сессию
    </a>
    
    <a href="auth.php" style="background: #34C759; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
        → Попробовать auth.php
    </a>
</p>

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
</style>
