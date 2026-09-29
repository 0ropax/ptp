<?php

namespace Biigle\Modules\Ptp\Jobs;

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

#vorscaubilder erzeugen
#

class PtpPatchJob extends Job
{

    use SerializesModels;

    #welches bild und welcher job
    public function __construct( public Image $image, public int $ptpJobId) {

    }

    public function handle():void {

        \Log::info("PTP_PATCH_HANDLE", ["image_id" => $this->image->id, "ptp_job_id" => $this->ptpJobId]);
        \Log::info("PTP_PATCH_URL", ["class" => get_class($this->image), "id" => $this->image->id, "url" =>$this->image->url]);

        FileCache::get($this->image, [$this, "handleFile"]); #bilder lokal holen, noch abfrage ob verfügbar?

        \Log::info("PTP_PATCH_HANDLE_AFTER", ["image_id" => $this->image->id, "ptp_job_id" => $this->ptpJobId]);
    }


    public function handleFile(Image $image, string $path): void {

        \Log::info("PTP_PATCH_HANDLEFILE", ["image_id" => $image->id, "path" => $path]);

        $vipsImage = VipsImage::newFromFile($path); #jetzt bildobjekt, darauf später crop


        \Log::info("PTP_NEWFROMFILE", ["image_id" => $image->id, "path" => $path]);

        $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $this->ptpJobId)
        ->whereHas("sourceAnnotation", function ($query) use ($image) {
            $query->where("image_id", $image->id); #vlt besser doch di eimage id direkt in die DB packen
        })->get();


        \Log::info("PTP_FOREACH", ["image_id" => $image->id, "count" => $ptpAnnotations->count()]);

        #erstmal jeder anno abe rnur von dem einen bild
        foreach($ptpAnnotations as $ptpAnnotation) {
            $points = $ptpAnnotation->points;

            \Log::info("PTP_FOR EACH LÄUFT");
            $buffer = $this->createPtpPatch($vipsImage, $points);
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



            #####
            $debugDirectory = base_path("vendor/biigle/ptp/ptp_patches");
            $debugP = "{$debugDirectory}/{$ptpAnnotation->id}.jpg";
            file_put_contents($debugP, $buffer);
            ###

            PtpJobModel::where("id", $this->ptpJobId)->update(["status" => "waiting-for-review"]);

        }

    }


    public function createPtpPatch($image, $points)
    {

        $thumbWidth = config('thumbnails.width');
        $thumbHeight = config('thumbnails.height');


            $padding = config('largo.patch_padding');


            $box = $this->getAnnotationBoundingBox($points, $padding);

            $box = $this->ensureBoxAspectRatio($box, $thumbWidth, $thumbHeight);
            $box = $this->makeBoxContained($box, $image->width, $image->height);


        $patch = $image->crop(...$box)
            ->resize(floatval($thumbWidth) / $box[2]); #... entpackt array


      #      $image = $image->crop(...$box)->resize(floatval($thumbWidth) / $box[2]);


        return $patch->writeToBuffer('.'.config('largo.patch_format'), [
            'Q' => 85,
            'strip' => true,
        ]);
    }


    public function storePtpPatch(string $path, string $buffer): void {
        $disk = config("ptp.patch_storage_disk");
        Storage::disk(config("ptp.patch_storage_disk"))->put($path, $buffer); #speicher


        \Log::info("PTP_STORAGE", ["disk" => $disk, "config" =>config("Filesys : .{$disk}")]);

    }


    public function getPtpPatchPath(Image $image, PtpAnnotation $ptpAnnotation): string {

        $format = config('largo.patch_format');
        $teil = $image->uuid;

        $targetPath = "{$teil}/{$ptpAnnotation->id}.{$format}";

        \Log::info("PTP_TARGET_PATH", ["path" => $targetPath]);

        return $targetPath;
        #path in der main directory von der storage disk

    }

    # # # #  # # # #


    public function getAnnotationBoundingBox(
        array $points,


        int $boxPadding = 0,
        int $minSize = 32
    ): array {
        $box = $this->getPolygonBoundingBox($points);


        if ($boxPadding > 0) {
            $box = [
                $box[0] - $boxPadding,
                $box[1] - $boxPadding,
                $box[2] + $boxPadding,
                $box[3] + $boxPadding,
            ];
        }

        $box = $this->makeBoxIntegers([
            $box[0], // left
            $box[1], // top
            $box[2] - $box[0], // width
            $box[3] - $box[1], // height
        ]);

        // Ensure minimum dimensions. This is important e.g. with a line string that is
        // exactly parallel to the x or y axis.
        if ($box[2] === 0) {
            $box[0] -= intval($minSize / 2);
            $box[2] = $minSize;
        }

        if ($box[3] === 0) {
            $box[1] -= intval($minSize / 2);
            $box[3] = $minSize;
        }

        return $box;
    }

    public function ensureBoxAspectRatio(array $box, int $aspectWidth, int $aspectHeight): array
    {
        [$left, $top, $width, $height] = $box;

        // Ensure the minimum width so the annotation patch is not "zoomed in".
        if ($width < $aspectWidth) {
            $left -= ($aspectWidth - $width) / 2.0;
            $width = $aspectWidth;
        }

        // Ensure the minimum height so the annotation patch is not "zoomed in".
        if ($height < $aspectHeight) {
            $top -= ($aspectHeight - $height) / 2.0;
            $height = $aspectHeight;
        }

        $widthRatio = $width / $aspectWidth;
        $heightRatio = $height / $aspectHeight;

        // Increase the size of the patch so its aspect ratio is the same than the
        // ratio of the given dimensions.
        if ($widthRatio > $heightRatio) {
            $newHeight = round($aspectHeight * $widthRatio);
            $top -= round(($newHeight - $height) / 2);
            $height = $newHeight;
        } else {
            $newWidth = round($aspectWidth * $heightRatio);
            $left -= round(($newWidth - $width) / 2);
            $width = $newWidth;
        }

        return $this->makeBoxIntegers([$left, $top, $width, $height]);
    }


    public function makeBoxContained(array $box, ?int $maxWidth, ?int $maxHeight)
    {
        [$left, $top, $width, $height] = $box;

        if (!is_null($maxWidth)) {
            $left = min($maxWidth - $width, $left);
            // Adjust dimensions of rect if it is larger than the image.
            $width = min($maxWidth, $width);
        }

        if (!is_null($maxHeight)) {
            $top = min($maxHeight - $height, $top);
            // Adjust dimensions of rect if it is larger than the image.
            $height = min($maxHeight, $height);
        }

        // Order of min max is importans so the point gets no negative coordinates.
        $left = max(0, $left);
        $top = max(0, $top);

        return [$left, $top, $width, $height];
    }


    protected function getPolygonBoundingBox(array $points): array
    {
        $minX = INF;
        $minY = INF;
        $maxX = -INF;
        $maxY = -INF;

        for ($i = 0; $i < count($points); $i += 2) {
            $minX = min($minX, $points[$i]);
            $minY = min($minY, $points[$i + 1]);
            $maxX = max($maxX, $points[$i]);
            $maxY = max($maxY, $points[$i + 1]);
        }

        return [$minX, $minY, $maxX, $maxY];
    }


    protected function makeBoxIntegers(array $box): array
    {
        return array_map(fn ($v) => intval(round($v)), $box);
    }




}

