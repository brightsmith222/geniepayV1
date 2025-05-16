@extends('layout')

@section('dashboard-content')

@livewire('airtime-settings')

@endsection

@section('scripts')
    <script src="{{ URL::to('/assets/js/airtime.js')}}"></script>
@endsection