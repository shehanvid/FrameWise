<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

require 'dbh.inc.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: index.php");
    exit();
}

// ── Fetch shoot result ─────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM shoot_results WHERE id = ? AND user_id = ?");
$uid  = $_SESSION['userid'];
$stmt->bind_param("ii", $id, $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header("Location: index.php");
    exit();
}

// ── Parse stored data ──────────────────────────────────────────────────────
$dt_obj        = new DateTime($row['shoot_datetime']);
$date_display  = $dt_obj->format('l, F j Y \a\t g:i A');
$gender        = $row['gender'] ?? 'female';
$body_analysis = $row['body_analysis'] ? json_decode($row['body_analysis'], true) : null;
$equipment     = $row['equipment']     ? json_decode($row['equipment'],     true) : [];
$ai_plan       = $row['ai_plan']       ? json_decode($row['ai_plan'],       true) : [];

// ── Shot list helper ───────────────────────────────────────────────────────
function getShotListLocal(string $shoot_type): array {
    $lists = [
        'portrait'  => ['Wide establishing shot','Medium 3/4 body','Close-up face','Eye-level gaze','Over-shoulder look','Environmental context','Candid laugh','Detail — hands/accessories'],
        'fashion'   => ['Full-length runway','3/4 editorial','Close crop — outfit detail','Movement / walk','Sitting pose','Against wall — graphic','Low angle power shot','Overhead flat-lay accessories'],
        'product'   => ['Hero shot — clean white','Lifestyle in use','Detail close-up','Angle shot — 45°','Scale reference','Packaging / unboxing','Dark moody variant','Color variant'],
        'street'    => ['Wide environmental','Subject in context','Candid walk','Reflection shot','Geometry + subject','Silhouette','Window light','Motion blur'],
        'landscape' => ['Wide panoramic','Foreground interest','Leading lines','Golden light','Reflection','Intimate detail','Long exposure water','Milky Way / stars'],
        'wedding'   => ['First look','Detail — rings/bouquet','Ceremony wide','Kiss','Candid emotion','Bridal party','Reception dance','Golden hour couple'],
    ];
    $key = strtolower($shoot_type);
    return $lists[$key] ?? $lists['portrait'];
}

$shot_list = getShotListLocal($row['shoot_type']);

// ── Flags — did we already save these to DB? ───────────────────────────────
$conditions_saved = !is_null($row['sun_altitude']);
$weather_saved    = !is_null($row['weather_temp']);
$poses_saved      = !empty($ai_plan);
$tips_saved       = !is_null($row['director_tips']);

$director_tips    = $tips_saved ? json_decode($row['director_tips'], true) : null;

// Convenience display values used in the HTML below
$plan = [
    'location'   => $row['location'],
    'datetime'   => $date_display,
    'shoot_type' => ucfirst($row['shoot_type']),
    'mood'       => ucfirst($row['mood']),
    'outfit'     => $row['outfit_colour'] ?: '—',
];

include 'header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<link rel="stylesheet" href="../assets/css/view-plan-styles.css">

<div class="w-full flex justify-center px-4 pt-20 pb-10">
<div class="w-full max-w-screen-xl mx-auto">

<!-- TOP BAR -->
<div class="sp-topbar">
    <div class="sp-topbar-left">
        <a href="../index.php" class="sp-back-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="13" height="13">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Back to planner
        </a>
        <div class="sp-session-badge">
            <div class="sp-dot"></div>
            Shoot plan ready
        </div>
    </div>
    <button class="sp-export-btn" onclick="window.print()">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="13" height="13">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12M12 16.5V3"/>
        </svg>
        Export PDF
    </button>
</div>

<!-- META STRIP -->
<div class="sp-meta-strip">
    <div class="sp-meta-pill">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="12" height="12">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
        </svg>
        <?= htmlspecialchars($plan['location']) ?>
    </div>
    <div class="sp-meta-pill">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="12" height="12">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25"/>
        </svg>
        <span><?= htmlspecialchars($plan['datetime']) ?></span>
    </div>
    <div class="sp-meta-pill">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="12" height="12">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23C4.806 7.284 4.43 7.342 4.052 7.405 2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169"/>
        </svg>
        <span><?= htmlspecialchars($plan['shoot_type']) ?></span>
        &nbsp;·&nbsp;
        <span><?= htmlspecialchars($plan['mood']) ?></span>
    </div>
</div>

<!-- CARD GRID -->
<div class="sp-grid" id="sp-grid">

    <!-- WEATHER -->
    <div class="sp-card" id="weather-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#1a1209;border:0.5px solid #3a2d10;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Weather Conditions</div>
                <div class="sp-card-sub" id="weather-sub">
                    <?php if ($weather_saved): ?>
                        Saved · <?= htmlspecialchars($plan['location']) ?>
                    <?php else: ?>
                        Fetching live data…
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="sp-card-body" id="weather-body">
            <?php if ($weather_saved): ?>
                <div class="sp-weather-big"><?= round($row['weather_temp']) ?>°</div>
                <div class="sp-weather-cond"><?= htmlspecialchars($row['weather_condition']) ?></div>
                <div class="sp-weather-grid">
                    <div class="sp-weather-stat"><div class="sp-weather-stat-label">Humidity</div><div class="sp-weather-stat-val"><?= $row['weather_humidity'] ?>%</div></div>
                    <div class="sp-weather-stat"><div class="sp-weather-stat-label">Wind</div><div class="sp-weather-stat-val"><?= $row['weather_wind'] ?> km/h</div></div>
                    <div class="sp-weather-stat"><div class="sp-weather-stat-label">Cloud cover</div><div class="sp-weather-stat-val"><?= $row['weather_clouds'] ?>%</div></div>
                    <div class="sp-weather-stat"><div class="sp-weather-stat-label">Rain chance</div><div class="sp-weather-stat-val"><?= $row['weather_rain_chance'] ?>%</div></div>
                </div>
            <?php else: ?>
                <div style="color:#6b7280;font-size:12px;padding:12px 0;">Loading weather…</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SHOOT SCORE -->
    <div class="sp-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#0a1a10;border:0.5px solid #0f3d20;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Shoot Score</div>
                <div class="sp-card-sub">Overall viability</div>
            </div>
        </div>
        <div class="sp-card-body">
            <div class="sp-score-wrap">
                <div class="sp-score-ring">
                    <svg width="72" height="72" viewBox="0 0 72 72">
                        <circle cx="36" cy="36" r="30" fill="none" stroke="#1a1a1a" stroke-width="5"/>
                        <circle id="score-ring-fill" cx="36" cy="36" r="30" fill="none" stroke="#22c55e" stroke-width="5"
                            stroke-dasharray="188.4" stroke-dashoffset="26" stroke-linecap="round"/>
                    </svg>
                    <div class="sp-score-val" id="score-num">—</div>
                </div>
                <div class="sp-score-details">
                    <div class="sp-score-label">Breakdown</div>
                    <div class="sp-score-bar-row">
                        <div class="sp-score-bar-label">Lighting</div>
                        <div class="sp-score-bar-track"><div class="sp-score-bar-fill" id="lighting-score-bar" style="width:0%;background:#22c55e;"></div></div>
                        <div class="sp-score-bar-val" id="lighting-score-val">—</div>
                    </div>
                    <div class="sp-score-bar-row">
                        <div class="sp-score-bar-label">Weather</div>
                        <div class="sp-score-bar-track"><div class="sp-score-bar-fill" id="weather-score-bar" style="width:<?= $weather_saved ? max(0,min(100, 100 - $row['weather_clouds'] - ($row['weather_rain_chance']*0.5))) : 0 ?>%;background:#3b82f6;"></div></div>
                        <div class="sp-score-bar-val" id="weather-score-val"><?= $weather_saved ? round(max(0,min(100, 100 - $row['weather_clouds'] - ($row['weather_rain_chance']*0.5)))) : '—' ?></div>
                    </div>
                    <div class="sp-score-bar-row">
                        <div class="sp-score-bar-label">Gear &amp; prep</div>
                        <div class="sp-score-bar-track"><div class="sp-score-bar-fill" id="sun-score-bar" style="width:0%;background:#f59e0b;"></div></div>
                        <div class="sp-score-bar-val" id="sun-score-val">—</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GOLDEN HOUR -->
    <div class="sp-card" id="golden-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#1e0f00;border:0.5px solid #4a2d00;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#fb923c" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Golden &amp; Blue Hour</div>
                <div class="sp-card-sub">
                    <?php if ($conditions_saved): ?>
                        Saved · shoot location
                    <?php else: ?>
                        SunCalc · shoot location
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="sp-card-body" id="golden-body">
            <?php if ($conditions_saved): ?>
                <?php
                $gh_s = $row['golden_hour_start'];
                $gh_e = $row['golden_hour_end'];
                $bh_s = $row['blue_hour_start'];
                $bh_e = $row['blue_hour_end'];
                $shoot_ts     = strtotime($row['shoot_datetime']);
                $golden_start = strtotime(date('Y-m-d ') . $gh_s);
                $blue_end     = strtotime(date('Y-m-d ') . $bh_e);
                $window       = max(1, $blue_end - $golden_start);
                $window_pct   = min(100, max(0, round(($shoot_ts - $golden_start) / $window * 100)));
                ?>
                <div class="sp-hour-row">
                    <div class="sp-hour-dot" style="background:#fb923c;box-shadow:0 0 5px #fb923c66;"></div>
                    <div class="sp-hour-name">Golden Hour</div>
                    <div class="sp-hour-time"><?= htmlspecialchars($gh_s) ?> – <?= htmlspecialchars($gh_e) ?></div>
                    <?php if ($row['is_golden_hour']): ?>
                        <div class="sp-hour-badge" style="background:#1e0f00;color:#fb923c;border:0.5px solid #fb923c44;">At shoot</div>
                    <?php endif; ?>
                </div>
                <div class="sp-hour-row">
                    <div class="sp-hour-dot" style="background:#60a5fa;box-shadow:0 0 5px #60a5fa66;"></div>
                    <div class="sp-hour-name">Blue Hour</div>
                    <div class="sp-hour-time"><?= htmlspecialchars($bh_s) ?> – <?= htmlspecialchars($bh_e) ?></div>
                    <?php if ($row['is_blue_hour']): ?>
                        <div class="sp-hour-badge" style="background:#0c1a2e;color:#60a5fa;border:0.5px solid #60a5fa44;">At shoot</div>
                    <?php elseif (!$row['is_golden_hour']): ?>
                        <div class="sp-hour-badge" style="background:#0c1a2e;color:#60a5fa;border:0.5px solid #60a5fa44;">Soon</div>
                    <?php endif; ?>
                </div>
                <div class="sp-hour-row">
                    <div class="sp-hour-dot" style="background:#6b7280;"></div>
                    <div class="sp-hour-name">Sunset</div>
                    <div class="sp-hour-time"><?= htmlspecialchars($gh_e) ?></div>
                    <div></div>
                </div>
                <div class="sp-tl-track">
                    <div class="sp-tl-fill" style="width:<?= $window_pct ?>%;background:linear-gradient(90deg,#fb923c,#fbbf24);"></div>
                </div>
                <div class="sp-tl-labels">
                    <span class="sp-tl-label"><?= htmlspecialchars($gh_s) ?></span>
                    <span class="sp-tl-label" style="color:#fb923c;font-weight:500;">
                        <?= ($row['is_golden_hour'] || $row['is_blue_hour']) ? 'NOW →' : 'SHOOT →' ?>
                    </span>
                    <span class="sp-tl-label"><?= htmlspecialchars($bh_e) ?></span>
                </div>
            <?php else: ?>
                <div style="color:#6b7280;font-size:12px;padding:12px 0;">Calculating sun times…</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SUN DIRECTION -->
    <div class="sp-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#1a1209;border:0.5px solid #3a2d10;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#fbbf24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Sun Position</div>
                <div class="sp-card-sub" id="sun-pos-sub">
                    <?php if ($conditions_saved): ?>
                        <?= round($row['sun_azimuth']) ?>° azimuth at shoot time
                    <?php else: ?>
                        Direction &amp; altitude
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="sp-card-body">
            <div class="sp-compass">
                <div class="sp-compass-ring" id="compass-ring">
                    <span class="sp-compass-label sp-compass-n">N</span>
                    <span class="sp-compass-label sp-compass-s">S</span>
                    <span class="sp-compass-label sp-compass-e">E</span>
                    <span class="sp-compass-label sp-compass-w">W</span>
                    <div style="position:relative;display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
                        <div id="sun-arrow"    style="position:absolute;width:2px;height:42px;background:linear-gradient(to top,transparent,#f59e0b);transform-origin:bottom center;bottom:50%;left:calc(50% - 1px);<?= $conditions_saved ? 'transform:rotate('.$row['sun_azimuth'].'deg)' : '' ?>"></div>
                        <div id="shadow-arrow" style="position:absolute;width:2px;height:32px;background:linear-gradient(to top,transparent,#60a5fa);transform-origin:bottom center;bottom:50%;left:calc(50% - 1px);<?= $conditions_saved ? 'transform:rotate('.(fmod((float)$row['sun_azimuth']+180,360)).'deg)' : '' ?>"></div>
                        <div class="sp-compass-center"></div>
                    </div>
                </div>
            </div>
            <div class="sp-sun-stats">
                <div class="sp-sun-stat">
                    <div class="sp-sun-stat-label">Sun direction</div>
                    <div class="sp-sun-stat-val" id="sun-azimuth"><?= $conditions_saved ? htmlspecialchars($row['sun_azimuth']).'°' : '—' ?></div>
                </div>
                <div class="sp-sun-stat">
                    <div class="sp-sun-stat-label">Altitude</div>
                    <div class="sp-sun-stat-val" id="sun-altitude"><?= $conditions_saved ? htmlspecialchars($row['sun_altitude']).'°' : '—' ?></div>
                </div>
                <div class="sp-sun-stat">
                    <div class="sp-sun-stat-label">Shadow dir</div>
                    <div class="sp-shadow-stat-val" id="shadow-dir"><?= $conditions_saved ? round(fmod((float)$row['sun_azimuth']+180,360)).'°' : '—' ?></div>
                </div>
                <div class="sp-sun-stat">
                    <div class="sp-sun-stat-label">Shadow len</div>
                    <div class="sp-shadow-stat-val" id="shadow-len"><?= $conditions_saved ? htmlspecialchars($row['shadow_length']) : '—' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- CAMERA SETTINGS -->
    <div class="sp-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#0f1520;border:0.5px solid #1e3a5f;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#60a5fa" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Camera Settings</div>
                <div class="sp-card-sub"><?= htmlspecialchars($plan['shoot_type']) ?> · <?= htmlspecialchars($plan['mood']) ?> mood</div>
            </div>
        </div>
        <div class="sp-card-body" id="camera-body">
            <div style="color:#6b7280;font-size:12px;padding:12px 0;">Calculating from sun position…</div>
        </div>
    </div>

    <!-- NEARBY SALONS -->
    <div class="sp-card" id="salons-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#0a1a10;border:0.5px solid #0f3d20;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Nearby Salons</div>
                <div class="sp-card-sub" id="salons-sub">Within 3 km of shoot location</div>
            </div>
        </div>
        <div class="sp-card-body" id="salons-body">
            <div style="color:#6b7280;font-size:12px;padding:12px 0;">Finding nearby salons…</div>
        </div>
    </div>

    <!-- POSE RECOMMENDATIONS -->
    <div class="sp-card wide">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#160d1a;border:0.5px solid #3d1f4a;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#a78bfa" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Pose Recommendations</div>
                <div class="sp-card-sub">Based on body &amp; face analysis · <?= htmlspecialchars($plan['shoot_type']) ?> shoot</div>
            </div>
        </div>
        <div class="sp-card-body" style="display:flex;gap:10px;align-items:start;overflow-x:auto;padding-bottom:8px;scroll-snap-type:x mandatory;" id="pose-body">
            <?php if ($poses_saved): ?>
                <?php foreach ($ai_plan as $i => $p): ?>
                <div class="sp-pose-card" style="flex-direction:column;gap:8px;">
                    <img src="<?= htmlspecialchars($p['image'] ?? '') ?>"
                         alt="<?= htmlspecialchars($p['name']  ?? '') ?>"
                         onclick="openLightbox(this.src, this.alt)"
                         style="width:100%;aspect-ratio:2/3;object-fit:cover;border-radius:8px;border:0.5px solid #1a1a1a;display:block;cursor:pointer;">
                    <div>
                        <div class="sp-pose-num">0<?= $i + 1 ?></div>
                        <div class="sp-pose-name"><?= htmlspecialchars($p['name']        ?? '') ?></div>
                        <div class="sp-pose-desc"><?= htmlspecialchars($p['description'] ?? '') ?></div>
                        <div class="sp-pose-tag"><?= htmlspecialchars($p['tags']         ?? '') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color:#6b7280;font-size:12px;padding:12px 0;grid-column:span 5;">Loading poses…</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- POSE CHECKLIST -->
    <div class="sp-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#160d1a;border:0.5px solid #3d1f4a;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#a78bfa" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Pose Checklist</div>
                <div class="sp-card-sub" id="pose-checklist-sub">
                    <?= $poses_saved ? count($ai_plan) . ' poses planned' : 'Loading poses…' ?>
                </div>
            </div>
        </div>
        <div class="sp-card-body" id="pose-checklist-body">
            <?php if ($poses_saved): ?>
                <?php foreach ($ai_plan as $pose): ?>
                <div class="sp-shot-item" onclick="toggleShot(this)">
                    <div class="sp-shot-cb">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="sp-shot-text"><?= htmlspecialchars($pose['name'] ?? '') ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color:#6b7280;font-size:12px;padding:12px 0;">Loading poses…</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AI DIRECTOR TIPS -->
    <div class="sp-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#1a1209;border:0.5px solid #3a2d10;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">AI Director Tips</div>
                <div class="sp-card-sub">Personalized for this shoot</div>
            </div>
        </div>
        <div class="sp-card-body" id="tips-body">
            <?php if ($tips_saved && $director_tips): ?>
                <?php foreach ($director_tips as $tip): ?>
                <div class="sp-tip">
                    <div class="sp-tip-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="sp-tip-title"><?= htmlspecialchars($tip['title'] ?? '') ?></div>
                        <div class="sp-tip-text"><?= htmlspecialchars($tip['text']  ?? '') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color:#6b7280;font-size:12px;padding:12px 0;">Generating tips…</div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /sp-grid -->

<!-- CHATBOT -->
<div class="sp-chatbot-wrap" style="margin-bottom:100px;">
    <div class="sp-chatbot-header">
        <div class="sp-chatbot-header-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
            </svg>
        </div>
        <div>
            <div class="sp-chatbot-header-title">AI Photography Director</div>
            <div class="sp-chatbot-header-sub">Ask anything about your shoot — poses, lighting, camera, creative direction</div>
        </div>
        <div class="sp-chatbot-header-badge">
            <div class="sp-dot" style="background:#a855f7;animation:spPulse 2s infinite;"></div>
            Live
        </div>
    </div>
    <div class="sp-chat-messages" id="sp-chat-messages">
        <div class="sp-msg ai">
            <div class="sp-msg-avatar">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
            </div>
            <div class="sp-msg-bubble">
                Hey! I've reviewed your shoot plan — a <strong><?= htmlspecialchars($plan['shoot_type']) ?></strong> shoot
                at <strong><?= htmlspecialchars($plan['location']) ?></strong> with a
                <strong><?= htmlspecialchars($plan['mood']) ?></strong> mood.
                I'm ready to help with poses, lighting setups, camera settings, or any creative direction.
                What do you want to explore?
            </div>
        </div>
    </div>
    <div class="sp-chat-suggestions" id="sp-suggestions">
        <button class="sp-suggestion-btn" onclick="sendSuggestion(this)">Best poses for <?= htmlspecialchars($plan['shoot_type']) ?> shoot?</button>
        <button class="sp-suggestion-btn" onclick="sendSuggestion(this)">How do I use the golden hour light?</button>
        <button class="sp-suggestion-btn" onclick="sendSuggestion(this)">What lens should I use today?</button>
        <button class="sp-suggestion-btn" onclick="sendSuggestion(this)">Color grading tips for <?= htmlspecialchars($plan['mood']) ?> mood?</button>
    </div>
    <div class="sp-chat-input-bar">
        <textarea id="sp-chat-input" class="sp-chat-textarea"
            placeholder="Ask your AI photography director…"
            rows="1"
            onkeydown="handleChatKey(event)"
            oninput="autoResize(this)"></textarea>
        <button class="sp-chat-send" id="sp-send-btn" onclick="sendChatMessage()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
            </svg>
        </button>
    </div>
</div>

<!-- LIGHTBOX -->
<div id="pose-lightbox" onclick="closeLightbox(event)">
    <div id="pose-lightbox-close" onclick="document.getElementById('pose-lightbox').classList.remove('active')">✕</div>
    <img id="pose-lightbox-img" src="" alt="">
    <div id="pose-lightbox-caption"></div>
</div>

</div>
</div>

<!-- ── External libs ──────────────────────────────────────────────────── -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/suncalc/1.9.0/suncalc.min.js"></script>

<!-- ── PHP → JS bridge (must come before the module scripts) ─────────── -->
<script>
// Page-level flags
const RESULT_ID        = <?= $id ?>;
const CONDITIONS_SAVED = <?= $conditions_saved ? 'true' : 'false' ?>;
const WEATHER_SAVED    = <?= $weather_saved    ? 'true' : 'false' ?>;
const POSES_SAVED      = <?= $poses_saved      ? 'true' : 'false' ?>;
const TIPS_SAVED       = <?= $tips_saved       ? 'true' : 'false' ?>;
const SAVED_TIPS       = <?= json_encode($director_tips) ?>;
const USER_INITIAL     = <?= json_encode(strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))) ?>;

// Full shoot context — read by all modules
const SHOOT_CONTEXT = {
    location:       <?= json_encode($row['location']) ?>,
    lat:            <?= json_encode($row['location_lat']) ?>,
    lng:            <?= json_encode($row['location_lng']) ?>,
    raw_datetime:   <?= json_encode($row['shoot_datetime']) ?>,
    datetime:       <?= json_encode($date_display) ?>,
    shoot_type:     <?= json_encode($row['shoot_type']) ?>,
    mood:           <?= json_encode($row['mood']) ?>,
    outfit:         <?= json_encode($row['outfit_colour']) ?>,
    gender:         <?= json_encode($row['gender']) ?>,
    camera_type:    <?= json_encode($row['camera_type']) ?>,
    experience:     <?= json_encode($row['experience']) ?>,
    lighting_style: <?= json_encode($row['lighting_style']) ?>,
    platform:       <?= json_encode($row['platform']) ?>,
    output_style:   <?= json_encode($row['output_style']) ?>,
    orientation:    <?= json_encode($row['orientation']) ?>,
    equipment:      <?= json_encode($equipment) ?>,
    ai_notes:       <?= json_encode($row['ai_notes']) ?>,
    body_analysis:  <?= json_encode($body_analysis) ?>,
};
</script>

<!-- ── App modules (order matters) ───────────────────────────────────── -->
<script src="scripts/shoot-plan.js"></script>
<script src="scripts/shoot-plan-suncalc.js"></script>
<script src="scripts/shoot-plan-weather.js"></script>
<script src="scripts/shoot-plan-salons.js"></script>
<script src="scripts/shoot-plan-poses.js"></script>
<script src="scripts/shoot-plan-chatbot.js"></script>

<?php include 'footer.php'; ?>