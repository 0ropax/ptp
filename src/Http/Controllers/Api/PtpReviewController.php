<?php
namespace Biigle\Modules\Ptp\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\ImageAnnotation;
use Biigle\Modules\Ptp\Jobs\PtpJob;
use Biigle\Shape;
use Biigle\Volume;
use Exception;
use Illuminate\Http\Request;
use Queue;
use Ramsey\Uuid\Uuid;

use Illuminate\Support\Facades\Storage;
use Biigle\Modules\Ptp\PtpAnnotation;

class PtpReviewController extends Controller {

    public function showReviewPage(Request $request, int $id) {
/*
        $volume = Volume::findOrFail($volumeId);


        $this->authorize('edit-in', $volume);
*/


/*
        $patchUrlTemplate = Storage::disk(config('ptp.patch_storage_disk'))
            ->url(':prefix/:id.'.config('largo.patch_format'));*/

        $patchUrlTemplate = Storage::disk(config('ptp.patch_storage_disk'))
            ->url(':uuid/:id.'.config('largo.patch_format'));


        \Log::info("PTP_PATCH__URL_TEMPLATE", ["url" => $patchUrlTemplate, "id" => $id]);


        $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $id)
            ->with("sourceAnnotation.image") #zu jedem kandidaten die bild infos
            ->get();



       # return $ptpAnnotations; #TODO !
        #http://localhost:8000/ptp/jobs/64/review


        return view('ptp::review', ["ptpAnnotations" => $ptpAnnotations]);





    }



    public function showPatch(Request $request, string $uuid, int $id) {
        $format = config("largo.patch_format");
        $path = "{$uuid}/{$id}.{$format}";

        return Storage::disk(config('ptp.patch_storage_disk'))->response($path);

    }




}
