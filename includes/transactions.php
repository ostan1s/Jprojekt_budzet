<?php

declare(strict_types=1);

/**
 * @return list<array<string, mixed>>
 */
function tx_all(): array
{
    $data = json_load_transactions();

    return $data['transactions'];
}

/**
 * @return array<string, mixed>|null
 */
function tx_find(string $id): ?array
{
    foreach (tx_all() as $t) {
        if (($t['id'] ?? '') === $id) {
            return $t;
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $row
 */
function tx_save_all(array $transactions): bool
{
    return json_save_transactions(['transactions' => array_values($transactions)]);
}

function tx_generate_id(): string
{
    return bin2hex(random_bytes(8));
}

/**
 * @return array{ok: bool, errors: list<string>, id?: string}
 */
function tx_create(string $type, string $title, float $amount, string $dateYmd, string $category, string $description, string $createdBy): array
{
    $errors = [];
    if ($type !== 'income' && $type !== 'expense') {
        $errors[] = 'Nieprawidłowy typ transakcji.';
    }
    $title = trim($title);
    if ($title === '') {
        $errors[] = 'Tytuł jest wymagany.';
    }
    if ($amount <= 0) {
        $errors[] = 'Kwota musi być większa od zera.';
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
    if (!$dt || $dt->format('Y-m-d') !== $dateYmd) {
        $errors[] = 'Nieprawidłowa data.';
    }
    if ($type === 'expense') {
        if (!in_array($category, EXPENSE_CATEGORIES, true)) {
            $errors[] = 'Wybierz kategorię wydatku.';
        }
    } else {
        $category = INCOME_CATEGORY_VALUE;
    }
    $createdBy = trim($createdBy);
    if ($createdBy === '' || !isset(auth_users()[$createdBy])) {
        $errors[] = 'Nieprawidłowy użytkownik.';
    }
    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors];
    }

    $row = [
        'id'          => tx_generate_id(),
        'type'        => $type,
        'title'       => $title,
        'amount'      => round($amount, 2),
        'date'        => $dateYmd,
        'category'    => $category,
        'description' => trim($description),
        'created_by'  => $createdBy,
    ];

    $all = tx_all();
    $all[] = $row;
    if (!tx_save_all($all)) {
        return ['ok' => false, 'errors' => ['Nie udało się zapisać danych.']];
    }

    return ['ok' => true, 'errors' => [], 'id' => $row['id']];
}

/**
 * @return array{ok: bool, errors: list<string>}
 */
function tx_update(string $id, string $type, string $title, float $amount, string $dateYmd, string $category, string $description, string $actingUser): array
{
    $errors = [];
    $all = tx_all();
    $idx = null;
    foreach ($all as $i => $t) {
        if (($t['id'] ?? '') === $id) {
            $idx = $i;
            break;
        }
    }
    if ($idx === null) {
        return ['ok' => false, 'errors' => ['Nie znaleziono wpisu.']];
    }
    if (($all[$idx]['created_by'] ?? '') !== $actingUser) {
        return ['ok' => false, 'errors' => ['Możesz edytować tylko własne wpisy.']];
    }
    if ($type !== 'income' && $type !== 'expense') {
        $errors[] = 'Nieprawidłowy typ transakcji.';
    }
    $title = trim($title);
    if ($title === '') {
        $errors[] = 'Tytuł jest wymagany.';
    }
    if ($amount <= 0) {
        $errors[] = 'Kwota musi być większa od zera.';
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
    if (!$dt || $dt->format('Y-m-d') !== $dateYmd) {
        $errors[] = 'Nieprawidłowa data.';
    }
    if ($type === 'expense') {
        if (!in_array($category, EXPENSE_CATEGORIES, true)) {
            $errors[] = 'Wybierz kategorię wydatku.';
        }
    } else {
        $category = INCOME_CATEGORY_VALUE;
    }
    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors];
    }

    $all[$idx]['type'] = $type;
    $all[$idx]['title'] = $title;
    $all[$idx]['amount'] = round($amount, 2);
    $all[$idx]['date'] = $dateYmd;
    $all[$idx]['category'] = $category;
    $all[$idx]['description'] = trim($description);

    if (!tx_save_all($all)) {
        return ['ok' => false, 'errors' => ['Nie udało się zapisać danych.']];
    }

    return ['ok' => true, 'errors' => []];
}

/**
 * @return array{ok: bool, errors: list<string>}
 */
function tx_delete(string $id, string $actingUser): array
{
    $all = tx_all();
    $found = false;
    $next = [];
    foreach ($all as $t) {
        if (($t['id'] ?? '') === $id) {
            if (($t['created_by'] ?? '') !== $actingUser) {
                return ['ok' => false, 'errors' => ['Możesz usuwać tylko własne wpisy.']];
            }
            $found = true;

            continue;
        }
        $next[] = $t;
    }
    if (!$found) {
        return ['ok' => false, 'errors' => ['Nie znaleziono wpisu.']];
    }
    if (!tx_save_all($next)) {
        return ['ok' => false, 'errors' => ['Nie udało się zapisać danych.']];
    }

    return ['ok' => true, 'errors' => []];
}

/**
 * @param list<array<string, mixed>> $items
 * @return list<array<string, mixed>>
 */
function tx_sort_by_date_desc(array $items): array
{
    usort($items, static function (array $a, array $b): int {
        $da = $a['date'] ?? '';
        $db = $b['date'] ?? '';
        if ($da === $db) {
            return 0;
        }

        return $da < $db ? 1 : -1;
    });

    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function tx_filter(
    ?string $type,
    ?string $monthYyyyMm,
    ?string $category,
    ?string $user
): array {
    $items = tx_all();
    $out = [];
    foreach ($items as $t) {
        $tt = $t['type'] ?? '';
        if ($type !== null && $type !== '' && $type !== 'all' && $tt !== $type) {
            continue;
        }
        if ($monthYyyyMm !== null && $monthYyyyMm !== '') {
            $d = $t['date'] ?? '';
            if (strlen($d) < 7 || substr($d, 0, 7) !== $monthYyyyMm) {
                continue;
            }
        }
        if ($category !== null && $category !== '' && $category !== 'all') {
            if (($t['category'] ?? '') !== $category) {
                continue;
            }
        }
        if ($user !== null && $user !== '' && $user !== 'all') {
            if (($t['created_by'] ?? '') !== $user) {
                continue;
            }
        }
        $out[] = $t;
    }

    return tx_sort_by_date_desc($out);
}

function tx_sum_by_type(string $wantType): float
{
    $s = 0.0;
    foreach (tx_all() as $t) {
        if (($t['type'] ?? '') === $wantType) {
            $s += (float) ($t['amount'] ?? 0);
        }
    }

    return round($s, 2);
}

/**
 * Agregacja miesięczna dla roku (indeksy 1–12).
 *
 * @return array{income: array<int, float>, expense: array<int, float>}
 */
function tx_monthly_totals_for_year(int $year): array
{
    $income = array_fill(1, 12, 0.0);
    $expense = array_fill(1, 12, 0.0);
    foreach (tx_all() as $t) {
        $d = $t['date'] ?? '';
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);
        if (!$dt || (int) $dt->format('Y') !== $year) {
            continue;
        }
        $m = (int) $dt->format('n');
        $amt = (float) ($t['amount'] ?? 0);
        if (($t['type'] ?? '') === 'income') {
            $income[$m] += $amt;
        } elseif (($t['type'] ?? '') === 'expense') {
            $expense[$m] += $amt;
        }
    }
    foreach ($income as $k => $v) {
        $income[$k] = round($v, 2);
    }
    foreach ($expense as $k => $v) {
        $expense[$k] = round($v, 2);
    }

    return ['income' => $income, 'expense' => $expense];
}

/**
 * Ostatnie N transakcji (wszystkie typy), po dacie malejąco.
 *
 * @return list<array<string, mixed>>
 */
function tx_recent(int $limit): array
{
    $sorted = tx_sort_by_date_desc(tx_all());

    return array_slice($sorted, 0, max(0, $limit));
}

/**
 * @return list<array<string, mixed>>
 */
function tx_last_of_type(string $type, int $limit): array
{
    $filtered = [];
    foreach (tx_all() as $t) {
        if (($t['type'] ?? '') === $type) {
            $filtered[] = $t;
        }
    }
    $sorted = tx_sort_by_date_desc($filtered);

    return array_slice($sorted, 0, max(0, $limit));
}

/**
 * Największy przychód / wydatek w danym miesiącu kalendarzowym.
 *
 * @return array{amount: float, title: string}|null
 */
function tx_max_in_month(string $type, int $year, int $month): ?array
{
    if ($month < 1 || $month > 12) {
        return null;
    }
    $max = -1.0;
    $title = '';
    foreach (tx_all() as $t) {
        if (($t['type'] ?? '') !== $type) {
            continue;
        }
        $d = $t['date'] ?? '';
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);
        if (!$dt || (int) $dt->format('Y') !== $year || (int) $dt->format('n') !== $month) {
            continue;
        }
        $a = (float) ($t['amount'] ?? 0);
        if ($a > $max) {
            $max = $a;
            $title = (string) ($t['title'] ?? '');
        }
    }
    if ($max < 0) {
        return null;
    }

    return ['amount' => round($max, 2), 'title' => $title];
}
