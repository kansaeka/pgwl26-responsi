<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('webgis');
});

use Illuminate\Support\Facades\DB;

Route::get('/api/facilities', function () {
    $data = DB::select("
        SELECT
            id,
            name,
            category,
            description,
            ST_AsGeoJSON(geom) AS geometry
        FROM facilities
    ");

    $features = [];

    foreach ($data as $row) {
        $features[] = [
            'type' => 'Feature',
            'geometry' => json_decode($row->geometry),
            'properties' => [
                'id' => $row->id,
                'name' => $row->name,
                'category' => $row->category,
                'description' => $row->description
            ]
        ];
    }

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features
    ]);
});
