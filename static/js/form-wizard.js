// Multi-step form wizard with validation and sessionStorage auto-save
(function () {
    "use strict";

    const form = document.getElementById("flightPlanForm");
    if (!form) return;

    const steps = document.querySelectorAll(".form-step");
    const progressSteps = document.querySelectorAll(".step-progress .step");
    let currentStep = 1;
    const STORAGE_KEY = "flyingplan_form_data";

    // Step validation rules
    const validations = {
        1: function () {
            const name = document.getElementById("customer_name");
            const email = document.getElementById("customer_email");
            let valid = true;
            if (!name.value.trim()) {
                name.classList.add("is-invalid");
                valid = false;
            } else {
                name.classList.remove("is-invalid");
            }
            if (!email.value.trim() || !email.value.includes("@")) {
                email.classList.add("is-invalid");
                valid = false;
            } else {
                email.classList.remove("is-invalid");
            }
            return valid;
        },
        2: function () {
            const jobType = document.getElementById("job_type");
            const desc = document.getElementById("job_description");
            let valid = true;
            if (!jobType.value) {
                jobType.classList.add("is-invalid");
                valid = false;
            } else {
                jobType.classList.remove("is-invalid");
            }
            if (!desc.value.trim()) {
                desc.classList.add("is-invalid");
                valid = false;
            } else {
                desc.classList.remove("is-invalid");
            }
            return valid;
        },
        3: function () {
            const lat = document.getElementById("location_lat").value;
            const lng = document.getElementById("location_lng").value;
            if (!lat || !lng) {
                alert("Please place a pin on the map to mark the location.");
                return false;
            }
            return true;
        },
        4: function () {
            return true; // All fields have defaults
        },
        5: function () {
            const consent = document.getElementById("consent_given");
            if (!consent.checked) {
                consent.classList.add("is-invalid");
                return false;
            }
            consent.classList.remove("is-invalid");
            return true;
        },
    };

    function goToStep(step) {
        if (step < 1 || step > 5) return;

        steps.forEach(function (el) {
            el.classList.remove("active");
        });
        progressSteps.forEach(function (el, i) {
            el.classList.remove("active", "completed");
            if (i + 1 < step) el.classList.add("completed");
            if (i + 1 === step) el.classList.add("active");
        });

        var target = document.querySelector('.form-step[data-step="' + step + '"]');
        if (target) target.classList.add("active");

        currentStep = step;

        // Init map when entering step 3
        if (step === 3 && typeof window.initCustomerMap === "function") {
            window.initCustomerMap();
        }

        // Populate review when entering step 5
        if (step === 5) {
            populateReview();
        }

        // Save progress
        saveToStorage();
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    // Next/Prev buttons
    document.querySelectorAll(".btn-next").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (validations[currentStep] && !validations[currentStep]()) return;
            goToStep(currentStep + 1);
        });
    });

    document.querySelectorAll(".btn-prev").forEach(function (btn) {
        btn.addEventListener("click", function () {
            goToStep(currentStep - 1);
        });
    });

    // Submit validation
    form.addEventListener("submit", function (e) {
        if (!validations[5]()) {
            e.preventDefault();
        } else {
            sessionStorage.removeItem(STORAGE_KEY);
        }
    });

    // Custom altitude toggle
    var altPreset = document.getElementById("altitude_preset");
    var altCustomGroup = document.getElementById("custom-altitude-group");
    if (altPreset && altCustomGroup) {
        altPreset.addEventListener("change", function () {
            altCustomGroup.style.display =
                altPreset.value === "custom" ? "block" : "none";
        });
    }

    // Review population
    function populateReview() {
        _setText("rev-name", _val("customer_name"));
        _setText("rev-email", _val("customer_email"));
        _setText("rev-phone", _val("customer_phone") || "-");
        _setText("rev-company", _val("customer_company") || "-");

        var jobType = _val("job_type");
        _setText("rev-job-type", jobType ? jobType.replace(/_/g, " ") : "-");
        _setText("rev-urgency", _val("urgency") || "Normal");
        _setText("rev-description", _val("job_description") || "-");
        _setText("rev-dates", _val("preferred_dates") || "Flexible");
        _setText("rev-time", _val("time_window") || "Flexible");

        _setText("rev-address", _val("location_address") || "-");
        var lat = _val("location_lat");
        var lng = _val("location_lng");
        _setText("rev-coords", lat && lng ? lat + ", " + lng : "-");

        var altP = _val("altitude_preset");
        var altText = altP === "custom" ? "Custom (" + _val("altitude_custom_m") + "m)" : (altP || "Medium");
        _setText("rev-altitude", altText);
        _setText("rev-camera", _selText("camera_angle") || "Pilot Decides");
        _setText("rev-resolution", _val("video_resolution") || "4K");

        // Mini review map
        initReviewMap(lat, lng);
    }

    function initReviewMap(lat, lng) {
        var el = document.getElementById("review-map");
        if (!el || !lat || !lng) return;
        // Clear any existing map by removing children
        while (el.firstChild) el.removeChild(el.firstChild);
        // Remove leaflet internal reference
        if (el._leaflet_id) {
            delete el._leaflet_id;
        }
        var reviewMap = L.map("review-map", {
            zoomControl: false,
            dragging: false,
            scrollWheelZoom: false,
        }).setView([parseFloat(lat), parseFloat(lng)], 15);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
        }).addTo(reviewMap);
        L.marker([parseFloat(lat), parseFloat(lng)]).addTo(reviewMap);
        setTimeout(function () { reviewMap.invalidateSize(); }, 200);
    }

    function _val(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : "";
    }

    function _selText(name) {
        var el = document.querySelector('[name="' + name + '"]');
        if (!el) return "";
        return el.options ? el.options[el.selectedIndex].text : el.value;
    }

    function _setText(id, text) {
        var el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    // SessionStorage auto-save/restore
    function saveToStorage() {
        var data = {};
        form.querySelectorAll("input, select, textarea").forEach(function (el) {
            if (el.name && el.type !== "file" && el.type !== "hidden" && el.name !== "csrf_token") {
                if (el.type === "checkbox") {
                    data[el.name] = el.checked;
                } else {
                    data[el.name] = el.value;
                }
            }
        });
        data._step = currentStep;
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function restoreFromStorage() {
        var raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        try {
            var data = JSON.parse(raw);
            Object.keys(data).forEach(function (key) {
                if (key === "_step") return;
                var el = form.querySelector('[name="' + key + '"]');
                if (!el) return;
                if (el.type === "checkbox") {
                    el.checked = data[key];
                } else {
                    el.value = data[key];
                }
            });
            // Trigger altitude toggle
            if (altPreset) altPreset.dispatchEvent(new Event("change"));
            // Go to saved step
            if (data._step && data._step > 1) {
                goToStep(data._step);
            }
        } catch (e) {
            // Ignore parse errors
        }
    }

    restoreFromStorage();
})();
