/* ==========================================
   СИСТЕМА ТЕМ AQUM
   ========================================== */

(function() {
    // Получаем сохраненную тему или 'auto'
    const savedTheme = localStorage.getItem('aqum_theme') || 'auto';
    
    // Применяем тему
    window.applyTheme = function(theme) {
        const root = document.documentElement;
        
        if (theme === 'dark') {
            root.setAttribute('data-theme', 'dark');
        } else if (theme === 'light') {
            root.setAttribute('data-theme', 'light');
        } else {
            // auto - определяем по системным настройкам
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            root.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
        }
    }
    
    // Применяем сразу (до загрузки страницы)
    window.applyTheme(savedTheme);
    
    // Переключение темы
    window.toggleTheme = function() {
        const currentTheme = localStorage.getItem('aqum_theme') || 'auto';
        let newTheme;
        
        if (currentTheme === 'auto') {
            newTheme = 'light';
        } else if (currentTheme === 'light') {
            newTheme = 'dark';
        } else {
            newTheme = 'auto';
        }
        
        localStorage.setItem('aqum_theme', newTheme);
        window.applyTheme(newTheme);
        
        // Обновляем иконку
        updateThemeIcon(newTheme);
        
        // Показываем уведомление
        showThemeNotification(newTheme);
    };
    
    // Обновление иконки
    function updateThemeIcon(theme) {
        // Можно добавить изменение иконки в зависимости от темы
    }
    
    // Уведомление о смене темы
    function showThemeNotification(theme) {
        const names = {
            'auto': 'Авто',
            'light': 'Светлая',
            'dark': 'Темная'
        };
        
        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.textContent = `Тема: ${names[theme]}`;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 10);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }
    
    // Слушаем изменения системной темы
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        const theme = localStorage.getItem('aqum_theme') || 'auto';
        if (theme === 'auto') {
            window.applyTheme('auto');
        }
    });
})();
