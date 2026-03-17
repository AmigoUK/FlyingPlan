/**
 * Grid planner UI panel for auto-generating survey grid waypoints.
 * Integrates with map-admin.js via GridPlanner global.
 */
var GridPlanner = (function () {
    "use strict";

    var _panelEl = null;

    function init(panelId) {
        _panelEl = document.getElementById(panelId);
    }

    function buildPanel() {
        if (!_panelEl) return;
        _panelEl.textContent = "";

        var fields = [
            { id: "grid-spacing", label: "Line Spacing (m)", type: "number", value: 20, min: 2, max: 200, step: 1 },
            { id: "grid-angle", label: "Direction (\u00b0 from N)", type: "number", value: 0, min: 0, max: 359, step: 1 },
            { id: "grid-altitude", label: "Altitude (m)", type: "number", value: 30, min: 5, max: 120, step: 1 },
            { id: "grid-speed", label: "Speed (m/s)", type: "number", value: 5, min: 1, max: 15, step: 0.5 },
        ];

        fields.forEach(function (f) {
            var group = document.createElement("div");
            group.className = "mb-2";
            var label = document.createElement("label");
            label.className = "form-label small mb-0";
            label.textContent = f.label;
            label.setAttribute("for", f.id);
            var input = document.createElement("input");
            input.type = f.type;
            input.className = "form-control form-control-sm";
            input.id = f.id;
            input.value = f.value;
            if (f.min !== undefined) input.min = f.min;
            if (f.max !== undefined) input.max = f.max;
            if (f.step) input.step = f.step;
            group.appendChild(label);
            group.appendChild(input);
            _panelEl.appendChild(group);
        });

        // Pattern select
        var patGroup = document.createElement("div");
        patGroup.className = "mb-2";
        var patLabel = document.createElement("label");
        patLabel.className = "form-label small mb-0";
        patLabel.textContent = "Pattern";
        var patSelect = document.createElement("select");
        patSelect.className = "form-select form-select-sm";
        patSelect.id = "grid-pattern";
        [{ v: "parallel", t: "Parallel" }, { v: "crosshatch", t: "Crosshatch" }].forEach(function (o) {
            var opt = document.createElement("option");
            opt.value = o.v;
            opt.textContent = o.t;
            patSelect.appendChild(opt);
        });
        patGroup.appendChild(patLabel);
        patGroup.appendChild(patSelect);
        _panelEl.appendChild(patGroup);

        // Generate button
        var btn = document.createElement("button");
        btn.className = "btn btn-sm btn-primary w-100";
        btn.id = "btn-generate-grid";
        btn.type = "button";
        var icon = document.createElement("i");
        icon.className = "bi bi-grid-3x3 me-1";
        btn.appendChild(icon);
        btn.appendChild(document.createTextNode("Generate Grid"));
        _panelEl.appendChild(btn);
    }

    function getConfig() {
        return {
            spacing_m: parseFloat(document.getElementById("grid-spacing").value) || 20,
            angle_deg: parseFloat(document.getElementById("grid-angle").value) || 0,
            altitude_m: parseFloat(document.getElementById("grid-altitude").value) || 30,
            speed_ms: parseFloat(document.getElementById("grid-speed").value) || 5,
            pattern: document.getElementById("grid-pattern").value || "parallel",
            gimbal_pitch_deg: -90,
            action_type: "takePhoto",
        };
    }

    return {
        init: init,
        buildPanel: buildPanel,
        getConfig: getConfig,
    };
})();
