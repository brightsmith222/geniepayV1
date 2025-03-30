@extends('layout')

@section('dashboard-content')

<!-- Table Row -->
<div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Wallet Transaction</h5>
                         <!-- Search Input -->
                         <div class="d-flex justify-content-end mb-3">
                            <input type="hidden" id="walletIndexRoute" value="{{ route('wallet_transac.index') }}">
                                  <div class="search-container">
                                    <form>
                                        <input type="text" 
                                               id="walletSearchInput" 
                                               class="form-control search-box" 
                                               placeholder="Search wallet..." 
                                               data-route="{{ route('wallet_transac.index') }}" 
                                               data-table="#wallet-table" 
                                               data-pagination=".wallet-pagination">
                                        <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                                    </form>
                                  </div>
                        </div>
                        <!-- Sorting Dropdown -->
                        <div class="mb-3">
                            <label for="sort">Sort By:</label>
                            <select id="sort" onchange="window.location.href = this.value;">
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => 'desc']) }}"
                                        {{ request('sort') === 'created_at' && request('direction') === 'desc' ? 'selected' : '' }}>
                                    Newest First
                                </option>
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => 'asc']) }}"
                                        {{ request('sort') === 'created_at' && request('direction') === 'asc' ? 'selected' : '' }}>
                                    Oldest First
                                </option>
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'amount', 'direction' => 'desc']) }}"
                                        {{ request('sort') === 'amount' && request('direction') === 'desc' ? 'selected' : '' }}>
                                    Amount (High to Low)
                                </option>
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'amount', 'direction' => 'asc']) }}"
                                        {{ request('sort') === 'amount' && request('direction') === 'asc' ? 'selected' : '' }}>
                                    Amount (Low to High)
                                </option>
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => 'asc']) }}"
                                        {{ request('sort') === 'status' && request('direction') === 'asc' ? 'selected' : '' }}>
                                    Status (A-Z)
                                </option>
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => 'desc']) }}"
                                        {{ request('sort') === 'status' && request('direction') === 'desc' ? 'selected' : '' }}>
                                    Status (Z-A)
                                </option>
                            </select>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="wallet-transaction-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Invoice Id</th>
                                        <th>Status</th>
                                        <th>Username</th>
                                        <th>Trans Type</th>
                                        <th>Service</th>
                                        <th>Sender Email</th>
                                        <th>Receiver Email</th>
                                        <th>Amount</th>
                                        <th>Bal Before</th>
                                        <th>Bal. After</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($walletTransactions->isEmpty())
                                      <tr>
                                          <td colspan="15" class="text-center">No data available</td>
                                          </tr>
                                      @else

                                        @foreach($walletTransactions as $transaction)
                                        <tr data-id="{{ $transaction->id }}" 
                                            data-invoice="{{ $transaction->transaction_id }}" 
                                            data-status="{{ $transaction->status }}" 
                                            data-username="{{ $transaction->user }}"
                                            data-type="{{ $transaction->trans_type }}"
                                            data-service="{{ $transaction->service }}"
                                            data-sender-email="{{ $transaction->sender_email }}"
                                            data-receiver-email="{{ $transaction->receiver_email }}"
                                            data-amount="{{ $transaction->amount }}"
                                            data-balance-before="{{ $transaction->balance_before }}"
                                            data-balance-after="{{ $transaction->balance_after }}">

                                        <td>{{ $transaction->id }}</td>
                                        <td>{{ $transaction->transaction_id }}</td>
                                        <td><span class="status {{ strtolower($transaction->status) === 'successful' ? 'completed #198754' : 'cancel #dc3545' }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span></td>
                                        <td>{{ $transaction->user }}</td>
                                        <td>{{ $transaction->trans_type }}</td>                                        
                                        <td>{{ $transaction->service }}</td>
                                        <td>{{ $transaction->sender_email }}</td>
                                        <td>{{ $transaction->receiver_email }}</td>
                                        <td>₦{{ number_format($transaction->amount ?? 0, 2) }}</td>                                        
                                        <td>₦{{ number_format($transaction->balance_before ?? 0, 2) }}</td>
                                        <td>₦{{ number_format($transaction->balance_after ?? 0, 2) }}</td>
                                        <td>{{ $transaction->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="material-icons-outlined walletvisibility visib" data-toggle="modal" data-target="#wallettransactionModal">visibility</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                            {{ $walletTransactions->links('vendor.pagination.bootstrap-4') }}
                        </div>
                        <!-- Transaction Details Modal -->
  @foreach($walletTransactions as $transaction)
<div class="modal fade" id="wallettransactionModal" tabindex="-1" aria-labelledby="wallettransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form>
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
                <a href="javascript:void(0);" class="btn btn-danger refund-btns" id="walletrefundBtn"
                data-id="{{ $transaction->id }}"
                {{ $transaction->status == 'Refunded' ? 'disabled' : '' }}>
                Refund
              </a>
                  <a href="" class="btn btn-warning" id="debitBtn">Debit</a>
              </div>
            </form>
        </div>
    </div>
</div>
@endforeach

                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
    <script src="{{ URL::to('assets/js/wallet.js')}}"></script>
@endsection