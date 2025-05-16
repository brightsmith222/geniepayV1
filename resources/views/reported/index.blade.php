@extends('layout')

@section('dashboard-content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <!-- Tab Navigation -->
                <div class="transaction-tab-wrapper">
                <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="reported-tab" data-toggle="tab" href="#reported" role="tab">
                            Reported Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="resolved-tab" data-toggle="tab" href="#resolved" role="tab">
                            Resolved/Refunded
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="transactionTabsContent">
                    <!-- Reported Transactions Tab -->
                    <div class="tab-pane fade show active" id="reported" role="tabpanel">
                        @include('reported.partials.reported_tab')
                    </div>

                    <!-- Resolved/Refunded Tab -->
                    <div class="tab-pane fade" id="resolved" role="tabpanel">
                        @include('reported.partials.resolved_tab')
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
    <!-- Modal content remains the same -->
</div>

<!-- Hidden fields for routes -->
<input type="hidden" id="reportedIndexRoute" value="{{ route('reported.index') }}">
<input type="hidden" id="resolvedIndexRoute" value="{{ route('reported.index') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@section('scripts')
    <script src="{{ asset('assets/js/reported.js') }}"></script>
@endsection