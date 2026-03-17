// Shared mission: read-only map renderer
(function () {
    "use strict";

    var mapEl = document.getElementById("shared-map");
    if (!mapEl) return;

    var planLat = parseFloat(document.getElementById("plan-lat").value) || 0;
    var planLng = parseFloat(document.getElementById("plan-lng").value) || 0;
    if (!planLat && !planLng) return;

    var map = L.map("shared-map").setView([planLat, planLng], 16);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19, attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    var allLatLngs = [[planLat, planLng]];

    // Customer pin
    L.marker([planLat, planLng], {
        icon: L.divIcon({
            className: "",
            html: '<i class="bi bi-geo-alt-fill" style="color: #dc3545; font-size: 1.5rem;"></i>',
            iconSize: [24, 24], iconAnchor: [12, 24],
        }),
    }).addTo(map).bindTooltip("Location", { direction: "top" });

    // Polygon
    var polygonEl = document.getElementById("plan-polygon");
    if (polygonEl && polygonEl.value) {
        try {
            var coords = JSON.parse(polygonEl.value);
            if (coords.length > 0) {
                L.polygon(coords, { color: "#0d6efd", fillOpacity: 0.1, interactive: false }).addTo(map);
                coords.forEach(function (c) { allLatLngs.push(c); });
            }
        } catch (e) {}
    }

    // POIs
    try {
        var pois = JSON.parse(document.getElementById("plan-pois").value);
        pois.forEach(function (p) {
            L.marker([p.lat, p.lng], {
                icon: L.divIcon({
                    className: "",
                    html: '<i class="bi bi-star-fill" style="color: #fd7e14; font-size: 1rem;"></i>',
                    iconSize: [16, 16], iconAnchor: [8, 8],
                }),
            }).addTo(map).bindTooltip(p.label || "POI", { direction: "top" });
            allLatLngs.push([p.lat, p.lng]);
        });
    } catch (e) {}

    // Waypoints
    try {
        var waypoints = JSON.parse(document.getElementById("plan-waypoints").value);
        if (waypoints.length > 0) {
            waypoints.forEach(function (w, i) {
                var ratio = Math.min((w.altitude_m || 30) / 120, 1);
                var r = Math.round(ratio * 220);
                var g = Math.round((1 - ratio) * 180);
                var color = "rgb(" + r + "," + g + ",50)";
                L.marker([w.lat, w.lng], {
                    icon: L.divIcon({
                        className: "",
                        html: '<div style="background:' + color + ';color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.4);">' + i + "</div>",
                        iconSize: [24, 24], iconAnchor: [12, 12],
                    }),
                }).addTo(map).bindTooltip("WP " + i + " (" + (w.altitude_m || 30) + "m)", { direction: "top", offset: [0, -15] });
                allLatLngs.push([w.lat, w.lng]);
            });

            if (waypoints.length >= 2) {
                var latlngs = waypoints.map(function (w) { return [w.lat, w.lng]; });
                L.polyline(latlngs, { color: "#198754", weight: 3, dashArray: "8, 6" }).addTo(map);
            }
        }
    } catch (e) {}

    if (allLatLngs.length > 1) {
        map.fitBounds(allLatLngs, { padding: [30, 30], maxZoom: 17 });
    }
})();
