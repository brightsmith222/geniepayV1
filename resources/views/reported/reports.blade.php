@extends('layout')

@section('dashboard-content')

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4>Transaction Details</h4>
        </div>
        <div class="card-body">
            @if(!empty($transaction))
                <p><strong>Invoice ID:</strong> {{ $transaction['requestId'] ?? 'N/A' }}</p>
                <p><strong>Status:</strong> {{ ucfirst($transaction['status'] ?? 'Unknown') }}</p>
                <p><strong>Username:</strong> {{ $transaction['email'] ?? 'N/A' }}</p>
                <p><strong>Service:</strong> {{ $transaction['product_name'] ?? 'N/A' }}</p>
                <p><strong>Amount:</strong> ₦{{ number_format($transaction['amount'] ?? 0, 2) }}</p>
                <p><strong>Date:</strong> {{ isset($transaction['transaction_date']) ? date('d/m/Y', strtotime($transaction['transaction_date'])) : 'N/A' }}</p>
                <a href="{{ route('reported.index') }}" class="btn btn-secondary">Back to List</a>
            @else
                <p class="text-danger">Transaction details not found.</p>
                <a href="{{ route('reported.index') }}" class="btn btn-secondary">Back</a>
            @endif
        </div>
    </div>
</div>

@stop
