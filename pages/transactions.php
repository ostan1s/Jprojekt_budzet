<?php

declare(strict_types=1);

$actor = current_user();

$fType = isset($_GET['type']) ? (string) $_GET['type'] : 'all';
$fMonth = isset($_GET['month']) ? (string) $_GET['month'] : '';
$fCategory = isset($_GET['category']) ? (string) $_GET['category'] : 'all';
$fUser = isset($_GET['user']) ? (string) $_GET['user'] : 'all';

$typeParam = ($fType === 'all' || $fType === '') ? null : $fType;
$monthParam = $fMonth !== '' ? $fMonth : null;
$catParam = ($fCategory === 'all' || $fCategory === '') ? null : $fCategory;
$userParam = ($fUser === 'all' || $fUser === '') ? null : $fUser;

$items = tx_filter($typeParam, $monthParam, $catParam, $userParam);

$editId = isset($_GET['edit']) ? (string) $_GET['edit'] : '';
$editRow = null;
if ($editId !== '') {
    $cand = tx_find($editId);
    if ($cand !== null && ($cand['created_by'] ?? '') === $actor) {
        $editRow = $cand;
    }
}

$usersList = array_keys(auth_users());

?>
<section class="card section-gap">
    <h3 class="card__title">Filtry</h3>
    <form class="form form--filters" method="get" action="index.php">
        <input type="hidden" name="page" value="transactions">
        <div class="field field--inline">
            <label class="field__label" for="f_type">Typ</label>
            <select class="field__input" id="f_type" name="type">
                <option value="all" <?= $fType === 'all' ? 'selected' : '' ?>>Wszystkie</option>
                <option value="income" <?= $fType === 'income' ? 'selected' : '' ?>>Przychód</option>
                <option value="expense" <?= $fType === 'expense' ? 'selected' : '' ?>>Wydatek</option>
            </select>
        </div>
        <div class="field field--inline">
            <label class="field__label" for="f_month">Miesiąc</label>
            <input class="field__input" id="f_month" name="month" type="month" value="<?= e($fMonth) ?>">
        </div>
        <div class="field field--inline">
            <label class="field__label" for="f_category">Kategoria</label>
            <select class="field__input" id="f_category" name="category">
                <option value="all">Wszystkie</option>
                <?php foreach (EXPENSE_CATEGORIES as $cat) : ?>
                    <option value="<?= e($cat) ?>" <?= $fCategory === $cat ? 'selected' : '' ?>><?= e(category_label($cat)) ?></option>
                <?php endforeach; ?>
                <option value="<?= e(INCOME_CATEGORY_VALUE) ?>" <?= $fCategory === INCOME_CATEGORY_VALUE ? 'selected' : '' ?>><?= e(INCOME_CATEGORY_VALUE) ?></option>
            </select>
        </div>
        <div class="field field--inline">
            <label class="field__label" for="f_user">Użytkownik</label>
            <select class="field__input" id="f_user" name="user">
                <option value="all">Wszyscy</option>
                <?php foreach ($usersList as $u) : ?>
                    <option value="<?= e($u) ?>" <?= $fUser === $u ? 'selected' : '' ?>><?= e($u) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn--secondary" type="submit">Zastosuj</button>
        <a class="btn btn--ghost" href="<?= e(app_url('transactions')) ?>">Wyczyść</a>
    </form>
</section>

<?php if ($editRow) : ?>
    <?php
    $et = (string) ($editRow['type'] ?? 'expense');
    ?>
    <section class="card section-gap">
        <h3 class="card__title">Edytuj transakcję</h3>
        <form class="form" method="post" action="<?= e(app_url('transactions')) ?>">
            <input type="hidden" name="action" value="tx_update">
            <input type="hidden" name="id" value="<?= e((string) ($editRow['id'] ?? '')) ?>">
            <input type="hidden" name="type" value="<?= e($et) ?>">
            <input type="hidden" name="redirect_page" value="transactions">
            <div class="field">
                <label class="field__label" for="title">Tytuł</label>
                <input class="field__input" id="title" name="title" required value="<?= e((string) ($editRow['title'] ?? '')) ?>">
            </div>
            <div class="field">
                <label class="field__label" for="amount">Kwota (zł)</label>
                <input class="field__input" id="amount" name="amount" inputmode="decimal" required value="<?= e((string) ($editRow['amount'] ?? '')) ?>">
            </div>
            <div class="field">
                <label class="field__label" for="date">Data</label>
                <input class="field__input" id="date" name="date" type="date" required value="<?= e((string) ($editRow['date'] ?? '')) ?>">
            </div>
            <?php if ($et === 'expense') : ?>
                <div class="field">
                    <label class="field__label" for="category">Kategoria</label>
                    <select class="field__input" id="category" name="category" required>
                        <?php foreach (EXPENSE_CATEGORIES as $cat) : ?>
                            <option value="<?= e($cat) ?>" <?= (($editRow['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= e(category_label($cat)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else : ?>
                <input type="hidden" name="category" value="<?= e(INCOME_CATEGORY_VALUE) ?>">
                <p class="muted">Typ: przychód — kategoria: <?= e(INCOME_CATEGORY_VALUE) ?>.</p>
            <?php endif; ?>
            <div class="field">
                <label class="field__label" for="description">Opis (opcjonalnie)</label>
                <textarea class="field__input" id="description" name="description" rows="3"><?= e((string) ($editRow['description'] ?? '')) ?></textarea>
            </div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Zapisz</button>
                <a class="btn btn--ghost" href="<?= e(app_url('transactions', array_filter([
                    'type'     => $fType !== 'all' ? $fType : null,
                    'month'    => $fMonth !== '' ? $fMonth : null,
                    'category' => $fCategory !== 'all' ? $fCategory : null,
                    'user'     => $fUser !== 'all' ? $fUser : null,
                ]))) ?>">Anuluj</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="card">
    <h3 class="card__title">Wszystkie transakcje</h3>
    <?php if ($items === []) : ?>
        <p class="muted">Brak transakcji dla wybranych filtrów.</p>
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
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row) : ?>
                        <?php
                        $tid = (string) ($row['id'] ?? '');
                        $tt = (string) ($row['type'] ?? '');
                        $isIn = $tt === 'income';
                        $own = ($row['created_by'] ?? '') === $actor;
                        $q = [
                            'edit'     => $tid,
                            'type'     => $fType !== 'all' ? $fType : null,
                            'month'    => $fMonth !== '' ? $fMonth : null,
                            'category' => $fCategory !== 'all' ? $fCategory : null,
                            'user'     => $fUser !== 'all' ? $fUser : null,
                        ];
                        $editUrl = app_url('transactions', array_filter($q));
                        ?>
                        <tr>
                            <td><span class="pill<?= $isIn ? ' pill--income' : ' pill--expense' ?>"><?= e(type_label($tt)) ?></span></td>
                            <td><?= e((string) ($row['title'] ?? '')) ?></td>
                            <td class="<?= $isIn ? 'amount--income' : 'amount--expense' ?>"><?= e(format_money((float) ($row['amount'] ?? 0))) ?></td>
                            <td><?= e(format_date_display((string) ($row['date'] ?? ''))) ?></td>
                            <td><?= $isIn ? e(INCOME_CATEGORY_VALUE) : e(category_label((string) ($row['category'] ?? ''))) ?></td>
                            <td><?= e((string) ($row['created_by'] ?? '')) ?></td>
                            <td class="table-actions">
                                <?php if ($own) : ?>
                                    <a class="btn btn--small btn--ghost" href="<?= e($editUrl) ?>">Edytuj</a>
                                    <form class="inline-form" method="post" action="<?= e(app_url('transactions')) ?>" onsubmit="return confirm('Usunąć wpis?');">
                                        <input type="hidden" name="action" value="tx_delete">
                                        <input type="hidden" name="id" value="<?= e($tid) ?>">
                                        <input type="hidden" name="redirect_page" value="transactions">
                                        <button class="btn btn--small btn--danger" type="submit">Usuń</button>
                                    </form>
                                <?php else : ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
