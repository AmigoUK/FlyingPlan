/**
 * Flight path manipulation tools: reverse, straighten, offset.
 * Operates on waypoint arrays client-side.
 */
var PathTools = (function () {
    "use strict";

    function reversePath(waypoints) {
        if (!waypoints || waypoints.length < 2) return waypoints;
        var reversed = waypoints.slice().reverse();
        for (var i = 0; i < reversed.length; i++) {
            reversed[i] = Object.assign({}, reversed[i]);
            reversed[i].index = i;
            // Recalculate heading (reverse direction)
            if (reversed[i].heading_deg !== null && reversed[i].heading_deg !== undefined) {
                reversed[i].heading_deg = (reversed[i].heading_deg + 180) % 360;
            }
        }
        return reversed;
    }

    function straightenPath(waypoints) {
        if (!waypoints || waypoints.length < 2) return waypoints;
        var first = Object.assign({}, waypoints[0], { index: 0 });
        var last = Object.assign({}, waypoints[waypoints.length - 1], { index: 1 });
        return [first, last];
    }

    function offsetPath(waypoints, offsetDistM, side) {
        if (!waypoints || waypoints.length < 2) return waypoints;
        // side: 'left' or 'right'
        var sign = side === "left" ? -1 : 1;
        var result = [];

        for (var i = 0; i < waypoints.length; i++) {
            var wp = Object.assign({}, waypoints[i]);
            var bearing;

            if (i < waypoints.length - 1) {
                bearing = _bearing(wp.lat, wp.lng, waypoints[i + 1].lat, waypoints[i + 1].lng);
            } else {
                bearing = _bearing(waypoints[i - 1].lat, waypoints[i - 1].lng, wp.lat, wp.lng);
            }

            // Perpendicular offset
            var perpBearing = bearing + sign * (Math.PI / 2);
            var dlat = (offsetDistM * Math.cos(perpBearing)) / 110540;
            var dlng = (offsetDistM * Math.sin(perpBearing)) / (111320 * Math.cos(wp.lat * Math.PI / 180));

            wp.lat = wp.lat + dlat;
            wp.lng = wp.lng + dlng;
            wp.index = i;
            result.push(wp);
        }
        return result;
    }

    function _bearing(lat1, lng1, lat2, lng2) {
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var lat1R = lat1 * Math.PI / 180;
        var lat2R = lat2 * Math.PI / 180;
        var x = Math.sin(dLng) * Math.cos(lat2R);
        var y = Math.cos(lat1R) * Math.sin(lat2R) - Math.sin(lat1R) * Math.cos(lat2R) * Math.cos(dLng);
        return Math.atan2(x, y);
    }

    function buildToolbar(containerId) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.textContent = "";

        var buttons = [
            { id: "btn-path-reverse", icon: "bi-arrow-left-right", title: "Reverse", text: "Reverse" },
            { id: "btn-path-straighten", icon: "bi-slash-lg", title: "Straighten (keep first/last)", text: "Straighten" },
            { id: "btn-path-offset-left", icon: "bi-box-arrow-left", title: "Offset left", text: "Offset L" },
            { id: "btn-path-offset-right", icon: "bi-box-arrow-right", title: "Offset right", text: "Offset R" },
        ];

        buttons.forEach(function (b) {
            var btn = document.createElement("button");
            btn.className = "btn btn-sm btn-outline-secondary me-1 mb-1";
            btn.id = b.id;
            btn.title = b.title;
            btn.type = "button";
            var icon = document.createElement("i");
            icon.className = "bi " + b.icon + " me-1";
            btn.appendChild(icon);
            btn.appendChild(document.createTextNode(b.text));
            el.appendChild(btn);
        });
    }

    return {
        reversePath: reversePath,
        straightenPath: straightenPath,
        offsetPath: offsetPath,
        buildToolbar: buildToolbar,
    };
})();
