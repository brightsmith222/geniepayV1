<table class="table" id="resolved-transaction-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Invoice ID</th>
            <th>Status</th>
            <th>Username</th>
            <th>Service</th>
            <th>Amount</th>
            <th>Processed By</th>
            <th>Date Resolved</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $transaction)
        <tr>
            <td>{{ $transaction->id }}</td>
            <td>{{ $transaction->transaction_id }}</td>
            <td>
                <span class="status {{ strtolower($transaction->status) === 'refunded' ? 'completed' : 'resolved' }}">
                    {{ ucfirst($transaction->status) }}
                </span>
            </td>
            <td>{{ $transaction->username }}</td>
            <td>{{ $transaction->service }}</td>
            <td>₦{{ number_format($transaction->amount ?? 0, 2) }}</td>
            <td>{{ $transaction->updated_by ?? 'System' }}</td>
            <td>{{ $transaction->updated_at->format('d/m/Y H:i') }}</td>
            <td>
                <a href="{{ route('reported.reports', $transaction->transaction_id) }}">
                    <span class="material-icons-outlined datavisibility visib">visibility</span>
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center">No resolved transactions found</td>
        </tr>
        @endforelse
    </tbody>
</table>