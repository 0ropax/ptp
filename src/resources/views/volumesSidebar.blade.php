
@if ($user->can('edit-in', $volume) && $volume->isImageVolume())
    @if($volume->hasTiledImages())
        <sidebar-tab name="ptp"
                     icon="hat-wizard"
                     title="Magic SAM point conversion is not available for volumes with very large images"
                     :disabled="true">
        </sidebar-tab>
    @else
        <sidebar-tab name="ptp"
                     icon="hat-wizard"
                     title="Convert Points to Polygons"
                     href="{{route("volumes-ptp", $volume->id)}}">
        </sidebar-tab>
    @endif
@endif
