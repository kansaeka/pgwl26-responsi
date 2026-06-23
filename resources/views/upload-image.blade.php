<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <meta charset="utf-8">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
        }

        .container {
            max-width: 520px;
            margin: 60px auto;
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }

        h2 {
            margin-top: 0;
        }

        .info {
            background: #eff6ff;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        input[type="file"] {
            width: 100%;
            margin: 12px 0;
        }

        button, a {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        button {
            border: none;
            background: #1d4ed8;
            color: white;
            cursor: pointer;
        }

        a {
            background: #e5e7eb;
            color: #111827;
            margin-left: 6px;
        }

        img {
            width: 100%;
            max-height: 260px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 14px;
        }

        .error {
            color: #b91c1c;
            font-size: 13px;
        }
    </style>
</head>

<body>

<div class="container">
    <h2>{{ $title }}</h2>

    <div class="info">
        <b>Nama Data:</b><br>
        {{ $data->name ?? $data->route_name ?? $data->zone_name ?? 'Data' }}
    </div>

    @if (!empty($data->image_path))
        <p><b>Gambar saat ini:</b></p>
        <img src="{{ asset($data->image_path) }}" alt="Gambar data">
    @endif

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
        @csrf

        <label>Pilih gambar:</label>
        <input type="file" name="image" accept="image/*" required>

        <button type="submit">Upload Gambar</button>
        <a href="{{ url('/') }}">Kembali ke WebGIS</a>
    </form>
</div>

</body>
</html>
