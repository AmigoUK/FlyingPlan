/**
 * Map measurement tools: ruler mode + route stats calculator.
 * Integrates with waypoint arrays from map-admin.js / map-pilot-edit.js.
 *
 * Usage: call MapMeasure.init(map) then MapMeasure.updateStats(waypoints)
 */
var MapMeasure = (function () {
    "use strict";

    var _map = null;
    var _rulerActive = false;
    var _rulerPoints = [];
    var _rulerMarkers = [];
    var _rulerLine = null;

    function init(map) {
        _map = map;
    }

    function toggleRuler() {
        _rulerActive = !_rulerActive;
        if (!_rulerActive) {
            _clearRuler();
        }
        return _rulerActive;
    }

    function isRulerActive() {
        return _rulerActive;
    }

    function handleMapClick(latlng) {
        if (!_rulerActive || !_map) return false;

        _rulerPoints.push(latlng);
        var marker = L.circleMarker(latlng, {
            radius: 5, color: "#ff6600", fillColor: "#ff6600", fillOpacity: 1,
        }).addTo(_map);
        _rulerMarkers.push(marker);

        if (_rulerPoints.length >= 2) {
            if (_rulerLine) _map.removeLayer(_rulerLine);
            _rulerLine = L.polyline(_rulerPoints, {
                color: "#ff6600", weight: 2, dashArray: "5, 5",
            }).addTo(_map);

            var totalDist = 0;
            for (var i = 1; i < _rulerPoints.length; i++) {
                totalDist += _rulerPoints[i - 1].distanceTo(_rulerPoints[i]);
            }
            var label = totalDist < 1000
                ? totalDist.toFixed(1) + " m"
                : (totalDist / 1000).toFixed(2) + " km";
            var lastPt = _rulerPoints[_rulerPoints.length - 1];
            marker.bindTooltip(label, { permanent: true, direction: "right" }).openTooltip();
        }
        return true;
    }

    function _clearRuler() {
        _rulerMarkers.forEach(function (m) { if (_map) _map.removeLayer(m); });
        _rulerMarkers = [];
        _rulerPoints = [];
        if (_rulerLine && _map) _map.removeLayer(_rulerLine);
        _rulerLine = null;
    }

    function calculateRouteStats(waypoints) {
        if (!waypoints || waypoints.length === 0) {
            return { distance_m: 0, distance_km: 0, est_time_s: 0, est_time_min: 0, count: 0 };
        }

        var totalDist = 0;
        var totalTime = 0;

        for (var i = 1; i < waypoints.length; i++) {
            var from = L.latLng(waypoints[i - 1].lat, waypoints[i - 1].lng);
            var to = L.latLng(waypoints[i].lat, waypoints[i].lng);
            var segDist = from.distanceTo(to);
            totalDist += segDist;

            var speed = waypoints[i].speed_ms || 5.0;
            totalTime += segDist / speed;
        }

        // Add hover times
        waypoints.forEach(function (wp) {
            totalTime += (wp.hover_time_s || 0);
        });

        return {
            distance_m: Math.round(totalDist),
            distance_km: (totalDist / 1000).toFixed(2),
            est_time_s: Math.round(totalTime),
            est_time_min: (totalTime / 60).toFixed(1),
            count: waypoints.length,
        };
    }

    function renderStatsBar(containerId, waypoints) {
        var el = document.getElementById(containerId);
        if (!el) return;
        var stats = calculateRouteStats(waypoints);
        if (stats.count === 0) {
            el.textContent = "";
            return;
        }
        el.textContent = "";
        var items = [
            { icon: "bi-signpost-2", text: stats.count + " waypoints" },
            { icon: "bi-arrows-angle-expand", text: stats.distance_m < 1000 ? stats.distance_m + " m" : stats.distance_km + " km" },
            { icon: "bi-clock", text: stats.est_time_min + " min" },
        ];
        items.forEach(function (item) {
            var span = document.createElement("span");
            span.className = "badge bg-light text-dark me-1";
            var icon = document.createElement("i");
            icon.className = "bi " + item.icon + " me-1";
            span.appendChild(icon);
            span.appendChild(document.createTextNode(item.text));
            el.appendChild(span);
        });
    }

    return {
        init: init,
        toggleRuler: toggleRuler,
        isRulerActive: isRulerActive,
        handleMapClick: handleMapClick,
        calculateRouteStats: calculateRouteStats,
        renderStatsBar: renderStatsBar,
    };
})();
