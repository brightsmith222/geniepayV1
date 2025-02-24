@extends('layouts.admin')

@section('content')
<div class="dashboard">
    <div class="row">
        <!-- Summary Cards -->
        <div class="col-md-3 card">
            <h4>Total Revenue</h4>
            <p>{{ $totalRevenue }}</p>
        </div>
        <div class="col-md-3 card">
            <h4>Total Transactions</h4>
            <p>{{ $totalTransactions }}</p>
        </div>
        <div class="col-md-3 card">
            <h4>Active Users</h4>
            <p>{{ $activeUsers }}</p>
        </div>
        <div class="col-md-3 card">
            <h4>Failed Transactions</h4>
            <p>{{ $failedTransactions }}</p>
        </div>
    </div>

    <!-- Graphs -->
    <div class="charts">
        <h3>Revenue Trends</h3>
        <div id="revenue-chart"></div>
    </div>

    <!-- Recent Transactions -->
    <div class="recent-transactions">
        <h3>Recent Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentTransactions as $transaction)
                    <tr>
                        <td>{{ $transaction->user->name }}</td>
                        <td>{{ $transaction->service }}</td>
                        <td>{{ $transaction->amount }}</td>
                        <td>{{ $transaction->status }}</td>
                        <td>{{ $transaction->created_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection