<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ObstaclePoint;

class ObstaclePointController extends Controller
{
    public function index()
    {
        return response()->json(ObstaclePoint::geoJson());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'obstacle_type' => 'nullable|string|max:100',
            'severity' => 'required|in:Ringan,Sedang,Tinggi',
            'description' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        return response()->json(ObstaclePoint::createPoint($validated));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'obstacle_type' => 'nullable|string|max:100',
            'severity' => 'required|in:Ringan,Sedang,Tinggi',
            'description' => 'nullable|string',
            'recommendation' => 'nullable|string',
        ]);

        return response()->json(ObstaclePoint::updateData($id, $validated));
    }

    public function destroy($id)
    {
        return response()->json(ObstaclePoint::deleteData($id));
    }

    public function updateGeometry(Request $request, $id)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        return response()->json(ObstaclePoint::updateGeometry($id, $validated));
    }
}
