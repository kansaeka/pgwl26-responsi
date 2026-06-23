// 1. Inisialisasi peta
        var initialCenter = [-7.7702, 110.3776];
        var initialZoom = 16;

        var map = L.map('map').setView(initialCenter, initialZoom);

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
        function loadRoutes() {
            routesLayer.clearLayers();
            routeCache = {};

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
                            routeCache[feature.properties.id] = feature.properties;

                            layer.bindPopup(`
                        <b>${feature.properties.route_name}</b><br>
                        Kategori: ${feature.properties.category}<br>
                        Skor: ${feature.properties.score}<br>
                        ${feature.properties.description}<br><br>
                        <button onclick="editRouteAttribute(${feature.properties.id})">Edit Atribut</button>
                        <button onclick="startEditRouteGeometry(${feature.properties.id})">Edit Bentuk</button>
                        <button onclick="deleteRoute(${feature.properties.id})">Hapus</button>
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
                        style: function(feature) {
                            return {
                                color: zoneColor(feature.properties.comfort_level),
                                fillColor: zoneColor(feature.properties.comfort_level),
                                fillOpacity: 0.25,
                                weight: 2
                            };
                        },
                        onEachFeature: function(feature, layer) {
                            zoneCache[feature.properties.id] = feature.properties;

                            layer.bindPopup(`
                        <b>${feature.properties.zone_name}</b><br>
                        Tingkat kenyamanan: ${feature.properties.comfort_level}<br>
                        Skor: ${feature.properties.score}<br>
                        ${feature.properties.description}<br><br>
                        <button onclick="editZoneAttribute(${feature.properties.id})">Edit Atribut</button>
                        <button onclick="startEditZoneGeometry(${feature.properties.id})">Edit Bentuk</button>
                        <button onclick="deleteZone(${feature.properties.id})">Hapus</button>
                    `);
                        }
                    }).addTo(zonesLayer);
                });
        }

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

            // Mode edit bentuk/geometri
            if (geometryEditMode !== null) {
                if (geometryEditMode.type === 'route') {
                    if (layerType !== 'polyline') {
                        alert('Untuk mengedit bentuk jalur, gunakan garis/polyline.');
                        return;
                    }

                    var oldData = geometryEditMode.data;
                    var wkt = polylineToWKT(layer);

                    fetch('/api/routes/' + geometryEditMode.id, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                route_name: oldData.route_name,
                                score: oldData.score,
                                category: oldData.category,
                                description: oldData.description,
                                wkt: wkt
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            geometryEditMode = null;
                            alert(data.message);
                            location.reload();
                        });

                    return;
                }

                if (geometryEditMode.type === 'zone') {
                    if (layerType !== 'polygon') {
                        alert('Untuk mengedit bentuk zona, gunakan polygon/area.');
                        return;
                    }

                    var oldData = geometryEditMode.data;
                    var wkt = polygonToWKT(layer);

                    fetch('/api/zones/' + geometryEditMode.id, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                zone_name: oldData.zone_name,
                                score: oldData.score,
                                comfort_level: oldData.comfort_level,
                                description: oldData.description,
                                wkt: wkt
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            geometryEditMode = null;
                            alert(data.message);
                            location.reload();
                        });

                    return;
                }
            }

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
                    alert(data.message);
                    location.reload();
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
                    alert(data.message);
                    location.reload();
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
                    alert(data.message);
                    location.reload();
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
                    alert(data.message);
                    location.reload();
                });
        }

        function startEditRouteGeometry(id) {
            var data = routeCache[id];

            geometryEditMode = {
                type: 'route',
                id: id,
                data: data
            };

            alert('Gambar ulang jalur pedestrian menggunakan ikon garis/polyline pada toolbar peta.');
        }

        function startEditZoneGeometry(id) {
            var data = zoneCache[id];

            geometryEditMode = {
                type: 'zone',
                id: id,
                data: data
            };

            alert('Gambar ulang zona kenyamanan menggunakan ikon polygon pada toolbar peta.');
        }

        function searchFacility() {
            var keyword = document.getElementById('searchInput').value.toLowerCase().trim();

            if (keyword === '') {
                alert('Masukkan nama fasilitas yang ingin dicari.');
                return;
            }

            var result = facilitySearchIndex.find(function(item) {
                return item.name.toLowerCase().includes(keyword);
            });

            if (!result) {
                alert('Fasilitas tidak ditemukan pada layer yang sedang tampil.');
                return;
            }

            var latlng = result.layer.getLatLng();

            map.setView(latlng, 18);
            result.layer.openPopup();
        }

        function resetView() {
            map.setView(initialCenter, initialZoom);
        }

        document.getElementById('searchInput').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchFacility();
            }
        });
