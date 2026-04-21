<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    exit;
}

define('STORAGE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage');
define('TRANSACTIONS_FILE', STORAGE_PATH . DIRECTORY_SEPARATOR . 'transactions.json');

/** Kategorie wydatków (wartości zapisu i filtrów). */
const EXPENSE_CATEGORIES = [
    'jedzenie',
    'rachunki',
    'transport',
    'szkoła',
    'rozrywka',
    'zdrowie',
    'kasyno',
    'inne',
];

/** Maks. kwota jednego rozliczenia gry w Kasynie (zł). */
const CASINO_MAX_STAKE = 10000.0;

const INCOME_CATEGORY_VALUE = 'Przychód';

const RECENT_TRANSACTIONS_LIMIT = 10;
const DASHBOARD_PREVIEW_LIMIT = 5;
