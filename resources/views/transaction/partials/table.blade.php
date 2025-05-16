@php
    $columns = [
        'all' => ['id', 'invoice', 'status', 'service', 'username', 'provider', 'plan', 'amount', 
                 'phone', 'card', 'meter', 'quantity', 'token', 'epin', 'which_api', 'updated_by', 'date', 'actions'],
        'data' => ['invoice', 'status', 'service', 'username', 'amount', 'phone', 'updated_by', 'date', 'actions'],
        'airtime' => ['invoice', 'status', 'service', 'username', 'amount', 'phone', 'updated_by', 'date', 'actions'],
        'cable' => ['invoice', 'status', 'service', 'username', 'amount', 'phone', 'updated_by', 'date', 'actions'],
        'electricity' => ['invoice', 'status', 'service', 'username', 'amount', 'phone', 'updated_by', 'date', 'actions'],
        'exam' => ['invoice', 'status', 'service', 'username', 'amount', 'phone', 'updated_by', 'date', 'actions']
    ];
@endphp

<table class="table" id="{{ $type }}-transaction-table">
    <thead>
        <tr>
            @foreach($columns[$type] as $column)
                <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $transaction)
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
            data-epin="{{ $transaction->epin }}"
            data-updated_by="{{ $transaction->updated_by }}"
            data-date="{{ $transaction->created_at->format('d/m/Y') }}">
            
            @foreach($columns[$type] as $column)
                @if($column == 'id')
                    <td>{{ $transaction->id }}</td>
                @elseif($column == 'invoice')
                    <td>{{ $transaction->transaction_id }}</td>
                @elseif($column == 'status')
                    <td>
                        <span class="status {{ strtolower($transaction->status) === 'successful' ? 'completed' : 'cancel' }}">
                            {{ ucfirst($transaction->status) }}
                        </span>
                    </td>
                @elseif($column == 'service')
                    <td>
                        <div class="d-flex">
                            <span><img src="{{ $transaction->image }}" alt="User Image" class="img-thumbnails rounded-circle" width="40"></span>
                            <div class="ms-2"><span>{{ $transaction->service }}</span></div>
                        </div>
                    </td>
                @elseif($column == 'username')
                    <td>{{ $transaction->username }}</td>
                @elseif($column == 'provider')
                    <td>{{ $transaction->service_provider }}</td>
                @elseif($column == 'plan')
                    <td>{{ $transaction->service_plan }}</td>
                @elseif($column == 'amount')
                    <td>₦{{ number_format($transaction->amount ?? 0, 2) }}</td>
                @elseif($column == 'phone')
                    <td>{{ $transaction->phone_number }}</td>
                @elseif($column == 'card')
                    <td>{{ $transaction->smart_card_number }}</td>
                @elseif($column == 'meter')
                    <td>{{ $transaction->meter_number }}</td>
                @elseif($column == 'quantity')
                    <td>{{ $transaction->quantity }}</td>
                @elseif($column == 'token')
                    <td>{{ $transaction->electricity_token }}</td>
                @elseif($column == 'epin')
                    <td>{{ $transaction->epin }}</td>
                    @elseif($column == 'which_api')
                    <td>{{ $transaction->which_api }}</td>
                    @elseif($column == 'updated_by')
                    <td>{{ $transaction->updated_by }}</td>
                @elseif($column == 'date')
                    <td>{{ $transaction->created_at->format('d/m/Y') }}</td>
                @elseif($column == 'actions')
                <td>
                    <a href="{{ route('transaction.reports', $transaction->transaction_id) }}" class="report-link">
                        <span class="material-icons-outlined datavisibility visib">visibility</span>
                    </a>
                    @if(!in_array(strtolower($transaction->status), ['refunded', 'resolved', 'successful', 'failed']))
                    <button class="btn btn-sm btn-warning refresh-btn" data-id="{{ $transaction->transaction_id }}">
                        Refresh Status
                    </button>
                @endif   
                </td>
                @endif
            @endforeach
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($columns[$type]) }}" class="text-center">No transactions found</td>
        </tr>
        @endforelse
    </tbody>
</table>

