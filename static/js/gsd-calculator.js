/**
 * GSD Calculator panel — interactive with live updates.
 */
var GSDCalculator = (function () {
    "use strict";

    var _tooltips = {
        "gsd-altitude": "Height above ground. Lower = more detail but smaller area covered. 30m is a good starting point.",
        "gsd-overlap": "How much each photo overlaps the next. 70% for standard surveys, 80%+ for 3D reconstruction.",
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

        var fields = [
            { id: "gsd-altitude", label: "Altitude (m)", value: 30, min: 5, max: 120 },
            { id: "gsd-overlap", label: "Overlap (%)", value: 70, min: 0, max: 99 },
        ];

        fields.forEach(function (f) {
            var group = document.createElement("div");
            group.className = "mb-2";
            var label = document.createElement("label");
            label.className = "form-label small mb-0";
            label.textContent = f.label;
            if (_tooltips[f.id]) _addTooltip(label, _tooltips[f.id]);
            var input = document.createElement("input");
            input.type = "number";
            input.className = "form-control form-control-sm";
            input.id = f.id;
            input.value = f.value;
            input.min = f.min;
            input.max = f.max;
            group.appendChild(label);
            group.appendChild(input);
            el.appendChild(group);
        });

        var btn = document.createElement("button");
        btn.className = "btn btn-sm btn-outline-primary w-100 mb-2";
        btn.id = "btn-calc-gsd";
        btn.type = "button";
        btn.textContent = "Calculate GSD";
        el.appendChild(btn);

        var resultsDiv = document.createElement("div");
        resultsDiv.id = "gsd-results";
        el.appendChild(resultsDiv);
    }

    function renderResults(containerId, data) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = "";

        var items = [
            { label: "GSD", value: data.gsd_cm_per_px + " cm/px", bold: true },
            { label: "Quality", value: data.quality_tier },
            { label: "Footprint", value: data.footprint_width_m + " x " + data.footprint_height_m + " m" },
            { label: "Line Spacing", value: data.line_spacing_m + " m" },
            { label: "Photo Interval", value: data.photo_interval_m + " m" },
        ];

        if (data.estimated_photos) {
            items.push({ label: "Est. Photos", value: data.estimated_photos });
            items.push({ label: "Est. Time", value: data.estimated_flight_time_min + " min" });
            items.push({ label: "Battery", value: data.estimated_battery_pct + "% (" + data.batteries_needed + " needed)" });
        }

        items.forEach(function (item) {
            var row = document.createElement("div");
            row.className = "d-flex justify-content-between small" + (item.bold ? " fw-bold" : "");
            var lbl = document.createElement("span");
            lbl.className = "text-muted";
            lbl.textContent = item.label;
            var val = document.createElement("span");
            val.textContent = item.value;
            row.appendChild(lbl);
            row.appendChild(val);
            el.appendChild(row);
        });
    }

    return {
        buildPanel: buildPanel,
        renderResults: renderResults,
    };
})();
