<?php

declare(strict_types=1);

$actor = current_user();
$editId = isset($_GET['edit']) ? (string) $_GET['edit'] : '';
$editRow = null;
if ($editId !== '') {
    $cand = tx_find($editId);
    if (
        $cand !== null
        && ($cand['type'] ?? '') === 'income'
        && ($cand['created_by'] ?? '') === $actor
    ) {
        $editRow = $cand;
    }
}

$items = tx_filter('income', null, null, null);

?>
<div class="grid grid--2 section-gap">
    <section class="card">
        <h3 class="card__title"><?= $editRow ? 'Edytuj przychód' : 'Dodaj przychód' ?></h3>
        <?php if ($editRow) : ?>
            <form class="form" method="post" action="<?= e(app_url('incomes')) ?>">
                <input type="hidden" name="action" value="tx_update">
                <input type="hidden" name="id" value="<?= e((string) ($editRow['id'] ?? '')) ?>">
                <input type="hidden" name="type" value="income">
                <input type="hidden" name="redirect_page" value="incomes">
                <input type="hidden" name="category" value="<?= e(INCOME_CATEGORY_VALUE) ?>">
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
                <div class="field">
                    <label class="field__label" for="description">Opis (opcjonalnie)</label>
                    <textarea class="field__input" id="description" name="description" rows="3"><?= e((string) ($editRow['description'] ?? '')) ?></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Zapisz</button>
                    <a class="btn btn--ghost" href="<?= e(app_url('incomes')) ?>">Anuluj</a>
                </div>
            </form>
        <?php else : ?>
            <form class="form" method="post" action="<?= e(app_url('incomes')) ?>">
                <input type="hidden" name="action" value="tx_create">
                <input type="hidden" name="type" value="income">
                <input type="hidden" name="category" value="<?= e(INCOME_CATEGORY_VALUE) ?>">
                <div class="field">
                    <label class="field__label" for="title">Tytuł</label>
                    <input class="field__input" id="title" name="title" required>
                </div>
                <div class="field">
                    <label class="field__label" for="amount">Kwota (zł)</label>
                    <input class="field__input" id="amount" name="amount" inputmode="decimal" required placeholder="np. 3500 lub 3500,50">
                </div>
                <div class="field">
                    <label class="field__label" for="date">Data</label>
                    <input class="field__input" id="date" name="date" type="date" required value="<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>">
                </div>
                <div class="field">
                    <label class="field__label" for="description">Opis (opcjonalnie)</label>
                    <textarea class="field__input" id="description" name="description" rows="3"></textarea>
                </div>
                <button class="btn btn--primary" type="submit">Dodaj przychód</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="card">
        <h3 class="card__title">Lista przychodów</h3>
        <?php if ($items === []) : ?>
            <p class="muted">Brak przychodów.</p>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tytuł</th>
                            <th>Kwota</th>
                            <th>Data</th>
                            <th>Kto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $row) : ?>
                            <?php
                            $own = ($row['created_by'] ?? '') === $actor;
                            ?>
                            <tr>
                                <td><?= e((string) ($row['title'] ?? '')) ?></td>
                                <td class="amount--income"><?= e(format_money((float) ($row['amount'] ?? 0))) ?></td>
                                <td><?= e(format_date_display((string) ($row['date'] ?? ''))) ?></td>
                                <td><?= e((string) ($row['created_by'] ?? '')) ?></td>
                                <td class="table-actions">
                                    <?php if ($own) : ?>
                                        <a class="btn btn--small btn--ghost" href="<?= e(app_url('incomes', ['edit' => (string) ($row['id'] ?? '')])) ?>">Edytuj</a>
                                        <form class="inline-form" method="post" action="<?= e(app_url('incomes')) ?>" onsubmit="return confirm('Usunąć wpis?');">
                                            <input type="hidden" name="action" value="tx_delete">
                                            <input type="hidden" name="id" value="<?= e((string) ($row['id'] ?? '')) ?>">
                                            <input type="hidden" name="redirect_page" value="incomes">
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
</div>
