<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ComfortZonePolygon extends Model
{
    protected $table = 'comfort_zones';
    public $timestamps = false;

    public static function comfortFromScore($score)
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
                zone_name,
                comfort_level,
                score,
                description,
                priority_level,
                recommendation,
                image_path,
                ST_AsGeoJSON(geom) AS geometry
            FROM comfort_zones
        ");

        $features = [];

        foreach ($data as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row->geometry),
                'properties' => [
                    'id' => $row->id,
                    'zone_name' => $row->zone_name,
                    'comfort_level' => $row->comfort_level,
                    'score' => $row->score,
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

    public static function createPolygon(array $data)
    {
        $comfortLevel = self::comfortFromScore($data['score']);
        $priority = self::priorityFromScore($data['score']);

        DB::insert("
            INSERT INTO comfort_zones
            (zone_name, comfort_level, score, description, priority_level, recommendation, geom)
            VALUES (?, ?, ?, ?, ?, ?, ST_SetSRID(ST_GeomFromText(?), 4326))
        ", [
            $data['zone_name'],
            $comfortLevel,
            $data['score'],
            $data['description'] ?? null,
            $priority,
            $data['recommendation'] ?? 'Perlu peningkatan teduhan, pengurangan hambatan fisik, dan penataan ruang pejalan kaki.',
            $data['wkt']
        ]);

        return [
            'status' => 'success',
            'message' => 'Zona kenyamanan berhasil ditambahkan'
        ];
    }

    public static function updateData($id, array $data)
    {
        $comfortLevel = self::comfortFromScore($data['score']);
        $priority = self::priorityFromScore($data['score']);

        if (!empty($data['wkt'])) {
            DB::update("
                UPDATE comfort_zones
                SET zone_name = ?, comfort_level = ?, score = ?, description = ?, priority_level = ?, recommendation = ?,
                    geom = ST_SetSRID(ST_GeomFromText(?), 4326)
                WHERE id = ?
            ", [
                $data['zone_name'],
                $comfortLevel,
                $data['score'],
                $data['description'] ?? null,
                $priority,
                $data['recommendation'] ?? 'Perlu peningkatan teduhan, pengurangan hambatan fisik, dan penataan ruang pejalan kaki.',
                $data['wkt'],
                $id
            ]);
        } else {
            DB::update("
                UPDATE comfort_zones
                SET zone_name = ?, comfort_level = ?, score = ?, description = ?, priority_level = ?, recommendation = ?
                WHERE id = ?
            ", [
                $data['zone_name'],
                $comfortLevel,
                $data['score'],
                $data['description'] ?? null,
                $priority,
                $data['recommendation'] ?? 'Perlu peningkatan teduhan, pengurangan hambatan fisik, dan penataan ruang pejalan kaki.',
                $id
            ]);
        }

        return [
            'status' => 'success',
            'message' => 'Zona kenyamanan berhasil diperbarui'
        ];
    }

    public static function deleteData($id)
    {
        DB::delete("DELETE FROM comfort_zones WHERE id = ?", [$id]);

        return [
            'status' => 'success',
            'message' => 'Zona kenyamanan berhasil dihapus'
        ];
    }

    public static function updateGeometry($id, array $data)
    {
        DB::update("
        UPDATE comfort_zones
        SET geom = ST_SetSRID(ST_GeomFromText(?), 4326)
        WHERE id = ?
    ", [
            $data['wkt'],
            $id
        ]);

        return [
            'status' => 'success',
            'message' => 'Bentuk zona kenyamanan berhasil diperbarui'
        ];
    }
}
