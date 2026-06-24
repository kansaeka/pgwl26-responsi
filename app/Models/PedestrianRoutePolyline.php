<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PedestrianRoutePolyline extends Model
{
    protected $table = 'pedestrian_routes';
    public $timestamps = false;

    public static function categoryFromScore($score)
    {
        $score = (int) $score;

        if ($score >= 4) return 'Nyaman';
        if ($score >= 2) return 'Cukup nyaman';
        return 'Kurang nyaman';
    }

    public static function priorityFromScore($score)
    {
        $score = (int) $score;

        if ($score <= 1) return 'Tinggi';
        if ($score <= 3) return 'Sedang';
        return 'Rendah';
    }

    public static function geoJson()
    {
        $data = DB::select("
            SELECT
                id,
                route_name,
                score,
                category,
                description,
                priority_level,
                recommendation,
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
                    'description' => $row->description,
                    'priority_level' => $row->priority_level,
                    'recommendation' => $row->recommendation
                ]
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features
        ];
    }

    public static function createPolyline(array $data)
    {
        $category = self::categoryFromScore($data['score']);
        $priority = self::priorityFromScore($data['score']);

        DB::insert("
            INSERT INTO pedestrian_routes
            (route_name, score, category, description, priority_level, recommendation, geom)
            VALUES (?, ?, ?, ?, ?, ?, ST_SetSRID(ST_GeomFromText(?), 4326))
        ", [
            $data['route_name'],
            $data['score'],
            $category,
            $data['description'] ?? null,
            $priority,
            $data['recommendation'] ?? 'Perlu peningkatan fasilitas pedestrian, penataan crossing, dan penguatan kenyamanan jalur.',
            $data['wkt']
        ]);

        return [
            'status' => 'success',
            'message' => 'Jalur pedestrian berhasil ditambahkan'
        ];
    }

    public static function updateData($id, array $data)
    {
        $category = self::categoryFromScore($data['score']);
        $priority = self::priorityFromScore($data['score']);

        if (!empty($data['wkt'])) {
            DB::update("
                UPDATE pedestrian_routes
                SET route_name = ?, score = ?, category = ?, description = ?, priority_level = ?, recommendation = ?,
                    geom = ST_SetSRID(ST_GeomFromText(?), 4326)
                WHERE id = ?
            ", [
                $data['route_name'],
                $data['score'],
                $category,
                $data['description'] ?? null,
                $priority,
                $data['recommendation'] ?? 'Perlu peningkatan fasilitas pedestrian, penataan crossing, dan penguatan kenyamanan jalur.',
                $data['wkt'],
                $id
            ]);
        } else {
            DB::update("
                UPDATE pedestrian_routes
                SET route_name = ?, score = ?, category = ?, description = ?, priority_level = ?, recommendation = ?
                WHERE id = ?
            ", [
                $data['route_name'],
                $data['score'],
                $category,
                $data['description'] ?? null,
                $priority,
                $data['recommendation'] ?? 'Perlu peningkatan fasilitas pedestrian, penataan crossing, dan penguatan kenyamanan jalur.',
                $id
            ]);
        }

        return [
            'status' => 'success',
            'message' => 'Jalur pedestrian berhasil diperbarui'
        ];
    }

    public static function deleteData($id)
    {
        DB::delete("DELETE FROM pedestrian_routes WHERE id = ?", [$id]);

        return [
            'status' => 'success',
            'message' => 'Jalur pedestrian berhasil dihapus'
        ];
    }
}
