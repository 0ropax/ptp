<?php
namespace Biigle\Modules\Ptp\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\ImageAnnotation;
use Biigle\ImageAnnotationLabel;
use Biigle\Modules\Ptp\PtpJob;
use Biigle\Project;
use Biigle\Role;
use Biigle\Shape;
use Biigle\Volume;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Biigle\LabelTree;
use Illuminate\Support\Facades\Storage;
use Biigle\Modules\Ptp\PtpAnnotation;


class PtpReviewController extends Controller {

    public function showReviewPage(Request $request, int $id) {

        //Permission check
     /*   $volume = Volume::findOrFail($id);*/
        $ptpJob = PtpJob::findOrFail($id);
        $volume = $ptpJob->volume;

        //ptp seite wird blockiert für gäste
        if (!$request->user()->can("sudo")) {
            $this->authorize("edit-in", $volume);
        }


    /*    if (!$volume->isImageVolume() || $volume->hasTiledImages()) {
            abort(Response::HTTP_NOT_FOUND);
        }*/

    /*
    vorher, aber dann unfunktional weil nicht genau wie bei maia
       $patchUrlTemplate = Storage::disk(config('ptp.patch_storage_disk'))
            ->url(':prefix/:id.'.config('largo.patch_format')); #weil ptp sotrage var/www/storage/ptp

        \Log::info("PTP_PATCH_URL_TEMPLATE", ["url" => $patchUrlTemplate, "id" => $id]);*/


        $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $id)
            ->whereHas("sourceAnnotation")
            ->with(["sourceAnnotation.image", "sourceAnnotation.labels"]) #zu jedem kandidaten die bild infos
            ->get();



       # return $ptpAnnotations; #TODO !
        #http://localhost:8000/ptp/jobs/64/review



        $user = $request->user();


        if ($user->can('sudo')) {
            // Global admins have no restrictions.
            $projectIds = $volume->projects()->pluck('id');
        } else {
            // Array of all project IDs that the user and the image have in common
            // and where the user is editor, expert or admin.
            $projectIds = Project::inCommon($user, $volume->id, [
                Role::editorId(),
                Role::expertId(),
                Role::adminId(),
            ])->pluck('id');
        }

        $labelTrees = LabelTree::select('id', 'name', 'version_id')
            ->with('labels', 'version')
            ->whereIn('id', function ($query) use ($projectIds) {
                $query->select('label_tree_id')
                    ->from('label_tree_project')
                    ->whereIn('project_id', $projectIds);
            })
            ->get();

        return view('ptp::review', ["ptpJob" => $ptpJob, "volume" => $volume, "ptpAnnotations" => $ptpAnnotations, "labelTrees" => $labelTrees,]);

    }


    // TODO ! kommentare
    //zieht bilder ausm ptp storage, liefert aus
    public function showPatch(Request $request, string $uuid, int $id, ?string $type = null) {

        $ptpAnnotation = PtpAnnotation::findOrFail($id);
        $source = $ptpAnnotation->sourceAnnotation;

        if ($source === null) {
            //noch unsicher wegen löschlogik, sonst image id in db
            abort(Response::HTTP_NOT_FOUND);
        }

        $image = $source->image;
        $volume = $image->volume;


        if (!$request->user()->can("sudo")) {
            $this->authorize("edit-in", $volume);
        }

        if ($image->uuid !== $uuid) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($type === "svg") {
            $format = "svg";
        } else {
            $format = config("largo.patch_format");
        }

        $prefix = fragment_uuid_path($uuid);
        $path = "{$prefix}/{$id}.{$format}";

        //id ptp vorschlag in ptp anno db,
      /*  $path = "{$uuid}/{$id}.{$format}";*/

        return Storage::disk(config('ptp.patch_storage_disk'))->response($path); //targetDisk string machen TODO

    }


    public function review(Request $request, int $id) {
        \Log::info("PTP_REVIEW_REJECTED", ["jobid" => $id, "rejected" => $request->input("rejected")]);

        $ptpJob = PtpJob::findOrFail($id);
        $volume = $ptpJob->volume;

        if (!$request->user()->can("sudo")) {
            $this->authorize("edit-in", $volume);
        }


        $rejIds = $request->input("rejected", []); //array noch sichern? TODO !



        //das auch noch sichern?
        $result = DB::transaction(function () use ($id, $rejIds, $request) {
            $accepted = 0;
            $skippedDeleted = 0;
            $skippedUnlabeled = 0;

            $job = PtpJob::where("id", $id)
                ->lockForUpdate()
                ->firstOrFail();


            if($job->status !== "waiting-for-review") {
                abort(409);
            }




            $accepThumbnails = PtpAnnotation::where("ptp_job_id", $id)
                ->whereNotIn("id", $rejIds)
                ->get();


            foreach ($accepThumbnails as $accTh) {

                //accth ptp anno obj , hier die image id auch wieder besser
                $source = $accTh->sourceAnnotation;
                if ($source === null) {
                    $skippedDeleted++;
                    continue;
                }

                $label = $source->labels->first();

                if ($label === null) {
                    $skippedUnlabeled++;
                    continue;
                }


                \Log::info("PTP_annoDB", []);
                $imageAnno = new ImageAnnotation();

                $imageAnno->points = $accTh->points;
                $imageAnno->image_id = $source->image_id;
                $imageAnno->shape_id = Shape::polygon()->id;

                $imageAnno->save();

                $accTh->annotation_id = $imageAnno->id;
                $accTh->save();


                \Log::info("PTP_annoLabelsDB", []);
                $imageAnnoL = new ImageAnnotationLabel();

                $imageAnnoL->annotation_id = $imageAnno->id;

                $imageAnnoL->label_id = $label->label_id;

                $imageAnnoL->user_id = $request->user()->id;

                $imageAnnoL->confidence = 1.0;
                $imageAnnoL->save();

                $accepted++;


            }

            $job->status ="review-completed";
            $job->save();

            return [ "accepted" => $accepted, "skipped_deleted" => $skippedDeleted, "skipped_unlabeled" => $skippedUnlabeled];

            });

        return response()->json($result);

    }

}
