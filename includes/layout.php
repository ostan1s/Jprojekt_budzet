<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var string $content */
/** @var string $currentPage */
/** @var string $layoutMode app|auth */
/** @var bool $includeChart */

$pageTitle = $pageTitle ?? 'Budżet Domowy';
$layoutMode = $layoutMode ?? 'app';
$includeChart = $includeChart ?? false;
$cu = current_user();

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Budżet Domowy</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <?php if ($includeChart) : ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script src="assets/js/chart-dashboard.js" defer></script>
    <?php endif; ?>
</head>
<body class="layout <?= $layoutMode === 'auth' ? 'layout--auth' : '' ?>">
<?php if ($layoutMode === 'app') : ?>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Menu główne">
            <div class="sidebar__brand">
                <span class="sidebar__logo">BD</span>
                <div>
                    <div class="sidebar__title">Budżet Domowy</div>
                    <div class="sidebar__user"><?= e($cu) ?></div>
                </div>
            </div>
            <nav class="sidebar__nav">
                <a class="nav-link<?= ($currentPage ?? '') === 'dashboard' ? ' is-active' : '' ?>" href="<?= e(app_url('dashboard')) ?>">Dashboard</a>
                <a class="nav-link<?= ($currentPage ?? '') === 'incomes' ? ' is-active' : '' ?>" href="<?= e(app_url('incomes')) ?>">Przychody</a>
                <a class="nav-link<?= ($currentPage ?? '') === 'expenses' ? ' is-active' : '' ?>" href="<?= e(app_url('expenses')) ?>">Wydatki</a>
                <a class="nav-link<?= ($currentPage ?? '') === 'transactions' ? ' is-active' : '' ?>" href="<?= e(app_url('transactions')) ?>">Transakcje</a>
                <a class="nav-link nav-link--muted" href="<?= e(app_url('logout')) ?>">Wyloguj</a>
            </nav>
        </aside>
        <div class="main-wrap">
            <header class="topbar">
                <h1 class="topbar__heading"><?= e($pageTitle) ?></h1>
            </header>
            <main class="main">
                <?php
                $flashOk = flash('success');
                $flashErr = flash('error');
                if ($flashOk) :
                    ?>
                    <div class="flash flash--ok" role="status"><?= e($flashOk) ?></div>
                <?php endif; ?>
                <?php if ($flashErr) : ?>
                    <div class="flash flash--err" role="alert"><?= e($flashErr) ?></div>
                <?php endif; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
<?php else : ?>
    <main class="auth-main">
        <?php
        $flashOk = flash('success');
        $flashErr = flash('error');
        if ($flashOk) :
            ?>
            <div class="flash flash--ok" role="status"><?= e($flashOk) ?></div>
        <?php endif; ?>
        <?php if ($flashErr) : ?>
            <div class="flash flash--err" role="alert"><?= e($flashErr) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
<?php endif; ?>
<?php if ($includeChart && isset($chartDataJson)) : ?>
<script type="application/json" id="chart-data"><?= $chartDataJson ?></script>
<?php endif; ?>
</body>
</html>
