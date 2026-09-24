<div >

    @foreach($ptpAnnotations as $ptpAnnotation)
        <img
            src="/ptp/patches/{{  $ptpAnnotation->sourceAnnotation->image->uuid }}/{{$ptpAnnotation->id}}">
    @endforeach


</div>
