<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include 'header.php';

$uid = $_SESSION['userid'];

// ── Total shoots ───────────────────────────────────────────────────────────
$r = $conn->prepare("SELECT COUNT(*) FROM shoot_results WHERE user_id = ?");
$r->bind_param("i", $uid); $r->execute();
$total_shoots = $r->get_result()->fetch_row()[0];

// ── Completed (shoot date has passed) ─────────────────────────────────────
$r = $conn->prepare("SELECT COUNT(*) FROM shoot_results WHERE user_id = ? AND shoot_datetime < NOW()");
$r->bind_param("i", $uid); $r->execute();
$completed = $r->get_result()->fetch_row()[0];

// ── Pending (shoot date is in the future) ──────────────────────────────────
$r = $conn->prepare("SELECT COUNT(*) FROM shoot_results WHERE user_id = ? AND shoot_datetime >= NOW()");
$r->bind_param("i", $uid); $r->execute();
$pending = $r->get_result()->fetch_row()[0];

// ── Average shoot score ────────────────────────────────────────────────────
$r = $conn->prepare("SELECT AVG(weather_score) FROM shoot_results WHERE user_id = ? AND weather_score IS NOT NULL");
$r->bind_param("i", $uid); $r->execute();
$avg_score = (int)round($r->get_result()->fetch_row()[0] ?? 0);

// ── This month count ───────────────────────────────────────────────────────
$r = $conn->prepare("SELECT COUNT(*) FROM shoot_results WHERE user_id = ? AND MONTH(shoot_datetime) = MONTH(NOW()) AND YEAR(shoot_datetime) = YEAR(NOW())");
$r->bind_param("i", $uid); $r->execute();
$this_month = $r->get_result()->fetch_row()[0];

// ── This week count ────────────────────────────────────────────────────────
$r = $conn->prepare("SELECT COUNT(*) FROM shoot_results WHERE user_id = ? AND shoot_datetime >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$r->bind_param("i", $uid); $r->execute();
$this_week = $r->get_result()->fetch_row()[0];

// ── Monthly activity (last 12 months) ─────────────────────────────────────
$r = $conn->prepare("
    SELECT 
        MONTH(shoot_datetime) as mon,
        YEAR(shoot_datetime)  as yr,
        COUNT(*)              as total,
        SUM(CASE WHEN shoot_datetime < NOW() THEN 1 ELSE 0 END) as done
    FROM shoot_results
    WHERE user_id = ?
      AND shoot_datetime >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(shoot_datetime), MONTH(shoot_datetime)
    ORDER BY yr ASC, mon ASC
");
$r->bind_param("i", $uid); $r->execute();
$monthly_rows = $r->get_result()->fetch_all(MYSQLI_ASSOC);

// Build a month-keyed array for the chart
$monthly_data = [];
foreach ($monthly_rows as $row) {
    $monthly_data[$row['yr'] . '-' . str_pad($row['mon'], 2, '0', STR_PAD_LEFT)] = [
        'planned' => (int)$row['total'],
        'done'    => (int)$row['done'],
    ];
}

// ── Mood breakdown ─────────────────────────────────────────────────────────
$r = $conn->prepare("
    SELECT mood, COUNT(*) as cnt 
    FROM shoot_results 
    WHERE user_id = ? 
    GROUP BY mood 
    ORDER BY cnt DESC 
    LIMIT 6
");
$r->bind_param("i", $uid); $r->execute();
$mood_rows = $r->get_result()->fetch_all(MYSQLI_ASSOC);

$mood_colors = [
    'warm'     => ['color'=>'#fb923c','bg'=>'linear-gradient(135deg,#3d1f00,#c8690a)'],
    'cool'     => ['color'=>'#3b82f6','bg'=>'linear-gradient(135deg,#001a3d,#0a6ac8)'],
    'dramatic' => ['color'=>'#a855f7','bg'=>'linear-gradient(135deg,#1a0a2e,#6b0f8a)'],
    'natural'  => ['color'=>'#4ade80','bg'=>'linear-gradient(135deg,#1a2e1a,#3d6b2a)'],
    'moody'    => ['color'=>'#6b7280','bg'=>'linear-gradient(135deg,#0d0d1a,#2a2040)'],
    'airy'     => ['color'=>'#93c5fd','bg'=>'linear-gradient(135deg,#1a2a3a,#6a9abf)'],
    'default'  => ['color'=>'#e5e7eb','bg'=>'linear-gradient(135deg,#1a1a1a,#2a2a2a)'],
];
$mood_max = !empty($mood_rows) ? $mood_rows[0]['cnt'] : 1;

// ── Top locations ──────────────────────────────────────────────────────────
$r = $conn->prepare("
    SELECT location, COUNT(*) as cnt 
    FROM shoot_results 
    WHERE user_id = ? 
    GROUP BY location 
    ORDER BY cnt DESC 
    LIMIT 5
");
$r->bind_param("i", $uid); $r->execute();
$location_rows = $r->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Shoot type distribution ────────────────────────────────────────────────
$r = $conn->prepare("
    SELECT shoot_type, COUNT(*) as cnt 
    FROM shoot_results 
    WHERE user_id = ? 
    GROUP BY shoot_type 
    ORDER BY cnt DESC
");
$r->bind_param("i", $uid); $r->execute();
$type_rows = $r->get_result()->fetch_all(MYSQLI_ASSOC);

$type_colors = [
    'portrait'  => '#c084fc',
    'fashion'   => '#3b82f6',
    'wedding'   => '#fb923c',
    'street'    => '#4ade80',
    'landscape' => '#f59e0b',
    'product'   => '#60a5fa',
    'default'   => '#6b7280',
];

// ── Recent shoots (last 8) ─────────────────────────────────────────────────
$r = $conn->prepare("
    SELECT id, location, shoot_type, mood, outfit_colour, shoot_datetime,
           weather_score, ai_plan
    FROM shoot_results
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 8
");
$r->bind_param("i", $uid); $r->execute();
$recent_shoots = $r->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Pending shoots details (upcoming, soonest first) ──────────────────────
$r = $conn->prepare("
    SELECT id, location, shoot_type, shoot_datetime
    FROM shoot_results
    WHERE user_id = ? AND shoot_datetime >= NOW()
    ORDER BY shoot_datetime ASC
    LIMIT 5
");
$r->bind_param("i", $uid); $r->execute();
$pending_shoots = $r->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Completion rate by type ────────────────────────────────────────────────
$r = $conn->prepare("
    SELECT shoot_type,
           COUNT(*) as total,
           SUM(CASE WHEN shoot_datetime < NOW() THEN 1 ELSE 0 END) as done
    FROM shoot_results
    WHERE user_id = ?
    GROUP BY shoot_type
    ORDER BY total DESC
");
$r->bind_param("i", $uid); $r->execute();
$completion_rows = $r->get_result()->fetch_all(MYSQLI_ASSOC);

$r = $conn->prepare("
    SELECT id, location, shoot_type, mood, outfit_colour,
           shoot_datetime, weather_score,
           (ai_plan IS NOT NULL) AS has_plan
    FROM shoot_results
    WHERE user_id = ?
    ORDER BY shoot_datetime DESC
");
$r->bind_param("i", $uid);
$r->execute();
$all_shoots_raw = $r->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($all_shoots_raw as &$row) {
    $row['has_plan']      = (bool)$row['has_plan'];
    $row['weather_score'] = (int)($row['weather_score'] ?? 0);
}
unset($row);
?>

<link rel="stylesheet" href="../assets/css/dashboard-style.css">

<div class="db-main">

    <!-- ── Page Header ──────────────────────────────────────────────────── -->
    <div class="db-page-header">
        <div>
            <div class="db-page-title">Overview</div>
            <div class="db-page-sub"><?= date('l, F j Y') ?> · Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></div>
        </div>
        <div class="db-header-actions">
            <button class="db-btn-outline">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="13" height="13">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25"/>
                </svg>
                <?= date('M Y') ?>
            </button>
            <button class="db-btn-outline" onclick="window.print()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="13" height="13">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12M12 16.5V3"/>
                </svg>
                Export
            </button>
        </div>
    </div>

    <!-- ── Stats Row ────────────────────────────────────────────────────── -->
    <div class="db-stats-grid">

        <div class="db-stat-card">
            <div class="db-stat-accent" style="background:#3b82f6;"></div>
            <div class="db-stat-label" style="color:#3b82f6;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                Total Shoots
            </div>
            <div class="db-stat-value"><?= $total_shoots ?></div>
            <div class="db-stat-change up">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                +<?= $this_month ?> this month
            </div>
        </div>

        <div class="db-stat-card">
            <div class="db-stat-accent" style="background:#22c55e;"></div>
            <div class="db-stat-label" style="color:#22c55e;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Completed
            </div>
            <div class="db-stat-value"><?= $completed ?></div>
            <div class="db-stat-change up">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                <?= round(($completed / max($total_shoots, 1)) * 100) ?>% success rate
            </div>
        </div>

        <!-- PENDING SHOOTS (replaces AI Plans Generated) -->
        <div class="db-stat-card">
            <div class="db-stat-accent" style="background:#f59e0b;"></div>
            <div class="db-stat-label" style="color:#f59e0b;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pending Shoots
            </div>
            <div class="db-stat-value"><?= $pending ?></div>
            <div class="db-stat-change <?= $pending > 0 ? 'ai' : 'up' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                <?= $pending > 0 ? "$pending awaiting AI plan" : 'All plans generated' ?>
            </div>
        </div>

        <div class="db-stat-card">
            <div class="db-stat-accent" style="background:#f59e0b;"></div>
            <div class="db-stat-label" style="color:#f59e0b;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                Avg. Shoot Score
            </div>
            <div class="db-stat-value"><?= $avg_score ?: '—' ?></div>
            <div class="db-stat-change up">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                Based on weather score
            </div>
        </div>

    </div>

    <!-- ── Row 1: Activity Chart + Quick Actions ─────────────────────────── -->
    <div class="db-grid-3">

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#0f1520;border:0.5px solid #1e3a5f;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm9.75-4.875C12.75 7.629 13.254 7.125 13.875 7.125h2.25c.621 0 1.125.504 1.125 1.125v11.625c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.25zm9.75-3.375c0-.621.504-1.125 1.125-1.125h.75c.621 0 1.125.504 1.125 1.125v15c0 .621-.504 1.125-1.125 1.125h-.75A1.125 1.125 0 0121 19.875V4.875z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Shoot Activity</div>
                        <div class="db-card-sub">Monthly volume · <?= date('Y') ?></div>
                    </div>
                </div>
                <div class="db-chart-legend">
                    <div style="font-size:10px;color:#6b7280;display:flex;align-items:center;gap:4px;">
                        <span class="db-legend-dot" style="background:#3b82f6;"></span>Planned
                    </div>
                    <div style="font-size:10px;color:#6b7280;display:flex;align-items:center;gap:4px;">
                        <span class="db-legend-dot" style="background:#22c55e;"></span>Done
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-bottom:10px;">
                <div class="db-chart-wrap">
                    <div class="db-chart-bars" id="db-chart-bars"></div>
                </div>
                <div class="db-chart-labels" id="db-chart-labels"></div>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#1a1209;border:0.5px solid #3a2d10;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Quick Actions</div>
                        <div class="db-card-sub">Shortcuts</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:12px;">
                <div class="db-credits-wrap">
                    <div class="db-credits-label">Plans Generated · <?= date('M Y') ?></div>
                    <div class="db-credits-track">
                        <div class="db-credits-fill" style="width:<?= $total_shoots > 0 ? round(($completed / $total_shoots) * 100) : 0 ?>%;"></div>
                    </div>
                    <div class="db-credits-meta">
                        <span><?= $completed ?> / <?= $total_shoots ?> completed</span>
                        <span><?= $total_shoots > 0 ? round(($completed / $total_shoots) * 100) : 0 ?>%</span>
                    </div>
                </div>
                <a href="../index.php" class="db-quick-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New Shoot Plan
                </a>
                <button onclick="openShootsModal()" class="db-quick-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                    View All Shoots
                </button>

            </div>
        </div>

    </div>

    <!-- ── Row 2: Recent Plans Table ────────────────────────────────────── -->
    <div style="margin-bottom:14px;">
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#0a1a10;border:0.5px solid #0f3d20;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Recent Shoot Plans</div>
                        <div class="db-card-sub">Last 8 shoots</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding:0 18px;">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th style="width:20%;">Location</th>
                            <th style="width:12%;">Type</th>
                            <th style="width:12%;">Mood</th>
                            <th style="width:14%;">Outfit</th>
                            <th style="width:12%;">Score</th>
                            <th style="width:14%;">Date</th>
                            <th style="width:8%;">Status</th>
                            <th style="width:8%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_shoots)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;color:#4b5563;padding:24px 0;">No shoots yet — <a href="index.php" style="color:#3b82f6;">create your first plan</a></td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_shoots as $s):
                            $is_done    = !empty($s['ai_plan']);
                            $score      = (int)($s['weather_score'] ?? 0);
                            $score_col  = $score >= 80 ? '#22c55e' : ($score >= 60 ? '#f59e0b' : '#e87070');
                            $type_key   = strtolower($s['shoot_type'] ?? '');
                            $type_col   = $type_colors[$type_key] ?? $type_colors['default'];
                            $shoot_date = (new DateTime($s['shoot_datetime']))->format('M j');
                        ?>
                        <tr>
                            <td style="font-size:11px;color:#e5e7eb;"><?= htmlspecialchars($s['location']) ?></td>
                            <td><span class="db-tag" style="background:<?= $type_col ?>22;color:<?= $type_col ?>;border:0.5px solid <?= $type_col ?>44;"><?= ucfirst(htmlspecialchars($s['shoot_type'])) ?></span></td>
                            <td style="font-size:11px;color:#9ca3af;"><?= ucfirst(htmlspecialchars($s['mood'])) ?></td>
                            <td style="font-size:11px;color:#6b7280;"><?= htmlspecialchars($s['outfit_colour'] ?: '—') ?></td>
                            <td>
                                <?php if ($score > 0): ?>
                                <div class="db-score-wrap">
                                    <div class="db-score-track">
                                        <div class="db-score-fill" style="width:<?= $score ?>%;background:<?= $score_col ?>;"></div>
                                    </div>
                                    <span style="font-size:11px;color:<?= $score_col ?>;"><?= $score ?></span>
                                </div>
                                <?php else: ?>
                                <span style="font-size:11px;color:#4b5563;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:11px;color:#6b7280;"><?= $shoot_date ?></td>
                            <td>
                                <?php if ($is_done): ?>
                                    <span class="db-tag db-status-done">Done</span>
                                <?php else: ?>
                                    <span class="db-tag db-status-review">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view-result.php?id=<?= $s['id'] ?>" class="db-action-btn" style="font-size:10px;">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Row 3: Mood + Locations + Activity ────────────────────────────── -->
    <div class="db-grid-4">

        <!-- Mood Breakdown -->
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#160d1a;border:0.5px solid #3d1f4a;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#c084fc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Mood Breakdown</div>
                        <div class="db-card-sub">All shoots</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php if (empty($mood_rows)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else: ?>
                <?php foreach ($mood_rows as $m):
                    $mk    = strtolower($m['mood']);
                    $mc    = $mood_colors[$mk] ?? $mood_colors['default'];
                    $pct   = round(($m['cnt'] / $mood_max) * 100);
                ?>
                <div class="db-mood-row">
                    <div class="db-mood-swatch" style="background:<?= $mc['bg'] ?>;"></div>
                    <div class="db-mood-name"><?= ucfirst(htmlspecialchars($m['mood'])) ?></div>
                    <div class="db-mood-count"><?= $m['cnt'] ?></div>
                    <div class="db-mood-bar"><div class="db-mood-fill" style="width:<?= $pct ?>%;background:<?= $mc['color'] ?>;"></div></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Locations -->
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#0a1a10;border:0.5px solid #0f3d20;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.159.69.159 1.006 0z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Top Locations</div>
                        <div class="db-card-sub">By shoot count</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php if (empty($location_rows)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else: ?>
                <?php foreach ($location_rows as $i => $l): ?>
                <div class="db-location-item">
                    <div class="db-location-rank"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
                    <div style="flex:1;">
                        <div class="db-location-name"><?= htmlspecialchars($l['location']) ?></div>
                        <div class="db-location-sub"><?= $l['cnt'] ?> shoot<?= $l['cnt'] != 1 ? 's' : '' ?></div>
                    </div>
                    <?php if ($i === 0): ?><span class="db-location-badge">Top</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Shoots -->
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#1a1209;border:0.5px solid #3a2d10;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Pending Shoots</div>
                        <div class="db-card-sub">Awaiting AI plan</div>
                    </div>
                </div>
                <?php if ($pending > 0): ?>
                <span class="db-tag db-status-review" style="font-size:10px;"><?= $pending ?> pending</span>
                <?php endif; ?>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php if (empty($pending_shoots)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;display:flex;align-items:center;gap:8px;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="1.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        All shoots have AI plans!
                    </div>
                <?php else: ?>
                <?php foreach ($pending_shoots as $p):
                    $pd = (new DateTime($p['shoot_datetime']))->format('M j · g:i A');
                ?>
                <div class="db-activity-item" style="align-items:flex-start;">
                    <div class="db-activity-dot" style="background:#f59e0b;border:2px solid #f59e0b;margin-top:4px;"></div>
                    <div style="flex:1;">
                        <div style="font-size:12px;color:#e5e7eb;font-weight:500;"><?= htmlspecialchars($p['location']) ?></div>
                        <div style="font-size:11px;color:#6b7280;"><?= ucfirst($p['shoot_type']) ?> · <?= $pd ?></div>
                    </div>
                    <a href="view-result.php?id=<?= $p['id'] ?>" class="db-action-btn" style="font-size:10px;flex-shrink:0;">Open</a>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#0f1520;border:0.5px solid #1e3a5f;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#60a5fa" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Activity Feed</div>
                        <div class="db-card-sub">Recent shoots</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php if (empty($recent_shoots)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No activity yet.</div>
                <?php else: ?>
                <?php foreach (array_slice($recent_shoots, 0, 6) as $s):
                    $is_done   = !empty($s['ai_plan']);
                    $dot_color = $is_done ? '#22c55e' : '#f59e0b';
                    $ago_dt    = new DateTime($s['shoot_datetime']);
                    $now_dt    = new DateTime();
                    $diff      = $now_dt->diff($ago_dt);
                    if ($diff->days === 0)       $ago = $diff->h . 'h ago';
                    elseif ($diff->days === 1)   $ago = 'Yesterday';
                    else                         $ago = $ago_dt->format('M j');
                ?>
                <div class="db-activity-item">
                    <div class="db-activity-dot" style="background:<?= $dot_color ?>;border:2px solid <?= $dot_color ?>;"></div>
                    <div class="db-activity-text">
                        <strong><?= ucfirst(htmlspecialchars($s['shoot_type'])) ?></strong> shoot at <?= htmlspecialchars($s['location']) ?>
                        <?= $is_done ? '· <span style="color:#22c55e;">Plan ready</span>' : '· <span style="color:#f59e0b;">Pending</span>' ?>
                    </div>
                    <div class="db-activity-time"><?= $ago ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ── Row 4: Shoot Type Donut ───────────────────────────────────────── -->
    <div class="db-grid-2" style="margin-bottom:0;">

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#160d1a;border:0.5px solid #3d1f4a;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#c084fc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Shoot Type Distribution</div>
                        <div class="db-card-sub">All time · <?= $total_shoots ?> shoots</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body">
                <?php if (empty($type_rows)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else:
                    // Build donut segments
                    $circumference = 276.46; // 2π × 44
                    $offset = 0;
                    $segments = [];
                    foreach ($type_rows as $t) {
                        $pct = $t['cnt'] / max($total_shoots, 1);
                        $segments[] = [
                            'type'   => $t['shoot_type'],
                            'cnt'    => $t['cnt'],
                            'pct'    => round($pct * 100),
                            'dash'   => round($pct * $circumference, 1),
                            'offset' => -$offset,
                            'color'  => $type_colors[strtolower($t['shoot_type'])] ?? $type_colors['default'],
                        ];
                        $offset += round($pct * $circumference, 1);
                    }
                ?>
                <div class="db-donut-wrap">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="44" fill="none" stroke="#1a1a1a" stroke-width="20"/>
                        <?php foreach ($segments as $seg): ?>
                        <circle cx="60" cy="60" r="44" fill="none"
                            stroke="<?= $seg['color'] ?>" stroke-width="20"
                            stroke-dasharray="<?= $seg['dash'] ?> <?= $circumference - $seg['dash'] ?>"
                            stroke-dashoffset="<?= $seg['offset'] ?>"
                            stroke-linecap="butt"
                            transform="rotate(-90 60 60)"/>
                        <?php endforeach; ?>
                        <text x="60" y="55" text-anchor="middle" fill="#fff" font-family="Bebas Neue,sans-serif" font-size="20"><?= $total_shoots ?></text>
                        <text x="60" y="68" text-anchor="middle" fill="#6b7280" font-family="DM Sans,sans-serif" font-size="10">total</text>
                    </svg>
                    <div class="db-donut-legend">
                        <?php foreach ($segments as $seg): ?>
                        <div class="db-donut-legend-item">
                            <div class="db-donut-legend-dot" style="background:<?= $seg['color'] ?>;"></div>
                            <div>
                                <div class="db-donut-label"><?= ucfirst(htmlspecialchars($seg['type'])) ?></div>
                                <div class="db-donut-pct" style="color:<?= $seg['color'] ?>;"><?= $seg['pct'] ?>%</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Completion Rate by Type -->
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#0a1a10;border:0.5px solid #0f3d20;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Completion by Type</div>
                        <div class="db-card-sub">AI plan generated rate</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php
                // Completion rate per shoot type
                $r = $conn->prepare("
                    SELECT shoot_type,
                           COUNT(*) as total,
                           SUM(CASE WHEN ai_plan IS NOT NULL THEN 1 ELSE 0 END) as done
                    FROM shoot_results
                    WHERE user_id = ?
                    GROUP BY shoot_type
                    ORDER BY total DESC
                ");
                $r->bind_param("i", $uid); $r->execute();
                $completion_rows = $r->get_result()->fetch_all(MYSQLI_ASSOC);
                if (empty($completion_rows)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else: ?>
                <?php foreach ($completion_rows as $cr):
                    $pct   = round(($cr['done'] / max($cr['total'], 1)) * 100);
                    $col   = $type_colors[strtolower($cr['shoot_type'])] ?? $type_colors['default'];
                ?>
                <div class="db-mood-row">
                    <div class="db-mood-swatch" style="background:<?= $col ?>22;border:0.5px solid <?= $col ?>44;"></div>
                    <div class="db-mood-name"><?= ucfirst(htmlspecialchars($cr['shoot_type'])) ?></div>
                    <div class="db-mood-count" style="color:<?= $col ?>;"><?= $pct ?>%</div>
                    <div class="db-mood-bar"><div class="db-mood-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<div id="all-shoots-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#0d0d0d;border:0.5px solid #1f1f1f;border-radius:16px;width:90%;max-width:820px;max-height:85vh;display:flex;flex-direction:column;overflow:hidden;">

        <!-- Modal Header -->
        <div style="padding:18px 20px 14px;border-bottom:0.5px solid #1a1a1a;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:28px;height:28px;border-radius:8px;background:#0f1520;border:0.5px solid #1e3a5f;display:flex;align-items:center;justify-content:center;">
                    <svg fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="1.5" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:500;color:#e5e7eb;">All Shoot Plans</div>
                    <div style="font-size:11px;color:#4b5563;" id="modal-shoot-count">Loading…</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <!-- Filter -->
                <select id="modal-filter-type" onchange="filterModalShoots()" style="background:#111;border:0.5px solid #1f1f1f;border-radius:8px;color:#9ca3af;font-size:11px;padding:5px 8px;cursor:pointer;">
                    <option value="">All types</option>
                    <option value="portrait">Portrait</option>
                    <option value="fashion">Fashion</option>
                    <option value="wedding">Wedding</option>
                    <option value="street">Street</option>
                    <option value="landscape">Landscape</option>
                    <option value="product">Product</option>
                </select>
                <select id="modal-filter-status" onchange="filterModalShoots()" style="background:#111;border:0.5px solid #1f1f1f;border-radius:8px;color:#9ca3af;font-size:11px;padding:5px 8px;cursor:pointer;">
                    <option value="">All status</option>
                    <option value="done">Plan ready</option>
                    <option value="pending">Pending</option>
                </select>
                <!-- Close -->
                <button onclick="closeShootsModal()" style="background:#1a1a1a;border:0.5px solid #2a2a2a;border-radius:8px;color:#9ca3af;font-size:11px;padding:5px 10px;cursor:pointer;display:flex;align-items:center;gap:4px;">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="11" height="11">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Close
                </button>
            </div>
        </div>

        <!-- Scrollable list -->
        <div style="overflow-y:auto;flex:1;padding:12px 20px;" id="modal-shoots-list">
            <div style="color:#4b5563;font-size:12px;padding:24px 0;text-align:center;">Loading shoots…</div>
        </div>

    </div>
</div>

<script>
(function buildChart() {
    const monthlyData = <?= json_encode($monthly_data) ?>;
    const barsEl   = document.getElementById('db-chart-bars');
    const labelsEl = document.getElementById('db-chart-labels');
    if (!barsEl || !labelsEl) return;

    const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const keys = Object.keys(monthlyData);
    if (keys.length === 0) {
        barsEl.innerHTML = '<div style="color:#4b5563;font-size:12px;padding:20px;">No shoot data yet.</div>';
        return;
    }

    const maxVal = Math.max(...keys.map(k => monthlyData[k].planned), 1);

    keys.forEach(key => {
        const [yr, mon] = key.split('-');
        const p  = monthlyData[key].planned;
        const d  = monthlyData[key].done;
        const ph = Math.round((p / maxVal) * 130);
        const dh = Math.round((d / maxVal) * 130);
        const now = new Date();
        const isNow = parseInt(mon) - 1 === now.getMonth() && parseInt(yr) === now.getFullYear();

        const col = document.createElement('div');
        col.className = 'db-bar-col';
        col.innerHTML = `
            <div class="db-bar-group" style="height:${ph}px;width:100%;position:relative;">
                <div class="db-bar-planned" style="height:${ph}px;background:${isNow?'#3b82f6':'#1e3a5f'};opacity:${isNow?1:0.65};width:70%;"></div>
                <div class="db-bar-done"    style="height:${dh}px;background:#22c55e;width:70%;left:15%;"></div>
            </div>
        `;
        barsEl.appendChild(col);

        const lbl = document.createElement('div');
        lbl.className = 'db-chart-label';
        lbl.textContent = monthNames[parseInt(mon) - 1];
        lbl.style.color = isNow ? '#3b82f6' : '#4b5563';
        lbl.style.fontWeight = isNow ? '500' : '400';
        labelsEl.appendChild(lbl);
    });
})();

const TYPE_COLORS = <?= json_encode($type_colors) ?>;
let allShoots = [];

allShoots = <?= json_encode($all_shoots_raw) ?>;

function openShootsModal() {
    document.getElementById('all-shoots-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    renderModalShoots(allShoots);
}

function closeShootsModal() {
    document.getElementById('all-shoots-modal').style.display = 'none';
    document.body.style.overflow = '';
}

document.getElementById('all-shoots-modal').addEventListener('click', function(e) {
    if (e.target === this) closeShootsModal();
});

function filterModalShoots() {
    const type   = document.getElementById('modal-filter-type').value;
    const status = document.getElementById('modal-filter-status').value;
    let filtered = allShoots;
    if (type)   filtered = filtered.filter(s => s.shoot_type === type);
    if (status) filtered = filtered.filter(s => status === 'done' ? s.has_plan : !s.has_plan);
    renderModalShoots(filtered);
}

function renderModalShoots(shoots) {
    const list = document.getElementById('modal-shoots-list');
    document.getElementById('modal-shoot-count').textContent = shoots.length + ' shoot' + (shoots.length !== 1 ? 's' : '');

    if (shoots.length === 0) {
        list.innerHTML = '<div style="color:#4b5563;font-size:12px;padding:32px 0;text-align:center;">No shoots match this filter.</div>';
        return;
    }

    list.innerHTML = shoots.map((s, i) => {
        const isLast   = i === shoots.length - 1;
        const col      = TYPE_COLORS[s.shoot_type] ?? TYPE_COLORS['default'];
        const isPast   = new Date(s.shoot_datetime) < new Date();
        const hasPlan  = s.has_plan;
        const score    = parseInt(s.weather_score) || 0;
        const scoreCol = score >= 80 ? '#22c55e' : score >= 60 ? '#f59e0b' : score > 0 ? '#e87070' : null;

        const d    = new Date(s.shoot_datetime);
        const date = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const time = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

        return `
        <div style="padding:12px 0;border-bottom:${isLast ? 'none' : '0.5px solid #141414'};display:flex;align-items:center;gap:12px;">

            <!-- Type badge -->
            <div style="flex-shrink:0;width:36px;height:36px;border-radius:10px;background:${col}18;border:0.5px solid ${col}33;display:flex;align-items:center;justify-content:center;">
                <svg fill="none" viewBox="0 0 24 24" stroke="${col}" stroke-width="1.5" width="15" height="15">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                </svg>
            </div>

            <!-- Main info -->
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:500;color:#e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;">${s.location}</span>
                    <span style="font-size:10px;background:${col}18;color:${col};border:0.5px solid ${col}33;border-radius:5px;padding:1px 6px;">${s.shoot_type}</span>
                    ${hasPlan
                        ? `<span style="font-size:10px;background:#0a1a10;color:#22c55e;border:0.5px solid #0f3d2044;border-radius:5px;padding:1px 6px;">Plan ready</span>`
                        : `<span style="font-size:10px;background:#1a1209;color:#f59e0b;border:0.5px solid #3a2d1044;border-radius:5px;padding:1px 6px;">Pending</span>`
                    }
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="font-size:11px;color:#4b5563;">${date} · ${time}</span>
                    <span style="font-size:11px;color:#4b5563;">${s.mood ? s.mood.charAt(0).toUpperCase() + s.mood.slice(1) + ' mood' : ''}</span>
                    ${s.outfit_colour ? `<span style="font-size:11px;color:#4b5563;">Outfit: ${s.outfit_colour}</span>` : ''}
                </div>
            </div>

            <!-- Score -->
            ${scoreCol ? `
            <div style="flex-shrink:0;text-align:center;min-width:36px;">
                <div style="font-size:14px;font-weight:500;color:${scoreCol};">${score}</div>
                <div style="font-size:9px;color:#4b5563;">score</div>
            </div>` : ''}

            <!-- View button -->
            <a href="view-result.php?id=${s.id}" style="flex-shrink:0;display:flex;align-items:center;gap:4px;background:#0f1520;border:0.5px solid #1e3a5f;border-radius:8px;padding:6px 12px;font-size:11px;color:#60a5fa;text-decoration:none;white-space:nowrap;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                </svg>
                View
            </a>

        </div>`;
    }).join('');
}

</script>

<?php include 'footer.php'; ?>