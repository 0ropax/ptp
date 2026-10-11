<div class="list-group">
    @foreach ($jobs as $job)
        <a href="{{route('ptp-review', $job->id)}}"
           class="list-group-item @if ($job->status === "waiting-for-review") list-group-item-warning @endif">

            Job #{{$job->id}} created

            <span title="{{$job->created_at->toIso8601String()}}">
                {{$job->created_at->diffForHumans()}}
            </span>

            <span class="pull-right">
                {{ $job->status }}
            </span>
        </a>
    @endforeach
</div>
