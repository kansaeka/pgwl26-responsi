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
        $priority = self::priorityFromSeverity($data['severity']);

        DB::insert("
            INSERT INTO pedestrian_obstacles
            (name, obstacle_type, severity, description, priority_level, recommendation, geom)
            VALUES (?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326))
        ", [
            $data['name'],
            $data['obstacle_type'] ?? null,
            $data['severity'],
            $data['description'] ?? null,
            $priority,
            $data['recommendation'] ?? 'Perlu penataan ulang ruang pedestrian dan pengurangan konflik dengan kendaraan.',
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
        $priority = self::priorityFromSeverity($data['severity']);

        DB::update("
            UPDATE pedestrian_obstacles
            SET name = ?, obstacle_type = ?, severity = ?, description = ?, priority_level = ?, recommendation = ?
            WHERE id = ?
        ", [
            $data['name'],
            $data['obstacle_type'] ?? null,
            $data['severity'],
            $data['description'] ?? null,
            $priority,
            $data['recommendation'] ?? 'Perlu penataan ulang ruang pedestrian dan pengurangan konflik dengan kendaraan.',
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
}
