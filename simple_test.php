<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db.php';

echo "<h2>🧪 Простой тест регистрации</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: #0f0; padding: 20px; margin: 20px 0;'>";
    echo "<h3>✅ POST ПОЛУЧЕН!</h3>";
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    echo "Имя: $name<br>";
    echo "Email: $email<br>";
    echo "Пароль: " . (strlen($password) > 0 ? "ЕСТЬ (" . strlen($password) . " символов)" : "НЕТ") . "<br>";
    
    // Попытка регистрации
    try {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$name, $email, $hashedPassword]);
        
        $user_id = $pdo->lastInsertId();
        
        echo "<h3 style='color: green;'>✅✅✅ УСПЕШНО ЗАРЕГИСТРИРОВАН!</h3>";
        echo "ID: $user_id<br>";
        
        // Входим
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        
        echo "<h3>Перенаправляем на dashboard...</h3>";
        echo "<a href='dashboard.php' style='font-size: 20px; color: blue;'>→ ПЕРЕЙТИ НА DASHBOARD</a>";
        
        // Редирект через 2 секунды
        header("refresh:2;url=dashboard.php");
        
    } catch (Exception $e) {
        echo "<h3 style='color: red;'>❌ ОШИБКА!</h3>";
        echo $e->getMessage();
    }
    
    echo "</div>";
}
?>

<h3>Форма регистрации:</h3>
<form method="POST" action="simple_test.php" style="max-width: 400px; padding: 20px; background: #f0f0f0;">
    <div style="margin-bottom: 10px;">
        <label>Имя:</label><br>
        <input type="text" name="name" required style="width: 100%; padding: 10px; font-size: 16px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Email:</label><br>
        <input type="email" name="email" required style="width: 100%; padding: 10px; font-size: 16px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Пароль:</label><br>
        <input type="password" name="password" required style="width: 100%; padding: 10px; font-size: 16px;">
    </div>
    
    <button type="submit" style="width: 100%; padding: 15px; background: #7F2CDF; color: white; border: none; font-size: 18px; font-weight: bold; cursor: pointer;">
        ЗАРЕГИСТРИРОВАТЬСЯ
    </button>
</form>

<hr>
<h3>Или войти с существующим:</h3>
<p>Email: test@example.com<br>Пароль: password</p>
<a href="auth.php">→ Перейти на auth.php</a>
