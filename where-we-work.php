<?php
session_start();
$pageTitle = 'Où nous travaillons';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';

require_once __DIR__ . '/inc/db.php';

$organisation = null;
$locations = [];
$locationCounts = [];
$mapLocations = [];
$mapCountries = [];

if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = 'Où nous travaillons — ' . $organisation->name;
    }

    $orgId = (int) $pdo->query("SELECT id FROM organisation WHERE is_active = 1 LIMIT 1")->fetchColumn();
    if ($orgId) {
        $stmt = $pdo->query("
            SELECT m.location, COUNT(*) AS cnt
            FROM mission m
            WHERE m.organisation_id = $orgId AND m.location IS NOT NULL AND TRIM(m.location) != ''
            GROUP BY m.location
            ORDER BY m.location
        ");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $locations[] = $row->location;
                $locationCounts[$row->location] = (int) $row->cnt;
            }
        }

        try {
            $stmt = $pdo->prepare("
                SELECT m.id, m.title, m.location, m.latitude, m.longitude
                FROM mission m
                WHERE m.organisation_id = ? AND m.latitude IS NOT NULL AND m.longitude IS NOT NULL AND m.location IS NOT NULL AND TRIM(m.location) != ''
                ORDER BY m.updated_at DESC
                LIMIT 80
            ");
            $stmt->execute([$orgId]);
            if ($stmt) $mapLocations = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {}

        $countryFrToEn = [
            'Guinée équatoriale' => 'Equatorial Guinea',
            'République démocratique du Congo' => 'Democratic Republic of the Congo', 'RDC' => 'Democratic Republic of the Congo',
            'Soudan du Sud' => 'South Sudan', 'Soudan' => 'Sudan', 'Yémen' => 'Yemen', 'France' => 'France',
            'Congo' => 'Republic of the Congo', 'République du Congo' => 'Republic of the Congo',
        ];
        foreach ($locations as $loc) {
            $parts = array_map('trim', explode(',', $loc));
            if (!empty($parts)) {
                $c = end($parts);
                $en = $countryFrToEn[$c] ?? $c;
                $mapCountries[$en] = true;
            }
        }
        $mapCountries = array_keys($mapCountries);
    }
}

require_once __DIR__ . '/inc/asset_url.php';
require_once __DIR__ . '/inc/page-static.php';
?>

    <section class="where-we-work-hero py-4 py-md-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-3">
                <a href="<?= $baseUrl ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-2">Où nous travaillons</h1>
            <p class="lead text-muted mb-0">Visualisez nos zones d'intervention et accédez aux missions par lieu.</p>
        </div>
    </section>

    <section class="where-we-work-map-section py-4">
        <div class="container">
            <div id="where-we-work-map" class="where-we-work-map rounded overflow-hidden shadow-sm"
                 data-base-url="<?= htmlspecialchars($baseUrl) ?>"
                 data-locations="<?= htmlspecialchars(json_encode($mapLocations)) ?>"
                 data-countries="<?= htmlspecialchars(json_encode($mapCountries)) ?>"
                 style="height: 400px; background: #e9ecef;"></div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-heading mb-4">Nos lieux d'intervention</h2>
            <?php if (count($locations) > 0): ?>
                <p class="text-muted mb-4">Cliquez sur un lieu pour voir les missions associées.</p>
                <div class="row g-3">
                    <?php foreach ($locations as $loc):
                        $count = $locationCounts[$loc] ?? 0;
                    ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <a href="<?= $baseUrl ?>missions.php?location=<?= urlencode($loc) ?>" class="where-we-work-card card h-100 text-decoration-none border-0 shadow-sm">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <span class="where-we-work-card-icon rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-geo-alt"></i></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <span class="d-block fw-semibold text-dark"><?= htmlspecialchars($loc) ?></span>
                                        <span class="small text-muted"><?= $count ?> mission<?= $count !== 1 ? 's' : '' ?></span>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Les lieux d'intervention seront listés ici à partir des missions enregistrées.</p>
            <?php endif; ?>
        </div>
    </section>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
    (function() {
        var mapEl = document.getElementById('where-we-work-map');
        if (!mapEl || typeof L === 'undefined') return;
        var baseUrl = mapEl.getAttribute('data-base-url') || '';
        var locations = [], countries = [];
        try { locations = JSON.parse(mapEl.getAttribute('data-locations') || '[]'); } catch(e) {}
        try { countries = JSON.parse(mapEl.getAttribute('data-countries') || '[]'); } catch(e) {}
        var map = L.map('where-we-work-map').setView([2, 20], 3);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        if (countries.length > 0) {
            var countrySet = {};
            countries.forEach(function(c) { countrySet[c] = true; });
            fetch('https://raw.githubusercontent.com/datasets/geo-countries/master/data/countries.geojson')
                .then(function(r) { return r.json(); })
                .then(function(geojson) {
                    L.geoJSON(geojson, {
                        style: function(feature) {
                            var name = (feature.properties && (feature.properties.ADMIN || feature.properties.name)) || '';
                            var isIntervention = countrySet[name];
                            return {
                                fillColor: isIntervention ? '#0071BC' : '#e8e8e8',
                                fillOpacity: isIntervention ? 0.45 : 0.25,
                                color: isIntervention ? '#1D1C3E' : '#ccc',
                                weight: isIntervention ? 1.2 : 0.6
                            };
                        }
                    }).addTo(map);
                })
                .catch(function() {});
        }
        function escapeHtml(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
        var bounds = null;
        locations.forEach(function(m) {
            var lat = parseFloat(m.latitude), lng = parseFloat(m.longitude);
            if (isNaN(lat) || isNaN(lng)) return;
            var popup = '<a href="' + baseUrl + 'mission.php?id=' + m.id + '">' + escapeHtml(m.title || 'Mission') + '</a>';
            if (m.location) popup += '<br><small class="text-muted">' + escapeHtml(m.location) + '</small>';
            L.marker([lat, lng]).addTo(map).bindPopup(popup);
            if (!bounds) bounds = L.latLngBounds([lat, lng], [lat, lng]); else bounds.extend([lat, lng]);
        });
        if (bounds && locations.length > 0) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 10 });
    })();
    </script>

<?php require __DIR__ . '/inc/footer.php'; ?>
