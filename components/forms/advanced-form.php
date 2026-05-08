<?php
function oldAdv(string $field, string $default = ''): string {
    return htmlspecialchars($_POST[$field] ?? $default);
}
function selectedAdv(string $field, string $value): string {
    return (($_POST[$field] ?? '') === $value) ? 'active' : '';
}
?>

<div class="card" id="advanced-details-card" style="margin-top: 0;">
  <div class="card-header">
    <div style="display:flex; align-items:center; justify-content:space-between;">
      <div>
        <div class="header-badge">
          <div class="dot" style="background:#a855f7;"></div>
          Optional
        </div>
        <h1 style="font-size:20px; margin-top:8px;">Advanced Details</h1>
        <p>Gear, environment & mood board for a richer plan</p>
      </div>
    </div>
  </div>

  <div id="adv-body">
    <div class="divider"></div>
    <div class="card-body">

      <!-- Indoor / Outdoor Toggle -->
      <div class="field">
        <label>Environment</label>
        <div style="display:flex; gap:8px;">
          <button type="button"
            class="adv-pill <?= selectedAdv('environment','outdoor') ?: (empty($_POST) ? 'active' : '') ?>"
            data-env="outdoor"
            onclick="pickPill(this,'environment','adv-pill')"
            style="flex:1;"
          >
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="15" height="15">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
            </svg>
            Outdoor
          </button>
          <button type="button"
            class="adv-pill <?= selectedAdv('environment','indoor') ?>"
            data-env="indoor"
            onclick="pickPill(this,'environment','adv-pill')"
            style="flex:1;"
          >
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="15" height="15">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            Indoor
          </button>
        </div>
        <input type="hidden" name="environment" id="environment"
          value="<?= oldAdv('environment','outdoor') ?>">
      </div>

      <!-- Camera / Gear -->
      <div class="field">
        <label for="gear">Camera &amp; Gear</label>
        <div class="input-wrap">
          <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
          </svg>
          <select id="gear" name="gear">
            <option value="">Select gear…</option>
            <option value="phone"         <?= selectedAdv('gear','phone') ?>>📱 Smartphone</option>
            <option value="entry_dslr"    <?= selectedAdv('gear','entry_dslr') ?>>📷 Entry DSLR / Mirrorless</option>
            <option value="full_frame"    <?= selectedAdv('gear','full_frame') ?>>🎞 Full Frame</option>
            <option value="medium_format" <?= selectedAdv('gear','medium_format') ?>>🔲 Medium Format</option>
          </select>
          <svg class="select-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>

      <!-- Backdrop -->
      <div class="field">
        <label>Backdrop / Background</label>
        <div class="mood-grid" style="grid-template-columns:repeat(3,1fr);">
          <?php
          $backdrops = [
            'natural'      => ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 22V8M12 8C12 8 7 3 3 5c0 0 2 8 9 8M12 8c0 0 5-5 9-3 0 0-2 8-9 8"/>',  'label'=>'Natural'],
            'urban'        => ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/>',  'label'=>'Urban'],
            'studio'       => ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/>',  'label'=>'Studio'],
            'beach'        => ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636"/>',  'label'=>'Beach'],
            'forest'       => ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>',  'label'=>'Forest'],
            'architecture' => ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21"/>',  'label'=>'Arch'],
          ];
          $savedBd = $_POST['backdrop'] ?? '';
          foreach ($backdrops as $val => $b):
          ?>
          <button type="button"
            class="mood-btn<?= $savedBd === $val ? ' active' : '' ?>"
            data-backdrop="<?= $val ?>"
            onclick="selectBackdrop(this)"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?= $b['icon'] ?></svg>
            <?= $b['label'] ?>
          </button>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="backdrop" id="backdrop" value="<?= oldAdv('backdrop') ?>">
      </div>

      <!-- Mood Board Upload -->
      <div class="field">
        <label>Mood Board / Client Brief</label>
        <div class="upload-area" id="brief-upload-area">
          <input type="file" name="mood_board" id="mood_board"
            accept="image/*,application/pdf"
            onchange="handleBriefFile(this)">
          <svg class="upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
          </svg>
          <div class="upload-text" id="brief-upload-text">Drop mood board or PDF brief</div>
          <div class="upload-sub">Image or PDF — max 10 MB</div>
        </div>

        <!-- Preview strip -->
        <div id="brief-preview-wrap" style="display:none; margin-top:10px; position:relative; width:fit-content;">
          <img id="brief-preview-img" src="" alt=""
            style="height:64px; border-radius:8px; border:0.5px solid #2e2e2e; display:none;">
          <div id="brief-pdf-tag" style="display:none; background:#1a1a1a; border:0.5px solid #2e2e2e;
            border-radius:8px; padding:8px 14px; font-size:12px; color:#9ca3af; display:none; align-items:center; gap:6px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <span id="brief-pdf-name">document.pdf</span>
          </div>
          <button type="button" onclick="clearBrief()"
            style="position:absolute; top:-6px; right:-6px; width:18px; height:18px;
              background:#e87070; border:none; border-radius:50%; color:#fff;
              font-size:11px; cursor:pointer; display:flex; align-items:center;
              justify-content:center; z-index:10; padding:0;"
          >&times;</button>
        </div>

      </div>

    </div><!-- /card-body -->
  </div><!-- /adv-body -->
</div>

<script>

// ── Pill toggle (environment) ──────────────────────────────────────────────
function pickPill(btn, hiddenId, pillClass) {
  document.querySelectorAll('.' + pillClass).forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById(hiddenId).value = btn.dataset.env || btn.dataset.value;
}

// ── Backdrop select ────────────────────────────────────────────────────────
function selectBackdrop(btn) {
  document.querySelectorAll('[data-backdrop]').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('backdrop').value = btn.dataset.backdrop;
}

// ── Brief upload ───────────────────────────────────────────────────────────
function handleBriefFile(input) {
  const file = input.files[0];
  if (!file) return;

  const area    = document.getElementById('brief-upload-area');
  const text    = document.getElementById('brief-upload-text');
  const wrap    = document.getElementById('brief-preview-wrap');
  const img     = document.getElementById('brief-preview-img');
  const pdfTag  = document.getElementById('brief-pdf-tag');
  const pdfName = document.getElementById('brief-pdf-name');

  area.classList.add('has-file');
  text.textContent = file.name;
  wrap.style.display = 'block';

  if (file.type === 'application/pdf') {
    img.style.display     = 'none';
    pdfTag.style.display  = 'flex';
    pdfName.textContent   = file.name;
  } else {
    pdfTag.style.display = 'none';
    img.style.display    = 'block';
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; };
    reader.readAsDataURL(file);
  }
}

function clearBrief() {
  document.getElementById('mood_board').value        = '';
  document.getElementById('brief-upload-area').classList.remove('has-file');
  document.getElementById('brief-upload-text').textContent = 'Drop mood board or PDF brief';
  document.getElementById('brief-preview-wrap').style.display = 'none';
  document.getElementById('brief-preview-img').src   = '';
  document.getElementById('brief-pdf-tag').style.display = 'none';
}
</script>