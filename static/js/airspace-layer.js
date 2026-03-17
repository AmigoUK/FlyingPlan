/**
 * Airspace restriction layer for Leaflet maps.
 * Adds colour-coded airspace polygons/circles with toggle control.
 */
var AirspaceLayer = (function () {
    "use strict";

    var _map = null;
    var _layerGroup = null;
    var _visible = false;
    var _colors = {
        prohibited: "#dc3545",
        FRZ: "#dc3545",
        controlled: "#fd7e14",
        CTR: "#fd7e14",
        advisory: "#ffc107",
        danger: "#dc3545",
    };

    function init(map) {
        _map = map;
        _layerGroup = L.layerGroup();
    }

    function toggle() {
        _visible = !_visible;
        if (_visible) {
            _layerGroup.addTo(_map);
        } else {
            _map.removeLayer(_layerGroup);
        }
        return _visible;
    }

    function isVisible() {
        return _visible;
    }

    function loadData(geojsonData) {
        if (!_map || !_layerGroup) return;
        _layerGroup.clearLayers();

        var features = geojsonData.features || [];
        features.forEach(function (feature) {
            var props = feature.properties || {};
            var geom = feature.geometry || {};
            var color = _colors[props.type] || _colors[props["class"]] || "#6c757d";
            var name = props.name || "Airspace";
            var tooltipText = name + " (" + (props.type || "") + ")";

            if (geom.type === "Polygon") {
                var coords = geom.coordinates[0].map(function (c) {
                    return [c[1], c[0]]; // GeoJSON is [lng, lat], Leaflet is [lat, lng]
                });
                L.polygon(coords, {
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.15,
                    weight: 2,
                    dashArray: "5, 5",
                }).addTo(_layerGroup).bindTooltip(tooltipText);
            } else if (geom.type === "Point" && props.radius_m) {
                L.circle([geom.coordinates[1], geom.coordinates[0]], {
                    radius: props.radius_m,
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.15,
                    weight: 2,
                    dashArray: "5, 5",
                }).addTo(_layerGroup).bindTooltip(tooltipText);
            }
        });
    }

    return {
        init: init,
        toggle: toggle,
        isVisible: isVisible,
        loadData: loadData,
    };
})();
