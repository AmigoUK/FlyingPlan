<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Help & Documentation<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-lg-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-list-ul"></i> Contents</h6>
                <nav>
                    <a class="d-block mb-1" href="#getting-started">Getting Started</a>
                    <a class="d-block mb-1" href="#user-roles">User Roles</a>
                    <a class="d-block mb-1" href="#customer-form">Customer Form</a>
                    <a class="d-block mb-1" href="#admin-dashboard">Admin Dashboard</a>
                    <a class="d-block mb-1" href="#flight-planning">Flight Planning</a>
                    <a class="d-block mb-1" href="#pilot-workflow">Pilot Workflow</a>
                    <a class="d-block mb-1" href="#category-engine">CAA Categories</a>
                    <a class="d-block mb-1" href="#risk-assessment">Risk Assessment</a>
                    <a class="d-block mb-1" href="#exports">Export Formats</a>
                </nav>
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <section id="getting-started" class="mb-4">
            <h3>Getting Started</h3>
            <p>FlyingPlan is a commercial drone flight management system. Customers submit flight briefs through the public form, managers review and plan routes, and pilots execute flights with pre-flight risk assessments.</p>
        </section>

        <section id="user-roles" class="mb-4">
            <h3>User Roles</h3>
            <table class="table">
                <thead><tr><th>Role</th><th>Permissions</th></tr></thead>
                <tbody>
                    <tr><td><strong>Admin</strong></td><td>Full system control, manage pilots, settings, all operations</td></tr>
                    <tr><td><strong>Manager</strong></td><td>Review requests, plan flights, assign pilots, manage orders</td></tr>
                    <tr><td><strong>Pilot</strong></td><td>Accept jobs, complete risk assessments, record deliverables</td></tr>
                </tbody>
            </table>
        </section>

        <section id="customer-form" class="mb-4">
            <h3>Customer Request Form</h3>
            <p>A 5-step wizard guides customers through submitting a flight brief: customer details, job brief, location & map, flight preferences, and review.</p>
        </section>

        <section id="admin-dashboard" class="mb-4">
            <h3>Admin Dashboard</h3>
            <p>Lists all flight plan submissions with filtering by status, job type, and search. Each plan can be opened for detailed route planning.</p>
        </section>

        <section id="flight-planning" class="mb-4">
            <h3>Flight Planning Tools</h3>
            <ul>
                <li><strong>GSD Calculator:</strong> Determines photo resolution based on altitude and camera specs</li>
                <li><strong>Mission Patterns:</strong> Orbit, spiral, cable cam for inspections and cinematography</li>
                <li><strong>Grid Mission:</strong> Automated lawn-mower pattern for area coverage</li>
                <li><strong>3D Mapping Grid:</strong> Double-grid and multi-angle passes for photogrammetry</li>
                <li><strong>Facade Scanner:</strong> Vertical column patterns for building inspections</li>
                <li><strong>Coverage Analysis:</strong> Heatmap showing photo overlap quality</li>
                <li><strong>Quality Report:</strong> Predicts 3D reconstruction quality</li>
            </ul>
        </section>

        <section id="pilot-workflow" class="mb-4">
            <h3>Pilot Workflow</h3>
            <p>Order status progresses: Assigned &rarr; Accepted &rarr; In Progress &rarr; Flight Complete &rarr; Delivered &rarr; Closed</p>
        </section>

        <section id="category-engine" class="mb-4">
            <h3>UK CAA Category Engine</h3>
            <p>Automatically determines the operational category (Open A1/A2/A3, Specific PDRA-01/SORA, Certified) based on drone class, pilot qualifications, and flight parameters per UK Regulation EU 2019/947.</p>
        </section>

        <section id="risk-assessment" class="mb-4">
            <h3>Pre-Flight Risk Assessment</h3>
            <p>28-point mandatory checklist covering site assessment, airspace, weather, equipment, IMSAFE pilot fitness, permissions, and emergency procedures. Category-specific checks are added automatically.</p>
        </section>

        <section id="exports" class="mb-4">
            <h3>Export Formats</h3>
            <table class="table table-sm">
                <thead><tr><th>Format</th><th>Use</th></tr></thead>
                <tbody>
                    <tr><td>KMZ</td><td>DJI Fly app mission import</td></tr>
                    <tr><td>KML</td><td>Google Earth visualization</td></tr>
                    <tr><td>GeoJSON</td><td>GIS software</td></tr>
                    <tr><td>CSV</td><td>Spreadsheet analysis</td></tr>
                    <tr><td>GPX</td><td>GPS devices</td></tr>
                    <tr><td>Litchi CSV</td><td>Litchi flight app</td></tr>
                    <tr><td>Photo Positions</td><td>Pix4D/Metashape photogrammetry</td></tr>
                </tbody>
            </table>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
