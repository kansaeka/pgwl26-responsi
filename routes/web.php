<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('webgis');
});

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

    $routeLength = DB::selectOne("
        SELECT COALESCE(SUM(ST_Length(geom::geography)), 0) AS total_length
        FROM pedestrian_routes
    ");

    $zoneArea = DB::selectOne("
        SELECT COALESCE(SUM(ST_Area(geom::geography)), 0) AS total_area
        FROM comfort_zones
    ");

    return response()->json([
        'facilities' => $facilities,
        'obstacles' => $obstacles,
        'routes' => $routes,
        'zones' => $zones,
        'route_length' => round($routeLength->total_length, 2),
        'zone_area' => round($zoneArea->total_area, 2)
    ]);
});

Route::post('/api/facilities', function (Request $request) {
    DB::insert("
        INSERT INTO facilities (name, category, description, geom)
        VALUES (?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326))
    ", [
        $request->name,
        $request->category,
        $request->description,
        $request->lng,
        $request->lat
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Fasilitas berhasil ditambahkan'
    ]);
});

Route::put('/api/facilities/{id}', function (Request $request, $id) {
    DB::update("
        UPDATE facilities
        SET name = ?, category = ?, description = ?
        WHERE id = ?
    ", [
        $request->name,
        $request->category,
        $request->description,
        $id
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Fasilitas berhasil diperbarui'
    ]);
});

Route::delete('/api/facilities/{id}', function ($id) {
    DB::delete("DELETE FROM facilities WHERE id = ?", [$id]);

    return response()->json([
        'status' => 'success',
        'message' => 'Fasilitas berhasil dihapus'
    ]);
});

Route::post('/api/obstacles', function (Request $request) {
    DB::insert("
        INSERT INTO pedestrian_obstacles (name, obstacle_type, severity, description, geom)
        VALUES (?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326))
    ", [
        $request->name,
        $request->obstacle_type,
        $request->severity,
        $request->description,
        $request->lng,
        $request->lat
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Hambatan berhasil ditambahkan'
    ]);
});

Route::put('/api/obstacles/{id}', function (Request $request, $id) {
    DB::update("
        UPDATE pedestrian_obstacles
        SET name = ?, obstacle_type = ?, severity = ?, description = ?
        WHERE id = ?
    ", [
        $request->name,
        $request->obstacle_type,
        $request->severity,
        $request->description,
        $id
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Hambatan berhasil diperbarui'
    ]);
});

Route::delete('/api/obstacles/{id}', function ($id) {
    DB::delete("DELETE FROM pedestrian_obstacles WHERE id = ?", [$id]);

    return response()->json([
        'status' => 'success',
        'message' => 'Hambatan berhasil dihapus'
    ]);
});
