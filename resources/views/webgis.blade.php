<!DOCTYPE html>
<html>
<head>
    <title>Walk the Talk</title>
    <meta charset="utf-8">

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

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
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .title-box h3 {
            margin: 0;
            font-size: 18px;
        }

        .title-box p {
            margin: 4px 0 0;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="title-box">
    <h3>Walk the Talk</h3>
    <p>WebGIS Evaluasi Walkability Kawasan UGM</p>
</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    var map = L.map('map').setView([-7.7702, 110.3776], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20,
        attribution: '© OpenStreetMap'
    }).addTo(map);
</script>

<script>
    fetch('/api/facilities')
        .then(response => response.json())
        .then(data => {
            L.geoJSON(data, {
                pointToLayer: function (feature, latlng) {
                    return L.circleMarker(latlng, {
                        radius: 7,
                        fillOpacity: 0.8,
                        weight: 1
                    });
                },
                onEachFeature: function (feature, layer) {
                    layer.bindPopup(`
                        <b>${feature.properties.name}</b><br>
                        Kategori: ${feature.properties.category}<br>
                        ${feature.properties.description}
                    `);
                }
            }).addTo(map);
        });
</script>

</body>
</html>
