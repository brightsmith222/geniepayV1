@extends('layout')

@section('dashboard-content')

<div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Task Overview</h5>
                        <div class="table-responsive">
                            <!-- Search Input -->
                            <div class="d-flex justify-content-end mb-3">
                                <div class="search-container">
                                    <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
                                    <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                                </div>
                            </div>
                            <table class="table">
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  @foreach($transactions as $transaction)

                                    <tr>
                                    <td>{{ $transaction->transaction_id }}</td>
                                    <td>{{ $transaction->status }}</td>
                                    <td>{{ $transaction->service }}</td>
                                    <td>{{ $transaction->username }}</td>
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
                                                <span class="material-icons-outlined visibility">visibility</span>
                                        </td>
                                    </tr>
                                    @endforeach
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

@stop