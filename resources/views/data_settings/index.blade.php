@extends('layout')

@section('dashboard-content')

@livewire('data-setting')

@endsection

@section('scripts')
    <script src="{{ URL::to('assets/js/data.js')}}"></script>
@endsection