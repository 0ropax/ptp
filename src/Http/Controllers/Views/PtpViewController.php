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
use Biigle\Modules\Ptp\PtpJob as PtpJobModel;

#controller für alles was mit den blades zu tun hat
class PtpViewController extends Controller {

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index(Request $request, $id) {

        $volume = Volume::findOrFail($id);
        $user = $request->user();

        //User can access ptp job page
        if (!$user->can("sudo")) {
            $this->authorize("edit-in", $volume);
        }


        $jobs = PtpJobModel::where("volume_id", $volume->id)
            ->orderByDesc("created_at")
            ->get();


        //TODO ! problematisch weil sich volume verändern kann und dann jobliste fehlt
        //right volume for ptp jobs; abort showing the page if volume isnt supported
     /*   if (!$volume->isImageVolume() || $volume->hasTiledImages()) {
            abort(Response::HTTP_NOT_FOUND);
        }*/


        //Total point annotation count
        $totalPointCount = ImageAnnotation::join("images", "image_annotations.image_id", "=", "images.id")
                            ->where("images.volume_id", $volume->id)
                            ->where("image_annotations.shape_id", Shape::pointId())
                            ->count();

        //Remaining point annotation count
        $successfulPolygonIds = PtpJobModel::join("ptp_annotations", "ptp_annotations.ptp_job_id", "=", "ptp_jobs.id")
                                ->where("ptp_jobs.volume_id", $volume->id)
                                ->whereNotNull("ptp_annotations.annotation_id")
                                ->distinct()
                                ->pluck("ptp_annotations.source_annotation_id");

        $remainingPointCount = ImageAnnotation::join("images", "image_annotations.image_id", "=", "images.id")
                                ->where("images.volume_id", $volume->id)
                                ->where("image_annotations.shape_id", Shape::pointId())
                                ->whereNotIn("image_annotations.id", $successfulPolygonIds)
                                ->count();




// TODO ! für den nutzer einschränken welche labels wegen den rechten und deswegen auch point count anpassen
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



       /*
        * Das auch nochmal angucken vom LargoController TODO !
       $labelTrees = $project->labelTrees()
            ->select('id', 'name', 'version_id')
            ->with('labels', 'version')
            ->get();

        $patchUrlTemplate = Storage::disk(config('largo.patch_storage_disk'))
            ->url(':prefix/:id.'.config('largo.patch_format'));

        $shapes = Shape::pluck('name', 'id');

        return view('largo.project', [
            'project' => $project,
            'labelTrees' => $labelTrees,
            'target' => $project,
            'patchUrlTemplate' => $patchUrlTemplate,
            'shapes' => $shapes,
        ]);*/
//// TODO ! für den nutzer einschränken welche labels wegen den rechten und deswegen auch point count anpassen


        return view("ptp::index", compact(
            "volume",
            "totalPointCount",
            "labelTrees",
            "jobs",
            "remainingPointCount",
        ));
    }
}
