<?php

namespace Biigle\Modules\Ptp\Jobs;

use Biigle\Shape;
use Illuminate\Support\Facades\Storage;
use Biigle\Modules\Ptp\PtpAnnotation;
use FileCache;
use Biigle\Jobs\Job;
use Biigle\VolumeFile;
use Illuminate\Queue\SerializesModels;
use Biigle\Image;
use Biigle\Jobs\ProcessAnnotatedImage;
use SVG\SVG;
use Exception;

use Jcupitt\Vips\Image as VipsImage;
use Biigle\Modules\Ptp\PtpJob as PtpJobModel;


#vorscaubilder erzeugen
#

class PtpPatchJob extends ProcessAnnotatedImage {

    use SerializesModels;

    #welches bild und welcher job
    public function __construct( public Image $image, public int $ptpJobId) {
        parent::__construct(
            file: $image,
            skipFeatureVectors: true,
            skipSvgs: true,
            targetDisk: config("ptp.patch_storage_disk"),
        );
    }

    public function handle():void {

        // Callback
        FileCache::get($this->image, [$this, "handlePtpFile"]); #bilder lokal holen, noch abfrage ob verfügbar?

        \Log::info("PTP_PATCH_HANDLE_AFTER", ["image_id" => $this->image->id, "ptp_job_id" => $this->ptpJobId]);
    }


    public function handlePtpFile(Image $image, string $path): void {

        $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $this->ptpJobId)
            ->whereHas("sourceAnnotation", function ($query) use ($image) {
                $query->where("image_id", $image->id); #vlt besser doch di eimage id direkt in die DB packen
        })->get();


    /*    \Log::info("PTP_FOREACH", ["image_id" => $image->id, "count" => $ptpAnnotations->count()]);*/

        // Only load images with annotations
        if ($ptpAnnotations->isNotEmpty()) {
            $vipsImage = VipsImage::newFromFile($path);

            foreach($ptpAnnotations as $ptpAnnotation) {

                $buffer = $this->getAnnotationPatch($vipsImage, $ptpAnnotation->points, Shape::polygon());
                /*   \Log::info("PTP_BUFFER GESENDET");*/

                $targetPath = $this->getPtpPatchPath($image, $ptpAnnotation);

                $this->storePtpPatch($targetPath, $buffer);


                $this->createPtpSvg($ptpAnnotation);

                \Log::info("PTP_PATCH_STORAGE_DISK", [ "disk" => config("ptp.patch_storage_disk"),
                    "path"=> $targetPath, "exists" => Storage::disk(config("ptp.patch_storage_disk"))->exists($targetPath),]);
            }
        }

        PtpJobModel::where("id", $this->ptpJobId)->update(["status" => "waiting-for-review"]); // TODO triggert zu früh weil noch nicht alle bilder durch sind
    }


    public function createPtpSvg(PtpAnnotation $ptpAnnotation): void {
        \Log::info("PTP_SVG_-2");

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

        $targetPath = $this->getPtpTargetPath($this->file, $ptpAnnotation, "svg");

        if (Storage::disk($this->targetDisk)->put($targetPath, $svg) === false) {
            throw new Exception("Failed to save svg to PTP_PATCH_STORAGE_DISK");
        };

        \Log::info("PTP_SVG_GANZ", ["path" => $targetPath, "exists" => Storage::disk($this->targetDisk)->exists($targetPath)]);

    }




    public function storePtpPatch(string $path, string $buffer): void {

        if (Storage::disk($this->targetDisk)->put($path, $buffer) === false) {
            throw new Exception("Failed to write to PTP_PATCH_STORAGE_DISK");
        }

     /*   \Log::info("PTP_STORAGE", ["disk" => $disk, "config" =>config("Filesys : .{$disk}")]);*/
    }


    public function getPtpTargetPath(VolumeFile $file, PtpAnnotation $ptpAnnotation, string $format): string {

        $prefix = fragment_uuid_path($file->uuid);
        return "$prefix/{$ptpAnnotation->id}.{$format}";
    }


    public function getPtpPatchPath(Image $image, PtpAnnotation $ptpAnnotation): string {

        $format = config('largo.patch_format');
        $targetPath = $this->getPtpTargetPath($image, $ptpAnnotation, $format);

        \Log::info("PTP_TARGET_PATH", ["path" => $targetPath]);

        return $targetPath;
        #path in der main directory von der storage disk

    }


}
