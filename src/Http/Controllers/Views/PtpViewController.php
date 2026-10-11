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

    public function index(Request $request, $id) {

        $volume = Volume::findOrFail($id);
        if (!$request->user()->can('sudo')) {
            $this->authorize('edit-in', $volume);
        }

        if (!$volume->isImageVolume() || $volume->hasTiledImages()) {
            abort(Response::HTTP_NOT_FOUND);
        }


        $pointCount = ImageAnnotation::where("shape_id", Shape::pointId())
                        ->whereRelation("image", "volume_id", $volume->id)
                        ->count();


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


        $jobs = PtpJobModel::where("volume_id", $volume->id)
            ->orderByDesc('created_at')
            ->get();


        return view("ptp::index", compact(
            "volume",
            "pointCount",
            "labelTrees",
            "jobs",
        ));
    }
}
