<?php
/**
 * Универсальное боковое меню (Desktop)
 * Автоматически определяет активную страницу
 * Параметры:
 * - $compact: true для компактного режима (только иконки) - для чатов, звонков
 */
$current_page = basename($_SERVER['PHP_SELF']);
$compact_mode = isset($compact) && $compact === true;
?>
<aside class="sidebar <?= $compact_mode ? 'sidebar-compact' : '' ?>">
    <div class="sidebar-header">
        <img src="/img/logo-white.svg" alt="aqum" class="sidebar-logo">
        <?php if (!$compact_mode): ?>
        <span class="sidebar-brand">aqum</span>
        <?php endif; ?>
    </div>
    
    <nav class="sidebar-nav">
        <a href="/feed.php" class="nav-item <?= in_array($current_page, ['feed.php', 'dashboard.php', 'index.php']) ? 'active' : '' ?>" title="Лента">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="9" y1="9" x2="15" y2="9"/>
                <line x1="9" y1="13" x2="15" y2="13"/>
                <line x1="9" y1="17" x2="13" y2="17"/>
            </svg>
            <?php if (!$compact_mode): ?>
            <span>Лента</span>
            <?php endif; ?>
        </a>
        
        <a href="/search.php" class="nav-item <?= $current_page === 'search.php' ? 'active' : '' ?>" title="Поиск">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <?php if (!$compact_mode): ?>
            <span>Поиск</span>
            <?php endif; ?>
        </a>
        
        <a href="/messages.php" class="nav-item <?= $current_page === 'messages.php' ? 'active' : '' ?>" title="Сообщения">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <?php if (!$compact_mode): ?>
            <span>Сообщения</span>
            <?php endif; ?>
        </a>
        
        <a href="/profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>" title="Профиль">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <?php if (!$compact_mode): ?>
            <span>Профиль</span>
            <?php endif; ?>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="/logout.php" class="nav-item" title="Выйти">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <?php if (!$compact_mode): ?>
            <span>Выйти</span>
            <?php endif; ?>
        </a>
    </div>
</aside>
