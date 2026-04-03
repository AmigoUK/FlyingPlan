// Admin map: read-only customer layers + editable waypoint route
// NOTE: innerHTML usage in this file is safe - all content is generated from
// numeric waypoint data (indices, coordinates, altitude values), not user input.
(function () {
    "use strict";

    var mapEl = document.getElementById("admin-map");
    if (!mapEl) return;

    var planId = document.getElementById("plan-id").value;
    var planLat = parseFloat(document.getElementById("plan-lat").value) || -33.87;
    var planLng = parseFloat(document.getElementById("plan-lng").value) || 151.21;
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
    var appBase = (document.getElementById("app-base-url") || {}).value || "";

    // Tile layers
    var streetLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    });

    var satelliteLayer = L.tileLayer(
        "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        { maxZoom: 19, attribution: "&copy; Esri" }
    );

    var map = L.map("admin-map", { layers: [streetLayer] }).setView([planLat, planLng], 16);

    // Satellite toggle
    document.getElementById("toggle-satellite").addEventListener("change", function () {
        if (this.checked) {
            map.removeLayer(streetLayer);
            map.addLayer(satelliteLayer);
        } else {
            map.removeLayer(satelliteLayer);
            map.addLayer(streetLayer);
        }
    });

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

    // Customer polygon + admin draw tools
    var polygonRaw = document.getElementById("plan-polygon").value;
    var drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    if (polygonRaw) {
        try {
            var coords = JSON.parse(polygonRaw);
            var existingPoly = L.polygon(coords, { color: "#0d6efd", fillOpacity: 0.1 });
            drawnItems.addLayer(existingPoly);
        } catch (e) { /* ignore */ }
    }

    // Leaflet.Draw for admin polygon editing
    if (typeof L.Control.Draw !== "undefined") {
        var drawControl = new L.Control.Draw({
            position: 'topleft',
            draw: {
                polyline: false,
                circle: false,
                circlemarker: false,
                marker: false,
                polygon: {
                    allowIntersection: false,
                    shapeOptions: { color: "#0d6efd", fillOpacity: 0.15, weight: 2 }
                },
                rectangle: {
                    shapeOptions: { color: "#0d6efd", fillOpacity: 0.15, weight: 2 }
                },
            },
            edit: {
                featureGroup: drawnItems,
                remove: true
            },
        });
        map.addControl(drawControl);

        map.on(L.Draw.Event.CREATED, function (ev) {
            drawnItems.clearLayers();
            drawnItems.addLayer(ev.layer);
            _savePolygon(ev.layer);
        });
        map.on(L.Draw.Event.EDITED, function () {
            drawnItems.eachLayer(function (layer) { _savePolygon(layer); });
        });
        map.on(L.Draw.Event.DELETED, function () {
            document.getElementById("plan-polygon").value = "";
            fetch(appBase + "/admin/" + planId + "/polygon", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({ polygon: null }),
            });
        });
    }

    function _savePolygon(layer) {
        var latlngs = layer.getLatLngs()[0].map(function (ll) { return [ll.lat, ll.lng]; });
        var json = JSON.stringify(latlngs);
        document.getElementById("plan-polygon").value = json;
        fetch(appBase + "/admin/" + planId + "/polygon", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
            body: JSON.stringify({ polygon: json }),
        }).then(function (r) { return r.json(); })
          .then(function (resp) {
              if (resp.success) _toast("Area polygon saved", "success");
          })
          .catch(function () { _toast("Failed to save polygon", "danger"); });
    }

    // Customer POIs (orange stars)
    var poisData = [];
    try {
        poisData = JSON.parse(document.getElementById("plan-pois").value);
    } catch (e) { /* ignore */ }
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
    });

    // === Waypoint system ===
    var waypoints = [];
    var waypointMarkers = [];
    var routeLayerGroup = L.layerGroup().addTo(map);
    var selectedIndex = -1;
    var _editorMode = "add";
    var _insertHighlight = null;
    mapEl.classList.add("map-mode-add");

    // Load existing waypoints
    try {
        var existing = JSON.parse(document.getElementById("plan-waypoints").value);
        existing.forEach(function (w) {
            addWaypoint(L.latLng(w.lat, w.lng), w);
        });
        updateRoute();
    } catch (e) { /* ignore */ }

    // Measurement tools
    if (typeof MapMeasure !== "undefined") {
        MapMeasure.init(map);
    }
    var rulerBtn = document.getElementById("btn-ruler");
    if (rulerBtn) {
        rulerBtn.addEventListener("click", function () {
            var active = MapMeasure.toggleRuler();
            rulerBtn.classList.toggle("active", active);
            rulerBtn.classList.toggle("btn-outline-info", !active);
            rulerBtn.classList.toggle("btn-info", active);
        });
    }

    // Click map to add waypoint (mode-dispatched)
    map.on("click", function (e) {
        if (typeof MapMeasure !== "undefined" && MapMeasure.isRulerActive()) {
            MapMeasure.handleMapClick(e.latlng);
            return;
        }
        if (e.originalEvent.shiftKey) return; // Facade Shift+click handled separately
        if (_editorMode === "add") {
            addWaypoint(e.latlng, {});
            updateRoute();
        } else if (_editorMode === "insert") {
            _handleInsertClick(e.latlng);
        }
        // pointer and delete modes: no-op on empty map click
    });

    // Insert mode: highlight nearest segment on mousemove
    map.on("mousemove", function (e) {
        if (_editorMode !== "insert" || waypoints.length < 2) {
            if (_insertHighlight) {
                map.removeLayer(_insertHighlight);
                _insertHighlight = null;
            }
            return;
        }
        var pt = map.latLngToContainerPoint(e.latlng);
        var bestDist = Infinity;
        var bestIdx = -1;
        for (var si = 0; si < waypoints.length - 1; si++) {
            var a = map.latLngToContainerPoint(L.latLng(waypoints[si].lat, waypoints[si].lng));
            var b = map.latLngToContainerPoint(L.latLng(waypoints[si + 1].lat, waypoints[si + 1].lng));
            var proj = _projectPointOnSegment(pt, a, b);
            if (proj.dist < bestDist) {
                bestDist = proj.dist;
                bestIdx = si;
            }
        }
        if (_insertHighlight) {
            map.removeLayer(_insertHighlight);
            _insertHighlight = null;
        }
        if (bestDist <= 20 && bestIdx >= 0) {
            _insertHighlight = L.polyline(
                [[waypoints[bestIdx].lat, waypoints[bestIdx].lng],
                 [waypoints[bestIdx + 1].lat, waypoints[bestIdx + 1].lng]],
                { color: "#0d6efd", weight: 6, opacity: 0.6 }
            ).addTo(map);
        }
    });

    function addWaypoint(latlng, data) {
        var idx = waypoints.length;
        var wp = {
            index: idx,
            lat: latlng.lat,
            lng: latlng.lng,
            altitude_m: data.altitude_m || 30.0,
            speed_ms: data.speed_ms || 5.0,
            heading_deg: data.heading_deg !== undefined ? data.heading_deg : null,
            gimbal_pitch_deg: data.gimbal_pitch_deg !== undefined ? data.gimbal_pitch_deg : -90.0,
            turn_mode: data.turn_mode || "toPointAndStopWithDiscontinuityCurvature",
            turn_damping_dist: data.turn_damping_dist || 0.0,
            hover_time_s: data.hover_time_s || 0.0,
            action_type: data.action_type || null,
            poi_lat: data.poi_lat || null,
            poi_lng: data.poi_lng || null,
        };
        waypoints.push(wp);

        var marker = L.marker(latlng, {
            draggable: true,
            icon: _waypointIcon(idx, wp),
        }).addTo(map);

        marker.bindTooltip("WP " + idx, { direction: "top", offset: [0, -15] });

        marker.on("dragend", function () {
            var pos = marker.getLatLng();
            wp.lat = pos.lat;
            wp.lng = pos.lng;
            updateRoute();
            updateWaypointList();
            mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
        });

        marker.on("click", function (e) {
            L.DomEvent.stopPropagation(e);
            if (_editorMode === "delete") {
                deleteWaypoint(wp.index);
            } else {
                selectWaypoint(wp.index);
            }
        });

        waypointMarkers.push(marker);
        updateWaypointList();
        mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
    }

    function _waypointIcon(idx, wp) {
        var altitude = (typeof wp === "object") ? (wp.altitude_m || 30) : wp;
        var actionType = (typeof wp === "object") ? wp.action_type : null;
        // Color by altitude: green (low) to red (high)
        var ratio = Math.min(altitude / 120, 1);
        var r = Math.round(ratio * 220);
        var g = Math.round((1 - ratio) * 180);
        var color = "rgb(" + r + "," + g + ",50)";

        var badge = "";
        if (actionType === "takePhoto") {
            badge = '<div style="position:absolute;top:-4px;right:-4px;background:#0d6efd;color:#fff;width:14px;height:14px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:8px;border:1px solid #fff;"><i class="bi bi-camera-fill"></i></div>';
        } else if (actionType === "startRecord") {
            badge = '<div style="position:absolute;top:-4px;right:-4px;background:#dc3545;width:12px;height:12px;border-radius:50%;border:1px solid #fff;"></div>';
        } else if (actionType === "stopRecord") {
            badge = '<div style="position:absolute;top:-4px;right:-4px;background:#6c757d;width:12px;height:12px;border-radius:2px;border:1px solid #fff;"></div>';
        }

        return L.divIcon({
            className: "",
            html:
                '<div style="position:relative;width:24px;height:24px;">' +
                '<div style="background:' + color +
                ';color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.4);">' +
                idx + "</div>" + badge + "</div>",
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        });
    }

    function _projectPointOnSegment(point, segA, segB) {
        var dx = segB.x - segA.x;
        var dy = segB.y - segA.y;
        var lenSq = dx * dx + dy * dy;
        if (lenSq === 0) return { dist: point.distanceTo(segA), point: segA, t: 0 };
        var t = Math.max(0, Math.min(1, ((point.x - segA.x) * dx + (point.y - segA.y) * dy) / lenSq));
        var proj = L.point(segA.x + t * dx, segA.y + t * dy);
        return { dist: point.distanceTo(proj), point: proj, t: t };
    }

    function insertWaypoint(atIndex, latlng, data) {
        var wp = {
            index: atIndex,
            lat: latlng.lat,
            lng: latlng.lng,
            altitude_m: data.altitude_m || 30.0,
            speed_ms: data.speed_ms || 5.0,
            heading_deg: data.heading_deg !== undefined ? data.heading_deg : null,
            gimbal_pitch_deg: data.gimbal_pitch_deg !== undefined ? data.gimbal_pitch_deg : -90.0,
            turn_mode: data.turn_mode || "toPointAndStopWithDiscontinuityCurvature",
            turn_damping_dist: data.turn_damping_dist || 0.0,
            hover_time_s: data.hover_time_s || 0.0,
            action_type: data.action_type || null,
            poi_lat: data.poi_lat || null,
            poi_lng: data.poi_lng || null,
        };
        waypoints.splice(atIndex, 0, wp);

        var marker = L.marker(latlng, {
            draggable: true,
            icon: _waypointIcon(atIndex, wp),
        }).addTo(map);

        marker.bindTooltip("WP " + atIndex, { direction: "top", offset: [0, -15] });

        marker.on("dragend", function () {
            var pos = marker.getLatLng();
            wp.lat = pos.lat;
            wp.lng = pos.lng;
            updateRoute();
            updateWaypointList();
            mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
        });

        marker.on("click", function (e) {
            L.DomEvent.stopPropagation(e);
            if (_editorMode === "delete") {
                deleteWaypoint(wp.index);
            } else {
                selectWaypoint(wp.index);
            }
        });

        waypointMarkers.splice(atIndex, 0, marker);

        // Reindex all subsequent waypoints
        for (var i = atIndex + 1; i < waypoints.length; i++) {
            waypoints[i].index = i;
            waypointMarkers[i].setIcon(_waypointIcon(i, waypoints[i]));
            waypointMarkers[i].setTooltipContent("WP " + i);
        }

        selectedIndex = atIndex;
        updateRoute();
        updateWaypointList();
        mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
    }

    function _handleInsertClick(latlng) {
        if (waypoints.length < 2) {
            addWaypoint(latlng, {});
            updateRoute();
            return;
        }

        var clickPt = map.latLngToContainerPoint(latlng);
        var bestDist = Infinity;
        var bestIdx = -1;
        var bestPoint = null;
        var bestT = 0;

        for (var i = 0; i < waypoints.length - 1; i++) {
            var segA = map.latLngToContainerPoint(L.latLng(waypoints[i].lat, waypoints[i].lng));
            var segB = map.latLngToContainerPoint(L.latLng(waypoints[i + 1].lat, waypoints[i + 1].lng));
            var proj = _projectPointOnSegment(clickPt, segA, segB);
            if (proj.dist < bestDist) {
                bestDist = proj.dist;
                bestIdx = i;
                bestPoint = proj.point;
                bestT = proj.t;
            }
        }

        if (bestDist > 20) return; // Too far from any segment (20 screen pixels)

        var insertLatlng = map.containerPointToLatLng(bestPoint);
        var wpA = waypoints[bestIdx];
        var wpB = waypoints[bestIdx + 1];
        var interpolatedData = {
            altitude_m: wpA.altitude_m + (wpB.altitude_m - wpA.altitude_m) * bestT,
            speed_ms: wpA.speed_ms + (wpB.speed_ms - wpA.speed_ms) * bestT,
            gimbal_pitch_deg: wpA.gimbal_pitch_deg,
            heading_deg: null,
        };

        insertWaypoint(bestIdx + 1, insertLatlng, interpolatedData);
    }

    function updateRoute() {
        routeLayerGroup.clearLayers();
        if (typeof MapMeasure !== "undefined") {
            MapMeasure.renderStatsBar("route-stats", waypoints);
        }
        if (waypoints.length < 2) return;

        var latlngs = waypoints.map(function (w) {
            return [w.lat, w.lng];
        });

        L.polyline(latlngs, {
            color: "#198754",
            weight: 3,
            dashArray: "8, 6",
        }).addTo(routeLayerGroup);

        // Add direction arrows
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
            }).addTo(routeLayerGroup);
        }
    }

    function selectWaypoint(idx) {
        selectedIndex = idx;
        updateWaypointList();
        mapEl.dispatchEvent(new CustomEvent("waypoint-selected", { detail: { index: idx } }));
    }

    function updateWaypointList() {
        var list = document.getElementById("waypoint-list");
        if (waypoints.length === 0) {
            list.textContent = "";
            var p = document.createElement("p");
            p.className = "text-muted small";
            p.textContent = "Click on the map to add waypoints.";
            list.appendChild(p);
            return;
        }

        // Build DOM elements instead of innerHTML to avoid XSS concerns
        list.textContent = "";
        waypoints.forEach(function (wp, i) {
            var isActive = i === selectedIndex;
            var item = document.createElement("div");
            item.className = "waypoint-item" + (isActive ? " active" : "");
            item.dataset.idx = i;

            var header = document.createElement("div");
            header.className = "d-flex justify-content-between align-items-center";

            var title = document.createElement("strong");
            title.textContent = "WP " + i;

            var delBtn = document.createElement("button");
            delBtn.className = "btn btn-sm btn-outline-danger py-0 px-1 btn-delete-wp";
            delBtn.dataset.idx = i;
            var delIcon = document.createElement("i");
            delIcon.className = "bi bi-x";
            delBtn.appendChild(delIcon);

            header.appendChild(title);
            header.appendChild(delBtn);
            item.appendChild(header);

            if (isActive) {
                var formHtml = _buildWaypointEditForm(wp);
                item.appendChild(formHtml);
            } else {
                var info = document.createElement("small");
                info.className = "text-muted";
                info.textContent = wp.altitude_m + "m | " + wp.speed_ms + "m/s" +
                    (wp.action_type ? " | " + wp.action_type : "");
                item.appendChild(info);
            }

            item.addEventListener("click", function (e) {
                if (e.target.closest(".btn-delete-wp") || e.target.closest("input") || e.target.closest("select")) return;
                selectWaypoint(i);
            });

            delBtn.addEventListener("click", function (e) {
                e.stopPropagation();
                deleteWaypoint(i);
            });

            list.appendChild(item);
        });

        // Bind inline edit fields
        list.querySelectorAll(".wp-field").forEach(function (input) {
            input.addEventListener("change", function () {
                var idx = parseInt(input.dataset.idx);
                var field = input.dataset.field;
                var val = input.value;
                if (input.type === "number") {
                    val = val === "" ? null : parseFloat(val);
                }
                if (val === "") val = null;
                waypoints[idx][field] = val;
                // Update marker icon if altitude or action changed
                if ((field === "altitude_m" || field === "action_type") && waypointMarkers[idx]) {
                    waypointMarkers[idx].setIcon(_waypointIcon(idx, waypoints[idx]));
                }
                mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
            });
        });
    }

    function _buildWaypointEditForm(wp) {
        var container = document.createElement("div");
        container.className = "row g-1 mt-1";

        var fields = [
            { label: "Alt (m)", field: "altitude_m", type: "number", value: wp.altitude_m, step: "1", min: "5", max: "120", colClass: "col-6" },
            { label: "Speed (m/s)", field: "speed_ms", type: "number", value: wp.speed_ms, step: "0.5", min: "1", max: "15", colClass: "col-6" },
            { label: "Heading (\u00b0)", field: "heading_deg", type: "number", value: wp.heading_deg !== null ? wp.heading_deg : "", step: "1", min: "0", max: "360", colClass: "col-6", placeholder: "Auto" },
            { label: "Gimbal (\u00b0)", field: "gimbal_pitch_deg", type: "number", value: wp.gimbal_pitch_deg, step: "5", min: "-90", max: "0", colClass: "col-6" },
            { label: "Hover (s)", field: "hover_time_s", type: "number", value: wp.hover_time_s, step: "1", min: "0", colClass: "col-6" },
        ];

        fields.forEach(function (f) {
            var col = document.createElement("div");
            col.className = f.colClass;
            var lbl = document.createElement("label");
            lbl.className = "form-label mb-0";
            lbl.style.fontSize = ".75rem";
            lbl.textContent = f.label;
            var input = document.createElement("input");
            input.type = f.type;
            input.className = "form-control form-control-sm wp-field";
            input.dataset.idx = wp.index;
            input.dataset.field = f.field;
            input.value = f.value;
            if (f.step) input.step = f.step;
            if (f.min) input.min = f.min;
            if (f.max) input.max = f.max;
            if (f.placeholder) input.placeholder = f.placeholder;
            col.appendChild(lbl);
            col.appendChild(input);
            container.appendChild(col);
        });

        // Action select
        var actionCol = document.createElement("div");
        actionCol.className = "col-6";
        var actionLbl = document.createElement("label");
        actionLbl.className = "form-label mb-0";
        actionLbl.style.fontSize = ".75rem";
        actionLbl.textContent = "Action";
        var actionSel = document.createElement("select");
        actionSel.className = "form-select form-select-sm wp-field";
        actionSel.dataset.idx = wp.index;
        actionSel.dataset.field = "action_type";
        [
            { value: "", text: "None" },
            { value: "takePhoto", text: "Take Photo" },
            { value: "startRecord", text: "Start Record" },
            { value: "stopRecord", text: "Stop Record" },
        ].forEach(function (opt) {
            var o = document.createElement("option");
            o.value = opt.value;
            o.textContent = opt.text;
            if ((wp.action_type || "") === opt.value) o.selected = true;
            actionSel.appendChild(o);
        });
        actionCol.appendChild(actionLbl);
        actionCol.appendChild(actionSel);
        container.appendChild(actionCol);

        // Turn mode select
        var turnCol = document.createElement("div");
        turnCol.className = "col-12";
        var turnLbl = document.createElement("label");
        turnLbl.className = "form-label mb-0";
        turnLbl.style.fontSize = ".75rem";
        turnLbl.textContent = "Turn Mode";
        var turnSel = document.createElement("select");
        turnSel.className = "form-select form-select-sm wp-field";
        turnSel.dataset.idx = wp.index;
        turnSel.dataset.field = "turn_mode";
        [
            { value: "toPointAndStopWithDiscontinuityCurvature", text: "Stop & Turn" },
            { value: "toPointAndStopWithContinuityCurvature", text: "Smooth Stop" },
            { value: "toPointAndPassWithContinuityCurvature", text: "Fly Through" },
        ].forEach(function (opt) {
            var o = document.createElement("option");
            o.value = opt.value;
            o.textContent = opt.text;
            if (wp.turn_mode === opt.value) o.selected = true;
            turnSel.appendChild(o);
        });
        turnCol.appendChild(turnLbl);
        turnCol.appendChild(turnSel);
        container.appendChild(turnCol);

        return container;
    }

    function deleteWaypoint(idx) {
        if (_editorMode !== "delete" && !confirm("Delete waypoint " + idx + "?")) return;
        // Remove marker
        map.removeLayer(waypointMarkers[idx]);
        waypointMarkers.splice(idx, 1);
        waypoints.splice(idx, 1);
        // Reindex
        waypoints.forEach(function (w, i) {
            w.index = i;
            if (waypointMarkers[i]) {
                waypointMarkers[i].setIcon(_waypointIcon(i, w));
                waypointMarkers[i].setTooltipContent("WP " + i);
            }
        });
        if (selectedIndex >= waypoints.length) selectedIndex = waypoints.length - 1;
        updateRoute();
        updateWaypointList();
        mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
    }

    // Save waypoints
    document.getElementById("btn-save-waypoints").addEventListener("click", function () {
        var data = waypoints.map(function (w) {
            return {
                lat: w.lat,
                lng: w.lng,
                altitude_m: w.altitude_m,
                speed_ms: w.speed_ms,
                heading_deg: w.heading_deg,
                gimbal_pitch_deg: w.gimbal_pitch_deg,
                turn_mode: w.turn_mode,
                turn_damping_dist: w.turn_damping_dist,
                hover_time_s: w.hover_time_s,
                action_type: w.action_type,
                poi_lat: w.poi_lat,
                poi_lng: w.poi_lng,
            };
        });

        fetch(appBase + "/admin/" + planId + "/waypoints", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRFToken": csrfToken,
            },
            body: JSON.stringify(data),
        })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (resp.success) {
                    _toast("Waypoints saved (" + resp.count + " points)", "success"); if(typeof gtag==="function")gtag("event","demo_waypoints_save",{count:resp.count});
                    if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepComplete("waypoints");
                } else {
                    _toast("Error: " + (resp.error || "Unknown"), "danger");
                }
            })
            .catch(function () {
                _toast("Failed to save waypoints", "danger");
            });
    });

    // Clear waypoints
    document.getElementById("btn-clear-waypoints").addEventListener("click", function () {
        if (!confirm("Clear all waypoints?")) return;
        waypointMarkers.forEach(function (m) { map.removeLayer(m); });
        waypointMarkers.length = 0;
        waypoints.length = 0;
        routeLayerGroup.clearLayers();
        selectedIndex = -1;
        updateWaypointList();
        mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
        if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepIncomplete("waypoints");
    });

    // Status change
    document.getElementById("status-select").addEventListener("change", function () {
        var self = this;
        fetch(appBase + "/admin/" + planId + "/status", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRFToken": csrfToken,
            },
            body: JSON.stringify({ status: self.value }),
        })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (resp.success) {
                    _toast("Status updated to " + resp.status.replace(/_/g, " "), "success");
                    // Update badge text
                    var badge = document.querySelector(".badge");
                    if (badge) {
                        badge.className = "badge badge-" + resp.status + " ms-2";
                        badge.textContent = resp.status.replace(/_/g, " ").replace(/\b\w/g, function (c) { return c.toUpperCase(); });
                    }
                }
            });
    });

    // Path tools
    if (typeof PathTools !== "undefined" && document.getElementById("path-tools-bar")) {
        PathTools.buildToolbar("path-tools-bar");

        function _applyPathTransform(newWps) {
            waypointMarkers.forEach(function (m) { map.removeLayer(m); });
            waypointMarkers.length = 0;
            waypoints.length = 0;
            routeLayerGroup.clearLayers();
            selectedIndex = -1;
            newWps.forEach(function (w) { addWaypoint(L.latLng(w.lat, w.lng), w); });
            updateRoute();
            mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
        }

        document.getElementById("path-tools-bar").addEventListener("click", function (e) {
            var target = e.target.closest("button");
            if (!target || waypoints.length < 2) return;

            if (target.id === "btn-path-reverse") {
                _applyPathTransform(PathTools.reversePath(waypoints));
                _toast("Path reversed", "success");
            } else if (target.id === "btn-path-straighten") {
                _applyPathTransform(PathTools.straightenPath(waypoints));
                _toast("Path straightened", "success");
            } else if (target.id === "btn-path-offset-left") {
                var dist = parseFloat(prompt("Offset distance (metres):", "10")) || 10;
                _applyPathTransform(PathTools.offsetPath(waypoints, dist, "left"));
                _toast("Path offset left " + dist + "m", "success");
            } else if (target.id === "btn-path-offset-right") {
                var dist2 = parseFloat(prompt("Offset distance (metres):", "10")) || 10;
                _applyPathTransform(PathTools.offsetPath(waypoints, dist2, "right"));
                _toast("Path offset right " + dist2 + "m", "success");
            }
        });
    }

    // GSD Calculator
    if (typeof GSDCalculator !== "undefined" && document.getElementById("gsd-panel")) {
        GSDCalculator.buildPanel("gsd-panel");
        document.getElementById("gsd-panel").addEventListener("click", function (e) {
            var btn = e.target.closest("#btn-calc-gsd");
            if (!btn) return;
            var alt = parseFloat(document.getElementById("gsd-altitude").value) || 30;
            var overlap = parseFloat(document.getElementById("gsd-overlap").value) || 70;
            fetch(appBase + "/admin/" + planId + "/gsd", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({ altitude_m: alt, overlap_pct: overlap }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    GSDCalculator.renderResults("gsd-results", data);
                    if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepComplete("gsd");
                })
                .catch(function () { _toast("GSD calculation failed", "danger"); });
        });
    }

    // Mission patterns
    if (typeof MissionPatterns !== "undefined" && document.getElementById("mission-patterns-panel")) {
        MissionPatterns.buildPanel("mission-patterns-panel");
        document.getElementById("mission-patterns-panel").addEventListener("click", function (e) {
            var genBtn = e.target.closest("#btn-generate-pattern");
            if (!genBtn) return;
            var config = MissionPatterns.getConfig();
            config.config = Object.assign({}, config);
            config.config.center_lat = planLat;
            config.config.center_lng = planLng;
            config.config.start_lat = planLat;
            config.config.start_lng = planLng;
            fetch(appBase + "/admin/" + planId + "/generate-pattern", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify(config),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) {
                        waypointMarkers.forEach(function (m) { map.removeLayer(m); });
                        waypointMarkers.length = 0;
                        waypoints.length = 0;
                        routeLayerGroup.clearLayers();
                        resp.waypoints.forEach(function (w) {
                            addWaypoint(L.latLng(w.lat, w.lng), w);
                        });
                        updateRoute();
                        _toast("Generated " + resp.count + " pattern waypoints", "success"); if(typeof gtag==="function")gtag("event","demo_pattern_generate",{count:resp.count});
                        if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepComplete("patterns");
                    } else {
                        _toast("Pattern error: " + (resp.error || "Unknown"), "danger");
                    }
                })
                .catch(function () { _toast("Pattern generation failed", "danger"); });
        });
    }

    // Grid planner
    if (typeof GridPlanner !== "undefined" && document.getElementById("grid-planner-panel")) {
        GridPlanner.init("grid-planner-panel");
        GridPlanner.buildPanel();
        document.getElementById("grid-planner-panel").addEventListener("click", function (e) {
            var genBtn = e.target.closest("#btn-generate-grid");
            if (!genBtn) return;
            var config = GridPlanner.getConfig();
            var polygon = document.getElementById("plan-polygon").value;
            if (!polygon) {
                _toast("No polygon area defined", "warning");
                return;
            }
            fetch(appBase + "/admin/" + planId + "/generate-grid", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({ polygon: polygon, config: config }),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) {
                        waypointMarkers.forEach(function (m) { map.removeLayer(m); });
                        waypointMarkers.length = 0;
                        waypoints.length = 0;
                        routeLayerGroup.clearLayers();
                        resp.waypoints.forEach(function (w) {
                            addWaypoint(L.latLng(w.lat, w.lng), w);
                        });
                        updateRoute();
                        _toast("Generated " + resp.count + " grid waypoints", "success"); if(typeof gtag==="function")gtag("event","demo_grid_generate",{count:resp.count});
                        if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepComplete("grid");
                    } else {
                        _toast("Grid error: " + (resp.error || "Unknown"), "danger");
                    }
                })
                .catch(function () { _toast("Grid generation failed", "danger"); });
        });
    }

    // Oblique planner
    if (typeof ObliquePlanner !== "undefined" && document.getElementById("oblique-planner-panel")) {
        ObliquePlanner.buildPanel("oblique-planner-panel");
        document.getElementById("oblique-planner-panel").addEventListener("click", function (e) {
            var genBtn = e.target.closest("#btn-generate-oblique-grid");
            if (!genBtn) return;
            var config = ObliquePlanner.getConfig();
            var polygon = document.getElementById("plan-polygon").value;
            if (!polygon) {
                _toast("No polygon area defined", "warning");
                return;
            }
            fetch(appBase + "/admin/" + planId + "/generate-oblique-grid", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({ polygon: polygon, config: config }),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) {
                        _replaceWaypoints(resp.waypoints);
                        _toast("Generated " + resp.count + " 3D grid waypoints", "success");
                        if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepComplete("oblique");
                    } else {
                        _toast("Error: " + (resp.error || "Unknown"), "danger");
                    }
                })
                .catch(function () { _toast("3D grid generation failed", "danger"); });
        });
    }

    // Facade planner
    if (typeof FacadePlanner !== "undefined" && document.getElementById("facade-planner-panel")) {
        FacadePlanner.buildPanel("facade-planner-panel");
        var _facadePoints = [];
        var _facadeMarkers = [];

        document.getElementById("facade-planner-panel").addEventListener("click", function (e) {
            var genBtn = e.target.closest("#btn-generate-facade");
            if (!genBtn) return;
            var config = FacadePlanner.getConfig();
            var mode = FacadePlanner.getMode();
            var body = { config: config };

            if (mode === "multi") {
                var polygon = document.getElementById("plan-polygon").value;
                if (!polygon) {
                    _toast("No polygon defined for multi-face scan", "warning");
                    return;
                }
                body.polygon = polygon;
                body.mode = "multi";
            } else {
                if (_facadePoints.length < 2) {
                    _toast("Click two points on the map to define facade line", "warning");
                    return;
                }
                body.face_start = [_facadePoints[0].lat, _facadePoints[0].lng];
                body.face_end = [_facadePoints[1].lat, _facadePoints[1].lng];
            }

            fetch(appBase + "/admin/" + planId + "/generate-facade-scan", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify(body),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) {
                        _replaceWaypoints(resp.waypoints);
                        _clearFacadePoints();
                        _toast("Generated " + resp.count + " facade waypoints", "success");
                        if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepComplete("facade");
                    } else {
                        _toast("Error: " + (resp.error || "Unknown"), "danger");
                    }
                })
                .catch(function () { _toast("Facade scan failed", "danger"); });
        });

        function _clearFacadePoints() {
            _facadeMarkers.forEach(function (m) { map.removeLayer(m); });
            _facadeMarkers.length = 0;
            _facadePoints.length = 0;
        }

        // Allow adding facade points by holding Shift+click
        map.on("click", function (e) {
            if (!e.originalEvent.shiftKey) return;
            if (_facadePoints.length >= 2) _clearFacadePoints();
            _facadePoints.push(e.latlng);
            var m = L.marker(e.latlng, {
                icon: L.divIcon({
                    className: "",
                    html: '<div style="background:#dc3545;color:#fff;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;border:2px solid #fff;">F</div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                }),
            }).addTo(map).bindTooltip("Facade " + _facadePoints.length, { direction: "top" });
            _facadeMarkers.push(m);
            if (_facadePoints.length === 2) {
                L.polyline([_facadePoints[0], _facadePoints[1]], {
                    color: "#dc3545", weight: 2, dashArray: "5,5",
                }).addTo(map);
            }
        });
    }

    // Coverage analysis
    if (typeof CoverageHeatmap !== "undefined") {
        CoverageHeatmap.init(map);
    }

    var coverageBtn = document.getElementById("btn-run-coverage");
    if (coverageBtn) {
        coverageBtn.addEventListener("click", function () {
            if (waypoints.length === 0) {
                _toast("Add waypoints first", "warning");
                return;
            }
            fetch(appBase + "/admin/" + planId + "/coverage-analysis", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({}),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) {
                        if (typeof CoverageHeatmap !== "undefined") {
                            CoverageHeatmap.render(resp);
                            if (!CoverageHeatmap.isVisible()) CoverageHeatmap.toggle();
                            CoverageHeatmap.renderStats("coverage-panel", resp.stats);
                        }
                        _toast("Coverage analysis complete", "success"); if(typeof gtag==="function")gtag("event","demo_coverage_analysis");
                        if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepComplete("coverage");
                    } else {
                        _toast("Error: " + (resp.error || "Unknown"), "danger");
                    }
                })
                .catch(function () { _toast("Coverage analysis failed", "danger"); });
        });
    }

    // Coverage heatmap toggle
    var coverageToggleBtn = document.getElementById("btn-coverage-toggle");
    if (coverageToggleBtn) {
        coverageToggleBtn.addEventListener("click", function () {
            if (typeof CoverageHeatmap !== "undefined") {
                var visible = CoverageHeatmap.toggle();
                coverageToggleBtn.classList.toggle("active", visible);
                coverageToggleBtn.classList.toggle("btn-outline-warning", !visible);
                coverageToggleBtn.classList.toggle("btn-warning", visible);
            }
        });
    }

    // Quality report
    var qualityBtn = document.getElementById("btn-run-quality");
    if (qualityBtn) {
        qualityBtn.addEventListener("click", function () {
            if (waypoints.length === 0) {
                _toast("Add waypoints first", "warning");
                return;
            }
            fetch(appBase + "/admin/" + planId + "/quality-report", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({}),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success && typeof QualityReport !== "undefined") {
                        QualityReport.render("quality-panel", resp);
                        if (typeof WorkflowManager !== "undefined") WorkflowManager.markStepComplete("quality");
                    }
                })
                .catch(function () { _toast("Quality report failed", "danger"); });
        });
    }

    // 3D Preview
    var threeDBtn = document.getElementById("btn-load-3d");
    if (threeDBtn) {
        threeDBtn.addEventListener("click", function () {
            if (waypoints.length === 0) {
                _toast("Add waypoints first", "warning");
                return;
            }
            var container = document.getElementById("three-preview-container");
            if (container) container.style.display = "block";

            fetch(appBase + "/admin/" + planId + "/terrain-mesh")
                .then(function (r) { return r.json(); })
                .then(function (terrainData) {
                    if (typeof ThreePreview !== "undefined") {
                        ThreePreview.init("three-preview-canvas");
                        ThreePreview.renderTerrain(terrainData);
                        ThreePreview.renderFlightPath(waypoints);
                        _toast("3D preview loaded", "success");
                    }
                })
                .catch(function () { _toast("3D preview failed", "danger"); });
        });
    }

    // Helper to replace all waypoints (used by new generators)
    function _replaceWaypoints(newWps) {
        waypointMarkers.forEach(function (m) { map.removeLayer(m); });
        waypointMarkers.length = 0;
        waypoints.length = 0;
        routeLayerGroup.clearLayers();
        selectedIndex = -1;
        newWps.forEach(function (w) {
            addWaypoint(L.latLng(w.lat, w.lng), w);
        });
        updateRoute();
        mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
    }

    // Load elevation data
    var elevBtn = document.getElementById("btn-load-elevation");
    if (elevBtn) {
        elevBtn.addEventListener("click", function () {
            if (waypoints.length === 0) {
                _toast("Add waypoints first", "warning");
                return;
            }
            fetch(appBase + "/admin/" + planId + "/elevation", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({}),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success && typeof ElevationProfile !== "undefined") {
                        var chartData = [];
                        var totalDist = 0;
                        resp.waypoints.forEach(function (w, i) {
                            if (i > 0) {
                                var from = L.latLng(resp.waypoints[i - 1].lat, resp.waypoints[i - 1].lng);
                                var to = L.latLng(w.lat, w.lng);
                                totalDist += from.distanceTo(to);
                            }
                            chartData.push({
                                distance_m: totalDist,
                                ground_elevation_m: w.ground_elevation_m || 0,
                                flight_altitude_amsl: w.amsl_m || w.altitude_m,
                                is_waypoint: true,
                            });
                        });
                        var placeholder = document.getElementById("elevation-placeholder");
                        if (placeholder) placeholder.style.display = "none";
                        ElevationProfile.render("elevation-canvas", chartData);
                    }
                })
                .catch(function () { _toast("Failed to load elevation", "danger"); });
        });
    }

    // Share link
    var shareBtn = document.getElementById("btn-share");
    if (shareBtn) {
        shareBtn.addEventListener("click", function () {
            fetch(appBase + "/admin/" + planId + "/share", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({ expires_days: 30 }),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) {
                        var url = resp.url;
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(url);
                            _toast("Share link copied to clipboard!", "success");
                        } else {
                            prompt("Share this link:", url);
                        }
                    }
                })
                .catch(function () { _toast("Failed to create share link", "danger"); });
        });
    }

    // Airspace layer
    if (typeof AirspaceLayer !== "undefined") {
        AirspaceLayer.init(map);
        var airspaceBtn = document.getElementById("btn-airspace");
        var _airspaceLoaded = false;
        if (airspaceBtn) {
            airspaceBtn.addEventListener("click", function () {
                if (!_airspaceLoaded) {
                    fetch(appBase + "/admin/" + planId + "/airspace")
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            AirspaceLayer.loadData(data.geojson);
                            _airspaceLoaded = true;
                            AirspaceLayer.toggle();
                            airspaceBtn.classList.toggle("active", AirspaceLayer.isVisible());
                            if (data.violations && Object.keys(data.violations).length > 0) {
                                _toast("Warning: waypoints in restricted airspace!", "danger");
                            }
                        });
                } else {
                    var visible = AirspaceLayer.toggle();
                    airspaceBtn.classList.toggle("active", visible);
                }
            });
        }
    }

    // Weather
    var weatherBtn = document.getElementById("btn-load-weather");
    if (weatherBtn) {
        weatherBtn.addEventListener("click", function () {
            fetch(appBase + "/admin/" + planId + "/weather")
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (typeof WeatherPanel !== "undefined") {
                        WeatherPanel.render("weather-panel", data); if(typeof gtag==="function")gtag("event","demo_weather_check",{source:"admin"});
                    }
                })
                .catch(function () { _toast("Weather load failed", "danger"); });
        });
    }

    // Terrain follow
    var terrainBtn = document.getElementById("btn-terrain-follow");
    if (terrainBtn) {
        terrainBtn.addEventListener("click", function () {
            if (waypoints.length === 0) {
                _toast("Add waypoints first", "warning");
                return;
            }
            var agl = prompt("Target AGL (metres above ground):", "30");
            if (agl === null) return;
            fetch(appBase + "/admin/" + planId + "/terrain-follow", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRFToken": csrfToken },
                body: JSON.stringify({ target_agl_m: parseFloat(agl) || 30 }),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) {
                        waypointMarkers.forEach(function (m) { map.removeLayer(m); });
                        waypointMarkers.length = 0;
                        waypoints.length = 0;
                        routeLayerGroup.clearLayers();
                        resp.waypoints.forEach(function (w) {
                            addWaypoint(L.latLng(w.lat, w.lng), w);
                        });
                        updateRoute();
                        _toast("Terrain follow applied (" + resp.count + " points)", "success");
                    } else {
                        _toast("Error: " + (resp.error || "Unknown"), "danger");
                    }
                })
                .catch(function () { _toast("Terrain follow failed", "danger"); });
        });
    }

    // Import KMZ
    var importInput = document.getElementById("import-kmz-file");
    if (importInput) {
        importInput.addEventListener("change", function () {
            if (!importInput.files.length) return;
            var formData = new FormData();
            formData.append("kmz_file", importInput.files[0]);
            fetch(appBase + "/admin/" + planId + "/import-kmz", {
                method: "POST",
                headers: { "X-CSRFToken": csrfToken },
                body: formData,
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) {
                        // Clear existing and reload
                        waypointMarkers.forEach(function (m) { map.removeLayer(m); });
                        waypointMarkers.length = 0;
                        waypoints.length = 0;
                        routeLayerGroup.clearLayers();
                        resp.waypoints.forEach(function (w) {
                            addWaypoint(L.latLng(w.lat, w.lng), w);
                        });
                        updateRoute();
                        if (resp.drone_model) {
                            var ds = document.getElementById("drone-model-select");
                            if (ds) ds.value = resp.drone_model;
                        }
                        _toast("Imported " + resp.count + " waypoints from KMZ", "success");
                    } else {
                        _toast("Import error: " + (resp.error || "Unknown"), "danger");
                    }
                })
                .catch(function () { _toast("Import failed", "danger"); });
            importInput.value = "";
        });
    }

    // Save drone model
    var droneSelect = document.getElementById("drone-model-select");
    if (droneSelect) {
        droneSelect.addEventListener("change", function () {
            fetch(appBase + "/admin/" + planId + "/drone-model", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRFToken": csrfToken,
                },
                body: JSON.stringify({ drone_model: droneSelect.value }),
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.success) _toast("Drone model saved", "success");
                });
        });
    }

    // Workflow Manager init
    if (typeof WorkflowManager !== "undefined") {
        var jobType = (document.getElementById("plan-job-type") || {}).value || "";
        var hasPolygon = !!document.getElementById("plan-polygon").value;
        WorkflowManager.init(planId, jobType, hasPolygon);
    }

    // Save notes
    document.getElementById("btn-save-notes").addEventListener("click", function () {
        var notes = document.getElementById("admin-notes").value;
        fetch(appBase + "/admin/" + planId + "/notes", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRFToken": csrfToken,
            },
            body: JSON.stringify({ notes: notes }),
        })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (resp.success) _toast("Notes saved", "success");
            });
    });

    // Toast helper - uses DOM methods, no innerHTML
    function _toast(msg, type) {
        var container = document.querySelector(".flash-messages") || document.querySelector("main") || document.body;
        var div = document.createElement("div");
        div.className = "alert alert-" + type + " alert-dismissible fade show";
        div.textContent = msg;
        var closeBtn = document.createElement("button");
        closeBtn.type = "button";
        closeBtn.className = "btn-close";
        closeBtn.setAttribute("data-bs-dismiss", "alert");
        div.appendChild(closeBtn);
        container.prepend(div);
        setTimeout(function () { div.remove(); }, 3000);
    }

    // Init toolbox and camera viz
    if (typeof MapToolbox !== "undefined") {
        MapToolbox.init(mapEl);
    }
    if (typeof CameraViz !== "undefined") {
        CameraViz.init(map);
    }

    window.WaypointEditor = {
        getMap:             function() { return map; },
        getWaypoints:       function() { return waypoints; },
        getMarkers:         function() { return waypointMarkers; },
        getSelectedIndex:   function() { return selectedIndex; },
        addWaypoint:        function(ll, d) { addWaypoint(ll, d); updateRoute(); },
        deleteWaypoint:     deleteWaypoint,
        insertWaypoint:     insertWaypoint,
        updateWaypointField: function(idx, field, val) {
            waypoints[idx][field] = val;
            if (field === "altitude_m" || field === "action_type") {
                waypointMarkers[idx].setIcon(_waypointIcon(idx, waypoints[idx]));
            }
            updateRoute(); updateWaypointList();
            mapEl.dispatchEvent(new CustomEvent("waypoints-changed"));
        },
        selectWaypoint:     selectWaypoint,
        updateRoute:        updateRoute,
        setMode:            function(m) {
            _editorMode = m;
            mapEl.className = mapEl.className.replace(/map-mode-\w+/g, "").trim();
            mapEl.classList.add("map-mode-" + m);
            if (m !== "insert" && _insertHighlight) {
                map.removeLayer(_insertHighlight);
                _insertHighlight = null;
            }
        },
        getMode:            function() { return _editorMode; },
    };
})();
