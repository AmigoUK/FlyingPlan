/**
 * Mission patterns UI panel: orbit, spiral, cable cam.
 */
var MissionPatterns = (function () {
    "use strict";

    function buildPanel(containerId) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = "";

        // Pattern selector
        var selGroup = document.createElement("div");
        selGroup.className = "mb-2";
        var selLabel = document.createElement("label");
        selLabel.className = "form-label small mb-0";
        selLabel.textContent = "Pattern Type";
        var sel = document.createElement("select");
        sel.className = "form-select form-select-sm";
        sel.id = "pattern-type";
        [
            { v: "orbit", t: "Orbit (circle around point)" },
            { v: "spiral", t: "Spiral (ascending circle)" },
            { v: "cable_cam", t: "Cable Cam (linear path)" },
            { v: "multi_orbit", t: "Multi-Altitude Orbit (stacked)" },
        ].forEach(function (o) {
            var opt = document.createElement("option");
            opt.value = o.v;
            opt.textContent = o.t;
            sel.appendChild(opt);
        });
        selGroup.appendChild(selLabel);
        selGroup.appendChild(sel);
        el.appendChild(selGroup);

        // Config inputs container
        var configDiv = document.createElement("div");
        configDiv.id = "pattern-config";
        el.appendChild(configDiv);

        // Info
        var info = document.createElement("p");
        info.className = "text-muted small mb-2";
        info.id = "pattern-info";
        info.textContent = "Click on the map to set the orbit center point.";
        el.appendChild(info);

        // Generate button
        var btn = document.createElement("button");
        btn.className = "btn btn-sm btn-primary w-100";
        btn.id = "btn-generate-pattern";
        btn.type = "button";
        var icon = document.createElement("i");
        icon.className = "bi bi-bullseye me-1";
        btn.appendChild(icon);
        btn.appendChild(document.createTextNode("Generate Pattern"));
        el.appendChild(btn);

        _renderConfig("orbit");
        sel.addEventListener("change", function () { _renderConfig(sel.value); });
    }

    function _renderConfig(patternType) {
        var div = document.getElementById("pattern-config");
        var info = document.getElementById("pattern-info");
        if (!div) return;
        div.textContent = "";

        var fields = [];
        if (patternType === "orbit") {
            fields = [
                { id: "pat-radius", label: "Radius (m)", value: 30, min: 5, max: 500 },
                { id: "pat-altitude", label: "Altitude (m)", value: 30, min: 5, max: 120 },
                { id: "pat-points", label: "Points", value: 12, min: 4, max: 72 },
                { id: "pat-speed", label: "Speed (m/s)", value: 5, min: 1, max: 15, step: 0.5 },
            ];
            if (info) info.textContent = "Click on the map to set the orbit center point.";
        } else if (patternType === "spiral") {
            fields = [
                { id: "pat-radius", label: "Radius (m)", value: 30, min: 5, max: 500 },
                { id: "pat-start-alt", label: "Start Alt (m)", value: 20, min: 5, max: 120 },
                { id: "pat-end-alt", label: "End Alt (m)", value: 60, min: 5, max: 120 },
                { id: "pat-revolutions", label: "Revolutions", value: 3, min: 1, max: 10 },
                { id: "pat-speed", label: "Speed (m/s)", value: 4, min: 1, max: 15, step: 0.5 },
            ];
            if (info) info.textContent = "Click on the map to set the spiral center point.";
        } else if (patternType === "multi_orbit") {
            fields = [
                { id: "pat-radius", label: "Radius (m)", value: 30, min: 5, max: 500 },
                { id: "pat-start-alt", label: "Min Alt (m)", value: 15, min: 5, max: 120 },
                { id: "pat-end-alt", label: "Max Alt (m)", value: 60, min: 10, max: 120 },
                { id: "pat-alt-step", label: "Alt Step (m)", value: 15, min: 5, max: 50 },
                { id: "pat-points", label: "Points/Orbit", value: 12, min: 4, max: 72 },
                { id: "pat-speed", label: "Speed (m/s)", value: 5, min: 1, max: 15, step: 0.5 },
            ];
            if (info) info.textContent = "Click on the map to set center. Stacked orbits at multiple altitudes.";
        } else {
            fields = [
                { id: "pat-altitude", label: "Altitude (m)", value: 30, min: 5, max: 120 },
                { id: "pat-points", label: "Points", value: 10, min: 2, max: 50 },
                { id: "pat-speed", label: "Speed (m/s)", value: 3, min: 1, max: 15, step: 0.5 },
            ];
            if (info) info.textContent = "Click on the map twice: first for start, second for end.";
        }

        fields.forEach(function (f) {
            var group = document.createElement("div");
            group.className = "mb-1";
            var label = document.createElement("label");
            label.className = "form-label small mb-0";
            label.textContent = f.label;
            var input = document.createElement("input");
            input.type = "number";
            input.className = "form-control form-control-sm";
            input.id = f.id;
            input.value = f.value;
            if (f.min !== undefined) input.min = f.min;
            if (f.max !== undefined) input.max = f.max;
            input.step = f.step || 1;
            group.appendChild(label);
            group.appendChild(input);
            div.appendChild(group);
        });
    }

    function getConfig() {
        var type = document.getElementById("pattern-type").value;
        var config = { type: type };
        var getValue = function (id, def) {
            var el = document.getElementById(id);
            return el ? parseFloat(el.value) || def : def;
        };

        if (type === "orbit") {
            config.radius_m = getValue("pat-radius", 30);
            config.altitude_m = getValue("pat-altitude", 30);
            config.num_points = getValue("pat-points", 12);
            config.speed_ms = getValue("pat-speed", 5);
        } else if (type === "spiral") {
            config.radius_m = getValue("pat-radius", 30);
            config.start_altitude_m = getValue("pat-start-alt", 20);
            config.end_altitude_m = getValue("pat-end-alt", 60);
            config.num_revolutions = getValue("pat-revolutions", 3);
            config.speed_ms = getValue("pat-speed", 4);
        } else if (type === "multi_orbit") {
            config.radius_m = getValue("pat-radius", 30);
            config.min_altitude_m = getValue("pat-start-alt", 15);
            config.max_altitude_m = getValue("pat-end-alt", 60);
            config.altitude_step_m = getValue("pat-alt-step", 15);
            config.num_points = getValue("pat-points", 12);
            config.speed_ms = getValue("pat-speed", 5);
        } else {
            config.altitude_m = getValue("pat-altitude", 30);
            config.num_points = getValue("pat-points", 10);
            config.speed_ms = getValue("pat-speed", 3);
        }
        return config;
    }

    return {
        buildPanel: buildPanel,
        getConfig: getConfig,
    };
})();
