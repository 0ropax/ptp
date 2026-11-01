<?php

namespace Biigle\Modules\Ptp\Support;


use Biigle\ImageAnnotation;
use Biigle\Shape;
use Biigle\Volume;
use Illuminate\Database\Eloquent\Builder;
use Biigle\Modules\Ptp\PtpJob as PtpJobModel;


/**
 * Helper class to build queries for usage in the PTP conversion workflow.
 */
class PtpPointQuery {

    /**
     * Method to build a query for the point annotations in this volume.
     * The included points are determined by the selected conversion option on the PTP job form.
     *
     * @param Volume $volume
     * @param string $conversionOption
     * @return Builder
     */
    public static function getPointsToConvert(Volume $volume, string $conversionOption): Builder {
        /*$conversionOption = "included";*/

        // All point annotations in volume
        $query = ImageAnnotation::join("images", "image_annotations.image_id", "=", "images.id")
                    ->where("images.volume_id", $volume->id)
                    ->where("image_annotations.shape_id", Shape::pointId());


        if ($conversionOption == "ignored") {
            // Remaining point annotations
            // Exclude deleted source annos
            $successfulPolygonIds = PtpJobModel::join("ptp_annotations", "ptp_annotations.ptp_job_id", "=", "ptp_jobs.id")
                    ->where("ptp_jobs.volume_id", $volume->id)
                    ->whereNotNull("ptp_annotations.annotation_id")
                    ->whereNotNull("ptp_annotations.source_annotation_id")
                    ->distinct()
                    ->pluck("ptp_annotations.source_annotation_id");

            $query->whereNotIn("image_annotations.id", $successfulPolygonIds);

           /* $remainingPointCount = ImageAnnotation::join("images", "image_annotations.image_id", "=", "images.id")
                ->where("images.volume_id", $volume->id)
                ->where("image_annotations.shape_id", Shape::pointId())
                ->whereNotIn("image_annotations.id", $successfulPolygonIds)
                ->count();*/
        }

        return $query;
    }

}
