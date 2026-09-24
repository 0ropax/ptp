
@extends('app')
@section('title', "ppt review")

@push('scripts')
    {{vite_hot(base_path('vendor/biigle/ptp/hot'), ['src/resources/assets/js/main.js'], 'vendor/ptp')}}
@endpush


@section('content')

    @include("ptp::index.review")

@endsection

@section('navbar')

@endsection

