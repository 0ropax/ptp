@extends("app")
@section("title", "{$volume->name} PTP")

@section('navbar')
    <div class="navbar-text navbar-volumes-breadcrumbs">
        @include('volumes.partials.projectsBreadcrumb', ['projects' => $volume->projects])
            /
            <a href="{{  route('volume', $volume->id)  }}"> {{  $volume->name  }}</a>
            /
            <strong> PTP </strong>
    </div>
@endsection



@push("scripts")
    <script type="module">
        biigle.$declare("ptp.volumeId", {!!  $volume->id !!});
        biigle.$declare("ptp.isRunning", @json(isset($volume->attrs["ptp_job_id"])));
        biigle.$declare("ptp.totalPointCount", @json($totalPointCount));
        biigle.$declare("ptp.remainingPointCount", @json($remainingPointCount));
    </script>

    {{  vite_hot(base_path('vendor/biigle/ptp/hot'), ['src/resources/assets/js/main.js'], 'vendor/ptp')  }}
@endpush


@section('content')

    <div class="container">
        <div class="col-sm-8 col-sm-offset-2 col-lg-6 col-lg-offset-3">

            <div class="ptp-status
                @if($newestJob?->status === "failed")
                    ptp-status--failed
                @elseif ($newestJob?->status === "pending" || $newestJob?->status === "in-progress")
                    ptp-status--running
                @endif">

                <span class="fa-stack fa-2x">
                    <i class="fas fa-circle fa-stack-2x"></i>
                    <i class="fas fa-hat-wizard fa-stack-1x fa-inverse"></i>
                </span>
            </div>

            <!--- Job list at the top of the page --->
            @include("ptp::index.job-list")
            <!--- All selectable options for job configuration --->
            @include("ptp::index.job-form")


        </div>
    </div>
@endsection


@push("styles")

<style>

    .ptp-status {
        text-align: center;
        color: #5cb85c;
        margin-bottom: 20px;
    }

    .ptp-status--running {
        color: #f0ad4e;
    }

    .ptp-status--failed {
        color: #d9534f;
    }


</style>

@endpush
