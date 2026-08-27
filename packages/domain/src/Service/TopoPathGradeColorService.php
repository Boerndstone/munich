<?php

namespace App\Service;

/**
 * Stroke colors for topo paths, aligned with rock_grade_chart_controller.js gradeColorAt (light mode).
 */
class TopoPathGradeColorService
{
    /** UIAA buckets 3–11 → restrained heat-scale hex values, mirrored in frontend grade tokens/charts. */
    private const BUCKET_HEX = [
        3 => '#059669',  // emerald-600
        4 => '#16a34a',  // green-600
        5 => '#65a30d',  // lime-600
        6 => '#d97706',  // amber-600
        7 => '#ea580c',  // orange-600
        8 => '#c2410c',  // orange-700
        9 => '#dc2626',  // red-600
        10 => '#e11d48', // rose-600
        11 => '#4338ca', // indigo-700
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
