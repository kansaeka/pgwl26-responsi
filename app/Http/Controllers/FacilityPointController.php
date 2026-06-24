<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacilityPoint;

class FacilityPointController extends Controller
{
    public function index()
    {
        return response()->json(FacilityPoint::geoJson());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        return response()->json(FacilityPoint::createPoint($validated));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        return response()->json(FacilityPoint::updateData($id, $validated));
    }

    public function destroy($id)
    {
        return response()->json(FacilityPoint::deleteData($id));
    }

    public function updateGeometry(Request $request, $id)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        return response()->json(FacilityPoint::updateGeometry($id, $validated));
    }
}
