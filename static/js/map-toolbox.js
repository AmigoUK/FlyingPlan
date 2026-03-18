// Map Toolbox: floating toolbar for waypoint editor modes
(function () {
    "use strict";

    var MODES = [
        { id: "pointer", icon: "bi-cursor", title: "Select / Move waypoints" },
        { id: "add", icon: "bi-plus-circle", title: "Click map to add waypoint" },
        { id: "insert", icon: "bi-node-plus", title: "Click route segment to insert" },
        { id: "delete", icon: "bi-dash-circle", title: "Click waypoint to delete" },
    ];

    var _container = null;
    var _buttons = {};
    var _activeMode = "add";

    function init(mapContainerEl) {
        var cardBody = mapContainerEl.closest(".card-body");
        if (!cardBody) return;

        cardBody.style.position = "relative";

        _container = document.createElement("div");
        _container.className = "map-toolbox";

        MODES.forEach(function (mode) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "map-toolbox-btn" + (mode.id === _activeMode ? " active" : "");
            btn.title = mode.title;
            btn.dataset.mode = mode.id;

            var icon = document.createElement("i");
            icon.className = "bi " + mode.icon;
            btn.appendChild(icon);

            btn.addEventListener("click", function () {
                _setMode(mode.id);
            });

            _buttons[mode.id] = btn;
            _container.appendChild(btn);
        });

        cardBody.appendChild(_container);
    }

    function _setMode(mode) {
        _activeMode = mode;

        // Deactivate ruler if active
        if (typeof MapMeasure !== "undefined" && MapMeasure.isRulerActive()) {
            MapMeasure.toggleRuler();
            var rulerBtn = document.getElementById("btn-ruler");
            if (rulerBtn) {
                rulerBtn.classList.remove("active", "btn-info");
                rulerBtn.classList.add("btn-outline-info");
            }
        }

        // Update button styles
        Object.keys(_buttons).forEach(function (key) {
            _buttons[key].classList.toggle("active", key === mode);
        });

        // Set mode on editor
        if (window.WaypointEditor) {
            window.WaypointEditor.setMode(mode);
        }
    }

    function getMode() {
        return _activeMode;
    }

    window.MapToolbox = {
        init: init,
        getMode: getMode,
    };
})();
