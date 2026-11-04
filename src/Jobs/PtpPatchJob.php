<?php

namespace Biigle\Modules\Ptp\Jobs;

use Biigle\Shape;
use Illuminate\Support\Facades\Storage;
use Biigle\Modules\Ptp\PtpAnnotation;
use FileCache;
use Biigle\VolumeFile;
use Illuminate\Queue\SerializesModels;
use Biigle\Image;
use Biigle\Jobs\ProcessAnnotatedImage;
use SVG\SVG;
use Exception;

use Jcupitt\Vips\Image as VipsImage;
use Biigle\Modules\Ptp\PtpJob as PtpJobModel;


/**
 *
 */
class PtpPatchJob extends ProcessAnnotatedImage {

    use SerializesModels;


    public function __construct( public Image $image, public int $ptpJobId) {
        parent::__construct(
            file: $image,
            targetDisk: config("ptp.patch_storage_disk"),
        );
    }

    public function handle():void {

        // Callback
        FileCache::get($this->image, [$this, "handlePtpFile"]);

        \Log::info("PTP_PATCH_HANDLE_AFTER", ["image_id" => $this->image->id, "ptp_job_id" => $this->ptpJobId]);
    }


    /**
     *
     *
     * @param Image $image
     * @param string $path
     * @return void
     * @throws \Jcupitt\Vips\Exception
     */
    public function handlePtpFile(Image $image, string $path): void {

        // All possible polygon annotations belonging to this image
        /*$ptpAnnotations = PtpAnnotation::join("image_annotations", "ptp_annotations.source_annotation_id", "=", "image_annotations.id")
                ->where("ptp_annotations.ptp_job_id", $this->ptpJobId)
                ->where("image_annotations.image_id", $image->id)
                ->select("ptp_annotations.*")
                ->get();*/

        $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $this->ptpJobId)
            ->where("image_id", $image->id)
            ->get();


        // Only load images with annotations
        if ($ptpAnnotations->isNotEmpty()) {

            $vipsImage = VipsImage::newFromFile($path);

            foreach ($ptpAnnotations as $ptpAnnotation) {

                $buffer = $this->getAnnotationPatch($vipsImage, $ptpAnnotation->points, Shape::polygon());

                $targetPath = $this->getPtpPatchTargetPath($image, $ptpAnnotation, config("largo.patch_format"));
                $this->storePtpPatch($targetPath, $buffer);


                $this->createPtpSvg($ptpAnnotation, $image);

                /*\Log::info("PTP_PATCH_STORAGE_DISK", [ "disk" => config("ptp.patch_storage_disk"),
                    "path"=> $targetPath, "exists" => Storage::disk(config("ptp.patch_storage_disk"))->exists($targetPath),]);*/
            }
        }
    //elternjob status setzen danach TODO
        PtpJobModel::where("id", $this->ptpJobId)->update(["status" => "waiting-for-review"]); // TODO triggert zu früh weil noch nicht alle bilder durch sind
    }


    /**
     * Generate and upload an SVG for the annotation.
     *
     * @param PtpAnnotation $ptpAnnotation
     * @param Image $image
     * @return void
     * @throws Exception
     */
    protected function createPtpSvg(PtpAnnotation $ptpAnnotation, Image $image): void {

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

        \Log::info("PTP_SVG_GANZ", ["path" => $targetPath, "exists" => Storage::disk($this->targetDisk)->exists($targetPath)]);

    }


    /**
     * Write image data to storage disk.
     *
     * @param string $path
     * @param string $buffer
     * @return void
     * @throws Exception if
     */
    protected function storePtpPatch(string $path, string $buffer): void {

        if (Storage::disk($this->targetDisk)->put($path, $buffer) === false) {
            throw new Exception("Failed to write to PTP_PATCH_STORAGE_DISK");
        }

     /*   \Log::info("PTP_STORAGE", ["disk" => $disk, "config" =>config("Filesys : .{$disk}")]);*/
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
