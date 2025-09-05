@extends('layout')

@section('dashboard-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Support Tickets</h3>
                    <div class="card-tools">
                        <a href="{{ route('tickets.statistics') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-chart-bar"></i> Statistics
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('tickets.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="priority" class="form-control">
                                    <option value="">All Priorities</option>
                                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="category" class="form-control">
                                    <option value="">All Categories</option>
                                    <option value="technical" {{ request('category') == 'technical' ? 'selected' : '' }}>Technical</option>
                                    <option value="billing" {{ request('category') == 'billing' ? 'selected' : '' }}>Billing</option>
                                    <option value="general" {{ request('category') == 'general' ? 'selected' : '' }}>General</option>
                                    <option value="feature_request" {{ request('category') == 'feature_request' ? 'selected' : '' }}>Feature Request</option>
                                    <option value="bug_report" {{ request('category') == 'bug_report' ? 'selected' : '' }}>Bug Report</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="search" class="form-control" placeholder="Search tickets..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>

                    <!-- Tickets Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Category</th>
                                    <th>Replies</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->id }}</td>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket) }}">
                                            {{ Str::limit($ticket->subject, 50) }}
                                        </a>
                                    </td>
                                    <td>
                                        <strong>{{ $ticket->user->full_name }}</strong><br>
                                        <small>{{ $ticket->user->email }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->status == 'open' ? 'danger' : ($ticket->status == 'in_progress' ? 'warning' : ($ticket->status == 'resolved' ? 'success' : 'secondary')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->priority == 'urgent' ? 'danger' : ($ticket->priority == 'high' ? 'warning' : ($ticket->priority == 'medium' ? 'info' : 'secondary')) }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->category)) }}
                                        </span>
                                    </td>
                                    <td>{{ $ticket->replies->count() }}</td>
                                    <td>{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No tickets found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $tickets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
