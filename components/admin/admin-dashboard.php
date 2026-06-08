<?php
if (!isset($_SESSION['isAdmin'])) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    header('Location: dashboard.php');
    exit();
}


$uid = $_SESSION['usersId'] ?? $_SESSION['userid'] ?? 0;

// ── Stats ──────────────────────────────────────────────────────────────────
$total_users  = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_shoots = $conn->query("SELECT COUNT(*) FROM shoot_results")->fetch_row()[0];

// ── Category display config from DB ───────────────────────────────────────
$cat_display_config = [];
$cd_rows = $conn->query("SELECT * FROM category_display")->fetch_all(MYSQLI_ASSOC);
foreach ($cd_rows as $cd) {
    $cat_display_config[$cd['category_key']] = [
        'label'  => $cd['label'],
        'emoji'  => '',
        'color'  => $cd['color'],
        'bg'     => $cd['bg'],
        'border' => $cd['border'],
    ];
}

$default_display = [
    'label'  => '',
    'emoji'  => '',
    'color'  => '#6b7280',
    'bg'     => '#111111',
    'border' => '#2a2a2a',
];

// ── Lookup categories ──────────────────────────────────────────────────────
$lookup_categories = [];

$cat_rows = $conn->query(
    "SELECT DISTINCT category FROM lookup_options ORDER BY category ASC"
)->fetch_all(MYSQLI_ASSOC);

foreach ($cat_rows as $row) {
    $key     = $row['category'];
    $display = $cat_display_config[$key] ?? $default_display;
    $display['label'] = $display['label'] ?: ucwords(str_replace('_', ' ', $key));
    $display['table'] = 'lookup_options';
    $lookup_categories[$key] = $display;
}

$users_rows = $conn->query("
    SELECT usersId as id, usersName as username, usersEmail as email, isAdmin
    FROM users ORDER BY usersId DESC
")->fetch_all(MYSQLI_ASSOC);

$poses_rows = $conn->query("
    SELECT id, pose_id, gender, name, category, difficulty, image_file
    FROM poses ORDER BY id DESC
")->fetch_all(MYSQLI_ASSOC);
 
$total_poses = count($poses_rows);

// moods — separate table
$moodDisp = $cat_display_config['mood'] ?? $default_display;
$lookup_categories['mood'] = array_merge($moodDisp, [
    'label' => $moodDisp['label'] ?: 'Moods',
    'table' => 'moods',
]);

// equipment — separate table
$equipDisp = $cat_display_config['equipment'] ?? $default_display;
$lookup_categories['equipment'] = array_merge($equipDisp, [
    'label' => $equipDisp['label'] ?: 'Equipment',
    'table' => 'equipment_options',
]);

$stDisp = $cat_display_config['shoot_type'] ?? $default_display;
$lookup_categories['shoot_type'] = array_merge($stDisp, [
    'label' => $stDisp['label'] ?: 'Shoot Types',
    'table' => 'shoot_types',
]);

// ── Fetch items for each category ──────────────────────────────────────────
$category_items = [];

foreach ($lookup_categories as $cat_key => $cat_info) {
    if ($cat_key === 'mood') {
        $res = $conn->query(
            "SELECT id, value, label, '' as emoji FROM moods ORDER BY label ASC"
        );
    } elseif ($cat_key === 'equipment') {
        $res = $conn->query(
            "SELECT id, value, label FROM equipment_options ORDER BY label ASC"
        );
    } elseif ($cat_key === 'shoot_type') {
        $res = $conn->query(
            "SELECT id, value, label FROM shoot_types ORDER BY label ASC"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT id, value, label
            FROM lookup_options WHERE category = ?
            ORDER BY sort_order ASC, label ASC"
        );
        $stmt->bind_param("s", $cat_key);
        $stmt->execute();
        $res = $stmt->get_result();
    }
    $category_items[$cat_key] = $res->fetch_all(MYSQLI_ASSOC);
}

// ── Mood breakdown — colors from DB ───────────────────────────────────────
// Build mood color map from moods table
$mood_colors_map = [];
$mood_meta_rows = $conn->query(
    "SELECT value, color, bg_gradient FROM moods"
)->fetch_all(MYSQLI_ASSOC);
foreach ($mood_meta_rows as $mm) {
    $mood_colors_map[strtolower($mm['value'])] = [
        'color' => $mm['color'],
        'bg'    => $mm['bg_gradient'],
    ];
}
$mood_colors_map['default'] = [
    'color' => '#e5e7eb',
    'bg'    => 'linear-gradient(135deg,#1a1a1a,#2a2a2a)',
];

$mood_rows = $conn->query("
    SELECT mood, COUNT(*) as cnt FROM shoot_results
    GROUP BY mood ORDER BY cnt DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
$mood_max = !empty($mood_rows) ? $mood_rows[0]['cnt'] : 1;

// ── Top locations ──────────────────────────────────────────────────────────
$location_rows = $conn->query("
    SELECT location, COUNT(*) as cnt FROM shoot_results
    GROUP BY location ORDER BY cnt DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ── Shoot type distribution — colors from DB ──────────────────────────────
// Build type color map from shoot_types table
$type_colors_map = [];
$st_rows = $conn->query(
    "SELECT value, color FROM shoot_types"
)->fetch_all(MYSQLI_ASSOC);
foreach ($st_rows as $st) {
    $type_colors_map[strtolower($st['value'])] = $st['color'];
}
$type_colors_map['default'] = '#6b7280';

$type_rows = $conn->query("
    SELECT shoot_type, COUNT(*) as cnt FROM shoot_results
    GROUP BY shoot_type ORDER BY cnt DESC
")->fetch_all(MYSQLI_ASSOC);

// ── Completion by type ─────────────────────────────────────────────────────
$completion_rows = $conn->query("
    SELECT shoot_type,
           COUNT(*) as total,
           SUM(CASE WHEN ai_plan IS NOT NULL THEN 1 ELSE 0 END) as done
    FROM shoot_results
    GROUP BY shoot_type ORDER BY total DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard-style.css">

<style>
/* ── Admin-specific extras ─────────────────────────────────────────────── */
.adm-section-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 18px;
    letter-spacing: .06em;
    color: #fff;
    margin: 28px 0 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.adm-section-title::after {
    content: '';
    flex: 1;
    height: 0.5px;
    background: #1e1e1e;
}
.adm-lookup-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
.adm-lookup-card {
    background: #111;
    border: 0.5px solid #1e1e1e;
    border-radius: 14px;
    padding: 14px 16px;
    transition: border-color .2s;
}
.adm-lookup-card:hover { border-color: #2a2a2a; }
.adm-lookup-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.adm-lookup-title {
    display: flex;
    align-items: center;
    gap: 7px;
    font-family: 'Syne', sans-serif;
    font-size: 12px;
    font-weight: 700;
    color: #f0ede8;
}
.adm-lookup-emoji { font-size: 15px; line-height: 1; }
.adm-lookup-count {
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 5px;
    font-weight: 500;
}
.adm-lookup-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 12px;
    min-height: 28px;
}
.adm-pill {
    font-size: 10px;
    border-radius: 5px;
    padding: 3px 8px;
    border: 0.5px solid;
    white-space: nowrap;
}
.adm-modify-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    width: 100%;
    background: #0d0d0d;
    border: 0.5px solid #1e1e1e;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 11px;
    color: #9ca3af;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: all .2s;
    justify-content: center;
}
.adm-modify-btn:hover { border-color: #3b82f6; color: #3b82f6; background: #0a0f1a; }
.adm-modify-btn svg { width: 12px; height: 12px; }

/* ── Lookup modal ───────────────────────────────────────────────────────── */
.adm-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,0.75);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
}
.adm-modal-overlay.open { display: flex; }
.adm-modal-box {
    background: #0d0d0d;
    border: 0.5px solid #1f1f1f;
    border-radius: 16px;
    width: 90%;
    max-width: 520px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.adm-modal-head {
    padding: 16px 18px 12px;
    border-bottom: 0.5px solid #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.adm-modal-head-left { display: flex; align-items: center; gap: 10px; }
.adm-modal-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.adm-modal-title { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; color: #f0ede8; }
.adm-modal-sub { font-size: 11px; color: #4b5563; margin-top: 1px; }
.adm-modal-body { overflow-y: auto; flex: 1; padding: 12px 18px; }
.adm-modal-footer {
    padding: 12px 18px;
    border-top: 0.5px solid #1a1a1a;
    flex-shrink: 0;
}
.adm-add-row {
    display: flex;
    gap: 8px;
    align-items: center;
}
.adm-add-input {
    flex: 1;
    background: #111;
    border: 0.5px solid #2a2a2a;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 12px;
    color: #e5e7eb;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    transition: border-color .2s;
}
.adm-add-input:focus { border-color: #3b82f6; }
.adm-add-input::placeholder { color: #4b5563; }
.adm-add-emoji-input {
    width: 54px;
    background: #111;
    border: 0.5px solid #2a2a2a;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 14px;
    color: #e5e7eb;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    text-align: center;
    transition: border-color .2s;
}
.adm-add-emoji-input:focus { border-color: #3b82f6; }
.adm-add-btn {
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 11px;
    color: #fff;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    font-weight: 500;
    white-space: nowrap;
    transition: background .2s;
    display: flex; align-items: center; gap: 5px;
}
.adm-add-btn:hover { background: #2563eb; }
.adm-item-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 0;
    border-bottom: 0.5px solid #141414;
}
.adm-item-row:last-child { border-bottom: none; }
.adm-item-emoji { font-size: 16px; width: 22px; text-align: center; flex-shrink: 0; }
.adm-item-label { font-size: 12px; color: #e5e7eb; flex: 1; }
.adm-item-value { font-size: 10px; color: #4b5563; }
.adm-delete-btn {
    background: transparent;
    border: 0.5px solid #2a1a1a;
    border-radius: 6px;
    padding: 4px 9px;
    font-size: 10px;
    color: #6b7280;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: all .2s;
    display: flex; align-items: center; gap: 4px;
    flex-shrink: 0;
}
.adm-delete-btn:hover { border-color: #ef4444; color: #ef4444; background: #1a0808; }
.adm-close-btn {
    background: #1a1a1a;
    border: 0.5px solid #2a2a2a;
    border-radius: 8px;
    color: #9ca3af;
    font-size: 11px;
    padding: 5px 10px;
    cursor: pointer;
    display: flex; align-items: center; gap: 4px;
    font-family: 'DM Sans', sans-serif;
}
.adm-close-btn:hover { border-color: #444; color: #ccc; }
.adm-toast {
    position: fixed;
    bottom: 24px; right: 24px;
    background: #111;
    border: 0.5px solid #2a2a2a;
    border-radius: 10px;
    padding: 10px 16px;
    font-size: 12px;
    color: #e5e7eb;
    z-index: 99999;
    opacity: 0;
    transform: translateY(8px);
    transition: all .3s;
    pointer-events: none;
}
.adm-toast.show { opacity: 1; transform: translateY(0); }
.adm-toast.success { border-color: #22c55e44; color: #22c55e; }
.adm-toast.error   { border-color: #ef444444; color: #ef4444; }
@media (max-width: 1100px) { .adm-lookup-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .adm-lookup-grid { grid-template-columns: 1fr; } }
@media (max-width: 900px) {
    div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="db-main">

    <!-- ── Page Header ──────────────────────────────────────────────────── -->
    <div class="db-page-header">
        <div>
            <div class="db-page-title">Admin Panel</div>
            <div class="db-page-sub"><?= date('l, F j Y') ?> · Logged in as <?= htmlspecialchars($_SESSION['username']) ?></div>
        </div>
    </div>

    <!-- ── Stats Row ────────────────────────────────────────────────────── -->
    <div class="db-stats-grid" style="grid-template-columns: repeat(2, 1fr); max-width: 480px;">

        <div class="db-stat-card">
            <div class="db-stat-accent" style="background:#c084fc;"></div>
            <div class="db-stat-label" style="color:#c084fc;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                Number of Users
            </div>
            <div class="db-stat-value"><?= $total_users ?></div>
        </div>

        <div class="db-stat-card">
            <div class="db-stat-accent" style="background:#3b82f6;"></div>
            <div class="db-stat-label" style="color:#3b82f6;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                Total Shoots
            </div>
            <div class="db-stat-value"><?= $total_shoots ?></div>
        </div>

    </div>

    <!-- ── Lookup Management ────────────────────────────────────────────── -->
    <div class="adm-section-title">
        <svg fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="1.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Option Management
    </div>

    <div class="adm-lookup-grid">
        <?php foreach ($lookup_categories as $cat_key => $cat_info):
            $items = $category_items[$cat_key];
            $count = count($items);
        ?>
        <div class="adm-lookup-card">
            <div class="adm-lookup-head">
                <div class="adm-lookup-title">
                    <span class="adm-lookup-emoji"><?= $cat_info['emoji'] ?></span>
                    <?= htmlspecialchars($cat_info['label']) ?>
                </div>
                <span class="adm-lookup-count" style="background:<?= $cat_info['color'] ?>18;color:<?= $cat_info['color'] ?>;border:0.5px solid <?= $cat_info['color'] ?>33;"><?= $count ?></span>
            </div>
            <div class="adm-lookup-pills">
                <?php foreach (array_slice($items, 0, 6) as $item): ?>
                <span class="adm-pill" style="background:<?= $cat_info['color'] ?>12;color:<?= $cat_info['color'] ?>;border-color:<?= $cat_info['color'] ?>30;">
                    <?= htmlspecialchars($item['label']) ?>
                </span>
                <?php endforeach; ?>
                <?php if ($count > 6): ?>
                <span class="adm-pill" style="background:#1a1a1a;color:#6b7280;border-color:#2a2a2a;">+<?= $count - 6 ?> more</span>
                <?php endif; ?>
            </div>
            <button class="adm-modify-btn" onclick="openLookupModal('<?= $cat_key ?>')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                Modify
            </button>
        </div>
        <?php endforeach; ?>
    </div>

  <!-- ── User & Pose Management Row ─────────────────────────────────────────── -->
<div class="adm-section-title">
    <svg fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="1.5" width="16" height="16">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
    </svg>
    Management
</div>
 
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
 
    <!-- ── Users Card ── -->
    <div class="db-card">
        <div class="db-card-head">
            <div class="db-card-head-left">
                <div class="db-card-icon" style="background:#0d0f1a;border:0.5px solid #1e2a4a;">
                    <svg fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="db-card-title">All Users</div>
                    <div class="db-card-sub"><?= count($users_rows) ?> registered accounts</div>
                </div>
            </div>
            <button class="adm-modify-btn" style="width:auto;padding:6px 14px;" onclick="openUsersModal()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="12" height="12">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                View All
            </button>
        </div>
        <div class="db-card-body" style="padding-top:4px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:0.5px solid #1e1e1e;">
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:6px 0;letter-spacing:.08em;text-transform:uppercase;">User</th>
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:6px 0;letter-spacing:.08em;text-transform:uppercase;">Email</th>
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:6px 0;letter-spacing:.08em;text-transform:uppercase;">Role</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($users_rows, 0, 5) as $u): ?>
                    <tr style="border-bottom:0.5px solid #141414;" class="adm-user-row" id="user-row-<?= $u['id'] ?>">
                        <td style="padding:9px 0;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#3b82f620,#3b82f640);border:0.5px solid #3b82f640;display:flex;align-items:center;justify-content:center;font-size:11px;color:#3b82f6;font-weight:600;flex-shrink:0;">
                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                </div>
                                <span style="font-size:12px;color:#e5e7eb;"><?= htmlspecialchars($u['username']) ?></span>
                            </div>
                        </td>
                        <td style="font-size:11px;color:#6b7280;padding:9px 0;max-width:120px;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding:9px 0;">
                            <?php if ($u['isAdmin']): ?>
                                <span style="background:#c084fc18;color:#c084fc;border:0.5px solid #c084fc33;border-radius:5px;font-size:10px;padding:2px 7px;">Admin</span>
                            <?php else: ?>
                                <span style="background:#3b82f618;color:#3b82f6;border:0.5px solid #3b82f633;border-radius:5px;font-size:10px;padding:2px 7px;">User</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:9px 0;text-align:right;">
                            <?php if ($u['id'] != $uid && !$u['isAdmin']): ?>
                            <button class="adm-delete-btn" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                Delete
                            </button>
                            <?php else: ?>
                            <span style="font-size:10px;color:#4b5563;">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
 
    <!-- ── Poses Card ── -->
    <div class="db-card">
        <div class="db-card-head">
            <div class="db-card-head-left">
                <div class="db-card-icon" style="background:#0f1a10;border:0.5px solid #1f3a22;">
                    <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <div>
                    <div class="db-card-title">Poses Library</div>
                    <div class="db-card-sub"><?= $total_poses ?> poses in database</div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>components/admin/manage-poses.php" class="adm-modify-btn" style="width:auto;padding:6px 14px;text-decoration:none;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="12" height="12">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                </svg>
                Modify
            </a>
        </div>
        <div class="db-card-body" style="padding-top:4px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:0.5px solid #1e1e1e;">
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:6px 0;letter-spacing:.08em;text-transform:uppercase;">Pose</th>
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:6px 0;letter-spacing:.08em;text-transform:uppercase;">Category</th>
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:6px 0;letter-spacing:.08em;text-transform:uppercase;">Gender</th>
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:6px 0;letter-spacing:.08em;text-transform:uppercase;">Difficulty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($poses_rows, 0, 5) as $p):
                        $diffColors = [
                            'easy'   => ['bg' => '#14532d', 'color' => '#4ade80', 'border' => '#166534'],
                            'medium' => ['bg' => '#713f12', 'color' => '#fbbf24', 'border' => '#92400e'],
                            'hard'   => ['bg' => '#7f1d1d', 'color' => '#f87171', 'border' => '#991b1b'],
                        ];
                        $diff = strtolower($p['difficulty'] ?? 'easy');
                        $dc   = $diffColors[$diff] ?? $diffColors['easy'];
                    ?>
                    <tr style="border-bottom:0.5px solid #141414;">
                        <td style="padding:9px 0;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <?php if (!empty($p['image_file'])): ?>
                                <div style="width:28px;height:28px;border-radius:7px;overflow:hidden;flex-shrink:0;border:0.5px solid #2a2a2a;">
                                    <img src="<?= BASE_URL ?>assets/poses/<?= htmlspecialchars($p['image_file']) ?>"
                                         style="width:100%;height:100%;object-fit:cover;"
                                         onerror="this.style.display='none';this.parentElement.style.background='#1a1a1a';">
                                </div>
                                <?php else: ?>
                                <div style="width:28px;height:28px;border-radius:7px;background:#1a1a1a;border:0.5px solid #2a2a2a;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="#4b5563" stroke-width="1.5" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                </div>
                                <?php endif; ?>
                                <span style="font-size:12px;color:#e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($p['name']) ?></span>
                            </div>
                        </td>
                        <td style="padding:9px 0;">
                            <span style="font-size:11px;color:#9ca3af;"><?= ucfirst(htmlspecialchars($p['category'] ?? '—')) ?></span>
                        </td>
                        <td style="padding:9px 0;">
                            <span style="font-size:11px;color:#9ca3af;"><?= ucfirst(htmlspecialchars($p['gender'] ?? '—')) ?></span>
                        </td>
                        <td style="padding:9px 0;">
                            <span style="background:<?= $dc['bg'] ?>22;color:<?= $dc['color'] ?>;border:0.5px solid <?= $dc['border'] ?>;border-radius:5px;font-size:10px;padding:2px 7px;">
                                <?= ucfirst($diff) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
 
</div>

<!-- ── Users Modal ───────────────────────────────────────────────────────── -->
<div id="users-modal" class="adm-modal-overlay">
    <div class="adm-modal-box" style="max-width:680px;">
        <div class="adm-modal-head">
            <div class="adm-modal-head-left">
                <div class="adm-modal-icon" style="background:#0d0f1a;border:0.5px solid #1e2a4a;font-size:14px;display:flex;align-items:center;justify-content:center;">
                    <svg fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="1.5" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="adm-modal-title">All Users</div>
                    <div class="adm-modal-sub" id="users-modal-sub"><?= count($users_rows) ?> accounts</div>
                </div>
            </div>
            <button class="adm-close-btn" onclick="closeUsersModal()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="11" height="11">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Close
            </button>
        </div>
        <div class="adm-modal-body" id="users-modal-body">
            <table style="width:100%;border-collapse:collapse;">
                <thead style="position:sticky;top:0;background:#0d0d0d;z-index:1;">
                    <tr style="border-bottom:0.5px solid #1e1e1e;">
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:8px 0;letter-spacing:.08em;text-transform:uppercase;">User</th>
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:8px 0;letter-spacing:.08em;text-transform:uppercase;">Email</th>
                        <th style="text-align:left;font-size:10px;color:#4b5563;font-weight:500;padding:8px 0;letter-spacing:.08em;text-transform:uppercase;">Role</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="users-modal-tbody">
                    <?php foreach ($users_rows as $u): ?>
                    <tr style="border-bottom:0.5px solid #141414;" id="user-row-<?= $u['id'] ?>">
                        <td style="padding:9px 0;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#3b82f620,#3b82f640);border:0.5px solid #3b82f640;display:flex;align-items:center;justify-content:center;font-size:11px;color:#3b82f6;font-weight:600;flex-shrink:0;">
                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                </div>
                                <span style="font-size:12px;color:#e5e7eb;"><?= htmlspecialchars($u['username']) ?></span>
                            </div>
                        </td>
                        <td style="font-size:11px;color:#6b7280;padding:9px 0;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding:9px 0;">
                            <?php if ($u['isAdmin']): ?>
                                <span style="background:#c084fc18;color:#c084fc;border:0.5px solid #c084fc33;border-radius:5px;font-size:10px;padding:2px 7px;">Admin</span>
                            <?php else: ?>
                                <span style="background:#3b82f618;color:#3b82f6;border:0.5px solid #3b82f633;border-radius:5px;font-size:10px;padding:2px 7px;">User</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:9px 0;text-align:right;">
                            <?php if ($u['id'] != $uid && !$u['isAdmin']): ?>
                            <button class="adm-delete-btn" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                Delete
                            </button>
                            <?php else: ?>
                            <span style="font-size:10px;color:#4b5563;">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <!-- ── Analytics Section ────────────────────────────────────────────── -->
    <div class="adm-section-title">
        <svg fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="1.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm9.75-4.875C12.75 7.629 13.254 7.125 13.875 7.125h2.25c.621 0 1.125.504 1.125 1.125v11.625c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.25zm9.75-3.375c0-.621.504-1.125 1.125-1.125h.75c.621 0 1.125.504 1.125 1.125v15c0 .621-.504 1.125-1.125 1.125h-.75A1.125 1.125 0 0121 19.875V4.875z"/></svg>
        Platform Analytics
    </div>

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
                        <div class="db-card-sub">All users · all shoots</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php if (empty($mood_rows)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else: ?>
                <?php foreach ($mood_rows as $m):
                    $mk  = strtolower($m['mood']);
                    $mc  = $mood_colors_map[$mk] ?? $mood_colors_map['default'];
                    $pct = round(($m['cnt'] / $mood_max) * 100);
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
                        <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c-.317-.159.69-.159 1.006 0l4.994 2.497c.317.159.69.159 1.006 0z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Top Locations</div>
                        <div class="db-card-sub">All users · by shoot count</div>
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

        <!-- Completion by Type -->
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
                <?php if (empty($completion_rows)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else: ?>
                <?php foreach ($completion_rows as $cr):
                    $pct = round(($cr['done'] / max($cr['total'], 1)) * 100);
                    $col = $type_colors_map[strtolower($cr['shoot_type'])] ?? $type_colors_map['default'];
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

        <!-- Shoot Type Donut -->
        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="background:#160d1a;border:0.5px solid #3d1f4a;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#c084fc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Type Distribution</div>
                        <div class="db-card-sub">All time · <?= $total_shoots ?> shoots</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body">
                <?php if (empty($type_rows)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else:
                    $circumference = 276.46;
                    $offset = 0;
                    $segments = [];
                    foreach ($type_rows as $t) {
                        $pct = $t['cnt'] / max($total_shoots, 1);
                        $col = $type_colors_map[strtolower($t['shoot_type'])] ?? $type_colors_map['default'];
                        $segments[] = [
                            'type'   => $t['shoot_type'],
                            'cnt'    => $t['cnt'],
                            'pct'    => round($pct * 100),
                            'dash'   => round($pct * $circumference, 1),
                            'offset' => -$offset,
                            'color'  => $col,
                        ];
                        $offset += round($pct * $circumference, 1);
                    }
                ?>
                <div class="db-donut-wrap">
                    <svg width="100" height="100" viewBox="0 0 120 120">
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

    </div>

</div>

<!-- ── Lookup Modal ──────────────────────────────────────────────────────── -->
<div id="lookup-modal" class="adm-modal-overlay">
    <div class="adm-modal-box">
        <div class="adm-modal-head">
            <div class="adm-modal-head-left">
                <div class="adm-modal-icon" id="modal-icon-wrap"></div>
                <div>
                    <div class="adm-modal-title" id="modal-cat-title">Options</div>
                    <div class="adm-modal-sub" id="modal-cat-sub">Manage entries</div>
                </div>
            </div>
            <button class="adm-close-btn" onclick="closeLookupModal()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="11" height="11">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Close
            </button>
        </div>
        <div class="adm-modal-body" id="modal-items-list"></div>
        <div class="adm-modal-footer">
            <div class="adm-add-row">
                <input type="text" id="modal-add-label" class="adm-add-input" placeholder="Add new option label…">
                <input type="text" id="modal-add-emoji" class="adm-add-emoji-input" placeholder="😊" style="display:none;">
                <button class="adm-add-btn" onclick="addLookupItem()">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Toast ─────────────────────────────────────────────────────────────── -->
<div id="adm-toast" class="adm-toast"></div>

<script>
const LOOKUP_CATEGORIES = <?= json_encode($lookup_categories) ?>;
let categoryItems = <?= json_encode($category_items) ?>;
let currentCat = null;

function openLookupModal(catKey) {
    currentCat = catKey;
    const cat = LOOKUP_CATEGORIES[catKey];
    document.getElementById('modal-cat-title').textContent = cat.label;
    document.getElementById('modal-cat-sub').textContent = categoryItems[catKey].length + ' entries';
    document.getElementById('modal-icon-wrap').style.cssText =
        `background:${cat.bg};border:0.5px solid ${cat.border};font-size:16px;display:flex;align-items:center;justify-content:center;`;
    document.getElementById('modal-icon-wrap').textContent = cat.emoji;

    // Show emoji input only for moods
    const emojiInput = document.getElementById('modal-add-emoji');
    emojiInput.style.display = catKey === 'mood' ? 'block' : 'none';
    emojiInput.value = '';

    renderModalItems(catKey);
    document.getElementById('lookup-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeLookupModal() {
    document.getElementById('lookup-modal').classList.remove('open');
    document.body.style.overflow = '';
    currentCat = null;
}

document.getElementById('lookup-modal').addEventListener('click', function(e) {
    if (e.target === this) closeLookupModal();
});

function renderModalItems(catKey) {
    const items = categoryItems[catKey];
    const list  = document.getElementById('modal-items-list');
    document.getElementById('modal-cat-sub').textContent = items.length + ' entries';

    if (items.length === 0) {
        list.innerHTML = '<div style="color:#4b5563;font-size:12px;padding:24px 0;text-align:center;">No entries yet. Add one below.</div>';
        return;
    }

    list.innerHTML = items.map(item => `
        <div class="adm-item-row" id="item-row-${catKey}-${item.id}">
            <div class="adm-item-label">${escHtml(item.label)}</div>
            <div class="adm-item-value">${escHtml(item.value)}</div>
            <button class="adm-delete-btn" onclick="deleteLookupItem('${catKey}', ${item.id})">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                Delete
            </button>
        </div>
    `).join('');
}

function addLookupItem() {
    const label = document.getElementById('modal-add-label').value.trim();
    const emoji = document.getElementById('modal-add-emoji').value.trim();
    if (!label) { showToast('Please enter a label', 'error'); return; }

    fetch('<?= BASE_URL ?>components/admin/admin-dashboard.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&category=${encodeURIComponent(currentCat)}&label=${encodeURIComponent(label)}&emoji=${encodeURIComponent(emoji)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            categoryItems[currentCat].push({ id: data.id, value: data.value, label: label });
            renderModalItems(currentCat);
            updateCardPills(currentCat);
            document.getElementById('modal-add-label').value = '';
            document.getElementById('modal-add-emoji').value = '';
            showToast('Added successfully', 'success');
        } else {
            showToast(data.error || 'Failed to add', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

function deleteLookupItem(catKey, id) {
    if (!confirm('Delete this option? This cannot be undone.')) return;

    fetch('<?= BASE_URL ?>components/admin/admin-dashboard.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&category=${encodeURIComponent(catKey)}&id=${encodeURIComponent(id)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            categoryItems[catKey] = categoryItems[catKey].filter(i => i.id != id);
            renderModalItems(catKey);
            updateCardPills(catKey);
            showToast('Deleted', 'success');
        } else {
            showToast(data.error || 'Failed to delete', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

function updateCardPills(catKey) {
    const items = categoryItems[catKey];
    const cat   = LOOKUP_CATEGORIES[catKey];
    document.querySelectorAll('.adm-modify-btn').forEach(btn => {
        if (btn.getAttribute('onclick') === `openLookupModal('${catKey}')`) {
            const card    = btn.closest('.adm-lookup-card');
            const pillsEl = card.querySelector('.adm-lookup-pills');
            const countEl = card.querySelector('.adm-lookup-count');
            countEl.textContent = items.length;
            pillsEl.innerHTML = items.slice(0, 6).map(item =>
                `<span class="adm-pill" style="background:${cat.color}12;color:${cat.color};border-color:${cat.color}30;">${escHtml(item.label)}</span>`
            ).join('') + (items.length > 6 ? `<span class="adm-pill" style="background:#1a1a1a;color:#6b7280;border-color:#2a2a2a;">+${items.length - 6} more</span>` : '');
        }
    });
}

function showToast(msg, type = '') {
    const t = document.getElementById('adm-toast');
    t.textContent = msg;
    t.className = 'adm-toast show ' + type;
    setTimeout(() => { t.className = 'adm-toast'; }, 2800);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('modal-add-label').addEventListener('keydown', e => {
    if (e.key === 'Enter') addLookupItem();
});

// ── User Management ────────────────────────────────────────────────────────
let totalUsers = <?= count($users_rows) ?>;

function openUsersModal() {
    document.getElementById('users-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeUsersModal() {
    document.getElementById('users-modal').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('users-modal').addEventListener('click', function(e) {
    if (e.target === this) closeUsersModal();
});

function deleteUser(id, username) {
    if (!confirm(`Delete user "${username}"? This cannot be undone.`)) return;

    fetch('<?= BASE_URL ?>components/admin/admin-dashboard.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete_user&id=${encodeURIComponent(id)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Remove row from both modal and preview table
            document.querySelectorAll(`#user-row-${id}`).forEach(el => el.remove());
            totalUsers--;
            document.getElementById('users-modal-sub').textContent = totalUsers + ' accounts';
            showToast('User deleted', 'success');
        } else {
            showToast(data.error || 'Failed to delete', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

</script>