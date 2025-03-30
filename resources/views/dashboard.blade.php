@extends('layout')

@section('dashboard-content')
<div>
  <!-- Available Balance -->
<div class="wallet-wrapper">
  <div class="wallet-balance">
      <span class="material-icons-outlined">account_balance_wallet</span>
      <span id="wallet-balance">Loading...</span>
  </div>
  <button id="refreshBalance" class="btn btn-sm">⟳ Refresh</button>
</div>
  <!-- Report stats -->
<div class="alert report-stat d-flex align-items-center shadow-sm p-3 mb-4">
    <span class="material-icons-outlined me-2">report_problem</span>
    
    <div class="ticker-container flex-grow-1 overflow-hidden" style="height: 24px;">
        <div class="ticker-track" id="reportTicker">
            <div class="ticker-item text-truncate">
                ⚠️ Urgent Alert: {{ $reportedTransactionsCount ?? 0 }} transactions reported. Please review immediately.
            </div>
        </div>
    </div>

    <span class="badge reported-badge ms-2 px-3 py-1" id="reportedCountBadge">
        {{ $reportedTransactionsCount ?? 0 }}
    </span>
</div>

  <!-- Header -->
  <div class="header mb-4 d-flex">
    <div>
      <h4>Good afternoon, User</h4>
      <p>Here is what's happening with your projects today:</p>
    </div>
    <div>
      <div class="time-display">
        <div class="time-block">
          <div class="time-number" id="hours">00</div>
        </div><span>:</span>
        <div class="time-block">
          <div class="time-number" id="minutes">00</div>
        </div>
        <div class="time-block">
          <div class="time-number" id="ampm">AM</div>
        </div>
      </div>
      <div id="current-date" class="date-display"></div>
    </div>
  </div>

  <!-- Sales Statistics -->
  <div class="d-flex stat-head" style="justify-content: space-between;">
    <div>
      <h3>Sales Statistics</h3>
      <p>Track and manage income effortlessly</p>
    </div>
    <div class="mb-3">
      <label for="sort">Filter By:</label>
      <!-- Sales Filter Dropdown -->
<select class="revenue-date-range" id="revenue-sort" onchange="handleFilterChange(this.value, 'sales')">
    <option value="all_time" {{ $filter === 'all_time' ? 'selected' : '' }}>All Time</option>
    <option value="today" {{ $filter === 'today' ? 'selected' : '' }}>Today</option>
    <option value="yesterday" {{ $filter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
    <option value="this_month" {{ $filter === 'this_month' ? 'selected' : '' }}>This Month</option>
    <option value="last_month" {{ $filter === 'last_month' ? 'selected' : '' }}>Last Month</option>
    <option value="this_year" {{ $filter === 'this_year' ? 'selected' : '' }}>This Year</option>
    <option value="last_year" {{ $filter === 'last_year' ? 'selected' : '' }}>Last Year</option>
    <option value="custom">Custom</option>
</select>

<!-- Date Range Picker for Sales -->
<div id="custom-date-range-sales" style="display: none; margin-top: 10px;">
    <input type="text" id="date-range-sales" placeholder="Select Date Range">
    <button onclick="applyCustomFilter('sales')">Apply</button>
</div>
    </div>
  </div>
  <div class="row">
    <!-- First Column -->
    <div class="col-md-12">
      <div class="row">
        <!-- Top Row -->
        <div class="col-md-4">
          <div class="card total-sale">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Sales</span>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              <p class="card-text" id="total-sales">₦{{ number_format($totalSales, 2) }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics">
                    @php
                        $percentageChange = $previousTotalSales != 0 
                            ? (($totalSales - $previousTotalSales) / $previousTotalSales) * 100 
                            : 0;
                        echo number_format($percentageChange, 2) . '%';
                    @endphp
                </span> 
                vs ₦{{ number_format($previousTotalSales, 2) }} (prev.)
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card data-sale">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Data Sale</span>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              <p class="card-text" id="total-data-sales">₦{{ number_format($totalDataSales, 2) }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics-data-sales">
                  @php
                      $percentageChangeData = $previousTotalDataSales != 0 
                          ? (($totalDataSales - $previousTotalDataSales) / $previousTotalDataSales) * 100 
                          : 0;
                      echo number_format($percentageChangeData, 2) . '%';
                  @endphp
                </span> 
                vs ₦{{ number_format($previousTotalDataSales, 2) }} (prev.)
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card airtime-sale">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Airtime Sale</span>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              <p class="card-text" id="total-airtime-sales">₦{{ number_format($totalAirtimeSales, 2) }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics-airtime-sales">
                  @php
                      $percentageChangeAirtime = $previousTotalAirtimeSales != 0 
                          ? (($totalAirtimeSales - $previousTotalAirtimeSales) / $previousTotalAirtimeSales) * 100 
                          : 0;
                      echo number_format($percentageChangeAirtime, 2) . '%';
                  @endphp
                </span> 
                vs ₦{{ number_format($previousTotalAirtimeSales, 2) }} (prev.)
              </p>
            </div>
          </div>
        </div>
        <!-- Bottom Row -->
        <div class="col-md-4">
          <div class="card electricity-sale">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Electricity Sale</span>
                <span class="stat-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
                </span>
              </div>
              <p class="card-text" id="total-electricity-sales">₦{{ number_format($totalElectricitySales, 2) }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics-electricity-sales">
                  @php
                      $percentageChangeElectricity = $previousTotalElectricitySales != 0 
                          ? (($totalElectricitySales - $previousTotalElectricitySales) / $previousTotalElectricitySales) * 100 
                          : 0;
                      echo number_format($percentageChangeElectricity, 2) . '%';
                  @endphp
                </span> 
                vs ₦{{ number_format($previousTotalElectricitySales, 2) }} (prev.)
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card cable-sale">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Cable Sale</span>
                <span class="stat-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
                </span>
              </div>
              <p class="card-text" id="total-cable-sales">₦{{ number_format($totalCableSales, 2) }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics-cable-sales">
                  @php
                      $percentageChangeCable = $previousTotalCableSales != 0 
                          ? (($totalCableSales - $previousTotalCableSales) / $previousTotalCableSales) * 100 
                          : 0;
                      echo number_format($percentageChangeCable, 2) . '%';
                  @endphp
                </span> 
                vs ₦{{ number_format($previousTotalCableSales, 2) }} (prev.)
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card exam-sale">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Exam Sale</span>
                <span class="stat-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
                </span>
              </div>
              <p class="card-text" id="total-exam-sales">₦{{ number_format($totalExamSales, 2) }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics-exam-sales">
                  @php
                      $percentageChangeExam = $previousTotalExamSales != 0 
                          ? (($totalExamSales - $previousTotalExamSales) / $previousTotalExamSales) * 100 
                          : 0;
                      echo number_format($percentageChangeExam, 2) . '%';
                  @endphp
                </span> 
                vs ₦{{ number_format($previousTotalExamSales, 2) }} (prev.)
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- User Statistics -->
  <div class="d-flex stat-head" style="justify-content: space-between;">
    <div>
      <h3>Users Overview</h3>
      <p>Track and manage users effortlessly</p>
    </div>
    <div class="mb-3">
      <label for="sort">Filter By:</label>
      <!-- Users Filter Dropdown -->
<select class="users-date-range" id="user-sort" onchange="handleFilterChange(this.value, 'users')">
    <option value="all_time" {{ $filter === 'all_time' ? 'selected' : '' }}>All Time</option>
    <option value="today" {{ $filter === 'today' ? 'selected' : '' }}>Today</option>
    <option value="yesterday" {{ $filter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
    <option value="this_month" {{ $filter === 'this_month' ? 'selected' : '' }}>This Month</option>
    <option value="last_month" {{ $filter === 'last_month' ? 'selected' : '' }}>Last Month</option>
    <option value="this_year" {{ $filter === 'this_year' ? 'selected' : '' }}>This Year</option>
    <option value="last_year" {{ $filter === 'last_year' ? 'selected' : '' }}>Last Year</option>
    <option value="custom">Custom</option>
</select>

<!-- Date Range Picker for Users -->
<div id="custom-date-range-users" style="display: none; margin-top: 10px;">
    <input type="text" id="date-range-users" placeholder="Select Date Range">
    <button onclick="applyCustomFilter('users')">Apply</button>
</div>
    </div>
  </div>
  <div class="row">
    <!-- First Column -->
    <div class="col-md-8">
      <div class="row">
        <!-- Top Row -->
        <div class="col-md-6">
          <div class="card all-users">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Users</span>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              <p class="card-text" id="total-users">{{ $totalUsers }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics">
                    @php
                        $percentageChangeTotal = $previousTotalUsers != 0 
                            ? (($totalUsers - $previousTotalUsers) / $previousTotalUsers) * 100 
                            : 0;
                        echo number_format($percentageChangeTotal, 2) . '%';
                    @endphp
                </span> 
                vs {{ number_format($previousTotalUsers) }} (prev.)
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card active-users">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Active Users</span>
                <span class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
              </div>
              <p class="card-text" id="active-users">{{ $activeUsers }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics">
                    @php
                        $percentageChangeUsers = $previousActiveUsers != 0 
                            ? (($activeUsers - $previousActiveUsers) / $previousActiveUsers) * 100 
                            : 0;
                        echo number_format($percentageChangeUsers, 2) . '%';
                    @endphp
                </span> 
                vs {{ number_format($previousActiveUsers) }} (prev.)
              </p>
            </div>
          </div>
        </div>
        <!-- Bottom Row -->
        <div class="col-md-6">
          <div class="card suspend-users">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Suspended Users</span>
                <span class="stat-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
                </span>
              </div>
              <p class="card-text" id="suspended-users">{{ $suspendedUsers }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics">
                    @php
                        $percentageChangeSuspended = $previousSuspendedUsers != 0 
                            ? (($suspendedUsers - $previousSuspendedUsers) / $previousSuspendedUsers) * 100 
                            : 0;
                        echo number_format($percentageChangeSuspended, 2) . '%';
                    @endphp
                </span> 
                vs {{ number_format($previousSuspendedUsers) }} (prev.)
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card block-users">
            <div class="card-body">
              <div class="d-flex" style="justify-content: space-between;">
                <span>Total Blocked Users</span>
                <span class="stat-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
                </span>
              </div>
              <p class="card-text" id="blocked-users">{{ $blockedUsers }}</p>
              <p class="card-subtext">
                <span class="negative" id="statistics">
                    @php
                        $percentageChangeBlocked = $previousBlockedUsers != 0 
                            ? (($blockedUsers - $previousBlockedUsers) / $previousBlockedUsers) * 100 
                            : 0;
                        echo number_format($percentageChangeBlocked, 2) . '%';
                    @endphp
                </span> 
                vs {{ number_format($previousBlockedUsers) }} (prev.)
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Second Column -->
    <div class="col-md-4">
      <div class="card report-users">
        <div class="card-body">
          <div class="d-flex" style="justify-content: space-between;">
            <span>Active Reported</span>
            <span class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity text-gray-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
              </svg>
            </span>
          </div>
          <p class="card-text" id="reported-transactions">{{ $reportedTransactionsCount }}</p>
          <p class="card-subtext">
            <span>21.50%</span> vs 2.19 (prev.)
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection


@section('scripts')
    <script src="{{ URL::to('assets/js/dashboard.js')}}"></script>
@endsection
