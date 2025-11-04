        </div><!-- /.page-content -->
    </main><!-- /.main-content -->
    
    <!-- Нижнее меню (Mobile) -->
    <?php include __DIR__ . '/bottom_nav.php'; ?>
    
    <!-- Общие скрипты -->
    <script src="/js/dashboard.js"></script>
    <?php if (isset($page_js) && is_array($page_js)): ?>
    <?php foreach ($page_js as $js): ?>
    <script src="/js/<?= e($js) ?>"></script>
    <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
