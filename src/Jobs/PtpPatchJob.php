<?php

namespace Biigle\Modules\Ptp\Jobs;

use Biigle\Shape;
use Biigle\VolumeFile;
use Biigle\Image;
use Biigle\Jobs\ProcessAnnotatedImage;
use Biigle\Modules\Ptp\PtpAnnotation;
use Biigle\Modules\Ptp\PtpJob as PtpJobModel;
use Illuminate\Support\Facades\Storage;
use FileCache;
use SVG\SVG;
use Exception;

use Jcupitt\Vips\Image as VipsImage;


/**
 * Generates the annotation patches with svg overlays for the ImageGrid in the review page.
 *
 */
class PtpPatchJob extends ProcessAnnotatedImage {


    public function __construct( public Image $image, public int $ptpJobId) {
        parent::__construct(
            file: $image,
            targetDisk: config("ptp.patch_storage_disk"),
        );
    }

    public function handle():void {

        // Don't generate the patches when the PTP job has already been deleted.
        if (!(PtpJobModel::whereKey($this->ptpJobId)->exists())) {
            return;
        }

        FileCache::get($this->image, [$this, "handlePtpFile"]);

    }


    /**
     * Generate and store patches with their SVGs.
     *
     * @param Image $image
     * @param string $path
     * @return void
     * @throws \Jcupitt\Vips\Exception
     */
    public function handlePtpFile(Image $image, string $path): void {

        // All possible polygon annotations belonging to this image
        $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $this->ptpJobId)
            ->where("image_id", $image->id)
            ->get();


        // Only load images with annotations
        if ($ptpAnnotations->isNotEmpty()) {

            $vipsImage = VipsImage::newFromFile($path);


            try {
                foreach ($ptpAnnotations as $ptpAnnotation) {

                    $buffer = $this->getAnnotationPatch($vipsImage, $ptpAnnotation->points, Shape::polygon());

                    $targetPath = $this->getPtpPatchTargetPath($image, $ptpAnnotation, config("largo.patch_format"));
                    $this->storePtpPatch($targetPath, $buffer);


                    $this->createPtpSvg($ptpAnnotation);


                }
            } finally {
                if (!(PtpJobModel::whereKey($this->ptpJobId)->exists())) {
                    foreach ($ptpAnnotations as $ptpAnnotation) {

                        $patchPath = $this->getPtpPatchTargetPath($image, $ptpAnnotation, config("largo.patch_format"));

                        $svgPath = $this->getPtpPatchTargetPath($image, $ptpAnnotation, "svg");

                        Storage::disk($this->targetDisk)->delete([$patchPath, $svgPath]);


                    }
                }
            }





        }
    }


    /**
     * Generate and upload an SVG for the annotation.
     *
     * @param PtpAnnotation $ptpAnnotation
     * @return void
     * @throws Exception
     */
    protected function createPtpSvg(PtpAnnotation $ptpAnnotation): void {

        $points = $ptpAnnotation->points;
        $shape = Shape::polygon();


        $thumbWidth = config('thumbnails.width');
        $thumbHeight = config('thumbnails.height');
        $padding = config('largo.patch_padding');
        $pointPadding = config('largo.point_padding');

        $box = $this->getAnnotationBoundingBox($points, $shape, $pointPadding, $padding);
        $box = $this->ensureBoxAspectRatio($box, $thumbWidth, $thumbHeight);
        $box = $this->makeBoxContained($box, $this->file->width, $this->file->height);

        $svg = new SVG($thumbWidth, $thumbHeight);
        $doc = $svg->getDocument();

        $doc->setAttribute('viewBox', implode(' ', $box));

        $svgAnnotation = $this->getSVGAnnotation($points, $shape);
        $doc->addChild($svgAnnotation);

        $targetPath = $this->getPtpPatchTargetPath($this->file, $ptpAnnotation, "svg");

        if (Storage::disk($this->targetDisk)->put($targetPath, $svg) === false) {
            throw new Exception("Failed to save svg to PTP_PATCH_STORAGE_DISK");
        };

    }


    /**
     * Write image data to storage disk.
     *
     * @param string $path
     * @param string $buffer
     * @return void
     * @throws Exception if patch couldn't be written to storage
     */
    protected function storePtpPatch(string $path, string $buffer): void {

        if (Storage::disk($this->targetDisk)->put($path, $buffer) === false) {
            throw new Exception("Failed to write to PTP_PATCH_STORAGE_DISK");
        }
    }



    /**
     * Combines prefix, UUID and PTP-annotation-id to a storage path for the patches.
     *
     * @param VolumeFile $file
     * @param PtpAnnotation $ptpAnnotation
     * @param string $format
     * @return string
     */
    protected function getPtpPatchTargetPath(VolumeFile $file, PtpAnnotation $ptpAnnotation, string $format): string {

        $prefix = fragment_uuid_path($file->uuid);

        return "$prefix/{$ptpAnnotation->id}.{$format}";

    }


}
