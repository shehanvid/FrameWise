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
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/manage-poses-style.css">
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