<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FacilityPoint extends Model
{
    protected $table = 'facilities';
    public $timestamps = false;

    public static function geoJson()
    {
        $data = DB::select("
            SELECT
                id,
                name,
                category,
                description,
                image_path,
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
                    'description' => $row->description,
                    'image_path' => $row->image_path
                ]
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features
        ];
    }

    public static function createPoint(array $data)
    {
        DB::insert("
            INSERT INTO facilities (name, category, description, geom)
            VALUES (?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326))
        ", [
            $data['name'],
            $data['category'] ?? null,
            $data['description'] ?? null,
            $data['lng'],
            $data['lat']
        ]);

        return [
            'status' => 'success',
            'message' => 'Fasilitas berhasil ditambahkan'
        ];
    }

    public static function updateData($id, array $data)
    {
        DB::update("
            UPDATE facilities
            SET name = ?, category = ?, description = ?
            WHERE id = ?
        ", [
            $data['name'],
            $data['category'] ?? null,
            $data['description'] ?? null,
            $id
        ]);

        return [
            'status' => 'success',
            'message' => 'Fasilitas berhasil diperbarui'
        ];
    }

    public static function deleteData($id)
    {
        DB::delete("DELETE FROM facilities WHERE id = ?", [$id]);

        return [
            'status' => 'success',
            'message' => 'Fasilitas berhasil dihapus'
        ];
    }

    public static function updateGeometry($id, array $data)
    {
        DB::update("
        UPDATE facilities
        SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326)
        WHERE id = ?
    ", [
            $data['lng'],
            $data['lat'],
            $id
        ]);

        return [
            'status' => 'success',
            'message' => 'Lokasi fasilitas berhasil diperbarui'
        ];
    }
}
