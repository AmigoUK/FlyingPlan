/**
 * Facade scanning planner UI panel.
 * Configures standoff distance, column spacing, altitude range.
 */
var FacadePlanner = (function () {
    "use strict";

    function buildPanel(containerId) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = "";

        var info = document.createElement("p");
        info.className = "text-muted small mb-2";
        info.textContent = "Click two points on the map to define the facade line, then generate.";
        el.appendChild(info);

        var fields = [
            { id: "facade-standoff", label: "Standoff Distance (m)", value: 10, min: 3, max: 50 },
            { id: "facade-col-spacing", label: "Column Spacing (m)", value: 5, min: 2, max: 30 },
            { id: "facade-min-alt", label: "Min Altitude (m)", value: 10, min: 5, max: 120 },
            { id: "facade-max-alt", label: "Max Altitude (m)", value: 40, min: 10, max: 120 },
            { id: "facade-alt-step", label: "Altitude Step (m)", value: 5, min: 2, max: 20 },
            { id: "facade-speed", label: "Speed (m/s)", value: 3, min: 1, max: 10, step: 0.5 },
        ];

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
            el.appendChild(group);
        });

        // Mode: single face or multi-face from polygon
        var modeGroup = document.createElement("div");
        modeGroup.className = "mb-2";
        var modeLabel = document.createElement("label");
        modeLabel.className = "form-label small mb-0";
        modeLabel.textContent = "Scan Mode";
        var modeSel = document.createElement("select");
        modeSel.className = "form-select form-select-sm";
        modeSel.id = "facade-mode";
        [
            { v: "single", t: "Single Face (2 map clicks)" },
            { v: "multi", t: "Multi-Face (from polygon)" },
        ].forEach(function (o) {
            var opt = document.createElement("option");
            opt.value = o.v;
            opt.textContent = o.t;
            modeSel.appendChild(opt);
        });
        modeGroup.appendChild(modeLabel);
        modeGroup.appendChild(modeSel);
        el.appendChild(modeGroup);

        // Buttons
        var btnGroup = document.createElement("div");
        btnGroup.className = "d-flex gap-2";

        var scanBtn = document.createElement("button");
        scanBtn.className = "btn btn-sm btn-primary flex-fill";
        scanBtn.id = "btn-generate-facade";
        scanBtn.type = "button";
        var icon1 = document.createElement("i");
        icon1.className = "bi bi-building me-1";
        scanBtn.appendChild(icon1);
        scanBtn.appendChild(document.createTextNode("Generate Facade Scan"));
        btnGroup.appendChild(scanBtn);

        el.appendChild(btnGroup);
    }

    function getConfig() {
        var getValue = function (id, def) {
            var el = document.getElementById(id);
            return el ? parseFloat(el.value) || def : def;
        };

        return {
            standoff_m: getValue("facade-standoff", 10),
            column_spacing_m: getValue("facade-col-spacing", 5),
            min_altitude_m: getValue("facade-min-alt", 10),
            max_altitude_m: getValue("facade-max-alt", 40),
            altitude_step_m: getValue("facade-alt-step", 5),
            speed_ms: getValue("facade-speed", 3),
        };
    }

    function getMode() {
        var el = document.getElementById("facade-mode");
        return el ? el.value : "single";
    }

    return {
        buildPanel: buildPanel,
        getConfig: getConfig,
        getMode: getMode,
    };
})();
