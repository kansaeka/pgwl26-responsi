<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PedestrianRoutePolyline;

class PedestrianRoutePolylineController extends Controller
{
    public function index()
    {
        return response()->json(PedestrianRoutePolyline::geoJson());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'route_name' => 'required|string|max:150',
            'score' => 'required|integer|min:0|max:5',
            'description' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'wkt' => 'required|string',
        ]);

        return response()->json(PedestrianRoutePolyline::createPolyline($validated));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'route_name' => 'required|string|max:150',
            'score' => 'required|integer|min:0|max:5',
            'description' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'wkt' => 'nullable|string',
        ]);

        return response()->json(PedestrianRoutePolyline::updateData($id, $validated));
    }

    public function destroy($id)
    {
        return response()->json(PedestrianRoutePolyline::deleteData($id));
    }

    public function updateGeometry(Request $request, $id)
    {
        $validated = $request->validate([
            'wkt' => 'required|string',
        ]);

        return response()->json(PedestrianRoutePolyline::updateGeometry($id, $validated));
    }
}
