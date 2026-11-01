<?php

namespace Biigle\Modules\Ptp\Http\Controllers\Views;


use Biigle\Http\Controllers\Views\Controller;
use Illuminate\Http\Request;
use Biigle\Volume;
use Biigle\Shape;
use Biigle\ImageAnnotation;
use Illuminate\Http\Response;
use Biigle\LabelTree;
use Biigle\Role;
use Biigle\Project;
use Biigle\Modules\Ptp\Support\PtpPointQuery;

use Biigle\Modules\Ptp\PtpJob as PtpJobModel;


/**
 * Controller for the PTP job page.
 * Loads volume and jobs. Counts the point annotations of the volume.
 */
class PtpViewController extends Controller {

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index(Request $request, $id) {

        $volume = Volume::findOrFail($id);
        $user = $request->user();

        // User can access ptp job page
        if (!$user->can("sudo")) {
            $this->authorize("access", $volume);
        }

        // right volume for ptp jobs; abort showing the page if volume isnt supported
        if (!$volume->isImageVolume() || $volume->hasTiledImages()) {
            abort(Response::HTTP_NOT_FOUND);
        }


        $jobs = PtpJobModel::where("volume_id", $volume->id)
            ->orderByDesc("created_at")
            ->get();



        // Calculate amount of annotations based on conversion choice
        $totalPointCount = PtpPointQuery::getPointsToConvert($volume, "included")->count();
        $remainingPointCount = PtpPointQuery::getPointsToConvert($volume, "ignored")->count();



        return view("ptp::index", compact(
            "volume",
            "totalPointCount",
            "jobs",
            "remainingPointCount",
        ));
    }
}
