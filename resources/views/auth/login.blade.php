<!DOCTYPE html>
<html>
<head>
    <title>Login | Walk the Talk</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body>

<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            <h1>Walk the Talk</h1>
            <p>WebGIS Evaluasi Walkability Kawasan UGM</p>
        </div>

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" required autofocus>

            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password" required>

            <div class="remember-row">
                <label>
                    <input type="checkbox" name="remember">
                    Ingat saya
                </label>
            </div>

            <button type="submit">Masuk ke WebGIS</button>
        </form>

        <div class="login-footer">
            <p>Laravel • Leaflet • PostgreSQL/PostGIS</p>
        </div>
    </div>
</div>

</body>
</html>
