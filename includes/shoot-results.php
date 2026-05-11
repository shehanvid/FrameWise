<?php
// ── All PHP logic FIRST, before any includes that output HTML ──────────────
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

include 'header.php';
// ── Process form data ──────────────────────────────────────────────────────
$location     = trim($_POST['location']     ?? '');
$location_lat = trim($_POST['location_lat'] ?? '');
$location_lng = trim($_POST['location_lng'] ?? '');
$datetime     = trim($_POST['datetime']     ?? '');
$shoot_type   = trim($_POST['shoot_type']   ?? '');
$mood         = trim($_POST['mood']         ?? '');
$outfit       = trim($_POST['outfit']       ?? '');
$gear         = trim($_POST['gear']         ?? '');
$environment  = trim($_POST['environment']  ?? 'outdoor');
$backdrop     = trim($_POST['backdrop']     ?? '');

$errors = [];
if ($location   === '') $errors[] = 'Location is required.';
if ($datetime   === '') $errors[] = 'Date & time is required.';
if ($shoot_type === '') $errors[] = 'Shoot type is required.';
if ($mood       === '') $errors[] = 'Mood is required.';

$image_name = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $image_name = basename($_FILES['image']['name']);
}

if (!empty($errors)) {
    header("Location: index.php");
    exit();
}

$dt_obj       = new DateTime($datetime);
$date_display = $dt_obj->format('l, F j Y \a\t g:i A');

$plan = [
    'location'     => htmlspecialchars($location),
    'location_lat' => $location_lat,
    'location_lng' => $location_lng,
    'datetime'     => $date_display,
    'shoot_type'   => ucfirst(htmlspecialchars($shoot_type)),
    'mood'         => ucfirst(htmlspecialchars($mood)),
    'outfit'       => $outfit ? htmlspecialchars($outfit) : '—',
    'image_name'   => $image_name,
    'shot_list'    => getShotList($conn, $shoot_type),
    'gear'         => $gear,
    'environment'  => $environment,
    'backdrop'     => $backdrop,
];

// ── NOW it's safe to output HTML ───────────────────────────────────────────
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">

/* ... */
<style>
/* ── Reset / base ─────────────────────────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&family=Syne:wght@700&display=swap');
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Top bar ──────────────────────────────────────────────────────────────── */
.sp-topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.5rem; flex-wrap: wrap; gap: 10px;
}
.sp-topbar-left { display: flex; align-items: center; gap: 10px; }
.sp-back-btn {
    display: flex; align-items: center; gap: 6px;
    background: #161616; border: 0.5px solid #2a2a2a; border-radius: 10px;
    padding: 8px 14px; color: #9ca3af; font-size: 12px;
    font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all .2s;
    text-decoration: none;
}
.sp-back-btn:hover { border-color: #3b82f6; color: #3b82f6; }
.sp-session-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #1c1c1c; border: 0.5px solid #2e2e2e; border-radius: 100px;
    padding: 5px 12px; font-size: 11px; color: #e5e7eb;
    letter-spacing: .08em; text-transform: uppercase;
}
.sp-dot { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; animation: spPulse 2s infinite; }
@keyframes spPulse { 0%,100%{opacity:1} 50%{opacity:.3} }
.sp-export-btn {
    display: flex; align-items: center; gap: 6px;
    background: #3b82f6; border: none; border-radius: 10px;
    padding: 9px 16px; color: #0a0a0a;
    font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700;
    cursor: pointer; transition: all .2s;
}
.sp-export-btn:hover { background: #2563eb; }

/* ── Meta strip ───────────────────────────────────────────────────────────── */
.sp-meta-strip { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 1.5rem; }
.sp-meta-pill {
    display: flex; align-items: center; gap: 6px;
    background: #111; border: 0.5px solid #222; border-radius: 8px;
    padding: 6px 12px; font-size: 12px; color: #9ca3af;
}
.sp-meta-pill span { color: #e5e7eb; font-weight: 500; }

/* ── Card grid ────────────────────────────────────────────────────────────── */
.sp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
}
.sp-card {
    background: #111; border: 0.5px solid #1e1e1e; border-radius: 16px;
    overflow: hidden; transition: border-color .2s;
}
.sp-card:hover { border-color: #2a2a2a; }
.sp-card.wide { grid-column: span 2; }

/* ── Card header ──────────────────────────────────────────────────────────── */
.sp-card-header {
    padding: 14px 16px 10px; border-bottom: 0.5px solid #1a1a1a;
    display: flex; align-items: center; gap: 10px;
}
.sp-card-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.sp-card-icon svg { width: 16px; height: 16px; }
.sp-card-title { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; color: #f0ede8; }
.sp-card-sub { font-size: 11px; color: #6b7280; margin-top: 1px; }
.sp-card-body { padding: 14px 16px; }

/* ── Weather ──────────────────────────────────────────────────────────────── */
.sp-weather-big { font-family: 'Bebas Neue', sans-serif; font-size: 52px; color: #fff; line-height: 1; margin-bottom: 4px; }
.sp-weather-cond { font-size: 13px; color: #9ca3af; margin-bottom: 12px; }
.sp-weather-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.sp-weather-stat { background: #0d0d0d; border: 0.5px solid #1a1a1a; border-radius: 8px; padding: 8px 10px; }
.sp-weather-stat-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 3px; }
.sp-weather-stat-val { font-size: 15px; font-weight: 500; color: #e5e7eb; }

/* ── Score ring ───────────────────────────────────────────────────────────── */
.sp-score-wrap { display: flex; align-items: center; gap: 16px; padding: 4px 0; }
.sp-score-ring { position: relative; flex-shrink: 0; }
.sp-score-ring svg { transform: rotate(-90deg); }
.sp-score-val {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-family: 'Bebas Neue', sans-serif; font-size: 24px; color: #fff;
}
.sp-score-details { flex: 1; }
.sp-score-label { font-size: 11px; color: #6b7280; margin-bottom: 6px; }
.sp-score-bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
.sp-score-bar-label { font-size: 11px; color: #6b7280; width: 60px; flex-shrink: 0; }
.sp-score-bar-track { flex: 1; height: 3px; background: #1a1a1a; border-radius: 2px; overflow: hidden; }
.sp-score-bar-fill { height: 100%; border-radius: 2px; }
.sp-score-bar-val { font-size: 11px; color: #9ca3af; width: 24px; text-align: right; flex-shrink: 0; }

/* ── Golden hour ──────────────────────────────────────────────────────────── */
.sp-hour-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 0; border-bottom: 0.5px solid #161616;
}
.sp-hour-row:last-child { border-bottom: none; }
.sp-hour-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sp-hour-name { font-size: 12px; color: #e5e7eb; margin-left: 8px; flex: 1; }
.sp-hour-time { font-size: 12px; color: #9ca3af; font-weight: 500; }
.sp-hour-badge { font-size: 10px; padding: 2px 8px; border-radius: 100px; margin-left: 8px; }
.sp-tl-track { height: 4px; background: #1a1a1a; border-radius: 2px; position: relative; overflow: hidden; margin-top: 14px; }
.sp-tl-fill { height: 100%; border-radius: 2px; position: absolute; }
.sp-tl-labels { display: flex; justify-content: space-between; margin-top: 6px; }
.sp-tl-label { font-size: 10px; color: #6b7280; }

/* ── Sun compass ──────────────────────────────────────────────────────────── */
.sp-compass { display: flex; justify-content: center; margin: 10px 0; }
.sp-compass-ring {
    width: 120px; height: 120px; border-radius: 50%;
    border: 0.5px solid #222; background: #0d0d0d;
    position: relative; display: flex; align-items: center; justify-content: center;
}
.sp-compass-label { position: absolute; font-size: 10px; color: #4b5563; font-weight: 500; }
.sp-compass-n { top: 6px; left: 50%; transform: translateX(-50%); }
.sp-compass-s { bottom: 6px; left: 50%; transform: translateX(-50%); }
.sp-compass-e { right: 8px; top: 50%; transform: translateY(-50%); }
.sp-compass-w { left: 8px; top: 50%; transform: translateY(-50%); }
.sp-compass-center { width: 8px; height: 8px; background: #f59e0b; border-radius: 50%; position: relative; z-index: 2; }
.sp-sun-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; }
.sp-sun-stat { text-align: center; background: #0d0d0d; border: 0.5px solid #1a1a1a; border-radius: 8px; padding: 8px; }
.sp-sun-stat-label { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
.sp-sun-stat-val { font-size: 13px; color: #f59e0b; font-weight: 500; }

/* ── Camera settings ──────────────────────────────────────────────────────── */
.sp-cam-setting {
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 0; border-bottom: 0.5px solid #161616;
}
.sp-cam-setting:last-child { border-bottom: none; }
.sp-cam-label { font-size: 12px; color: #6b7280; display: flex; align-items: center; gap: 6px; }
.sp-cam-val { font-size: 14px; font-weight: 500; color: #3b82f6; font-family: 'Bebas Neue', sans-serif; letter-spacing: .04em; }
.sp-cam-badge { font-size: 10px; background: #0f1520; border: 0.5px solid #1e3a5f; color: #60a5fa; border-radius: 4px; padding: 2px 6px; margin-left: 6px; }

/* ── Color harmony ────────────────────────────────────────────────────────── */
.sp-palette-row { display: flex; gap: 6px; margin-bottom: 10px; }
.sp-swatch-block { flex: 1; height: 40px; border-radius: 8px; }
.sp-swatch-label { font-size: 9px; color: #6b7280; text-align: center; margin-top: 4px; text-transform: uppercase; letter-spacing: .04em; }
.sp-harmony-tip { background: #0d0d0d; border: 0.5px solid #1a1a1a; border-radius: 8px; padding: 10px 12px; font-size: 12px; color: #9ca3af; line-height: 1.5; }
.sp-harmony-tip strong { color: #e5e7eb; }

/* ── Pose cards ───────────────────────────────────────────────────────────── */
.sp-pose-card {
    background: #0d0d0d; border: 0.5px solid #1a1a1a; border-radius: 10px;
    padding: 10px 12px; display: flex; align-items: flex-start; gap: 10px;
}
.sp-pose-num { font-family: 'Bebas Neue', sans-serif; font-size: 22px; color: #1e2030; line-height: 1; flex-shrink: 0; width: 22px; }
.sp-pose-name { font-size: 12px; color: #e5e7eb; font-weight: 500; margin-bottom: 3px; }
.sp-pose-desc { font-size: 11px; color: #6b7280; line-height: 1.4; }
.sp-pose-tag { display: inline-flex; font-size: 9px; background: #130d1f; border: 0.5px solid #3d1f7a44; color: #a78bfa; border-radius: 4px; padding: 2px 6px; margin-top: 5px; }

/* ── Shot checklist ───────────────────────────────────────────────────────── */
.sp-shot-item { display: flex; align-items: center; gap: 8px; padding: 7px 0; border-bottom: 0.5px solid #161616; cursor: pointer; }
.sp-shot-item:last-child { border-bottom: none; }
.sp-shot-cb {
    width: 16px; height: 16px; border: 0.5px solid #2a2a2a; border-radius: 4px;
    background: #0a0a0a; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: all .2s;
}
.sp-shot-cb.done { background: #3b82f6; border-color: #3b82f6; }
.sp-shot-cb svg { width: 9px; height: 9px; color: #fff; opacity: 0; transition: opacity .15s; }
.sp-shot-cb.done svg { opacity: 1; }
.sp-shot-text { font-size: 12px; color: #9ca3af; transition: all .2s; user-select: none; }
.sp-shot-text.done { color: #374151; text-decoration: line-through; }

/* ── AI tips ──────────────────────────────────────────────────────────────── */
.sp-tip { display: flex; gap: 10px; padding: 10px 0; border-bottom: 0.5px solid #161616; }
.sp-tip:last-child { border-bottom: none; }
.sp-tip-icon { width: 28px; height: 28px; background: #161209; border: 0.5px solid #3a2d10; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sp-tip-icon svg { width: 14px; height: 14px; color: #f59e0b; }
.sp-tip-title { font-size: 12px; color: #e5e7eb; font-weight: 500; margin-bottom: 2px; }
.sp-tip-text { font-size: 11px; color: #6b7280; line-height: 1.45; }

/* ── Chatbot section ──────────────────────────────────────────────────────── */
.sp-chatbot-wrap {
    margin-top: 24px;
    background: #111;
    border: 0.5px solid #1e1e1e;
    border-radius: 20px;
    overflow: hidden;
}
.sp-chatbot-header {
    padding: 16px 20px;
    border-bottom: 0.5px solid #1a1a1a;
    display: flex;
    align-items: center;
    gap: 12px;
}
.sp-chatbot-header-icon {
    width: 36px; height: 36px;
    background: #160d1a; border: 0.5px solid #4a1a6e;
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
}
.sp-chatbot-header-icon svg { width: 17px; height: 17px; color: #c084fc; }
.sp-chatbot-header-title { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; color: #f0ede8; }
.sp-chatbot-header-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }
.sp-chatbot-header-badge {
    margin-left: auto;
    display: flex; align-items: center; gap: 5px;
    background: #1c1c1c; border: 0.5px solid #2e2e2e;
    border-radius: 100px; padding: 4px 10px;
    font-size: 10px; color: #9ca3af; letter-spacing: .06em; text-transform: uppercase;
}
.sp-chatbot-header-badge .dot { background: #a855f7; }

/* Messages container */
.sp-chat-messages {
    padding: 16px 20px;
    min-height: 260px;
    max-height: 440px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
    scroll-behavior: smooth;
}
.sp-chat-messages::-webkit-scrollbar { width: 4px; }
.sp-chat-messages::-webkit-scrollbar-track { background: transparent; }
.sp-chat-messages::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 2px; }

/* Message bubbles */
.sp-msg { display: flex; gap: 8px; max-width: 86%; }
.sp-msg.user { align-self: flex-end; flex-direction: row-reverse; }
.sp-msg.ai   { align-self: flex-start; }

.sp-msg-avatar {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 2px;
}
.sp-msg.ai .sp-msg-avatar { background: #160d1a; border: 0.5px solid #4a1a6e; }
.sp-msg.ai .sp-msg-avatar svg { width: 14px; height: 14px; color: #c084fc; }
.sp-msg.user .sp-msg-avatar { background: #0f1520; border: 0.5px solid #1e3a5f; color: #60a5fa; font-size: 10px; }

.sp-msg-bubble {
    padding: 10px 13px;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.55;
}
.sp-msg.ai .sp-msg-bubble {
    background: #0d0d0d;
    border: 0.5px solid #1a1a1a;
    color: #d1d5db;
    border-top-left-radius: 4px;
}
.sp-msg.user .sp-msg-bubble {
    background: #0f1520;
    border: 0.5px solid #1e3a5f;
    color: #bfdbfe;
    border-top-right-radius: 4px;
}

/* Typing indicator */
.sp-typing { display: flex; gap: 4px; align-items: center; padding: 2px 0; }
.sp-typing span {
    width: 5px; height: 5px; background: #4b5563; border-radius: 50%;
    animation: spTyping 1.2s infinite;
}
.sp-typing span:nth-child(2) { animation-delay: .2s; }
.sp-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes spTyping { 0%,80%,100%{opacity:.3;transform:scale(1)} 40%{opacity:1;transform:scale(1.2)} }

/* Suggested prompts */
.sp-chat-suggestions {
    padding: 0 20px 14px;
    display: flex; gap: 6px; flex-wrap: wrap;
}
.sp-suggestion-btn {
    background: #0d0d0d; border: 0.5px solid #222;
    border-radius: 100px; padding: 5px 12px;
    font-size: 11px; color: #9ca3af;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer; transition: all .2s;
}
.sp-suggestion-btn:hover { border-color: #a855f7; color: #c084fc; background: #0d0a14; }

/* Input bar */
.sp-chat-input-bar {
    padding: 12px 16px;
    border-top: 0.5px solid #1a1a1a;
    display: flex; gap: 8px; align-items: flex-end;
}
.sp-chat-textarea {
    flex: 1;
    background: #0d0d0d;
    border: 0.5px solid #222;
    border-radius: 12px;
    color: #e5e7eb;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 300;
    padding: 10px 14px;
    outline: none;
    resize: none;
    max-height: 120px;
    min-height: 42px;
    overflow-y: auto;
    transition: border-color .2s;
    line-height: 1.5;
}
.sp-chat-textarea:focus { border-color: #a855f7; }
.sp-chat-textarea::placeholder { color: #4b5563; }
.sp-chat-send {
    width: 42px; height: 42px; flex-shrink: 0;
    background: #a855f7; border: none; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .2s;
}
.sp-chat-send:hover { background: #9333ea; }
.sp-chat-send:disabled { background: #1a1a1a; cursor: not-allowed; }
.sp-chat-send svg { width: 16px; height: 16px; color: #fff; }

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 640px) {
    .sp-card.wide { grid-column: span 1; }
    .sp-grid { grid-template-columns: 1fr; }
    .sp-topbar { flex-direction: column; align-items: flex-start; }
}
</style>


<div class="w-full flex justify-center px-4 pt-20 pb-10">
<div class="w-full max-w-screen-xl mx-auto">
<!-- ══════════════════════════════════════════════════════════════════════════
     TOP BAR
═══════════════════════════════════════════════════════════════════════════ -->
<div class="sp-topbar">
    <div class="sp-topbar-left">
        <a href="index.php" class="sp-back-btn">
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

<!-- ══════════════════════════════════════════════════════════════════════════
     META STRIP
═══════════════════════════════════════════════════════════════════════════ -->
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

<!-- ══════════════════════════════════════════════════════════════════════════
     CARD GRID
═══════════════════════════════════════════════════════════════════════════ -->
<div class="sp-grid" id="sp-grid">

    <!-- ── WEATHER ──────────────────────────────────────────────────────── -->
    <div class="sp-card" id="weather-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#1a1209;border:0.5px solid #3a2d10;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Weather Conditions</div>
                <div class="sp-card-sub" id="weather-sub">Fetching live data…</div>
            </div>
        </div>
        <div class="sp-card-body" id="weather-body">
            <div style="color:#6b7280;font-size:12px;padding:12px 0;">Loading weather…</div>
        </div>
    </div>

    <!-- ── SHOOT SCORE ───────────────────────────────────────────────────── -->
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
                    <div class="sp-score-val" id="score-num">86</div>
                </div>
                <div class="sp-score-details">
                    <div class="sp-score-label">Breakdown</div>
                    <div class="sp-score-bar-row">
                        <div class="sp-score-bar-label">Lighting</div>
                        <div class="sp-score-bar-track"><div class="sp-score-bar-fill" style="width:92%;background:#22c55e;"></div></div>
                        <div class="sp-score-bar-val">92</div>
                    </div>
                    <div class="sp-score-bar-row">
                        <div class="sp-score-bar-label">Weather</div>
                        <div class="sp-score-bar-track"><div class="sp-score-bar-fill" id="weather-score-bar" style="width:80%;background:#3b82f6;"></div></div>
                        <div class="sp-score-bar-val" id="weather-score-val">80</div>
                    </div>
                    <div class="sp-score-bar-row">
                        <div class="sp-score-bar-label">Sun angle</div>
                        <div class="sp-score-bar-track"><div class="sp-score-bar-fill" id="sun-score-bar" style="width:78%;background:#f59e0b;"></div></div>
                        <div class="sp-score-bar-val" id="sun-score-val">78</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── GOLDEN HOUR ──────────────────────────────────────────────────── -->
    <div class="sp-card" id="golden-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#1e0f00;border:0.5px solid #4a2d00;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#fb923c" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Golden &amp; Blue Hour</div>
                <div class="sp-card-sub">SunCalc · shoot location</div>
            </div>
        </div>
        <div class="sp-card-body" id="golden-body">
            <div style="color:#6b7280;font-size:12px;padding:12px 0;">Calculating sun times…</div>
        </div>
    </div>

    <!-- ── SUN DIRECTION ────────────────────────────────────────────────── -->
    <div class="sp-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#1a1209;border:0.5px solid #3a2d10;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#fbbf24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Sun Position</div>
                <div class="sp-card-sub" id="sun-pos-sub">Direction &amp; altitude</div>
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
                        <div id="sun-arrow" style="position:absolute;width:2px;height:42px;background:linear-gradient(to top,transparent,#f59e0b);transform-origin:bottom center;bottom:50%;left:calc(50% - 1px);transform:rotate(220deg) translateY(-2px);"></div>
                        <div id="shadow-arrow" style="position:absolute;width:2px;height:30px;background:linear-gradient(to top,transparent,#374151);transform-origin:bottom center;bottom:50%;left:calc(50% - 1px);transform:rotate(40deg) translateY(-2px);"></div>
                        <div class="sp-compass-center"></div>
                    </div>
                </div>
            </div>
            <div class="sp-sun-stats">
                <div class="sp-sun-stat">
                    <div class="sp-sun-stat-label">Sun direction</div>
                    <div class="sp-sun-stat-val" id="sun-azimuth">—</div>
                </div>
                <div class="sp-sun-stat">
                    <div class="sp-sun-stat-label">Altitude</div>
                    <div class="sp-sun-stat-val" id="sun-altitude">—</div>
                </div>
                <div class="sp-sun-stat">
                    <div class="sp-sun-stat-label">Shadow dir</div>
                    <div class="sp-sun-stat-val" id="shadow-dir">—</div>
                </div>
                <div class="sp-sun-stat">
                    <div class="sp-sun-stat-label">Shadow len</div>
                    <div class="sp-sun-stat-val" id="shadow-len">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── CAMERA SETTINGS ──────────────────────────────────────────────── -->
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
            <?php
            // Generate camera settings based on shoot type and mood
            $cam = getCameraSettings($plan['shoot_type'], $plan['mood']);
            foreach ($cam as $setting):
            ?>
            <div class="sp-cam-setting">
                <div class="sp-cam-label"><?= $setting['icon'] ?>&nbsp;<?= $setting['label'] ?></div>
                <div style="display:flex;align-items:center;">
                    <div class="sp-cam-val"><?= $setting['value'] ?></div>
                    <?php if (!empty($setting['badge'])): ?>
                    <div class="sp-cam-badge"><?= $setting['badge'] ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── COLOR HARMONY ────────────────────────────────────────────────── -->
    <div class="sp-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#160d1a;border:0.5px solid #3d1f4a;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#c084fc" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Color Harmony</div>
                <div class="sp-card-sub">Outfit: <?= htmlspecialchars($plan['outfit']) ?></div>
            </div>
        </div>
        <div class="sp-card-body" id="color-body">
            <div style="color:#6b7280;font-size:12px;padding:12px 0;">Generating palette…</div>
        </div>
    </div>

    <!-- ── POSE RECOMMENDATIONS (wide) ──────────────────────────────────── -->
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
        <div class="sp-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;" id="pose-body">
            <div style="color:#6b7280;font-size:12px;padding:12px 0;grid-column:span 2;">Analyzing model image…</div>
        </div>
    </div>

    <!-- ── SHOT CHECKLIST ────────────────────────────────────────────────── -->
    <div class="sp-card">
        <div class="sp-card-header">
            <div class="sp-card-icon" style="background:#0a1a10;border:0.5px solid #0f3d20;">
                <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                </svg>
            </div>
            <div>
                <div class="sp-card-title">Shot Checklist</div>
                <div class="sp-card-sub"><?= htmlspecialchars($plan['shoot_type']) ?> · <?= count($plan['shot_list']) ?> shots planned</div>
            </div>
        </div>
        <div class="sp-card-body" id="shot-list-body">
            <?php foreach ($plan['shot_list'] as $shot): ?>
            <div class="sp-shot-item" onclick="toggleShot(this)">
                <div class="sp-shot-cb">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="sp-shot-text"><?= htmlspecialchars($shot) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── AI DIRECTOR TIPS ──────────────────────────────────────────────── -->
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
            <div style="color:#6b7280;font-size:12px;padding:12px 0;">Generating tips…</div>
        </div>
    </div>

</div><!-- /sp-grid -->


<!-- ══════════════════════════════════════════════════════════════════════════
     AI CHATBOT
═══════════════════════════════════════════════════════════════════════════ -->
<div class="sp-chatbot-wrap" style="margin-bottom: 100px;">

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
            <div class="sp-dot" style="background:#a855f7;"></div>
            Live
        </div>
    </div>

    <!-- Messages -->
    <div class="sp-chat-messages" id="sp-chat-messages">
        <div class="sp-msg ai">
            <div class="sp-msg-avatar">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
            </div>
            <div class="sp-msg-bubble">
                Hey! I've reviewed your shoot plan — a <strong><?= htmlspecialchars($plan['shoot_type']) ?></strong> shoot at <strong><?= htmlspecialchars($plan['location']) ?></strong> with a <strong><?= htmlspecialchars($plan['mood']) ?></strong> mood. I'm ready to help with poses, lighting setups, camera settings, or any creative direction. What do you want to explore?
            </div>
        </div>
    </div>

    <!-- Suggested prompts -->
    <div class="sp-chat-suggestions" id="sp-suggestions">
        <button class="sp-suggestion-btn" onclick="sendSuggestion(this)">Best poses for <?= htmlspecialchars($plan['shoot_type']) ?> shoot?</button>
        <button class="sp-suggestion-btn" onclick="sendSuggestion(this)">How do I use the golden hour light?</button>
        <button class="sp-suggestion-btn" onclick="sendSuggestion(this)">What lens should I use today?</button>
        <button class="sp-suggestion-btn" onclick="sendSuggestion(this)">Color grading tips for <?= htmlspecialchars($plan['mood']) ?> mood?</button>
    </div>

    <!-- Input bar -->
    <div class="sp-chat-input-bar">
        <textarea
            id="sp-chat-input"
            class="sp-chat-textarea"
            placeholder="Ask your AI photography director…"
            rows="1"
            onkeydown="handleChatKey(event)"
            oninput="autoResize(this)"
        ></textarea>
        <button class="sp-chat-send" id="sp-send-btn" onclick="sendChatMessage()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
            </svg>
        </button>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/suncalc/1.9.0/suncalc.min.js"></script>

<script>
// ── Shoot context (for chatbot system prompt) ──────────────────────────────
const SHOOT_CONTEXT = {
    location:   <?= json_encode($plan['location']) ?>,
    lat:        <?= json_encode($plan['location_lat']) ?>,
    lng:        <?= json_encode($plan['location_lng']) ?>,
    datetime:   <?= json_encode($plan['datetime']) ?>,
    shoot_type: <?= json_encode($plan['shoot_type']) ?>,
    mood:       <?= json_encode($plan['mood']) ?>,
    outfit:     <?= json_encode($plan['outfit']) ?>,
    camera_type:   <?= json_encode($_POST['camera_type']  ?? 'DSLR') ?>,
    experience:    <?= json_encode($_POST['experience']   ?? 'intermediate') ?>,
    lighting_style:<?= json_encode($_POST['lighting_style'] ?? 'natural') ?>,
    platform:      <?= json_encode($_POST['platform']     ?? 'instagram') ?>,
};

// ── Checklist toggle ───────────────────────────────────────────────────────
function toggleShot(row) {
    const cb  = row.querySelector('.sp-shot-cb');
    const txt = row.querySelector('.sp-shot-text');
    cb.classList.toggle('done');
    txt.classList.toggle('done');
}

// ── SunCalc ───────────────────────────────────────────────────────────────
(function initSunCalc() {
    const lat = parseFloat(<?= json_encode($plan['location_lat'] ?: '6.9271') ?>);
    const lng = parseFloat(<?= json_encode($plan['location_lng'] ?: '79.8612') ?>);
    const dt  = new Date(<?= json_encode(str_replace(' at ', ' ', $plan['datetime'])) ?>);

    if (isNaN(lat) || isNaN(lng)) return;

    // Sun position
    const pos = SunCalc.getPosition(dt, lat, lng);
    const azimuthDeg = (pos.azimuth * 180 / Math.PI + 180) % 360;
    const altitudeDeg = pos.altitude * 180 / Math.PI;
    const shadowDeg   = (azimuthDeg + 180) % 360;
    const shadowLen   = altitudeDeg > 0 ? (1 / Math.tan(pos.altitude)).toFixed(1) + '× height' : 'No shadow';

    function degToCard(d) {
        const cards = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
        return cards[Math.round(d / 22.5) % 16];
    }

    document.getElementById('sun-azimuth').textContent  = degToCard(azimuthDeg) + ' ' + Math.round(azimuthDeg) + '°';
    document.getElementById('sun-altitude').textContent = altitudeDeg.toFixed(1) + '°';
    document.getElementById('shadow-dir').textContent   = degToCard(shadowDeg) + ' ' + Math.round(shadowDeg) + '°';
    document.getElementById('shadow-len').textContent   = shadowLen;

    // Rotate compass arrows
    document.getElementById('sun-arrow').style.transform    = `rotate(${azimuthDeg}deg) translateY(-2px)`;
    document.getElementById('shadow-arrow').style.transform = `rotate(${shadowDeg}deg) translateY(-2px)`;

    // Sun score
    const sunScore = Math.min(100, Math.max(0, Math.round(((30 - Math.abs(altitudeDeg - 15)) / 30) * 100)));
    document.getElementById('sun-score-bar').style.width = sunScore + '%';
    document.getElementById('sun-score-val').textContent  = sunScore;

    // Golden / blue hour
    const times = SunCalc.getTimes(dt, lat, lng);
    const fmt   = t => t.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    const now   = dt.getTime();

    const goldenStart = times.goldenHour;
    const goldenEnd   = times.goldenHourEnd || times.sunsetStart;
    const blueStart   = times.sunsetStart   || times.goldenHourEnd;
    const blueEnd     = times.sunset;
    const sunset      = times.sunset;

    const isGolden = now >= goldenStart.getTime() && now <= goldenEnd.getTime();
    const isBlue   = now >= blueStart.getTime()   && now <= blueEnd.getTime();

    function badgeHtml(label, col1, col2) {
        return `<div class="sp-hour-badge" style="background:${col1};color:${col2};border:0.5px solid ${col2}44;">${label}</div>`;
    }

    document.getElementById('golden-body').innerHTML = `
        <div class="sp-hour-row">
            <div class="sp-hour-dot" style="background:#fb923c;box-shadow:0 0 5px #fb923c66;"></div>
            <div class="sp-hour-name">Golden Hour</div>
            <div class="sp-hour-time">${fmt(goldenStart)} – ${fmt(goldenEnd)}</div>
            ${isGolden ? badgeHtml('Now','#1e0f00','#fb923c') : ''}
        </div>
        <div class="sp-hour-row">
            <div class="sp-hour-dot" style="background:#60a5fa;box-shadow:0 0 5px #60a5fa66;"></div>
            <div class="sp-hour-name">Blue Hour</div>
            <div class="sp-hour-time">${fmt(blueStart)} – ${fmt(blueEnd)}</div>
            ${isBlue ? badgeHtml('Now','#0c1a2e','#60a5fa') : (!isGolden && now < blueStart.getTime() ? badgeHtml('Soon','#0c1a2e','#60a5fa') : '')}
        </div>
        <div class="sp-hour-row">
            <div class="sp-hour-dot" style="background:#6b7280;"></div>
            <div class="sp-hour-name">Sunset</div>
            <div class="sp-hour-time">${fmt(sunset)}</div>
            <div></div>
        </div>
        <div class="sp-tl-track">
            <div class="sp-tl-fill" style="width:68%;background:linear-gradient(90deg,#fb923c,#fbbf24);"></div>
        </div>
        <div class="sp-tl-labels">
            <span class="sp-tl-label">${fmt(goldenStart)}</span>
            <span class="sp-tl-label" style="color:#fb923c;font-weight:500;">${isGolden||isBlue?'NOW →':'SHOOT →'}</span>
            <span class="sp-tl-label">${fmt(blueEnd)}</span>
        </div>
    `;
})();

// ── OpenWeather fetch ──────────────────────────────────────────────────────
(function fetchWeather() {
    const lat = <?= json_encode($plan['location_lat'] ?: '') ?>;
    const lng = <?= json_encode($plan['location_lng'] ?: '') ?>;
    if (!lat || !lng) {
        document.getElementById('weather-body').innerHTML = '<div style="color:#6b7280;font-size:12px;">No coordinates — weather unavailable.</div>';
        return;
    }
    fetch(`weather.php?lat=${lat}&lng=${lng}`)
        .then(r => r.json())
        .then(d => {
            if (d.error) throw new Error(d.error);
            document.getElementById('weather-sub').textContent = 'Live data · ' + d.location;
            document.getElementById('weather-body').innerHTML = `
                <div class="sp-weather-big">${Math.round(d.temp)}°</div>
                <div class="sp-weather-cond">${d.description} · ${d.suitability}</div>
                <div class="sp-weather-grid">
                    <div class="sp-weather-stat"><div class="sp-weather-stat-label">Humidity</div><div class="sp-weather-stat-val">${d.humidity}%</div></div>
                    <div class="sp-weather-stat"><div class="sp-weather-stat-label">Wind</div><div class="sp-weather-stat-val">${d.wind} km/h</div></div>
                    <div class="sp-weather-stat"><div class="sp-weather-stat-label">Cloud cover</div><div class="sp-weather-stat-val">${d.clouds}%</div></div>
                    <div class="sp-weather-stat"><div class="sp-weather-stat-label">Rain chance</div><div class="sp-weather-stat-val">${d.rain_chance}%</div></div>
                </div>
            `;
            // Update weather score bar
            const ws = Math.max(0, Math.min(100, 100 - d.clouds - (d.rain_chance * 0.5)));
            document.getElementById('weather-score-bar').style.width = Math.round(ws) + '%';
            document.getElementById('weather-score-val').textContent  = Math.round(ws);
        })
        .catch(() => {
            document.getElementById('weather-body').innerHTML = '<div style="color:#6b7280;font-size:12px;">Weather unavailable — check API key in includes/weather.php</div>';
        });
})();

// ── Color harmony (client-side) ────────────────────────────────────────────
(function buildColorHarmony() {
    const outfit = <?= json_encode(strtolower(trim($plan['outfit']))) ?>;
    const mood   = <?= json_encode(strtolower($plan['mood'])) ?>;

    const palette = {
        cream:      { hex:'#f5ead7', comp:'#d97040', shadow:'#7a3e1c', bg:'#1a0a04', tip:'Complementary pairing: your cream outfit contrasts beautifully against warm terracotta. Avoid cool blues.' },
        white:      { hex:'#f8f8f8', comp:'#3b82f6', shadow:'#1e3a5f', bg:'#0a0a0a', tip:'High contrast: white pops against deep, dark backdrops. Use coloured gels for accent.' },
        black:      { hex:'#1a1a1a', comp:'#c084fc', shadow:'#160d1a', bg:'#0d0d0d', tip:'Dramatic: black outfits suit moody, high-contrast environments. Use rim lighting generously.' },
        red:        { hex:'#c0392b', comp:'#27ae60', shadow:'#0a3a1e', bg:'#0a0a0a', tip:'Bold statement: red clashes with green — use neutral backgrounds. Golden light flatters red tones.' },
        blue:       { hex:'#2980b9', comp:'#e67e22', shadow:'#7a3e1c', bg:'#0a0a0a', tip:'Analogous: blue outfit pairs well with amber and sunset tones. Avoid green backdrops.' },
        green:      { hex:'#27ae60', comp:'#c0392b', shadow:'#3a0a0a', bg:'#0a0a0a', tip:'Nature tones: green outfits blend beautifully into foliage — use contrast in accessories.' },
        yellow:     { hex:'#f1c40f', comp:'#8e44ad', shadow:'#4a1a6e', bg:'#0a0a0a', tip:'Energetic: yellow needs a muted background to shine. Avoid white — it competes.' },
        pink:       { hex:'#e91e8c', comp:'#1abc9c', shadow:'#0a3a2e', bg:'#0a0a0a', tip:'Playful: pink works well with teal and mint. Soft backdrops enhance the feminine energy.' },
        purple:     { hex:'#8e44ad', comp:'#f1c40f', shadow:'#3a2d10', bg:'#0a0a0a', tip:'Regal: purple pairs with gold and amber tones. Sunset backgrounds elevate purple outfits.' },
        orange:     { hex:'#e67e22', comp:'#2980b9', shadow:'#0f1520', bg:'#0a0a0a', tip:'Warm pop: orange suits golden hour and earthy tones. Blue sky backdrops create vivid contrast.' },
        grey:       { hex:'#95a5a6', comp:'#e67e22', shadow:'#7a3e1c', bg:'#0a0a0a', tip:'Versatile: grey is neutral — works with any backdrop. Add a bold accessory for focal interest.' },
        gray:       { hex:'#95a5a6', comp:'#e67e22', shadow:'#7a3e1c', bg:'#0a0a0a', tip:'Versatile: grey is neutral — works with any backdrop. Add a bold accessory for focal interest.' },
        beige:      { hex:'#e8d5b7', comp:'#c0a060', shadow:'#7a5030', bg:'#1a0a04', tip:'Earthy warmth: beige suits warm, golden hour light. Pair with terracotta and forest greens.' },
        brown:      { hex:'#795548', comp:'#78909c', shadow:'#37474f', bg:'#0a0a0a', tip:'Earthy: brown suits outdoor and rustic settings. Works beautifully in forest and beach environments.' },
        navy:       { hex:'#1a237e', comp:'#e67e22', shadow:'#7a3e1c', bg:'#0a0a0a', tip:'Classic: navy blue reads as authoritative and clean. Warm sunset tones create elegant contrast.' },
    };

    const key = Object.keys(palette).find(k => outfit.includes(k)) || 'cream';
    const p   = palette[key] || palette['cream'];

    document.getElementById('color-body').innerHTML = `
        <div class="sp-palette-row">
            <div><div class="sp-swatch-block" style="background:${p.hex};"></div><div class="sp-swatch-label">Outfit</div></div>
            <div><div class="sp-swatch-block" style="background:${p.comp};"></div><div class="sp-swatch-label">Accent</div></div>
            <div><div class="sp-swatch-block" style="background:${p.shadow};"></div><div class="sp-swatch-label">Shadow</div></div>
            <div><div class="sp-swatch-block" style="background:${p.bg};"></div><div class="sp-swatch-label">Backdrop</div></div>
        </div>
        <div class="sp-harmony-tip"><strong>Tip:</strong> ${p.tip}</div>
    `;
})();

// ── AI Pose & Tips generation via Anthropic API ───────────────────────────
(async function generateAICards() {
    try {
        const resp = await fetch('includes/ai-generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                shoot_type: SHOOT_CONTEXT.shoot_type,
                mood:       SHOOT_CONTEXT.mood,
                outfit:     SHOOT_CONTEXT.outfit,
                location:   SHOOT_CONTEXT.location,
                datetime:   SHOOT_CONTEXT.datetime,
                experience: SHOOT_CONTEXT.experience,
                platform:   SHOOT_CONTEXT.platform,
            })
        });
        const data = await resp.json();

        // Poses
        if (data.poses) {
            document.getElementById('pose-body').innerHTML = data.poses.map((p, i) => `
                <div class="sp-pose-card">
                    <div class="sp-pose-num">0${i+1}</div>
                    <div>
                        <div class="sp-pose-name">${p.name}</div>
                        <div class="sp-pose-desc">${p.description}</div>
                        <div class="sp-pose-tag">${p.tag}</div>
                    </div>
                </div>
            `).join('');
        }

        // Tips
        if (data.tips) {
            document.getElementById('tips-body').innerHTML = data.tips.map(t => `
                <div class="sp-tip">
                    <div class="sp-tip-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="sp-tip-title">${t.title}</div>
                        <div class="sp-tip-text">${t.text}</div>
                    </div>
                </div>
            `).join('');
        }
    } catch(e) {
        document.getElementById('pose-body').innerHTML = '<div style="color:#6b7280;font-size:12px;grid-column:span 2;">Pose generation unavailable — check includes/ai-generate.php</div>';
        document.getElementById('tips-body').innerHTML = '<div style="color:#6b7280;font-size:12px;">Tips unavailable.</div>';
    }
})();


// ══════════════════════════════════════════════════════════════════════════
// CHATBOT
// ══════════════════════════════════════════════════════════════════════════
const chatHistory = [];

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function handleChatKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendChatMessage();
    }
}

function sendSuggestion(btn) {
    document.getElementById('sp-chat-input').value = btn.textContent;
    document.getElementById('sp-suggestions').style.display = 'none';
    sendChatMessage();
}

function appendMsg(role, html) {
    const wrap = document.getElementById('sp-chat-messages');
    const div  = document.createElement('div');
    div.className = 'sp-msg ' + role;

    const avatarDiv = document.createElement('div');
    avatarDiv.className = 'sp-msg-avatar';

    if (role === 'ai') {
        avatarDiv.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>`;
    } else {
        avatarDiv.textContent = '<?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>';
    }

    const bubble = document.createElement('div');
    bubble.className = 'sp-msg-bubble';
    bubble.innerHTML = html;

    div.appendChild(avatarDiv);
    div.appendChild(bubble);
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
    return bubble;
}

function appendTyping() {
    const wrap = document.getElementById('sp-chat-messages');
    const div  = document.createElement('div');
    div.className = 'sp-msg ai';
    div.id = 'sp-typing-indicator';

    const avatar = document.createElement('div');
    avatar.className = 'sp-msg-avatar';
    avatar.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>`;

    const bubble = document.createElement('div');
    bubble.className = 'sp-msg-bubble';
    bubble.innerHTML = `<div class="sp-typing"><span></span><span></span><span></span></div>`;

    div.appendChild(avatar);
    div.appendChild(bubble);
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
}

async function sendChatMessage() {
    const input = document.getElementById('sp-chat-input');
    const btn   = document.getElementById('sp-send-btn');
    const text  = input.value.trim();
    if (!text) return;

    // Clear input
    input.value = '';
    input.style.height = 'auto';
    btn.disabled = true;

    // Show user message
    appendMsg('user', text.replace(/</g,'&lt;').replace(/>/g,'&gt;'));

    // Add to history
    chatHistory.push({ role: 'user', content: text });

    // Show typing
    appendTyping();

    try {
        const resp = await fetch('includes/chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                messages: chatHistory,
                context: SHOOT_CONTEXT,
            })
        });
        const data = await resp.json();
        const reply = data.reply || 'Sorry, I could not generate a response.';

        // Remove typing indicator
        const typer = document.getElementById('sp-typing-indicator');
        if (typer) typer.remove();

        // Add AI reply
        chatHistory.push({ role: 'assistant', content: reply });
        appendMsg('ai', reply.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>'));

    } catch(e) {
        const typer = document.getElementById('sp-typing-indicator');
        if (typer) typer.remove();
        appendMsg('ai', 'Connection error — please check <code>includes/chatbot.php</code>.');
    }

    btn.disabled = false;
    input.focus();
}
</script>


<?php
// ══════════════════════════════════════════════════════════════════════════
// PHP HELPER: Camera Settings Logic
// ══════════════════════════════════════════════════════════════════════════
function getCameraSettings(string $shoot_type, string $mood): array {
    $aperture     = 'f / 2.8';
    $shutter      = '1/250s';
    $iso          = '400';
    $focal        = '50mm';
    $wb           = '5500K';
    $aperture_badge = 'Shallow DOF';
    $iso_badge      = 'Clean';

    switch (strtolower($shoot_type)) {
        case 'portrait':
            $aperture = 'f / 1.8'; $shutter = '1/250s'; $iso = '200'; $focal = '85mm'; $wb = '5500K';
            $aperture_badge = 'Bokeh'; break;
        case 'fashion':
            $aperture = 'f / 2.0'; $shutter = '1/500s'; $iso = '200'; $focal = '85mm'; $wb = '6200K';
            $aperture_badge = 'Shallow DOF'; break;
        case 'product':
            $aperture = 'f / 8.0'; $shutter = '1/125s'; $iso = '100'; $focal = '100mm'; $wb = '5000K';
            $aperture_badge = 'Deep DOF'; break;
        case 'street':
            $aperture = 'f / 5.6'; $shutter = '1/1000s'; $iso = '800'; $focal = '35mm'; $wb = 'Auto';
            $aperture_badge = 'Zone focus'; break;
        case 'landscape':
            $aperture = 'f / 11.0'; $shutter = '1/250s'; $iso = '100'; $focal = '24mm'; $wb = '5500K';
            $aperture_badge = 'Deep DOF'; break;
        case 'wedding':
            $aperture = 'f / 2.0'; $shutter = '1/500s'; $iso = '400'; $focal = '50mm'; $wb = '5500K';
            $aperture_badge = 'Bokeh'; break;
    }

    if (strtolower($mood) === 'dramatic' || strtolower($mood) === 'moody') {
        $iso = '800'; $iso_badge = 'Film grain';
    } elseif (strtolower($mood) === 'airy' || strtolower($mood) === 'natural') {
        $iso = '100'; $iso_badge = 'Clean';
    }

    return [
        ['label'=>'Aperture',    'icon'=>'◎', 'value'=>$aperture, 'badge'=>$aperture_badge],
        ['label'=>'Shutter speed','icon'=>'⏱', 'value'=>$shutter,  'badge'=>''],
        ['label'=>'ISO',          'icon'=>'☀', 'value'=>$iso,      'badge'=>$iso_badge],
        ['label'=>'Focal length', 'icon'=>'⌖', 'value'=>$focal,    'badge'=>'Portrait'],
        ['label'=>'White balance','icon'=>'◑', 'value'=>$wb,       'badge'=>''],
    ];
}
?>
</div>
</div>
<?php include 'footer.php'; ?>