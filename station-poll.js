(function () {
    'use strict';

    var POLL_INTERVAL_MS = 10000;

    function formatDecimal(value, places) {
        return Number(value).toFixed(places);
    }

    function setStationReadout(data) {
        var stationName = document.getElementById('station-name');
        var datetime = document.getElementById('station-datetime');
        var wind = document.getElementById('station-wind');
        var size = document.getElementById('station-size');
        var choppiness = document.getElementById('station-choppiness');

        if (stationName && data.station_name) {
            stationName.textContent = data.station_name;
        }
        if (datetime && data.time) {
            datetime.textContent = data.time;
        }
        if (wind && data.wind !== undefined) {
            wind.textContent = formatDecimal(data.wind, WIND_SPEED_DECIMAL_PLACES);
        }
        if (size && data.size !== undefined) {
            size.textContent = formatDecimal(data.size, SIZE_DECIMAL_PLACES);
        }
        if (choppiness && data.choppiness !== undefined) {
            choppiness.textContent = formatDecimal(data.choppiness, CHOPPINESS_DECIMAL_PLACES);
        }
    }

    function applyToSimulator(data) {
        var simulator = window.wavesSimulator;
        if (!simulator) {
            return;
        }

        if (data.wind !== undefined) {
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
            .catch(function (error) {
                console.warn('Station poll failed:', error);
            });
    }

    function initStationReadout() {
        updateFromPayload({
            station_name: window.STATION_NAME,
            time: window.STATION_TIME,
            wind: window.INITIAL_WIND[0],
            size: window.INITIAL_SIZE,
            choppiness: window.INITIAL_CHOPPINESS
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
