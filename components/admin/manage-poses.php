<?php
define('BASE_URL', '/FrameWise/'); 
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/FrameWise/includes/dbh.inc.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    header('Location: dashboard.php');
    exit();
}

$poses = $conn->query("
    SELECT id, pose_id, gender, name, description, category,
           body_position, mood, suitable_for, best_for_body_types,
           best_for_face_shapes, difficulty, tags, image_file, created_at
    FROM poses ORDER BY id DESC
")->fetch_all(MYSQLI_ASSOC);

$total_poses = count($poses);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Poses · Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&family=Syne:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard-style.css">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background: #080808;
    color: #e5e7eb;
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
}

.mp-wrap {
    max-width: 1300px;
    margin: 0 auto;
    padding: 32px 24px 60px;
}

.mp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 14px;
}
.mp-header-left { display: flex; align-items: center; gap: 14px; }
.mp-back-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #111;
    border: 0.5px solid #2a2a2a;
    border-radius: 9px;
    padding: 7px 13px;
    font-size: 12px;
    color: #9ca3af;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    text-decoration: none;
    transition: all .2s;
}
.mp-back-btn:hover { border-color: #444; color: #e5e7eb; }
.mp-page-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 28px;
    letter-spacing: .06em;
    color: #fff;
    line-height: 1;
}
.mp-page-sub { font-size: 12px; color: #4b5563; margin-top: 3px; }
.mp-add-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    background: #3b82f6;
    border: none;
    border-radius: 10px;
    padding: 10px 18px;
    font-size: 13px;
    color: #fff;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    font-weight: 500;
    transition: background .2s;
}
.mp-add-btn:hover { background: #2563eb; }

.mp-table-wrap {
    background: #0d0d0d;
    border: 0.5px solid #1e1e1e;
    border-radius: 16px;
    overflow: hidden;
}
.mp-table { width: 100%; border-collapse: collapse; }
.mp-table thead th {
    text-align: left;
    font-size: 10px;
    color: #4b5563;
    font-weight: 500;
    padding: 12px 16px;
    letter-spacing: .08em;
    text-transform: uppercase;
    border-bottom: 0.5px solid #1e1e1e;
    background: #0a0a0a;
    white-space: nowrap;
}
.mp-table tbody tr {
    border-bottom: 0.5px solid #141414;
    transition: background .15s;
}
.mp-table tbody tr:last-child { border-bottom: none; }
.mp-table tbody tr:hover { background: #111; }
.mp-table td { padding: 11px 16px; font-size: 12px; vertical-align: middle; }

.mp-pose-thumb {
    width: 40px; height: 40px;
    border-radius: 9px; object-fit: cover;
    border: 0.5px solid #2a2a2a; flex-shrink: 0;
}
.mp-pose-thumb-placeholder {
    width: 40px; height: 40px;
    border-radius: 9px; background: #1a1a1a;
    border: 0.5px solid #2a2a2a;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.mp-pose-name { font-size: 12px; color: #e5e7eb; font-weight: 500; }
.mp-pose-id { font-size: 10px; color: #4b5563; margin-top: 2px; font-family: monospace; }
.mp-badge {
    display: inline-block; font-size: 10px;
    padding: 2px 8px; border-radius: 5px;
    border: 0.5px solid; white-space: nowrap;
}
.mp-tags { display: flex; flex-wrap: wrap; gap: 4px; max-width: 180px; }
.mp-tag {
    font-size: 9px; padding: 2px 6px; border-radius: 4px;
    background: #1a1a1a; color: #6b7280;
    border: 0.5px solid #2a2a2a; white-space: nowrap;
}
.mp-delete-btn {
    background: transparent;
    border: 0.5px solid #2a1a1a;
    border-radius: 7px; padding: 5px 11px;
    font-size: 11px; color: #6b7280;
    cursor: pointer; font-family: 'DM Sans', sans-serif;
    transition: all .2s; display: flex; align-items: center; gap: 5px; white-space: nowrap;
}
.mp-delete-btn:hover { border-color: #ef4444; color: #ef4444; background: #1a0808; }

.mp-empty {
    padding: 60px 20px; text-align: center; color: #4b5563; font-size: 13px;
}
.mp-empty svg { margin: 0 auto 12px; display: block; opacity: .3; }

/* Modal */
.mp-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.8); backdrop-filter: blur(5px);
    align-items: center; justify-content: center; padding: 16px;
}
.mp-modal-overlay.open { display: flex; }
.mp-modal-box {
    background: #0d0d0d; border: 0.5px solid #1f1f1f;
    border-radius: 18px; width: 100%; max-width: 640px;
    max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;
}
.mp-modal-head {
    padding: 18px 20px 14px; border-bottom: 0.5px solid #1a1a1a;
    display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
}
.mp-modal-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: #f0ede8; }
.mp-modal-sub { font-size: 11px; color: #4b5563; margin-top: 2px; }
.mp-modal-close {
    background: #1a1a1a; border: 0.5px solid #2a2a2a; border-radius: 8px;
    color: #9ca3af; font-size: 11px; padding: 5px 10px; cursor: pointer;
    display: flex; align-items: center; gap: 4px; font-family: 'DM Sans', sans-serif; transition: all .2s;
}
.mp-modal-close:hover { border-color: #444; color: #ccc; }
.mp-modal-body { overflow-y: auto; flex: 1; padding: 18px 20px; }
.mp-modal-footer {
    padding: 14px 20px; border-top: 0.5px solid #1a1a1a;
    display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0;
}
.mp-modal-cancel {
    background: #111; border: 0.5px solid #2a2a2a; border-radius: 9px;
    padding: 9px 16px; font-size: 12px; color: #9ca3af;
    cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all .2s;
}
.mp-modal-cancel:hover { border-color: #444; color: #ccc; }
.mp-modal-submit {
    background: #3b82f6; border: none; border-radius: 9px;
    padding: 9px 20px; font-size: 12px; color: #fff; cursor: pointer;
    font-family: 'DM Sans', sans-serif; font-weight: 500; transition: background .2s;
    display: flex; align-items: center; gap: 6px;
}
.mp-modal-submit:hover { background: #2563eb; }
.mp-modal-submit:disabled { opacity: .5; cursor: not-allowed; }

/* Form */
.mp-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.mp-form-grid .full { grid-column: 1 / -1; }
.mp-field { display: flex; flex-direction: column; gap: 5px; }
.mp-label { font-size: 11px; color: #9ca3af; font-weight: 500; letter-spacing: .04em; }
.mp-label span { color: #ef4444; margin-left: 2px; }
.mp-input, .mp-select, .mp-textarea {
    background: #111; border: 0.5px solid #2a2a2a; border-radius: 9px;
    padding: 9px 12px; font-size: 12px; color: #e5e7eb;
    font-family: 'DM Sans', sans-serif; outline: none; transition: border-color .2s; width: 100%;
}
.mp-input:focus, .mp-select:focus, .mp-textarea:focus { border-color: #3b82f6; }
.mp-input::placeholder, .mp-textarea::placeholder { color: #374151; }
.mp-textarea { resize: vertical; min-height: 72px; }
.mp-select { cursor: pointer; }
.mp-select option { background: #111; }

.mp-img-pick {
    border: 0.5px dashed #2a2a2a; border-radius: 12px; padding: 20px;
    text-align: center; cursor: pointer; transition: all .2s; position: relative; overflow: hidden;
}
.mp-img-pick:hover { border-color: #3b82f6; background: #0a0f1a; }
.mp-img-pick input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.mp-img-pick-icon { color: #4b5563; margin-bottom: 6px; }
.mp-img-pick-label { font-size: 12px; color: #6b7280; }
.mp-img-pick-sub { font-size: 10px; color: #374151; margin-top: 3px; }
.mp-img-preview { width: 100%; max-height: 160px; object-fit: contain; border-radius: 8px; display: none; }

.mp-toast {
    position: fixed; bottom: 24px; right: 24px; background: #111;
    border: 0.5px solid #2a2a2a; border-radius: 10px; padding: 10px 16px;
    font-size: 12px; color: #e5e7eb; z-index: 99999;
    opacity: 0; transform: translateY(8px); transition: all .3s; pointer-events: none;
}
.mp-toast.show { opacity: 1; transform: translateY(0); }
.mp-toast.success { border-color: #22c55e44; color: #22c55e; }
.mp-toast.error   { border-color: #ef444444; color: #ef4444; }

@media (max-width: 640px) {
    .mp-form-grid { grid-template-columns: 1fr; }
    .mp-form-grid .full { grid-column: 1; }
    .mp-table thead th:nth-child(n+4) { display: none; }
    .mp-table td:nth-child(n+4) { display: none; }
}
</style>
</head>
<body>

<div class="mp-wrap">

    <div class="mp-header">
        <div class="mp-header-left">
            <button onclick="history.back()" class="mp-back-btn">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="13" height="13">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Back
            </button>
            <div>
                <div class="mp-page-title">Manage Poses</div>
                <div class="mp-page-sub"><?= date('l, F j Y') ?> · <?= $total_poses ?> poses · Logged in as <?= htmlspecialchars($_SESSION['username']) ?></div>
            </div>
        </div>
        <button class="mp-add-btn" onclick="openAddModal()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Pose
        </button>
    </div>

    <div class="mp-table-wrap">
        <?php if (empty($poses)): ?>
        <div class="mp-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" width="48" height="48">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/>
            </svg>
            No poses yet. Click <strong>Add Pose</strong> to get started.
        </div>
        <?php else: ?>
        <table class="mp-table">
            <thead>
                <tr>
                    <th>Pose</th>
                    <th>Category</th>
                    <th>Gender</th>
                    <th>Difficulty</th>
                    <th>Body Position</th>
                    <th>Tags</th>
                    <th>Added</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="poses-tbody">
                <?php foreach ($poses as $p):
                    $diff = strtolower($p['difficulty'] ?? 'easy');
                    $dmap = [
                        'easy'   => ['color' => '#4ade80'],
                        'medium' => ['color' => '#fbbf24'],
                        'hard'   => ['color' => '#f87171'],
                    ];
                    $dc = $dmap[$diff] ?? $dmap['easy'];
                    $tags = array_filter(explode(',', $p['tags'] ?? ''));
                    $gmap = [
                        'male'   => '#60a5fa',
                        'female' => '#f472b6',
                        'unisex' => '#a78bfa',
                    ];
                    $gc = $gmap[strtolower($p['gender'] ?? '')] ?? '#6b7280';
                ?>
                <tr id="pose-row-<?= $p['id'] ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if (!empty($p['image_file'])): ?>
                            <img src="<?= BASE_URL ?>assets/poses/<?= htmlspecialchars($p['image_file']) ?>" class="mp-pose-thumb"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="mp-pose-thumb-placeholder" style="display:none;">
                                <svg fill="none" viewBox="0 0 24 24" stroke="#4b5563" stroke-width="1.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                            </div>
                            <?php else: ?>
                            <div class="mp-pose-thumb-placeholder">
                                <svg fill="none" viewBox="0 0 24 24" stroke="#4b5563" stroke-width="1.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="mp-pose-name"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="mp-pose-id"><?= htmlspecialchars($p['pose_id']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span style="font-size:11px;color:#9ca3af;"><?= ucfirst(htmlspecialchars($p['category'] ?? '—')) ?></span></td>
                    <td>
                        <span class="mp-badge" style="background:transparent;color:<?= $gc ?>;border:1px solid <?= $gc ?>;">
                            <?= ucfirst(htmlspecialchars($p['gender'] ?? '—')) ?>
                        </span>
                    </td>
                    <td>
                        <span class="mp-badge" style="background:transparent;color:<?= $dc['color'] ?>;border:1px solid <?= $dc['color'] ?>;">
                            <?= ucfirst($diff) ?>
                        </span>
                    </td>
                    <td><span style="font-size:11px;color:#9ca3af;"><?= ucfirst(htmlspecialchars($p['body_position'] ?? '—')) ?></span></td>
                    <td>
                        <div class="mp-tags">
                            <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                            <span class="mp-tag"><?= htmlspecialchars(trim($tag)) ?></span>
                            <?php endforeach; ?>
                            <?php if (count($tags) > 3): ?>
                            <span class="mp-tag" style="color:#4b5563;">+<?= count($tags) - 3 ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="font-size:10px;color:#4b5563;white-space:nowrap;"><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                    <td style="text-align:right;">
                        <button class="mp-delete-btn" onclick="deletePose(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                            Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<!-- Add Pose Modal -->
<div id="add-modal" class="mp-modal-overlay">
    <div class="mp-modal-box">
        <div class="mp-modal-head">
            <div>
                <div class="mp-modal-title">Add New Pose</div>
                <div class="mp-modal-sub">Fill in the details and upload a reference image</div>
            </div>
            <button class="mp-modal-close" onclick="closeAddModal()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="11" height="11">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Close
            </button>
        </div>
        <div class="mp-modal-body">
            <div class="mp-form-grid">
                <div class="mp-field full">
                    <label class="mp-label">Pose Name <span>*</span></label>
                    <input type="text" id="f-name" class="mp-input" placeholder="e.g. Walking Toward Camera">
                </div>
                <div class="mp-field full">
                    <label class="mp-label">Description</label>
                    <textarea id="f-desc" class="mp-textarea" placeholder="Brief description of the pose…"></textarea>
                </div>
                <div class="mp-field full">
                    <label class="mp-label">Reference Image</label>
                    <div class="mp-img-pick">
                        <input type="file" id="f-image" accept="image/*" onchange="previewImage(this)">
                        <div id="img-pick-content">
                            <div class="mp-img-pick-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="28" height="28">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                                </svg>
                            </div>
                            <div class="mp-img-pick-label">Click to upload or drag & drop</div>
                            <div class="mp-img-pick-sub">JPG, PNG, WEBP · max 5 MB</div>
                        </div>
                        <img id="img-preview" class="mp-img-preview" alt="Preview">
                    </div>
                </div>
                <div class="mp-field">
                    <label class="mp-label">Category <span>*</span></label>
                    <input type="text" id="f-category" class="mp-input" placeholder="e.g. fashion, portrait">
                </div>
                <div class="mp-field">
                    <label class="mp-label">Gender <span>*</span></label>
                    <select id="f-gender" class="mp-select">
                        <option value="">Select…</option>
                        <option value="unisex">Unisex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="mp-field">
                    <label class="mp-label">Body Position</label>
                    <select id="f-body-position" class="mp-select">
                        <option value="">Select…</option>
                        <option value="standing">Standing</option>
                        <option value="sitting">Sitting</option>
                        <option value="lying">Lying</option>
                        <option value="crouching">Crouching</option>
                        <option value="walking">Walking</option>
                        <option value="jumping">Jumping</option>
                        <option value="leaning">Leaning</option>
                        <option value="kneeling">Kneeling</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mp-field">
                    <label class="mp-label">Difficulty <span>*</span></label>
                    <select id="f-difficulty" class="mp-select">
                        <option value="">Select…</option>
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>
                <div class="mp-field">
                    <label class="mp-label">Mood</label>
                    <input type="text" id="f-mood" class="mp-input" placeholder="e.g. confident, calm">
                </div>
                <div class="mp-field">
                    <label class="mp-label">Suitable For</label>
                    <input type="text" id="f-suitable" class="mp-input" placeholder="e.g. fashion, street">
                </div>
                <div class="mp-field">
                    <label class="mp-label">Best for Body Types</label>
                    <input type="text" id="f-body-types" class="mp-input" placeholder="e.g. slim, athletic">
                </div>
                <div class="mp-field">
                    <label class="mp-label">Best for Face Shapes</label>
                    <input type="text" id="f-face-shapes" class="mp-input" placeholder="e.g. oval, round">
                </div>
                <div class="mp-field full">
                    <label class="mp-label">Tags <small style="color:#4b5563;">(comma separated)</small></label>
                    <input type="text" id="f-tags" class="mp-input" placeholder="e.g. movement,walking,full-body,dynamic">
                </div>
            </div>
        </div>
        <div class="mp-modal-footer">
            <button class="mp-modal-cancel" onclick="closeAddModal()">Cancel</button>
            <button class="mp-modal-submit" id="submit-btn" onclick="submitPose()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="13" height="13">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Pose
            </button>
        </div>
    </div>
</div>

<div id="mp-toast" class="mp-toast"></div>

<script>
let totalPoses = <?= $total_poses ?>;

function openAddModal() {
    document.getElementById('add-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeAddModal() {
    document.getElementById('add-modal').classList.remove('open');
    document.body.style.overflow = '';
    resetForm();
}
document.getElementById('add-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});

function previewImage(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('img-preview');
        const content = document.getElementById('img-pick-content');
        preview.src = e.target.result;
        preview.style.display = 'block';
        content.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function resetForm() {
    ['f-name','f-desc','f-category','f-mood','f-suitable','f-body-types','f-face-shapes','f-tags'].forEach(id => {
        document.getElementById(id).value = '';
    });
    ['f-gender','f-body-position','f-difficulty'].forEach(id => {
        document.getElementById(id).selectedIndex = 0;
    });
    document.getElementById('f-image').value = '';
    document.getElementById('img-preview').style.display = 'none';
    document.getElementById('img-pick-content').style.display = 'block';
}

function submitPose() {
    const name       = document.getElementById('f-name').value.trim();
    const category   = document.getElementById('f-category').value.trim();
    const gender     = document.getElementById('f-gender').value;
    const difficulty = document.getElementById('f-difficulty').value;

    if (!name)       { showToast('Pose name is required', 'error'); return; }
    if (!category)   { showToast('Category is required', 'error'); return; }
    if (!gender)     { showToast('Gender is required', 'error'); return; }
    if (!difficulty) { showToast('Difficulty is required', 'error'); return; }

    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="13" height="13" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg> Saving…`;

    const fd = new FormData();
    fd.append('action', 'add_pose');
    fd.append('name',               name);
    fd.append('description',        document.getElementById('f-desc').value.trim());
    fd.append('category',           category);
    fd.append('gender',             gender);
    fd.append('body_position',      document.getElementById('f-body-position').value);
    fd.append('difficulty',         difficulty);
    fd.append('mood',               document.getElementById('f-mood').value.trim());
    fd.append('suitable_for',       document.getElementById('f-suitable').value.trim());
    fd.append('best_for_body_types',  document.getElementById('f-body-types').value.trim());
    fd.append('best_for_face_shapes', document.getElementById('f-face-shapes').value.trim());
    fd.append('tags',               document.getElementById('f-tags').value.trim());

    const imgFile = document.getElementById('f-image').files[0];
    if (imgFile) fd.append('image', imgFile);

    fetch('<?= BASE_URL ?>components/admin/manage-poses.inc.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg> Add Pose`;
        if (data.success) {
            closeAddModal();
            showToast('Pose added successfully!', 'success');
            appendPoseRow(data.pose);
            totalPoses++;
        } else {
            showToast(data.error || 'Failed to add pose', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg> Add Pose`;
        showToast('Network error', 'error');
    });
}

function appendPoseRow(p) {
    const tbody = document.getElementById('poses-tbody');
    if (!tbody) return;
    const diff = (p.difficulty || 'easy').toLowerCase();
    const dmap = {
        easy:   { color: '#4ade80' },
        medium: { color: '#fbbf24' },
        hard:   { color: '#f87171' },
    };
    const dc = dmap[diff] || dmap.easy;
    const gmap = { male: '#60a5fa', female: '#f472b6', unisex: '#a78bfa' };
    const gc = gmap[(p.gender||'').toLowerCase()] || '#6b7280';
    const tags = (p.tags||'').split(',').filter(Boolean);
    const tagHtml = tags.slice(0,3).map(t=>`<span class="mp-tag">${escHtml(t.trim())}</span>`).join('')
        + (tags.length > 3 ? `<span class="mp-tag" style="color:#4b5563;">+${tags.length-3}</span>` : '');
    const thumbHtml = p.image_file
        ? `<img src="<?= BASE_URL ?>assets/poses/${escHtml(p.image_file)}" class="mp-pose-thumb" onerror="this.style.display='none'">`
        : `<div class="mp-pose-thumb-placeholder"><svg fill="none" viewBox="0 0 24 24" stroke="#4b5563" stroke-width="1.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg></div>`;
    const today = new Date().toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
    const tr = document.createElement('tr');
    tr.id = `pose-row-${p.id}`;
    tr.style.borderBottom = '0.5px solid #141414';
    tr.innerHTML = `
        <td><div style="display:flex;align-items:center;gap:10px;">${thumbHtml}<div><div class="mp-pose-name">${escHtml(p.name)}</div><div class="mp-pose-id">${escHtml(p.pose_id)}</div></div></div></td>
        <td><span style="font-size:11px;color:#9ca3af;">${escHtml(p.category||'—')}</span></td>
        <td><span class="mp-badge" style="background:transparent;color:${gc};border:1px solid ${gc};">${escHtml(p.gender||'—')}</span></td>
        <td><span class="mp-badge" style="background:transparent;color:${dc.color};border:1px solid ${dc.color};">${diff.charAt(0).toUpperCase()+diff.slice(1)}</span></td>
        <td><span style="font-size:11px;color:#9ca3af;">${escHtml(p.body_position||'—')}</span></td>
        <td><div class="mp-tags">${tagHtml}</div></td>
        <td style="font-size:10px;color:#4b5563;white-space:nowrap;">${today}</td>
        <td style="text-align:right;"><button class="mp-delete-btn" onclick="deletePose(${p.id}, '${escHtml(p.name).replace(/'/g,"\\'")}')">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
            Delete</button></td>`;
    tbody.insertBefore(tr, tbody.firstChild);
}

function deletePose(id, name) {
    if (!confirm(`Delete pose "${name}"? This cannot be undone.`)) return;
    fetch('<?= BASE_URL ?>components/admin/manage-poses.inc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete_pose&id=${encodeURIComponent(id)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`pose-row-${id}`);
            if (row) { row.style.opacity = '0'; row.style.transition = 'opacity .3s'; setTimeout(() => row.remove(), 300); }
            totalPoses--;
            showToast('Pose deleted', 'success');
        } else {
            showToast(data.error || 'Failed to delete', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

function showToast(msg, type = '') {
    const t = document.getElementById('mp-toast');
    t.textContent = msg;
    t.className = 'mp-toast show ' + type;
    setTimeout(() => { t.className = 'mp-toast'; }, 2800);
}

function escHtml(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>

</body>
</html>