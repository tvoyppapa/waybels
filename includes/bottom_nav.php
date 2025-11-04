<?php
/**
 * Нижнее меню (Mobile) с бургером
 */
$current_page = basename($_SERVER['PHP_SELF']);
$hide_nav = isset($hide_nav) && $hide_nav === true;

if ($hide_nav) return;
?>
<nav class="bottom-nav">
    <a href="/feed.php" class="bottom-nav-item <?= in_array($current_page, ['feed.php', 'dashboard.php', 'index.php']) ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <line x1="9" y1="9" x2="15" y2="9"/>
            <line x1="9" y1="13" x2="15" y2="13"/>
            <line x1="9" y1="17" x2="13" y2="17"/>
        </svg>
        <span>Лента</span>
    </a>
    
    <a href="/search.php" class="bottom-nav-item <?= $current_page === 'search.php' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.35-4.35"/>
        </svg>
        <span>Поиск</span>
    </a>
    
    <a href="/messages.php" class="bottom-nav-item <?= $current_page === 'messages.php' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span>Сообщения</span>
    </a>
    
    <a href="/profile.php" class="bottom-nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        <span>Профиль</span>
    </a>
    
    <!-- БУРГЕР МЕНЮ -->
    <button class="bottom-nav-item" id="burgerMenuBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
        <span>Меню</span>
    </button>
</nav>

<!-- БУРГЕР МЕНЮ MODAL -->
<div class="burger-menu-overlay" id="burgerMenuOverlay">
    <div class="burger-menu">
        <div class="burger-menu-header">
            <h3>Меню</h3>
            <button class="burger-close" onclick="closeBurgerMenu()">×</button>
        </div>
        
        <div class="burger-menu-content">
            <!-- ТЕМА -->
            <div class="burger-item">
                <div class="burger-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                    </svg>
                </div>
                <div class="burger-item-content">
                    <div class="burger-item-title">Тема оформления</div>
                    <div class="theme-switcher">
                        <button class="theme-btn" data-theme="auto">Авто</button>
                        <button class="theme-btn" data-theme="light">Светлая</button>
                        <button class="theme-btn" data-theme="dark">Темная</button>
                    </div>
                </div>
            </div>
            
            <div class="burger-divider"></div>
            
            <!-- ПОДДЕРЖКА -->
            <a href="/support.php" class="burger-item">
                <div class="burger-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div class="burger-item-content">
                    <div class="burger-item-title">Поддержка</div>
                    <div class="burger-item-desc">Чат с поддержкой</div>
                </div>
            </a>
            
            <!-- НАСТРОЙКИ -->
            <a href="/settings.php" class="burger-item">
                <div class="burger-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12 1v6m0 6v6"/>
                    </svg>
                </div>
                <div class="burger-item-content">
                    <div class="burger-item-title">Настройки</div>
                    <div class="burger-item-desc">Управление аккаунтом</div>
                </div>
            </a>
            
            <div class="burger-divider"></div>
            
            <!-- ВЫЙТИ -->
            <a href="/logout.php" class="burger-item danger">
                <div class="burger-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </div>
                <div class="burger-item-content">
                    <div class="burger-item-title">Выйти</div>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
/* БУРГЕР МЕНЮ OVERLAY */
.burger-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: none;
    animation: fadeIn 0.3s;
}

.burger-menu-overlay.active {
    display: flex;
    align-items: flex-end;
}

.burger-menu {
    width: 100%;
    background: var(--bg-primary);
    border-radius: 20px 20px 0 0;
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
    animation: slideUp 0.3s;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.burger-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.burger-menu-header h3 {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}

.burger-close {
    background: none;
    border: none;
    font-size: 32px;
    color: var(--text-secondary);
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.burger-close:hover {
    background: var(--bg-secondary);
}

.burger-menu-content {
    display: flex;
    flex-direction: column;
}

.burger-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-radius: 12px;
    transition: all 0.3s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.burger-item:hover {
    background: var(--bg-secondary);
}

.burger-item.danger {
    color: #e53e3e;
}

.burger-item-icon {
    flex-shrink: 0;
}

.burger-item-icon svg {
    width: 24px;
    height: 24px;
}

.burger-item-content {
    flex: 1;
}

.burger-item-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
}

.burger-item-desc {
    font-size: 13px;
    color: var(--text-secondary);
}

.burger-divider {
    height: 1px;
    background: var(--border-color);
    margin: 10px 0;
}

/* ПЕРЕКЛЮЧАТЕЛЬ ТЕМЫ */
.theme-switcher {
    display: flex;
    gap: 8px;
    margin-top: 10px;
}

.theme-btn {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    background: var(--bg-secondary);
    color: var(--text-primary);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.theme-btn:hover {
    background: var(--bg-tertiary);
}

.theme-btn.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: #667eea;
}
</style>

<script>
// Открытие бургер меню
document.getElementById('burgerMenuBtn')?.addEventListener('click', function() {
    document.getElementById('burgerMenuOverlay').classList.add('active');
});

// Закрытие бургер меню
function closeBurgerMenu() {
    document.getElementById('burgerMenuOverlay').classList.remove('active');
}

// Закрытие по клику на overlay
document.getElementById('burgerMenuOverlay')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeBurgerMenu();
    }
});

// Переключение темы из бургер меню
document.querySelectorAll('.theme-btn').forEach(btn => {
    const currentTheme = localStorage.getItem('aqum_theme') || 'auto';
    if (btn.dataset.theme === currentTheme) {
        btn.classList.add('active');
    }
    
    btn.addEventListener('click', function() {
        const theme = this.dataset.theme;
        localStorage.setItem('aqum_theme', theme);
        
        // Применяем тему
        if (window.applyTheme) {
            window.applyTheme(theme);
        } else {
            window.toggleTheme();
        }
        
        // Обновляем активную кнопку
        document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>
