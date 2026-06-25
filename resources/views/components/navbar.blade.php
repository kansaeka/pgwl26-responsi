<nav class="top-navbar">
    <div class="nav-brand">
        <span class="nav-title">Walk the Talk</span>
        <span class="nav-subtitle">WebGIS Evaluasi Walkability Kawasan UGM</span>
    </div>

    <div class="nav-actions">
        <button type="button" onclick="resetView()">Reset View</button>
        <button id="aboutMenuButton" type="button" class="about-menu-button" onclick="showAbout()">Tentang</button>

        <a href="https://github.com/kansaeka/pgwl26-responsi" target="_blank">
            GitHub
        </a>

        <form action="{{ route('logout') }}" method="POST" class="logout-form">
            @csrf
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>
</nav>
