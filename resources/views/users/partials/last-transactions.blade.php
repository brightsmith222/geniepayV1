<div class="transaction-history">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="">
            <i class="fas fa-history text-primary mr-2"></i>Recent Transactions
        </h5>
        <span class="badge badge-pill badge-primary">{{ count($transactions) }} records</span>
    </div>

    @forelse($transactions as $txn)
    <div class="transaction-card mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="transaction-icon mr-3 bg-{{ $txn->service === 'airtime' ? 'primary' : ($txn->service === 'data' ? 'info' : ($txn->service === 'electricity' ? 'warning' : ($txn->service === 'cable' ? 'purple' : ($txn->service === 'exam' ? 'success' : 'secondary')))) }}-light">
                        @if($txn->service === 'airtime')
                            <i class="fas fa-mobile-alt text-primary"></i>
                        @elseif($txn->service === 'data')
                            <i class="fas fa-database text-info"></i>
                        @elseif($txn->service === 'electricity')
                            <i class="fas fa-bolt text-warning"></i>
                        @elseif($txn->service === 'cable')
                            <i class="fas fa-tv text-purple"></i>
                        @elseif($txn->service === 'exam')
                            <i class="fas fa-graduation-cap text-success"></i>
                        @else
                            <i class="fas fa-exchange-alt text-secondary"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-1 font-weight-bold text-capitalize">{{ $txn->service }}</h6>
                            <small class="text-muted">{{ $txn->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div>
                                <p class="mb-1">
                                    <span class="badge badge-{{ $txn->status == 'success' ? 'success' : 'danger' }} badge-pill">
                                        {{ ucfirst($txn->status) }}
                                    </span>
                                </p>
                                @if($txn->phone_number)
                                <small class="text-muted">
                                    <i class="fas fa-phone-alt mr-1"></i> {{ $txn->phone_number }}
                                </small>
                                @endif
                            </div>
                            <div class="text-right">
                                <h6 class="mb-0 font-weight-bold text-danger">₦{{ number_format($txn->amount, 2) }}</h6>
                                <small class="text-muted">Amount</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="empty-state text-center py-4">
        <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
        <h6 class="text-muted">No transactions found</h6>
        <small class="text-muted">This user hasn't made any transactions yet</small>
    </div>
    @endforelse
</div>