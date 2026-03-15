// Customer map: pin placement, polygon drawing, POI markers
(function () {
    "use strict";

    const mapEl = document.getElementById("customer-map");
    if (!mapEl) return;

    // Default to Birmingham, UK — overridden by geolocation if available
    const FALLBACK = [52.4862, -1.8904]; // Birmingham, UK
    const map = L.map("customer-map").setView(FALLBACK, 13);

    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                map.setView([pos.coords.latitude, pos.coords.longitude], 13);
            },
            function () { /* denied/error — keep Birmingham */ },
            { timeout: 5000 }
        );
    }

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    // State
    let mainPin = null;
    let drawnPolygon = null;
    const pois = [];

    // Leaflet Draw
    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    const drawControl = new L.Control.Draw({
        draw: {
            polyline: false,
            circle: false,
            circlemarker: false,
            marker: false,
            polygon: {
                shapeOptions: { color: "#0d6efd", fillOpacity: 0.15 },
            },
            rectangle: {
                shapeOptions: { color: "#0d6efd", fillOpacity: 0.15 },
            },
        },
        edit: { featureGroup: drawnItems },
    });
    map.addControl(drawControl);

    // Handle draw events
    map.on(L.Draw.Event.CREATED, function (e) {
        // Only allow one polygon/rectangle at a time
        drawnItems.clearLayers();
        drawnPolygon = e.layer;
        drawnItems.addLayer(drawnPolygon);
        _updatePolygonField();
    });

    map.on(L.Draw.Event.EDITED, function () {
        drawnItems.eachLayer(function (layer) {
            drawnPolygon = layer;
        });
        _updatePolygonField();
    });

    map.on(L.Draw.Event.DELETED, function () {
        drawnPolygon = null;
        document.getElementById("area_polygon").value = "";
    });

    // Left-click: place/move main pin
    map.on("click", function (e) {
        if (mainPin) {
            mainPin.setLatLng(e.latlng);
        } else {
            mainPin = L.marker(e.latlng, { draggable: true }).addTo(map);
            mainPin.on("dragend", function () {
                _updatePinFields(mainPin.getLatLng());
            });
        }
        _updatePinFields(e.latlng);
    });

    // Right-click: add POI
    map.on("contextmenu", function (e) {
        e.originalEvent.preventDefault();
        const label = prompt("POI Label:", "Point of Interest " + (pois.length + 1));
        if (label === null) return;

        const poiMarker = L.marker(e.latlng, {
            icon: L.divIcon({
                className: "poi-icon",
                html: '<i class="bi bi-star-fill" style="color: #fd7e14; font-size: 1.2rem;"></i>',
                iconSize: [20, 20],
                iconAnchor: [10, 10],
            }),
            draggable: true,
        }).addTo(map);

        poiMarker.bindTooltip(label, { permanent: true, direction: "right", offset: [10, 0] });

        const poiData = { lat: e.latlng.lat, lng: e.latlng.lng, label: label, marker: poiMarker };
        pois.push(poiData);

        poiMarker.on("dragend", function () {
            const pos = poiMarker.getLatLng();
            poiData.lat = pos.lat;
            poiData.lng = pos.lng;
            _updatePoisField();
        });

        _updatePoisField();
    });

    // Address search via Nominatim
    const searchBtn = document.getElementById("btn-search-address");
    const searchInput = document.getElementById("address-search");

    function doSearch() {
        const q = searchInput.value.trim();
        if (!q) return;

        fetch(
            "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" +
            encodeURIComponent(q)
        )
            .then((r) => r.json())
            .then(function (results) {
                if (results.length === 0) {
                    alert("Address not found. Try a different search.");
                    return;
                }
                const r = results[0];
                const latlng = L.latLng(parseFloat(r.lat), parseFloat(r.lon));
                map.setView(latlng, 16);

                if (mainPin) {
                    mainPin.setLatLng(latlng);
                } else {
                    mainPin = L.marker(latlng, { draggable: true }).addTo(map);
                    mainPin.on("dragend", function () {
                        _updatePinFields(mainPin.getLatLng());
                    });
                }
                _updatePinFields(latlng);
                document.getElementById("location_address").value = r.display_name;
            })
            .catch(function () {
                alert("Search failed. Please try again.");
            });
    }

    if (searchBtn) searchBtn.addEventListener("click", doSearch);
    if (searchInput)
        searchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                doSearch();
            }
        });

    // Helpers
    function _updatePinFields(latlng) {
        document.getElementById("location_lat").value = latlng.lat.toFixed(7);
        document.getElementById("location_lng").value = latlng.lng.toFixed(7);
    }

    function _updatePolygonField() {
        if (!drawnPolygon) return;
        const coords = drawnPolygon.getLatLngs()[0].map(function (ll) {
            return [ll.lat, ll.lng];
        });
        document.getElementById("area_polygon").value = JSON.stringify(coords);
    }

    function _updatePoisField() {
        const data = pois.map(function (p) {
            return { lat: p.lat, lng: p.lng, label: p.label };
        });
        document.getElementById("pois_json").value = JSON.stringify(data);
    }

    // Invalidate map size when step 3 becomes visible
    window.initCustomerMap = function () {
        setTimeout(function () {
            map.invalidateSize();
        }, 200);
    };
})();
