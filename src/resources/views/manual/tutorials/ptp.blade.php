
@extends('manual.base')

@section('manual-title', 'Magic SAM Point Conversion')

@section('manual-content')
    <div class="row">
        <p class="lead">
            <br>
            Convert all point annotations of an image volume to polygon annotations using Magic SAM.
        </p>

        <p>
            Many image collections are annotated using only point annotations, which are sometimes not very useful.
            The Magic SAM point conversion tool allows you to convert existing point annotations of a whole image volume to polygons.
        </p>

        <div class="col-xs-6">
            <p class="text-center text-muted">
                <a href="{{asset('vendor/ptp/images/manual/points.png')}}"><img src="{{asset('vendor/ptp/images/manual/points.png')}}" width="100%"></a>
                before
            </p>
        </div>

        <div class="col-xs-6">
            <p class="text-center text-muted">
                <a href="{{asset('vendor/ptp/images/manual/polygons.png')}}"><img src="{{asset('vendor/ptp/images/manual/polygons.png')}}" width="100%"></a>
                after
            </p>
        </div>

        <p>
            Access the PTP tool through the tab with the <button class="btn btn-default btn-xs"><i class="fa fa-hat-wizard" aria-hidden="true"></i></button> icon in the volume overview.
            This will redirect you to the main PTP configuration page.

            <br>
            In the <strong>Scope of conversion</strong> section, you can choose whether to convert only the point annotations that have not been converted yet or
            to include all point annotations currently in this volume, including those from previous jobs.


            Click on the <button class="btn btn-success btn-xs" aria-hidden="true">Create job</button> button beneath, to start the conversion.
            <br>
            <br>
            Depending on how many images and annotations are included in the volume, the job could take a long time.
            For this conversion to work optimally, there need to be numerous point annotations for each label of interest.
            Not all of the points may be converted, depending on the algorithm's ability to detect a valid boundary.
        </p>


        <br>
        <p class="lead"> Review page </p>

        <p>
            You will receive a notification once the conversion is finished. You can also view the status of the job in the job list at the top of the PTP configuration page.
            <br>
            Jobs which are ready for review are highlighted.
                <div class="list-group">
                        <a class="list-group-item list-group-item-warning">
                            Job #1 created
                            <span> 1 min ago </span>
                            <span class="pull-right"> waiting-for-review</span>
                        </a>
                </div>
            Clicking on such an entry will open the review page.
            <br>
            <br>

            The review page displays the thumbnails of the polygon proposals that SAM successfully generated using the selected point annotations based on the previously selected conversion option.
            <br>
            <br>
            You can use the <strong>Label Tree </strong> <i class="fa fa-tags" aria-hidden="true"></i> tab in the sidebar to filter the proposals by label.
            Click a label to select it; click it again to clear the filter.
        </p>


        <br>
        <p class="lead"> Review the proposals </p>

        <p>
            To reject a polygon proposal, you can click on its thumbnail. Clicking it again will undo the selection.
            <br>

            After you selected all the polygon proposals you want to dismiss, you can finish the review by clicking the <button class="btn btn-success btn-xs" aria-hidden="true">Finish review</button>
            button in the <strong>Job information</strong> tab in the sidebar.
            All accepted polygons are saved as permanent polygon annotations in the volume. All dismissed annotations are discarded.
            <br>

            <strong> The review cannot be modified after confirmation. </strong>
            <br>
            <br>
            The existing point annotations will not be deleted as part of a conversion job. To delete the point annotations you can browse
            them in <a href="{{route('manual-tutorials', ['largo', 'largo'])}}">Largo</a> and filter the annotations by shape.
        </p>

        <br>
        <p class="lead"> Deleting a conversion job </p>

        <p>
            If you want to delete a conversion job, you can do so as long as the review hasn't been finished,
            by clicking the <button class="btn btn-danger btn-xs" aria-hidden="true">Delete this job</button> button in the sidebar.
        </p>

    </div>
@endsection
