<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImageUploadController extends Controller
{
    public function facilityForm($id)
    {
        $data = DB::table('facilities')->where('id', $id)->first();

        if (!$data) {
            abort(404);
        }

        return view('upload-image', [
            'type' => 'facility',
            'title' => 'Upload Gambar Fasilitas Kampus',
            'data' => $data,
            'action' => url('/facilities/' . $id . '/image')
        ]);
    }

    public function facilityUpload(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $file = $request->file('image');
        $filename = 'facility_' . $id . '_' . time() . '.' . $file->getClientOriginalExtension();

        $file->move(public_path('images/facilities'), $filename);

        DB::table('facilities')
            ->where('id', $id)
            ->update([
                'image_path' => 'images/facilities/' . $filename
            ]);

        return redirect('/')->with('success', 'Gambar fasilitas berhasil diunggah.');
    }

    public function obstacleForm($id)
    {
        $data = DB::table('pedestrian_obstacles')->where('id', $id)->first();

        if (!$data) {
            abort(404);
        }

        return view('upload-image', [
            'type' => 'obstacle',
            'title' => 'Upload Gambar Hambatan Pedestrian',
            'data' => $data,
            'action' => url('/obstacles/' . $id . '/image')
        ]);
    }

    public function obstacleUpload(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $file = $request->file('image');
        $filename = 'obstacle_' . $id . '_' . time() . '.' . $file->getClientOriginalExtension();

        $file->move(public_path('images/obstacles'), $filename);

        DB::table('pedestrian_obstacles')
            ->where('id', $id)
            ->update([
                'image_path' => 'images/obstacles/' . $filename
            ]);

        return redirect('/')->with('success', 'Gambar hambatan berhasil diunggah.');
    }
}
