<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/includes/bootstrap.php';
require BASE_PATH . '/config/app.php';
require BASE_PATH . '/includes/helpers.php';
require BASE_PATH . '/includes/auth.php';
require BASE_PATH . '/includes/json_store.php';
require BASE_PATH . '/includes/transactions.php';
require BASE_PATH . '/includes/loan.php';

$page = isset($_GET['page']) ? (string) $_GET['page'] : 'dashboard';

if ($page === 'logout') {
    if (is_logged_in()) {
        logout();
        set_flash('success', 'Wylogowano.');
    }
    redirect(app_url('login'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'login') {
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (login($username, $password)) {
            set_flash('success', 'Zalogowano pomyślnie.');
            redirect(app_url('dashboard'));
        }
        set_flash('error', 'Nieprawidłowy login lub hasło.');
        redirect(app_url('login'));
    }

    require_login();
    $actor = current_user();
    if ($actor === null) {
        redirect(app_url('login'));
    }

    if ($action === 'tx_create') {
        $type = (string) ($_POST['type'] ?? '');
        $title = (string) ($_POST['title'] ?? '');
        $amountRaw = (string) ($_POST['amount'] ?? '');
        $date = (string) ($_POST['date'] ?? '');
        $category = (string) ($_POST['category'] ?? '');
        $description = (string) ($_POST['description'] ?? '');
        $amt = parse_amount($amountRaw);
        if ($amt === null) {
            set_flash('error', 'Podaj poprawną kwotę większą od zera.');
            $redir = $type === 'expense' ? 'expenses' : 'incomes';
            redirect(app_url($redir));
        }
        $res = tx_create($type, $title, $amt, $date, $category, $description, $actor);
        if ($res['ok']) {
            set_flash('success', 'Dodano wpis.');
        } else {
            set_flash('error', implode(' ', $res['errors']));
        }
        $redir = $type === 'expense' ? 'expenses' : 'incomes';
        redirect(app_url($redir));
    }

    if ($action === 'tx_update') {
        $id = (string) ($_POST['id'] ?? '');
        $type = (string) ($_POST['type'] ?? '');
        $title = (string) ($_POST['title'] ?? '');
        $amountRaw = (string) ($_POST['amount'] ?? '');
        $date = (string) ($_POST['date'] ?? '');
        $category = (string) ($_POST['category'] ?? '');
        $description = (string) ($_POST['description'] ?? '');
        $redirectPage = (string) ($_POST['redirect_page'] ?? 'transactions');
        if (!in_array($redirectPage, ['incomes', 'expenses', 'transactions'], true)) {
            $redirectPage = 'transactions';
        }
        $amt = parse_amount($amountRaw);
        if ($amt === null) {
            set_flash('error', 'Podaj poprawną kwotę większą od zera.');
            redirect(app_url($redirectPage));
        }
        $res = tx_update($id, $type, $title, $amt, $date, $category, $description, $actor);
        if ($res['ok']) {
            set_flash('success', 'Zapisano zmiany.');
        } else {
            set_flash('error', implode(' ', $res['errors']));
        }
        redirect(app_url($redirectPage));
    }

    if ($action === 'tx_delete') {
        $id = (string) ($_POST['id'] ?? '');
        $res = tx_delete($id, $actor);
        if ($res['ok']) {
            set_flash('success', 'Usunięto wpis.');
        } else {
            set_flash('error', implode(' ', $res['errors']));
        }
        $from = (string) ($_POST['redirect_page'] ?? 'transactions');
        if (!in_array($from, ['incomes', 'expenses', 'transactions', 'dashboard'], true)) {
            $from = 'transactions';
        }
        redirect(app_url($from));
    }

    if ($action === 'casino_settle') {
        $game = (string) ($_POST['game'] ?? '');
        $kind = (string) ($_POST['kind'] ?? '');
        $amountRaw = (string) ($_POST['amount'] ?? '');
        $note = trim((string) ($_POST['note'] ?? ''));
        $allowedGames = ['blackjack', 'roulette', 'race'];
        if (!in_array($game, $allowedGames, true) || !in_array($kind, ['win', 'loss'], true)) {
            set_flash('error', 'Nieprawidłowe dane gry.');
            redirect(app_url('casino'));
        }
        $amt = parse_amount($amountRaw);
        if ($amt === null || $amt > CASINO_MAX_STAKE) {
            set_flash(
                'error',
                'Kwota musi być dodatnia i nie większa niż ' . number_format((float) CASINO_MAX_STAKE, 0, ',', ' ') . ' zł.'
            );
            redirect(app_url('casino'));
        }
        $gameTitles = [
            'blackjack' => 'Blackjack',
            'roulette'  => 'Ruletka',
            'race'      => 'Wyścig chartów',
        ];
        $gname = $gameTitles[$game];
        if ($kind === 'win') {
            $title = 'Kasyno: ' . $gname . ' – wygrana';
            $type = 'income';
            $category = INCOME_CATEGORY_VALUE;
        } else {
            $title = 'Kasyno: ' . $gname . ' – przegrana';
            $type = 'expense';
            $category = 'kasyno';
        }
        $date = (new DateTimeImmutable('today'))->format('Y-m-d');
        $res = tx_create($type, $title, $amt, $date, $category, $note, $actor);
        if ($res['ok']) {
            set_flash('success', 'Zapisano rozliczenie w budżecie.');
        } else {
            set_flash('error', implode(' ', $res['errors']));
        }
        redirect(app_url('casino'));
    }

    if ($action === 'loan_take') {
        $res = loan_take($actor);
        if ($res['ok']) {
            set_flash('success', 'Przyznano chwilówkę. Pamiętaj o spłacie z odsetkami.');
        } else {
            set_flash('error', implode(' ', $res['errors']));
        }
        redirect(app_url('dashboard'));
    }

    set_flash('error', 'Nieznana akcja.');
    redirect(app_url('dashboard'));
}

$protected = ['dashboard', 'incomes', 'expenses', 'transactions', 'casino'];
if (in_array($page, $protected, true)) {
    require_login();
}

if ($page === 'login') {
    if (is_logged_in()) {
        redirect(app_url('dashboard'));
    }
    $pageTitle = 'Logowanie';
    $currentPage = 'login';
    $layoutMode = 'auth';
    ob_start();
    require BASE_PATH . '/pages/login.php';
    $content = ob_get_clean();
    require BASE_PATH . '/includes/layout.php';
    exit;
}

if ($page === 'dashboard') {
    $pageTitle = 'Dashboard';
    $currentPage = 'dashboard';
    $layoutMode = 'app';
    $includeChart = true;
    ob_start();
    require BASE_PATH . '/pages/dashboard.php';
    $content = ob_get_clean();
    require BASE_PATH . '/includes/layout.php';
    exit;
}

if ($page === 'incomes') {
    $pageTitle = 'Przychody';
    $currentPage = 'incomes';
    $layoutMode = 'app';
    ob_start();
    require BASE_PATH . '/pages/incomes.php';
    $content = ob_get_clean();
    require BASE_PATH . '/includes/layout.php';
    exit;
}

if ($page === 'expenses') {
    $pageTitle = 'Wydatki';
    $currentPage = 'expenses';
    $layoutMode = 'app';
    ob_start();
    require BASE_PATH . '/pages/expenses.php';
    $content = ob_get_clean();
    require BASE_PATH . '/includes/layout.php';
    exit;
}

if ($page === 'transactions') {
    $pageTitle = 'Transakcje';
    $currentPage = 'transactions';
    $layoutMode = 'app';
    ob_start();
    require BASE_PATH . '/pages/transactions.php';
    $content = ob_get_clean();
    require BASE_PATH . '/includes/layout.php';
    exit;
}

if ($page === 'casino') {
    $pageTitle = 'Kasyno';
    $currentPage = 'casino';
    $layoutMode = 'app';
    $includeCasinoAssets = true;
    ob_start();
    require BASE_PATH . '/pages/casino.php';
    $content = ob_get_clean();
    require BASE_PATH . '/includes/layout.php';
    exit;
}

http_response_code(404);
echo 'Strona nie znaleziona';
