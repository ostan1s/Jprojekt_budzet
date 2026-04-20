<?php

declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $v = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return is_string($v) ? $v : null;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function format_money(float $amount): string
{
    return number_format($amount, 2, ',', ' ') . ' zł';
}

function format_date_display(string $ymd): string
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);

    return $dt ? $dt->format('d.m.Y') : $ymd;
}

/** Normalizacja kwoty z formularza (przecinek → kropka). */
function parse_amount(string $raw): ?float
{
    $t = trim(str_replace([' ', "\xc2\xa0"], '', $raw));
    $t = str_replace(',', '.', $t);
    if ($t === '' || !is_numeric($t)) {
        return null;
    }
    $f = (float) $t;

    return $f > 0 ? round($f, 2) : null;
}

/** Etykiety miesięcy do wykresu (1–12). */
function month_labels_pl(): array
{
    return [
        1  => 'Sty',
        2  => 'Lut',
        3  => 'Mar',
        4  => 'Kwi',
        5  => 'Maj',
        6  => 'Cze',
        7  => 'Lip',
        8  => 'Sie',
        9  => 'Wrz',
        10 => 'Paź',
        11 => 'Lis',
        12 => 'Gru',
    ];
}

function app_url(string $page, array $query = []): string
{
    $q = array_merge(['page' => $page], $query);
    $qs = http_build_query($q);

    return 'index.php?' . $qs;
}

/** Etykieta kategorii wydatku do wyświetlania. */
function category_label(string $cat): string
{
    $map = [
        'jedzenie'  => 'Jedzenie',
        'rachunki'  => 'Rachunki',
        'transport' => 'Transport',
        'szkoła'    => 'Szkoła',
        'rozrywka'  => 'Rozrywka',
        'zdrowie'   => 'Zdrowie',
        'inne'      => 'Inne',
    ];

    return $map[$cat] ?? $cat;
}

function type_label(string $type): string
{
    return $type === 'income' ? 'Przychód' : ($type === 'expense' ? 'Wydatek' : $type);
}
