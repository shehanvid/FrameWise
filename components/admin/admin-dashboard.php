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

$me = $_SESSION['usersId'] ?? $_SESSION['userid'] ?? 0;

$total_users  = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_shoots = $conn->query("SELECT COUNT(*) FROM shoot_results")->fetch_row()[0];

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

$fallback = [
    'label'  => '',
    'emoji'  => '',
    'color'  => '#6b7280',
    'bg'     => '#111111',
    'border' => '#2a2a2a',
];

$all_cats = [];

$cat_rows = $conn->query(
    "SELECT DISTINCT category FROM lookup_options ORDER BY category ASC"
)->fetch_all(MYSQLI_ASSOC);

foreach ($cat_rows as $row) {
    $k = $row['category'];
    $d = $cat_display_config[$k] ?? $fallback;
    $d['label'] = $d['label'] ?: ucwords(str_replace('_', ' ', $k));
    $d['table'] = 'lookup_options';
    $all_cats[$k] = $d;
}

$users_list = $conn->query("
    SELECT usersId as id, usersName as username, usersEmail as email, isAdmin
    FROM users ORDER BY usersId DESC
")->fetch_all(MYSQLI_ASSOC);

$poses_list = $conn->query("
    SELECT id, pose_id, gender, name, category, difficulty, image_file
    FROM poses ORDER BY id DESC
")->fetch_all(MYSQLI_ASSOC);

$total_poses = count($poses_list);

$moodDisp = $cat_display_config['mood'] ?? $fallback;
$all_cats['mood'] = array_merge($moodDisp, [
    'label' => $moodDisp['label'] ?: 'Moods',
    'table' => 'moods',
]);

$equipDisp = $cat_display_config['equipment'] ?? $fallback;
$all_cats['equipment'] = array_merge($equipDisp, [
    'label' => $equipDisp['label'] ?: 'Equipment',
    'table' => 'equipment_options',
]);

$stDisp = $cat_display_config['shoot_type'] ?? $fallback;
$all_cats['shoot_type'] = array_merge($stDisp, [
    'label' => $stDisp['label'] ?: 'Shoot Types',
    'table' => 'shoot_types',
]);

$cat_entries = [];

foreach ($all_cats as $ck => $ci) {
    if ($ck === 'mood') {
        $res = $conn->query("SELECT id, value, label, '' as emoji FROM moods ORDER BY label ASC");
    } elseif ($ck === 'equipment') {
        $res = $conn->query("SELECT id, value, label FROM equipment_options ORDER BY label ASC");
    } elseif ($ck === 'shoot_type') {
        $res = $conn->query("SELECT id, value, label FROM shoot_types ORDER BY label ASC");
    } else {
        $st = $conn->prepare("SELECT id, value, label FROM lookup_options WHERE category = ? ORDER BY sort_order ASC, label ASC");
        $st->bind_param("s", $ck);
        $st->execute();
        $res = $st->get_result();
    }
    $cat_entries[$ck] = $res->fetch_all(MYSQLI_ASSOC);
}

$mood_clr = [];
$mood_meta = $conn->query("SELECT value, color, bg_gradient FROM moods")->fetch_all(MYSQLI_ASSOC);
foreach ($mood_meta as $mm) {
    $mood_clr[strtolower($mm['value'])] = ['color' => $mm['color'], 'bg' => $mm['bg_gradient']];
}
$mood_clr['default'] = ['color' => '#e5e7eb', 'bg' => 'linear-gradient(135deg,#1a1a1a,#2a2a2a)'];

$mood_stats = $conn->query("
    SELECT mood, COUNT(*) as cnt FROM shoot_results
    GROUP BY mood ORDER BY cnt DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
$mood_top = !empty($mood_stats) ? $mood_stats[0]['cnt'] : 1;

$loc_stats = $conn->query("
    SELECT location, COUNT(*) as cnt FROM shoot_results
    GROUP BY location ORDER BY cnt DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$type_clr = [];
$st_meta = $conn->query("SELECT value, color FROM shoot_types")->fetch_all(MYSQLI_ASSOC);
foreach ($st_meta as $st) {
    $type_clr[strtolower($st['value'])] = $st['color'];
}
$type_clr['default'] = '#6b7280';

$type_stats = $conn->query("
    SELECT shoot_type, COUNT(*) as cnt FROM shoot_results
    GROUP BY shoot_type ORDER BY cnt DESC
")->fetch_all(MYSQLI_ASSOC);

$done_stats = $conn->query("
    SELECT shoot_type,
           COUNT(*) as total,
           SUM(CASE WHEN ai_plan IS NOT NULL THEN 1 ELSE 0 END) as done
    FROM shoot_results
    GROUP BY shoot_type ORDER BY total DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-dashboard-style.css">

<div class="db-main">

    <div class="db-page-header">
        <div>
            <div class="db-page-title">Admin Panel</div>
            <div class="db-page-sub"><?= date('l, F j Y') ?> · Logged in as <?= htmlspecialchars($_SESSION['username']) ?></div>
        </div>
    </div>

    <div class="db-stats-grid" style="grid-template-columns:repeat(2,1fr);max-width:480px;">

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

    <div class="section-head">
        <svg fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="1.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Option Management
    </div>

    <div class="options-grid">
        <?php foreach ($all_cats as $ck => $ci):
            $entries = $cat_entries[$ck];
            $n = count($entries);
        ?>
        <div class="option-box">
            <div class="option-box-top">
                <div class="option-box-name">
                    <span class="option-icon"><?= $ci['emoji'] ?></span>
                    <?= htmlspecialchars($ci['label']) ?>
                </div>
                <span class="option-count" style="color:<?= $ci['color'] ?>;border:1px solid <?= $ci['color'] ?>;"><?= $n ?></span>
            </div>
            <div class="tag-list">
                <?php foreach (array_slice($entries, 0, 6) as $entry): ?>
                <span class="tag-item" style="color:<?= $ci['color'] ?>;border-color:<?= $ci['color'] ?>;">
                    <?= htmlspecialchars($entry['label']) ?>
                </span>
                <?php endforeach; ?>
                <?php if ($n > 6): ?>
                <span class="tag-item" style="background:#1a1a1a;color:#6b7280;border-color:#2a2a2a;">+<?= $n - 6 ?> more</span>
                <?php endif; ?>
            </div>
            <button class="edit-btn" onclick="openCatPopup('<?= $ck ?>')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                Modify
            </button>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="section-head">
        <svg fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="1.5" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
        </svg>
        Management
    </div>

    <div class="mgmt-grid">

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="border:1px solid #3b82f6;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="db-card-title">All Users</div>
                        <div class="db-card-sub"><?= count($users_list) ?> registered accounts</div>
                    </div>
                </div>
                <button class="edit-btn" style="width:auto;padding:6px 14px;" onclick="openUsersPopup()">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="12" height="12">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    View All
                </button>
            </div>
            <div class="db-card-body users-table-wrap" style="padding-top:4px;">
                <table>
                    <thead>
                        <tr style="border-bottom:0.5px solid #1e1e1e;">
                            <th class="th-label">User</th>
                            <th class="th-label">Email</th>
                            <th class="th-label">Role</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($users_list, 0, 5) as $u): ?>
                        <tr style="border-bottom:0.5px solid #141414;" id="urow-<?= $u['id'] ?>">
                            <td style="padding:9px 0;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="user-avatar"><?= strtoupper(substr($u['username'], 0, 1)) ?></div>
                                    <span style="font-size:12px;color:#e5e7eb;"><?= htmlspecialchars($u['username']) ?></span>
                                </div>
                            </td>
                            <td style="font-size:11px;color:#6b7280;padding:9px 0;max-width:120px;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="padding:9px 0;">
                                <?php if ($u['isAdmin']): ?>
                                    <span class="role-badge is-admin">Admin</span>
                                <?php else: ?>
                                    <span class="role-badge is-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:9px 0;text-align:right;">
                                <?php if ($u['id'] == $me): ?>
                                    <span class="you-label">You</span>
                                <?php elseif (!$u['isAdmin']): ?>
                                    <button class="remove-btn" onclick="removeUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        Delete
                                    </button>
                                <?php else: ?>
                                    <span class="you-label">Admin</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="border:1px solid #4ade80;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="db-card-title">Poses Library</div>
                        <div class="db-card-sub"><?= $total_poses ?> poses in database</div>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>components/admin/manage-poses.php" class="edit-btn" style="width:auto;padding:6px 14px;">
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
                            <th class="th-label">Pose</th>
                            <th class="th-label">Category</th>
                            <th class="th-label">Gender</th>
                            <th class="th-label">Difficulty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($poses_list, 0, 5) as $p):
                            $dclr = [
                                'easy'   => '#4ade80',
                                'medium' => '#fbbf24',
                                'hard'   => '#f87171',
                            ];
                            $dv = strtolower($p['difficulty'] ?? 'easy');
                            $dc = $dclr[$dv] ?? $dclr['easy'];
                        ?>
                        <tr style="border-bottom:0.5px solid #141414;">
                            <td style="padding:9px 0;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <?php if (!empty($p['image_file'])): ?>
                                    <div class="pose-thumb-wrap">
                                        <img src="<?= BASE_URL ?>assets/poses/<?= htmlspecialchars($p['image_file']) ?>"
                                             onerror="this.style.display='none';this.parentElement.style.background='#1a1a1a';">
                                    </div>
                                    <?php else: ?>
                                    <div class="pose-no-img">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="#4b5563" stroke-width="1.5" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                    </div>
                                    <?php endif; ?>
                                    <span style="font-size:12px;color:#e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($p['name']) ?></span>
                                </div>
                            </td>
                            <td style="padding:9px 0;font-size:11px;color:#9ca3af;"><?= ucfirst(htmlspecialchars($p['category'] ?? '—')) ?></td>
                            <td style="padding:9px 0;font-size:11px;color:#9ca3af;"><?= ucfirst(htmlspecialchars($p['gender'] ?? '—')) ?></td>
                            <td style="padding:9px 0;">
                                <span class="diff-badge" style="color:<?= $dc ?>;border-color:<?= $dc ?>;"><?= ucfirst($dv) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div id="users-popup" class="popup-bg">
        <div class="users-popup-wrap">
            <div class="popup-top">
                <div class="popup-top-left">
                    <div class="popup-icon" style="background:#0d0f1a;border:0.5px solid #1e2a4a;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="1.5" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="popup-title">All Users</div>
                        <div class="popup-sub" id="users-count-label"><?= count($users_list) ?> accounts</div>
                    </div>
                </div>
                <button class="close-btn" onclick="closeUsersPopup()">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="11" height="11">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Close
                </button>
            </div>
            <div class="popup-body">
                <table style="width:100%;border-collapse:collapse;">
                    <thead style="position:sticky;top:0;background:#0d0d0d;z-index:1;">
                        <tr style="border-bottom:0.5px solid #1e1e1e;">
                            <th class="th-label" style="padding:8px 0;">User</th>
                            <th class="th-label" style="padding:8px 0;">Email</th>
                            <th class="th-label" style="padding:8px 0;">Role</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="users-popup-list">
                        <?php foreach ($users_list as $u): ?>
                        <tr style="border-bottom:0.5px solid #141414;" id="urow-<?= $u['id'] ?>">
                            <td style="padding:9px 0;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="user-avatar"><?= strtoupper(substr($u['username'], 0, 1)) ?></div>
                                    <span style="font-size:12px;color:#e5e7eb;"><?= htmlspecialchars($u['username']) ?></span>
                                </div>
                            </td>
                            <td style="font-size:11px;color:#6b7280;padding:9px 0;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="padding:9px 0;">
                                <?php if ($u['isAdmin']): ?>
                                    <span class="role-badge is-admin">Admin</span>
                                <?php else: ?>
                                    <span class="role-badge is-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:9px 0;text-align:right;">
                                <?php if ($u['id'] == $me): ?>
                                    <span class="you-label">You</span>
                                <?php elseif (!$u['isAdmin']): ?>
                                    <button class="remove-btn" onclick="removeUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        Delete
                                    </button>
                                <?php else: ?>
                                    <span class="you-label">Admin</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section-head">
        <svg fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="1.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm9.75-4.875C12.75 7.629 13.254 7.125 13.875 7.125h2.25c.621 0 1.125.504 1.125 1.125v11.625c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.25zm9.75-3.375c0-.621.504-1.125 1.125-1.125h.75c.621 0 1.125.504 1.125 1.125v15c0 .621-.504 1.125-1.125 1.125h-.75A1.125 1.125 0 0121 19.875V4.875z"/></svg>
        Platform Analytics
    </div>

    <div class="db-grid-4">

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="border:1px solid #c084fc;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#c084fc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Mood Breakdown</div>
                        <div class="db-card-sub">All users · all shoots</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php if (empty($mood_stats)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else: ?>
                <?php foreach ($mood_stats as $m):
                    $mk = strtolower($m['mood']);
                    $mc = $mood_clr[$mk] ?? $mood_clr['default'];
                ?>
                <div class="db-mood-row">
                    <div class="db-mood-swatch" style="background:<?= $mc['bg'] ?>;"></div>
                    <div class="db-mood-name"><?= ucfirst(htmlspecialchars($m['mood'])) ?></div>
                    <div class="db-mood-count"><?= $m['cnt'] ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="border:1px solid #4ade80;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c-.317-.159.69-.159 1.006 0l4.994 2.497c.317.159.69.159 1.006 0z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Top Locations</div>
                        <div class="db-card-sub">All users · by shoot count</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php if (empty($loc_stats)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else: ?>
                <?php foreach ($loc_stats as $i => $l): ?>
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

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="border:1px solid #4ade80;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#4ade80" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Completion by Type</div>
                        <div class="db-card-sub">AI plan generated rate</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body" style="padding-top:8px;">
                <?php if (empty($done_stats)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else: ?>
                <?php foreach ($done_stats as $cr):
                    $pct = round(($cr['done'] / max($cr['total'], 1)) * 100);
                    $col = $type_clr[strtolower($cr['shoot_type'])] ?? $type_clr['default'];
                ?>
                <div class="db-mood-row">
                    <div class="db-mood-swatch" style="background:<?= $col ?>22;border:0.5px solid <?= $col ?>44;"></div>
                    <div class="db-mood-name"><?= ucfirst(htmlspecialchars($cr['shoot_type'])) ?></div>
                    <div class="db-mood-count" style="color:<?= $col ?>;"><?= $pct ?>%</div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-head">
                <div class="db-card-head-left">
                    <div class="db-card-icon" style="border:1px solid #c084fc;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="#c084fc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"/></svg>
                    </div>
                    <div>
                        <div class="db-card-title">Type Distribution</div>
                        <div class="db-card-sub">All time · <?= $total_shoots ?> shoots</div>
                    </div>
                </div>
            </div>
            <div class="db-card-body">
                <?php if (empty($type_stats)): ?>
                    <div style="color:#4b5563;font-size:12px;padding:12px 0;">No data yet.</div>
                <?php else:
                    $circ = 276.46;
                    $off  = 0;
                    $segs = [];
                    foreach ($type_stats as $t) {
                        $p   = $t['cnt'] / max($total_shoots, 1);
                        $col = $type_clr[strtolower($t['shoot_type'])] ?? $type_clr['default'];
                        $segs[] = [
                            'type'  => $t['shoot_type'],
                            'cnt'   => $t['cnt'],
                            'pct'   => round($p * 100),
                            'dash'  => round($p * $circ, 1),
                            'off'   => -$off,
                            'color' => $col,
                        ];
                        $off += round($p * $circ, 1);
                    }
                ?>
                <div class="db-donut-wrap">
                    <svg width="100" height="100" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="44" fill="none" stroke="#1a1a1a" stroke-width="20"/>
                        <?php foreach ($segs as $sg): ?>
                        <circle cx="60" cy="60" r="44" fill="none"
                            stroke="<?= $sg['color'] ?>" stroke-width="20"
                            stroke-dasharray="<?= $sg['dash'] ?> <?= $circ - $sg['dash'] ?>"
                            stroke-dashoffset="<?= $sg['off'] ?>"
                            stroke-linecap="butt"
                            transform="rotate(-90 60 60)"/>
                        <?php endforeach; ?>
                        <text x="60" y="55" text-anchor="middle" fill="#fff" font-family="Bebas Neue,sans-serif" font-size="20"><?= $total_shoots ?></text>
                        <text x="60" y="68" text-anchor="middle" fill="#6b7280" font-family="DM Sans,sans-serif" font-size="10">total</text>
                    </svg>
                    <div class="db-donut-legend">
                        <?php foreach ($segs as $sg): ?>
                        <div class="db-donut-legend-item">
                            <div class="db-donut-legend-dot" style="background:<?= $sg['color'] ?>;"></div>
                            <div>
                                <div class="db-donut-label"><?= ucfirst(htmlspecialchars($sg['type'])) ?></div>
                                <div class="db-donut-pct" style="color:<?= $sg['color'] ?>;"><?= $sg['pct'] ?>%</div>
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

<div id="cat-popup" class="popup-bg">
    <div class="popup-wrap">
        <div class="popup-top">
            <div class="popup-top-left">
                <div class="popup-icon" id="cat-popup-icon"></div>
                <div>
                    <div class="popup-title" id="cat-popup-title">Options</div>
                    <div class="popup-sub" id="cat-popup-sub">Manage entries</div>
                </div>
            </div>
            <button class="close-btn" onclick="closeCatPopup()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="11" height="11">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Close
            </button>
        </div>
        <div class="popup-body" id="cat-popup-list"></div>
        <div class="popup-footer">
            <div class="new-item-row">
                <input type="text" id="new-entry-label" class="new-item-input" placeholder="Add new option label…">
                <input type="text" id="new-entry-emoji" class="emoji-box" placeholder="😊" style="display:none;">
                <button class="save-btn" onclick="addEntry()">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add
                </button>
            </div>
        </div>
    </div>
</div>

<div id="flash" class="flash-msg"></div>

<script>
const ALL_CATS = <?= json_encode($all_cats) ?>;
let entries = <?= json_encode($cat_entries) ?>;
let activeCat = null;
let userCount = <?= count($users_list) ?>;

function openCatPopup(k) {
    activeCat = k;
    const c = ALL_CATS[k];
    document.getElementById('cat-popup-title').textContent = c.label;
    document.getElementById('cat-popup-sub').textContent = entries[k].length + ' entries';
    document.getElementById('cat-popup-icon').style.cssText =
        `background:transparent;border:1px solid ${c.color};font-size:16px;display:flex;align-items:center;justify-content:center;color:${c.color};`;
    document.getElementById('cat-popup-icon').textContent = c.emoji;

    const emojiIn = document.getElementById('new-entry-emoji');
    emojiIn.style.display = k === 'mood' ? 'block' : 'none';
    emojiIn.value = '';

    drawEntries(k);
    document.getElementById('cat-popup').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeCatPopup() {
    document.getElementById('cat-popup').classList.remove('open');
    document.body.style.overflow = '';
    activeCat = null;
}

document.getElementById('cat-popup').addEventListener('click', function(e) {
    if (e.target === this) closeCatPopup();
});

function drawEntries(k) {
    const list = document.getElementById('cat-popup-list');
    document.getElementById('cat-popup-sub').textContent = entries[k].length + ' entries';

    if (!entries[k].length) {
        list.innerHTML = '<div style="color:#4b5563;font-size:12px;padding:24px 0;text-align:center;">No entries yet. Add one below.</div>';
        return;
    }

    list.innerHTML = entries[k].map(item => `
        <div class="list-row" id="entry-${k}-${item.id}">
            <div class="list-row-label">${safeHtml(item.label)}</div>
            <div class="list-row-val">${safeHtml(item.value)}</div>
            <button class="remove-btn" onclick="removeEntry('${k}', ${item.id})">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                Delete
            </button>
        </div>
    `).join('');
}

function addEntry() {
    const label = document.getElementById('new-entry-label').value.trim();
    const emoji = document.getElementById('new-entry-emoji').value.trim();
    if (!label) { flash('Please enter a label', 'err'); return; }

    fetch('<?= BASE_URL ?>components/admin/admin-dashboard.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&category=${encodeURIComponent(activeCat)}&label=${encodeURIComponent(label)}&emoji=${encodeURIComponent(emoji)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            entries[activeCat].push({ id: data.id, value: data.value, label });
            drawEntries(activeCat);
            refreshPills(activeCat);
            document.getElementById('new-entry-label').value = '';
            document.getElementById('new-entry-emoji').value = '';
            flash('Added successfully', 'ok');
        } else {
            flash(data.error || 'Failed to add', 'err');
        }
    })
    .catch(() => flash('Network error', 'err'));
}

function removeEntry(k, id) {
    if (!confirm('Delete this option? This cannot be undone.')) return;

    fetch('<?= BASE_URL ?>components/admin/admin-dashboard.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&category=${encodeURIComponent(k)}&id=${encodeURIComponent(id)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            entries[k] = entries[k].filter(i => i.id != id);
            drawEntries(k);
            refreshPills(k);
            flash('Deleted', 'ok');
        } else {
            flash(data.error || 'Failed to delete', 'err');
        }
    })
    .catch(() => flash('Network error', 'err'));
}

function refreshPills(k) {
    const list = entries[k];
    const cat  = ALL_CATS[k];
    document.querySelectorAll('.edit-btn').forEach(btn => {
        if (btn.getAttribute('onclick') === `openCatPopup('${k}')`) {
            const box    = btn.closest('.option-box');
            const tags   = box.querySelector('.tag-list');
            const count  = box.querySelector('.option-count');
            count.textContent = list.length;
            count.style.border = `1px solid ${cat.color}`;
            tags.innerHTML = list.slice(0, 6).map(item =>
                `<span class="tag-item" style="color:${cat.color};border-color:${cat.color};">${safeHtml(item.label)}</span>`
            ).join('') + (list.length > 6 ? `<span class="tag-item" style="background:#1a1a1a;color:#6b7280;border-color:#2a2a2a;">+${list.length - 6} more</span>` : '');
        }
    });
}

function openUsersPopup() {
    document.getElementById('users-popup').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeUsersPopup() {
    document.getElementById('users-popup').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('users-popup').addEventListener('click', function(e) {
    if (e.target === this) closeUsersPopup();
});

function removeUser(id, name) {
    if (!confirm(`Delete user "${name}"? This cannot be undone.`)) return;

    fetch('<?= BASE_URL ?>components/admin/admin-dashboard.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete_user&id=${encodeURIComponent(id)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll(`#urow-${id}`).forEach(el => el.remove());
            userCount--;
            document.getElementById('users-count-label').textContent = userCount + ' accounts';
            flash('User deleted', 'ok');
        } else {
            flash(data.error || 'Failed to delete', 'err');
        }
    })
    .catch(() => flash('Network error', 'err'));
}

function flash(msg, type) {
    const el = document.getElementById('flash');
    el.textContent = msg;
    el.className = 'flash-msg show ' + type;
    setTimeout(() => { el.className = 'flash-msg'; }, 2800);
}

function safeHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('new-entry-label').addEventListener('keydown', e => {
    if (e.key === 'Enter') addEntry();
});
</script>