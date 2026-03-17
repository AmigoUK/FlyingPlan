/**
 * Weather panel: displays current conditions + warnings.
 */
var WeatherPanel = (function () {
    "use strict";

    function render(containerId, data) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = "";

        if (data.error) {
            var errP = document.createElement("p");
            errP.className = "text-danger small";
            errP.textContent = "Weather unavailable: " + data.error;
            el.appendChild(errP);
            return;
        }

        var c = data.current;
        if (!c) return;

        // Warnings
        if (data.warnings && data.warnings.length > 0) {
            data.warnings.forEach(function (w) {
                var badge = document.createElement("div");
                badge.className = "alert alert-warning py-1 px-2 mb-1 small";
                badge.textContent = w;
                el.appendChild(badge);
            });
        }

        // Current conditions grid
        var grid = document.createElement("div");
        grid.className = "row g-1";
        var items = [
            { icon: "bi-thermometer-half", label: "Temp", value: (c.temp_c !== null ? c.temp_c + "\u00b0C" : "—") },
            { icon: "bi-wind", label: "Wind", value: (c.wind_speed_kmh !== null ? c.wind_speed_kmh + " km/h" : "—") },
            { icon: "bi-arrow-up-circle", label: "Gusts", value: (c.wind_gusts_kmh !== null ? c.wind_gusts_kmh + " km/h" : "—") },
            { icon: "bi-droplet", label: "Rain", value: (c.precipitation_mm !== null ? c.precipitation_mm + " mm" : "—") },
            { icon: "bi-cloud", label: "Cloud", value: (c.cloud_cover_pct !== null ? c.cloud_cover_pct + "%" : "—") },
            { icon: "bi-eye", label: "Vis", value: _formatVis(c.visibility_m) },
        ];

        items.forEach(function (item) {
            var col = document.createElement("div");
            col.className = "col-4 text-center";
            var icon = document.createElement("i");
            icon.className = "bi " + item.icon + " d-block";
            icon.style.fontSize = "1.2rem";
            var val = document.createElement("div");
            val.className = "fw-bold small";
            val.textContent = item.value;
            var lbl = document.createElement("div");
            lbl.className = "text-muted";
            lbl.style.fontSize = "0.7rem";
            lbl.textContent = item.label;
            col.appendChild(icon);
            col.appendChild(val);
            col.appendChild(lbl);
            grid.appendChild(col);
        });
        el.appendChild(grid);
    }

    function _formatVis(m) {
        if (m === null || m === undefined) return "—";
        if (m >= 10000) return ">10 km";
        if (m >= 1000) return (m / 1000).toFixed(1) + " km";
        return m + " m";
    }

    return { render: render };
})();
