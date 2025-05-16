@extends('layout')

@section('dashboard-content')

@livewire('voucher-settings')

@endsection

@section('scripts')
    <script src="{{ URL::to('/assets/js/voucher.js')}}"></script>
@endsection