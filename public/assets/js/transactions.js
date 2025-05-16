$(document).ready(function() {
    // Initialize CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Currency formatter
    const formatter = new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2
    });

    // Debounce function for search
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    // Load transactions for a specific tab
    function loadTransactions(tab, params = {}) {
        const sortValue = $(`#${tab}Sort`).val();
        const [sortColumn, sortDirection] = sortValue.split('_');
        
        const requestParams = {
            search: $(`#${tab}SearchInput`).val(),
            sort_column: sortColumn,
            sort_direction: sortDirection,
            page: params.page || 1,
            type: tab === 'all' ? null : tab
        };

        // Show loading indicator
        $(`#${tab}-table-container`).html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p>Loading transactions...</p>
            </div>
        `);

        $.ajax({
            url: $('#transactionIndexRoute').val(),
            type: 'GET',
            data: requestParams,
            success: function(response) {
                if (typeof response === 'object') {
                    // AJAX response
                    $(`#${tab}-table-container`).html(response.table);
                    $(`#${tab}-pagination-container`).html(response.pagination);
                }
            },
            error: function(xhr) {
                console.error('Error loading transactions:', xhr.responseText);
                $(`#${tab}-table-container`).html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Error loading transactions. Please try again.
                    </div>
                `);
            }
        });
    }

    
    // View transaction details
    $(document).on('click', '.visibility', function(e) {
        e.preventDefault();
        const row = $(this).closest('tr');
        
        console.log('Viewing transaction:', {
            id: row.data('id'),
            invoice: row.data('invoice'),
            status: row.data('status')
        });

        const modalContent = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Invoice:</strong> ${row.data('invoice')}</p>
                    <p><strong>Status:</strong> 
                        <span class="status ${row.data('status').toLowerCase() === 'successful' ? 'completed' : 'cancel'}">
                            ${row.data('status')}
                        </span>
                    </p>
                    <p><strong>Service:</strong> ${row.data('service')}</p>
                    <p><strong>Username:</strong> ${row.data('username')}</p>
                    ${row.data('provider') ? `<p><strong>Provider:</strong> ${row.data('provider')}</p>` : ''}
                    ${row.data('plan') ? `<p><strong>Plan:</strong> ${row.data('plan')}</p>` : ''}
                </div>
                <div class="col-md-6">
                    <p><strong>Amount:</strong> ${formatter.format(row.data('amount') || 0)}</p>
                    <p><strong>Phone Number:</strong> ${row.data('phone')}</p>
                    ${row.data('card') ? `<p><strong>Card Number:</strong> ${row.data('card')}</p>` : ''}
                    ${row.data('meter') ? `<p><strong>Meter Number:</strong> ${row.data('meter')}</p>` : ''}
                    ${row.data('quantity') ? `<p><strong>Quantity:</strong> ${row.data('quantity')}</p>` : ''}
                    ${row.data('token') ? `<p><strong>Token:</strong> ${row.data('token')}</p>` : ''}
                    ${row.data('epin') ? `<p><strong>ePIN:</strong> ${row.data('epin')}</p>` : ''}
                </div>
            </div>
        `;
        
        $('#transactionModal .modal-body').html(modalContent);
        
        // Update refund button
        const refundBtn = $('#refundBtn');
        refundBtn.data('id', row.data('id'));
        refundBtn.prop('disabled', row.data('status').toLowerCase() === 'refunded');
        
        $('#transactionModal').modal('show');
    });

    // Initialize event listeners for a specific tab
    function initTabListeners(tab) {
        // Search input
        $(`#${tab}SearchInput`).on('keyup', debounce(function() {
            loadTransactions(tab);
        }, 300));

        // Sort dropdown
        $(`#${tab}Sort`).on('change', function() {
            loadTransactions(tab);
        });

        // Pagination clicks
        $(document).on('click', `#${tab}-pagination-container .pagination a`, function(e) {
            e.preventDefault();
            const page = $(this).attr('href').split('page=')[1];
            loadTransactions(tab, { page: page });
        });
    }

    // Initialize all tab listeners
    function initAllTabListeners() {
        ['all', 'data', 'airtime', 'cable', 'electricity', 'exam'].forEach(tab => {
            initTabListeners(tab);
        });
    }

    

    // Tab switching with session storage
    function setActiveTab(tabId) {
        $('.nav-link').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $(`#${tabId}-tab`).addClass('active');
        $(`#${tabId}`).addClass('show active');
    }

    function saveActiveTab(tabId) {
        sessionStorage.setItem('activeTab', tabId);
    }

    function loadActiveTab() {
        const activeTab = sessionStorage.getItem('activeTab') || 'all';
        setActiveTab(activeTab);
        return activeTab;
    }

    $('.nav-link').on('click', function() {
        const tabId = $(this).attr('aria-controls');
        saveActiveTab(tabId);
    });

    // Initial load
    const initialTab = loadActiveTab();
    initAllTabListeners();
    
    // Load initial data for all tabs
    ['all', 'data', 'airtime', 'cable', 'electricity', 'exam'].forEach(tab => {
        loadTransactions(tab);
    });


    // Handle refresh button click
    $(document).on('click', '.refresh-btn', function () {
        console.log('Refresh button clicked'); // Debugging
        const id = $(this).data('id');
        const routeUrl = $('#transactionRefreshRoute').val(); // Fetch the route URL
        console.log('Route URL:', routeUrl); // Debugging
    
        // Use SweetAlert for confirmation
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to check and refresh the transaction status?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, refresh it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with the AJAX request
                $.post({
                    url: routeUrl, // Use the fetched route URL
                    data: {
                        transaction_id: id,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        Swal.fire('Success', res.message, 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'An error occurred.', 'error');
                    }
                });
            }
        });
    });

    
});