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

    // Customer polygon
    var polygonRaw = document.getElementById("plan-polygon").value;
    if (polygonRaw) {
        try {
            var coords = JSON.parse(polygonRaw);
            L.polygon(coords, { color: "#0d6efd", fillOpacity: 0.1, interactive: false }).addTo(map);
        } catch (e) { /* ignore */ }
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

    // Click map to add waypoint
    map.on("click", function (e) {
        if (typeof MapMeasure !== "undefined" && MapMeasure.isRulerActive()) {
            MapMeasure.handleMapClick(e.latlng);
            return;
        }
        addWaypoint(e.latlng, {});
        updateRoute();
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
            icon: _waypointIcon(idx, wp.altitude_m),
        }).addTo(map);

        marker.bindTooltip("WP " + idx, { direction: "top", offset: [0, -15] });

        marker.on("dragend", function () {
            var pos = marker.getLatLng();
            wp.lat = pos.lat;
            wp.lng = pos.lng;
            updateRoute();
            updateWaypointList();
        });

        marker.on("click", function (e) {
            L.DomEvent.stopPropagation(e);
            selectWaypoint(wp.index);
        });

        waypointMarkers.push(marker);
        updateWaypointList();
    }

    function _waypointIcon(idx, altitude) {
        // Color by altitude: green (low) to red (high)
        var ratio = Math.min(altitude / 120, 1);
        var r = Math.round(ratio * 220);
        var g = Math.round((1 - ratio) * 180);
        var color = "rgb(" + r + "," + g + ",50)";

        return L.divIcon({
            className: "",
            html:
                '<div style="background:' + color +
                ';color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.4);">' +
                idx + "</div>",
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        });
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
                // Update marker icon if altitude changed
                if (field === "altitude_m" && waypointMarkers[idx]) {
                    waypointMarkers[idx].setIcon(_waypointIcon(idx, waypoints[idx].altitude_m));
                }
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
        if (!confirm("Delete waypoint " + idx + "?")) return;
        // Remove marker
        map.removeLayer(waypointMarkers[idx]);
        waypointMarkers.splice(idx, 1);
        waypoints.splice(idx, 1);
        // Reindex
        waypoints.forEach(function (w, i) {
            w.index = i;
            if (waypointMarkers[i]) {
                waypointMarkers[i].setIcon(_waypointIcon(i, w.altitude_m));
                waypointMarkers[i].setTooltipContent("WP " + i);
            }
        });
        if (selectedIndex >= waypoints.length) selectedIndex = waypoints.length - 1;
        updateRoute();
        updateWaypointList();
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

        fetch("/admin/" + planId + "/waypoints", {
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
                    _toast("Waypoints saved (" + resp.count + " points)", "success");
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
    });

    // Status change
    document.getElementById("status-select").addEventListener("change", function () {
        var self = this;
        fetch("/admin/" + planId + "/status", {
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
            fetch("/admin/" + planId + "/generate-pattern", {
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
                        _toast("Generated " + resp.count + " pattern waypoints", "success");
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
            fetch("/admin/" + planId + "/generate-grid", {
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
                        _toast("Generated " + resp.count + " grid waypoints", "success");
                    } else {
                        _toast("Grid error: " + (resp.error || "Unknown"), "danger");
                    }
                })
                .catch(function () { _toast("Grid generation failed", "danger"); });
        });
    }

    // Load elevation data
    var elevBtn = document.getElementById("btn-load-elevation");
    if (elevBtn) {
        elevBtn.addEventListener("click", function () {
            if (waypoints.length === 0) {
                _toast("Add waypoints first", "warning");
                return;
            }
            fetch("/admin/" + planId + "/elevation", {
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

    // Airspace layer
    if (typeof AirspaceLayer !== "undefined") {
        AirspaceLayer.init(map);
        var airspaceBtn = document.getElementById("btn-airspace");
        var _airspaceLoaded = false;
        if (airspaceBtn) {
            airspaceBtn.addEventListener("click", function () {
                if (!_airspaceLoaded) {
                    fetch("/admin/" + planId + "/airspace")
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
            fetch("/admin/" + planId + "/weather")
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (typeof WeatherPanel !== "undefined") {
                        WeatherPanel.render("weather-panel", data);
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
            fetch("/admin/" + planId + "/terrain-follow", {
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
            fetch("/admin/" + planId + "/import-kmz", {
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
            fetch("/admin/" + planId + "/drone-model", {
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

    // Save notes
    document.getElementById("btn-save-notes").addEventListener("click", function () {
        var notes = document.getElementById("admin-notes").value;
        fetch("/admin/" + planId + "/notes", {
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
        var container = document.querySelector(".flash-messages") || document.querySelector("main");
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
})();
