/**
 * Photogrammetry quality report UI panel.
 * Shows traffic-light indicators for each metric with recommendations.
 */
var QualityReport = (function () {
    "use strict";

    function render(containerId, data) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = "";

        if (!data || !data.metrics) {
            var p = document.createElement("p");
            p.className = "text-muted small";
            p.textContent = "No quality data available.";
            el.appendChild(p);
            return;
        }

        // Overall rating badge
        var overallDiv = document.createElement("div");
        overallDiv.className = "d-flex align-items-center mb-2";
        var overallLabel = document.createElement("span");
        overallLabel.className = "me-2 small";
        overallLabel.textContent = "Overall Quality:";
        var overallBadge = document.createElement("span");
        overallBadge.className = "badge " + _badgeClass(data.overall_rating);
        overallBadge.textContent = data.overall_rating === "green" ? "Good" :
            data.overall_rating === "yellow" ? "Fair" : "Poor";
        overallDiv.appendChild(overallLabel);
        overallDiv.appendChild(overallBadge);
        el.appendChild(overallDiv);

        // Metrics table
        var metricsDiv = document.createElement("div");
        metricsDiv.className = "mb-2";

        var metricKeys = Object.keys(data.metrics);
        metricKeys.forEach(function (key) {
            var metric = data.metrics[key];
            var row = document.createElement("div");
            row.className = "d-flex justify-content-between align-items-center py-1 border-bottom";

            var labelSpan = document.createElement("span");
            labelSpan.className = "small";
            labelSpan.textContent = metric.label;

            var valueDiv = document.createElement("div");
            valueDiv.className = "d-flex align-items-center gap-1";

            var dot = document.createElement("span");
            dot.style.width = "10px";
            dot.style.height = "10px";
            dot.style.borderRadius = "50%";
            dot.style.display = "inline-block";
            dot.style.backgroundColor = _dotColor(metric.rating);

            var valSpan = document.createElement("strong");
            valSpan.className = "small";
            valSpan.textContent = metric.value;

            valueDiv.appendChild(dot);
            valueDiv.appendChild(valSpan);
            row.appendChild(labelSpan);
            row.appendChild(valueDiv);
            metricsDiv.appendChild(row);
        });

        el.appendChild(metricsDiv);

        // Recommendations
        if (data.recommendations && data.recommendations.length > 0) {
            var recHeader = document.createElement("p");
            recHeader.className = "small mb-1";
            var recBold = document.createElement("strong");
            recBold.textContent = "Recommendations:";
            recHeader.appendChild(recBold);
            el.appendChild(recHeader);

            var recList = document.createElement("ul");
            recList.className = "small mb-0 ps-3";
            data.recommendations.forEach(function (rec) {
                var li = document.createElement("li");
                li.textContent = rec;
                recList.appendChild(li);
            });
            el.appendChild(recList);
        }
    }

    function _badgeClass(rating) {
        if (rating === "green") return "bg-success";
        if (rating === "yellow") return "bg-warning text-dark";
        return "bg-danger";
    }

    function _dotColor(rating) {
        if (rating === "green") return "#28a745";
        if (rating === "yellow") return "#ffc107";
        return "#dc3545";
    }

    return {
        render: render,
    };
})();
