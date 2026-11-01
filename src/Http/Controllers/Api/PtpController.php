<?php

namespace Biigle\Modules\Ptp\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\ImageAnnotation;
use Biigle\Shape;
use Biigle\Volume;
use Exception;
use Illuminate\Http\Request;
use Queue;
use Ramsey\Uuid\Uuid;
use Biigle\Modules\Ptp\Support\PtpPointQuery;

use Biigle\Modules\Ptp\Jobs\PtpJob as PtpConversionJob;
use Biigle\Modules\Ptp\PtpJob as PtpJobModel;

/**
 * Controller used for creating a PTP Job Chain
 */
class PtpController extends Controller
{
    /**
     * Generate Point to Polygon Job for the specified volume
     *
     * @api {post} send-ptp-job/:id Submit point conversion job
     * @apiGroup Volumes
     * @apiName StorePtpJob
     * @apiPermission projectEditor
     * @apiDescription This will start a Magic SAM point conversion job for all point annotations in the volume.
     * This is not available for volumes with very large images and video volumes.
     * Only one job can be submitted at a time for each volume.
     *
     * @apiParam {Number} id The volume ID.
     *
     * @param  $request
     * @param $volumeId
     * @return void
     */
    public function store(Request $request, int $volumeId)
    {
        $volume = Volume::findOrFail($volumeId);
        $this->authorize('edit-in', $volume);

        if (!$volume->isImageVolume() || $volume->hasTiledImages()) {
            abort(400, 'The point to polygon conversion cannot be executed on video volumes or volumes with very large images.');
        }

        if (is_array($volume->attrs) && array_key_exists('ptp_job_id', $volume->attrs)) {
            abort(400, 'Another point to polygon conversion job is running in this volume!');
        }

        // Conversion choice radio buttons
        $conversion_choice = $request->input("converted_points_sam");

        if ($conversion_choice === "ignored" || $conversion_choice === "included") {
            $options_param = $conversion_choice;
        } else {
            abort(400, "Invalid conversion option");
        }

        // Checks if enough points are available to start a conversion job
        $query = PtpPointQuery::getPointsToConvert($volume, $options_param);

        if ($query->count() <= 0) {
            abort(400, "No point annotations to convert!");
        }


        try {
            // Create PTP job object and data set
            $ptpJobEntry = PtpJobModel::create([
                "volume_id" => $volumeId,
                "status" => "pending",
                "conversion_options" => ["already_converted_points" => $options_param],
            ]);

            // Register PTP job for this volume
            $jobUuid = $this->setUniquePtpJob($volume);

            // Object for queue worker
            $job = new PtpConversionJob($volume, $request->user(), $jobUuid, $ptpJobEntry->id);
            Queue::connection(config('ptp.job_connection'))
                ->pushOn(config('ptp.job_queue'), $job);

        } catch (Exception $e) {
            // If unable to dispatch a PTP Job, reset the PTP Job ID
            $attrs = $volume->attrs;
            unset($attrs['ptp_job_id']);
            $volume->attrs = $attrs;
            $volume->save();

            if (isset($ptpJobEntry)) {
                $ptpJobEntry->update(["status" => "failed"]);
            }

            throw $e;
        }
    }

    /**
    *
    * Assign UUID to a volume for the PTP job so that only one job is run per volume at a time.
    * @param Volume $volume Volume where the PTP job is executed
    *
    **/
    protected function setUniquePtpJob(Volume $volume): string
    {
        $attrs = $volume->attrs;
        $uuid = Uuid::uuid4();
        $attrs['ptp_job_id'] = $uuid;
        $volume->attrs = $attrs;
        $volume->save();
        return $uuid;
    }
}
