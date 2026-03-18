// Workflow Manager: guided card visibility, stepper, auto-progress, localStorage
// Uses DOM methods exclusively — no innerHTML.
var WorkflowManager = (function () {
    "use strict";

    var WORKFLOWS = {
        area_survey: {
            label: "Area Survey (2D)",
            steps: ["gsd", "grid", "coverage", "waypoints"],
            autoFor: ["survey", "agriculture", "construction"]
        },
        photogrammetry_3d: {
            label: "3D Photogrammetry",
            steps: ["gsd", "oblique", "quality", "coverage", "waypoints"],
            autoFor: []
        },
        building_inspection: {
            label: "Building Inspection",
            steps: ["facade", "coverage", "waypoints"],
            autoFor: ["inspection"]
        },
        poi_cinematic: {
            label: "POI / Cinematic",
            steps: ["patterns", "waypoints"],
            autoFor: ["aerial_photo", "real_estate", "event_celebration"]
        },
        manual: {
            label: "Manual Planning",
            steps: ["path_tools", "waypoints"],
            autoFor: ["custom_other", "emergency_insurance"]
        }
    };

    var STEP_LABELS = {
        gsd: "GSD",
        grid: "Grid",
        oblique: "3D Map",
        patterns: "Patterns",
        facade: "Facade",
        path_tools: "Path",
        coverage: "Coverage",
        quality: "Quality",
        waypoints: "Save"
    };

    var _planId = "";
    var _currentWorkflow = "manual";
    var _completedSteps = {};
    var _showAllTools = false;
    var _collapseStates = {};
    var _containerEl = null;

    function _storageKey() {
        return "wfm_" + _planId;
    }

    function _saveState() {
        var state = {
            workflow: _currentWorkflow,
            completed: _completedSteps,
            showAll: _showAllTools,
            collapsed: _collapseStates
        };
        try { localStorage.setItem(_storageKey(), JSON.stringify(state)); } catch (e) { /* ignore */ }
    }

    function _loadState() {
        try {
            var raw = localStorage.getItem(_storageKey());
            if (raw) return JSON.parse(raw);
        } catch (e) { /* ignore */ }
        return null;
    }

    function _autoSelectWorkflow(jobType, hasPolygon) {
        if (hasPolygon && jobType === "survey") return "photogrammetry_3d";
        for (var key in WORKFLOWS) {
            if (WORKFLOWS[key].autoFor.indexOf(jobType) !== -1) return key;
        }
        return "manual";
    }

    function init(planId, jobType, hasPolygon) {
        _planId = planId;
        _containerEl = document.getElementById("workflow-bar");
        if (!_containerEl) return;

        var saved = _loadState();
        if (saved) {
            _currentWorkflow = saved.workflow || _autoSelectWorkflow(jobType, hasPolygon);
            _completedSteps = saved.completed || {};
            _showAllTools = saved.showAll || false;
            _collapseStates = saved.collapsed || {};
        } else {
            _currentWorkflow = _autoSelectWorkflow(jobType, hasPolygon);
        }

        _renderWorkflowBar();
        _applyWorkflow(!saved);
        _restoreCollapseStates();
        _bindCollapseEvents();
    }

    function _renderWorkflowBar() {
        var wf = WORKFLOWS[_currentWorkflow];
        _containerEl.textContent = "";

        // Workflow selector row
        var selectorRow = document.createElement("div");
        selectorRow.className = "d-flex align-items-center mb-2";
        var lbl = document.createElement("label");
        lbl.className = "form-label mb-0 me-2 fw-semibold small";
        lbl.textContent = "Workflow:";
        var sel = document.createElement("select");
        sel.id = "workflow-select";
        sel.className = "form-select form-select-sm";
        sel.style.width = "auto";
        for (var key in WORKFLOWS) {
            var opt = document.createElement("option");
            opt.value = key;
            opt.textContent = WORKFLOWS[key].label;
            if (key === _currentWorkflow) opt.selected = true;
            sel.appendChild(opt);
        }
        selectorRow.appendChild(lbl);
        selectorRow.appendChild(sel);
        _containerEl.appendChild(selectorRow);

        // Stepper
        var stepper = document.createElement("div");
        stepper.className = "workflow-stepper";
        wf.steps.forEach(function (stepKey, i) {
            var done = _completedSteps[stepKey];
            var stepDiv = document.createElement("div");
            stepDiv.className = "wf-step" + (done ? " completed" : "");
            stepDiv.dataset.step = stepKey;
            var circle = document.createElement("span");
            circle.className = "wf-step-circle";
            circle.textContent = done ? "\u2713" : String(i + 1);
            var label = document.createElement("span");
            label.className = "wf-step-label";
            label.textContent = STEP_LABELS[stepKey] || stepKey;
            stepDiv.appendChild(circle);
            stepDiv.appendChild(label);
            stepper.appendChild(stepDiv);
        });
        _containerEl.appendChild(stepper);

        // Show all tools link
        var linkWrap = document.createElement("div");
        linkWrap.className = "text-center mt-1";
        var link = document.createElement("a");
        link.href = "#";
        link.id = "btn-show-all-tools";
        link.className = "small text-muted";
        link.textContent = _showAllTools ? "Hide extra tools" : "Show all tools";
        linkWrap.appendChild(link);
        _containerEl.appendChild(linkWrap);

        // Bind workflow select
        sel.addEventListener("change", function () {
            _currentWorkflow = sel.value;
            _completedSteps = {};
            _showAllTools = false;
            _renderWorkflowBar();
            _applyWorkflow(true);
            _saveState();
        });

        // Bind show all tools
        link.addEventListener("click", function (e) {
            e.preventDefault();
            _showAllTools = !_showAllTools;
            link.textContent = _showAllTools ? "Hide extra tools" : "Show all tools";
            _applyToolVisibility();
            _saveState();
        });

        // Bind step clicks
        stepper.querySelectorAll(".wf-step").forEach(function (el) {
            el.addEventListener("click", function () {
                _scrollToCard(el.dataset.step);
            });
        });
    }

    function _applyWorkflow(isInitial) {
        _applyToolVisibility();
        if (isInitial) {
            _applyInitialExpansion();
        }
    }

    function _applyToolVisibility() {
        var wf = WORKFLOWS[_currentWorkflow];
        var activeSteps = wf.steps;
        var hasPolygon = !!document.getElementById("plan-polygon").value;

        document.querySelectorAll("[data-card-key]").forEach(function (card) {
            var key = card.dataset.cardKey;
            if (key === "route_stats" || key === "waypoints") {
                card.style.display = "";
                return;
            }
            var inWorkflow = activeSteps.indexOf(key) !== -1;
            if (inWorkflow || _showAllTools) {
                card.style.display = "";
                if ((key === "grid" || key === "oblique") && !hasPolygon) {
                    _showPolygonMessage(card, true);
                } else {
                    _showPolygonMessage(card, false);
                }
            } else {
                card.style.display = "none";
            }
        });
    }

    function _showPolygonMessage(card, show) {
        var existing = card.querySelector(".card-needs-polygon");
        if (show) {
            if (!existing) {
                var target = card.querySelector(".collapse") || card.querySelector(".card-body");
                if (target) {
                    var msg = document.createElement("div");
                    msg.className = "card-needs-polygon";
                    msg.textContent = "Draw an area polygon on the map to enable this tool.";
                    target.prepend(msg);
                }
            }
        } else {
            if (existing) existing.remove();
        }
    }

    function _applyInitialExpansion() {
        var wf = WORKFLOWS[_currentWorkflow];
        var firstExpanded = false;

        document.querySelectorAll("[data-card-key]").forEach(function (card) {
            var key = card.dataset.cardKey;
            if (key === "waypoints" || key === "route_stats") return;

            var collapseEl = card.querySelector(".collapse");
            if (!collapseEl) return;

            var inWorkflow = wf.steps.indexOf(key) !== -1;
            var bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });

            if (inWorkflow && !firstExpanded && !_completedSteps[key]) {
                bsCollapse.show();
                firstExpanded = true;
            } else {
                bsCollapse.hide();
            }
        });

        // Collapse info cards on initial load
        document.querySelectorAll(".info-card-collapse").forEach(function (el) {
            var bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
            bsCollapse.hide();
        });
    }

    function _restoreCollapseStates() {
        if (!Object.keys(_collapseStates).length) return;
        for (var id in _collapseStates) {
            var el = document.getElementById(id);
            if (!el) continue;
            var bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
            if (_collapseStates[id]) {
                bsCollapse.show();
            } else {
                bsCollapse.hide();
            }
        }
    }

    function _bindCollapseEvents() {
        document.querySelectorAll(".collapse").forEach(function (el) {
            if (!el.id) return;
            el.addEventListener("shown.bs.collapse", function () {
                _collapseStates[el.id] = true;
                _saveState();
            });
            el.addEventListener("hidden.bs.collapse", function () {
                _collapseStates[el.id] = false;
                _saveState();
            });
        });
    }

    function _scrollToCard(stepKey) {
        var card = document.querySelector('[data-card-key="' + stepKey + '"]');
        if (!card) return;

        if (card.style.display === "none") {
            card.style.display = "";
        }

        var collapseEl = card.querySelector(".collapse");
        if (collapseEl) {
            var bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            bsCollapse.show();
        }

        card.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function markStepComplete(stepKey) {
        _completedSteps[stepKey] = true;
        _renderWorkflowBar();

        var wf = WORKFLOWS[_currentWorkflow];
        var idx = wf.steps.indexOf(stepKey);
        if (idx === -1) { _saveState(); return; }

        // Find next incomplete step
        for (var i = idx + 1; i < wf.steps.length; i++) {
            var nextKey = wf.steps[i];
            if (!_completedSteps[nextKey]) {
                _scrollToCard(nextKey);
                break;
            }
        }
        _saveState();
    }

    function markStepIncomplete(stepKey) {
        delete _completedSteps[stepKey];
        _renderWorkflowBar();
        _saveState();
    }

    function notifyPolygonChange() {
        _applyToolVisibility();
    }

    return {
        init: init,
        markStepComplete: markStepComplete,
        markStepIncomplete: markStepIncomplete,
        notifyPolygonChange: notifyPolygonChange
    };
})();
