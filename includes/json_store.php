<?php

declare(strict_types=1);

/**
 * @return array{transactions: list<array<string, mixed>>}
 */
function json_load_transactions(): array
{
    $path = TRANSACTIONS_FILE;
    if (!is_dir(STORAGE_PATH)) {
        if (!@mkdir(STORAGE_PATH, 0755, true) && !is_dir(STORAGE_PATH)) {
            return ['transactions' => []];
        }
    }
    if (!is_file($path)) {
        $empty = ['transactions' => []];
        json_save_transactions($empty);

        return $empty;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return ['transactions' => []];
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['transactions']) || !is_array($data['transactions'])) {
        return ['transactions' => []];
    }

    return ['transactions' => array_values($data['transactions'])];
}

/**
 * @param array{transactions: list<array<string, mixed>>} $data
 */
function json_save_transactions(array $data): bool
{
    if (!is_dir(STORAGE_PATH)) {
        if (!@mkdir(STORAGE_PATH, 0755, true) && !is_dir(STORAGE_PATH)) {
            return false;
        }
    }
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($payload === false) {
        return false;
    }

    return file_put_contents(TRANSACTIONS_FILE, $payload, LOCK_EX) !== false;
}
