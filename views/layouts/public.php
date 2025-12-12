<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'Lukrato' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS global da landing -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site/landing-base.css">

    <!-- CSS específicos da página -->
    <?php if (!empty($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site/<?= $css ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>

</head>

<body>

    <?php include __DIR__ . '/../site/partials/header.php'; ?>

    <main>
        <?= $content ?? '' ?>
    </main>

    <?php include __DIR__ . '/../site/partials/footer.php'; ?>

    <!-- JS global -->
    <script src="<?= BASE_URL ?>/assets/js/site/landing-base.js"></script>

    <!-- JS específicos da página -->
    <?php if (!empty($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?= BASE_URL ?>/assets/js/site/<?= $js ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>

</html>