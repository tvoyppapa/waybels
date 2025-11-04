<?php
/**
 * Универсальный хедер страницы
 * Отображается справа вверху - профиль пользователя
 * Параметры:
 * - $page_title: заголовок страницы
 * - $show_back: true для показа кнопки "Назад"
 * - $back_url: URL для кнопки "Назад"
 */

// Получаем данные пользователя из сессии
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // Если пользователь не авторизован - минимальный хедер
    ?>
    <header class="header">
        <div class="header-left">
            <img src="/img/logo-white.svg" alt="aqum" class="mobile-logo">
        </div>
    </header>
    <?php
    return;
}

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT id, name, email, avatar, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

if (!$current_user) {
    header('Location: /logout.php');
    exit;
}

$page_title = $page_title ?? '';
$show_back = $show_back ?? false;
$back_url = $back_url ?? 'javascript:history.back()';
?>
<header class="header">
    <div class="header-left">
        <img src="/img/logo-white.svg" alt="aqum" class="mobile-logo">
        
        <?php if ($show_back): ?>
        <a href="<?= e($back_url) ?>" class="back-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            <span class="back-text">Назад</span>
        </a>
        <?php endif; ?>
        
        <?php if ($page_title): ?>
        <h1 class="page-title"><?= e($page_title) ?></h1>
        <?php endif; ?>
    </div>
    
    <div class="header-right">
        <div class="user-profile-dropdown">
            <button class="user-profile-btn" id="userProfileBtn">
                <?php if ($current_user['avatar']): ?>
                <img src="<?= e($current_user['avatar']) ?>" alt="<?= e($current_user['name']) ?>" class="user-avatar">
                <?php else: ?>
                <div class="user-avatar user-avatar-placeholder">
                    <?= strtoupper(mb_substr($current_user['name'], 0, 1)) ?>
                </div>
                <?php endif; ?>
                <span class="user-name"><?= e($current_user['name']) ?></span>
                <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
            
            <div class="user-dropdown-menu" id="userDropdownMenu">
                <a href="/profile.php?user=<?= $current_user['id'] ?>" class="dropdown-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Мой профиль
                </a>
                
                <a href="/settings.php" class="dropdown-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"/>
                    </svg>
                    Настройки
                </a>
                
                <?php if ($current_user['role'] === 'admin'): ?>
                <div class="dropdown-divider"></div>
                <a href="/admin.php" class="dropdown-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="9" y1="3" x2="9" y2="21"/>
                    </svg>
                    Админ-панель
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
// Dropdown toggle
document.addEventListener('DOMContentLoaded', function() {
    const profileBtn = document.getElementById('userProfileBtn');
    const dropdownMenu = document.getElementById('userDropdownMenu');
    
    if (profileBtn && dropdownMenu) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        // Закрыть при клике вне
        document.addEventListener('click', function() {
            dropdownMenu.classList.remove('show');
        });
    }
});
</script>
