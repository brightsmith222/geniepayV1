@extends('layout')

@section('dashboard-content')
<div>
    <h1>Transactions</h1>

    <!-- Tabs -->
    <ul class="nav trans-tab nav-tabs" id="transactionTabs" role="tablist">
        @foreach(['all', 'data', 'airtime', 'cable', 'electricity', 'exam'] as $tab)
        <li class="nav-item">
            <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
               id="{{ $tab }}-transaction-tab" 
               data-toggle="tab" 
               href="#{{ $tab }}-transaction" 
               role="tab" 
               aria-controls="{{ $tab }}-transaction" 
               aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ ucfirst($tab) }} Transactions
            </a>
        </li>
        @endforeach
    </ul>

    <!-- Tab Content -->
<div class="tab-content" id="transactionTabsContent">
    @foreach(['all', 'data', 'airtime', 'cable', 'electricity', 'exam'] as $tab)
    @php
        $transactionsVar = $tab.'Transactions';
        $transactions = isset($$transactionsVar) ? $$transactionsVar : collect();
    @endphp
    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
         id="{{ $tab }}-transaction" 
         role="tabpanel" 
         aria-labelledby="{{ $tab }}-transaction-tab">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ ucfirst($tab) }} Transactions</h5>
                        
                     <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                        <!-- Search Box -->
                        <div class="position-relative" style="max-width: 320px; width: 100%;">
                            <input type="text" 
                            id="{{ $tab }}SearchInput"
                            class="form-control pe-5 py-2 rounded-pill border border-secondary-subtle shadow-sm"
                            placeholder="Search {{ $tab }} transactions..."
                            value="{{ $searchTerm ?? '' }}">
                            <span class="position-absolute top-50 end-0 translate-middle-y pe-3 text-muted">
                                <i class="material-icons-outlined" style="font-size: 18px; line-height: 1;">search</i>
                            </span>
                        </div>
                        
                        <!-- Sorting Dropdown -->
                        <div class="d-flex align-items-center gap-2">
                            <label for="userSort" class="text-muted fw-semibold mb-0">Sort:</label>
                            <select id="{{ $tab }}Sort" class="form-select rounded-pill shadow-sm border-secondary-subtle"
                            style="min-width: 240px;">
                                <option value="created_at_desc" {{ $sortColumn == 'created_at' && $sortDirection == 'desc' ? 'selected' : '' }}>🕒 Newest First</option>
                                <option value="created_at_asc" {{ $sortColumn == 'created_at' && $sortDirection == 'asc' ? 'selected' : '' }}>📅 Oldest First</option>
                                <option value="amount_desc" {{ $sortColumn == 'amount' && $sortDirection == 'desc' ? 'selected' : '' }}>💰 Amount (High → Low)</option>
                                <option value="amount_asc" {{ $sortColumn == 'amount' && $sortDirection == 'asc' ? 'selected' : '' }}>💸 Amount (Low → High)</option>
                                <option value="status_asc" {{ $sortColumn == 'status' && $sortDirection == 'asc' ? 'selected' : '' }}>🔤 Status (A-Z)</option>
                                <option value="status_desc" {{ $sortColumn == 'status' && $sortDirection == 'desc' ? 'selected' : '' }}>🔡 Status (Z-A)</option>
                            </select>
                        </div>
                    </div>
                        
                        <!-- Transactions Table Container -->
                        <div id="{{ $tab }}-table-container" class="table-responsive">
                    @include('transaction.partials.table', [
                                'transactions' => $transactions,
                                'type' => $tab
                            ])
                        </div>
                        
                        <!-- Pagination Container -->
                        <div id="{{ $tab }}-pagination-container" class="d-flex justify-content-center mt-4">
                        {{$transactions->links('vendor.pagination.bootstrap-4')}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
</div>


<!-- Hidden fields for routes and CSRF -->
<input type="hidden" id="transactionRefreshRoute"value="{{ route('transaction.refresh') }}">
<input type="hidden" id="transactionIndexRoute" value="{{ route('transaction.index') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@section('scripts')
    <script src="{{ asset('assets/js/transactions.js') }}"></script>
@endsection