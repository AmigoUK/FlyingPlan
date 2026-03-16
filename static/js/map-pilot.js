// Pilot map: read-only renderer for customer location, polygon, POIs, and waypoints
// NOTE: innerHTML usage in this file is safe - all content is generated from
// numeric data (indices, coordinates, altitude values), not user input.
(function () {
    "use strict";

    var mapEl = document.getElementById("pilot-map");
    if (!mapEl) return;

    var planLat = parseFloat(document.getElementById("plan-lat").value) || 0;
    var planLng = parseFloat(document.getElementById("plan-lng").value) || 0;
    if (!planLat && !planLng) return;

    // Tile layers
    var streetLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    });

    var satelliteLayer = L.tileLayer(
        "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        { maxZoom: 19, attribution: "&copy; Esri" }
    );

    var map = L.map("pilot-map", { layers: [streetLayer] }).setView([planLat, planLng], 16);

    // Satellite toggle (if present)
    var satToggle = document.getElementById("toggle-satellite");
    if (satToggle) {
        satToggle.addEventListener("change", function () {
            if (this.checked) {
                map.removeLayer(streetLayer);
                map.addLayer(satelliteLayer);
            } else {
                map.removeLayer(satelliteLayer);
                map.addLayer(streetLayer);
            }
        });
    }

    // Collect all points for auto-fit bounds
    var allLatLngs = [[planLat, planLng]];

    // Customer pin (red)
    L.marker([planLat, planLng], {
        icon: L.divIcon({
            className: "",
            html: '<i class="bi bi-geo-alt-fill" style="color: #dc3545; font-size: 1.5rem;"></i>',
            iconSize: [24, 24],
            iconAnchor: [12, 24],
        }),
    })
        .addTo(map)
        .bindTooltip("Customer Location", { direction: "top" });

    // Customer polygon (blue)
    var polygonEl = document.getElementById("plan-polygon");
    if (polygonEl && polygonEl.value) {
        try {
            var coords = JSON.parse(polygonEl.value);
            if (coords.length > 0) {
                L.polygon(coords, { color: "#0d6efd", fillOpacity: 0.1, interactive: false }).addTo(map);
                coords.forEach(function (c) { allLatLngs.push(c); });
            }
        } catch (e) { /* ignore */ }
    }

    // Customer POIs (orange stars)
    var poisEl = document.getElementById("plan-pois");
    if (poisEl && poisEl.value) {
        try {
            var poisData = JSON.parse(poisEl.value);
            poisData.forEach(function (p) {
                L.marker([p.lat, p.lng], {
                    icon: L.divIcon({
                        className: "",
                        html: '<i class="bi bi-star-fill" style="color: #fd7e14; font-size: 1rem;"></i>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8],
                    }),
                })
                    .addTo(map)
                    .bindTooltip(p.label || "POI", { direction: "top" });
                allLatLngs.push([p.lat, p.lng]);
            });
        } catch (e) { /* ignore */ }
    }

    // Waypoints (color-coded, non-draggable)
    var wpEl = document.getElementById("plan-waypoints");
    if (wpEl && wpEl.value) {
        try {
            var waypoints = JSON.parse(wpEl.value);
            if (waypoints.length > 0) {
                // Add waypoint markers
                waypoints.forEach(function (w, i) {
                    var ratio = Math.min((w.altitude_m || 30) / 120, 1);
                    var r = Math.round(ratio * 220);
                    var g = Math.round((1 - ratio) * 180);
                    var color = "rgb(" + r + "," + g + ",50)";

                    L.marker([w.lat, w.lng], {
                        icon: L.divIcon({
                            className: "",
                            html:
                                '<div style="background:' + color +
                                ';color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.4);">' +
                                i + "</div>",
                            iconSize: [24, 24],
                            iconAnchor: [12, 12],
                        }),
                    })
                        .addTo(map)
                        .bindTooltip("WP " + i + " (" + (w.altitude_m || 30) + "m)", { direction: "top", offset: [0, -15] });

                    allLatLngs.push([w.lat, w.lng]);
                });

                // Draw route line
                if (waypoints.length >= 2) {
                    var latlngs = waypoints.map(function (w) { return [w.lat, w.lng]; });
                    L.polyline(latlngs, {
                        color: "#198754",
                        weight: 3,
                        dashArray: "8, 6",
                    }).addTo(map);

                    // Direction arrows
                    for (var i = 0; i < latlngs.length - 1; i++) {
                        var from = L.latLng(latlngs[i]);
                        var to = L.latLng(latlngs[i + 1]);
                        var mid = L.latLng(
                            (from.lat + to.lat) / 2,
                            (from.lng + to.lng) / 2
                        );
                        var angle = Math.atan2(to.lng - from.lng, to.lat - from.lat) * (180 / Math.PI);
                        L.marker(mid, {
                            icon: L.divIcon({
                                className: "",
                                html: '<div style="transform:rotate(' + angle + 'deg);color:#198754;font-size:16px;">&#9650;</div>',
                                iconSize: [16, 16],
                                iconAnchor: [8, 8],
                            }),
                            interactive: false,
                        }).addTo(map);
                    }
                }
            }
        } catch (e) { /* ignore */ }
    }

    // Auto-fit bounds to show all markers
    if (allLatLngs.length > 1) {
        map.fitBounds(allLatLngs, { padding: [30, 30], maxZoom: 17 });
    }
})();
