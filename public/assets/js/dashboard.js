//report stats
let currentTickerIndex = 0;
let reportedTransactions = [];
let tickerInterval;

function updateReportedCount(count) {
    $("#reportedCountBadge, #reported-transactions").text(count);
}

function updateTickerDisplay() {
    const $ticker = $("#reportTicker");
    $ticker.empty();

    if (reportedTransactions.length === 0) {
        $ticker.append('<div class="ticker-item">No active reported transactions</div>');
        return;
    }

    reportedTransactions.forEach(report => {
        const message = `User ${report.username} reported ${report.service} (₦${report.amount})`;
        $ticker.append(`<div class="ticker-item">${message}</div>`);
    });
}

function fetchReportedTransactions() {
    $.getJSON("dash-reported", function(response) {
        if (response.success) {
            reportedTransactions = response.reports;
            updateReportedCount(response.count);
            updateTickerDisplay();
            if (reportedTransactions.length > 1) {
                startTicker();
            }
        }
    }).fail(function() {
        console.log("Failed to load reports");
    });
}

function startTicker() {
    clearInterval(tickerInterval);
    tickerInterval = setInterval(() => {
        currentTickerIndex = (currentTickerIndex + 1) % reportedTransactions.length;
        $("#reportTicker").css('transform', `translateY(-${currentTickerIndex * 100}%)`);
    }, 5000);
}

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

function handleFilterChange(filter, type) {
    const currentDate = new Date().toDateString();
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
        filterData(filter, type);
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
        if (type === 'sales') {
            document.getElementById('total-sales').textContent = `₦${data.totalSales.toLocaleString()}`;
            document.getElementById('total-data-sales').textContent = `₦${data.totalDataSales.toLocaleString()}`;
            document.getElementById('total-airtime-sales').textContent = `₦${data.totalAirtimeSales.toLocaleString()}`;
            document.getElementById('total-electricity-sales').textContent = `₦${data.totalElectricitySales.toLocaleString()}`;
            document.getElementById('total-cable-sales').textContent = `₦${data.totalCableSales.toLocaleString()}`;
            document.getElementById('total-exam-sales').textContent = `₦${data.totalExamSales.toLocaleString()}`;
        } else if (type === 'users') {
            document.getElementById('total-users').textContent = data.totalUsers;
            document.getElementById('active-users').textContent = data.activeUsers;
            document.getElementById('suspended-users').textContent = data.suspendedUsers;
            document.getElementById('blocked-users').textContent = data.blockedUsers;
            document.getElementById('reported-transactions').textContent = data.reportedTransactionsCount;
        }
    })
    .catch(error => console.error('Error fetching data:', error));
}

$(document).ready(function () {
    // *********** START OF DASHBOARD TIMER ********

    document.addEventListener('DOMContentLoaded', function() {
        const currentDate = new Date().toDateString();

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

    flatpickr("#date-range-sales", {
        mode: "range",
        dateFormat: "d-m-Y",
        onClose: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                const startDate = selectedDates[0].toISOString().split('T')[0];
                const endDate = selectedDates[1].toISOString().split('T')[0];
                document.getElementById('date-range-sales').dataset.startDate = startDate;
                document.getElementById('date-range-sales').dataset.endDate = endDate;
            }
        },
    });

    flatpickr("#date-range-users", {
        mode: "range",
        dateFormat: "d-m-Y",
        onClose: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                const startDate = selectedDates[0].toISOString().split('T')[0];
                const endDate = selectedDates[1].toISOString().split('T')[0];
                document.getElementById('date-range-users').dataset.startDate = startDate;
                document.getElementById('date-range-users').dataset.endDate = endDate;
            }
        },
    });

    function updateTime() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';

        hours = hours % 12;
        hours = hours ? hours : 12;

        updateNumber('hours', `${hours}`);
        updateNumber('minutes', `${minutes}`);
        updateNumber('ampm', ampm);

        const dayOfWeek = now.toLocaleDateString(undefined, { weekday: 'short' });
        const day = now.getDate();
        const month = now.toLocaleDateString(undefined, { month: 'short' });
        const year = now.getFullYear();
        document.getElementById('current-date').textContent = `${dayOfWeek}, ${day} ${month}, ${year}`;
    }

    function updateNumber(id, newValue) {
        const element = document.getElementById(id);
        if (element.textContent !== newValue) {
            element.style.animation = 'slide-up 0.5s ease-in-out';
            element.addEventListener('animationend', () => {
                element.textContent = newValue;
                element.style.animation = '';
            }, { once: true });
        }
    }

    setInterval(updateTime, 1000);
    updateTime();

    // ******** START OF FETCH WALLET BALANCE *******

    // Helper to show/hide balance and reset icon
    function showBalance(target, value) {
        const $balance = $(target);
        $balance.text(value);
        $balance.data("real-value", value);
        $balance.data("visible", true);
        $(`.toggle-balance[data-target='${target}'] i`).removeClass("fa-eye-slash").addClass("fa-eye");
    }

    function fetchWalletBalance() {
        $.ajax({
            url: "/get-wallet-balance",
            type: "GET",
            dataType: "json",
            success: function(response) {
                if (response.success && response.balance !== undefined) {
                    showBalance("#wallet-balance", "₦" + response.balance);
                } else {
                    showBalance("#wallet-balance", "Error: " + (response.message || "Unknown error"));
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = "Error loading balance";
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage += ": " + response.message;
                    }
                } catch (e) {
                    errorMessage += " (Network error)";
                }
                showBalance("#wallet-balance", errorMessage);
            }
        });
    }

    function fetchGladWalletBalance() {
        $.ajax({
            url: "/get-glad-wallet-balance",
            type: "GET",
            dataType: "json",
            success: function(response) {
                if (response.success && response.balance !== undefined) {
                    showBalance("#wallet-balance-glad", "₦" + response.balance);
                } else {
                    showBalance("#wallet-balance-glad", "Error: " + (response.message || "Unknown error"));
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = "Error loading GladTidings balance";
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage += ": " + response.message;
                    }
                } catch (e) {
                    errorMessage += " (Network error)";
                }
                showBalance("#wallet-balance-glad", errorMessage);
            }
        });
    }

    function fetchArtxWalletBalance() {
        $.ajax({
            url: "/get-artx-wallet-balance",
            type: "GET",
            dataType: "json",
            success: function(response) {
                if (response.success && response.balance !== undefined) {
                    showBalance("#wallet-balance-artx", "₦" + response.balance);
                } else {
                    showBalance("#wallet-balance-artx", "Error: " + (response.message || "Unknown error"));
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = "Error loading ARTX balance";
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage += ": " + response.message;
                    }
                } catch (e) {
                    errorMessage += " (Network error)";
                }
                showBalance("#wallet-balance-artx", errorMessage);
            }
        });
    }

    // Toggle wallet balance visibility
    $(".toggle-balance").click(function() {
        const $icon = $(this).find("i");
        const target = $(this).data("target");
        const $balance = $(target);

        if ($balance.data("visible") === false) {
            // Show balance
            $balance.text($balance.data("real-value") || $balance.text());
            $balance.data("visible", true);
            $icon.removeClass("fa-eye-slash").addClass("fa-eye");
        } else {
            // Hide balance
            $balance.data("real-value", $balance.text());
            $balance.text("••••••••");
            $balance.data("visible", false);
            $icon.removeClass("fa-eye").addClass("fa-eye-slash");
        }
    });

    // Refresh artx balance every 10 minutes
    setInterval(fetchArtxWalletBalance, 3000000);
    fetchArtxWalletBalance();

    // Fetch vtpass balance every 10 minutes
    setInterval(fetchWalletBalance, 3000000);
    fetchWalletBalance();

    // Fetch GladTidings balance every 10 minutes
    setInterval(fetchGladWalletBalance, 3000000);
    fetchGladWalletBalance();

    // Refresh balance button
    $(".refresh-balance").click(function() {
        const $btn = $(this);
        const originalText = $btn.text();
        fetchWalletBalance();
        fetchGladWalletBalance();
        fetchArtxWalletBalance();
        $btn.html('<i class="fa fa-spinner fa-spin"></i>');
        setTimeout(() => $btn.text(originalText), 1000);
    });

    // Initiate report ticker
    fetchReportedTransactions();
    setInterval(fetchReportedTransactions, 30000);
});