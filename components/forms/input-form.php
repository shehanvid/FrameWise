
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

    // ── Image upload ─────────────────────────────────────────────────────
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['image']['tmp_name']);

        if (!in_array($mime, $allowed_types)) {
            $errors[] = 'Invalid image format. Use JPG, PNG, or WEBP.';
        } elseif ($_FILES['image']['size'] > 10 * 1024 * 1024) {
            $errors[] = 'Image must be under 10 MB.';
        } else {
            $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = 'upload_' . uniqid() . '.' . strtolower($ext);
            $upload_dir = __DIR__ . '/uploads/';

            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                $errors[] = 'Failed to save uploaded image.';
                $image_name = '';
            }
        }
    } else if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'A model image is required.';
    } else {
        $errors[] = 'Image upload error (code ' . $_FILES['image']['error'] . ').';
    }

    // ── Generate plan if no errors ───────────────────────────────────────
    if (empty($errors)) {
        $success = true;

        // Format date nicely
        $dt_obj       = new DateTime($datetime);
        $date_display = $dt_obj->format('l, F j Y \a\t g:i A');

        // Build shot list based on shoot type
        $shot_lists = [
            'portrait'    => ['Close-up headshot', 'Three-quarter profile', 'Environmental portrait', 'Candid expression', 'Detail — eyes / hands'],
            'fashion'     => ['Full-length editorial', 'Walking / movement shot', 'Detail — accessories', 'Seated pose', 'Over-shoulder look-back'],
            'product'     => ['Hero front-facing', 'Top-down flat lay', '45-degree angle', 'Detail / texture macro', 'Lifestyle in-use shot'],
            'editorial'   => ['Wide establishing frame', 'Story-driven mid shot', 'Expressive close-up', 'Environmental context', 'Graphic / abstract angle'],
            'commercial'  => ['Clean white-background hero', 'In-use lifestyle shot', 'Group / team dynamic', 'Logo / branding in frame', 'Call-to-action composition'],
        ];

        // Lighting recommendations per mood
        $lighting_map = [
            'warm'      => 'Golden-hour window light or tungsten gels. Use a large silver reflector to wrap fill. Target 5600K → 4200K blend.',
            'cool'      => 'Overcast natural light or CTB-gelled strobes. Large softbox at 90° for even, shadowless coverage. Aim for 6500–7500K.',
            'dramatic'  => 'Hard single-source key light at 45°. Deep shadows — no fill or minimal negative fill with a black flag. High contrast ratio (1:8+).',
            'natural'   => 'Diffused window or open shade. Bounce card for subtle fill. Keep it clean: 5000–5500K, minimal post-processing.',
            'moody'     => 'Low-key setup: floor or side rim light. Fog machine optional. Pull exposure down ½–1 stop in-camera for atmosphere.',
            'airy'      => 'Backlit bright window or large octabox overhead. Over-expose ½ stop. White reflectors everywhere. Aim for lifted shadows.',
        ];

        // Colour palette suggestion based on outfit
        $palette_note = $outfit
            ? "Outfit colour <strong>" . htmlspecialchars($outfit) . "</strong> — consider complementary background tones and accent props that harmonise without competing."
            : "No outfit colour specified — keep the background neutral (white, grey, or muted earthy tones) to give you flexibility in post.";

        $plan = [
            'location'    => htmlspecialchars($location),
            'location_lat' => $location_lat,
            'location_lng' => $location_lng,
            'datetime'    => $date_display,
            'shoot_type'  => ucfirst(htmlspecialchars($shoot_type)),
            'mood'        => ucfirst(htmlspecialchars($mood)),
            'outfit'      => $outfit ? htmlspecialchars($outfit) : '—',
            'image_name'  => $image_name,
            'shot_list'   => $shot_lists[$shoot_type] ?? ['Custom shot 1', 'Custom shot 2', 'Custom shot 3'],
            'lighting'    => $lighting_map[$mood] ?? 'Set lighting to complement your chosen mood.',
            'palette'     => $palette_note,
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
        <h2>Your Shoot Plan</h2>
        <p>Generated &mdash; <?= date('M j, Y g:i A') ?></p>
      </div>
    </div>

    <div class="result-body">

      <?php if ($plan['image_name']): ?>
      <img
        src="uploads/<?= htmlspecialchars($plan['image_name']) ?>"
        alt="Model reference"
        class="preview-img"
      >
      <?php endif; ?>

      <div class="meta-grid">
        <div class="meta-item">
          <div class="meta-label">Location</div>
          <div class="meta-val"><?= $plan['location'] ?></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Date &amp; Time</div>
          <div class="meta-val"><?= $plan['datetime'] ?></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Shoot Type</div>
          <div class="meta-val"><?= $plan['shoot_type'] ?></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Mood</div>
          <div class="meta-val"><?= $plan['mood'] ?></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Outfit Colour</div>
          <div class="meta-val"><?= $plan['outfit'] ?></div>
        </div>
      </div>

      <div>
        <div class="section-title">Shot List</div>
        <ul class="shot-list">
          <?php foreach ($plan['shot_list'] as $i => $shot): ?>
          <li>
            <span class="shot-num"><?= $i + 1 ?></span>
            <?= htmlspecialchars($shot) ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <div class="section-title">Lighting Setup</div>
        <div class="info-block"><?= $plan['lighting'] ?></div>
      </div>

      <div>
        <div class="section-title">Colour &amp; Palette Notes</div>
        <div class="info-block"><?= $plan['palette'] ?></div>
      </div>

    </div>
  </div>

  <div style="margin-top:1rem; text-align:center;">
    <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="font-size:12px; color:#555; text-decoration:none; letter-spacing:.06em; text-transform:uppercase;">
      &#8592; Plan a new shoot
    </a>
  </div>

  <?php else: ?>
  <!-- ── Form ───────────────────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <div class="header-badge">
        <div class="dot"></div>
        Studio AI
      </div>
      <h1>Photo Shoot<br>Planner</h1>
      <p>Fill in the details to generate your personalised shoot plan</p>
      <div class="progress-bar">
        <div class="progress-fill" id="progress-fill"></div>
      </div>
      <div class="progress-meta">
        <span>Completion</span>
        <span id="progress-pct">0%</span>
      </div>
    </div>

    <form
      class="card-body"
      method="POST"
      enctype="multipart/form-data"
      action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
      id="shoot-form"
    >
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
            <option value="">Select type…</option>
            <option value="portrait"   <?= selected('shoot_type','portrait')   ?>>Portrait</option>
            <option value="fashion"    <?= selected('shoot_type','fashion')    ?>>Fashion</option>
            <option value="product"    <?= selected('shoot_type','product')    ?>>Product</option>
            <option value="editorial"  <?= selected('shoot_type','editorial')  ?>>Editorial</option>
            <option value="commercial" <?= selected('shoot_type','commercial') ?>>Commercial</option>
          </select>
          <svg class="select-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>

      <!-- Mood -->
      <div class="field">
        <label>Mood</label>
        <div class="mood-grid">
          <?php
          $moods = [
            'warm'      => ['icon' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',  'label' => 'Warm'],
            'cool'      => ['icon' => '<path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M19.07 4.93L4.93 19.07"/>',  'label' => 'Cool'],
            'dramatic'  => ['icon' => '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>',  'label' => 'Dramatic'],
            'natural'   => ['icon' => '<path d="M12 22V8M12 8C12 8 7 3 3 5c0 0 2 8 9 8M12 8c0 0 5-5 9-3 0 0-2 8-9 8"/>',  'label' => 'Natural'],
            'moody'     => ['icon' => '<path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>',  'label' => 'Moody'],
            'airy'      => ['icon' => '<path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z"/><path d="M18 12c0 3.31-2.69 6-6 6s-6-2.69-6-6 2.69-6 6-6 6 2.69 6 6z"/><path d="M12 2a10 10 0 100 20A10 10 0 0012 2z" opacity=".3"/>',  'label' => 'Airy'],
          ];
          $savedMood = $_POST['mood'] ?? '';
          foreach ($moods as $val => $m):
          ?>
          <button
            type="button"
            class="mood-btn<?= $savedMood === $val ? ' active' : '' ?>"
            data-mood="<?= $val ?>"
            onclick="selectMood(this)"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?= $m['icon'] ?></svg>
            <?= $m['label'] ?>
          </button>
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
          $swatches = [
            ['color'=>'white',  'bg'=>'#f5f5f0'],
            ['color'=>'black',  'bg'=>'#1a1a1a'],
            ['color'=>'red',    'bg'=>'#c0392b'],
            ['color'=>'blue',   'bg'=>'#2980b9'],
            ['color'=>'green',  'bg'=>'#27ae60'],
            ['color'=>'amber',  'bg'=>'#f39c12'],
            ['color'=>'violet', 'bg'=>'#8e44ad'],
            ['color'=>'cream',  'bg'=>'#e8d5b7'],
          ];
          $savedOutfit = strtolower(trim($_POST['outfit'] ?? ''));
          foreach ($swatches as $s):
          ?>
          <div
            class="swatch<?= $savedOutfit === $s['color'] ? ' selected' : '' ?>"
            style="background:<?= $s['bg'] ?>;"
            data-color="<?= $s['color'] ?>"
            onclick="pickSwatch(this,'<?= $s['color'] ?>')"
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

      <div class="divider"></div>

      <!-- Image Upload -->
      <div class="field">
        <label>Model Image</label>
        <div class="upload-area" id="upload-area">
          <input type="file" name="image" id="image" accept="image/*" required onchange="handleFile(this)">
          <svg class="upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <div class="upload-text" id="upload-text">Drop image or click to browse</div>
          <div class="upload-sub">JPG, PNG, WEBP &mdash; max 10 MB</div>
        </div>
      </div>

      <button type="submit" class="submit-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
        </svg>
        Generate Shoot Plan
      </button>
    </form>
  </div>
  <?php endif; ?>

</div><!-- /container -->

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
function selectMood(btn) {
  document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('mood').value = btn.dataset.mood;
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
</script>