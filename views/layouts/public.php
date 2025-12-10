<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'Lukrato' ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS global da landing -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site/landing.css">
</head>

<body>

    <?php
    // Header da landing (vamos caprichar depois)
    include __DIR__ . '/../site/partials/header.php';
    ?>

    <main>
        <?= $content ?? '' ?>
    </main>

    <?php
    // Footer da landing
    include __DIR__ . '/../site/partials/footer.php';
    ?>

    <!-- JS da landing -->
    <script src="<?= BASE_URL ?>/assets/js/site/landing.js"></script>
</body>

</html>