<!-- Sidebar для desktop (80px, только иконки) -->
<aside class="sidebar">
    <!-- Логотип -->
    <div class="sidebar-logo">
        <a href="/feed_aqum.php">
            <!-- SVG логотип AQUM с градиентом -->
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="brandGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#7F2CDF;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#9B5CF9;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <circle cx="24" cy="24" r="20" fill="url(#brandGradient)"/>
                <path d="M24 14L28 22H20L24 14Z" fill="white" opacity="0.9"/>
                <path d="M24 26L20 34H28L24 26Z" fill="white" opacity="0.7"/>
            </svg>
        </a>
    </div>
    
    <!-- Навигация -->
    <nav class="sidebar-nav">
        <!-- Главная (Feed) -->
        <a href="/feed_aqum.php" 
           class="sidebar-item <?= ($current_page ?? '') === 'feed' ? 'active' : '' ?>"
           title="Главная">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </a>
        
        <!-- Поиск -->
        <a href="/search_aqum.php" 
           class="sidebar-item <?= ($current_page ?? '') === 'search' ? 'active' : '' ?>"
           title="Поиск">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
        </a>
        
        <!-- Сообщения -->
        <a href="/messages_aqum.php" 
           class="sidebar-item <?= ($current_page ?? '') === 'messages' ? 'active' : '' ?>"
           title="Сообщения">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="sidebar-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
            <?php endif; ?>
        </a>
        
        <!-- Профиль -->
        <a href="/profile_aqum.php?u=<?= $_SESSION['username'] ?? '' ?>" 
           class="sidebar-item <?= ($current_page ?? '') === 'profile' ? 'active' : '' ?>"
           title="Профиль">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </a>
    </nav>
    
    <!-- Бургер-меню внизу -->
    <div class="sidebar-footer">
        <button class="sidebar-item" id="burger-menu" title="Меню">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
    </div>
</aside>

<!-- Bottom Navigation для mobile -->
<nav class="bottom-nav">
    <div class="bottom-nav-inner">
        <a href="/feed_aqum.php" class="bottom-nav-item <?= ($current_page ?? '') === 'feed' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Главная</span>
        </a>
        
        <a href="/search_aqum.php" class="bottom-nav-item <?= ($current_page ?? '') === 'search' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <span>Поиск</span>
        </a>
        
        <a href="/messages_aqum.php" class="bottom-nav-item <?= ($current_page ?? '') === 'messages' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span>Чаты</span>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="sidebar-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
            <?php endif; ?>
        </a>
        
        <a href="/profile_aqum.php?u=<?= $_SESSION['username'] ?? '' ?>" class="bottom-nav-item <?= ($current_page ?? '') === 'profile' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Профиль</span>
        </a>
    </div>
</nav>

<!-- Модальное меню (бургер) -->
<div id="burger-modal" style="display: none;">
    <!-- JavaScript добавит содержимое -->
</div>
