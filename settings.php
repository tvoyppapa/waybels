<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Обработка обновления темы
if (isset($_POST['update_theme'])) {
    $theme = $_POST['theme'] ?? 'auto';
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $user_id]);
    $_SESSION['success'] = 'Тема обновлена';
    header('Location: settings.php');
    exit;
}

$page_title = 'Настройки';
include 'includes/layout.php';
?>

<div class="settings-container">
    <h1>Настройки</h1>
    
    <!-- ПРОФИЛЬ -->
    <section class="settings-section">
        <h2>Профиль</h2>
        
        <div class="setting-item">
            <div class="setting-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <div>
                    <div class="setting-title">Имя пользователя</div>
                    <div class="setting-desc">@<?= e($user['username']) ?></div>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="window.location.href='profile.php?user=<?= $user['username'] ?>'">Просмотр</button>
        </div>
    </section>
    
    <!-- АККАУНТ -->
    <section class="settings-section">
        <h2>Аккаунт</h2>
        
        <div class="setting-item">
            <div class="setting-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
                <div>
                    <div class="setting-title">Стать репетитором</div>
                    <div class="setting-desc">Начните зарабатывать на обучении</div>
                </div>
            </div>
            <button class="btn btn-primary" onclick="window.location.href='apply_teacher.php'">Подать заявку</button>
        </div>
    </section>
    
    <!-- ВНЕШНИЙ ВИД -->
    <section class="settings-section">
        <h2>Внешний вид</h2>
        
        <form method="POST">
            <div class="setting-item">
                <div class="setting-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                    </svg>
                    <div>
                        <div class="setting-title">Тема оформления</div>
                        <div class="setting-desc">Выберите светлую, темную или автоматическую тему</div>
                    </div>
                </div>
                <select name="theme" class="form-select" onchange="this.form.submit()">
                    <option value="auto" <?= $user['theme'] === 'auto' ? 'selected' : '' ?>>Авто</option>
                    <option value="light" <?= $user['theme'] === 'light' ? 'selected' : '' ?>>Светлая</option>
                    <option value="dark" <?= $user['theme'] === 'dark' ? 'selected' : '' ?>>Темная</option>
                </select>
                <input type="hidden" name="update_theme" value="1">
            </div>
        </form>
    </section>
    
    <!-- ВЫХОД -->
    <section class="settings-section">
        <div class="setting-item danger">
            <div class="setting-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <div>
                    <div class="setting-title">Выйти из аккаунта</div>
                    <div class="setting-desc">Завершить текущую сессию</div>
                </div>
            </div>
            <button class="btn btn-danger" onclick="if(confirm('Вы уверены?')) window.location.href='logout.php'">Выйти</button>
        </div>
    </section>
</div>

<?php include 'includes/layout_footer.php'; ?>

<style>
.settings-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.settings-container h1 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 30px;
    color: var(--text-primary);
}

.settings-section {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 20px;
}

.settings-section h2 {
    font-size: 16px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-secondary);
    margin-bottom: 20px;
    letter-spacing: 0.5px;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid var(--border-color);
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-label {
    display: flex;
    gap: 15px;
    align-items: center;
    flex: 1;
}

.setting-label svg {
    width: 24px;
    height: 24px;
    color: var(--text-secondary);
    flex-shrink: 0;
}

.setting-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.setting-desc {
    font-size: 13px;
    color: var(--text-secondary);
}

.form-select {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-size: 14px;
    cursor: pointer;
}

.setting-item.danger {
    border-color: rgba(229, 62, 62, 0.2);
}

.setting-item.danger .setting-title {
    color: #e53e3e;
}

.btn-danger {
    background: #e53e3e;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

@media (max-width: 768px) {
    .settings-container {
        padding: 15px;
    }
    
    .setting-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .setting-item .btn,
    .setting-item .form-select {
        width: 100%;
    }
}
</style>
