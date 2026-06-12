(function fetchWeather() {

    if (WEATHER_SAVED) return;

    const lat = SHOOT_CONTEXT.lat;
    const lng = SHOOT_CONTEXT.lng;

    if (!lat || !lng) {
        document.getElementById('weather-body').innerHTML =
            '<div style="color:#6b7280;font-size:12px;">No coordinates — weather unavailable.</div>';
        return;
    }

    const url = `weather.php?lat=${lat}&lng=${lng}&datetime=${encodeURIComponent(SHOOT_CONTEXT.raw_datetime)}`;

    fetch(url)
        .then(r => r.json())
        .then(d => {
            if (d.error) throw new Error(d.error);

            document.getElementById('weather-sub').textContent = 'Live data · ' + d.location;
            document.getElementById('weather-body').innerHTML  = `
                <div class="sp-weather-big">${Math.round(d.temp)}°</div>
                <div class="sp-weather-cond">${d.description} · ${d.suitability}</div>
                <div class="sp-weather-grid">
                    <div class="sp-weather-stat">
                        <div class="sp-weather-stat-label">Humidity</div>
                        <div class="sp-weather-stat-val">${d.humidity}%</div>
                    </div>
                    <div class="sp-weather-stat">
                        <div class="sp-weather-stat-label">Wind</div>
                        <div class="sp-weather-stat-val">${d.wind} km/h</div>
                    </div>
                    <div class="sp-weather-stat">
                        <div class="sp-weather-stat-label">Cloud cover</div>
                        <div class="sp-weather-stat-val">${d.clouds}%</div>
                    </div>
                    <div class="sp-weather-stat">
                        <div class="sp-weather-stat-label">Rain chance</div>
                        <div class="sp-weather-stat-val">${d.rain_chance}%</div>
                    </div>
                </div>
            `;


            const ws = Math.max(0, Math.min(100, 100 - d.clouds - (d.rain_chance * 0.5)));
            document.getElementById('weather-score-bar').style.width = Math.round(ws) + '%';
            document.getElementById('weather-score-val').textContent  = Math.round(ws);


            fetch('save-conditions.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    result_id:          RESULT_ID,
                    weather_temp:       d.temp,
                    weather_condition:  d.description,
                    weather_humidity:   d.humidity,
                    weather_wind:       d.wind,
                    weather_clouds:     d.clouds,
                    weather_rain_chance: d.rain_chance,
                    weather_score:      Math.round(ws),

                    sun_altitude: null, sun_azimuth: null,
                    shadow_direction: null, shadow_length: null,
                    golden_hour_start: null, golden_hour_end: null,
                    blue_hour_start:   null, blue_hour_end:   null,
                    is_golden_hour:    null, is_blue_hour:    null,
                }),
            });
        })
        .catch(() => {
            document.getElementById('weather-body').innerHTML =
                '<div style="color:#6b7280;font-size:12px;">Weather unavailable.</div>';
        });

})();