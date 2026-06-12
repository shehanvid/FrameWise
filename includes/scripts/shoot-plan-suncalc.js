(function initSunCalc() {
    const lat       = parseFloat(SHOOT_CONTEXT.lat  || '6.9271');
    const lng       = parseFloat(SHOOT_CONTEXT.lng  || '79.8612');
    const dt        = new Date(SHOOT_CONTEXT.raw_datetime);
    const shootType = (SHOOT_CONTEXT.shoot_type || '').toLowerCase();
    const mood      = (SHOOT_CONTEXT.mood       || '').toLowerCase();

    if (isNaN(lat) || isNaN(lng)) return;

    const pos         = SunCalc.getPosition(dt, lat, lng);
    const azimuthDeg  = (pos.azimuth * 180 / Math.PI + 180) % 360;
    const altitudeDeg = pos.altitude * 180 / Math.PI;
    const shadowDeg   = (azimuthDeg + 180) % 360;
    const shadowLen   = altitudeDeg > 0
        ? (1 / Math.tan(pos.altitude)).toFixed(1) + '× height'
        : 'No shadow';


    document.getElementById('sun-azimuth').textContent  = degToCard(azimuthDeg);
    document.getElementById('sun-altitude').textContent = altitudeDesc(altitudeDeg);
    document.getElementById('shadow-dir').textContent   = degToCard(shadowDeg);
    document.getElementById('shadow-len').textContent   = shadowLen;
    document.getElementById('sun-pos-sub').textContent  = altitudeDeg < 0
        ? 'Sun below horizon at shoot time'
        : 'At shoot time · ' + Math.round(azimuthDeg) + '° azimuth';

    document.getElementById('sun-arrow').style.transform    = `rotate(${azimuthDeg}deg)`;
    document.getElementById('shadow-arrow').style.transform = `rotate(${shadowDeg}deg)`;


    const sunScore = altitudeDeg < 0  ? 10
                   : altitudeDeg < 6  ? 72
                   : altitudeDeg < 15 ? 98
                   : altitudeDeg < 30 ? 82
                   : altitudeDeg < 60 ? 55
                   :                    30;

    document.getElementById('lighting-score-bar').style.width = sunScore + '%';
    document.getElementById('lighting-score-val').textContent  = sunScore;


    const equipment  = SHOOT_CONTEXT.equipment  || [];
    const experience = SHOOT_CONTEXT.experience || '';
    const lighting   = SHOOT_CONTEXT.lighting_style || '';
    const camType    = SHOOT_CONTEXT.camera_type    || '';
    const hasNotes   = (SHOOT_CONTEXT.ai_notes || '').trim().length > 10;

    let gearScore = 50;
    const gearBonuses = {
        reflector: 8, tripod: 6, flash: 7, diffuser: 8,
        lens_prime: 6, lens_tele: 5, nd_filter: 5, drone: 5,
    };
    for (const [item, bonus] of Object.entries(gearBonuses)) {
        if (equipment.includes(item)) gearScore += bonus;
    }
    gearScore = Math.min(gearScore, 100);

    const expScore = { beginner: 55, intermediate: 75, advanced: 90, professional: 100 }[experience] || 70;

    let lightingScore = 70;
    if      (altitudeDeg < 0)  lightingScore = lighting === 'artificial' ? 85 : lighting === 'mixed' ? 60 : 30;
    else if (altitudeDeg < 15) lightingScore = lighting === 'natural'    ? 100 : lighting === 'mixed' ? 90 : 70;
    else if (altitudeDeg < 45) lightingScore = lighting === 'natural'    ? 85  : lighting === 'mixed' ? 90 : 75;
    else                       lightingScore = lighting === 'artificial' ? 80
                                             : lighting === 'mixed'      ? 75
                                             : (equipment.includes('reflector') || equipment.includes('diffuser')) ? 70 : 50;

    const camScore = { dslr: 90, mirrorless: 95, medium_format: 100, film: 85, smartphone: 60, action: 65 }[camType] || 75;

    const prefScore = Math.min(100, Math.round(
        gearScore     * 0.30 +
        expScore      * 0.25 +
        lightingScore * 0.30 +
        camScore      * 0.15
    ) + (hasNotes ? 5 : 0));

    document.getElementById('sun-score-bar').style.width = prefScore + '%';
    document.getElementById('sun-score-val').textContent  = prefScore;


    const wsVal   = parseInt(document.getElementById('weather-score-val').textContent) || 80;
    const overall = Math.min(100, Math.round(
        sunScore  * 0.30 +
        wsVal     * 0.25 +
        prefScore * 0.30 +
        sunScore  * 0.15
    ));

    document.getElementById('score-num').textContent = overall;

    const circ      = 188.4;
    const ringColor = overall >= 80 ? '#22c55e' : overall >= 60 ? '#f59e0b' : '#ef4444';
    const ring      = document.getElementById('score-ring-fill');
    ring.setAttribute('stroke-dashoffset', circ - (circ * overall / 100));
    ring.setAttribute('stroke', ringColor);


    const times       = SunCalc.getTimes(dt, lat, lng);
    const fmt         = t => t.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const now         = dt.getTime();
    const goldenStart = times.goldenHour;
    const goldenEnd   = times.goldenHourEnd || times.sunsetStart;
    const blueStart   = times.sunsetStart   || times.goldenHourEnd;
    const blueEnd     = times.sunset;
    const isGolden    = now >= goldenStart.getTime() && now <= goldenEnd.getTime();
    const isBlue      = now >= blueStart.getTime()   && now <= blueEnd.getTime();

    function hourBadge(label, bg, col) {
        return `<div class="sp-hour-badge" style="background:${bg};color:${col};border:0.5px solid ${col}44;">${label}</div>`;
    }

    const windowPct = Math.min(100, Math.max(0,
        Math.round(((now - goldenStart.getTime()) / (blueEnd.getTime() - goldenStart.getTime())) * 100)
    ));

    if (!CONDITIONS_SAVED) {
        document.getElementById('golden-body').innerHTML = `
            <div class="sp-hour-row">
                <div class="sp-hour-dot" style="background:#fb923c;box-shadow:0 0 5px #fb923c66;"></div>
                <div class="sp-hour-name">Golden Hour</div>
                <div class="sp-hour-time">${fmt(goldenStart)} – ${fmt(goldenEnd)}</div>
                ${isGolden ? hourBadge('Now', '#1e0f00', '#fb923c') : ''}
            </div>
            <div class="sp-hour-row">
                <div class="sp-hour-dot" style="background:#60a5fa;box-shadow:0 0 5px #60a5fa66;"></div>
                <div class="sp-hour-name">Blue Hour</div>
                <div class="sp-hour-time">${fmt(blueStart)} – ${fmt(blueEnd)}</div>
                ${isBlue ? hourBadge('Now', '#0c1a2e', '#60a5fa') : (!isGolden && now < blueStart.getTime() ? hourBadge('Soon', '#0c1a2e', '#60a5fa') : '')}
            </div>
            <div class="sp-hour-row">
                <div class="sp-hour-dot" style="background:#6b7280;"></div>
                <div class="sp-hour-name">Sunset</div>
                <div class="sp-hour-time">${fmt(times.sunset)}</div>
                <div></div>
            </div>
            <div class="sp-tl-track">
                <div class="sp-tl-fill" style="width:${windowPct}%;background:linear-gradient(90deg,#fb923c,#fbbf24);"></div>
            </div>
            <div class="sp-tl-labels">
                <span class="sp-tl-label">${fmt(goldenStart)}</span>
                <span class="sp-tl-label" style="color:#fb923c;font-weight:500;">${isGolden || isBlue ? 'NOW →' : 'SHOOT →'}</span>
                <span class="sp-tl-label">${fmt(blueEnd)}</span>
            </div>
        `;
    }


    let aperture, shutter, iso, focal, wb, apertureBadge, isoBadge, lightNote, lightQuality;

    if      (altitudeDeg < 0)  { aperture='f/2.0'; shutter='1/200s';  iso='800';  apertureBadge='Low light';   isoBadge='Boosted';    wb='3200K'; lightQuality='#6b7280'; lightNote='Sun below horizon — use flash or continuous artificial light'; }
    else if (altitudeDeg < 6)  { aperture='f/1.8'; shutter='1/60s';   iso='1600'; apertureBadge='Max light';   isoBadge='High';       wb='7500K'; lightQuality='#60a5fa'; lightNote='Blue hour — cool ethereal light, long exposures possible'; }
    else if (altitudeDeg < 15) { aperture='f/2.0'; shutter='1/250s';  iso='200';  apertureBadge='Bokeh';       isoBadge='Clean';      wb='4000K'; lightQuality='#fb923c'; lightNote='✦ Golden hour — warmest, most flattering light of the day'; }
    else if (altitudeDeg < 30) { aperture='f/2.8'; shutter='1/500s';  iso='200';  apertureBadge='Shallow DOF'; isoBadge='Clean';      wb='5200K'; lightQuality='#4ade80'; lightNote='Low-mid sun — soft shadows, balanced contrast'; }
    else if (altitudeDeg < 60) { aperture='f/4.0'; shutter='1/1000s'; iso='100';  apertureBadge='Deeper DOF';  isoBadge='Base ISO';   wb='6000K'; lightQuality='#fbbf24'; lightNote='High sun — harsh shadows. Seek open shade or use a diffuser'; }
    else                        { aperture='f/5.6'; shutter='1/2000s'; iso='100';  apertureBadge='Stop down';   isoBadge='Base ISO';   wb='6500K'; lightQuality='#e87070'; lightNote='Near-overhead sun — avoid direct light. Use shade or reflector'; }

    const focalMap = { portrait:'85mm', fashion:'85mm', product:'100mm', street:'35mm', landscape:'24mm', wedding:'50mm' };
    focal = focalMap[shootType] || '50mm';

    if (['dramatic', 'moody'].includes(mood) && altitudeDeg > 6) {
        iso      = String(Math.min(3200, parseInt(iso) * 2));
        isoBadge = 'Film grain';
    }
    if (['airy', 'natural'].includes(mood) && parseInt(iso) > 100) {
        iso      = String(Math.max(100, Math.round(parseInt(iso) / 2)));
        isoBadge = 'Clean';
    }

    function camRow(icon, label, value, badge) {
        return `
            <div class="sp-cam-setting">
                <div class="sp-cam-label">${icon}&nbsp;${label}</div>
                <div style="display:flex;align-items:center;">
                    <div class="sp-cam-val">${value}</div>
                    ${badge ? `<div class="sp-cam-badge">${badge}</div>` : ''}
                </div>
            </div>`;
    }

    document.getElementById('camera-body').innerHTML = `
        <div style="background:#0d0d0d;border:0.5px solid ${lightQuality}33;border-left:2px solid ${lightQuality};border-radius:8px;padding:9px 12px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="${lightQuality}" stroke-width="2" width="14" height="14" style="flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
            </svg>
            <div>
                <div style="font-size:11px;color:${lightQuality};font-weight:500;">${Math.round(altitudeDeg)}° sun altitude</div>
                <div style="font-size:11px;color:#6b7280;margin-top:1px;line-height:1.4;">${lightNote}</div>
            </div>
        </div>
        ${camRow('◎', 'Aperture',     aperture, apertureBadge)}
        ${camRow('⏱', 'Shutter speed', shutter, '')}
        ${camRow('☀', 'ISO',           iso,     isoBadge)}
        ${camRow('⌖', 'Focal length',  focal,   'Portrait')}
        ${camRow('◑', 'White balance', wb,      '')}
    `;


    if (!CONDITIONS_SAVED) {
        fetch('save-conditions.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                result_id:         RESULT_ID,
                sun_altitude:      altitudeDeg.toFixed(2),
                sun_azimuth:       azimuthDeg.toFixed(2),
                shadow_direction:  shadowDeg.toFixed(2),
                shadow_length:     shadowLen,
                golden_hour_start: fmt(goldenStart),
                golden_hour_end:   fmt(goldenEnd),
                blue_hour_start:   fmt(blueStart),
                blue_hour_end:     fmt(blueEnd),
                is_golden_hour:    isGolden ? 1 : 0,
                is_blue_hour:      isBlue   ? 1 : 0,
            }),
        });
    }

})();