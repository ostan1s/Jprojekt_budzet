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
    'inne',
];

const INCOME_CATEGORY_VALUE = 'Przychód';

const RECENT_TRANSACTIONS_LIMIT = 10;
const DASHBOARD_PREVIEW_LIMIT = 5;
