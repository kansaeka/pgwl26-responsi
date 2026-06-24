<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    public function index()
    {
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
    }
}
