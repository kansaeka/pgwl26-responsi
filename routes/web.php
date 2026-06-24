<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacilityPointController;
use App\Http\Controllers\ObstaclePointController;
use App\Http\Controllers\PedestrianRoutePolylineController;
use App\Http\Controllers\ComfortZonePolygonController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\StatisticController;

Route::get('/', function () {
    return view('webgis');
});

// Facility point
Route::get('/api/facilities', [FacilityPointController::class, 'index']);
Route::post('/api/facilities', [FacilityPointController::class, 'store']);
Route::put('/api/facilities/{id}', [FacilityPointController::class, 'update']);
Route::delete('/api/facilities/{id}', [FacilityPointController::class, 'destroy']);

// Obstacle point
Route::get('/api/obstacles', [ObstaclePointController::class, 'index']);
Route::post('/api/obstacles', [ObstaclePointController::class, 'store']);
Route::put('/api/obstacles/{id}', [ObstaclePointController::class, 'update']);
Route::delete('/api/obstacles/{id}', [ObstaclePointController::class, 'destroy']);

// Pedestrian route polyline
Route::get('/api/routes', [PedestrianRoutePolylineController::class, 'index']);
Route::post('/api/routes', [PedestrianRoutePolylineController::class, 'store']);
Route::put('/api/routes/{id}', [PedestrianRoutePolylineController::class, 'update']);
Route::delete('/api/routes/{id}', [PedestrianRoutePolylineController::class, 'destroy']);

// Comfort zone polygon
Route::get('/api/zones', [ComfortZonePolygonController::class, 'index']);
Route::post('/api/zones', [ComfortZonePolygonController::class, 'store']);
Route::put('/api/zones/{id}', [ComfortZonePolygonController::class, 'update']);
Route::delete('/api/zones/{id}', [ComfortZonePolygonController::class, 'destroy']);

// Statistics
Route::get('/api/statistics', [StatisticController::class, 'index']);

// Image upload
Route::get('/facilities/{id}/image', [ImageUploadController::class, 'facilityForm']);
Route::post('/facilities/{id}/image', [ImageUploadController::class, 'facilityUpload']);

Route::get('/obstacles/{id}/image', [ImageUploadController::class, 'obstacleForm']);
Route::post('/obstacles/{id}/image', [ImageUploadController::class, 'obstacleUpload']);
