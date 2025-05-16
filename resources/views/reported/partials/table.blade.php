<table class="table" id="reported-transaction-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Invoice Id</th>
            <th>Status</th>
            <th>Username</th>
            <th>Service</th>
            <th>Amount</th>
            <th>Bal Before</th>
            <th>Bal. After</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $transaction)
        <tr>

            <td>{{ $transaction->id }}</td>
            <td>{{ $transaction->transaction_id }}</td>
            <td>
                <span class="status {{ strtolower($transaction->status) === 'reported' ? 'pending' : 'cancel' }}">
                    {{ ucfirst($transaction->status) }}
                </span>
            </td>
            <td>{{ $transaction->username }}</td>
            <td>{{ $transaction->service }}</td>
            <td>₦{{ number_format($transaction->amount ?? 0, 2) }}</td>
            <td>₦{{ number_format($transaction->balance_before ?? 0, 2) }}</td>
            <td>₦{{ number_format($transaction->balance_after ?? 0, 2) }}</td>
            <td>{{ $transaction->created_at->format('d/m/Y') }}</td>
            <td>
                <a href="{{ route('reported.reports', $transaction->transaction_id) }}">
                    <span class="material-icons-outlined datavisibility visib">visibility</span>
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="10" class="text-center">No reported transactions found</td>
        </tr>
        @endforelse
    </tbody>
</table>