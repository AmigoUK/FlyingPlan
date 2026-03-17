/**
 * 3D Mission Preview using Three.js (loaded from CDN).
 * Renders terrain mesh, flight path, and camera frustum cones.
 * Read-only visualization — no editing.
 */
var ThreePreview = (function () {
    "use strict";

    var _scene, _camera, _renderer, _controls;
    var _container = null;
    var _initialized = false;

    function init(canvasId) {
        _container = document.getElementById(canvasId);
        if (!_container) return;

        // Check if Three.js is available
        if (typeof THREE === "undefined") return;

        _scene = new THREE.Scene();
        _scene.background = new THREE.Color(0x87ceeb); // sky blue

        var width = _container.clientWidth || 600;
        var height = 400;
        _container.style.height = height + "px";

        _camera = new THREE.PerspectiveCamera(60, width / height, 1, 10000);
        _camera.position.set(200, 200, 200);

        _renderer = new THREE.WebGLRenderer({ canvas: _container, antialias: true });
        _renderer.setSize(width, height);

        // Orbit controls
        if (typeof THREE.OrbitControls !== "undefined") {
            _controls = new THREE.OrbitControls(_camera, _renderer.domElement);
            _controls.enableDamping = true;
        }

        // Lighting
        var ambient = new THREE.AmbientLight(0xffffff, 0.6);
        _scene.add(ambient);
        var directional = new THREE.DirectionalLight(0xffffff, 0.8);
        directional.position.set(100, 200, 100);
        _scene.add(directional);

        _initialized = true;
        _animate();
    }

    function renderTerrain(terrainData) {
        if (!_initialized) return;

        var elevations = terrainData.elevations;
        var bounds = terrainData.bounds;
        var rows = terrainData.rows;
        var cols = terrainData.cols;
        var minElev = terrainData.min_elevation;
        var maxElev = terrainData.max_elevation;

        if (!elevations || rows === 0 || cols === 0) return;

        // Scale factors
        var scaleX = 400 / cols;
        var scaleZ = 400 / rows;
        var elevRange = maxElev - minElev || 1;
        var scaleY = 100 / elevRange;

        // Create terrain geometry
        var geometry = new THREE.PlaneGeometry(400, 400, cols - 1, rows - 1);
        var vertices = geometry.attributes.position.array;

        for (var r = 0; r < rows; r++) {
            for (var c = 0; c < cols; c++) {
                var idx = (r * cols + c) * 3;
                vertices[idx + 2] = (elevations[r][c] - minElev) * scaleY;
            }
        }
        geometry.computeVertexNormals();

        var material = new THREE.MeshLambertMaterial({
            color: 0x4a7c59,
            side: THREE.DoubleSide,
            wireframe: false,
        });

        var mesh = new THREE.Mesh(geometry, material);
        mesh.rotation.x = -Math.PI / 2;
        mesh.name = "terrain";
        _scene.add(mesh);

        // Store transform info for waypoint positioning
        _terrainInfo = {
            bounds: bounds,
            minElev: minElev,
            scaleX: scaleX,
            scaleZ: scaleZ,
            scaleY: scaleY,
            cols: cols,
            rows: rows,
        };
    }

    var _terrainInfo = null;

    function renderFlightPath(waypoints) {
        if (!_initialized || !_terrainInfo) return;

        var info = _terrainInfo;
        var points = [];

        waypoints.forEach(function (wp) {
            var pos = _wpToWorld(wp, info);
            points.push(new THREE.Vector3(pos.x, pos.y, pos.z));
        });

        if (points.length < 2) return;

        // Flight path line
        var lineGeom = new THREE.BufferGeometry().setFromPoints(points);
        var lineMat = new THREE.LineBasicMaterial({ color: 0x00ff00, linewidth: 2 });
        var line = new THREE.Line(lineGeom, lineMat);
        line.name = "flightpath";
        _scene.add(line);

        // Camera frustum cones at each waypoint
        waypoints.forEach(function (wp, i) {
            var pos = _wpToWorld(wp, info);
            var gimbalDeg = wp.gimbal_pitch_deg || -90;
            var headingDeg = wp.heading_deg || 0;

            // Small cone showing camera direction
            var coneGeom = new THREE.ConeGeometry(3, 10, 4);
            var coneMat = new THREE.MeshBasicMaterial({
                color: gimbalDeg <= -80 ? 0x00aaff : 0xff6600,
                transparent: true,
                opacity: 0.7,
            });
            var cone = new THREE.Mesh(coneGeom, coneMat);
            cone.position.set(pos.x, pos.y, pos.z);

            // Orient cone: default points up (+Y), rotate to camera direction
            var pitchRad = THREE.MathUtils.degToRad(gimbalDeg + 90);
            var yawRad = THREE.MathUtils.degToRad(-headingDeg);
            cone.rotation.set(pitchRad, yawRad, 0);

            cone.name = "camera_" + i;
            _scene.add(cone);

            // Waypoint sphere
            var sphereGeom = new THREE.SphereGeometry(2, 8, 8);
            var sphereMat = new THREE.MeshBasicMaterial({ color: 0xffff00 });
            var sphere = new THREE.Mesh(sphereGeom, sphereMat);
            sphere.position.set(pos.x, pos.y, pos.z);
            sphere.name = "wp_" + i;
            _scene.add(sphere);
        });

        // Center camera on the flight path
        if (points.length > 0) {
            var center = new THREE.Vector3();
            points.forEach(function (p) { center.add(p); });
            center.divideScalar(points.length);
            _camera.position.set(center.x + 200, center.y + 150, center.z + 200);
            _camera.lookAt(center);
            if (_controls) _controls.target.copy(center);
        }
    }

    function _wpToWorld(wp, info) {
        var bounds = info.bounds;
        var latRange = bounds.max_lat - bounds.min_lat || 0.001;
        var lngRange = bounds.max_lng - bounds.min_lng || 0.001;

        var x = ((wp.lng - bounds.min_lng) / lngRange - 0.5) * 400;
        var z = ((wp.lat - bounds.min_lat) / latRange - 0.5) * -400;
        var y = (wp.altitude_m || 30) * info.scaleY;

        return { x: x, y: y, z: z };
    }

    function clear() {
        if (!_initialized) return;
        // Remove named objects
        var toRemove = [];
        _scene.traverse(function (obj) {
            if (obj.name && (obj.name === "terrain" ||
                obj.name === "flightpath" ||
                obj.name.startsWith("camera_") ||
                obj.name.startsWith("wp_"))) {
                toRemove.push(obj);
            }
        });
        toRemove.forEach(function (obj) { _scene.remove(obj); });
        _terrainInfo = null;
    }

    function _animate() {
        if (!_initialized) return;
        requestAnimationFrame(_animate);
        if (_controls) _controls.update();
        _renderer.render(_scene, _camera);
    }

    return {
        init: init,
        renderTerrain: renderTerrain,
        renderFlightPath: renderFlightPath,
        clear: clear,
    };
})();
