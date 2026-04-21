<?php

declare(strict_types=1);

$maxStake = (float) CASINO_MAX_STAKE;

?>
<section class="casino-hero card">
    <h2 class="casino-hero__title">Dzień dobry Panie Pawle</h2>
    <p class="casino-hero__lead">
        Rozrywka demonstracyjna — każda runda może zostać zapisana w budżecie jako przychód (wygrana) lub wydatek (przegrana).
        Maks. kwota rozliczenia: <?= e(number_format($maxStake, 0, ',', ' ')) ?> zł.
    </p>
</section>

<section class="card casino-section" id="section-grade-wheel">
    <h3 class="casino-section__title">Koło fortuny (oceny 1–6)</h3>
    <p class="casino-section__hint">Zakręć kołem — wylosuj ocenę dla Pana.</p>
    <div class="grade-wheel-box">
        <div class="grade-wheel-pointer" aria-hidden="true"></div>
        <div class="grade-wheel" id="grade-wheel" role="img" aria-label="Koło fortuny z ocenami 1 do 6"></div>
        <button type="button" class="btn btn--primary" id="grade-wheel-btn">Zakręć kołem</button>
    </div>
</section>

<div class="grade-trap" id="grade-trap" hidden>
    <div class="grade-trap__panel">
        <button type="button" class="grade-trap__x" id="grade-trap-fake-x" aria-hidden="true" title="">&times;</button>
        <p class="grade-trap__grade" id="grade-trap-result">5</p>
        <p class="grade-trap__msg">
            Wypadła ocena <strong>5</strong>. Proszę wpisać w dzienniku ocenę <strong>5</strong>.
        </p>
    </div>
</div>

<section class="card casino-section" id="section-blackjack">
    <h3 class="casino-section__title">Blackjack</h3>
    <div class="casino-grid casino-grid--bj">
        <div class="casino-panel">
            <label class="field">
                <span class="field__label">Stawka (zł)</span>
                <input class="field__input" type="text" id="bj-stake" inputmode="decimal" placeholder="np. 20" autocomplete="off">
            </label>
            <div class="bj-controls">
                <button type="button" class="btn btn--secondary" id="bj-deal">Rozdaj</button>
                <button type="button" class="btn btn--ghost" id="bj-hit" disabled>Dobierz</button>
                <button type="button" class="btn btn--primary" id="bj-stand" disabled>Stand</button>
            </div>
            <p class="casino-msg" id="bj-msg" role="status"></p>
        </div>
        <div class="casino-panel">
            <div class="bj-hand">
                <div class="bj-hand__label">Krupier</div>
                <div class="bj-cards" id="bj-dealer-cards"></div>
                <div class="bj-sum" id="bj-dealer-sum"></div>
            </div>
            <div class="bj-hand">
                <div class="bj-hand__label">Gracz</div>
                <div class="bj-cards" id="bj-player-cards"></div>
                <div class="bj-sum" id="bj-player-sum"></div>
            </div>
        </div>
    </div>
    <div id="bj-result-modal" class="bj-modal" hidden>
        <div class="bj-modal__backdrop"></div>
        <div class="bj-modal__panel" role="dialog" aria-labelledby="bj-modal-text">
            <p id="bj-modal-text" class="bj-modal__text"></p>
            <button type="button" class="btn btn--primary" id="bj-modal-ok">OK</button>
        </div>
    </div>
</section>

<section class="card casino-section" id="section-roulette">
    <h3 class="casino-section__title">Ruletka (europejska 0–36)</h3>
    <div class="casino-grid casino-grid--roulette">
        <div class="casino-panel">
            <label class="field">
                <span class="field__label">Stawka (zł)</span>
                <input class="field__input" type="text" id="rl-stake" inputmode="decimal" placeholder="np. 10" autocomplete="off">
            </label>
            <fieldset class="rl-bet-type">
                <legend class="field__label">Zakład</legend>
                <label class="rl-radio"><input type="radio" name="rl-bet" value="red" checked> Czerwone (1:1)</label>
                <label class="rl-radio"><input type="radio" name="rl-bet" value="black"> Czarne (1:1)</label>
                <label class="rl-radio"><input type="radio" name="rl-bet" value="number"> Pojedynczy numer (35:1)</label>
            </fieldset>
            <label class="field" id="rl-number-wrap">
                <span class="field__label">Numer (0–36)</span>
                <input class="field__input" type="number" id="rl-number" min="0" max="36" value="17" disabled>
            </label>
            <button type="button" class="btn btn--primary" id="rl-spin">Kręć ruletką</button>
            <p class="casino-msg" id="rl-msg" role="status"></p>
        </div>
        <div class="casino-panel roulette-visual">
            <div class="roulette-wheel-outer" id="rl-wheel-outer">
                <div class="roulette-ball" id="rl-ball"></div>
            </div>
            <p class="rl-result" id="rl-result"></p>
        </div>
    </div>
</section>

<section class="card casino-section" id="section-race">
    <h3 class="casino-section__title">Wyścig chartów</h3>
    <p class="casino-section__hint">Co rundę losowe imiona i kursy. Wśród psów zawsze jest <strong>Yes Yes</strong>.</p>
    <div class="race-toolbar">
        <label class="field">
            <span class="field__label">Stawka (zł)</span>
            <input class="field__input" type="text" id="race-stake" inputmode="decimal" placeholder="np. 15" autocomplete="off">
        </label>
        <label class="field">
            <span class="field__label">Obstawiasz psa</span>
            <select class="field__input" id="race-pick" disabled></select>
        </label>
        <button type="button" class="btn btn--secondary" id="race-new-round">Nowa runda (losuj psy)</button>
        <button type="button" class="btn btn--primary" id="race-start" disabled>Start wyścigu</button>
    </div>
    <div class="race-meta" id="race-meta" aria-live="polite"></div>
    <div class="race-track" id="race-track">
        <div class="race-finish" aria-hidden="true"></div>
    </div>
    <p class="casino-msg" id="race-msg" role="status"></p>
</section>

<script>
window.CASINO_MAX_STAKE = <?= json_encode($maxStake) ?>;
</script>
