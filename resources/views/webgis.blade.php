<!DOCTYPE html>
<html>

<head>
    <title>Walk the Talk</title>
    <meta charset="utf-8">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

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
    </style>
</head>

<body>

    <!-- HTML: kotak judul -->
    <div class="title-box">
        <h3>Walk the Talk</h3>
        <p>WebGIS Evaluasi Walkability Kawasan UGM</p>
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
        <h4>Statistik</h4>
        <p>Fasilitas Kampus: <span id="totalFacilities">0</span></p>
        <p>Hambatan Pedestrian: <span id="totalObstacles">0</span></p>
        <p>Jalur Pedestrian: <span id="totalRoutes">0</span></p>
        <p>Zona Kenyamanan: <span id="totalZones">0</span></p>
    </div>

    <!-- Leaflet JS ditaruh sebelum script peta -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

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
        fetch('/api/facilities')
            .then(response => response.json())
            .then(data => {
                L.geoJSON(data, {
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
                        layer.bindPopup(`
                            <b>${feature.properties.name}</b><br>
                            Kategori: ${feature.properties.category}<br>
                            ${feature.properties.description}
                        `);
                    }
                }).addTo(facilitiesLayer);
            });

        // 7. Layer hambatan pedestrian
        fetch('/api/obstacles')
            .then(response => response.json())
            .then(data => {
                L.geoJSON(data, {
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
                        layer.bindPopup(`
                            <b>${feature.properties.name}</b><br>
                            Jenis: ${feature.properties.obstacle_type}<br>
                            Tingkat: ${feature.properties.severity}<br>
                            ${feature.properties.description}
                        `);
                    }
                }).addTo(obstaclesLayer);
            });

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

        // 11. Statistik
        fetch('/api/statistics')
            .then(response => response.json())
            .then(data => {
                document.getElementById('totalFacilities').textContent = data.facilities;
                document.getElementById('totalObstacles').textContent = data.obstacles;
                document.getElementById('totalRoutes').textContent = data.routes;
                document.getElementById('totalZones').textContent = data.zones;
            });
    </script>

</body>

</html>
