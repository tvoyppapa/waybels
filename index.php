<?php
/**
 * Главная страница - Landing Page
 */
session_start();

// Если пользователь уже авторизован - редирект на dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WayBels - Найди своего репетитора</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #7F2CDF 0%, #9851E8 100%);
            min-height: 100vh;
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 80px;
        }
        
        .logo {
            width: 60px;
            height: auto;
        }
        
        .auth-buttons {
            display: flex;
            gap: 16px;
        }
        
        .btn {
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: white;
            color: #7F2CDF;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .hero {
            text-align: center;
            padding: 60px 20px;
        }
        
        .hero h1 {
            font-size: 64px;
            font-weight: 800;
            margin-bottom: 24px;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 24px;
            margin-bottom: 40px;
            opacity: 0.95;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            margin-top: 100px;
        }
        
        .feature {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 32px;
            border-radius: 16px;
            text-align: center;
        }
        
        .feature-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .feature h3 {
            font-size: 24px;
            margin-bottom: 12px;
        }
        
        .feature p {
            opacity: 0.9;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 18px;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        <header>
            <img src="img/logo-white.svg" alt="WayBels" class="logo">
            <div class="auth-buttons">
                <a href="auth_simple.php" class="btn btn-secondary">Войти</a>
                <a href="auth_simple.php" class="btn btn-primary">Регистрация</a>
            </div>
        </header>
        
        <section class="hero">
            <h1>Найди своего идеального репетитора</h1>
            <p>Пространство, где знание превращается в опыт</p>
            
            <div class="cta-buttons">
                <a href="auth_simple.php" class="btn btn-primary" style="padding: 16px 48px; font-size: 18px;">
                    Начать обучение
                </a>
                <a href="auth_simple.php" class="btn btn-secondary" style="padding: 16px 48px; font-size: 18px;">
                    У меня есть аккаунт
                </a>
            </div>
        </section>
        
        <section class="features">
            <div class="feature">
                <div class="feature-icon">✨</div>
                <h3>Персонализированно</h3>
                <p>Найдите репетитора, который идеально подходит именно вам</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🎯</div>
                <h3>Эффективно</h3>
                <p>Достигайте своих целей быстрее с профессиональными преподавателями</p>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🏆</div>
                <h3>Результативно</h3>
                <p>Отслеживайте прогресс и празднуйте достижения</p>
            </div>
        </section>
    </div>
    
</body>
</html>
