<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    exit;
}

/** @return array{active: bool, principal?: float, repay_total?: float} */
function loan_get(): array
{
    $path = STORAGE_PATH . DIRECTORY_SEPARATOR . 'loan.json';
    if (!is_file($path)) {
        return ['active' => false];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return ['active' => false];
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['active'])) {
        return ['active' => false];
    }

    return [
        'active'      => true,
        'principal'   => isset($data['principal']) ? (float) $data['principal'] : LOAN_PRINCIPAL,
        'repay_total' => isset($data['repay_total']) ? (float) $data['repay_total'] : round(LOAN_PRINCIPAL * (1 + LOAN_INTEREST_RATE), 2),
    ];
}

/**
 * @param array{active: bool, principal?: float, repay_total?: float} $data
 */
function loan_save(array $data): bool
{
    if (!is_dir(STORAGE_PATH)) {
        if (!@mkdir(STORAGE_PATH, 0755, true) && !is_dir(STORAGE_PATH)) {
            return false;
        }
    }
    $path = STORAGE_PATH . DIRECTORY_SEPARATOR . 'loan.json';
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($payload === false) {
        return false;
    }

    return file_put_contents($path, $payload, LOCK_EX) !== false;
}

/**
 * @return array{ok: bool, errors: list<string>}
 */
function loan_take(string $actor): array
{
    if (loan_get()['active']) {
        return ['ok' => false, 'errors' => ['Masz już aktywną chwilówkę do spłaty.']];
    }
    $principal = LOAN_PRINCIPAL;
    $repay = round($principal * (1 + LOAN_INTEREST_RATE), 2);
    $date = (new DateTimeImmutable('today'))->format('Y-m-d');
    $title = 'Chwilówka';
    $desc = 'Do spłaty ' . number_format($repay, 2, ',', ' ') . ' zł (kapitał '
        . number_format($principal, 2, ',', ' ') . ' zł + odsetki ' . (int) (LOAN_INTEREST_RATE * 100) . '%).';
    $res = tx_create('income', $title, $principal, $date, INCOME_CATEGORY_VALUE, $desc, $actor);
    if (!$res['ok']) {
        return ['ok' => false, 'errors' => $res['errors']];
    }
    if (
        !loan_save([
            'active'      => true,
            'principal'   => $principal,
            'repay_total' => $repay,
        ])
    ) {
        return ['ok' => false, 'errors' => ['Nie udało się zapisać stanu pożyczki.']];
    }

    return ['ok' => true, 'errors' => []];
}
