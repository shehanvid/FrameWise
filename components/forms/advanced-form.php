<?php
function oldPref(string $field, string $default = ''): string {
    return htmlspecialchars($_POST[$field] ?? $default);
}
function checkedPref(string $field, string $value): string {
    $saved = $_POST[$field] ?? [];
    if (is_array($saved)) return in_array($value, $saved) ? 'checked' : '';
    return $saved === $value ? 'checked' : '';
}
function activePref(string $field, string $value): string {
    return (($_POST[$field] ?? '') === $value) ? 'active' : '';
}
?>

<style>

.pref-card {
  background: #111;
  border: 0.5px solid #2a2a2a;
  border-radius: 20px;
  overflow: hidden;
  margin-bottom: 1.5rem;
  font-family: 'DM Sans', sans-serif;
}


.pref-header {
  padding: 2rem 2rem 1.5rem;
  border-bottom: 0.5px solid #1e1e1e;
}

.pref-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #1c1c1c;
  border: 0.5px solid #2e2e2e;
  border-radius: 100px;
  padding: 5px 12px;
  font-size: 11px;
  color: #e5e7eb;
  letter-spacing: .08em;
  text-transform: uppercase;
  margin-bottom: 1rem;
}

.pref-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #3b82f6;
  animation: prefPulse 2s infinite;
}

@keyframes prefPulse { 0%,100%{opacity:1}50%{opacity:.3} }

.pref-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #f0ede8;
  letter-spacing: -.02em;
  line-height: 1.2;
  margin-bottom: 4px;
}

.pref-header p {
  font-size: 13px;
  color: #9ca3af;
  font-weight: 300;
}


.pref-body {
  padding: 1.75rem 2rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}


.pref-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pref-label {
  font-size: 11px;
  font-weight: 500;
  color: #9ca3af;
  letter-spacing: .1em;
  text-transform: uppercase;
}


.pref-select-wrap {
  position: relative;
}

.pref-select-wrap select {
  width: 100%;
  background: #0d0d0d;
  border: 0.5px solid #222;
  border-radius: 10px;
  color: #ffffff;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 300;
  padding: 10px 36px 10px 14px;
  outline: none;
  transition: border-color .2s, background .2s;
  -webkit-appearance: none;
  appearance: none;
  color-scheme: dark;
  cursor: pointer;
}

.pref-select-wrap select:focus {
  border-color: #3b82f6;
  background: #0f0f0f;
}

.pref-select-wrap select option { background: #111; }

.pref-select-arrow {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #6b7280;
}


.pref-radio-group {
  display: flex;
  gap: 8px;
}

.pref-radio-btn {
  flex: 1;
  position: relative;
}

.pref-radio-btn input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0; height: 0;
}

.pref-radio-btn label {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px 8px;
  background: #0d0d0d;
  border: 0.5px solid #222;
  border-radius: 10px;
  color: #6b7280;
  font-size: 12px;
  font-weight: 400;
  cursor: pointer;
  transition: all .2s;
  text-align: center;
}

.pref-radio-btn label .radio-icon {
  font-size: 16px;
  line-height: 1;
}

.pref-radio-btn input[type="radio"]:checked + label {
  border-color: #3b82f6;
  background: rgba(59,130,246,0.06);
  color: #3b82f6;
}

.pref-radio-btn label:hover {
  border-color: #374151;
  color: #e5e7eb;
}


.pref-style-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 6px;
}

.pref-style-card {
  position: relative;
  cursor: pointer;
}

.pref-style-card input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0; height: 0;
}

.pref-style-card label {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 6px;
  background: #0d0d0d;
  border: 0.5px solid #222;
  border-radius: 10px;
  color: #6b7280;
  font-size: 10px;
  font-weight: 500;
  letter-spacing: .04em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all .2s;
  text-align: center;
}

.pref-style-card label .style-icon {
  font-size: 18px;
  line-height: 1;
}

.pref-style-card input[type="radio"]:checked + label {
  border-color: #3b82f6;
  background: rgba(59,130,246,0.06);
  color: #3b82f6;
  box-shadow: 0 0 12px rgba(59,130,246,0.15);
}

.pref-style-card label:hover {
  border-color: #374151;
  color: #e5e7eb;
  background: #111;
}


.pref-checkbox-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
}

.pref-checkbox-item {
  position: relative;
}

.pref-checkbox-item input[type="checkbox"] {
  position: absolute;
  opacity: 0;
  width: 0; height: 0;
}

.pref-checkbox-item label {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  background: #0d0d0d;
  border: 0.5px solid #222;
  border-radius: 8px;
  color: #6b7280;
  font-size: 12px;
  font-weight: 400;
  letter-spacing: 0;
  text-transform: none;
  cursor: pointer;
  transition: all .2s;
}

.pref-checkbox-item label .check-box {
  width: 14px; height: 14px;
  border: 1px solid #333;
  border-radius: 4px;
  background: #0a0a0a;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all .2s;
}

.pref-checkbox-item label .check-box svg {
  width: 9px; height: 9px;
  color: #fff;
  opacity: 0;
  transition: opacity .15s;
}

.pref-checkbox-item input[type="checkbox"]:checked + label {
  border-color: #3b82f6;
  background: rgba(59,130,246,0.06);
  color: #e5e7eb;
}

.pref-checkbox-item input[type="checkbox"]:checked + label .check-box {
  background: #3b82f6;
  border-color: #3b82f6;
}

.pref-checkbox-item input[type="checkbox"]:checked + label .check-box svg {
  opacity: 1;
}

.pref-checkbox-item label:hover {
  border-color: #374151;
  color: #e5e7eb;
}


.pref-pill-group {
  display: flex;
  gap: 0;
  border: 0.5px solid #222;
  border-radius: 10px;
  overflow: hidden;
}

.pref-pill-item {
  flex: 1;
  position: relative;
}

.pref-pill-item input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0; height: 0;
}

.pref-pill-item label {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 10px 8px;
  background: #0d0d0d;
  color: #6b7280;
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0;
  text-transform: none;
  cursor: pointer;
  transition: all .2s;
  border-right: 0.5px solid #222;
}

.pref-pill-item:last-child label {
  border-right: none;
}

.pref-pill-item input[type="radio"]:checked + label {
  background: rgba(59,130,246,0.1);
  color: #3b82f6;
  box-shadow: inset 0 0 20px rgba(59,130,246,0.08);
}

.pref-pill-item label:hover {
  background: #111;
  color: #e5e7eb;
}


.pref-textarea {
  width: 100%;
  background: #0d0d0d;
  border: 0.5px solid #222;
  border-radius: 10px;
  color: #ffffff;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 300;
  padding: 12px 14px;
  outline: none;
  resize: vertical;
  min-height: 90px;
  transition: border-color .2s, background .2s;
  color-scheme: dark;
}

.pref-textarea:focus {
  border-color: #3b82f6;
  background: #0f0f0f;
}

.pref-textarea::placeholder { color: #4b5563; }

.pref-submit-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, transparent 60%);
  pointer-events: none;
}

.pref-submit-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 0 24px rgba(59,130,246,0.35);
}

.pref-submit-btn:active { transform: translateY(0); }
.pref-submit-btn svg { width: 16px; height: 16px; flex-shrink: 0; }


@media (max-width: 640px) {
  .pref-header, .pref-body { padding: 1.25rem 1.1rem; }
  .pref-style-grid { grid-template-columns: repeat(3, 1fr); }
  .pref-radio-group { flex-direction: column; }
}
</style>

<?php 
$camera_types  = getLookupOptions($conn, 'camera_type');
$exp_levels    = getLookupOptions($conn, 'experience');
$lighting_opts = getLookupOptions($conn, 'lighting_style');
$output_styles = getLookupOptions($conn, 'output_style');
$orientations  = getLookupOptions($conn, 'orientation');
$platforms     = getLookupOptions($conn, 'platform');
$equipment_db  = getEquipment($conn);
?>

<div class="pref-card">

  
  <div class="pref-header">
    <h1>Advanced AI<br>Preferences</h1>
    <p>Help the AI personalize your shoot recommendations.</p>
  </div>

  
  <div class="pref-body">

    
    <div class="pref-field">
      <div class="pref-label">Camera Type</div>
      <div class="pref-select-wrap">
        <select name="camera_type">
          <?php foreach ($camera_types as $item): ?>
            <option value="<?= $item['value'] ?>" <?= oldPref('camera_type') === $item['value'] ? 'selected' : '' ?>>
              <?= $item['label'] ?>
            </option>
          <?php endforeach; ?>
        </select>
        <svg class="pref-select-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </div>

    
    <div class="pref-field">
      <div class="pref-label">Experience Level</div>
      <div class="pref-radio-group">
        <?php foreach ($exp_levels as $item): ?>
          <div class="pref-radio-btn">
            <input type="radio" name="experience" id="exp-<?= $item['value'] ?>" value="<?= $item['value'] ?>"
              <?= activePref('experience', $item['value']) ? 'checked' : '' ?>>
            <label for="exp-<?= $item['value'] ?>">
              <?= $item['label'] ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    
    <div class="pref-field">
      <div class="pref-label">Preferred Lighting Style</div>
      <div class="pref-select-wrap">
        <select name="lighting_style">
          <?php foreach ($lighting_opts as $item): ?>
            <option value="<?= $item['value'] ?>" <?= oldPref('lighting_style') === $item['value'] ? 'selected' : '' ?>>
              <?= $item['label'] ?>
            </option>
          <?php endforeach; ?>
        </select>
        <svg class="pref-select-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </div>

    
    <div class="pref-field">
      <div class="pref-label">Desired Output Style</div>
      <div class="pref-style-grid">
        <?php
        $savedStyle = $_POST['output_style'] ?? '';
        foreach ($output_styles as $item):
        ?>
          <div class="pref-style-card">
            <input type="radio" name="output_style" id="style-<?= $item['value'] ?>" value="<?= $item['value'] ?>"
              <?= $savedStyle === $item['value'] ? 'checked' : '' ?>>
            <label for="style-<?= $item['value'] ?>">
              <?= $item['label'] ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    
    <div class="pref-field">
      <div class="pref-label">Available Equipment</div>
      <div class="pref-checkbox-group">
        <?php
        $savedEquip = $_POST['equipment'] ?? [];
        foreach ($equipment_db as $item):
        ?>
          <div class="pref-checkbox-item">
            <input type="checkbox" name="equipment[]" id="equip-<?= $item['value'] ?>" value="<?= $item['value'] ?>"
              <?= in_array($item['value'], (array)$savedEquip) ? 'checked' : '' ?>>
            <label for="equip-<?= $item['value'] ?>">
              <span class="check-box">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </span>
              <?= $item['label'] ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    
    <div class="pref-field">
      <div class="pref-label">Preferred Shot Orientation</div>
      <div class="pref-pill-group">
        <?php
        $savedOrientation = $_POST['orientation'] ?? '';
        foreach ($orientations as $item):
        ?>
          <div class="pref-pill-item">
            <input type="radio" name="orientation" id="orient-<?= $item['value'] ?>" value="<?= $item['value'] ?>"
              <?= $savedOrientation === $item['value'] ? 'checked' : '' ?>>
            <label for="orient-<?= $item['value'] ?>">
              <?= $item['label'] ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    
    <div class="pref-field">
      <div class="pref-label">Content Platform</div>
      <div class="pref-select-wrap">
        <select name="platform">
          <?php foreach ($platforms as $item): ?>
            <option value="<?= $item['value'] ?>" <?= oldPref('platform') === $item['value'] ? 'selected' : '' ?>>
              <?= $item['label'] ?>
            </option>
          <?php endforeach; ?>
        </select>
        <svg class="pref-select-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </div>

    
    <div class="pref-field">
      <div class="pref-label">Additional Notes</div>
      <textarea
        name="ai_notes"
        class="pref-textarea"
        placeholder="Describe your creative vision, inspiration, or special requirements…"
      ><?= oldPref('ai_notes') ?></textarea>
    </div>
  </div>
</div>