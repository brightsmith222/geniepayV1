@extends('layout')

@section('dashboard-content')

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4>Transaction Details</h4>
        </div>
        <div class="card-body">
            @if(isset($transaction))
                <p><strong>Invoice ID:</strong> {{ $transaction['requestId'] }}</p>
                <p><strong>Status:</strong> {{ ucfirst($transaction['status']) }}</p>
                <p><strong>Username:</strong> {{ $transaction['username'] ?? 'N/A' }}</p>
                <p><strong>Service:</strong> {{ $transaction['product_name'] ?? 'N/A' }}</p>
                <p><strong>Amount:</strong> ₦{{ number_format($transaction['amount'], 2) }}</p>
                <p><strong>Date:</strong> {{ date('d/m/Y', strtotime($transaction['date'])) }}</p>
                <a href="{{ route('reported.index') }}" class="btn btn-secondary">Back to List</a>
            @else
                <p class="text-danger">Transaction details not found.</p>
                <a href="{{ route('reported.index') }}" class="btn btn-secondary">Back</a>
            @endif
        </div>
    </div>
</div>

@stop