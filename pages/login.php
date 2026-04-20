<?php

declare(strict_types=1);

?>
<div class="auth-card card">
    <div class="auth-card__header">
        <div class="auth-card__badge">BD</div>
        <h2 class="auth-card__title">Budżet Domowy</h2>
        <p class="auth-card__sub">Zaloguj się, aby zarządzać finansami rodziny.</p>
    </div>
    <form class="form" method="post" action="<?= e(app_url('login')) ?>">
        <input type="hidden" name="action" value="login">
        <div class="field">
            <label class="field__label" for="username">Login</label>
            <input class="field__input" id="username" name="username" type="text" autocomplete="username" required>
        </div>
        <div class="field">
            <label class="field__label" for="password">Hasło</label>
            <input class="field__input" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn--primary btn--block" type="submit">Zaloguj</button>
    </form>
</div>
