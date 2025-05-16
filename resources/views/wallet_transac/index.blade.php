@extends('layout')

@section('dashboard-content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Wallet Transactions</h5>
                
               <!-- Modern Filter + Search Header -->
               <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

                <!-- Search Field -->
                <div class="position-relative" style="max-width: 320px; width: 100%;">
                    <input type="text" 
                               id="walletSearchInput"
                               class="form-control pe-5 py-2 rounded-pill border border-secondary-subtle shadow-sm" 
                               placeholder="Search transactions..."
                               value="{{ $searchTerm ?? '' }}">
                    <span class="position-absolute top-50 end-0 translate-middle-y pe-3 text-muted">
                        <i class="material-icons-outlined" style="font-size: 18px; line-height: 1;">search</i>
                    </span>
                </div>
                

                <!-- Sort Dropdown -->
                <div class="d-flex align-items-center gap-2">
                    <label for="walletSort" class="text-muted fw-semibold mb-0">Sort:</label>
                    <select id="walletSort"
                                    class="form-select rounded-pill shadow-sm border-secondary-subtle"
                                    style="min-width: 240px;">
                        <option value="created_at_desc" {{ $sortColumn == 'created_at' && $sortDirection == 'desc' ? 'selected' : '' }}>🕒 Newest First</option>
                        <option value="created_at_asc" {{ $sortColumn == 'created_at' && $sortDirection == 'asc' ? 'selected' : '' }}>📅 Oldest First</option>
                        <option value="username_asc" {{ $sortColumn == 'username' && $sortDirection == 'asc' ? 'selected' : '' }}>🔤 Username (A-Z)</option>
                        <option value="username_desc" {{ $sortColumn == 'username' && $sortDirection == 'desc' ? 'selected' : '' }}>🔡 Username (Z-A)</option>
                        <option value="wallet_balance_desc" {{ $sortColumn == 'wallet_balance' && $sortDirection == 'desc' ? 'selected' : '' }}>💰 Amount (High → Low)</option>
                        <option value="wallet_balance_asc" {{ $sortColumn == 'wallet_balance' && $sortDirection == 'asc' ? 'selected' : '' }}>💸 Amount (Low → High)</option>
                    </select>
                </div>

            </div>
                
                <!-- Transactions Table Container -->
                <div id="wallet-table-container" class="table-responsive">
                    @include('wallet_transac.partials.table', ['walletTransactions' => $walletTransactions])
                </div>
                
                <!-- Pagination Container -->
                <div id="wallet-pagination-container" class="d-flex justify-content-center mt-4">
                    {{ $walletTransactions->links('vendor.pagination.bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="wallettransactionModal" tabindex="-1" aria-labelledby="wallettransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionModalLabel">Transaction Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Invoice:</strong> <span id="walletmodalInvoice"></span></p>
                        <p><strong>Status:</strong> <span id="walletmodalStatus"></span></p>
                        <p><strong>Username:</strong> <span id="walletmodalUsername"></span></p>
                        <p><strong>Transaction Type:</strong> <span id="walletmodalType"></span></p>
                        <p><strong>Service:</strong> <span id="walletmodalService"></span></p>
                        <p><strong>Sender Email:</strong> <span id="walletmodalSender"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Receiver Email:</strong> <span id="walletmodalReceiver"></span></p>
                        <p><strong>Amount:</strong> <span id="walletmodalAmount"></span></p>
                        <p><strong>Balance Before:</strong> <span id="walletmodalBalanceBefore"></span></p>
                        <p><strong>Balance After:</strong> <span id="walletmodalBalanceAfter"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger refund-btns" id="walletrefundBtn" disabled>
                    Refund
                </button>
                <button class="btn btn-warning" id="debitBtn">Debit</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden fields for routes and CSRF -->
<input type="hidden" id="walletIndexRoute" value="{{ route('wallet_transac.index') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@section('scripts')
    <script src="{{ asset('assets/js/wallet.js') }}"></script>
@endsection