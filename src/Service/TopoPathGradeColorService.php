<?php

namespace App\Service;

/**
 * Stroke colors for topo paths, aligned with rock_grade_chart_controller.js gradeColorAt (light mode).
 */
class TopoPathGradeColorService
{
    /** UIAA buckets 3–11 → hex (Tailwind emerald/green/lime/yellow/amber/orange/red/rose, same as grade chart). */
    private const BUCKET_HEX = [
        3 => '#10b981',  // emerald-500
        4 => '#22c55e',  // green-500
        5 => '#84cc16',  // lime-500
        6 => '#eab308',  // yellow-500
        7 => '#f59e0b',  // amber-500
        8 => '#f97316',  // orange-500
        9 => '#ef4444',  // red-500
        10 => '#e11d48', // rose-600
        11 => '#9f1239', // rose-800
    ];

    /** Projects / unmapped — black (per site topo helper convention). */
    private const FALLBACK_HEX = '#000000';

    public function strokeHexForGrade(?string $grade): string
    {
        $bucket = GradeTranslationService::uiaaChartBucketForGrade($grade);

        return $this->strokeHexForBucket($bucket);
    }

    public function strokeHexForBucket(?int $bucket): string
    {
        if ($bucket === null) {
            return self::FALLBACK_HEX;
        }

        return self::BUCKET_HEX[$bucket] ?? self::FALLBACK_HEX;
    }
}
