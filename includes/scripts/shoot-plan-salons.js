(async function loadNearbySalons() {

    const lat = SHOOT_CONTEXT.lat;
    const lng = SHOOT_CONTEXT.lng;

    if (!lat || !lng) {
        document.getElementById('salons-body').innerHTML =
            '<div style="color:#6b7280;font-size:12px;">No coordinates for this location.</div>';
        return;
    }

    try {
        const resp = await fetch('nearby-salons.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ lat, lng, result_id: RESULT_ID }),
        });

        const rawText = await resp.text();
        let data;
        try {
            data = JSON.parse(rawText);
        } catch (parseErr) {
            document.getElementById('salons-body').innerHTML =
                '<div style="color:#ef4444;font-size:12px;">Parse error — check console.</div>';
            console.error('[Salons] JSON parse failed:', parseErr.message, '| raw:', rawText);
            return;
        }

        if (data.error) throw new Error(data.error);

        if (!data.salons || data.salons.length === 0) {
            document.getElementById('salons-body').innerHTML =
                '<div style="color:#6b7280;font-size:12px;">No salons found within 3 km.</div>';
            document.getElementById('salons-sub').textContent = 'None found nearby';
            return;
        }


        const salons = data.salons.slice(0, 3);
        document.getElementById('salons-sub').textContent = salons.length + ' found · within 3 km';

        document.getElementById('salons-body').innerHTML = salons.map((s, i) => {
            const isLast = i === salons.length - 1;
            return `
                <div style="padding:10px 0;border-bottom:${isLast ? 'none' : '0.5px solid #161616'};">

                    
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;">
                        <span style="font-size:13px;color:#e5e7eb;font-weight:500;">${s.name.replace(/</g, '&lt;')}</span>
                        ${openBadge(s.open)}
                    </div>

                    
                    <div style="font-size:11px;color:#6b7280;margin-bottom:4px;line-height:1.4;">
                        ${s.address.replace(/</g, '&lt;')}
                    </div>

                    
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                        ${stars(s.rating)}
                        <span style="font-size:10px;color:#4b5563;">· ${distLabel(s.distance)} away</span>
                    </div>

                    
                    <div style="display:flex;gap:6px;">
                        ${s.phone ? `
                        <a href="tel:${s.phone}" style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;border:1px solid #22c55e;border-radius:8px;padding:7px 10px;font-size:11px;color:#22c55e;text-decoration:none;">
                            <svg fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="1.5" width="11" height="11">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 6.75z"/>
                            </svg>
                            Call
                        </a>` : `
                        <span style="flex:1;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid #374151;border-radius:8px;padding:7px 10px;font-size:11px;color:#4b5563;">
                            No phone
                        </span>`}

                        <a href="${s.maps_url}" target="_blank" style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;border:1px solid #60a5fa;border-radius:8px;padding:7px 10px;font-size:11px;color:#60a5fa;text-decoration:none;">
                            <svg fill="none" viewBox="0 0 24 24" stroke="#60a5fa" stroke-width="1.5" width="11" height="11">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                            </svg>
                            Open Maps
                        </a>
                    </div>

                </div>
            `;
        }).join('');

    } catch (e) {
        console.error('[Salons]', e.message);
        document.getElementById('salons-body').innerHTML =
            '<div style="color:#6b7280;font-size:12px;">Salons unavailable: ' + e.message + '</div>';
    }

})();