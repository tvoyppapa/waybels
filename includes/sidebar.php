<?php
/**
 * Компактное боковое меню (только иконки)
 */
$current_page = basename($_SERVER['PHP_SELF']);
$compact_mode = isset($compact) && $compact === true;
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="/feed.php">
            <img src="/img/logo-white.svg" alt="aqum" class="sidebar-logo">
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <a href="/feed.php" class="nav-item <?= in_array($current_page, ['feed.php', 'dashboard.php', 'index.php']) ? 'active' : '' ?>" title="Лента">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="9" y1="9" x2="15" y2="9"/>
                <line x1="9" y1="13" x2="15" y2="13"/>
                <line x1="9" y1="17" x2="13" y2="17"/>
            </svg>
        </a>
        
        <a href="/search.php" class="nav-item <?= $current_page === 'search.php' ? 'active' : '' ?>" title="Поиск">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
        </a>
        
        <a href="/messages.php" class="nav-item <?= $current_page === 'messages.php' ? 'active' : '' ?>" title="Сообщения">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        </a>
        
        <a href="/profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>" title="Профиль">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <!-- Переключатель темы -->
        <button class="theme-toggle" onclick="toggleTheme()" title="Сменить тему">
            <svg class="theme-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/>
                <line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/>
                <line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
        </button>
    </div>
</aside>

<style>
/* КОМПАКТНЫЙ SIDEBAR - ТОЛЬКО ИКОНКИ */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 80px;
    background: var(--bg-primary);
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 0;
    z-index: 1000;
    box-shadow: 2px 0 20px rgba(0,0,0,0.05);
}

.sidebar-header {
    margin-bottom: 40px;
}

.sidebar-header a {
    display: flex;
    align-items: center;
    justify-content: center;
}

.sidebar-logo {
    width: 42px;
    height: 42px;
    transition: transform 0.3s;
    filter: none;
}

.sidebar-logo:hover {
    transform: scale(1.1);
}

[data-theme="dark"] .sidebar-logo {
    filter: brightness(0) invert(1);
}

.sidebar-nav {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
    align-items: center;
}

.nav-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s;
    position: relative;
}

.nav-item:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
    transform: scale(1.05);
}

.nav-item.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.nav-item.active::after {
    content: '';
    position: absolute;
    left: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 24px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 0 4px 4px 0;
}

.nav-icon {
    width: 24px;
    height: 24px;
}

.sidebar-footer {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.theme-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.3s;
    border-radius: 12px;
}

.theme-toggle:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
    transform: scale(1.05);
}

.theme-icon {
    width: 24px;
    height: 24px;
}

/* Адаптация для мобилки - скрываем sidebar */
@media (max-width: 768px) {
    .sidebar {
        display: none;
    }
}
</style>
