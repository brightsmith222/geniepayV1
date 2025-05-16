@extends('layout')

@section('dashboard-content')

@livewire('data-settings')

@endsection

@section('scripts')
    <script src="{{ URL::to('/assets/js/data.js')}}"></script>
@endsection