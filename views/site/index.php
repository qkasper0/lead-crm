<?php

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var array $statusCounts */
/** @var array $byManager */
/** @var int $todayCount */

use app\models\Lead;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Дашборд';
$total = array_sum($statusCounts);
$open = $statusCounts[Lead::STATUS_NEW] + $statusCounts[Lead::STATUS_IN_WORK] + $statusCounts[Lead::STATUS_CALLBACK];
$closed = $statusCounts[Lead::STATUS_SUCCESS] + $statusCounts[Lead::STATUS_REJECTED];
$conversion = $closed > 0 ? round($statusCounts[Lead::STATUS_SUCCESS] / $closed * 100) : null;

// Цвет за каждым статусом закреплён и одинаков во всех графиках.
$colors = [
    Lead::STATUS_NEW => '#3b82f6',
    Lead::STATUS_IN_WORK => '#f59e0b',
    Lead::STATUS_CALLBACK => '#8b5cf6',
    Lead::STATUS_SUCCESS => '#10b981',
    Lead::STATUS_REJECTED => '#9ca3af',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $user->isAdmin() ? 'Все лиды' : 'Мои лиды' ?></h1>
    <a class="btn btn-outline-primary btn-sm" href="<?= Url::to(['/lead/index']) ?>">Открыть список</a>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Всего лидов', $total],
        ['Сегодня', $todayCount],
        ['Открытые', $open],
        ['Конверсия в успех', $conversion === null ? '—' : $conversion . '%'],
    ] as [$label, $value]): ?>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small"><?= Html::encode($label) ?></div>
                <div class="fs-3 fw-semibold"><?= Html::encode((string)$value) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-<?= $user->isAdmin() ? '5' : '12' ?>">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 mb-3">Лиды по статусам</h2>
            <div style="position: relative; height: 260px"><canvas id="byStatus"></canvas></div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <?php foreach (Lead::STATUSES as $key => $label): ?>
                    <a class="badge rounded-pill text-decoration-none" style="background: <?= $colors[$key] ?>"
                       href="<?= Url::to(['/lead/index', 'status' => $key]) ?>">
                        <?= Html::encode($label) ?>: <?= $statusCounts[$key] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div></div>
    </div>
    <?php if ($user->isAdmin()): ?>
        <div class="col-lg-7">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6 mb-3">Лиды по менеджерам</h2>
                <?php if ($byManager): ?>
                    <div style="position: relative; height: <?= max(200, 60 * count($byManager) + 60) ?>px"><canvas id="byManager"></canvas></div>
                <?php else: ?>
                    <p class="text-secondary mb-0">Пока нет лидов.</p>
                <?php endif; ?>
            </div></div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<?php
$statusLabels = array_values(Lead::STATUSES);
$statusData = array_values($statusCounts);
$statusColors = array_values($colors);
$managerNames = array_keys($byManager);
$managerSets = [];
foreach (Lead::STATUSES as $key => $label) {
    $managerSets[] = [
        'label' => $label,
        'data' => array_map(fn($m) => $byManager[$m][$key] ?? 0, $managerNames),
        'backgroundColor' => $colors[$key],
        'borderWidth' => 0,
    ];
}
$js = <<<JS
new Chart(document.getElementById('byStatus'), {
    type: 'bar',
    data: { labels: %s, datasets: [{ data: %s, backgroundColor: %s, borderWidth: 0 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
const m = document.getElementById('byManager');
if (m) {
    new Chart(m, {
        type: 'bar',
        data: { labels: %s, datasets: %s },
        options: {
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { position: 'bottom' } },
            scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }, y: { stacked: true } }
        }
    });
}
JS;
$this->registerJs(sprintf(
    $js,
    Json::htmlEncode($statusLabels),
    Json::htmlEncode($statusData),
    Json::htmlEncode($statusColors),
    Json::htmlEncode($managerNames),
    Json::htmlEncode($managerSets)
), \yii\web\View::POS_END);
