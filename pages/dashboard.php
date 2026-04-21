<?php

declare(strict_types=1);

require_once BASE_PATH . '/includes/loan.php';
$loanDash = loan_get();

$totalIncome = tx_sum_by_type('income');
$totalExpense = tx_sum_by_type('expense');
$balance = round($totalIncome - $totalExpense, 2);
$recent = tx_recent(RECENT_TRANSACTIONS_LIMIT);
$recentCount = count($recent);
$lastIncomes = tx_last_of_type('income', DASHBOARD_PREVIEW_LIMIT);
$lastExpenses = tx_last_of_type('expense', DASHBOARD_PREVIEW_LIMIT);

$now = new DateTimeImmutable('today');
$chartYear = (int) $now->format('Y');
$monthNum = (int) $now->format('n');
$maxIncomeMonth = tx_max_in_month('income', $chartYear, $monthNum);
$maxExpenseMonth = tx_max_in_month('expense', $chartYear, $monthNum);

$labels = month_labels_pl();
ksort($labels);
$labelList = array_values($labels);
$tot = tx_monthly_totals_for_year($chartYear);
$incomeSeries = [];
$expenseSeries = [];
for ($m = 1; $m <= 12; $m++) {
    $incomeSeries[] = $tot['income'][$m];
    $expenseSeries[] = $tot['expense'][$m];
}
$chartDataJson = json_encode([
    'labels'  => $labelList,
    'income'  => $incomeSeries,
    'expense' => $expenseSeries,
    'year'    => $chartYear,
], JSON_UNESCAPED_UNICODE);

?>
<?php if (!empty($loanDash['active'])) :
    $repayTotal = (float) ($loanDash['repay_total'] ?? round(LOAN_PRINCIPAL * (1 + LOAN_INTEREST_RATE), 2));
    ?>
<section class="card section-gap loan-banner">
    <h3 class="card__title">Chwilówka do spłaty</h3>
    <p class="loan-banner__text">
        Pamiętaj: musisz spłacić chwilówkę w kwocie <strong><?= e(format_money($repayTotal)) ?></strong>
        (kapitał <?= e(format_money((float) LOAN_PRINCIPAL)) ?> + odsetki <?= (int) (LOAN_INTEREST_RATE * 100) ?>%).
    </p>
</section>
<?php endif; ?>

<section class="grid grid--stats">
    <article class="card stat-card stat-card--income">
        <div class="stat-card__label">Suma przychodów</div>
        <div class="stat-card__value"><?= e(format_money($totalIncome)) ?></div>
    </article>
    <article class="card stat-card stat-card--expense">
        <div class="stat-card__label">Suma wydatków</div>
        <div class="stat-card__value"><?= e(format_money($totalExpense)) ?></div>
    </article>
    <article class="card stat-card stat-card--balance">
        <div class="stat-card__label">Bilans</div>
        <div class="stat-card__value"><?= e(format_money($balance)) ?></div>
    </article>
    <article class="card stat-card">
        <div class="stat-card__label">Ostatnie transakcje (liczba)</div>
        <div class="stat-card__value stat-card__value--sm"><?= e((string) $recentCount) ?></div>
        <p class="stat-card__hint">Pokazujemy do <?= (int) RECENT_TRANSACTIONS_LIMIT ?> najnowszych wpisów.</p>
    </article>
</section>

<section class="grid grid--2 section-gap">
    <article class="card">
        <h3 class="card__title">Największy przychód w tym miesiącu</h3>
        <?php if ($maxIncomeMonth === null) : ?>
            <p class="muted">Brak przychodów w bieżącym miesiącu.</p>
        <?php else : ?>
            <div class="highlight highlight--income"><?= e(format_money($maxIncomeMonth['amount'])) ?></div>
            <p class="card__meta"><?= e($maxIncomeMonth['title']) ?></p>
        <?php endif; ?>
    </article>
    <article class="card">
        <h3 class="card__title">Największy wydatek w tym miesiącu</h3>
        <?php if ($maxExpenseMonth === null) : ?>
            <p class="muted">Brak wydatków w bieżącym miesiącu.</p>
        <?php else : ?>
            <div class="highlight highlight--expense"><?= e(format_money($maxExpenseMonth['amount'])) ?></div>
            <p class="card__meta"><?= e($maxExpenseMonth['title']) ?></p>
        <?php endif; ?>
    </article>
</section>

<section class="card section-gap">
    <div class="card__head">
        <h3 class="card__title">Przychody i wydatki w <?= e((string) $chartYear) ?> r.</h3>
        <p class="card__sub">Podsumowanie miesięczne (styczeń–grudzień)</p>
    </div>
    <div class="chart-wrap">
        <canvas id="budget-chart" height="120" aria-label="Wykres przychodów i wydatków"></canvas>
    </div>
</section>

<section class="grid grid--2 section-gap">
    <article class="card">
        <h3 class="card__title">Ostatnie przychody</h3>
        <?php if ($lastIncomes === []) : ?>
            <p class="muted">Brak wpisów.</p>
        <?php else : ?>
            <ul class="mini-list">
                <?php foreach ($lastIncomes as $row) : ?>
                    <li class="mini-list__item">
                        <span class="mini-list__title"><?= e((string) ($row['title'] ?? '')) ?></span>
                        <span class="mini-list__amount mini-list__amount--income"><?= e(format_money((float) ($row['amount'] ?? 0))) ?></span>
                        <span class="mini-list__date"><?= e(format_date_display((string) ($row['date'] ?? ''))) ?> · <?= e((string) ($row['created_by'] ?? '')) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="card">
        <h3 class="card__title">Ostatnie wydatki</h3>
        <?php if ($lastExpenses === []) : ?>
            <p class="muted">Brak wpisów.</p>
        <?php else : ?>
            <ul class="mini-list">
                <?php foreach ($lastExpenses as $row) : ?>
                    <li class="mini-list__item">
                        <span class="mini-list__title"><?= e((string) ($row['title'] ?? '')) ?></span>
                        <span class="mini-list__amount mini-list__amount--expense"><?= e(format_money((float) ($row['amount'] ?? 0))) ?></span>
                        <span class="mini-list__date"><?= e(format_date_display((string) ($row['date'] ?? ''))) ?> · <?= e(category_label((string) ($row['category'] ?? ''))) ?> · <?= e((string) ($row['created_by'] ?? '')) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<section class="card section-gap">
    <h3 class="card__title">Ostatnie transakcje</h3>
    <?php if ($recent === []) : ?>
        <p class="muted">Brak transakcji — dodaj przychód lub wydatek.</p>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Typ</th>
                        <th>Tytuł</th>
                        <th>Kwota</th>
                        <th>Data</th>
                        <th>Kategoria</th>
                        <th>Kto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $row) : ?>
                        <?php
                        $t = (string) ($row['type'] ?? '');
                        $isIn = $t === 'income';
                        ?>
                        <tr>
                            <td><span class="pill<?= $isIn ? ' pill--income' : ' pill--expense' ?>"><?= e(type_label($t)) ?></span></td>
                            <td><?= e((string) ($row['title'] ?? '')) ?></td>
                            <td class="<?= $isIn ? 'amount--income' : 'amount--expense' ?>"><?= e(format_money((float) ($row['amount'] ?? 0))) ?></td>
                            <td><?= e(format_date_display((string) ($row['date'] ?? ''))) ?></td>
                            <td><?= $isIn ? '—' : e(category_label((string) ($row['category'] ?? ''))) ?></td>
                            <td><?= e((string) ($row['created_by'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
