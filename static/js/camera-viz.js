// Camera Visualization: heading arrows, FOV polygons, and drag-to-rotate handles
(function () {
    "use strict";

    var _map = null;
    var _layerGroup = null;
    var _visible = false;
    var _droneProfiles = null;

    function init(map) {
        _map = map;
        _layerGroup = L.layerGroup();

        // Load drone profiles from hidden input
        var profEl = document.getElementById("drone-profiles");
        if (profEl) {
            try {
                _droneProfiles = JSON.parse(profEl.value);
            } catch (e) { /* ignore */ }
        }

        // Zoom-gated visibility
        _map.on("zoomend", function () {
            var zoom = _map.getZoom();
            if (zoom >= 17 && !_visible) {
                _visible = true;
                _layerGroup.addTo(_map);
                render();
            } else if (zoom < 17 && _visible) {
                _visible = false;
                _map.removeLayer(_layerGroup);
            }
        });

        // Re-render on waypoint changes
        var mapEl = document.getElementById("admin-map");
        if (mapEl) {
            mapEl.addEventListener("waypoints-changed", function () {
                if (_visible) render();
            });
            mapEl.addEventListener("waypoint-selected", function () {
                if (_visible) render();
            });
        }
    }

    function render() {
        if (!_map || !_visible) return;
        _layerGroup.clearLayers();

        if (!window.WaypointEditor) return;
        var waypoints = window.WaypointEditor.getWaypoints();
        if (!waypoints || waypoints.length === 0) return;

        var bounds = _map.getBounds();
        var profile = _getActiveProfile();

        waypoints.forEach(function (wp, idx) {
            var ll = L.latLng(wp.lat, wp.lng);
            // Only render waypoints in current viewport
            if (!bounds.contains(ll)) return;

            var heading = _getEffectiveHeading(wp, idx, waypoints);
            var isAuto = wp.heading_deg === null || wp.heading_deg === undefined;

            _renderWaypointViz(wp, idx, heading, isAuto, profile);
        });
    }

    function _getActiveProfile() {
        if (!_droneProfiles) return null;
        var sel = document.getElementById("drone-model-select");
        var model = sel ? sel.value : "mini_4_pro";
        return _droneProfiles[model] || _droneProfiles["mini_4_pro"] || null;
    }

    function _getEffectiveHeading(wp, idx, waypoints) {
        if (wp.heading_deg !== null && wp.heading_deg !== undefined) {
            return wp.heading_deg;
        }
        // Auto: bearing to next waypoint (or from previous if last)
        if (idx < waypoints.length - 1) {
            return _bearing(wp.lat, wp.lng, waypoints[idx + 1].lat, waypoints[idx + 1].lng);
        } else if (idx > 0) {
            return _bearing(waypoints[idx - 1].lat, waypoints[idx - 1].lng, wp.lat, wp.lng);
        }
        return 0;
    }

    function _bearing(lat1, lng1, lat2, lng2) {
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var y = Math.sin(dLng) * Math.cos(lat2 * Math.PI / 180);
        var x = Math.cos(lat1 * Math.PI / 180) * Math.sin(lat2 * Math.PI / 180) -
                Math.sin(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.cos(dLng);
        var brng = Math.atan2(y, x) * 180 / Math.PI;
        return (brng + 360) % 360;
    }

    function _offsetLatLng(lat, lng, distMetres, bearingDeg) {
        var R = 6371000;
        var brng = bearingDeg * Math.PI / 180;
        var lat1 = lat * Math.PI / 180;
        var lng1 = lng * Math.PI / 180;
        var d = distMetres / R;
        var lat2 = Math.asin(Math.sin(lat1) * Math.cos(d) + Math.cos(lat1) * Math.sin(d) * Math.cos(brng));
        var lng2 = lng1 + Math.atan2(Math.sin(brng) * Math.sin(d) * Math.cos(lat1),
                                       Math.cos(d) - Math.sin(lat1) * Math.sin(lat2));
        return L.latLng(lat2 * 180 / Math.PI, lng2 * 180 / Math.PI);
    }

    function _renderWaypointViz(wp, idx, heading, isAuto, profile) {
        var ll = L.latLng(wp.lat, wp.lng);
        var arrowLen = (wp.altitude_m || 30) * 0.4;
        var tipLL = _offsetLatLng(wp.lat, wp.lng, arrowLen, heading);

        // Heading arrow
        var arrowOpacity = isAuto ? 0.4 : 0.8;
        L.polyline([ll, tipLL], {
            color: "#0d6efd",
            weight: 3,
            opacity: arrowOpacity,
        }).addTo(_layerGroup);

        // Arrowhead triangle at tip
        var headSize = Math.max(arrowLen * 0.15, 2);
        var leftLL = _offsetLatLng(tipLL.lat, tipLL.lng, headSize, (heading + 150) % 360);
        var rightLL = _offsetLatLng(tipLL.lat, tipLL.lng, headSize, (heading + 210) % 360);
        L.polygon([tipLL, leftLL, rightLL], {
            color: "#0d6efd",
            fillColor: "#0d6efd",
            fillOpacity: arrowOpacity,
            weight: 1,
            opacity: arrowOpacity,
        }).addTo(_layerGroup);

        // FOV polygon (only if gimbal angled enough and profile available)
        var gimbal = wp.gimbal_pitch_deg !== undefined ? wp.gimbal_pitch_deg : -90;
        if (profile && gimbal <= -10) {
            _renderFOV(wp, heading, gimbal, profile);
        }

        // Drag-to-rotate heading handle
        _renderDragHandle(wp, idx, tipLL);
    }

    function _renderFOV(wp, heading, gimbalDeg, profile) {
        var alt = wp.altitude_m || 30;
        var sW = profile.sensor_width_mm;
        var sH = profile.sensor_height_mm;
        var fL = profile.focal_length_mm;
        if (!sW || !sH || !fL) return;

        var hfovHalf = Math.atan(sW / (2 * fL));
        var vfovHalf = Math.atan(sH / (2 * fL));
        var lookFromVert = Math.PI / 2 + gimbalDeg * Math.PI / 180;

        var nearDist = alt * Math.tan(lookFromVert - vfovHalf);
        var farDist = alt * Math.tan(lookFromVert + vfovHalf);

        // Cap far distance to avoid huge polygons
        if (farDist > 500) farDist = 500;
        if (farDist < 0) return; // Camera looking past horizon

        var nearHalfW = Math.abs(nearDist) * Math.tan(hfovHalf);
        var farHalfW = Math.abs(farDist) * Math.tan(hfovHalf);

        // Four corners in local frame (forward = heading direction, side = perpendicular)
        var corners = [
            { fwd: nearDist, side: -nearHalfW },
            { fwd: nearDist, side: nearHalfW },
            { fwd: farDist, side: farHalfW },
            { fwd: farDist, side: -farHalfW },
        ];

        var headingRad = heading * Math.PI / 180;
        var latlngs = corners.map(function (c) {
            var rotNorth = c.fwd * Math.cos(headingRad) - c.side * Math.sin(headingRad);
            var rotEast = c.fwd * Math.sin(headingRad) + c.side * Math.cos(headingRad);
            var dist = Math.sqrt(rotNorth * rotNorth + rotEast * rotEast);
            if (dist === 0) return L.latLng(wp.lat, wp.lng);
            var angle = Math.atan2(rotEast, rotNorth) * 180 / Math.PI;
            return _offsetLatLng(wp.lat, wp.lng, dist, angle);
        });

        L.polygon(latlngs, {
            color: "#0d6efd",
            fillColor: "#0d6efd",
            fillOpacity: 0.12,
            weight: 1,
            opacity: 0.4,
            interactive: false,
        }).addTo(_layerGroup);
    }

    function _renderDragHandle(wp, idx, tipLL) {
        var handle = L.marker(tipLL, {
            draggable: true,
            icon: L.divIcon({
                className: "",
                html: '<div class="camera-drag-handle"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6],
            }),
        }).addTo(_layerGroup);

        handle.on("drag", function () {
            var pos = handle.getLatLng();
            wp.heading_deg = Math.round(_bearing(wp.lat, wp.lng, pos.lat, pos.lng));
        });

        handle.on("dragend", function () {
            var pos = handle.getLatLng();
            var newHeading = Math.round(_bearing(wp.lat, wp.lng, pos.lat, pos.lng));
            if (window.WaypointEditor) {
                window.WaypointEditor.updateWaypointField(idx, "heading_deg", newHeading);
            }
        });
    }

    window.CameraViz = {
        init: init,
        render: render,
    };
})();
