<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include 'header.php';

// ── Fetch counts from DB (replace with your real queries) ──────────────────
// $total_shoots    = getTotalShoots($conn);
// $completed       = getCompletedShoots($conn);
// $ai_plans        = getAIPlans($conn);
// $avg_score       = getAverageScore($conn);
// $recent_shoots   = getRecentShoots($conn, 5);
// $mood_stats      = getMoodStats($conn);
// $top_locations   = getTopLocations($conn, 5);
// $ai_body_stats   = getBodyAnalysisStats($conn);

// ── Placeholder data (remove when real queries are in place) ───────────────
$total_shoots  = 148;
$completed     = 124;
$ai_plans      = 89;
$avg_score     = 86;
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
                +12 this month
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
                <?= round(($completed / max($total_shoots,1)) * 100) ?>% success rate
            </div>
        </div>

        <div class="db-stat-card">
            <div class="db-stat-accent" style="background:#a855f7;"></div>
            <div class="db-stat-label" style="color:#a855f7;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                AI Plans Generated
            </div>
            <div class="db-stat-value"><?= $ai_plans ?></div>
            <div class="db-stat-change ai">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                +8 this week
            </div>
        </div>

        <div class="db-stat-card">
            <div class="db-stat-accent" style="background:#f59e0b;"></div>
            <div class="db-stat-label" style="color:#f59e0b;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                Avg. Shoot Score
            </div>
            <div class="db-stat-value"><?= $avg_score ?></div>
            <div class="db-stat-change up">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                +4 pts from last month
            </div>
        </div>

    </div>

    <!-- ── Row 1: Activity Chart + Quick Actions ─────────────────────────── -->
    <div class="db-grid-3">

        <!-- Activity Chart -->
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

        <!-- Quick Actions -->
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

                <!-- AI Credits -->
                <div class="db-credits-wrap">
                    <div class="db-credits-label">AI Credits — <?= date('M Y') ?></div>
                    <div class="db-credits-track">
                        <div class="db-credits-fill" style="width:62%;"></div>
                    </div>
                    <div class="db-credits-meta">
                        <span>620 / 1000 used</span>
                        <span>62%</span>
                    </div>
                </div>

                <a href="index.php" class="db-quick-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New Shoot Plan
                </a>
                <a href="chatbot.php" class="db-quick-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                    Ask AI Director
                </a>
                <a href="weather.php" class="db-quick-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z"/></svg>
                    Check Weather
                </a>
                <a href="locations.php" class="db-quick-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    Scout Location
                </a>
                <a href="color-harmony.php" class="db-quick-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197"/></svg>
                    Colour Harmony
                </a>
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
                        <div class="db-card-sub">Last 30 days</div>
                    </div>
                </div>
                <div style="display:flex;gap:6px;">
                    <button class="db-action-btn">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                        Filter
                    </button>
                    <button class="db-action-btn">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/></svg>
                        Sort
                    </button>
                </div>
            </div>
            <div class="db-card-body" style="padding:0 18px;">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th style="width:28%;">Model / Client</th>
                            <th style="width:14%;">Type</th>
                            <th style="width:16%;">Location</th>
                            <th style="width:14%;">Mood</th>
                            <th style="width:10%;">Score</th>
                            <th style="width:10%;">Date</th>
                            <th style="width:8%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ── Replace this block with your DB query results ──────────────
                        $sample_shoots = [
                            ['name'=>'Mia Fernandez',   'outfit'=>'Cream outfit',  'type'=>'Fashion',  'type_class'=>'db-tag-blue',   'location'=>'Colombo Fort',  'mood'=>'Warm',     'score'=>91, 'score_color'=>'#22c55e', 'date'=>'Jun 3',  'status'=>'Done',   'status_class'=>'db-status-done'],
                            ['name'=>'James Obasi',     'outfit'=>'Black outfit',  'type'=>'Portrait', 'type_class'=>'db-tag-purple', 'location'=>'Kandy Hills',   'mood'=>'Dramatic', 'score'=>84, 'score_color'=>'#3b82f6', 'date'=>'Jun 1',  'status'=>'Done',   'status_class'=>'db-status-done'],
                            ['name'=>'Ayesha Kumar',    'outfit'=>'White outfit',  'type'=>'Wedding',  'type_class'=>'db-tag-orange', 'location'=>'Galle Fort',    'mood'=>'Airy',     'score'=>96, 'score_color'=>'#22c55e', 'date'=>'May 29', 'status'=>'Done',   'status_class'=>'db-status-done'],
                            ['name'=>'Tom Wijesinghe',  'outfit'=>'Navy outfit',   'type'=>'Street',   'type_class'=>'db-tag-green',  'location'=>'Pettah Market', 'mood'=>'Moody',    'score'=>72, 'score_color'=>'#f59e0b', 'date'=>'May 27', 'status'=>'Review', 'status_class'=>'db-status-review'],
                            ['name'=>'Elena Rodrigo',   'outfit'=>'Green outfit',  'type'=>'Portrait', 'type_class'=>'db-tag-purple', 'location'=>'Horton Plains', 'mood'=>'Natural',  'score'=>88, 'score_color'=>'#22c55e', 'date'=>'May 24', 'status'=>'Done',   'status_class'=>'db-status-done'],
                            ['name'=>'Sophia Laurent',  'outfit'=>'Beige outfit',  'type'=>'Fashion',  'type_class'=>'db-tag-blue',   'location'=>'Galle Face',    'mood'=>'Cool',     'score'=>79, 'score_color'=>'#3b82f6', 'date'=>'May 20', 'status'=>'Draft',  'status_class'=>'db-status-draft'],
                        ];
                        foreach ($sample_shoots as $s): ?>
                        <tr>
                            <td>
                                <div style="font-weight:500;color:#e5e7eb;"><?= htmlspecialchars($s['name']) ?></div>
                                <div style="font-size:10px;color:#4b5563;"><?= htmlspecialchars($s['outfit']) ?></div>
                            </td>
                            <td><span class="db-tag <?= $s['type_class'] ?>"><?= $s['type'] ?></span></td>
                            <td style="font-size:11px;"><?= htmlspecialchars($s['location']) ?></td>
                            <td style="font-size:11px;color:#9ca3af;"><?= htmlspecialchars($s['mood']) ?></td>
                            <td>
                                <div class="db-score-wrap">
                                    <div class="db-score-track">
                                        <div class="db-score-fill" style="width:<?= $s['score'] ?>%;background:<?= $s['score_color'] ?>;"></div>
                                    </div>
                                    <span style="font-size:11px;color:<?= $s['score_color'] ?>;"><?= $s['score'] ?></span>
                                </div>
                            </td>
                            <td style="font-size:11px;"><?= $s['date'] ?></td>
                            <td><span class="db-tag <?= $s['status_class'] ?>"><?= $s['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Row 3: Mood + Locations + Weather + Activity ─────────────────── -->
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
                        <div class="db-card-sub">All time</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php
                $moods = [
                    ['name'=>'Warm',     'count'=>34, 'pct'=>100, 'color'=>'#fb923c', 'bg'=>'linear-gradient(135deg,#3d1f00,#c8690a)'],
                    ['name'=>'Cool',     'count'=>28, 'pct'=>82,  'color'=>'#3b82f6', 'bg'=>'linear-gradient(135deg,#001a3d,#0a6ac8)'],
                    ['name'=>'Dramatic', 'count'=>22, 'pct'=>65,  'color'=>'#a855f7', 'bg'=>'linear-gradient(135deg,#1a0a2e,#6b0f8a)'],
                    ['name'=>'Natural',  'count'=>19, 'pct'=>56,  'color'=>'#4ade80', 'bg'=>'linear-gradient(135deg,#1a2e1a,#3d6b2a)'],
                    ['name'=>'Moody',    'count'=>16, 'pct'=>47,  'color'=>'#6b7280', 'bg'=>'linear-gradient(135deg,#0d0d1a,#2a2040)'],
                    ['name'=>'Airy',     'count'=>14, 'pct'=>41,  'color'=>'#93c5fd', 'bg'=>'linear-gradient(135deg,#1a2a3a,#6a9abf)'],
                ];
                foreach ($moods as $m): ?>
                <div class="db-mood-row">
                    <div class="db-mood-swatch" style="background:<?= $m['bg'] ?>;"></div>
                    <div class="db-mood-name"><?= $m['name'] ?></div>
                    <div class="db-mood-count"><?= $m['count'] ?></div>
                    <div class="db-mood-bar"><div class="db-mood-fill" style="width:<?= $m['pct'] ?>%;background:<?= $m['color'] ?>;"></div></div>
                </div>
                <?php endforeach; ?>
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
                <?php
                $locations = [
                    ['rank'=>'01','name'=>'Galle Fort',       'sub'=>'Southern Province · 18 shoots', 'fav'=>true],
                    ['rank'=>'02','name'=>'Galle Face Green',  'sub'=>'Colombo · 14 shoots',           'fav'=>false],
                    ['rank'=>'03','name'=>'Negombo Beach',     'sub'=>'Western Province · 11 shoots',  'fav'=>false],
                    ['rank'=>'04','name'=>'Horton Plains',     'sub'=>'Central Province · 9 shoots',   'fav'=>false],
                    ['rank'=>'05','name'=>'Sigiriya Rock',     'sub'=>'North Central · 7 shoots',      'fav'=>false],
                ];
                foreach ($locations as $l): ?>
                <div class="db-location-item">
                    <div class="db-location-rank"><?= $l['rank'] ?></div>
                    <div style="flex:1;">
                        <div class="db-location-name"><?= htmlspecialchars($l['name']) ?></div>
                        <div class="db-location-sub"><?= htmlspecialchars($l['sub']) ?></div>
                    </div>
                    <?php if ($l['fav']): ?><span class="db-location-badge">Fav</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Today's Weather -->
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#1a1209;border:0.5px solid #3a2d10;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Today's Weather</div>
                        <div class="db-card-sub">Saved locations</div>
                    </div>
                </div>
                <a href="weather.php" class="db-card-link">Refresh</a>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php
                $weather_locs = [
                    ['icon'=>'🌤',  'loc'=>'Negombo',    'cond'=>'Partly cloudy · 82% humidity', 'temp'=>'31°', 'label'=>'Good',  'label_class'=>'db-status-done'],
                    ['icon'=>'☀️',  'loc'=>'Galle Fort', 'cond'=>'Clear · Golden hr 5:58 PM',     'temp'=>'29°', 'label'=>'Ideal', 'label_class'=>'db-status-done'],
                    ['icon'=>'🌧',  'loc'=>'Kandy',      'cond'=>'Rain expected · 70% chance',    'temp'=>'24°', 'label'=>'Avoid', 'label_class'=>'db-status-review'],
                ];
                foreach ($weather_locs as $w): ?>
                <div class="db-weather-item">
                    <div class="db-weather-icon"><?= $w['icon'] ?></div>
                    <div class="db-weather-info">
                        <div class="db-weather-loc"><?= htmlspecialchars($w['loc']) ?></div>
                        <div class="db-weather-cond"><?= htmlspecialchars($w['cond']) ?></div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div class="db-weather-temp"><?= $w['temp'] ?></div>
                        <span class="db-tag <?= $w['label_class'] ?>" style="font-size:9px;"><?= $w['label'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
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
                        <div class="db-card-sub">Recent events</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php
                $activities = [
                    ['dot'=>'#22c55e', 'text'=>'<strong>Mia Fernandez</strong> shoot marked complete · Score 91',         'time'=>'2h ago'],
                    ['dot'=>'#3b82f6', 'text'=>'<strong>New plan</strong> generated for Sophia Laurent · Fashion',         'time'=>'5h ago'],
                    ['dot'=>'#a855f7', 'text'=>'<strong>AI Director</strong> suggested 5 poses for portrait session',      'time'=>'Yesterday'],
                    ['dot'=>'#f59e0b', 'text'=>'<strong>Weather alert</strong> for Kandy shoot Jun 9 · Rain forecast',     'time'=>'Yesterday'],
                    ['dot'=>'#4ade80', 'text'=>'<strong>Sigiriya Rock</strong> added to favourite locations',              'time'=>'Jun 3'],
                    ['dot'=>'#60a5fa', 'text'=>'<strong>89 AI credits</strong> used this month · 62% of quota',           'time'=>'Jun 3'],
                ];
                foreach ($activities as $a): ?>
                <div class="db-activity-item">
                    <div class="db-activity-dot" style="background:<?= $a['dot'] ?>;border:2px solid <?= $a['dot'] ?>;"></div>
                    <div class="db-activity-text"><?= $a['text'] ?></div>
                    <div class="db-activity-time"><?= $a['time'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- ── Row 4: Shoot Type Donut + AI Body Analysis ────────────────────── -->
    <div class="db-grid-2" style="margin-bottom:0;">

        <!-- Shoot Type Distribution -->
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
                <div class="db-donut-wrap">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="44" fill="none" stroke="#1a1a1a" stroke-width="20"/>
                        <!-- Fashion 40% -->
                        <circle cx="60" cy="60" r="44" fill="none" stroke="#3b82f6" stroke-width="20"
                            stroke-dasharray="110.6 165.9" stroke-dashoffset="0"
                            stroke-linecap="butt" transform="rotate(-90 60 60)"/>
                        <!-- Portrait 30% -->
                        <circle cx="60" cy="60" r="44" fill="none" stroke="#c084fc" stroke-width="20"
                            stroke-dasharray="82.9 193.6" stroke-dashoffset="-110.6"
                            stroke-linecap="butt" transform="rotate(-90 60 60)"/>
                        <!-- Wedding 20% -->
                        <circle cx="60" cy="60" r="44" fill="none" stroke="#fb923c" stroke-width="20"
                            stroke-dasharray="55.3 221.2" stroke-dashoffset="-193.5"
                            stroke-linecap="butt" transform="rotate(-90 60 60)"/>
                        <!-- Street 10% -->
                        <circle cx="60" cy="60" r="44" fill="none" stroke="#4ade80" stroke-width="20"
                            stroke-dasharray="27.6 248.9" stroke-dashoffset="-248.8"
                            stroke-linecap="butt" transform="rotate(-90 60 60)"/>
                        <text x="60" y="55" text-anchor="middle" fill="#fff" font-family="Bebas Neue,sans-serif" font-size="20"><?= $total_shoots ?></text>
                        <text x="60" y="68" text-anchor="middle" fill="#6b7280" font-family="DM Sans,sans-serif" font-size="10">total</text>
                    </svg>
                    <div class="db-donut-legend">
                        <div class="db-donut-legend-item">
                            <div class="db-donut-legend-dot" style="background:#3b82f6;"></div>
                            <div><div class="db-donut-label">Fashion</div><div class="db-donut-pct" style="color:#3b82f6;">40%</div></div>
                        </div>
                        <div class="db-donut-legend-item">
                            <div class="db-donut-legend-dot" style="background:#c084fc;"></div>
                            <div><div class="db-donut-label">Portrait</div><div class="db-donut-pct" style="color:#c084fc;">30%</div></div>
                        </div>
                        <div class="db-donut-legend-item">
                            <div class="db-donut-legend-dot" style="background:#fb923c;"></div>
                            <div><div class="db-donut-label">Wedding</div><div class="db-donut-pct" style="color:#fb923c;">20%</div></div>
                        </div>
                        <div class="db-donut-legend-item">
                            <div class="db-donut-legend-dot" style="background:#4ade80;"></div>
                            <div><div class="db-donut-label">Street</div><div class="db-donut-pct" style="color:#4ade80;">10%</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Body Analysis Stats -->
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#0a1a10;border:0.5px solid #0f3d20;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">AI Body Analysis</div>
                        <div class="db-card-sub">MediaPipe · <?= $ai_plans ?> models analyzed</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-ai-grid">
                    <?php
                    $ai_cells = [
                        ['label'=>'Most Common',    'val'=>'Hourglass',    'color'=>'#c084fc', 'sub'=>'Body type · 34%'],
                        ['label'=>'Face Shape',     'val'=>'Oval',         'color'=>'#3b82f6', 'sub'=>'Most frequent · 41%'],
                        ['label'=>'Confidence',     'val'=>'High',         'color'=>'#22c55e', 'sub'=>'Avg. analysis · 78%'],
                        ['label'=>'Top Angle',      'val'=>'3/4 View',     'color'=>'#fb923c', 'sub'=>'Recommended · 52%'],
                        ['label'=>'Height Est.',    'val'=>'Average',      'color'=>'#f59e0b', 'sub'=>'Most models · 63%'],
                        ['label'=>'Symmetry',       'val'=>'Medium',       'color'=>'#60a5fa', 'sub'=>'Face avg · 58%'],
                    ];
                    foreach ($ai_cells as $c): ?>
                    <div class="db-ai-cell">
                        <div class="db-ai-cell-label"><?= $c['label'] ?></div>
                        <div class="db-ai-cell-val" style="color:<?= $c['color'] ?>;"><?= $c['val'] ?></div>
                        <div class="db-ai-cell-sub"><?= $c['sub'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

</div><!-- /db-main -->

<script>
(function buildChart() {
    const months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const planned = [8,11,9,14,12,16,0,0,0,0,0,0];
    const done    = [7,10,8,13,11,14,0,0,0,0,0,0];
    const max = 16;
    const curMonth = new Date().getMonth();
    const barsEl   = document.getElementById('db-chart-bars');
    const labelsEl = document.getElementById('db-chart-labels');

    if (!barsEl || !labelsEl) return;

    for (let i = 0; i <= curMonth; i++) {
        const p  = planned[i] || 0;
        const d  = done[i]    || 0;
        const ph = Math.round((p / max) * 130);
        const dh = Math.round((d / max) * 130);
        const isNow = i === curMonth;

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
        lbl.textContent = months[i];
        lbl.style.color = isNow ? '#3b82f6' : '#4b5563';
        lbl.style.fontWeight = isNow ? '500' : '400';
        labelsEl.appendChild(lbl);
    }
})();
</script>

<?php include 'footer.php'; ?>