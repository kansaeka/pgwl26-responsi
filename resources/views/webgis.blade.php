<!DOCTYPE html>
<html>

<head>
    <title>Walk the Talk</title>
    <meta charset="utf-8">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw/dist/leaflet.draw.css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- CSS ditaruh di bagian head -->
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        #map {
            height: 100vh;
            width: 100%;
        }

        .title-box {
            position: absolute;
            top: 15px;
            left: 60px;
            z-index: 1000;
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .title-box h3 {
            margin: 0;
            font-size: 18px;
        }

        .title-box p {
            margin: 4px 0 0;
            font-size: 13px;
        }

        .legend-box {
            position: absolute;
            bottom: 25px;
            left: 20px;
            z-index: 1000;
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            font-size: 13px;
            line-height: 1.5;
        }

        .legend-box h4 {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            margin-right: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .line-green {
            width: 22px;
            height: 4px;
            background: green;
            margin-right: 8px;
        }

        .line-orange {
            width: 22px;
            height: 4px;
            background: orange;
            margin-right: 8px;
        }

        .polygon-box {
            width: 16px;
            height: 16px;
            background: rgba(0, 128, 0, 0.3);
            border: 2px solid green;
            margin-right: 8px;
        }

        .stats-box {
            position: absolute;
            bottom: 25px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            font-size: 13px;
            min-width: 190px;
        }

        .stats-box h4 {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .stats-box p {
            margin: 4px 0;
        }

        .crud-box {
            position: absolute;
            top: 105px;
            left: 60px;
            z-index: 1000;
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            font-size: 13px;
            width: 220px;
        }

        .crud-box h4 {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .crud-box select {
            width: 100%;
            padding: 6px;
            margin-bottom: 6px;
        }

        .crud-box p {
            margin: 4px 0 0;
            font-size: 12px;
        }

        .filter-box {
            position: absolute;
            top: 260px;
            left: 60px;
            z-index: 1000;
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            font-size: 13px;
            width: 220px;
        }

        .filter-box h4 {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .filter-box label {
            display: block;
            margin-top: 8px;
            margin-bottom: 4px;
            font-weight: bold;
        }

        .filter-box select {
            width: 100%;
            padding: 6px;
            margin-bottom: 6px;
        }

        .filter-box button {
            width: 100%;
            padding: 6px;
            margin-top: 8px;
            border: none;
            background: #1d4ed8;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <!-- HTML: kotak judul -->
    <div class="title-box">
        <h3>Walk the Talk</h3>
        <p>WebGIS Evaluasi Walkability Kawasan UGM</p>
    </div>

    <div class="crud-box">
        <h4>Tambah Data</h4>
        <select id="drawType">
            <option value="facility">Fasilitas Kampus</option>
            <option value="obstacle">Hambatan Pedestrian</option>
            <option value="route">Jalur Pedestrian</option>
            <option value="zone">Zona Kenyamanan</option>
        </select>
        <p>Pilih jenis data, lalu gunakan marker, garis, atau polygon pada toolbar peta.</p>
    </div>

    <div class="filter-box">
        <h4>Filter Data</h4>

        <label>Fasilitas Kampus</label>
        <select id="facilityFilter" onchange="applyFilters()">
            <option value="all">Semua Fasilitas</option>
            <option value="Gedung publik">Gedung Publik</option>
            <option value="Fasilitas akademik">Fasilitas Akademik</option>
            <option value="Fasilitas ibadah">Fasilitas Ibadah</option>
            <option value="Fakultas">Fakultas</option>
            <option value="Ruang terbuka">Ruang Terbuka</option>
        </select>

        <label>Hambatan Pedestrian</label>
        <select id="obstacleFilter" onchange="applyFilters()">
            <option value="all">Semua Hambatan</option>
            <option value="Ringan">Ringan</option>
            <option value="Sedang">Sedang</option>
            <option value="Tinggi">Tinggi</option>
        </select>

        <button onclick="resetFilters()">Reset Filter</button>
    </div>

    <!-- HTML: div utama peta -->
    <div id="map"></div>

    <!-- HTML: legenda -->
    <div class="legend-box">
        <h4>Legenda</h4>
        <div class="legend-item">
            <span class="legend-color" style="background:#3b82f6;"></span> Fasilitas Kampus
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background:#ef4444;"></span> Hambatan Pedestrian
        </div>
        <div class="legend-item">
            <span class="line-green"></span> Jalur Nyaman
        </div>
        <div class="legend-item">
            <span class="line-orange"></span> Jalur Cukup Nyaman
        </div>
        <div class="legend-item">
            <span class="polygon-box"></span> Zona Kenyamanan
        </div>
    </div>

    <div class="stats-box">
        <h4>Statistik WebGIS</h4>
        <p>Fasilitas: <span id="totalFacilities">0</span></p>
        <p>Hambatan: <span id="totalObstacles">0</span></p>
        <p>Jalur: <span id="totalRoutes">0</span></p>
        <p>Zona: <span id="totalZones">0</span></p>
        <hr>
        <p>Panjang Jalur: <span id="totalLength">0</span> m</p>
        <p>Luas Zona: <span id="totalArea">0</span> m²</p>
    </div>

    <!-- Leaflet JS ditaruh sebelum script peta -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw/dist/leaflet.draw.js"></script>

    <!-- JS utama peta ditaruh sebelum penutup body -->
    <script>
        // 1. Inisialisasi peta
        var map = L.map('map').setView([-7.7702, 110.3776], 16);

        // 2. Basemap OpenStreetMap
        var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 20,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // 3. Layer group untuk layer control
        var facilitiesLayer = L.layerGroup().addTo(map);
        var obstaclesLayer = L.layerGroup().addTo(map);
        var routesLayer = L.layerGroup().addTo(map);
        var zonesLayer = L.layerGroup().addTo(map);

        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        var facilityCache = {};
        var obstacleCache = {};

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

            fetch('/api/facilities')
                .then(response => response.json())
                .then(data => {
                    L.geoJSON(data, {
                        filter: function(feature) {
                            if (filterCategory === 'all') return true;
                            return feature.properties.category === filterCategory;
                        },
                        pointToLayer: function(feature, latlng) {
                            return L.circleMarker(latlng, {
                                radius: 7,
                                color: '#1d4ed8',
                                fillColor: '#3b82f6',
                                fillOpacity: 0.8,
                                weight: 1
                            });
                        },
                        onEachFeature: function(feature, layer) {
                            facilityCache[feature.properties.id] = feature.properties;

                            layer.bindPopup(`
                        <b>${feature.properties.name}</b><br>
                        Kategori: ${feature.properties.category}<br>
                        ${feature.properties.description}<br><br>
                        <button onclick="editFacility(${feature.properties.id})">Edit</button>
                        <button onclick="deleteFacility(${feature.properties.id})">Hapus</button>
                    `);
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
                        filter: function(feature) {
                            if (filterSeverity === 'all') return true;
                            return feature.properties.severity === filterSeverity;
                        },
                        pointToLayer: function(feature, latlng) {
                            return L.circleMarker(latlng, {
                                radius: 7,
                                color: '#991b1b',
                                fillColor: '#ef4444',
                                fillOpacity: 0.9,
                                weight: 1
                            });
                        },
                        onEachFeature: function(feature, layer) {
                            obstacleCache[feature.properties.id] = feature.properties;

                            layer.bindPopup(`
                        <b>${feature.properties.name}</b><br>
                        Jenis: ${feature.properties.obstacle_type}<br>
                        Tingkat: ${feature.properties.severity}<br>
                        ${feature.properties.description}<br><br>
                        <button onclick="editObstacle(${feature.properties.id})">Edit</button>
                        <button onclick="deleteObstacle(${feature.properties.id})">Hapus</button>
                    `);
                        }
                    }).addTo(obstaclesLayer);
                });
        }

        // 8. Layer jalur pedestrian
        fetch('/api/routes')
            .then(response => response.json())
            .then(data => {
                L.geoJSON(data, {
                    style: function(feature) {
                        return {
                            color: routeColor(feature.properties.category),
                            weight: 5,
                            opacity: 0.85
                        };
                    },
                    onEachFeature: function(feature, layer) {
                        layer.bindPopup(`
                            <b>${feature.properties.route_name}</b><br>
                            Kategori: ${feature.properties.category}<br>
                            Skor: ${feature.properties.score}<br>
                            ${feature.properties.description}
                        `);
                    }
                }).addTo(routesLayer);
            });

        // 9. Layer zona kenyamanan
        fetch('/api/zones')
            .then(response => response.json())
            .then(data => {
                L.geoJSON(data, {
                    style: function(feature) {
                        return {
                            color: zoneColor(feature.properties.comfort_level),
                            fillColor: zoneColor(feature.properties.comfort_level),
                            fillOpacity: 0.25,
                            weight: 2
                        };
                    },
                    onEachFeature: function(feature, layer) {
                        layer.bindPopup(`
                            <b>${feature.properties.zone_name}</b><br>
                            Tingkat kenyamanan: ${feature.properties.comfort_level}<br>
                            Skor: ${feature.properties.score}<br>
                            ${feature.properties.description}
                        `);
                    }
                }).addTo(zonesLayer);
            });

        // 10. Layer control
        var overlayMaps = {
            "Fasilitas Kampus": facilitiesLayer,
            "Hambatan Pedestrian": obstaclesLayer,
            "Jalur Pedestrian": routesLayer,
            "Zona Kenyamanan": zonesLayer
        };

        L.control.layers(null, overlayMaps, {
            collapsed: false
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

            var coordinates = latlngs.map(function(latlng) {
                return latlng.lng + ' ' + latlng.lat;
            }).join(', ');

            return 'LINESTRING(' + coordinates + ')';
        }

        function polygonToWKT(layer) {
            var latlngs = layer.getLatLngs()[0];

            var coordinates = latlngs.map(function(latlng) {
                return latlng.lng + ' ' + latlng.lat;
            });

            // tutup polygon dengan koordinat pertama
            coordinates.push(latlngs[0].lng + ' ' + latlngs[0].lat);

            return 'POLYGON((' + coordinates.join(', ') + '))';
        }

        map.on(L.Draw.Event.CREATED, function(event) {
            var layer = event.layer;
            var layerType = event.layerType;
            var drawType = document.getElementById('drawType').value;

            // Tambah fasilitas kampus
            if (drawType === 'facility') {
                if (layerType !== 'marker') {
                    alert('Untuk fasilitas kampus, gunakan marker/titik.');
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
                        alert(data.message);
                        location.reload();
                    });
            }

            // Tambah hambatan pedestrian
            if (drawType === 'obstacle') {
                if (layerType !== 'marker') {
                    alert('Untuk hambatan pedestrian, gunakan marker/titik.');
                    return;
                }

                var latlng = layer.getLatLng();

                var name = prompt('Nama hambatan:');
                if (!name) return;

                var obstacleType = prompt('Jenis hambatan:', 'Hambatan Pedestrian');
                var severity = prompt('Tingkat hambatan:', 'Sedang');
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
                            severity: severity,
                            description: description,
                            lat: latlng.lat,
                            lng: latlng.lng
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        location.reload();
                    });
            }

            // Tambah jalur pedestrian
            if (drawType === 'route') {
                if (layerType !== 'polyline') {
                    alert('Untuk jalur pedestrian, gunakan garis/polyline.');
                    return;
                }

                var routeName = prompt('Nama jalur pedestrian:');
                if (!routeName) return;

                var score = prompt('Skor walkability 0-5:', '3');
                var category = getCategoryFromScore(score);
                var description = prompt('Deskripsi:', 'Jalur pedestrian kawasan UGM');
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
                        alert(data.message);
                        location.reload();
                    });
            }

            // Tambah zona kenyamanan
            if (drawType === 'zone') {
                if (layerType !== 'polygon') {
                    alert('Untuk zona kenyamanan, gunakan polygon/area.');
                    return;
                }

                var zoneName = prompt('Nama zona kenyamanan:');
                if (!zoneName) return;

                var score = prompt('Skor kenyamanan 0-5:', '3');
                var comfortLevel = getCategoryFromScore(score);
                var description = prompt('Deskripsi:', 'Zona kenyamanan pedestrian kawasan UGM');
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
                        alert(data.message);
                        location.reload();
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

        loadStatistics();

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
                    alert(data.message);
                    location.reload();
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
                    alert(data.message);
                    location.reload();
                });
        }

        function editObstacle(id) {
            var data = obstacleCache[id];

            var name = prompt('Edit nama hambatan:', data.name);
            if (!name) return;

            var obstacleType = prompt('Edit jenis hambatan:', data.obstacle_type);
            var severity = prompt('Edit tingkat hambatan:', data.severity);
            var description = prompt('Edit deskripsi:', data.description);

            fetch('/api/obstacles/' + id, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        name: name,
                        obstacle_type: obstacleType,
                        severity: severity,
                        description: description
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
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
                    alert(data.message);
                    location.reload();
                });
        }

        function applyFilters() {
            var facilityValue = document.getElementById('facilityFilter').value;
            var obstacleValue = document.getElementById('obstacleFilter').value;

            loadFacilities(facilityValue);
            loadObstacles(obstacleValue);
        }

        function resetFilters() {
            document.getElementById('facilityFilter').value = 'all';
            document.getElementById('obstacleFilter').value = 'all';

            loadFacilities();
            loadObstacles();
        }

        loadFacilities();
        loadObstacles();
    </script>

</body>

</html>
