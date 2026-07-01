(function () {
    'use strict';

    var POLL_INTERVAL_MS = 10000;
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function formatDecimal(value, places) {
        return Number(value).toFixed(places);
    }

    function setStationReadout(data) {
        var stationName = document.getElementById('station-name');
        var datetime = document.getElementById('station-datetime');
        var wind = document.getElementById('station-wind');
        var period = document.getElementById('station-period');
        var choppiness = document.getElementById('station-choppiness');

        if (stationName && data.station_name) {
            stationName.textContent = data.station_name;
        }
        if (datetime) {
            if (data.time) {
                datetime.textContent = data.time;
            }
            if (data.time_iso) {
                datetime.setAttribute('datetime', data.time_iso);
            }
        }
        if (wind && data.wind !== undefined) {
            wind.textContent = formatDecimal(data.wind, WIND_SPEED_DECIMAL_PLACES);
        }
        if (period && data.wave_period !== undefined) {
            period.textContent = formatDecimal(data.wave_period, WAVE_PERIOD_DECIMAL_PLACES);
        }
        if (choppiness && data.choppiness !== undefined) {
            choppiness.textContent = formatDecimal(data.choppiness, CHOPPINESS_DECIMAL_PLACES);
        }
    }

    function applyToSimulator(data) {
        if (reducedMotion) {
            return;
        }

        var simulator = window.wavesSimulator;
        if (!simulator) {
            return;
        }

        if (data.wind_x !== undefined && data.wind_y !== undefined) {
            simulator.setWind(data.wind_x, data.wind_y);
        } else if (data.wind !== undefined) {
            simulator.setWind(data.wind, data.wind);
        }
        if (data.size !== undefined) {
            simulator.setSize(data.size);
        }
        if (data.choppiness !== undefined) {
            simulator.setChoppiness(data.choppiness);
        }
    }

    function updateFromPayload(data) {
        setStationReadout(data);
        applyToSimulator(data);
    }

    function fetchLatestData() {
        fetch('call-api.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Station API returned ' + response.status);
                }
                return response.json();
            })
            .then(updateFromPayload)
            .catch(function () {
                // Keep the last rendered values when polling fails.
            });
    }

    function initStationReadout() {
        var station = window.STATION || {};
        updateFromPayload({
            station_name: station.name,
            time: station.time,
            time_iso: station.timeIso,
            wind: station.wind,
            wind_x: station.windX,
            wind_y: station.windY,
            size: station.size,
            wave_period: station.wavePeriod,
            choppiness: station.choppiness
        });
    }

    function startPolling() {
        initStationReadout();
        window.setInterval(fetchLatestData, POLL_INTERVAL_MS);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startPolling);
    } else {
        startPolling();
    }
})();
