// ─────────────────────────────────────────────
//  shoot-plan-utils.js
//  Small helpers used across the other modules
// ─────────────────────────────────────────────

// Toggle a shot/pose checklist row on or off
function toggleShot(row) {
    const cb  = row.querySelector('.sp-shot-cb');
    const txt = row.querySelector('.sp-shot-text');
    cb.classList.toggle('done');
    txt.classList.toggle('done');
}

// Auto-grow a textarea as the user types
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

// Send on Enter, new line on Shift+Enter
function handleChatKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendChatMessage();
    }
}

// Convert a compass bearing (0-360°) to a cardinal direction label
function degToCard(d) {
    const cards = [
        'North', 'North-Northeast', 'Northeast', 'East-Northeast',
        'East',  'East-Southeast',  'Southeast', 'South-Southeast',
        'South', 'South-Southwest', 'Southwest', 'West-Southwest',
        'West',  'West-Northwest',  'Northwest', 'North-Northwest',
    ];
    return cards[Math.round(d / 22.5) % 16];
}

// Turn a sun altitude in degrees into a plain-English description
function altitudeDesc(deg) {
    if (deg < 0)  return 'Below horizon';
    if (deg < 10) return 'Very low ('  + deg.toFixed(1) + '°)';
    if (deg < 25) return 'Low ('       + deg.toFixed(1) + '°)';
    if (deg < 45) return 'Mid-sky ('   + deg.toFixed(1) + '°)';
    if (deg < 70) return 'High ('      + deg.toFixed(1) + '°)';
    return 'Overhead (' + deg.toFixed(1) + '°)';
}

// Format a distance in metres — show km once it's ≥ 1 000 m
function distLabel(m) {
    return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : m + ' m';
}

// Build a five-star HTML string for a given numeric rating
function stars(rating) {
    if (!rating) return '';
    const full  = Math.floor(rating);
    const empty = 5 - full;
    return (
        '<span style="color:#f59e0b;font-size:10px;">'
        + '★'.repeat(full)
        + '<span style="color:#2a2a2a;">' + '★'.repeat(empty) + '</span>'
        + '</span>'
        + ' <span style="font-size:10px;color:#6b7280;">' + rating + '</span>'
    );
}

// Small open/closed pill badge for salon listings
function openBadge(open) {
    if (open === null) return '';
    return open
        ? '<span style="font-size:9px;background:transparent;border:1px solid #22c55e;color:#22c55e;border-radius:4px;padding:2px 6px;margin-left:6px;">Open</span>'
        : '<span style="font-size:9px;background:transparent;border:1px solid #ef4444;color:#ef4444;border-radius:4px;padding:2px 6px;margin-left:6px;">Closed</span>';
}

// ── Lightbox ──────────────────────────────────
function openLightbox(src, name) {
    document.getElementById('pose-lightbox-img').src        = src;
    document.getElementById('pose-lightbox-caption').textContent = name;
    document.getElementById('pose-lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox(e) {
    const ids = ['pose-lightbox', 'pose-lightbox-close'];
    if (ids.includes(e.target.id)) {
        document.getElementById('pose-lightbox').classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close lightbox on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('pose-lightbox').classList.remove('active');
        document.body.style.overflow = '';
    }
});