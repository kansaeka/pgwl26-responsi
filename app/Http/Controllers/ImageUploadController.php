<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImageUploadController extends Controller
{
    private function config($type)
    {
        return match ($type) {
            'facility' => [
                'table' => 'facilities',
                'folder' => 'facilities',
                'title' => 'Upload Gambar Fasilitas Kampus',
            ],
            'obstacle' => [
                'table' => 'pedestrian_obstacles',
                'folder' => 'obstacles',
                'title' => 'Upload Gambar Hambatan Pedestrian',
            ],
            'route' => [
                'table' => 'pedestrian_routes',
                'folder' => 'routes',
                'title' => 'Upload Gambar Jalur Pedestrian',
            ],
            'zone' => [
                'table' => 'comfort_zones',
                'folder' => 'zones',
                'title' => 'Upload Gambar Zona Kenyamanan',
            ],
            default => abort(404),
        };
    }

    public function form($type, $id)
    {
        $config = $this->config($type);

        $data = DB::table($config['table'])->where('id', $id)->first();

        if (!$data) {
            abort(404);
        }

        return view('upload-image', [
            'type' => $type,
            'title' => $config['title'],
            'data' => $data,
            'action' => url('/images/' . $type . '/' . $id)
        ]);
    }

    public function upload(Request $request, $type, $id)
    {
        $config = $this->config($type);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $folderPath = public_path('images/' . $config['folder']);
        File::ensureDirectoryExists($folderPath);

        $file = $request->file('image');
        $filename = $type . '_' . $id . '_' . time() . '.' . $file->getClientOriginalExtension();

        $file->move($folderPath, $filename);

        $imagePath = 'images/' . $config['folder'] . '/' . $filename;

        DB::table($config['table'])
            ->where('id', $id)
            ->update([
                'image_path' => $imagePath
            ]);

        return redirect('/')->with('success', 'Gambar berhasil diunggah.');
    }
}
