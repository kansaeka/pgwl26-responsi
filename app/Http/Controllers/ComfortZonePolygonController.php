<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ComfortZonePolygon;

class ComfortZonePolygonController extends Controller
{
    public function index()
    {
        return response()->json(ComfortZonePolygon::geoJson());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'zone_name' => 'required|string|max:150',
            'score' => 'required|integer|min:0|max:5',
            'description' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'wkt' => 'required|string',
        ]);

        return response()->json(ComfortZonePolygon::createPolygon($validated));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'zone_name' => 'required|string|max:150',
            'score' => 'required|integer|min:0|max:5',
            'description' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'wkt' => 'nullable|string',
        ]);

        return response()->json(ComfortZonePolygon::updateData($id, $validated));
    }

    public function destroy($id)
    {
        return response()->json(ComfortZonePolygon::deleteData($id));
    }

    public function updateGeometry(Request $request, $id)
    {
        $validated = $request->validate([
            'wkt' => 'required|string',
        ]);

        return response()->json(ComfortZonePolygon::updateGeometry($id, $validated));
    }
}
