
<?php
// ─── Process form submission ───────────────────────────────────────────────
$errors   = [];
$success  = false;
$plan     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitise & validate ──────────────────────────────────────────────
    $location   = trim($_POST['location']   ?? '');
    $location_lat = trim($_POST['location_lat'] ?? '');
    $location_lng = trim($_POST['location_lng'] ?? '');
    $datetime   = trim($_POST['datetime']   ?? '');
    $shoot_type = trim($_POST['shoot_type'] ?? '');
    $mood       = trim($_POST['mood']       ?? '');
    $outfit     = trim($_POST['outfit']     ?? '');
    $gear        = trim($_POST['gear']        ?? '');
    $environment = trim($_POST['environment'] ?? 'outdoor');
    $backdrop    = trim($_POST['backdrop']    ?? '');

    if ($location   === '') $errors[] = 'Location is required.';
    if ($datetime   === '') $errors[] = 'Date & time is required.';
    if ($shoot_type === '') $errors[] = 'Shoot type is required.';
    if ($mood       === '') $errors[] = 'Mood is required.';

    // ── Generate plan if no errors ───────────────────────────────────────
    if (empty($errors)) {
        $success = true;

        // Format date nicely
        $dt_obj       = new DateTime($datetime);
        $date_display = $dt_obj->format('l, F j Y \a\t g:i A');

        $plan = [
            'location'    => htmlspecialchars($location),
            'location_lat' => $location_lat,
            'location_lng' => $location_lng,
            'datetime'    => $date_display,
            'shoot_type'  => ucfirst(htmlspecialchars($shoot_type)),
            'mood'        => ucfirst(htmlspecialchars($mood)),
            'outfit'      => $outfit ? htmlspecialchars($outfit) : '—',
            'image_name'  => $image_name,
            'shot_list'   => getShotList($conn, $shoot_type),
            'gear'        => $gear,
            'environment' => $environment,
            'backdrop'    => $backdrop,
        ];
    }
}

// ─── Helpers ───────────────────────────────────────────────────────────────
function old(string $field, string $default = ''): string {
    return htmlspecialchars($_POST[$field] ?? $default);
}
function selected(string $field, string $value): string {
    return (($_POST[$field] ?? '') === $value) ? 'selected' : '';
}
?>
<link rel="stylesheet" href="assets/css/main-form.css">
<div class="container">

  <?php if (!empty($errors)): ?>
  <div class="error-box">
    <?php foreach ($errors as $e): ?>
      <p>&#9632; <?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($success && $plan): ?>
  <!-- ── Result ──────────────────────────────────────────────────────────── -->
  <div class="result-card">
    <div class="result-header">
      <div class="result-header-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
        </svg>
      </div>
      <div>

  <?php else: ?>
  <!-- ── Form ───────────────────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <div class="header-badge">
        <div class="dot"></div>
        Studio AI
      </div>
      <div class="adv-title">Photo Shoot<br>Planner</div>
      <p>Fill in the details to generate your personalised shoot plan</p>
      <div class="progress-bar">
        <div class="progress-fill" id="progress-fill"></div>
      </div>
      <div class="progress-meta">
        <span>Completion</span>
        <span id="progress-pct">0%</span>
      </div>
    </div>

    <div class="card-body">
      <!-- Location + DateTime -->
      <div class="field-row">
        <div class="field">
          <label>Location</label>
          <button type="button" id="map-pick-btn" onclick="openMapModal()" style="
            display: inline-flex; align-items: center; gap: 6px;
            background: #0d0d0d; border: 0.5px solid #2e2e2e; border-radius: 10px;
            padding: 10px 13px; color: #9ca3af; font-family: 'DM Sans', sans-serif;
            font-size: 13px; cursor: pointer; transition: all .2s; width: 100%;
          "
          onmouseover="this.style.borderColor='#3b82f6';this.style.color='#3b82f6'"
          onmouseout="this.style.borderColor='#2e2e2e';this.style.color='#9ca3af'"
          >
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="15" height="15">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.159.69.159 1.006 0z"/>
            </svg>
            Pick on Map
          </button>

          <!-- Hidden display box, shown after map selection -->
          <div id="location-display" style="display:none; margin-top:8px; background:#0d0d0d; border:0.5px solid #3b82f6; border-radius:10px; padding:10px 12px; font-size:12px; color:#9ca3af;">
            <div style="color:#e5e7eb; font-size:13px;" id="location-display-text">—</div>
            <div style="margin-top:3px; font-size:11px; color:#6b7280;" id="location-coords-text">—</div>
          </div>

          <!-- Hidden inputs carrying both values to PHP -->
          <input type="hidden" name="location" id="location" value="<?= old('location') ?>">
          <input type="hidden" name="location_lat" id="location_lat" value="<?= old('location_lat') ?>">
          <input type="hidden" name="location_lng" id="location_lng" value="<?= old('location_lng') ?>">
        </div>
        <div class="field">
          <label for="datetime">Date &amp; Time</label>
          <div class="input-wrap">
            <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/>
            </svg>
            <input type="datetime-local" id="datetime" name="datetime" value="<?= old('datetime') ?>" required>
          </div>
        </div>
      </div>

      <!-- Shoot Type -->
      <div class="field">
        <label for="shoot_type">Shoot Type</label>
        <div class="input-wrap">
          <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
          </svg>
          <select id="shoot_type" name="shoot_type" required>
            <option value="" disabled selected hidden>Select shoot type</option>
            <?php $shoot_types_db = getShootTypes($conn); foreach ($shoot_types_db as $st): ?>
              <option value="<?= $st['value'] ?>" <?= selected('shoot_type', $st['value']) ?>>
                <?= $st['label'] ?>
              </option>
            <?php endforeach; ?>
          </select>
          <svg class="select-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>

      <!-- Mood -->
      <div class="field">
        <label>Mood</label>
        <div class="backdrop-grid">
          <?php
          $moods_data     = getMoods($conn);
          $savedMood = $_POST['mood'] ?? '';
          foreach ($moods_data as $m):
            $val = $m['value'];
          ?>
          <div class="backdrop-tile <?= $savedMood === $val ? 'active' : '' ?>"
              data-mood="<?= $val ?>"
              onclick="selectMood(this)">
            <div class="tile-bg bg-mood-<?= $m['value'] ?>">
              <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:22px;opacity:0.6;"><?= $m['emoji'] ?></div>
            </div>
            <div class="tile-label"><?= $m['label'] ?></div>
            <div class="tile-check">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="mood" id="mood" value="<?= old('mood') ?>">
      </div>

      <!-- Outfit Colour -->
      <div class="field">
        <label for="outfit">Outfit Colour</label>
        <div class="swatch-row">
          <div class="input-wrap" style="flex:1">
            <input type="text" id="outfit" name="outfit" placeholder="e.g. cream, forest green…" value="<?= old('outfit') ?>">
          </div>

          <!-- 👗 Dress image picker button -->
          <div class="dress-upload-wrap" title="Upload dress to auto-detect colour">
            <input type="file" id="dress-image-input" accept="image/*" style="display:none">
            <button type="button" class="dress-upload-btn" id="dress-upload-btn" onclick="document.getElementById('dress-image-input').click()">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
              </svg>
              <span id="dress-btn-label">Auto-detect</span>
            </button>
            <!-- Spinner -->
            <div id="dress-spinner" style="display:none" class="dress-spinner"></div>
            <!-- Tiny preview with X button -->
            <div id="dress-preview-wrap" style="display:none; position:relative; flex-shrink:0;">
              <img id="dress-preview" src="" alt="" class="dress-preview" style="display:block;">
              <button
                type="button"
                id="dress-clear-btn"
                title="Remove image"
                style="
                  position:absolute; top:-6px; right:-6px;
                  width:18px; height:18px;
                  background:#e87070; border:none; border-radius:50%;
                  color:#fff; font-size:11px; line-height:1;
                  cursor:pointer; display:none;
                  align-items:center; justify-content:center;
                  z-index:10; padding:0;
                "
              >&times;</button>
            </div>
          </div>
        </div>

        <div class="color-swatches" id="swatch-wrap">
          <?php
          $swatches_db    = getSwatches($conn);
          $savedOutfit = strtolower(trim($_POST['outfit'] ?? ''));
          foreach ($swatches_db as $s):
          ?>
          <div
            class="swatch<?= $savedOutfit === $s['color_name'] ? ' selected' : '' ?>"
            style="background:<?= $s['hex_value'] ?>;"
            data-color="<?= $s['color_name'] ?>"
            onclick="pickSwatch(this,'<?= $s['color_name'] ?>')"
          ></div>
          <?php endforeach; ?>
        </div>

        <!-- AI result tag -->
        <div id="dress-result-tag" class="dress-result-tag" style="display:none">
          <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
          </svg>
          <span id="dress-result-text">Detected: —</span>
        </div>
      </div>

      <div class="divider"></div>

      <!-- Image Upload -->
       <div class="field">
        <label>Model Image</label>
 
        <div class="upload-area" id="upload-area">
          <input type="file" name="image" id="image" accept="image/*" required onchange="handleFile(this)">
          <svg class="upload-icon" id="upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <div class="upload-text" id="upload-text">Drop image or click to browse</div>
          <div class="upload-sub">JPG, PNG, WEBP &mdash; max 10 MB</div>
        </div>
 
        <!-- Analysis status bar (hidden until upload) -->
        <div id="analysis-status" style="display:none; margin-top:10px;">
          <div style="
            display:flex; align-items:center; gap:8px;
            background:#0d0d0d; border:0.5px solid #222;
            border-radius:10px; padding:10px 14px;
          ">
            <div id="analysis-spinner" class="dress-spinner" style="display:none;"></div>
            <div id="analysis-status-icon" style="display:none; width:14px; height:14px; flex-shrink:0;">
              <!-- filled by JS -->
            </div>
            <div style="flex:1;">
              <div style="font-size:12px; color:#9ca3af;" id="analysis-status-text">Analyzing body proportions…</div>
              <div style="font-size:10px; color:#4b5563; margin-top:2px;" id="analysis-status-sub"></div>
            </div>
          </div>
        </div>

        <!-- Hidden input to carry analysis JSON to PHP -->
        <input type="hidden" name="body_analysis" id="body_analysis_input" value="">
      </div>
    </div>
  </div>
  <?php endif; ?>

<!-- ── Map Modal ──────────────────────────────────────────────────────────── -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<div id="map-modal" style="
  display: none;
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0,0,0,0.75);
  align-items: center;
  justify-content: center;
">
  <div style="
    background: #111;
    border: 0.5px solid #2a2a2a;
    border-radius: 20px;
    overflow: hidden;
    width: min(620px, 95vw);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
  ">
    <!-- Modal header -->
    <div style="
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.25rem;
      border-bottom: 0.5px solid #1e1e1e;
      flex-shrink: 0;
    ">
      <div>
        <div style="font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:#f0ede8;">Pick a Location</div>
        <div style="font-size:11px; color:#6b7280; margin-top:2px;">Click anywhere on the map or search below</div>
      </div>
      <button type="button" onclick="closeMapModal()" style="
        background: none;
        border: none;
        color: #6b7280;
        font-size: 22px;
        cursor: pointer;
        line-height: 1;
        padding: 4px;
      ">&times;</button>
    </div>

    <!-- Search bar -->
    <div style="padding: 0.75rem 1.25rem; border-bottom: 0.5px solid #1e1e1e; flex-shrink:0; display:flex; gap:8px;">
      <div style="position:relative; flex:1;">
        <svg style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#6b7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/>
        </svg>
        <input
          type="text"
          id="map-search-input"
          placeholder="Search a place…"
          onkeydown="if(event.key==='Enter'){event.preventDefault();searchMapLocation();}"
          style="
            width:100%; background:#0d0d0d; border:0.5px solid #222;
            border-radius:10px; color:#fff; font-family:'DM Sans',sans-serif;
            font-size:13px; padding:9px 12px 9px 32px; outline:none;
            box-sizing:border-box;
          "
        >
        <div id="map-suggestions" style="
          position: absolute;
          top: 100%;
          left: 0; right: 0;
          background: #111;
          border: 0.5px solid #2a2a2a;
          border-radius: 0 0 10px 10px;
          z-index: 9999;
          max-height: 220px;
          overflow-y: auto;
        "></div>
      </div>
      <button type="button" onclick="searchMapLocation()" style="
        background:#3b82f6; border:none; border-radius:10px;
        color:#fff; font-size:12px; font-family:'DM Sans',sans-serif;
        padding:9px 14px; cursor:pointer; white-space:nowrap;
      ">Search</button>
      <button type="button" onclick="locateMe()" title="Use my location" style="
        background:#0d0d0d; border:0.5px solid #2e2e2e; border-radius:10px;
        color:#9ca3af; font-size:12px; padding:9px 12px; cursor:pointer;
        display:flex; align-items:center; gap:5px;
      ">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
        </svg>
        Me
      </button>
    </div>

    <!-- Map -->
    <div id="leaflet-map" style="flex:1; min-height:340px;"></div>

    <!-- Selected location bar -->
    <div style="
      padding: 0.75rem 1.25rem;
      border-top: 0.5px solid #1e1e1e;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-shrink:0;
    ">
      <div style="flex:1; overflow:hidden;">
        <div style="font-size:10px; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; margin-bottom:3px;">Selected location</div>
        <div id="map-selected-label" style="font-size:13px; color:#e5e7eb; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">None — click the map to pick</div>
      </div>
      <button type="button" id="map-confirm-btn" onclick="confirmMapLocation()" disabled style="
        background:#3b82f6; border:none; border-radius:10px;
        color:#0a0a0a; font-family:'Syne',sans-serif; font-size:13px;
        font-weight:700; padding:10px 18px; cursor:not-allowed;
        opacity:0.4; white-space:nowrap; transition:all .2s;
      ">Use this location</button>
    </div>
  </div>
</div>

<script>
// ── Mood toggle ────────────────────────────────────────────────────────────
function selectMood(tile) {
  document.querySelectorAll('[data-mood]').forEach(t => t.classList.remove('active'));
  tile.classList.add('active');
  document.getElementById('mood').value = tile.dataset.mood;
  updateProgress();
}

// ── Swatch pick ────────────────────────────────────────────────────────────
function pickSwatch(el, color) {
  document.querySelectorAll('.swatch').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('outfit').value = color;
  updateProgress();
}

// ── File upload ────────────────────────────────────────────────────────────
function handleFile(input) {
  const area = document.getElementById('upload-area');
  const text = document.getElementById('upload-text');
  if (input.files && input.files[0]) {
    area.classList.add('has-file');
    text.textContent = input.files[0].name;
    triggerBodyAnalysis(input.files[0]);
  }
  updateProgress();
}

// ── Dress colour auto-detect ───────────────────────────────────────────────
function lockOutfitField(lock) {
  const outfitInput  = document.getElementById('outfit');
  const swatchWrap   = document.getElementById('swatch-wrap');
  const swatches     = document.querySelectorAll('.swatch');

  if (lock) {
    // Disable text input
    outfitInput.setAttribute('readonly', true);
    outfitInput.style.opacity = '0.5';
    outfitInput.style.cursor  = 'not-allowed';
    // Disable swatches
    swatchWrap.style.opacity       = '0.4';
    swatchWrap.style.pointerEvents = 'none';
  } else {
    // Re-enable text input
    outfitInput.removeAttribute('readonly');
    outfitInput.style.opacity = '';
    outfitInput.style.cursor  = '';
    // Re-enable swatches
    swatchWrap.style.opacity       = '';
    swatchWrap.style.pointerEvents = '';
  }
}

function clearDressDetection() {
  // Clear input & swatches
  document.getElementById('outfit').value = '';
  document.querySelectorAll('.swatch').forEach(s => s.classList.remove('selected'));

  // Hide preview & clear btn
  const wrap    = document.getElementById('dress-preview-wrap');
  const preview = document.getElementById('dress-preview');
  const clearBtn = document.getElementById('dress-clear-btn');
  wrap.style.display     = 'none';
  preview.src            = '';
  clearBtn.style.display = 'none';

  // Hide result tag
  const tag = document.getElementById('dress-result-tag');
  tag.style.display = 'none';

  // Reset button
  const btn   = document.getElementById('dress-upload-btn');
  const label = document.getElementById('dress-btn-label');
  btn.classList.remove('success', 'loading');
  label.textContent = 'Auto-detect';

  // Reset file input so same file can be re-picked
  document.getElementById('dress-image-input').value = '';

  // Unlock outfit field
  lockOutfitField(false);

  updateProgress();
}

// X button click
document.getElementById('dress-clear-btn').addEventListener('click', clearDressDetection);

// Show/hide X on hover over preview wrap
const previewWrap = document.getElementById('dress-preview-wrap');
previewWrap.addEventListener('mouseenter', () => {
  document.getElementById('dress-clear-btn').style.display = 'flex';
});
previewWrap.addEventListener('mouseleave', () => {
  document.getElementById('dress-clear-btn').style.display = 'none';
});

document.getElementById('dress-image-input').addEventListener('change', async function () {
  const file = this.files[0];
  if (!file) return;

  const btn      = document.getElementById('dress-upload-btn');
  const spinner  = document.getElementById('dress-spinner');
  const label    = document.getElementById('dress-btn-label');
  const wrap     = document.getElementById('dress-preview-wrap');
  const preview  = document.getElementById('dress-preview');
  const tag      = document.getElementById('dress-result-tag');
  const tagText  = document.getElementById('dress-result-text');

  // Show preview thumbnail
  const reader = new FileReader();
  reader.onload = e => {
    preview.src        = e.target.result;
    wrap.style.display = 'flex';
  };
  reader.readAsDataURL(file);

  // Loading state
  btn.classList.add('loading');
  label.textContent     = 'Detecting…';
  spinner.style.display = 'block';
  tag.style.display     = 'none';

  try {
    const base64 = await new Promise((res, rej) => {
      const r = new FileReader();
      r.onload  = () => res(r.result.split(',')[1]);
      r.onerror = rej;
      r.readAsDataURL(file);
    });

    const mime = file.type || 'image/jpeg';

    const response = await fetch('includes/detect-colour.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ base64, mime })
    });

    const data     = await response.json();
    const detected = (data.colour && data.colour !== 'unknown')
      ? data.colour.trim().toLowerCase()
      : null;

    if (!detected) {
      btn.classList.remove('loading');
      label.textContent       = 'Auto-detect';
      tagText.textContent     = 'Could not detect — type it manually';
      tagText.style.color     = '#f59e0b';
      tag.style.display       = 'inline-flex';
      spinner.style.display   = 'none';
      // Don't lock if detection failed
      return;
    }

    // Fill outfit input & lock field
    document.getElementById('outfit').value = detected;
    lockOutfitField(true);
    updateProgress();

    // Clear any swatch selection
    document.querySelectorAll('.swatch').forEach(s => s.classList.remove('selected'));

    // Show result tag
    tagText.textContent = 'Detected: ' + detected;
    tagText.style.color = '';
    tag.style.display   = 'inline-flex';

    // Success state
    btn.classList.remove('loading');
    btn.classList.add('success');
    label.textContent = 'Re-detect';

  } catch (err) {
    console.error('Colour detection failed:', err);
    btn.classList.remove('loading');
    label.textContent     = 'Auto-detect';
    tagText.textContent   = 'Detection failed — try again';
    tagText.style.color   = '#e87070';
    tag.style.display     = 'inline-flex';
  } finally {
    spinner.style.display = 'none';
  }
});

// ── Progress bar ───────────────────────────────────────────────────────────
function updateProgress() {
  const fields = [
    document.getElementById('location')?.value,
    document.getElementById('datetime')?.value,
    document.getElementById('shoot_type')?.value,
    document.getElementById('mood')?.value,
    document.getElementById('outfit')?.value,
    document.getElementById('image')?.files.length > 0 ? '1' : ''
  ];
  const filled = fields.filter(f => f && f.trim() !== '').length;
  const pct    = Math.round((filled / fields.length) * 100);
  const fill   = document.getElementById('progress-fill');
  const label  = document.getElementById('progress-pct');
  if (fill)  fill.style.width  = pct + '%';
  if (label) label.textContent = pct + '%';
}

['location','datetime','shoot_type','outfit'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', updateProgress);
});

updateProgress();
</script>

<script>
// ── Map Modal ──────────────────────────────────────────────────────────────
let mapInstance   = null;
let mapMarker     = null;
let selectedLatLng = null;
let selectedLabel  = '';

function openMapModal() {
  const modal = document.getElementById('map-modal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';

  // Init map only once
  if (!mapInstance) {
    mapInstance = L.map('leaflet-map', { zoomControl: true }).setView([20, 0], 2);
    mapInstance.options.maxZoom = 20;

    // Dark tile layer (CartoDB Dark Matter — free, no key)
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 20
    }).addTo(mapInstance);

    mapInstance.on('click', function (e) {
      placeMarker(e.latlng.lat, e.latlng.lng);
    });
  }

  // Fix Leaflet tile rendering after display:none → flex
  setTimeout(() => mapInstance.invalidateSize(), 50);
}

function closeMapModal() {
  document.getElementById('map-modal').style.display = 'none';
  document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('map-modal').addEventListener('click', function(e) {
  if (e.target === this) closeMapModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const modal = document.getElementById('map-modal');
    if (modal.style.display === 'flex') closeMapModal();
  }
});

function placeMarker(lat, lng) {
  if (mapMarker) mapMarker.remove();

  mapMarker = L.marker([lat, lng], {
    icon: L.divIcon({
      className: '',
      html: `<div style="
        width:18px; height:18px; background:#3b82f6;
        border:3px solid #fff; border-radius:50%;
        box-shadow:0 0 0 3px rgba(59,130,246,0.35);
      "></div>`,
      iconSize: [18, 18],
      iconAnchor: [9, 9]
    })
  }).addTo(mapInstance);

  selectedLatLng = { lat, lng };

  // Set label as coords while reverse geocoding
  setSelectedLabel('Fetching address…', false);

  // Reverse geocode with Nominatim (free, no key)
  fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
    .then(r => r.json())
    .then(data => {
      const addr = data.display_name || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
      selectedLabel = addr;
      setSelectedLabel(addr, true);
    })
    .catch(() => {
      selectedLabel = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
      setSelectedLabel(selectedLabel, true);
    });
}

function setSelectedLabel(text, enableBtn) {
  document.getElementById('map-selected-label').textContent = text;
  const btn = document.getElementById('map-confirm-btn');
  btn.disabled = !enableBtn;
  btn.style.opacity = enableBtn ? '1' : '0.4';
  btn.style.cursor  = enableBtn ? 'pointer' : 'not-allowed';
}

function confirmMapLocation() {
  if (!selectedLabel) return;

  fetch(`https://nominatim.openstreetmap.org/reverse?lat=${selectedLatLng.lat}&lon=${selectedLatLng.lng}&format=json&zoom=10`)
    .then(r => r.json())
    .then(data => {
      const a = data.address || {};
      const short = [
        a.suburb || a.neighbourhood || a.village || a.town || a.city_district,
        a.city   || a.town || a.county,
        a.country
      ].filter(Boolean).join(', ');

      const label = short || selectedLabel;
      const lat   = selectedLatLng.lat.toFixed(6);
      const lng   = selectedLatLng.lng.toFixed(6);

      // Fill hidden inputs
      document.getElementById('location').value     = label;
      document.getElementById('location_lat').value = lat;
      document.getElementById('location_lng').value = lng;

      // Show the display box
      document.getElementById('location-display').style.display = 'block';
      document.getElementById('location-display-text').textContent  = label;
      document.getElementById('location-coords-text').textContent = `${lat}, ${lng}`;

      updateProgress();
      closeMapModal();
    })
    .catch(() => {
      document.getElementById('location').value     = selectedLabel;
      document.getElementById('location_lat').value = selectedLatLng.lat.toFixed(6);
      document.getElementById('location_lng').value = selectedLatLng.lng.toFixed(6);

      document.getElementById('location-display').style.display = 'block';
      document.getElementById('location-display-text').textContent  = selectedLabel;
      document.getElementById('location-coords-text').textContent =
        `${selectedLatLng.lat.toFixed(6)}, ${selectedLatLng.lng.toFixed(6)}`;

      updateProgress();
      closeMapModal();
    });
}

// Debounce timer
let searchTimer = null;

function searchMapLocation() {
  const query = document.getElementById('map-search-input').value.trim();
  if (!query) return;
  clearSuggestions();

  fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1&addressdetails=1`)
    .then(r => r.json())
    .then(results => {
      if (!results.length) { alert('No results found.'); return; }
      const { lat, lon } = results[0];
      mapInstance.setView([+lat, +lon], 17); 
      placeMarker(+lat, +lon);
    })
    .catch(() => alert('Search failed.'));
}

function clearSuggestions() {
  const box = document.getElementById('map-suggestions');
  if (box) box.innerHTML = '';
}

// Auto-suggest as user types
document.getElementById('map-search-input').addEventListener('input', function () {
  clearTimeout(searchTimer);
  const query = this.value.trim();
  if (query.length < 3) { clearSuggestions(); return; }

  searchTimer = setTimeout(() => {
    fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=6&addressdetails=1`)
      .then(r => r.json())
      .then(results => {
        const box = document.getElementById('map-suggestions');
        box.innerHTML = '';
        results.forEach(r => {
          const item = document.createElement('div');
          item.textContent = r.display_name;
          item.style.cssText = `
            padding: 9px 12px; font-size: 12px; color: #e5e7eb;
            cursor: pointer; border-bottom: 0.5px solid #1e1e1e;
            transition: background .15s;
          `;
          item.onmouseenter = () => item.style.background = '#1a1a1a';
          item.onmouseleave = () => item.style.background = 'transparent';
          item.onclick = () => {
            document.getElementById('map-search-input').value = r.display_name;
            mapInstance.setView([+r.lat, +r.lon], 17); 
            placeMarker(+r.lat, +r.lon);
            clearSuggestions();
          };
          box.appendChild(item);
        });
      });
  }, 350); // wait 350ms after user stops typing
});

function locateMe() {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser.');
    return;
  }
  navigator.geolocation.getCurrentPosition(
    pos => {
      const { latitude: lat, longitude: lng } = pos.coords;
      mapInstance.setView([lat, lng], 14);
      placeMarker(lat, lng);
    },
    () => alert('Could not get your location. Please allow location access.')
  );
}

// ── Body analysis state ───────────────────────────────────────────────────
let bodyAnalysisData = null;
 
// ── Override handleFile to also trigger analysis ──────────────────────────
function handleFile(input) {
  const area = document.getElementById('upload-area');
  const text = document.getElementById('upload-text');
  if (input.files && input.files[0]) {
    area.classList.add('has-file');
    text.textContent = input.files[0].name;
    triggerBodyAnalysis(input.files[0]);
  }
  updateProgress();
}
 
async function triggerBodyAnalysis(file) {
  const statusWrap = document.getElementById('analysis-status');
  const spinner    = document.getElementById('analysis-spinner');
  const statusIcon = document.getElementById('analysis-status-icon');
  const statusText = document.getElementById('analysis-status-text');
  const statusSub  = document.getElementById('analysis-status-sub');
  const panel      = document.getElementById('body-analysis-panel');
 
  // Reset
  panel.style.display   = 'none';
  statusWrap.style.display = 'flex';
  spinner.style.display    = 'block';
  statusIcon.style.display = 'none';
  statusText.textContent   = 'Analyzing body proportions…';
  statusSub.textContent    = 'Using Gemini Vision AI · takes a few seconds';
 
  try {
    const base64 = await new Promise((res, rej) => {
      const r = new FileReader();
      r.onload  = () => res(r.result.split(',')[1]);
      r.onerror = rej;
      r.readAsDataURL(file);
    });
 
    const resp = await fetch('includes/analyze-model.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ base64, mime: file.type || 'image/jpeg' })
    });
 
    const data = await resp.json();
 
    if (data.error) {
      showAnalysisError(data.error === 'no_person_detected'
        ? 'No person detected — upload a clear photo of the model.'
        : 'Analysis failed: ' + data.error);
      return;
    }
 
    bodyAnalysisData = data;
    document.getElementById('body_analysis_input').value = JSON.stringify(data);
 
    // Success state
    spinner.style.display    = 'none';
    statusIcon.style.display = 'block';
    statusIcon.innerHTML     = `<svg fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2.5" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`;
    statusText.textContent   = 'Body analysis complete — ' + data.body_type + ' body type detected';
    statusText.style.color   = '#22c55e';
    statusSub.textContent    = 'Face: ' + (data.face_shape || '—') + '  ·  Presence: ' + (data.overall_presence || '—');
 
  } catch(err) {
    showAnalysisError('Network error — check includes/analyze-model.php');
  }
}
 
function showAnalysisError(msg) {
  const spinner    = document.getElementById('analysis-spinner');
  const statusIcon = document.getElementById('analysis-status-icon');
  const statusText = document.getElementById('analysis-status-text');
  const statusSub  = document.getElementById('analysis-status-sub');
 
  spinner.style.display    = 'none';
  statusIcon.style.display = 'block';
  statusIcon.innerHTML     = `<svg fill="none" viewBox="0 0 24 24" stroke="#e87070" stroke-width="2.5" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`;
  statusText.textContent   = msg;
  statusText.style.color   = '#e87070';
  statusSub.textContent    = '';
}
 
function clearBodyAnalysis() {
  bodyAnalysisData = null;
  document.getElementById('body_analysis_input').value = '';
  document.getElementById('analysis-status').style.display = 'none';

  // Reset upload area
  const area = document.getElementById('upload-area');
  const text = document.getElementById('upload-text');
  area.classList.remove('has-file');
  text.textContent = 'Drop image or click to browse';
  document.getElementById('image').value = '';

  const statusText = document.getElementById('analysis-status-text');
  statusText.style.color = '';

  updateProgress();
}
 
// ── Utility: format snake_case to Title Case ──────────────────────────────
function fmt(str) {
  if (!str) return '—';
  return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}
</script>

<script type="module">
// ─── 1. Imports ──────────────────────────────────────────────────────────────
import {
  FaceLandmarker,
  PoseLandmarker,
  FilesetResolver,
  DrawingUtils
} from 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/vision_bundle.mjs';
 
// ─── 2. State ─────────────────────────────────────────────────────────────────
let faceLandmarker = null;
let poseLandmarker = null;
let modelsLoading  = false;
let modelsReady    = false;
 
// ─── 3. Lazy-load models (called on first image upload) ──────────────────────
async function loadModels() {
  if (modelsReady || modelsLoading) return;
  modelsLoading = true;
 
  try {
    const vision = await FilesetResolver.forVisionTasks(
      'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm'
    );
 
    [faceLandmarker, poseLandmarker] = await Promise.all([
      FaceLandmarker.createFromOptions(vision, {
        baseOptions: {
          modelAssetPath:
            'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task',
          delegate: 'GPU'   // falls back to CPU automatically
        },
        outputFaceBlendshapes: true,
        outputFacialTransformationMatrixes: true,
        runningMode: 'IMAGE',
        numFaces: 1
      }),
      PoseLandmarker.createFromOptions(vision, {
        baseOptions: {
          modelAssetPath:
            'https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/1/pose_landmarker_lite.task',
          delegate: 'GPU'
        },
        runningMode: 'IMAGE',
        numPoses: 1
      })
    ]);
 
    modelsReady  = true;
    modelsLoading = false;
  } catch (err) {
    modelsLoading = false;
    throw new Error('MediaPipe model load failed: ' + err.message);
  }
}
 
// ─── 4. Main analysis entry point ─────────────────────────────────────────────
window.triggerBodyAnalysis = async function(file) {
  const statusWrap = document.getElementById('analysis-status');
  const spinner    = document.getElementById('analysis-spinner');
  const statusIcon = document.getElementById('analysis-status-icon');
  const statusText = document.getElementById('analysis-status-text');
  const statusSub  = document.getElementById('analysis-status-sub');
 
  // Reset UI
  statusWrap.style.display = 'flex';
  spinner.style.display    = 'block';
  statusIcon.style.display = 'none';
  statusText.style.color   = '';
  statusText.textContent   = 'Loading MediaPipe models…';
  statusSub.textContent    = 'First run downloads ~8 MB of WASM — subsequent runs are instant';
 
  try {
    // ── 4a. Load models ──────────────────────────────────────────────────
    await loadModels();
    statusText.textContent = 'Running face & pose detection…';
    statusSub.textContent  = 'Powered by MediaPipe · runs entirely in your browser';
 
    // ── 4b. Draw image to offscreen canvas ───────────────────────────────
    const img = await fileToImage(file);
    const canvas  = document.createElement('canvas');
    canvas.width  = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
 
    // ── 4c. Run landmarkers ───────────────────────────────────────────────
    const [faceResult, poseResult] = await Promise.all([
      faceLandmarker.detect(canvas),
      poseLandmarker.detect(canvas)
    ]);
 
    const hasFace = faceResult.faceLandmarks && faceResult.faceLandmarks.length > 0 &&
    (() => {
        const lm = faceResult.faceLandmarks[0];
        // Check a spread of reliable face landmarks: nose tip (1), 
        // chin (152), left cheek (234), right cheek (454)
        const keyFaceIdx = [1, 152, 234, 454];
        // FaceLandmarker doesn't expose visibility scores the same way PoseLandmarker does,
        // so instead we check that landmarks are within a reasonable face region
        // (all within canvas bounds and not collapsed to a single point)
        const xs = keyFaceIdx.map(i => lm[i].x);
        const ys = keyFaceIdx.map(i => lm[i].y);
        const spreadX = Math.max(...xs) - Math.min(...xs);
        const spreadY = Math.max(...ys) - Math.min(...ys);
        // If face landmarks are collapsed (< 5% spread), it's not a real face detection
        return spreadX > 0.05 && spreadY > 0.05;
    })();
    const hasPose = poseResult.landmarks && poseResult.landmarks.length > 0 &&
    (() => {
        const lm = poseResult.landmarks[0];
        const keyBodyIdx = [11, 12, 23, 24]; // shoulders + hips
        const avgVis = keyBodyIdx.reduce((s, i) => s + (lm[i].visibility ?? 0), 0) / keyBodyIdx.length;
        return avgVis > 0.3; // at least 30% confident on core body landmarks
    })();
 
    if (!hasFace && !hasPose) {
      showAnalysisError('No person detected — please upload a clear photo of the model.');
      return;
    }
 
    // ── 4d. Extract attributes ────────────────────────────────────────────
    const faceAttrs = hasFace ? extractFaceAttributes(faceResult, canvas) : {};
    const poseAttrs = hasPose ? extractPoseAttributes(poseResult, canvas) : {};
 
    // ── 4e. Merge & derive body type ──────────────────────────────────────
    const attrs = {
      ...defaultAttrs(),
      ...faceAttrs,
      ...poseAttrs,
      confidence: (hasFace && hasPose) ? 'high' : hasFace ? 'medium' : 'medium'
    };
    attrs.body_type = deriveBodyType(attrs);
    attrs.overall_presence = derivePresence(attrs);
    attrs.recommended_angles = recommendAngles(attrs);
    attrs.avoid_angles       = avoidAngles(attrs);
 
    // ── 4f. Ask PHP to add pose_hints (same endpoint, lighter role) ───────
    let finalAttrs = attrs;
    try {
      const resp = await fetch('includes/analyze-model.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(attrs)
      });
      if (resp.ok) finalAttrs = await resp.json();
    } catch (_) { /* offline fallback — use attrs as-is */ }
 
    if (!finalAttrs.pose_hints) finalAttrs.pose_hints = derivePoseHintsClient(attrs);
 
    // ── 4g. Store & render ────────────────────────────────────────────────
    window.bodyAnalysisData = finalAttrs;
    document.getElementById('body_analysis_input').value = JSON.stringify(finalAttrs);
 
    spinner.style.display    = 'none';
    statusIcon.style.display = 'block';
    statusIcon.innerHTML     = `<svg fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2.5" width="14" height="14">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`;
    statusText.textContent = 'Model analyzed';
    statusText.style.color = '#22c55e';
    statusSub.textContent  = '';
  } catch (err) {
    showAnalysisError('Analysis failed: ' + err.message);
  }
};
 
// ─── 5. Image file → HTMLImageElement ────────────────────────────────────────
function fileToImage(file) {
  return new Promise((res, rej) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload  = () => { URL.revokeObjectURL(url); res(img); };
    img.onerror = () => { URL.revokeObjectURL(url); rej(new Error('Image load failed')); };
    img.src = url;
  });
}
 
// ─── 6. Face attribute extraction from 478-point mesh ────────────────────────
function extractFaceAttributes(result, canvas) {
    const lm = result.faceLandmarks[0];
    const W  = canvas.width;
    const H  = canvas.height;

    const p = (i) => ({ x: lm[i].x * W, y: lm[i].y * H });

    const foreheadTop = p(10);
    const chin        = p(152);
    const cheekL      = p(234);
    const cheekR      = p(454);

    const faceHeight  = dist(foreheadTop, chin);
    const cheekWidth  = dist(cheekL, cheekR);

    // Guard: if the detected face region is implausibly small
    // (less than 4% of image width), landmarks are unreliable
    const MIN_FACE_PX = W * 0.04;
    if (cheekWidth < MIN_FACE_PX || faceHeight < MIN_FACE_PX) {
        return {
            face_shape:    'unknown',
            face_symmetry: 'unknown',
            jawline:       'unknown',
            forehead:      'unknown',
            posture:       'unknown',
        };
    }

    const jawL          = p(172);
    const jawR          = p(397);
    const foreheadWidth = dist(p(54), p(284));

    const ratio_h_w        = faceHeight  / (cheekWidth || 1);
    const ratio_jaw_cheek  = dist(jawL, jawR) / (cheekWidth || 1);
    const ratio_fore_cheek = foreheadWidth / (cheekWidth || 1);

    let face_shape = 'oval';
    if (ratio_h_w > 1.55)                                          face_shape = 'oblong';
    else if (ratio_h_w < 1.10)                                     face_shape = 'round';
    else if (ratio_jaw_cheek > 0.90 && ratio_h_w < 1.35)          face_shape = 'square';
    else if (ratio_fore_cheek > 1.05 && ratio_jaw_cheek < 0.75)   face_shape = 'heart';
    else if (ratio_fore_cheek < 0.88 && ratio_jaw_cheek < 0.80)   face_shape = 'diamond';

    const jawline  = ratio_jaw_cheek  > 0.88 ? 'sharp' : ratio_jaw_cheek  > 0.72 ? 'soft' : 'round';
    const forehead = ratio_fore_cheek > 1.02 ? 'wide'  : ratio_fore_cheek > 0.88 ? 'medium' : 'narrow';

    const leftHalf  = dist(p(33),  p(234));
    const rightHalf = dist(p(263), p(454));
    const symRatio  = Math.min(leftHalf, rightHalf) / (Math.max(leftHalf, rightHalf) || 1);
    const face_symmetry = symRatio > 0.96 ? 'high' : symRatio > 0.90 ? 'medium' : 'natural';

    let posture = 'upright';
    if (result.facialTransformationMatrixes && result.facialTransformationMatrixes.length) {
        const mat   = result.facialTransformationMatrixes[0].data;
        const tiltX = Math.atan2(mat[8], mat[10]) * (180 / Math.PI);
        if (tiltX > 12)       posture = 'slightly_forward';
        else if (tiltX < -12) posture = 'relaxed';
    }

    return { face_shape, face_symmetry, jawline, forehead, posture };
}
 
// ─── 7. Pose attribute extraction from 33-point skeleton ─────────────────────
function extractPoseAttributes(result, canvas) {
    const lm = result.landmarks[0];
    const W  = canvas.width;
    const H  = canvas.height;

    const IDX = {
        leftShoulder:  11, rightShoulder: 12,
        leftElbow:     13, rightElbow:    14,
        leftWrist:     15, rightWrist:    16,
        leftHip:       23, rightHip:      24,
        leftKnee:      25, rightKnee:     26,
        leftAnkle:     27, rightAnkle:    28,
        nose:           0,
    };

    const VIS_THRESHOLD = 0.35; // landmark must be at least 35% confident

    const p   = (k) => ({ x: lm[IDX[k]].x * W, y: lm[IDX[k]].y * H });
    const vis = (k) => lm[IDX[k]].visibility ?? 0;
    const ok  = (...keys) => keys.every(k => vis(k) > VIS_THRESHOLD);

    // --- Shoulder width (needs both shoulders) ---
    let shoulder_width = 'unknown';
    if (ok('leftShoulder', 'rightShoulder', 'leftHip', 'rightHip')) {
        const shoulderW = dist(p('leftShoulder'), p('rightShoulder'));
        const hipW      = dist(p('leftHip'),      p('rightHip'));
        const ratio     = shoulderW / (hipW || 1);
        shoulder_width  = ratio > 1.25 ? 'broad' : ratio > 0.90 ? 'medium' : 'narrow';
    }

    // --- Hip ratio (needs hips + shoulders) ---
    let hip_ratio = 'unknown';
    if (ok('leftShoulder', 'rightShoulder', 'leftHip', 'rightHip')) {
        const shoulderW = dist(p('leftShoulder'), p('rightShoulder'));
        const hipW      = dist(p('leftHip'),      p('rightHip'));
        const ratio     = hipW / (shoulderW || 1);
        hip_ratio       = ratio > 1.10 ? 'wide' : ratio > 0.85 ? 'balanced' : 'narrow';
    }

    // --- Waist definition (needs shoulders + hips) ---
    let waist_definition = 'unknown';
    if (ok('leftShoulder', 'rightShoulder', 'leftHip', 'rightHip')) {
        const shoulderW = dist(p('leftShoulder'), p('rightShoulder'));
        const hipW      = dist(p('leftHip'),      p('rightHip'));
        const diff      = Math.abs(shoulderW - hipW) / (Math.max(shoulderW, hipW) || 1);
        waist_definition = diff > 0.15 ? 'defined' : diff > 0.07 ? 'moderate' : 'minimal';
    }

    // --- Leg proportion (needs hips + ankles) ---
    let leg_proportion = 'unknown';
    if (ok('leftHip', 'rightHip', 'leftAnkle', 'rightAnkle', 'nose')) {
        const midHip    = mid(p('leftHip'),    p('rightHip'));
        const midAnkle  = mid(p('leftAnkle'), p('rightAnkle'));
        const legH      = dist(midHip,   midAnkle);
        const fullH     = dist(p('nose'), midAnkle);
        const legRatio  = legH / (fullH || 1);
        leg_proportion  = legRatio > 0.55 ? 'long' : legRatio < 0.46 ? 'short' : 'average';
    }

    // --- Neck length (needs shoulders + nose) ---
    let neck_length = 'unknown';
    if (ok('leftShoulder', 'rightShoulder', 'nose')) {
        const midShoulder = mid(p('leftShoulder'), p('rightShoulder'));
        const torsoH      = ok('leftHip', 'rightHip')
            ? dist(midShoulder, mid(p('leftHip'), p('rightHip'))) : null;
        const neckH       = dist(midShoulder, p('nose'));
        if (torsoH) {
            const ratio  = neckH / (torsoH || 1);
            neck_length  = ratio > 0.38 ? 'long' : ratio < 0.22 ? 'short' : 'medium';
        }
    }

    // --- Arm length (needs shoulders + wrists) ---
    let arm_length = 'unknown';
    if (ok('leftShoulder', 'leftWrist') && ok('leftShoulder', 'rightShoulder', 'leftHip', 'rightHip')) {
        const midShoulder = mid(p('leftShoulder'), p('rightShoulder'));
        const torsoH      = dist(midShoulder, mid(p('leftHip'), p('rightHip')));
        const armH        = dist(p('leftShoulder'), p('leftWrist'));
        const ratio       = armH / (torsoH || 1);
        arm_length        = ratio > 1.1 ? 'long' : ratio < 0.85 ? 'short' : 'average';
    }

    // --- Estimated height (needs nose + ankles) ---
    let estimated_height = 'unknown';
    if (ok('nose', 'leftAnkle', 'rightAnkle')) {
        const midAnkle = mid(p('leftAnkle'), p('rightAnkle'));
        const fullH    = dist(p('nose'), midAnkle);
        estimated_height = fullH / H > 0.80 ? 'tall' : fullH / H < 0.60 ? 'petite' : 'average';
    }

    return {
        shoulder_width,
        hip_ratio,
        waist_definition,
        leg_proportion,
        neck_length,
        arm_length,
        estimated_height,
    };
}
 
// ─── 8. Derive body type from attrs ──────────────────────────────────────────
function deriveBodyType(a) {
    // If core body measurements are unknown, we can't determine body type
    if (a.shoulder_width === 'unknown' || a.hip_ratio === 'unknown') {
        return 'unknown';
    }

    const sw = a.shoulder_width;
    const hr = a.hip_ratio;
    const wd = a.waist_definition;

    if (sw === 'broad'  && hr === 'wide'    && wd === 'defined')  return 'hourglass';
    if (sw === 'medium' && hr === 'wide'    && wd !== 'defined')  return 'pear';
    if (sw === 'broad'  && hr === 'narrow')                       return 'inverted_triangle';
    if (sw === 'broad'  && hr === 'balanced')                     return 'athletic';
    if (wd === 'minimal' && sw !== 'narrow' && hr !== 'wide')     return 'apple';
    return 'rectangle';
}
 
function derivePresence(a) {
  if (a.estimated_height === 'tall' && a.shoulder_width === 'broad') return 'statuesque';
  if (a.estimated_height === 'petite')                               return 'petite';
  if (a.shoulder_width === 'broad')                                  return 'commanding';
  return 'balanced';
}
 
// ─── 9. Angle recommendations ─────────────────────────────────────────────────
function recommendAngles(a) {
  const angles = [];
  if (['oval','diamond'].includes(a.face_shape)) angles.push('three_quarter');
  if (['round','heart'].includes(a.face_shape))  angles.push('slightly_above');
  if (a.shoulder_width === 'broad')              angles.push('three_quarter');
  if (a.leg_proportion === 'long')               angles.push('full_length');
  angles.push('eye_level');
  return [...new Set(angles)].slice(0, 3);
}
 
function avoidAngles(a) {
  const avoid = [];
  if (a.face_shape === 'oblong')   avoid.push('very_high');
  if (a.face_shape === 'square')   avoid.push('straight_on');
  if (a.face_shape === 'round')    avoid.push('low_angle');
  return avoid.slice(0, 2);
}
 
// ─── 10. Client-side pose hints (fallback if PHP unreachable) ────────────────
function derivePoseHintsClient(a) {
  const hints = [];
  const bodyMap = {
    hourglass:         'Accentuate the waist — hands on hips, S-curve poses work beautifully.',
    pear:              'Draw attention upward — strong shoulder poses, A-line stances.',
    apple:             'Elongate the torso — side angles, slight lean forward.',
    rectangle:         'Create curves — hip pop, twisted torso, diagonal body lines.',
    inverted_triangle: 'Balance the frame — hip emphasis, low-angle shots.',
    athletic:          'Show strength and line — power poses, dynamic movement.',
  };
  if (bodyMap[a.body_type]) hints.push(bodyMap[a.body_type]);
 
  const faceMap = {
    round:   'Tilt chin slightly down and forward to define the jawline.',
    square:  'Soft three-quarter angle softens the jaw — avoid straight-on.',
    heart:   'Eye-level or slightly above — draws balance to forehead.',
    oblong:  'Avoid very high angles — eye-level or slightly low is best.',
    oval:    'Most angles work well — classic three-quarter is universally flattering.',
    diamond: 'Highlight cheekbones — three-quarter angle with slight chin tilt.',
  };
  if (faceMap[a.face_shape]) hints.push(faceMap[a.face_shape]);
 
  if (a.leg_proportion === 'short') hints.push('Shoot from a lower angle to elongate the legs.');
  if (a.leg_proportion === 'long')  hints.push('Full-length shots will make a dramatic impact.');
  if (a.neck_length === 'short')    hints.push('Avoid high necklines in styling — open neckline elongates.');
  if (a.shoulder_width === 'broad') hints.push('Three-quarter body angle minimizes shoulder width naturally.');
 
  return hints;
}
 
// ─── 11. Default attrs (for when face or pose is partially missing) ───────────
function defaultAttrs() {
    return {
        body_type:          'unknown',
        estimated_height:   'unknown',
        shoulder_width:     'unknown',
        waist_definition:   'unknown',
        hip_ratio:          'unknown',
        neck_length:        'unknown',
        leg_proportion:     'unknown',
        arm_length:         'unknown',
        posture:            'unknown',
        face_shape:         'unknown',   // was 'oval'
        face_symmetry:      'unknown',   // was 'medium'
        jawline:            'unknown',   // was 'soft'
        forehead:           'unknown',   // was 'medium'
        skin_tone:          'unknown',
        hair_length:        'unknown',
        hair_texture:       'unknown',
        overall_presence:   'unknown',
        recommended_angles: [],          // was ['eye_level'] — only add if we have data
        avoid_angles:       [],
        confidence:         'low',       // was 'medium' — unknown defaults = low confidence
    };
}
 
// ─── 12. Geometry helpers ─────────────────────────────────────────────────────
function dist(a, b) { return Math.hypot(a.x - b.x, a.y - b.y); }
function mid(a, b)  { return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 }; }
 
// ─── 13. UI helpers (reuse the existing renderAnalysisPanel / showAnalysisError
//         functions already defined later in input-form.php) ───────────────────
window.showAnalysisError = function(msg) {
  const spinner    = document.getElementById('analysis-spinner');
  const statusIcon = document.getElementById('analysis-status-icon');
  const statusText = document.getElementById('analysis-status-text');
  const statusSub  = document.getElementById('analysis-status-sub');
 
  spinner.style.display    = 'none';
  statusIcon.style.display = 'block';
  statusIcon.innerHTML     = `<svg fill="none" viewBox="0 0 24 24" stroke="#e87070" stroke-width="2.5" width="14" height="14">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`;
  statusText.textContent = msg;
  statusText.style.color = '#e87070';
  statusSub.textContent  = '';
};
 
</script>
</div>