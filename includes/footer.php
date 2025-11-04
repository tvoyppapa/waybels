
<!-- Скрипты -->
<script>
// Базовый JS для бургер-меню и темы
document.addEventListener('DOMContentLoaded', function() {
    // Переключение темы
    const currentTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    // Бургер-меню
    const burgerBtn = document.getElementById('burger-menu');
    if (burgerBtn) {
        burgerBtn.addEventListener('click', function() {
            // TODO: Открыть модальное меню
            console.log('Burger menu clicked');
        });
    }
});

// Функция переключения темы
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}
</script>

<?php if (isset($additional_js)): ?>
    <?php foreach ((array)$additional_js as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
