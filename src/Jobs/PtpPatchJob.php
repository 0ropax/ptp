<?php

namespace Biigle\Modules\Ptp\Jobs;

use Biigle\Shape;
use Illuminate\Support\Facades\Storage;
#use Biigle\Modules\Kpis\Storage;
use Biigle\Modules\Ptp\PtpAnnotation;
use FileCache;
use Biigle\Jobs\Job;
use Biigle\VolumeFile;
use Illuminate\Queue\SerializesModels;
use Biigle\Image;

use Jcupitt\Vips\Image as VipsImage;
use Biigle\Modules\Ptp\PtpJob as PtpJobModel;

use Biigle\Jobs\ProcessAnnotatedImage;
use SVG\SVG;

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

        \Log::info("PTP_PATCH_HANDLE", ["image_id" => $this->image->id, "ptp_job_id" => $this->ptpJobId]);
        \Log::info("PTP_PATCH_URL", ["class" => get_class($this->image), "id" => $this->image->id, "url" =>$this->image->url]);

        FileCache::get($this->image, [$this, "handleFilee"]); #bilder lokal holen, noch abfrage ob verfügbar?

        \Log::info("PTP_PATCH_HANDLE_AFTER", ["image_id" => $this->image->id, "ptp_job_id" => $this->ptpJobId]);
    }


    public function handleFilee(Image $image, string $path): void {

        $vipsImage = VipsImage::newFromFile($path); #jetzt bildobjekt, darauf später crop


        \Log::info("PTP_NEWFROMFILE", ["image_id" => $image->id, "path" => $path]);

        $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $this->ptpJobId)
        ->whereHas("sourceAnnotation", function ($query) use ($image) {
            $query->where("image_id", $image->id); #vlt besser doch di eimage id direkt in die DB packen
        })->get();


        \Log::info("PTP_FOREACH", ["image_id" => $image->id, "count" => $ptpAnnotations->count()]);
        $shape = Shape::polygon();

        #erstmal jeder anno abe rnur von dem einen bild
        foreach($ptpAnnotations as $ptpAnnotation) {
            $points = $ptpAnnotation->points;

            \Log::info("PTP_FOR EACH LÄUFT");
            $buffer = $this->getAnnotationPatch($vipsImage, $points, $shape);
            \Log::info("PTP_BUFFER GESENDET");



            #buffer als bilddatei
            $format = config('largo.patch_format');
            $debugPath = "/tmp/ptp_patch_{$ptpAnnotation->id}.{$format}";




            file_put_contents($debugPath, $buffer);
            \Log::info("PTP_DEBUG_PATH+BUFFER SCHREIBEN");

            \Log::info("PTP_PATCH_SAVE", ["path" => $debugPath]);
            $targetPath = $this->getPtpPatchPath($image, $ptpAnnotation);

            $this->storePtpPatch($targetPath, $buffer);

            #buffer schreibt in speicher
            #TODO ! jedes bild



           ##### TESTAUSGABE IN ORDNER
            $debugDirectory = base_path("vendor/biigle/ptp/ptp_patches");
            $debugP = "{$debugDirectory}/{$ptpAnnotation->id}.jpg";
            file_put_contents($debugP, $buffer);


           $this->createPtpSvg($ptpAnnotation);



        }

        PtpJobModel::where("id", $this->ptpJobId)->update(["status" => "waiting-for-review"]);
    }


    public function createPtpSvg(PtpAnnotation $ptpAnnotation): void {
        \Log::info("PTP_SVG_-2");
        $thumbWidth = config('thumbnails.width');
        $thumbHeight = config('thumbnails.height');
        $padding = config('largo.patch_padding');
        $pointPadding = config('largo.point_padding');




        $points = $ptpAnnotation->points;
        $shape = Shape::polygon();


        $svg = new SVG($thumbWidth, $thumbHeight);
        $doc = $svg->getDocument();



/*
        $points = $ptpAnnotation->getPoints();
        if (is_array($points[0])) {
            $points = $points[0];
        }*/


        $box = $this->getAnnotationBoundingBox($points, $shape, $pointPadding, $padding);
        $box = $this->ensureBoxAspectRatio($box, $thumbWidth, $thumbHeight);
        $box = $this->makeBoxContained($box, $this->file->width, $this->file->height);

        \Log::info("PTP_SVG_2");

        $doc->setAttribute('viewBox', implode(' ', $box));

        $svgAnnotation = $this->getSVGAnnotation($points, $shape);
        $doc->addChild($svgAnnotation);

     /*   // Set viewbox to show svgAnnotation
        $doc->setAttribute('viewBox', implode(' ', $box));*/



        $targetPath = $this->getPtpTargetPath($this->file, $ptpAnnotation, "svg");

        Storage::disk($this->targetDisk)->put($targetPath, $svg);

        \Log::info("PTP_SVG_GANZ");



            ##### TESTAUSGABE IN ORDNER
             $debugDirectory = base_path("vendor/biigle/ptp/ptp_patches");
             $debugP = "{$debugDirectory}/{$ptpAnnotation->id}.svg";
             file_put_contents($debugP, (string) $svg);

    }




    public function storePtpPatch(string $path, string $buffer): void {
        $disk = config("ptp.patch_storage_disk");
        Storage::disk(config("ptp.patch_storage_disk"))->put($path, $buffer); #speicher


        \Log::info("PTP_STORAGE", ["disk" => $disk, "config" =>config("Filesys : .{$disk}")]);

    }


    public function getPtpTargetPath(VolumeFile $file, PtpAnnotation $ptpAnnotation, string $format): string {
        return "{$file->uuid}/{$ptpAnnotation->id}.{$format}";
    }


    public function getPtpPatchPath(Image $image, PtpAnnotation $ptpAnnotation): string {

        $format = config('largo.patch_format');
        $teil = $image->uuid;

        $targetPath = "{$teil}/{$ptpAnnotation->id}.{$format}";

        \Log::info("PTP_TARGET_PATH", ["path" => $targetPath]);

        return $targetPath;
        #path in der main directory von der storage disk

    }


}

