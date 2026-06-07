// ─────────────────────────────────────────────
//  shoot-plan-poses.js
//  Fetches AI-matched poses and renders them
//  in both the pose strip and the checklist.
//  Skips the fetch if poses are already in DB.
//
//  Depends on:
//    - POSES_SAVED, RESULT_ID, SHOOT_CONTEXT  (inline PHP vars)
// ─────────────────────────────────────────────

async function loadPoses() {

    // Already saved — PHP rendered the cards, nothing to do
    if (POSES_SAVED) return;

    const bodyAnalysis = SHOOT_CONTEXT.body_analysis || {};

    const payload = {
        shoot_type:       SHOOT_CONTEXT.shoot_type,
        mood:             SHOOT_CONTEXT.mood,
        gender:           SHOOT_CONTEXT.gender,
        outfit:           SHOOT_CONTEXT.outfit,
        location:         SHOOT_CONTEXT.location,
        experience:       SHOOT_CONTEXT.experience,
        platform:         SHOOT_CONTEXT.platform,
        lighting_style:   SHOOT_CONTEXT.lighting_style,
        // Body analysis fields — fall back to 'unknown' if not present
        body_type:          bodyAnalysis.body_type          ?? 'unknown',
        face_shape:         bodyAnalysis.face_shape         ?? 'unknown',
        face_symmetry:      bodyAnalysis.face_symmetry      ?? 'unknown',
        jawline:            bodyAnalysis.jawline            ?? 'unknown',
        shoulder_width:     bodyAnalysis.shoulder_width     ?? 'unknown',
        waist_definition:   bodyAnalysis.waist_definition   ?? 'unknown',
        hip_ratio:          bodyAnalysis.hip_ratio          ?? 'unknown',
        leg_proportion:     bodyAnalysis.leg_proportion     ?? 'unknown',
        neck_length:        bodyAnalysis.neck_length        ?? 'unknown',
        arm_length:         bodyAnalysis.arm_length         ?? 'unknown',
        overall_presence:   bodyAnalysis.overall_presence   ?? 'unknown',
        recommended_angles: (bodyAnalysis.recommended_angles ?? []).join(', '),
        avoid_angles:       (bodyAnalysis.avoid_angles      ?? []).join(', '),
    };

    let rawResponse = null;

    try {
        const resp = await fetch('ai-pose-match.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });

        rawResponse = await resp.text();
        const data  = JSON.parse(rawResponse);

        if (data.error) throw new Error(data.error);
        if (!data.poses || !data.poses.length) throw new Error('Empty poses array');

        // ── Render pose strip ────────────────────────────────────────────
        document.getElementById('pose-body').innerHTML = data.poses.map((p, i) => `
            <div class="sp-pose-card" style="flex-direction:column;gap:8px;">
                <img src="${p.image}" alt="${p.name}"
                    onclick="openLightbox(this.src, this.alt)"
                    style="width:100%;aspect-ratio:2/3;object-fit:cover;border-radius:8px;border:0.5px solid #1a1a1a;display:block;cursor:pointer;">
                <div>
                    <div class="sp-pose-num">0${i + 1}</div>
                    <div class="sp-pose-name">${p.name}</div>
                    <div class="sp-pose-desc">${p.description}</div>
                    <div class="sp-pose-tag">${p.tags}</div>
                </div>
            </div>
        `).join('');

        // ── Render checklist ─────────────────────────────────────────────
        document.getElementById('pose-checklist-sub').textContent  = `${data.poses.length} poses planned`;
        document.getElementById('pose-checklist-body').innerHTML   = data.poses.map(p => `
            <div class="sp-shot-item" onclick="toggleShot(this)">
                <div class="sp-shot-cb">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="sp-shot-text">${p.name.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
            </div>
        `).join('');

        // ── Persist to DB ────────────────────────────────────────────────
        fetch('save-plan.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ result_id: RESULT_ID, poses: data.poses }),
        });

    } catch (e) {
        console.error('[Poses]', e.message, '| raw:', rawResponse);
        document.getElementById('pose-body').innerHTML =
            '<div style="color:#6b7280;font-size:12px;grid-column:span 7;">Pose matching unavailable.</div>';
    }
}

// Kick off on page load
loadPoses();