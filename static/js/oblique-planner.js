/**
 * Oblique grid planner UI panel for 3D photogrammetry missions.
 * Provides capture mode selection, gimbal pitch control, and heading mode.
 */
var ObliquePlanner = (function () {
    "use strict";

    var _tooltips = {
        "oblique-capture-mode": "Nadir = camera straight down (2D maps). Oblique = angled camera (adds depth). Double Grid = industry standard for 3D models. Multi-Angle = highest quality 3D (5 passes).",
        "oblique-spacing": "Distance between parallel flight lines. Smaller spacing = more overlap but longer flight time. Use GSD calculator to find ideal spacing.",
        "oblique-angle": "Compass direction of flight lines. 0\u00b0 = North-South, 90\u00b0 = East-West. Align with the longest edge of your survey area.",
        "oblique-altitude": "Flight altitude in metres above ground.",
        "oblique-speed": "Drone ground speed during the mission. Slower = sharper photos but longer battery use.",
        "oblique-gimbal": "Camera angle from horizontal. -45\u00b0 is standard for oblique capture. More negative = looking more downward.",
        "oblique-heading-mode": "Along Track: camera faces flight direction. Fixed: camera maintains constant compass heading.",
    };

    function _addTooltip(label, tipText) {
        var icon = document.createElement("i");
        icon.className = "bi bi-question-circle-fill text-muted ms-1 fp-tooltip-icon";
        icon.setAttribute("data-bs-toggle", "tooltip");
        icon.setAttribute("data-bs-placement", "top");
        icon.title = tipText;
        label.appendChild(icon);
    }

    function buildPanel(containerId) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = "";

        // Capture mode selector
        var modeGroup = document.createElement("div");
        modeGroup.className = "mb-2";
        var modeLabel = document.createElement("label");
        modeLabel.className = "form-label small mb-0";
        modeLabel.textContent = "Capture Mode";
        if (_tooltips["oblique-capture-mode"]) _addTooltip(modeLabel, _tooltips["oblique-capture-mode"]);
        var modeSel = document.createElement("select");
        modeSel.className = "form-select form-select-sm";
        modeSel.id = "oblique-capture-mode";
        [
            { v: "nadir", t: "Nadir (straight down)" },
            { v: "oblique", t: "Oblique (angled camera)" },
            { v: "double_grid", t: "Double Grid (nadir + oblique)" },
            { v: "multi_angle", t: "Multi-Angle (5 passes)" },
        ].forEach(function (o) {
            var opt = document.createElement("option");
            opt.value = o.v;
            opt.textContent = o.t;
            if (o.v === "double_grid") opt.selected = true;
            modeSel.appendChild(opt);
        });
        modeGroup.appendChild(modeLabel);
        modeGroup.appendChild(modeSel);
        el.appendChild(modeGroup);

        // Config container
        var configDiv = document.createElement("div");
        configDiv.id = "oblique-config";
        el.appendChild(configDiv);

        // Generate button
        var btn = document.createElement("button");
        btn.className = "btn btn-sm btn-primary w-100 mt-2";
        btn.id = "btn-generate-oblique-grid";
        btn.type = "button";
        var icon = document.createElement("i");
        icon.className = "bi bi-grid-3x3-gap me-1";
        btn.appendChild(icon);
        btn.appendChild(document.createTextNode("Generate 3D Grid"));
        el.appendChild(btn);

        _renderConfig(modeSel.value);
        modeSel.addEventListener("change", function () {
            _renderConfig(modeSel.value);
        });
    }

    function _renderConfig(mode) {
        var div = document.getElementById("oblique-config");
        if (!div) return;
        div.textContent = "";

        var fields = [
            { id: "oblique-spacing", label: "Line Spacing (m)", value: 20, min: 5, max: 100 },
            { id: "oblique-angle", label: "Grid Angle (deg)", value: 0, min: 0, max: 360 },
            { id: "oblique-altitude", label: "Altitude (m)", value: 50, min: 10, max: 120 },
            { id: "oblique-speed", label: "Speed (m/s)", value: 5, min: 1, max: 15, step: 0.5 },
        ];

        if (mode !== "nadir") {
            fields.push({ id: "oblique-gimbal", label: "Oblique Pitch (deg)", value: -45, min: -80, max: -15 });

            // Heading mode
            var headGroup = document.createElement("div");
            headGroup.className = "mb-1";
            var headLabel = document.createElement("label");
            headLabel.className = "form-label small mb-0";
            headLabel.textContent = "Heading Mode";
            if (_tooltips["oblique-heading-mode"]) _addTooltip(headLabel, _tooltips["oblique-heading-mode"]);
            var headSel = document.createElement("select");
            headSel.className = "form-select form-select-sm";
            headSel.id = "oblique-heading-mode";
            [
                { v: "along_track", t: "Along Track" },
                { v: "fixed", t: "Fixed Heading" },
            ].forEach(function (o) {
                var opt = document.createElement("option");
                opt.value = o.v;
                opt.textContent = o.t;
                headSel.appendChild(opt);
            });
            headGroup.appendChild(headLabel);
            headGroup.appendChild(headSel);

            // Render numeric fields first, then heading mode
            fields.forEach(function (f) {
                div.appendChild(_createField(f));
            });
            div.appendChild(headGroup);
            return;
        }

        fields.forEach(function (f) {
            div.appendChild(_createField(f));
        });
    }

    function _createField(f) {
        var group = document.createElement("div");
        group.className = "mb-1";
        var label = document.createElement("label");
        label.className = "form-label small mb-0";
        label.textContent = f.label;
        if (_tooltips[f.id]) _addTooltip(label, _tooltips[f.id]);
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
        return group;
    }

    function getConfig() {
        var getValue = function (id, def) {
            var el = document.getElementById(id);
            return el ? parseFloat(el.value) || def : def;
        };
        var getVal = function (id, def) {
            var el = document.getElementById(id);
            return el ? el.value : def;
        };

        return {
            capture_mode: getVal("oblique-capture-mode", "double_grid"),
            spacing_m: getValue("oblique-spacing", 20),
            angle_deg: getValue("oblique-angle", 0),
            altitude_m: getValue("oblique-altitude", 50),
            speed_ms: getValue("oblique-speed", 5),
            gimbal_pitch_deg: getValue("oblique-gimbal", -45),
            heading_mode: getVal("oblique-heading-mode", "along_track"),
        };
    }

    return {
        buildPanel: buildPanel,
        getConfig: getConfig,
    };
})();
