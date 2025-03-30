@extends('layout')

@section('dashboard-content')

@livewire('app-notification')

@endsection

@section('scripts')
    <script src="{{ URL::to('assets/js/notification.js')}}"></script>
@endsection