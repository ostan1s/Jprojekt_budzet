/**
 * Wykres miesięczny przychodów i wydatków (Chart.js).
 */
(function () {
    function boot() {
        var dataEl = document.getElementById('chart-data');
        var canvas = document.getElementById('budget-chart');
        if (!dataEl || !canvas || typeof Chart === 'undefined') {
            return;
        }
        var data;
        try {
            data = JSON.parse(dataEl.textContent || '{}');
        } catch (e) {
            return;
        }
        var labels = data.labels || [];
        var income = data.income || [];
        var expense = data.expense || [];

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Przychody',
                        data: income,
                        backgroundColor: 'rgba(22, 163, 74, 0.72)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Wydatki',
                        data: expense,
                        backgroundColor: 'rgba(244, 63, 94, 0.72)',
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 16,
                            font: { size: 13, family: 'system-ui, sans-serif' },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var v = ctx.parsed.y;
                                if (v == null || isNaN(v)) {
                                    return '';
                                }
                                return (
                                    ctx.dataset.label +
                                    ': ' +
                                    v.toLocaleString('pl-PL', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    }) +
                                    ' zł'
                                );
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return Number(value).toLocaleString('pl-PL');
                            },
                        },
                    },
                },
            },
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
