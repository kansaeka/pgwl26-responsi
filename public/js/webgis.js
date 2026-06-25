// 1. Inisialisasi peta
var initialCenter = [-7.7702, 110.3776];
var initialZoom = 16;

var map = L.map('map').setView(initialCenter, initialZoom);

// Base map: OpenStreetMap
var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 20,
    attribution: '© OpenStreetMap contributors'
});

// Base map: Citra Satelit Esri
var esriImagery = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    {
        maxZoom: 20,
        attribution: 'Tiles © Esri'
    }
);

// Label untuk citra satelit
var esriLabels = L.tileLayer(
    'https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
    {
        maxZoom: 20,
        attribution: 'Labels © Esri'
    }
);

// Gabungan citra satelit + label
var satelliteMap = L.layerGroup([esriImagery, esriLabels]);

// Base map: Carto Light
var cartoLight = L.tileLayer(
    'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
    {
        maxZoom: 20,
        attribution: '© OpenStreetMap contributors © CARTO'
    }
);

// Base map: Carto Dark
var cartoDark = L.tileLayer(
    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
    {
        maxZoom: 20,
        attribution: '© OpenStreetMap contributors © CARTO'
    }
);

// Default basemap
satelliteMap.addTo(map);

// Scale bar
L.control.scale({
    metric: true,
    imperial: false,
    position: 'bottomleft'
}).addTo(map);

// 3. Layer group untuk layer control
var facilitiesLayer = L.layerGroup().addTo(map);
var obstaclesLayer = L.layerGroup().addTo(map);
var routesLayer = L.layerGroup().addTo(map);
var zonesLayer = L.layerGroup().addTo(map);

var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

var facilityCache = {};
var obstacleCache = {};
var routeCache = {};
var zoneCache = {};

var facilitySearchIndex = [];

var geometryEditMode = null;

// 4. Fungsi warna jalur
function routeColor(category) {
    if (category === 'Nyaman') return 'green';
    if (category === 'Cukup nyaman') return 'orange';
    return 'red';
}

// 5. Fungsi warna zona
function zoneColor(level) {
    if (level === 'Nyaman') return 'green';
    if (level === 'Cukup nyaman') return 'orange';
    return 'red';
}

// 6. Layer fasilitas kampus
function loadFacilities(filterCategory = 'all') {
    facilitiesLayer.clearLayers();
    facilityCache = {};
    facilitySearchIndex = [];

    fetch('/api/facilities')
        .then(response => response.json())
        .then(data => {
            L.geoJSON(data, {
                filter: function (feature) {
                    if (filterCategory === 'all') return true;
                    return feature.properties.category === filterCategory;
                },
                pointToLayer: function (feature, latlng) {
                    return L.circleMarker(latlng, {
                        radius: 7,
                        color: '#1d4ed8',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.8,
                        weight: 1
                    });
                },
                onEachFeature: function (feature, layer) {
                    facilityCache[feature.properties.id] = feature.properties;

                    layer.bindPopup(`
    <div class="popup-card popup-facility">
        <div class="popup-header">
            <strong>${feature.properties.name}</strong>
            <span class="popup-badge">${feature.properties.category ?? '-'}</span>
        </div>

        <div class="popup-description">
            <strong>Keterangan</strong>
            <p>${feature.properties.description ?? '-'}</p>
        </div>

        ${popupImage(feature)}
        ${popupActions('facility', feature.properties.id)}
    </div>
`);

                    // Tambahkan ke indeks pencarian
                    facilitySearchIndex.push({
                        id: feature.properties.id,
                        name: feature.properties.name,
                        category: feature.properties.category,
                        layer: layer
                    });
                }
            }).addTo(facilitiesLayer);
        });
}

// 7. Layer hambatan pedestrian
function loadObstacles(filterSeverity = 'all') {
    obstaclesLayer.clearLayers();
    obstacleCache = {};

    fetch('/api/obstacles')
        .then(response => response.json())
        .then(data => {
            L.geoJSON(data, {
                filter: function (feature) {
                    if (filterSeverity === 'all') return true;
                    return feature.properties.severity === filterSeverity;
                },
                pointToLayer: function (feature, latlng) {
                    var scale = feature.properties.obstacle_scale ?? 3;

                    return L.circleMarker(latlng, {
                        radius: obstacleRadius(scale),
                        color: '#7f1d1d',
                        fillColor: obstacleColor(scale),
                        fillOpacity: 0.9,
                        weight: 1.5
                    });
                },
                onEachFeature: function (feature, layer) {
                    obstacleCache[feature.properties.id] = feature.properties;

                    layer.bindPopup(`
    <div class="popup-card popup-obstacle">
        <div class="popup-header">
            <strong>${feature.properties.name}</strong>
            <span class="popup-badge severity">${feature.properties.severity ?? '-'}</span>
        </div>

        <div class="popup-meta">
            <div class="popup-meta-row">
                <span>Jenis Hambatan</span>
                <span>${feature.properties.obstacle_type ?? '-'}</span>
            </div>
            <div class="popup-meta-row">
                <span>Tingkat</span>
                <span>${feature.properties.severity ?? '-'}</span>
        </div>
            <div class="popup-meta-row">
                <span>Skala Hambatan</span>
                <span>${feature.properties.obstacle_scale ?? '-'}</span>
            </div>
            <div class="popup-meta-row">
                <span>Prioritas</span>
                <span>${feature.properties.priority_level ?? '-'}</span>
            </div>
        </div>

        <div class="popup-description">
            <strong>Keterangan</strong>
            <p>${feature.properties.description ?? '-'}</p>
            <strong>Rekomendasi</strong>
            <p>${feature.properties.recommendation ?? '-'}</p>
        </div>

        ${popupImage(feature)}
        ${popupActions('obstacle', feature.properties.id)}
    </div>
`);
                }
            }).addTo(obstaclesLayer);
        });
}

function obstacleColor(scale) {
    scale = parseInt(scale);

    if (scale >= 4) return '#ef4444'; // merah
    if (scale === 3) return '#f97316'; // oranye
    return '#facc15'; // kuning
}

function obstacleRadius(scale) {
    scale = parseInt(scale);

    if (scale >= 4) return 9;
    if (scale === 3) return 7;
    return 5;
}

// 8. Layer jalur pedestrian
function loadRoutes(filterCategory = 'all') {
    routesLayer.clearLayers();
    routeCache = {};

    fetch('/api/routes')
        .then(response => response.json())
        .then(data => {
            L.geoJSON(data, {
                filter: function (feature) {
                    if (filterCategory === 'all') return true;
                    return feature.properties.category === filterCategory;
                },
                style: function (feature) {
                    return {
                        color: routeColor(feature.properties.category),
                        weight: 5,
                        opacity: 0.85
                    };
                },
                onEachFeature: function (feature, layer) {
                    routeCache[feature.properties.id] = feature.properties;

                    layer.bindPopup(`
    <div class="popup-card popup-route">
        <div class="popup-header">
            <strong>${feature.properties.route_name}</strong>
            <span class="popup-badge route">${feature.properties.category ?? '-'}</span>
        </div>

        <div class="popup-meta">
            <div class="popup-meta-row">
                <span>Skor</span>
                <span>${feature.properties.score ?? '-'}</span>
            </div>
            <div class="popup-meta-row">
                <span>Prioritas</span>
                <span>${feature.properties.priority_level ?? '-'}</span>
            </div>
        </div>

        <div class="popup-description">
            <strong>Keterangan</strong>
            <p>${feature.properties.description ?? '-'}</p>
            <strong>Rekomendasi</strong>
            <p>${feature.properties.recommendation ?? '-'}</p>
        </div>

        ${popupImage(feature)}
        ${popupActions('route', feature.properties.id)}
    </div>
`);
                }
            }).addTo(routesLayer);
        });
}

// 9. Layer zona kenyamanan
function loadZones() {
    zonesLayer.clearLayers();
    zoneCache = {};

    fetch('/api/zones')
        .then(response => response.json())
        .then(data => {
            L.geoJSON(data, {
                style: function (feature) {
                    return {
                        color: zoneColor(feature.properties.comfort_level),
                        fillColor: zoneColor(feature.properties.comfort_level),
                        fillOpacity: 0.25,
                        weight: 2
                    };
                },
                onEachFeature: function (feature, layer) {
                    zoneCache[feature.properties.id] = feature.properties;

                    layer.bindPopup(`
    <div class="popup-card popup-zone">
        <div class="popup-header">
            <strong>${feature.properties.zone_name}</strong>
            <span class="popup-badge route">${feature.properties.comfort_level ?? '-'}</span>
        </div>

        <div class="popup-meta">
            <div class="popup-meta-row">
                <span>Skor</span>
                <span>${feature.properties.score ?? '-'}</span>
            </div>
            <div class="popup-meta-row">
                <span>Prioritas</span>
                <span>${feature.properties.priority_level ?? '-'}</span>
            </div>
        </div>

        <div class="popup-description">
            <strong>Keterangan</strong>
            <p>${feature.properties.description ?? '-'}</p>
            <strong>Rekomendasi</strong>
            <p>${feature.properties.recommendation ?? '-'}</p>
        </div>

        ${popupImage(feature)}
        ${popupActions('zone', feature.properties.id)}
    </div>
`);
                }
            }).addTo(zonesLayer);
        });
}

// 10. Layer control
var baseMaps = {
    "🛰️ Citra Satelit": satelliteMap,
    "🗺️ OpenStreetMap": osm,
    "⬜ Peta Terang": cartoLight,
    "⬛ Peta Gelap": cartoDark
};

var overlayMaps = {
    "🏛️ Fasilitas Kampus": facilitiesLayer,
    "⚠️ Hambatan Pedestrian": obstaclesLayer,
    "🚶 Jalur Pedestrian": routesLayer,
    "🟩 Zona Kenyamanan": zonesLayer
};

L.control.layers(baseMaps, overlayMaps, {
    collapsed: true,
    position: 'topright'
}).addTo(map);

var drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

var drawControl = new L.Control.Draw({
    draw: {
        marker: true,
        polyline: true,
        polygon: true,
        rectangle: false,
        circle: false,
        circlemarker: false
    },
    edit: false
});

map.addControl(drawControl);

function getCategoryFromScore(score) {
    score = parseInt(score);

    if (score >= 4) return 'Nyaman';
    if (score >= 2) return 'Cukup nyaman';
    return 'Kurang nyaman';
}

function polylineToWKT(layer) {
    var latlngs = layer.getLatLngs();

    var coordinates = latlngs.map(function (latlng) {
        return latlng.lng + ' ' + latlng.lat;
    }).join(', ');

    return 'LINESTRING(' + coordinates + ')';
}

function polygonToWKT(layer) {
    var latlngs = layer.getLatLngs()[0];

    var coordinates = latlngs.map(function (latlng) {
        return latlng.lng + ' ' + latlng.lat;
    });

    // tutup polygon dengan koordinat pertama
    coordinates.push(latlngs[0].lng + ' ' + latlngs[0].lat);

    return 'POLYGON((' + coordinates.join(', ') + '))';
}

map.on(L.Draw.Event.CREATED, function (event) {
    var layer = event.layer;
    var layerType = event.layerType;
    var drawType = document.getElementById('drawType').value;

    // Mode edit bentuk/geometri
    if (geometryEditMode !== null) {
        var type = geometryEditMode.type;
        var id = geometryEditMode.id;

        if (type === 'facility' || type === 'obstacle') {
            if (layerType !== 'marker') {
                showToast('Untuk mengubah lokasi titik, gunakan marker.');
                return;
            }

            var latlng = layer.getLatLng();
            var endpoint = type === 'facility'
                ? `/api/facilities/${id}/geometry`
                : `/api/obstacles/${id}/geometry`;

            fetch(endpoint, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    lat: latlng.lat,
                    lng: latlng.lng
                })
            })
                .then(response => response.json())
                .then(data => {
                    geometryEditMode = null;
                    showToast(data.message);
                    setTimeout(function () {
                        location.reload();
                    }, 1200);
                });

            return;
        }

        if (type === 'route') {
            if (layerType !== 'polyline') {
                showToast('Untuk mengubah bentuk jalur, gunakan garis/polyline.');
                return;
            }

            var wkt = polylineToWKT(layer);

            fetch(`/api/routes/${id}/geometry`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    wkt: wkt
                })
            })
                .then(response => response.json())
                .then(data => {
                    geometryEditMode = null;
                    showToast(data.message);
                    setTimeout(function () {
                        location.reload();
                    }, 1200);
                });

            return;
        }

        if (type === 'zone') {
            if (layerType !== 'polygon') {
                showToast('Untuk mengubah bentuk zona, gunakan polygon.');
                return;
            }

            var wkt = polygonToWKT(layer);

            fetch(`/api/zones/${id}/geometry`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    wkt: wkt
                })
            })
                .then(response => response.json())
                .then(data => {
                    geometryEditMode = null;
                    showToast(data.message);
                    setTimeout(function () {
                        location.reload();
                    }, 1200);
                });

            return;
        }
    }


    // Tambah fasilitas kampus
    if (drawType === 'facility') {
        if (layerType !== 'marker') {
            showToast('Untuk fasilitas kampus, gunakan marker/titik.');
            return;
        }

        var latlng = layer.getLatLng();

        var name = prompt('Nama fasilitas:');
        if (!name) return;

        var category = prompt('Kategori fasilitas:', 'Fasilitas Kampus');
        var description = prompt('Deskripsi:', 'Fasilitas pendukung aktivitas kawasan UGM');

        fetch('/api/facilities', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                name: name,
                category: category,
                description: description,
                lat: latlng.lat,
                lng: latlng.lng
            })
        })
            .then(response => response.json())
            .then(data => {
                showToast(data.message);
                setTimeout(function () {
                    location.reload();
                }, 1200);
            });
    }

    // Tambah hambatan pedestrian
    if (drawType === 'obstacle') {
        if (layerType !== 'marker') {
            showToast('Untuk hambatan pedestrian, gunakan marker/titik.');
            return;
        }

        var latlng = layer.getLatLng();

        var name = prompt('Nama hambatan:');
        if (!name) return;

        var obstacleType = prompt('Jenis hambatan:', 'Hambatan Pedestrian');
        var obstacleScale = prompt('Skala hambatan 1-5:\n1 = sangat ringan\n2 = ringan\n3 = sedang\n4 = tinggi\n5 = sangat tinggi', '3');

        if (!obstacleScale) return;

        obstacleScale = parseInt(obstacleScale);

        if (isNaN(obstacleScale) || obstacleScale < 1 || obstacleScale > 5) {
            showToast('Skala hambatan harus berupa angka 1 sampai 5.');
            return;
        }
        var description = prompt('Deskripsi:', 'Titik hambatan pergerakan pejalan kaki');

        fetch('/api/obstacles', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                name: name,
                obstacle_type: obstacleType,
                oobstacle_scale: obstacleScale,
                description: description,
                lat: latlng.lat,
                lng: latlng.lng
            })
        })
            .then(response => response.json())
            .then(data => {
                showToast(data.message);
                setTimeout(function () {
                    location.reload();
                }, 1200);
            });
    }

    // Tambah jalur pedestrian
    if (drawType === 'route') {
        if (layerType !== 'polyline') {
            showToast('Untuk jalur pedestrian, gunakan garis/polyline.');
            return;
        }

        var routeName = prompt('Nama jalur pedestrian:');
        if (!routeName) return;

        var score = prompt('Skor walkability 0-5:', '3');
        var category = getCategoryFromScore(score);
        var description = prompt(
            'Keterangan dan rekomendasi:',
            'Jalur pedestrian kawasan UGM. Rekomendasi: perbaikan fasilitas pedestrian, penataan crossing, dan peningkatan kenyamanan jalur.'
        );
        var wkt = polylineToWKT(layer);

        fetch('/api/routes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                route_name: routeName,
                score: score,
                category: category,
                description: description,
                wkt: wkt
            })
        })
            .then(response => response.json())
            .then(data => {
                showToast(data.message);
                setTimeout(function () {
                    location.reload();
                }, 1200);
            });
    }

    // Tambah zona kenyamanan
    if (drawType === 'zone') {
        if (layerType !== 'polygon') {
            showToast('Untuk zona kenyamanan, gunakan polygon/area.');
            return;
        }

        var zoneName = prompt('Nama zona kenyamanan:');
        if (!zoneName) return;

        var score = prompt('Skor kenyamanan 0-5:', '3');
        var comfortLevel = getCategoryFromScore(score);
        var description = prompt(
            'Keterangan dan rekomendasi:',
            'Zona kenyamanan pedestrian kawasan UGM. Rekomendasi: peningkatan teduhan, pengurangan hambatan fisik, dan penataan ruang pejalan kaki.'
        );
        var wkt = polygonToWKT(layer);

        fetch('/api/zones', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                zone_name: zoneName,
                score: score,
                comfort_level: comfortLevel,
                description: description,
                wkt: wkt
            })
        })
            .then(response => response.json())
            .then(data => {
                showToast(data.message);
                setTimeout(function () {
                    location.reload();
                }, 1200);
            });
    }
});

// 11. Statistik
function loadStatistics() {
    fetch('/api/statistics')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalFacilities').textContent = data.facilities;
            document.getElementById('totalObstacles').textContent = data.obstacles;
            document.getElementById('totalRoutes').textContent = data.routes;
            document.getElementById('totalZones').textContent = data.zones;
            document.getElementById('totalLength').textContent = data.route_length;
            document.getElementById('totalArea').textContent = data.zone_area;
        });
}

// 12. Fungsi edit dan hapus fasilitas
function editFacility(id) {
    var data = facilityCache[id];

    var name = prompt('Edit nama fasilitas:', data.name);
    if (!name) return;

    var category = prompt('Edit kategori:', data.category);
    var description = prompt('Edit deskripsi:', data.description);

    fetch('/api/facilities/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            name: name,
            category: category,
            description: description
        })
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message);
            setTimeout(function () {
                location.reload();
            }, 1200);
        });
}

function deleteFacility(id) {
    if (!confirm('Yakin ingin menghapus fasilitas ini?')) return;

    fetch('/api/facilities/' + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message);
            setTimeout(function () {
                location.reload();
            }, 1200);
        });
}

function editObstacle(id) {
    var data = obstacleCache[id];

    var name = prompt('Edit nama hambatan:', data.name);
    if (!name) return;

    var obstacleType = prompt('Edit jenis hambatan:', data.obstacle_type);
    var obstacleScale = prompt(
        'Edit skala hambatan 1-5:\n1 = sangat ringan\n2 = ringan\n3 = sedang\n4 = tinggi\n5 = sangat tinggi',
        data.obstacle_scale ?? 3
    );

    if (!obstacleScale) return;

    obstacleScale = parseInt(obstacleScale);

    if (isNaN(obstacleScale) || obstacleScale < 1 || obstacleScale > 5) {
        showToast('Skala hambatan harus berupa angka 1 sampai 5.');
        return;
    }
    var description = prompt(
        'Keterangan dan rekomendasi:',
        'Titik hambatan pergerakan pejalan kaki. Rekomendasi: penataan ulang ruang pedestrian dan pengurangan konflik dengan kendaraan.'
    );
    fetch('/api/obstacles/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            name: name,
            obstacle_type: obstacleType,
            obstacle_scale: obstacleScale,
            description: description
        })
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message);
            setTimeout(function () {
                location.reload();
            }, 1200);
        });
}

function deleteObstacle(id) {
    if (!confirm('Yakin ingin menghapus hambatan ini?')) return;

    fetch('/api/obstacles/' + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message);
            setTimeout(function () {
                location.reload();
            }, 1200);
        });
}

function applyFilters() {
    var facilityValue = document.getElementById('facilityFilter').value;
    var obstacleValue = document.getElementById('obstacleFilter').value;
    var routeValue = document.getElementById('routeFilter').value;

    loadFacilities(facilityValue);
    loadObstacles(obstacleValue);
    loadRoutes(routeValue);
}

function resetFilters() {
    document.getElementById('facilityFilter').value = 'all';
    document.getElementById('obstacleFilter').value = 'all';
    document.getElementById('routeFilter').value = 'all';

    loadFacilities();
    loadObstacles();
    loadRoutes();
}

loadFacilities();
loadObstacles();
loadRoutes();
loadZones();
loadStatistics();

function editRouteAttribute(id) {
    var data = routeCache[id];

    var routeName = prompt('Edit nama jalur:', data.route_name);
    if (!routeName) return;

    var score = prompt('Edit skor walkability 0-5:', data.score);
    var category = getCategoryFromScore(score);
    var description = prompt('Edit deskripsi:', data.description);

    fetch('/api/routes/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            route_name: routeName,
            score: score,
            category: category,
            description: description
        })
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message);
            setTimeout(function () {
                location.reload();
            }, 1200);
        });
}

function deleteRoute(id) {
    if (!confirm('Yakin ingin menghapus jalur pedestrian ini?')) return;

    fetch('/api/routes/' + id, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message);
            setTimeout(function () {
                location.reload();
            }, 1200);
        });
}

function editZoneAttribute(id) {
    var data = zoneCache[id];

    var zoneName = prompt('Edit nama zona:', data.zone_name);
    if (!zoneName) return;

    var score = prompt('Edit skor kenyamanan 0-5:', data.score);
    var comfortLevel = getCategoryFromScore(score);
    var description = prompt('Edit deskripsi:', data.description);

    fetch('/api/zones/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            zone_name: zoneName,
            score: score,
            comfort_level: comfortLevel,
            description: description
        })
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message);
            setTimeout(function () {
                location.reload();
            }, 1200);
        });
}

function deleteZone(id) {
    if (!confirm('Yakin ingin menghapus zona kenyamanan ini?')) return;

    fetch('/api/zones/' + id, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message);
            setTimeout(function () {
                location.reload();
            }, 1200);
        });
}

function startEditRouteGeometry(id) {
    var data = routeCache[id];

    geometryEditMode = {
        type: 'route',
        id: id,
        data: data
    };

    showToast('Gambar ulang jalur pedestrian menggunakan ikon garis/polyline pada toolbar peta.');
}

function startEditZoneGeometry(id) {
    var data = zoneCache[id];

    geometryEditMode = {
        type: 'zone',
        id: id,
        data: data
    };

    showToast('Gambar ulang zona kenyamanan menggunakan ikon polygon pada toolbar peta.');
}

function searchFacility() {
    var keyword = document.getElementById('searchInput').value.toLowerCase().trim();

    if (keyword === '') {
        showToast('Masukkan nama fasilitas yang ingin dicari.');
        return;
    }

    var result = facilitySearchIndex.find(function (item) {
        return item.name.toLowerCase().includes(keyword);
    });

    if (!result) {
        showToast('Fasilitas tidak ditemukan pada layer yang sedang tampil.');
        return;
    }

    var latlng = result.layer.getLatLng();

    map.setView(latlng, 18);
    result.layer.openPopup();
}

function resetView() {
    map.setView(initialCenter, initialZoom);
}

document.getElementById('searchInput').addEventListener('keypress', function (event) {
    if (event.key === 'Enter') {
        searchFacility();
    }
});

function showToast(message) {
    var toast = document.getElementById('toast');
    var toastMessage = document.getElementById('toastMessage');

    if (!toast || !toastMessage) {
        console.warn('Toast element tidak ditemukan.');
        return;
    }

    toastMessage.textContent = message;

    toast.classList.add('show');

    clearTimeout(window.toastTimer);

    window.toastTimer = setTimeout(function () {
        toast.classList.remove('show');
    }, 2500);
}

function openAboutModal() {
    var overlay = document.getElementById('aboutModalOverlay');
    if (!overlay) {
        return;
    }

    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeAboutModal() {
    var overlay = document.getElementById('aboutModalOverlay');
    if (!overlay) {
        return;
    }

    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function showAbout() {
    openAboutModal();
}

document.addEventListener('DOMContentLoaded', function () {
    var overlay = document.getElementById('aboutModalOverlay');

    if (overlay) {
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeAboutModal();
            }
        });
    }
});

function popupImage(feature) {
    if (!feature.properties.image_path) {
        return '';
    }

    return `
        <img
            src="/${feature.properties.image_path}"
            alt="Dokumentasi"
            style="width:100%; max-height:140px; object-fit:cover; border-radius:8px; margin:8px 0;"
        >
    `;
}

function popupActions(type, id) {
    var geometryTitle = (type === 'facility' || type === 'obstacle') ? 'Ubah Lokasi' : 'Ubah Bentuk';

    return `
        <div class="popup-actions">
            <button class="popup-action-btn edit" title="Edit" onclick="editFeature('${type}', ${id})">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 0 0 0-1.41l-2.34-2.34a1.003 1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="currentColor"/></svg>
            </button>

            <button class="popup-action-btn upload" title="Upload Foto" onclick="uploadImage('${type}', ${id})">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 20h14a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1h-3.17l-1.84-2.21A2 2 0 0 0 13.46 3H10.54a2 2 0 0 0-1.53.79L7.17 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1zM12 17a4 4 0 1 1 0-8 4 4 0 0 1 0 8z" fill="currentColor"/></svg>
            </button>

            <button class="popup-action-btn geometry" title="${geometryTitle}" onclick="startEditGeometry('${type}', ${id})">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z" fill="currentColor"/></svg>
            </button>

            <button class="popup-action-btn remove" title="Hapus" onclick="deleteFeature('${type}', ${id})">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="currentColor"/></svg>
            </button>
        </div>
    `;
}

function uploadImage(type, id) {
    window.location.href = `/images/${type}/${id}`;
}

function editFeature(type, id) {
    if (type === 'facility') return editFacility(id);
    if (type === 'obstacle') return editObstacle(id);
    if (type === 'route') return editRouteAttribute(id);
    if (type === 'zone') return editZoneAttribute(id);
}

function deleteFeature(type, id) {
    if (type === 'facility') return deleteFacility(id);
    if (type === 'obstacle') return deleteObstacle(id);
    if (type === 'route') return deleteRoute(id);
    if (type === 'zone') return deleteZone(id);
}

function startEditGeometry(type, id) {
    var cacheMap = {
        facility: facilityCache,
        obstacle: obstacleCache,
        route: routeCache,
        zone: zoneCache
    };

    geometryEditMode = {
        type: type,
        id: id,
        data: cacheMap[type][id]
    };

    if (type === 'facility') {
        showToast('Klik ikon marker untuk menggambar ulang lokasi fasilitas.');
    } else if (type === 'obstacle') {
        showToast('Klik ikon marker untuk menggambar ulang lokasi hambatan.');
    } else if (type === 'route') {
        showToast('Gunakan garis/polyline untuk menggambar ulang jalur pedestrian.');
    } else if (type === 'zone') {
        showToast('Gunakan polygon untuk menggambar ulang zona kenyamanan.');
    }
}
