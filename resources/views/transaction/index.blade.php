@extends('layout')

@section('dashboard-content')

<div>
    <h1>Transactions</h1>

        <!-- Tabs -->
        <ul class="nav trans-tab nav-tabs" id="transactionTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="all-transaction-tab" data-toggle="tab" href="#all-transaction" role="tab" aria-controls="all-transaction" aria-selected="true">All Transactions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="data-transaction-tab" data-toggle="tab" href="#data-transaction" role="tab" aria-controls="data-transaction" aria-selected="false">Data Transactions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="airtime-transaction-tab" data-toggle="tab" href="#airtime-transaction" role="tab" aria-controls="airtime-transaction" aria-selected="false">Airtime Transactions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="cable-transaction-tab" data-toggle="tab" href="#cable-transaction" role="tab" aria-controls="cable-transaction" aria-selected="false">Cable Transactions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="electricity-transaction-tab" data-toggle="tab" href="#electricity-transaction" role="tab" aria-controls="electricity-transaction" aria-selected="false">Electricity Transactions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="exam-pins-transaction-tab" data-toggle="tab" href="#exam-pins-transaction" role="tab" aria-controls="exam-pins-transaction" aria-selected="false">Exam Pins Transactions</a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="transactionTabsContent">
            <!-- All Transaction Tab -->
            <div class="tab-pane fade show active" id="all-transaction" role="tabpanel" aria-labelledby="all-transaction-tab">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Task Overview</h5>
                                <!-- Search Input -->
                                <div class="d-flex justify-content-end mb-3">
                                    <input type="hidden" id="transactionIndexRoute" value="{{ route('transaction.index') }}">
                                  <div class="search-container">
                                    <form>
                                        <input type="text" 
                                               id="transactionSearchInput" 
                                               class="form-control search-box" 
                                               placeholder="Search transactions..." 
                                               data-route="{{ route('transaction.index') }}" 
                                               data-table="#transactions-table" 
                                               data-pagination=".transactions-pagination">
                                        <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                                    </form>
                                  </div>
                              </div>
                            <!-- Sorting Dropdown -->
                            <!-- Sorting Dropdown -->
                            <div class="mb-3">
                                <label for="sort">Sort By:</label>
                                <form id="sortForm" method="POST" action="{{ route('transaction.index') }}">
                                    @csrf
                                    <select id="sort" name="sort" onchange="document.getElementById('sortForm').submit();">
                                        <option value="created_at_desc" {{ request('sort') === 'created_at_desc' ? 'selected' : '' }}>
                                            Newest First
                                        </option>
                                        <option value="created_at_asc" {{ request('sort') === 'created_at_asc' ? 'selected' : '' }}>
                                            Oldest First
                                        </option>
                                        <option value="amount_desc" {{ request('sort') === 'amount_desc' ? 'selected' : '' }}>
                                            Amount (High to Low)
                                        </option>
                                        <option value="amount_asc" {{ request('sort') === 'amount_asc' ? 'selected' : '' }}>
                                            Amount (Low to High)
                                        </option>
                                        <option value="status_asc" {{ request('sort') === 'status_asc' ? 'selected' : '' }}>
                                            Status (A-Z)
                                        </option>
                                        <option value="status_desc" {{ request('sort') === 'status_desc' ? 'selected' : '' }}>
                                            Status (Z-A)
                                        </option>
                                    </select>
                                </form>
                            </div>
                                <div class="table-responsive">
                                    
                                  <table class="table" id="all-transaction-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Invoice</th>
                                                <th>Status</th>
                                                <th>Service</th>
                                                <th>Username</th>
                                                <th>Service Provider</th>
                                                <th>Service Plan</th>
                                                <th>Amount</th>
                                                <th>Phone Number</th>
                                                <th>Smart Card Number</th>
                                                <th>Meter Number</th>
                                                <th>Quantity</th>
                                                <th>Electricity Token</th>
                                                <th>ePIN</th>
                                                <th>Date </th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if($transactions->isEmpty())
                                            <tr>
                                                <td colspan="15" class="text-center">No data available</td>
                                                </tr>
                                            @else

                                            @foreach($transactions as $transaction)
                                            <tr data-id="{{ $transaction->id }}"
                                              data-invoice="{{ $transaction->transaction_id }}"
                                              data-status="{{ $transaction->status }}"
                                              data-service="{{ $transaction->service }}"
                                              data-username="{{ $transaction->username }}"
                                              data-provider="{{ $transaction->service_provider }}"
                                              data-plan="{{ $transaction->service_plan }}"
                                              data-amount="{{ $transaction->amount }}"
                                              data-phone="{{ $transaction->phone_number }}"
                                              data-card="{{ $transaction->smart_card_number }}"
                                              data-meter="{{ $transaction->meter_number }}"
                                              data-quantity="{{ $transaction->quantity }}"
                                              data-token="{{ $transaction->electricity_token }}"
                                              data-epin="{{ $transaction->epin }}">
                                            <td>{{ $transaction->id }}</td>
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td><span class="status {{ strtolower($transaction->status) === 'successful' ? 'completed #198754' : 'cancel #dc3545' }}">
                                              {{ ucfirst($transaction->status) }}
                                          </span></td>
                                                <td><div class="d-flex">
                                                  <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                                  <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                              </div></td>                                                <td>{{ $transaction->username }}</td>
                                                <td>{{ $transaction->service_provider }}</td>
                                                <td>{{ $transaction->service_plan }}</td>
                                                <td>₦{{number_format($transaction->amount ?? 0, 2) }}</td>
                                                <td>{{ $transaction->phone_number }}</td>
                                                <td>{{ $transaction->smart_card_number }}</td>
                                                <td>{{ $transaction->meter_number }}</td>
                                                <td>{{ $transaction->quantity }}</td>
                                                <td>{{ $transaction->electricity_token }}</td>
                                                <td>{{ $transaction->epin }}</td>
                                                <td>{{ $transaction->created_at->format('d/m/Y') }} </td>
                                                <td>
                                                  <span class="material-icons-outlined visibility" data-toggle="modal" data-target="#transactionModal">visibility</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-center mt-4">
                                    {{ $transactions->links() }}
                                </div>
                                <!-- Transaction Details Modal -->
                                @foreach($transactions as $transaction)
                                <div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
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
                                                        <p><strong>Invoice:</strong> <span id="modalInvoice"></span></p>
                                                        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                                                        <p><strong>Service:</strong> <span id="modalService"></span></p>
                                                        <p><strong>Username:</strong> <span id="modalUsername"></span></p>
                                                        <p><strong>Service Provider:</strong> <span id="modalProvider"></span></p>
                                                        <p><strong>Service Plan:</strong> <span id="modalPlan"></span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Amount:</strong> <span id="modalAmount"></span></p>
                                                        <p><strong>Phone Number:</strong> <span id="modalPhone"></span></p>
                                                        <p><strong>Smart Card Number:</strong> <span id="modalCard"></span></p>
                                                        <p><strong>Meter Number:</strong> <span id="modalMeter"></span></p>
                                                        <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
                                                        <p><strong>Electricity Token:</strong> <span id="modalToken"></span></p>
                                                        <p><strong>ePIN:</strong> <span id="modalEpin"></span></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                              <a href="javascript:void(0);" class="btn btn-danger refund-btn" id="refundBtn"
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
            </div>

            <!-- Data Transaction Tab -->
            <div class="tab-pane fade" id="data-transaction" role="tabpanel" aria-labelledby="data-transaction-tab">
              <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Task Overview</h5>
                            <!-- Search Input -->
                            <div class="d-flex justify-content-end mb-3">
                                <input type="hidden" id="dataIndexRoute" value="{{ route('transaction.index') }}">
                                  <div class="search-container">
                                    <form>
                                        <input type="text" 
                                               id="dataSearchInput" 
                                               class="form-control search-box" 
                                               placeholder="Search data..." 
                                               data-route="{{ route('transaction.index') }}" 
                                               data-table="#data-table" 
                                               data-pagination=".data-pagination">
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
                                
                              <table class="table" id="data-transaction-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Service</th>
                                            <th>Username</th>
                                            <th>Amount</th>
                                            <th>Phone Number</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      @if($dataTransactions->isEmpty())
                                      <tr>
                                          <td colspan="15" class="text-center">No data available</td>
                                          </tr>
                                      @else

                                        @foreach($dataTransactions as $transaction)
                                        <tr data-id="{{ $transaction->id }}" 
                                          data-invoice="{{ $transaction->transaction_id }}" 
                                          data-status="{{ $transaction->status }}" 
                                          data-service="{{ $transaction->service }}"
                                          data-username="{{ $transaction->username }}"
                                          data-amount="{{ $transaction->amount }}"
                                          data-phone="{{ $transaction->phone_number }}">
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td>{{ $transaction->status }}</td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                
                                          <td>{{ $transaction->username }}</td>
                                            <td>₦{{ number_format($transaction->amount ?? 0, 2) }}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="material-icons-outlined datavisibility visib" data-toggle="modal" data-target="#datatransactionModal">visibility</span>
                                                

                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-center mt-4">
                                {{ $dataTransactions->links() }}
                            </div>
                            <!-- Data Details Modal -->
                            @foreach($dataTransactions as $transaction)
                            <div class="modal fade" id="datatransactionModal" tabindex="-1" aria-labelledby="datatransactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="modalInvoices"></span></p>
                                                    <p><strong>Status:</strong> <span id="modalStatuss"></span></p>
                                                    <p><strong>Service:</strong> <span id="modalServices"></span></p>
                                                    <p><strong>Username:</strong> <span id="modalUsernames"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="modalAmounts"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="modalPhones"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                          <a href="javascript:void(0);" class="btn btn-danger refund-btn" id="refundBtns"
                                                    data-id="{{ $transaction->id }}"
                                                    {{ $transaction->status == 'Refunded' ? 'disabled' : '' }}>
                                                    Refund
                                                  </a>  
                                            <a href="" class="btn btn-warning" id="debitBtn">Debit</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <!-- Airtime Transaction Tab -->
            <div class="tab-pane fade" id="airtime-transaction" role="tabpanel" aria-labelledby="airtime-transaction-tab">
              <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Task Overview</h5>
                            <!-- Search Input -->
                            <div class="d-flex justify-content-end mb-3">
                                <input type="hidden" id="airtimeIndexRoute" value="{{ route('transaction.index') }}">
                              <div class="search-container">
                                <form>
                                    <input type="text" 
                                           id="airtimeSearchInput" 
                                           class="form-control search-box" 
                                           placeholder="Search airtime..." 
                                           data-route="{{ route('transaction.index') }}" 
                                           data-table="#airtime-table" 
                                           data-pagination=".airtime-pagination">
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
                                
                              <table class="table" id="airtime-transaction-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Service</th>
                                            <th>Username</th>
                                            <th>Amount</th>
                                            <th>Phone Number</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      @if($airtimeTransactions->isEmpty())
                                      <tr>
                                          <td colspan="15" class="text-center">No data available</td>
                                          </tr>
                                      @else

                                        @foreach($airtimeTransactions as $transaction)
                                        <tr data-id="{{ $transaction->id }}" 
                                          data-invoice="{{ $transaction->transaction_id }}" 
                                          data-status="{{ $transaction->status }}" 
                                          data-service="{{ $transaction->service }}"
                                          data-username="{{ $transaction->username }}"
                                          data-amount="{{ $transaction->amount }}"
                                          data-phone="{{ $transaction->phone_number }}">
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td><span class="status {{ strtolower($transaction->status) === 'successful' ? 'completed #198754' : 'cancel #dc3545' }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span></td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                
                                          <td>{{ $transaction->username }}</td>
                                            <td>₦{{ number_format($transaction->amount ?? 0, 2)}}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="material-icons-outlined airtimevisibility visib" data-toggle="modal" data-target="#airtimetransactionModal">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-center mt-4">
                                {{ $airtimeTransactions->links() }}
                            </div>
                            <!-- Airtime Details Modal -->
                            @foreach($airtimeTransactions as $transaction)
                            <div class="modal fade" id="airtimetransactionModal" tabindex="-1" aria-labelledby="airtimetransactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="airtimemodalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="airtimemodalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="airtimemodalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="airtimemodalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="airtimemodalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="airtimemodalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                          <a href="javascript:void(0);" class="btn btn-danger refund-btn" id="airtimerefundBtn"
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
            </div>

            <!-- Cable Transactions Tab -->
            <div class="tab-pane fade" id="cable-transaction" role="tabpanel" aria-labelledby="cable-transaction-tab">
              <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Task Overview</h5>
                            <!-- Search Input -->
                            <div class="d-flex justify-content-end mb-3">
                                <input type="hidden" id="cableIndexRoute" value="{{ route('transaction.index') }}">
                                <div class="search-container">
                                  <form>
                                      <input type="text" 
                                             id="cableSearchInput" 
                                             class="form-control search-box" 
                                             placeholder="Search cable..." 
                                             data-route="{{ route('transaction.index') }}" 
                                             data-table="#cable-table" 
                                             data-pagination=".cable-pagination">
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
                                
                              <table class="table" id="cable-transaction-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Service</th>
                                            <th>Username</th>
                                            <th>Amount</th>
                                            <th>Phone Number</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      @if($cableTransactions->isEmpty())
                                      <tr>
                                          <td colspan="15" class="text-center">No data available</td>
                                          </tr>
                                      @else
                                        @foreach($cableTransactions as $transaction)
                                        <tr data-id="{{ $transaction->id }}" 
                                          data-invoice="{{ $transaction->transaction_id }}" 
                                          data-status="{{ $transaction->status }}" 
                                          data-service="{{ $transaction->service }}"
                                          data-username="{{ $transaction->username }}"
                                          data-amount="{{ $transaction->amount }}"
                                          data-phone="{{ $transaction->phone_number }}">
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td><span class="status {{ strtolower($transaction->status) === 'successful' ? 'completed #198754' : 'cancel #dc3545' }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span></td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                
                                          <td>{{ $transaction->username }}</td>
                                            <td>₦{{ number_format($transaction->amount ?? 0, 2)}}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="material-icons-outlined cablevisibility visib" data-toggle="modal" data-target="#cabletransactionModal">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-center mt-4">
                                {{ $cableTransactions->links() }}
                            </div>
                            <!-- Cable Details Modal -->
                            @foreach($cableTransactions as $transaction)
                            <div class="modal fade" id="cabletransactionModal" tabindex="-1" aria-labelledby="cabletransactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="cablemodalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="cablemodalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="cablemodalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="cablemodalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="cablemodalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="cablemodalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                          <a href="javascript:void(0);" class="btn btn-danger refund-btn" id="cablerefundBtn"
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
            </div>

            <!-- Electricity Transactions Tab -->
            <div class="tab-pane fade" id="electricity-transaction" role="tabpanel" aria-labelledby="electricity-transaction-tab">
              <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Task Overview</h5>
                            <!-- Search Input -->
                            <div class="d-flex justify-content-end mb-3">
                                <input type="hidden" id="electricityIndexRoute" value="{{ route('transaction.index') }}">
                                <div class="search-container">
                                  <form>
                                      <input type="text" 
                                             id="electricitySearchInput" 
                                             class="form-control search-box" 
                                             placeholder="Search electricity..." 
                                             data-route="{{ route('transaction.index') }}" 
                                             data-table="#electricity-table" 
                                             data-pagination=".electricity-pagination">
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
                                
                              <table class="table" id="electricity-transaction-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Service</th>
                                            <th>Username</th>
                                            <th>Amount</th>
                                            <th>Phone Number</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      @if($electricityTransactions->isEmpty())
                                      <tr>
                                          <td colspan="15" class="text-center">No data available</td>
                                          </tr>
                                      @else

                                        @foreach($electricityTransactions as $transaction)
                                        <tr data-id="{{ $transaction->id }}" 
                                          data-invoice="{{ $transaction->transaction_id }}" 
                                          data-status="{{ $transaction->status }}" 
                                          data-service="{{ $transaction->service }}"
                                          data-username="{{ $transaction->username }}"
                                          data-amount="{{ $transaction->amount }}"
                                          data-phone="{{ $transaction->phone_number }}">
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td><span class="status {{ strtolower($transaction->status) === 'successful' ? 'completed #198754' : 'cancel #dc3545' }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span></td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                
                                          <td>{{ $transaction->username }}</td>
                                            <td>₦{{ number_format($transaction->amount ?? 0, 2)}}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="material-icons-outlined electricityvisibility visib" data-toggle="modal" data-target="#electricitytransactionModal">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-center mt-4">
                                {{ $electricityTransactions->links() }}
                            </div>
                            <!-- Electricity Details Modal -->
                            @foreach($electricityTransactions as $transaction)
                            <div class="modal fade" id="electricitytransactionModal" tabindex="-1" aria-labelledby="electricitytransactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="electricitymodalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="electricitymodalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="electricitymodalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="electricitymodalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="electricitymodalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="electricitymodalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                          <a href="javascript:void(0);" class="btn btn-danger refund-btn" id="electricityrefundBtn"
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
            </div>

            <!-- Exam Pins Transactions Tab -->
            <div class="tab-pane fade" id="exam-pins-transaction" role="tabpanel" aria-labelledby="exam-pins-transaction-tab">
              <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Task Overview</h5>
                            <!-- Search Input -->
                            <div class="d-flex justify-content-end mb-3">
                                <input type="hidden" id="examIndexRoute" value="{{ route('transaction.index') }}">
                                <div class="search-container">
                                  <form>
                                      <input type="text" 
                                             id="examSearchInput" 
                                             class="form-control search-box" 
                                             placeholder="Search exam..." 
                                             data-route="{{ route('transaction.index') }}" 
                                             data-table="#exam-table" 
                                             data-pagination=".exam-pagination">
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
                                
                              <table class="table" id="exam-transaction-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Service</th>
                                            <th>Username</th>
                                            <th>Amount</th>
                                            <th>Phone Number</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      @if($examTransactions->isEmpty())
                                      <tr>
                                          <td colspan="15" class="text-center">No data available</td>
                                          </tr>
                                      @else

                                        @foreach($examTransactions as $transaction)
                                        <tr data-id="{{ $transaction->id }}" 
                                          data-invoice="{{ $transaction->transaction_id }}" 
                                          data-status="{{ $transaction->status }}" 
                                          data-service="{{ $transaction->service }}"
                                          data-username="{{ $transaction->username }}"
                                          data-amount="{{ $transaction->amount }}"
                                          data-phone="{{ $transaction->phone_number }}">
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td><span class="status {{ strtolower($transaction->status) === 'successful' ? 'completed #198754' : 'cancel #dc3545' }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span></td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                
                                          <td>{{ $transaction->username }}</td>
                                            <td>₦{{ number_format($transaction->amount ?? 0, 2)}}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="material-icons-outlined examvisibility visib" data-toggle="modal" data-target="#examtransactionModal">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-center mt-4">
                                {{ $examTransactions->links() }}
                            </div>
                            <!-- Exam Details Modal -->
                            @foreach($examTransactions as $transaction)
                            <div class="modal fade" id="examtransactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="exammodalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="exammodalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="exammodalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="exammodalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="exammodalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="exammodalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                          <a href="javascript:void(0);" class="btn btn-danger refund-btn" id="examrefundBtn"
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
            </div>
        </div>
</div>

<script>
    function filterData(filter, type, startDate = null, endDate = null) {
    let url = `/filter-data`;
    let formData = new FormData();
    formData.append('filter', filter);
    formData.append('type', type);
    if (startDate && endDate) {
        formData.append('startDate', startDate);
        formData.append('endDate', endDate);
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        console.log(data); // Log the server response
        // Update the UI with the filtered data
    })
    .catch(error => console.error('Error fetching data:', error));
}
</script>

@stop