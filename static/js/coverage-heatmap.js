/**
 * Coverage analysis heatmap overlay for Leaflet.
 * Renders overlap counts as coloured rectangles on the map.
 * Red (0-1) -> Yellow (2-3) -> Green (4+)
 */
var CoverageHeatmap = (function () {
    "use strict";

    var _map = null;
    var _layer = null;
    var _visible = false;

    function init(map) {
        _map = map;
        _layer = L.layerGroup();
    }

    function render(data) {
        if (!_map || !_layer) return;
        _layer.clearLayers();

        var grid = data.grid;
        var bounds = data.bounds;
        var rows = data.rows;
        var cols = data.cols;

        if (!grid || rows === 0 || cols === 0) return;

        var latStep = (bounds.max_lat - bounds.min_lat) / rows;
        var lngStep = (bounds.max_lng - bounds.min_lng) / cols;

        for (var r = 0; r < rows; r++) {
            for (var c = 0; c < cols; c++) {
                var count = grid[r][c];
                if (count === 0) continue;

                var color = _overlapColor(count);
                var sw = [bounds.min_lat + r * latStep, bounds.min_lng + c * lngStep];
                var ne = [bounds.min_lat + (r + 1) * latStep, bounds.min_lng + (c + 1) * lngStep];

                L.rectangle([sw, ne], {
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.4,
                    weight: 0,
                    interactive: false,
                }).addTo(_layer);
            }
        }

        if (_visible) {
            _layer.addTo(_map);
        }
    }

    function _overlapColor(count) {
        if (count <= 1) return "#dc3545";      // red
        if (count <= 2) return "#fd7e14";       // orange
        if (count <= 3) return "#ffc107";       // yellow
        if (count <= 5) return "#28a745";       // green
        return "#198754";                        // dark green
    }

    function toggle() {
        if (!_map || !_layer) return false;
        if (_visible) {
            _map.removeLayer(_layer);
            _visible = false;
        } else {
            _layer.addTo(_map);
            _visible = true;
        }
        return _visible;
    }

    function isVisible() {
        return _visible;
    }

    function renderStats(containerId, stats) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = "";

        var items = [
            { label: "Min Overlap", value: stats.min_overlap, unit: " images" },
            { label: "Avg Overlap", value: stats.avg_overlap, unit: " images" },
            { label: "Max Overlap", value: stats.max_overlap, unit: " images" },
            { label: "Coverage Area", value: stats.coverage_area_sqm, unit: " m\u00b2" },
            { label: "Sufficient (3+)", value: stats.sufficient_pct, unit: "%" },
        ];

        items.forEach(function (item) {
            var row = document.createElement("div");
            row.className = "d-flex justify-content-between small";
            var lbl = document.createElement("span");
            lbl.className = "text-muted";
            lbl.textContent = item.label;
            var val = document.createElement("strong");
            val.textContent = item.value + item.unit;
            row.appendChild(lbl);
            row.appendChild(val);
            el.appendChild(row);
        });
    }

    return {
        init: init,
        render: render,
        toggle: toggle,
        isVisible: isVisible,
        renderStats: renderStats,
    };
})();
