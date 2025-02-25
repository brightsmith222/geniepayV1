@extends('layout')

@section('dashboard-content')

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
                                  <div class="search-container">
                                      <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
                                      <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                                  </div>
                              </div>
                                <div class="table-responsive">
                                    
                                  <table class="table" id="all-transaction-table">
                                        <thead>
                                            <tr>
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
                                                <th>Date</th>
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
                                            <tr>
                                                <td>{{ $transaction->transaction_id }}</td>
                                                <td><span class="status {{ $transaction->status == 'Successful' ? 'completed #198754' : 'cancel #dc3545' }}">
                                                  {{ ucfirst($transaction->status) }}
                                              </span></td>
                                                <td><div class="d-flex">
                                                  <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                                  <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                              </div></td>                                                <td>{{ $transaction->username }}</td>
                                                <td>{{ $transaction->service_provider }}</td>
                                                <td>{{ $transaction->service_plan }}</td>
                                                <td>{{ $transaction->amount }}</td>
                                                <td>{{ $transaction->phone_number }}</td>
                                                <td>{{ $transaction->smart_card_number }}</td>
                                                <td>{{ $transaction->meter_number }}</td>
                                                <td>{{ $transaction->quantity }}</td>
                                                <td>{{ $transaction->electricity_token }}</td>
                                                <td>{{ $transaction->epin }}</td>
                                                <td>{{ $transaction->created_at }}</td>
                                                <td>
                                                  <span class="material-icons-outlined visibility" data-toggle="modal" data-target="#transactionModal">visibility</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Transaction Details Modal -->
                                <div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
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
                                                <a href="javascript:void(0);" class="btn btn-danger" id="refundBtn">Refund</a>
                                                <a href="" class="btn btn-warning" id="debitBtn">Debit</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                              <div class="search-container">
                                  <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
                                  <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                              </div>
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
                                        <tr>
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td>{{ $transaction->status }}</td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                <td>{{ $transaction->username }}</td>
                                            <td>{{ $transaction->amount }}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at }}</td>
                                            <td>
                                                <span class="material-icons-outlined datavisibility visib">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <!-- Data Details Modal -->
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
                                                    <p><strong>Invoice:</strong> <span id="modalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="modalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="modalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="modalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="modalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="javascript:void(0);" class="btn btn-danger" id="refundBtn">Refund</a>
                                            <a href="" class="btn btn-warning" id="debitBtn">Debit</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                              <div class="search-container">
                                  <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
                                  <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                              </div>
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
                                        <tr>
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td>{{ $transaction->status }}</td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                <td>{{ $transaction->username }}</td>
                                            <td>{{ $transaction->amount }}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at }}</td>
                                            <td>
                                                <span class="material-icons-outlined airtimevisibility visib">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <!-- Airtime Details Modal -->
                            <div class="modal fade" id="airtimetransactionModal" tabindex="-1" aria-labelledby="airtimetransactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="modalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="modalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="modalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="modalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="modalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="javascript:void(0);" class="btn btn-danger" id="refundBtn">Refund</a>
                                            <a href="" class="btn btn-warning" id="debitBtn">Debit</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                              <div class="search-container">
                                  <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
                                  <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                              </div>
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
                                        <tr>
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td>{{ $transaction->status }}</td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                <td>{{ $transaction->username }}</td>
                                            <td>{{ $transaction->amount }}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at }}</td>
                                            <td>
                                                <span class="material-icons-outlined cablevisibility visib">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <!-- Cable Details Modal -->
                            <div class="modal fade" id="cabletransactionModal" tabindex="-1" aria-labelledby="cabletransactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="modalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="modalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="modalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="modalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="modalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="javascript:void(0);" class="btn btn-danger" id="refundBtn">Refund</a>
                                            <a href="" class="btn btn-warning" id="debitBtn">Debit</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                              <div class="search-container">
                                  <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
                                  <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                              </div>
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
                                        <tr>
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td>{{ $transaction->status }}</td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                <td>{{ $transaction->username }}</td>
                                            <td>{{ $transaction->amount }}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at }}</td>
                                            <td>
                                                <span class="material-icons-outlined electricityvisibility visib">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <!-- Electricity Details Modal -->
                            <div class="modal fade" id="electricitytransactionModal" tabindex="-1" aria-labelledby="electricitytransactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="modalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="modalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="modalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="modalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="modalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="javascript:void(0);" class="btn btn-danger" id="refundBtn">Refund</a>
                                            <a href="" class="btn btn-warning" id="debitBtn">Debit</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                              <div class="search-container">
                                  <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
                                  <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                              </div>
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
                                        <tr>
                                            <td>{{ $transaction->transaction_id }}</td>
                                            <td>{{ $transaction->status }}</td>
                                            <td><div class="d-flex">
                                              <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                                              <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                                          </div></td>                                                <td>{{ $transaction->username }}</td>
                                            <td>{{ $transaction->amount }}</td>
                                            <td>{{ $transaction->phone_number }}</td>
                                            <td>{{ $transaction->created_at }}</td>
                                            <td>
                                                <span class="material-icons-outlined examvisibility visib">visibility</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <!-- Exam Details Modal -->
                            <div class="modal fade" id="examtransactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
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
                                                    <p><strong>Invoice:</strong> <span id="modalInvoice"></span></p>
                                                    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                                                    <p><strong>Service:</strong> <span id="modalService"></span></p>
                                                    <p><strong>Username:</strong> <span id="modalUsername"></span></p>
                                                    
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> <span id="modalAmount"></span></p>
                                                    <p><strong>Phone Number:</strong> <span id="modalPhone"></span></p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="javascript:void(0);" class="btn btn-danger" id="refundBtn">Refund</a>
                                            <a href="" class="btn btn-warning" id="debitBtn">Debit</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>

@stop