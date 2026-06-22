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

Route::get('/api/obstacles', function () {
    $data = DB::select("
        SELECT
            id,
            name,
            obstacle_type,
            severity,
            description,
            ST_AsGeoJSON(geom) AS geometry
        FROM pedestrian_obstacles
    ");

    $features = [];

    foreach ($data as $row) {
        $features[] = [
            'type' => 'Feature',
            'geometry' => json_decode($row->geometry),
            'properties' => [
                'id' => $row->id,
                'name' => $row->name,
                'obstacle_type' => $row->obstacle_type,
                'severity' => $row->severity,
                'description' => $row->description
            ]
        ];
    }

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features
    ]);
});

Route::get('/api/routes', function () {
    $data = DB::select("
        SELECT
            id,
            route_name,
            score,
            category,
            description,
            ST_AsGeoJSON(geom) AS geometry
        FROM pedestrian_routes
    ");

    $features = [];

    foreach ($data as $row) {
        $features[] = [
            'type' => 'Feature',
            'geometry' => json_decode($row->geometry),
            'properties' => [
                'id' => $row->id,
                'route_name' => $row->route_name,
                'score' => $row->score,
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

Route::get('/api/zones', function () {
    $data = DB::select("
        SELECT
            id,
            zone_name,
            comfort_level,
            score,
            description,
            ST_AsGeoJSON(geom) AS geometry
        FROM comfort_zones
    ");

    $features = [];

    foreach ($data as $row) {
        $features[] = [
            'type' => 'Feature',
            'geometry' => json_decode($row->geometry),
            'properties' => [
                'id' => $row->id,
                'zone_name' => $row->zone_name,
                'comfort_level' => $row->comfort_level,
                'score' => $row->score,
                'description' => $row->description
            ]
        ];
    }

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features
    ]);
});

Route::get('/api/statistics', function () {
    $facilities = DB::table('facilities')->count();
    $obstacles = DB::table('pedestrian_obstacles')->count();
    $routes = DB::table('pedestrian_routes')->count();
    $zones = DB::table('comfort_zones')->count();

    return response()->json([
        'facilities' => $facilities,
        'obstacles' => $obstacles,
        'routes' => $routes,
        'zones' => $zones
    ]);
});
