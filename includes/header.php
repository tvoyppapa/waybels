<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'AQUM - Экосистема для онлайн-репетиторов' ?></title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/style/main.css">
    <?php if (isset($additional_css)): ?>
        <?php foreach ((array)$additional_css as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Meta -->
    <meta name="description" content="AQUM - Платформа для онлайн-репетиторов. Мессенджер, соцсеть и онлайн-школа в одном месте.">
    <meta name="theme-color" content="#7F2CDF">
    
    <!-- Favicon (можно добавить позже) -->
    <!-- <link rel="icon" type="image/svg+xml" href="/img/favicon.svg"> -->
</head>
<body>
