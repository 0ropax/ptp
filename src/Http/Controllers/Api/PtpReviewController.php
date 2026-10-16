<?php
namespace Biigle\Modules\Ptp\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\ImageAnnotation;
use Biigle\ImageAnnotationLabel;
use Biigle\Modules\Laserpoints\Image;
use Biigle\Modules\Ptp\PtpJob;
use Biigle\Project;
use Biigle\Role;
use Biigle\Shape;
use Biigle\Volume;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Queue;
use Ramsey\Uuid\Uuid;
use Biigle\LabelTree;
use Illuminate\Support\Facades\Storage;
use Biigle\Modules\Ptp\PtpAnnotation;
/*use Biigle\Modules\Ptp\PtpJob as PtpJobModel;*/


class PtpReviewController extends Controller {

    public function showReviewPage(Request $request, int $id) {
/*
        $volume = Volume::findOrFail($volumeId);


        $this->authorize('edit-in', $volume);
*/

        $ptpJob = PtpJob::findOrFail($id);
        $volume = $ptpJob->volume;




/*
        $patchUrlTemplate = Storage::disk(config('ptp.patch_storage_disk'))
            ->url(':prefix/:id.'.config('largo.patch_format'));*/

        $patchUrlTemplate = Storage::disk(config('ptp.patch_storage_disk'))
            ->url(':uuid/:id.'.config('largo.patch_format')); #weil ptp sotrage var/www/storage/ptp
#fragment uuid path TODO

        \Log::info("PTP_PATCH_URL_TEMPLATE", ["url" => $patchUrlTemplate, "id" => $id]);


        $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $id)
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



    public function showPatch(Request $request, string $uuid, int $id, ?string $type = null) {
        if ($type === "svg") {
            $format = "svg";
        } else {
            $format = config("largo.patch_format");
        }



        $path = "{$uuid}/{$id}.{$format}";

        return Storage::disk(config('ptp.patch_storage_disk'))->response($path);

    }


    public function review(Request $request, int $id) {
        \Log::info("PTP_REVIEW_REJECTED", ["jobid" => $id, "rejected" => $request->input("rejected")]);

        #TODO ! hier dann db abfrage nach annotation id

        $rejIds = $request->input("rejected", []);

   /*     $ptpAnnotations = PtpAnnotation::where("ptp_job_id", $id)->get();*/

     /*   $annotation = PtpAnnotation::findOrFail($rejIds);*/

#einmal tesweis emarkieren
  /*      PtpAnnotation::where("ptp_job_id", $id)
            ->whereIn("id", $rejIds)
            ->update([
           "annotation_id" => 1,

        ]);*/



        DB::transaction(function () use ($id, $rejIds, $request) {
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
                \Log::info("PTP_annoDB", []);
                $imageAnno = new ImageAnnotation();

                $imageAnno->points = $accTh->points;
                $imageAnno->image_id = $accTh->sourceAnnotation->image_id;
                $imageAnno->shape_id = Shape::polygon()->id;

                $imageAnno->save();

                $accTh->annotation_id = $imageAnno->id;
                $accTh->save();


                \Log::info("PTP_annoLabelsDB", []);
                $imageAnnoL = new ImageAnnotationLabel();

                $imageAnnoL->annotation_id = $imageAnno->id;

                $imageAnnoL->label_id = $accTh->sourceAnnotation->labels->first()->label_id;

                $imageAnnoL->user_id = $request->user()->id;

                $imageAnnoL->confidence = 1.0;
                $imageAnnoL->save();


            }

            $job->status ="review-completed";
            $job->save();


            });




/*
        $accepThumbnails = PtpAnnotation::where("ptp_job_id", $id)
            ->whereNotIn("id", $rejIds)
            ->get();







        /*    $imageAnno = ImageAnnotation::create([*/ #geht nicht wegen Mass Assignment, insert umgeht fillable aber gibt keine id


/*
                "points" => $accTh->points,

                "image_id" => $accTh->sourceAnnotation->image_id,

                "shape_id" => Shape::polygon()->id,*/

              /*  "created_at" => $now,
                "updated_at" => $now,*/


                # TODO ! image_annotation_labels db eintrag

     /*       ]);*/

         /*   $newAnnotationId = $imageAnno->id;
            \Log::info("PTP_annoZeile1", []);
            \Log::info("PTP_anno1", ["id" => $newAnnotationId]);

            $accTh->annotation_id = $newAnnotationId;
            \Log::info("PTP_annoDB_vorSAVE", []);

            $accTh->save();

        }*/

     /*   $notNull = PtpAnnotation::where("ptp_job_id", $id)
            ->where("annotation_id" != null);

        if ($notNull->count() > 0) {
            ImageAnnotation::insert($notNull->toArray());
        }*/




    }



}
