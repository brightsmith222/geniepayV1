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
        @forelse($walletTransactions as $transaction)
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
            <td>
                <span class="status {{ strtolower($transaction->status) === 'successful' ? 'completed' : 'cancel' }}">
                    {{ ucfirst($transaction->status) }}
                </span>
            </td>
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
        @empty
        <tr>
            <td colspan="13" class="text-center">No transactions found</td>
        </tr>
        @endforelse
    </tbody>
</table>