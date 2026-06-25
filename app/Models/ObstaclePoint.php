<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ObstaclePoint extends Model
{
    protected $table = 'pedestrian_obstacles';
    public $timestamps = false;

    public static function priorityFromSeverity($severity)
    {
        if ($severity === 'Tinggi') return 'Tinggi';
        if ($severity === 'Sedang') return 'Sedang';
        return 'Rendah';
    }

    public static function geoJson()
    {
        $data = DB::select("
            SELECT
                id,
                name,
                obstacle_type,
                severity,
                description,
                priority_level,
                recommendation,
                image_path,
                obstacle_scale,
                ST_AsGeoJSON(geom) AS geometry
            FROM pedestrian_obstacles
        ");

        $features = [];

        foreach ($data as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row->geometry),
                'properties' => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'obstacle_type' => $row->obstacle_type,
                    'severity' => $row->severity,
                    'description' => $row->description,
                    'priority_level' => $row->priority_level,
                    'recommendation' => $row->recommendation,
                    'obstacle_scale' => $row->obstacle_scale,
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
        $scale = $data['obstacle_scale'] ?? 3;
        $severity = self::severityFromScale($scale);
        $priority = self::priorityFromScale($scale);
        $recommendation = $data['recommendation'] ?? self::recommendationFromScale($scale);

        DB::insert("
        INSERT INTO pedestrian_obstacles
        (name, obstacle_type, severity, obstacle_scale, description, priority_level, recommendation, geom)
        VALUES (?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326))
    ", [
            $data['name'],
            $data['obstacle_type'] ?? null,
            $severity,
            $scale,
            $data['description'] ?? null,
            $priority,
            $recommendation,
            $data['lng'],
            $data['lat']
        ]);

        return [
            'status' => 'success',
            'message' => 'Hambatan berhasil ditambahkan'
        ];
    }

    public static function updateData($id, array $data)
    {
        $scale = $data['obstacle_scale'] ?? 3;
        $severity = self::severityFromScale($scale);
        $priority = self::priorityFromScale($scale);
        $recommendation = $data['recommendation'] ?? self::recommendationFromScale($scale);

        DB::update("
        UPDATE pedestrian_obstacles
        SET name = ?, obstacle_type = ?, severity = ?, obstacle_scale = ?, description = ?, priority_level = ?, recommendation = ?
        WHERE id = ?
    ", [
            $data['name'],
            $data['obstacle_type'] ?? null,
            $severity,
            $scale,
            $data['description'] ?? null,
            $priority,
            $recommendation,
            $id
        ]);

        return [
            'status' => 'success',
            'message' => 'Hambatan berhasil diperbarui'
        ];
    }
    
    public static function deleteData($id)
    {
        DB::delete("DELETE FROM pedestrian_obstacles WHERE id = ?", [$id]);

        return [
            'status' => 'success',
            'message' => 'Hambatan berhasil dihapus'
        ];
    }

    public static function updateGeometry($id, array $data)
    {
        DB::update("
        UPDATE pedestrian_obstacles
        SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326)
        WHERE id = ?
    ", [
            $data['lng'],
            $data['lat'],
            $id
        ]);

        return [
            'status' => 'success',
            'message' => 'Lokasi hambatan berhasil diperbarui'
        ];
    }

    public static function severityFromScale($scale)
    {
        $scale = (int) $scale;

        if ($scale >= 4) {
            return 'Tinggi';
        }

        if ($scale === 3) {
            return 'Sedang';
        }

        return 'Ringan';
    }

    public static function priorityFromScale($scale)
    {
        $scale = (int) $scale;

        if ($scale >= 4) {
            return 'Tinggi';
        }

        if ($scale === 3) {
            return 'Sedang';
        }

        return 'Rendah';
    }

    public static function recommendationFromScale($scale)
    {
        $scale = (int) $scale;

        if ($scale >= 4) {
            return 'Perlu penanganan prioritas seperti perbaikan jalur pedestrian, penertiban hambatan, dan peningkatan keamanan pejalan kaki.';
        }

        if ($scale === 3) {
            return 'Perlu perbaikan sedang seperti penataan jalur, peningkatan kenyamanan, dan pengurangan hambatan fisik.';
        }

        return 'Perlu pemeliharaan ringan dan monitoring berkala agar tidak berkembang menjadi hambatan yang lebih besar.';
    }
}
