@extends('layout')

@section('dashboard-content')

@livewire('setting-controller')

@endsection

@section('scripts')
    <script src="{{ URL::to('assets/js/settings.js')}}"></script>
@endsection