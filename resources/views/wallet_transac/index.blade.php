@extends('layout')

@section('dashboard-content')

<!-- Table Row -->
<div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Wallet Transaction</h5>
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
                                        <th>ID</th>
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
                                    <tr>
                                        <td>TXN123456</td>
                                        <td><span class="status completed">Successful</span></td> 
                                        <td>Johnny112</td>
                                        <td>Credit</td>
                                        <td>Wallet Funded</td>
                                        <td>johnnydoe@gmail.com</td>
                                        <td>Samlarry@gmail.com</td>
                                        <td>#3500</td>
                                        <td>#6000</td>
                                        <td>#9500</td>
                                        <td>06/06/25</td>
                                        <td>
                                                <span class="material-icons-outlined visibility">visibility</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>TXN654321</td>
                                        <td><span class="status completed">Successful</span></td>
                                        <td>Tessy424</td>
                                        <td>Debit</td>                                        
                                        <td>Transfer</td>
                                        <td>tessyjoh@gmail.com</td>
                                        <td>jessygreg@gmail.com</td>
                                        <td>#5000</td>
                                        <td>#6000</td>
                                        <td>#11000</td>
                                        <td>12/12/25</td>
                                        <td>
                                                <span class="material-icons-outlined visibility">visibility</span> </td>
                                    </tr>
                                        <tr>
                                        <td>TXN789012</td>
                                        <td><span class="status cancel">Failed</span></td>
                                        <td>Tommyjay</td>
                                        <td>Credit</td>                                        
                                        <td>Wallet Funded</td>
                                        <td>tommyjay@gmail.com</td>
                                        <td>favournancy@gmail.com</td>
                                        <td>#6000</td>
                                        <td>#3000</td>
                                        <td>#9000</td>
                                        <td>02/11/25</td>
                                        <td><span class="material-icons-outlined visibility">visibility</span></td>
                                    </tr>
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
                <button class="btn btn-danger" id="refundBtn">Refund</button>
                <button class="btn btn-warning" id="debitBtn">Debit</button>
            </div>
        </div>
    </div>
</div>

                    </div>
                </div>
            </div>
        </div>

@stop