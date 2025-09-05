@extends('layout')

@section('dashboard-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ticket Statistics</h3>
                    <div class="card-tools">
                        <a href="{{ route('tickets.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Tickets
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $stats['total_tickets'] }}</h3>
                                    <p>Total Tickets</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $stats['open_tickets'] }}</h3>
                                    <p>Open Tickets</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $stats['in_progress_tickets'] }}</h3>
                                    <p>In Progress</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $stats['resolved_tickets'] }}</h3>
                                    <p>Resolved</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $stats['urgent_tickets'] ?? 0 }}</h3>
                                    <p>Urgent</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-primary">
                                                    <i class="fas fa-calendar-day"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Today</span>
                                                    <span class="info-box-number">{{ $stats['tickets_today'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-success">
                                                    <i class="fas fa-calendar-week"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">This Week</span>
                                                    <span class="info-box-number">{{ $stats['tickets_this_week'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-info">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">This Month</span>
                                                    <span class="info-box-number">{{ $stats['tickets_this_month'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-box bg-light">
                                                <span class="info-box-icon bg-secondary">
                                                    <i class="fas fa-times-circle"></i>
                                                </span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Closed</span>
                                                    <span class="info-box-number">{{ $stats['closed_tickets'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="btn btn-danger">
                                            <i class="fas fa-exclamation-circle"></i> View Open Tickets
                                        </a>
                                        <a href="{{ route('tickets.index', ['status' => 'in_progress']) }}" class="btn btn-warning">
                                            <i class="fas fa-clock"></i> View In Progress
                                        </a>
                                        <a href="{{ route('tickets.index', ['status' => 'resolved']) }}" class="btn btn-success">
                                            <i class="fas fa-check-circle"></i> View Resolved
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
