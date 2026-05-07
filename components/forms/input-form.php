
<?php
// ─── Process form submission ───────────────────────────────────────────────
$errors   = [];
$success  = false;
$plan     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitise & validate ──────────────────────────────────────────────
    $location   = trim($_POST['location']   ?? '');
    $datetime   = trim($_POST['datetime']   ?? '');
    $shoot_type = trim($_POST['shoot_type'] ?? '');
    $mood       = trim($_POST['mood']       ?? '');
    $outfit     = trim($_POST['outfit']     ?? '');

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
            'datetime'    => $date_display,
            'shoot_type'  => ucfirst(htmlspecialchars($shoot_type)),
            'mood'        => ucfirst(htmlspecialchars($mood)),
            'outfit'      => $outfit ? htmlspecialchars($outfit) : '—',
            'image_name'  => $image_name,
            'shot_list'   => $shot_lists[$shoot_type] ?? ['Custom shot 1', 'Custom shot 2', 'Custom shot 3'],
            'lighting'    => $lighting_map[$mood] ?? 'Set lighting to complement your chosen mood.',
            'palette'     => $palette_note,
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
          <label for="location">Location</label>
          <div class="input-wrap">
            <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
            </svg>
            <input type="text" id="location" name="location" placeholder="Studio / Outdoor…" value="<?= old('location') ?>" required>
          </div>
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
            <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
            </svg>
            <input type="text" id="outfit" name="outfit" placeholder="e.g. cream, forest green…" value="<?= old('outfit') ?>">
          </div>
        </div>
        <div class="color-swatches">
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