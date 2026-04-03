/**
 * Elevation profile chart — renders terrain line + flight path on a canvas.
 * Usage: ElevationProfile.render(canvasId, data)
 *   data: array of {distance_m, ground_elevation_m, flight_altitude_amsl}
 */
var ElevationProfile = (function () {
    "use strict";

    var PADDING = { top: 20, right: 20, bottom: 35, left: 50 };
    var DANGER_CLEARANCE_M = 10;

    function render(canvasId, data) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !data || data.length < 2) {
            if (canvas) canvas.style.display = "none";
            return;
        }
        canvas.style.display = "block";
        var ctx = canvas.getContext("2d");
        var w = canvas.width = canvas.parentElement.clientWidth || 600;
        var h = canvas.height = 200;

        var plotW = w - PADDING.left - PADDING.right;
        var plotH = h - PADDING.top - PADDING.bottom;

        // Calculate ranges
        var maxDist = 0, minElev = Infinity, maxElev = -Infinity;
        data.forEach(function (d) {
            if (d.distance_m > maxDist) maxDist = d.distance_m;
            var ge = d.ground_elevation_m || 0;
            var fa = d.flight_altitude_amsl || 0;
            if (ge < minElev) minElev = ge;
            if (fa < minElev) minElev = fa;
            if (ge > maxElev) maxElev = ge;
            if (fa > maxElev) maxElev = fa;
        });

        var elevRange = maxElev - minElev || 1;
        minElev -= elevRange * 0.1;
        maxElev += elevRange * 0.1;
        elevRange = maxElev - minElev;

        function xPos(dist) { return PADDING.left + (dist / (maxDist || 1)) * plotW; }
        function yPos(elev) { return PADDING.top + plotH - ((elev - minElev) / elevRange) * plotH; }

        // Clear
        ctx.clearRect(0, 0, w, h);

        // Terrain fill
        ctx.beginPath();
        ctx.moveTo(xPos(data[0].distance_m), yPos(data[0].ground_elevation_m || 0));
        data.forEach(function (d) {
            ctx.lineTo(xPos(d.distance_m), yPos(d.ground_elevation_m || 0));
        });
        ctx.lineTo(xPos(data[data.length - 1].distance_m), yPos(minElev));
        ctx.lineTo(xPos(data[0].distance_m), yPos(minElev));
        ctx.closePath();
        ctx.fillStyle = "rgba(139, 119, 101, 0.3)";
        ctx.fill();

        // Terrain line
        ctx.beginPath();
        data.forEach(function (d, i) {
            var x = xPos(d.distance_m);
            var y = yPos(d.ground_elevation_m || 0);
            i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        });
        ctx.strokeStyle = "#8B7765";
        ctx.lineWidth = 2;
        ctx.stroke();

        // Flight path
        ctx.beginPath();
        data.forEach(function (d, i) {
            var x = xPos(d.distance_m);
            var y = yPos(d.flight_altitude_amsl || 0);
            i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        });
        ctx.strokeStyle = "#198754";
        ctx.lineWidth = 2;
        ctx.setLineDash([6, 4]);
        ctx.stroke();
        ctx.setLineDash([]);

        // Danger zones (clearance < threshold)
        data.forEach(function (d) {
            var clearance = (d.flight_altitude_amsl || 0) - (d.ground_elevation_m || 0);
            if (clearance < DANGER_CLEARANCE_M) {
                var x = xPos(d.distance_m);
                var y1 = yPos(d.ground_elevation_m || 0);
                var y2 = yPos(d.flight_altitude_amsl || 0);
                if (clearance < 0) {
                    // Below terrain — solid red
                    ctx.fillStyle = "rgba(220, 53, 69, 0.7)";
                    ctx.fillRect(x - 3, y1, 6, y2 - y1);
                } else {
                    // Low clearance — semi-transparent red
                    ctx.fillStyle = "rgba(220, 53, 69, 0.3)";
                    ctx.fillRect(x - 3, y2, 6, y1 - y2);
                }
            }
        });

        // Waypoint dots on flight path
        data.forEach(function (d) {
            if (d.is_waypoint) {
                var x = xPos(d.distance_m);
                var y = yPos(d.flight_altitude_amsl || 0);
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fillStyle = "#198754";
                ctx.fill();
                ctx.strokeStyle = "#fff";
                ctx.lineWidth = 1.5;
                ctx.stroke();
            }
        });

        // Axes
        ctx.strokeStyle = "#ccc";
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(PADDING.left, PADDING.top);
        ctx.lineTo(PADDING.left, h - PADDING.bottom);
        ctx.lineTo(w - PADDING.right, h - PADDING.bottom);
        ctx.stroke();

        // Labels
        ctx.fillStyle = "#666";
        ctx.font = "11px sans-serif";
        ctx.textAlign = "center";

        // X axis
        var xTicks = 5;
        for (var i = 0; i <= xTicks; i++) {
            var dist = (maxDist / xTicks) * i;
            var label = dist < 1000 ? Math.round(dist) + "m" : (dist / 1000).toFixed(1) + "km";
            ctx.fillText(label, xPos(dist), h - 5);
        }

        // Y axis
        ctx.textAlign = "right";
        var yTicks = 4;
        for (var j = 0; j <= yTicks; j++) {
            var elev = minElev + (elevRange / yTicks) * j;
            ctx.fillText(Math.round(elev) + "m", PADDING.left - 5, yPos(elev) + 4);
        }

        // Legend
        ctx.textAlign = "left";
        ctx.fillStyle = "#8B7765";
        ctx.fillRect(w - 150, 5, 10, 10);
        ctx.fillStyle = "#666";
        ctx.fillText("Terrain", w - 135, 14);
        ctx.fillStyle = "#198754";
        ctx.fillRect(w - 150, 20, 10, 10);
        ctx.fillStyle = "#666";
        ctx.fillText("Flight path", w - 135, 29);
    }

    return { render: render };
})();
