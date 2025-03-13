@extends('layout')

@section('dashboard-content')
<div>
  <!-- Report stats -->
  <div class="report-stat mb-4 d-flex">
    <p>A user username has reported a transaction</p>
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
                <span class="negative" id="statistics">-2.50%</span> vs 74.60 (prev.)
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
                <span class="negative" id="statistics">-2.50%</span> vs 74.60 (prev.)
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
                <span class="negative" id="statistics" id="statistics">-1.83%</span> vs 2.19 (prev.)
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
                <span class="negative" id="statistics" id="statistics">-1.83%</span> vs 2.19 (prev.)
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
                <span class="negative" id="statistics" id="statistics">-1.83%</span> vs 2.19 (prev.)
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
                <span class="negative" id="statistics" id="statistics">-26.50%</span> vs 66.88 (prev.)
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
                <span class="negative" id="statistics" id="statistics">-2.50%</span> vs 74.60 (prev.)
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
                <span class="negative" id="statistics" id="statistics">-1.83%</span> vs 2.19 (prev.)
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
                <span class="negative" id="statistics" id="statistics">-1.83%</span> vs 2.19 (prev.)
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
            <span>Active Reported Transactions</span>
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

<script>
  
  function handleFilterChange(filter, type) {
    // Save the selected filter and the current date to localStorage
    const currentDate = new Date().toDateString(); // Get today's date as a string
    localStorage.setItem(`${type}_filter`, filter);
    localStorage.setItem(`${type}_last_selected_date`, currentDate);

    if (filter === 'custom') {
        if (type === 'sales') {
            document.getElementById('custom-date-range-sales').style.display = 'block';
        } else if (type === 'users') {
            document.getElementById('custom-date-range-users').style.display = 'block';
        }
    } else {
        if (type === 'sales') {
            document.getElementById('custom-date-range-sales').style.display = 'none';
        } else if (type === 'users') {
            document.getElementById('custom-date-range-users').style.display = 'none';
        }
        filterData(filter, type); // Apply the selected filter
    }
}

function filterData(filter, type, startDate = null, endDate = null) {
    let url = `/filter-data?filter=${filter}&type=${type}`;
    if (startDate && endDate) {
        url += `&startDate=${startDate}&endDate=${endDate}`;
    }

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(response => response.json())
    .then(data => {
        console.log(data); // Log the server response
        if (type === 'sales') {
            // Update sales statistics
            document.getElementById('total-sales').textContent = `₦${data.totalSales.toLocaleString()}`;
            document.getElementById('total-data-sales').textContent = `₦${data.totalDataSales.toLocaleString()}`;
            document.getElementById('total-airtime-sales').textContent = `₦${data.totalAirtimeSales.toLocaleString()}`;
            document.getElementById('total-electricity-sales').textContent = `₦${data.totalElectricitySales.toLocaleString()}`;
            document.getElementById('total-cable-sales').textContent = `₦${data.totalCableSales.toLocaleString()}`;
            document.getElementById('total-exam-sales').textContent = `₦${data.totalExamSales.toLocaleString()}`;
        } else if (type === 'users') {
            // Update user statistics
            document.getElementById('total-users').textContent = data.totalUsers;
            document.getElementById('active-users').textContent = data.activeUsers;
            document.getElementById('suspended-users').textContent = data.suspendedUsers;
            document.getElementById('blocked-users').textContent = data.blockedUsers;
            document.getElementById('reported-transactions').textContent = data.reportedTransactionsCount;
        }
    })
    .catch(error => console.error('Error fetching data:', error));
}

// Initialize Flatpickr for Sales date range
flatpickr("#date-range-sales", {
    mode: "range", // Enable range selection
    dateFormat: "d-m-Y", // Date format
    onClose: function(selectedDates, dateStr, instance) {
        if (selectedDates.length === 2) {
            const startDate = selectedDates[0].toISOString().split('T')[0]; // Format as YYYY-MM-DD
            const endDate = selectedDates[1].toISOString().split('T')[0]; // Format as YYYY-MM-DD
            document.getElementById('date-range-sales').dataset.startDate = startDate;
            document.getElementById('date-range-sales').dataset.endDate = endDate;
        }
    },
});

// Initialize Flatpickr for Users date range
flatpickr("#date-range-users", {
    mode: "range", // Enable range selection
    dateFormat: "d-m-Y", // Date format
    onClose: function(selectedDates, dateStr, instance) {
        if (selectedDates.length === 2) {
            const startDate = selectedDates[0].toISOString().split('T')[0]; // Format as YYYY-MM-DD
            const endDate = selectedDates[1].toISOString().split('T')[0]; // Format as YYYY-MM-DD
            document.getElementById('date-range-users').dataset.startDate = startDate;
            document.getElementById('date-range-users').dataset.endDate = endDate;
        }
    },
});

function applyCustomFilter(type) {
    let startDate, endDate;
    if (type === 'sales') {
        startDate = document.getElementById('date-range-sales').dataset.startDate;
        endDate = document.getElementById('date-range-sales').dataset.endDate;
    } else if (type === 'users') {
        startDate = document.getElementById('date-range-users').dataset.startDate;
        endDate = document.getElementById('date-range-users').dataset.endDate;
    }

    if (startDate && endDate) {
        filterData('custom', type, startDate, endDate);
    } else {
        alert('Please select a valid date range.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const currentDate = new Date().toDateString(); // Get today's date as a string

    // Check and reset sales filter if the date has changed
    const savedSalesFilter = localStorage.getItem('sales_filter') || 'all_time';
    const savedSalesDate = localStorage.getItem('sales_last_selected_date');
    if (savedSalesDate !== currentDate) {
        localStorage.removeItem('sales_filter');
        localStorage.removeItem('sales_last_selected_date');
        document.getElementById('revenue-sort').value = 'all_time';
        filterData('all_time', 'sales');
    } else {
        document.getElementById('revenue-sort').value = savedSalesFilter;
        filterData(savedSalesFilter, 'sales');
    }

    // Check and reset users filter if the date has changed
    const savedUsersFilter = localStorage.getItem('users_filter') || 'all_time';
    const savedUsersDate = localStorage.getItem('users_last_selected_date');
    if (savedUsersDate !== currentDate) {
        localStorage.removeItem('users_filter');
        localStorage.removeItem('users_last_selected_date');
        document.getElementById('user-sort').value = 'all_time';
        filterData('all_time', 'users');
    } else {
        document.getElementById('user-sort').value = savedUsersFilter;
        filterData(savedUsersFilter, 'users');
    }
});
  
</script>

@stop