<!DOCTYPE html>
<html>

<head>
    <title>Walk the Talk</title>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw/dist/leaflet.draw.css" />
    <link rel="stylesheet" href="{{ asset('css/webgis.css') }}">

</head>

<body>

    @include('components.navbar')

    <!-- Peta utama -->
    <div id="map"></div>

    <!-- Sidebar kiri -->
    <aside class="left-sidebar">

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

            <label>Jalur Pedestrian</label>
            <select id="routeFilter" onchange="applyFilters()">
                <option value="all">Semua Jalur</option>
                <option value="Nyaman">Nyaman</option>
                <option value="Cukup nyaman">Cukup Nyaman</option>
                <option value="Kurang nyaman">Kurang Nyaman</option>
            </select>

            <button onclick="resetFilters()">Reset Filter</button>
        </div>

        <div class="search-box">
            <h4>Pencarian</h4>
            <input type="text" id="searchInput" placeholder="Cari fasilitas kampus...">
            <button onclick="searchFacility()">Cari</button>
            <button onclick="resetView()" class="secondary-button">Reset View</button>
        </div>

    </aside>

    <!-- Sidebar kanan -->
    <aside class="right-sidebar">

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
                <span class="line-red"></span> Jalur Kurang Nyaman
            </div>

            <div class="legend-item">
                <span class="polygon-box"></span> Zona Kenyamanan
            </div>

            <hr>

            <div class="score-info">
                <b>Skor Walkability</b>
                <p>4–5 = Nyaman</p>
                <p>2–3 = Cukup Nyaman</p>
                <p>0–1 = Kurang Nyaman</p>

                <b>Indikator</b>
                <ol>
                    <li>Jalur pedestrian tersedia</li>
                    <li>Aman dari kendaraan</li>
                    <li>Tersedia teduhan</li>
                    <li>Dekat fasilitas kampus</li>
                    <li>Minim hambatan fisik</li>
                </ol>
            </div>
        </div>

    </aside>

    <div id="toast" class="toast-box">
        <strong>WalkTheTalk</strong>
        <p id="toastMessage">Pesan berhasil ditampilkan.</p>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw/dist/leaflet.draw.js"></script>
    <script src="{{ asset('js/webgis.js') }}"></script>

</body>

</html>
