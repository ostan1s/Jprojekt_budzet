/**
 * Kasyno: koło ocen, blackjack, ruletka, wyścig chartów, zapis do budżetu (POST).
 */
(function () {
    'use strict';

    var MAX_STAKE = typeof window.CASINO_MAX_STAKE === 'number' ? window.CASINO_MAX_STAKE : 10000;

    function parseStake(str) {
        var t = String(str || '').trim().replace(/\s/g, '').replace(',', '.');
        var n = parseFloat(t);
        if (!isFinite(n) || n <= 0 || n > MAX_STAKE) {
            return null;
        }
        return Math.round(n * 100) / 100;
    }

    function submitSettle(game, kind, amount, note) {
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = 'index.php';
        [
            ['action', 'casino_settle'],
            ['game', game],
            ['kind', kind],
            ['amount', String(amount)],
            ['note', note || '']
        ].forEach(function (pair) {
            var i = document.createElement('input');
            i.type = 'hidden';
            i.name = pair[0];
            i.value = pair[1];
            f.appendChild(i);
        });
        document.body.appendChild(f);
        f.submit();
    }

    /* --- Koło ocen (zawsze 5) --- */
    function initGradeWheel() {
        var wheel = document.getElementById('grade-wheel');
        var btn = document.getElementById('grade-wheel-btn');
        var trap = document.getElementById('grade-trap');
        if (!wheel || !btn || !trap) {
            return;
        }

        var colors = ['#dc2626', '#2563eb', '#16a34a', '#ca8a04', '#7c3aed', '#0891b2'];
        var stops = colors
            .map(function (c, i) {
                return c + ' ' + (i / 6) * 100 + '% ' + ((i + 1) / 6) * 100 + '%';
            })
            .join(', ');
        wheel.style.background = 'conic-gradient(' + stops + ')';

        for (var i = 0; i < 6; i++) {
            var lab = document.createElement('div');
            lab.className = 'grade-wheel-label';
            lab.textContent = String(i + 1);
            lab.style.transform =
                'rotate(' + i * 60 + 'deg) translateY(-118px) rotate(' + -i * 60 + 'deg)';
            wheel.appendChild(lab);
        }

        var rot = 0;
        var spinning = false;

        function onTrapKey(e) {
            if (!trap.hidden) {
                e.preventDefault();
                e.stopPropagation();
            }
        }

        function showTrap() {
            trap.hidden = false;
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', onTrapKey, true);
        }

        btn.addEventListener('click', function () {
            if (spinning) {
                return;
            }
            spinning = true;
            btn.disabled = true;
            var full = 360 * 8;
            var targetMod = 90;
            var delta = (targetMod - (rot % 360) + 360) % 360;
            rot += full + delta;
            wheel.style.transform = 'rotate(' + rot + 'deg)';

            function onEnd() {
                wheel.removeEventListener('transitionend', onEnd);
                spinning = false;
                btn.disabled = false;
                showTrap();
            }

            wheel.addEventListener('transitionend', onEnd);
        });
    }

    /* --- Blackjack --- */
    var SUITS = [
        { s: '\u2660', c: 'bj-card--black' },
        { s: '\u2665', c: 'bj-card--red' },
        { s: '\u2666', c: 'bj-card--red' },
        { s: '\u2663', c: 'bj-card--black' }
    ];
    var RANKS = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

    function buildDeck() {
        var d = [];
        for (var si = 0; si < 4; si++) {
            for (var ri = 0; ri < 13; ri++) {
                d.push({ suit: si, rank: ri });
            }
        }
        return d;
    }

    function shuffle(arr) {
        for (var i = arr.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = arr[i];
            arr[i] = arr[j];
            arr[j] = t;
        }
        return arr;
    }

    function handValue(cards) {
        var sum = 0;
        var aces = 0;
        for (var i = 0; i < cards.length; i++) {
            var r = cards[i].rank;
            if (r === 0) {
                aces++;
                sum += 11;
            } else if (r >= 10) {
                sum += 10;
            } else {
                sum += r + 1;
            }
        }
        while (sum > 21 && aces > 0) {
            sum -= 10;
            aces--;
        }
        return sum;
    }

    function renderCard(container, card, hidden) {
        var el = document.createElement('div');
        el.className = 'bj-card ' + (hidden ? 'bj-card--hidden' : SUITS[card.suit].c);
        if (!hidden) {
            el.textContent = RANKS[card.rank] + SUITS[card.suit].s;
        }
        container.appendChild(el);
    }

    function initBlackjack() {
        var stakeEl = document.getElementById('bj-stake');
        var dealBtn = document.getElementById('bj-deal');
        var hitBtn = document.getElementById('bj-hit');
        var standBtn = document.getElementById('bj-stand');
        var dealerCards = document.getElementById('bj-dealer-cards');
        var playerCards = document.getElementById('bj-player-cards');
        var dealerSum = document.getElementById('bj-dealer-sum');
        var playerSum = document.getElementById('bj-player-sum');
        var msg = document.getElementById('bj-msg');
        if (!dealBtn || !stakeEl) {
            return;
        }

        var deck = [];
        var player = [];
        var dealer = [];
        var dealerHide = true;
        var roundActive = false;
        var roundStake = 0;

        function clearHands() {
            dealerCards.innerHTML = '';
            playerCards.innerHTML = '';
            dealerSum.textContent = '';
            playerSum.textContent = '';
        }

        function refreshSums() {
            playerSum.textContent = 'Suma: ' + handValue(player);
            if (dealerHide && dealer.length > 0) {
                dealerSum.textContent = 'Suma: ?';
            } else {
                dealerSum.textContent = 'Suma: ' + handValue(dealer);
            }
        }

        function endRound(text, netStake, won, record) {
            if (typeof record === 'undefined') {
                record = true;
            }
            roundActive = false;
            hitBtn.disabled = true;
            standBtn.disabled = true;
            msg.textContent = text;
            if (record === false) {
                return;
            }
            if (netStake !== null && netStake > 0) {
                if (won) {
                    submitSettle('blackjack', 'win', netStake, text);
                } else {
                    submitSettle('blackjack', 'loss', netStake, text);
                }
            }
        }

        dealBtn.addEventListener('click', function () {
            var stake = parseStake(stakeEl.value);
            if (stake === null) {
                msg.textContent = 'Podaj stawkę od 0 do ' + MAX_STAKE + ' zł.';
                return;
            }
            roundStake = stake;
            deck = shuffle(buildDeck());
            player = [deck.pop(), deck.pop()];
            dealer = [deck.pop(), deck.pop()];
            dealerHide = true;
            roundActive = true;
            clearHands();
            renderCard(playerCards, player[0], false);
            renderCard(playerCards, player[1], false);
            renderCard(dealerCards, dealer[0], false);
            renderCard(dealerCards, dealer[1], true);
            refreshSums();
            hitBtn.disabled = false;
            standBtn.disabled = false;
            msg.textContent = 'Gra w toku. Dobierz lub zatrzymaj się.';

            if (handValue(player) === 21 && player.length === 2) {
                dealerHide = false;
                clearHands();
                renderCard(playerCards, player[0], false);
                renderCard(playerCards, player[1], false);
                renderCard(dealerCards, dealer[0], false);
                renderCard(dealerCards, dealer[1], false);
                refreshSums();
                var dv0 = handValue(dealer);
                if (dv0 === 21 && dealer.length === 2) {
                    endRound('Remis — oba blackjack.', null, false, false);
                } else {
                    endRound('Blackjack! Wygrywasz.', roundStake * 1.5, true, true);
                }
            }
        });

        hitBtn.addEventListener('click', function () {
            if (!roundActive || !deck.length) {
                return;
            }
            player.push(deck.pop());
            renderCard(playerCards, player[player.length - 1], false);
            refreshSums();
            if (handValue(player) > 21) {
                dealerHide = false;
                clearHands();
                player.forEach(function (c) {
                    renderCard(playerCards, c, false);
                });
                dealer.forEach(function (c) {
                    renderCard(dealerCards, c, false);
                });
                refreshSums();
                endRound('Przekroczenie 21 — przegrana.', roundStake, false, true);
            }
        });

        standBtn.addEventListener('click', function () {
            if (!roundActive) {
                return;
            }
            dealerHide = false;
            clearHands();
            player.forEach(function (c) {
                renderCard(playerCards, c, false);
            });
            dealer.forEach(function (c) {
                renderCard(dealerCards, c, false);
            });
            while (handValue(dealer) < 17 && deck.length) {
                dealer.push(deck.pop());
                renderCard(dealerCards, dealer[dealer.length - 1], false);
            }
            refreshSums();
            var pv = handValue(player);
            var dv = handValue(dealer);
            if (dv > 21) {
                endRound('Krupier przekroczył — wygrana!', roundStake, true, true);
            } else if (pv > dv) {
                endRound('Wygrywasz rundę.', roundStake, true, true);
            } else if (pv < dv) {
                endRound('Przegrywasz rundę.', roundStake, false, true);
            } else {
                roundActive = false;
                hitBtn.disabled = true;
                standBtn.disabled = true;
                msg.textContent = 'Remis — bez zmiany w budżecie.';
            }
        });
    }

    /* --- Ruletka --- */
    var RL_RED = [
        1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36
    ];

    function rlColor(n) {
        if (n === 0) {
            return 'green';
        }
        return RL_RED.indexOf(n) >= 0 ? 'red' : 'black';
    }

    function initRoulette() {
        var stakeEl = document.getElementById('rl-stake');
        var spinBtn = document.getElementById('rl-spin');
        var msg = document.getElementById('rl-msg');
        var resultEl = document.getElementById('rl-result');
        var wheel = document.getElementById('rl-wheel-outer');
        var radios = document.querySelectorAll('input[name="rl-bet"]');
        var numEl = document.getElementById('rl-number');
        if (!spinBtn || !stakeEl || !wheel) {
            return;
        }

        var rot = 0;
        var rlBusy = false;

        function syncBetUi() {
            var v = document.querySelector('input[name="rl-bet"]:checked');
            var mode = v ? v.value : 'red';
            if (numEl) {
                numEl.disabled = mode !== 'number';
            }
        }

        for (var i = 0; i < radios.length; i++) {
            radios[i].addEventListener('change', syncBetUi);
        }
        syncBetUi();

        spinBtn.addEventListener('click', function () {
            if (rlBusy) {
                return;
            }
            var stake = parseStake(stakeEl.value);
            if (stake === null) {
                msg.textContent = 'Podaj poprawną stawkę.';
                return;
            }
            var mode = document.querySelector('input[name="rl-bet"]:checked').value;
            var num = mode === 'number' ? parseInt(numEl.value, 10) : null;
            if (mode === 'number' && (isNaN(num) || num < 0 || num > 36)) {
                msg.textContent = 'Wybierz numer 0–36.';
                return;
            }
            rlBusy = true;
            spinBtn.disabled = true;
            var n = Math.floor(Math.random() * 37);
            var col = rlColor(n);
            rot += 360 * 6 + Math.floor(Math.random() * 360);
            wheel.style.transform = 'rotate(' + rot + 'deg)';

            setTimeout(function () {
                var win = false;
                var profit = 0;
                if (mode === 'red') {
                    win = col === 'red';
                    profit = win ? stake : 0;
                } else if (mode === 'black') {
                    win = col === 'black';
                    profit = win ? stake : 0;
                } else if (mode === 'number') {
                    win = n === num;
                    profit = win ? stake * 35 : 0;
                }
                var colPl = col === 'red' ? 'czerwone' : col === 'black' ? 'czarne' : 'zielone (0)';
                resultEl.textContent = 'Wynik: ' + n + ' (' + colPl + ')';
                if (win) {
                    msg.textContent = 'Wygrana!';
                    submitSettle('roulette', 'win', profit, 'Wynik ' + n);
                } else {
                    msg.textContent = 'Przegrana.';
                    submitSettle('roulette', 'loss', stake, 'Wynik ' + n);
                }
                rlBusy = false;
                spinBtn.disabled = false;
            }, 3600);
        });
    }

    /* --- Wyścig chartów --- */
    var DOG_COLORS = ['#e11d48', '#2563eb', '#16a34a', '#ca8a04', '#9333ea', '#0891b2'];
    var NAME_POOL = [
        'Bąbel',
        'Szczyl',
        'Fafik',
        'Kieł',
        'Piorun',
        'Orbita',
        'Zgredek',
        'Luna',
        'Borys',
        'Ares',
        'Kluska',
        'Nugget'
    ];

    function initRace() {
        var track = document.getElementById('race-track');
        var meta = document.getElementById('race-meta');
        var stakeEl = document.getElementById('race-stake');
        var pickEl = document.getElementById('race-pick');
        var newBtn = document.getElementById('race-new-round');
        var startBtn = document.getElementById('race-start');
        var msg = document.getElementById('race-msg');
        if (!track || !newBtn || !startBtn) {
            return;
        }

        var dogs = [];
        var racing = false;

        function pickWinner(weights) {
            var sum = weights.reduce(function (a, b) {
                return a + b;
            }, 0);
            var r = Math.random() * sum;
            var acc = 0;
            for (var i = 0; i < weights.length; i++) {
                acc += weights[i];
                if (r <= acc) {
                    return i;
                }
            }
            return weights.length - 1;
        }

        function newRound() {
            racing = false;
            msg.textContent = '';
            var used = NAME_POOL.slice().sort(function () {
                return Math.random() - 0.5;
            });
            var names = [];
            var yesIdx = Math.floor(Math.random() * 6);
            var ni = 0;
            for (var i = 0; i < 6; i++) {
                if (i === yesIdx) {
                    names.push('Yes Yes');
                } else {
                    names.push(used[ni++] || 'Pies ' + (i + 1));
                }
            }
            dogs = [];
            for (var j = 0; j < 6; j++) {
                var odds = Math.round((1.5 + Math.random() * 6.5) * 10) / 10;
                dogs.push({
                    name: names[j],
                    color: DOG_COLORS[j],
                    odds: odds
                });
            }
            pickEl.innerHTML = '';
            for (var k = 0; k < 6; k++) {
                var opt = document.createElement('option');
                opt.value = String(k);
                opt.textContent = dogs[k].name + ' (' + dogs[k].odds + 'x)';
                pickEl.appendChild(opt);
            }
            pickEl.disabled = false;
            startBtn.disabled = false;

            var lanesHtml = '<div class="race-finish" aria-hidden="true"></div>';
            for (var l = 0; l < 6; l++) {
                lanesHtml +=
                    '<div class="race-lane" data-idx="' +
                    l +
                    '">' +
                    '<div class="race-dog-info"><div class="race-dog-name" style="color:' +
                    dogs[l].color +
                    '">' +
                    dogs[l].name +
                    '</div><div class="race-dog-odds">Kurs: ' +
                    dogs[l].odds +
                    'x</div></div>' +
                    '<div class="race-lane-bar"><div class="race-dog" id="dog-' +
                    l +
                    '" style="background:' +
                    dogs[l].color +
                    '"></div></div></div>';
            }
            track.innerHTML = lanesHtml;

            meta.innerHTML =
                dogs
                    .map(function (d, idx) {
                        return idx + 1 + '. ' + d.name + ' — kurs ' + d.odds + 'x';
                    })
                    .join(' · ');
        }

        newBtn.addEventListener('click', newRound);

        startBtn.addEventListener('click', function () {
            if (racing) {
                return;
            }
            var stake = parseStake(stakeEl.value);
            if (stake === null) {
                msg.textContent = 'Podaj stawkę.';
                return;
            }
            var pick = parseInt(pickEl.value, 10);
            if (isNaN(pick) || pick < 0 || pick > 5) {
                msg.textContent = 'Wybierz psa.';
                return;
            }
            var weights = dogs.map(function (d) {
                return 1 / d.odds;
            });
            var winner = pickWinner(weights);
            racing = true;
            startBtn.disabled = true;
            msg.textContent = 'Wyścig trwa…';

            var els = [];
            for (var i = 0; i < 6; i++) {
                els.push(document.getElementById('dog-' + i));
            }

            var start = performance.now();
            var dur = 4500;

            function frame(now) {
                var t = Math.min(1, (now - start) / dur);
                var ease = 1 - Math.pow(1 - t, 3);
                for (var j = 0; j < 6; j++) {
                    var lag = 0.82 + (j % 4) * 0.04;
                    if (j === winner) {
                        lag = 1;
                    }
                    var pos = ease * 100 * lag;
                    if (j !== winner) {
                        pos = Math.min(pos, 94);
                    } else {
                        pos = Math.min(100, pos);
                    }
                    if (els[j]) {
                        els[j].style.left = 'calc(' + pos + '% - 36px)';
                    }
                }
                if (t < 1) {
                    requestAnimationFrame(frame);
                } else {
                    for (var x = 0; x < 6; x++) {
                        if (els[x]) {
                            els[x].style.left =
                                x === winner ? 'calc(100% - 42px)' : 'calc(93% - 36px)';
                        }
                    }
                    racing = false;
                    startBtn.disabled = false;
                    var w = dogs[winner];
                    msg.textContent = 'Zwycięzca: ' + w.name + '.';
                    if (pick === winner) {
                        var profit = Math.round(stake * (w.odds - 1) * 100) / 100;
                        submitSettle('race', 'win', profit, 'Wygrał ' + w.name);
                    } else {
                        submitSettle('race', 'loss', stake, 'Wygrał ' + w.name);
                    }
                }
            }

            requestAnimationFrame(frame);
        });

        newRound();
    }

    function init() {
        initGradeWheel();
        initBlackjack();
        initRoulette();
        initRace();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
