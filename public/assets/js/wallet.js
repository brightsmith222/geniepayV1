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

    // Modify the loadWalletTransactions function
function loadWalletTransactions(params = {}) {
    const sortValue = $('#walletSort').val();
    const [sortColumn, sortDirection] = sortValue.split('_');
    
    // Validate sort direction
    const validDirections = ['asc', 'desc'];
    const direction = validDirections.includes(sortDirection) ? sortDirection : 'desc';
    
    const requestParams = {
        search: $('#walletSearchInput').val(),
        sort_column: sortColumn,
        sort_direction: direction,
        page: params.page || 1
    };

    // Show loading indicator
    $('#wallet-table-container').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p>Loading transactions...</p>
        </div>
    `);

    $.ajax({
        url: $('#walletIndexRoute').val(),
        type: 'GET',
        data: requestParams,
        success: function(response) {
            if (typeof response === 'object') {
                $('#wallet-table-container').html(response.table);
                $('#wallet-pagination-container').html(response.pagination);
            }
        },
        error: function(xhr) {
            console.error('Error loading transactions:', xhr.responseText);
            $('#wallet-table-container').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Error loading transactions. Please try again.
                </div>
            `);
        }
    });
}

    // Handle refund action
    function handleRefund(transactionId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You are about to refund this transaction. This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, refund it!',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: `/wallet_transac/${transactionId}/walletrefund`,
                    type: 'POST'
                }).then(response => {
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(
                        `Request failed: ${error.responseJSON?.message || error.statusText}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    title: 'Success!',
                    text: result.value.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    // Refresh the transactions table
                    loadWalletTransactions();
                });
            }
        });
    }

    // Initialize event listeners
    function initEventListeners() {
        // Search input
        $('#walletSearchInput').on('keyup', debounce(function() {
            loadWalletTransactions();
        }, 300));

        // Sort dropdown
        $('#walletSort').on('change', function() {
            loadWalletTransactions();
        });

        // Pagination clicks
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            const page = $(this).attr('href').split('page=')[1];
            loadWalletTransactions({ page: page });
        });

        // View transaction details
        $(document).on('click', '.walletvisibility', function() {
            const row = $(this).closest('tr');
            $('#walletmodalInvoice').text(row.data('invoice'));
            $('#walletmodalStatus').text(row.data('status'));
            $('#walletmodalUsername').text(row.data('username'));
            $('#walletmodalType').text(row.data('type'));
            $('#walletmodalService').text(row.data('service'));
            $('#walletmodalSender').text(row.data('sender-email'));
            $('#walletmodalReceiver').text(row.data('receiver-email'));
            $('#walletmodalAmount').text(formatter.format(row.data('amount') || 0));
            $('#walletmodalBalanceBefore').text(formatter.format(row.data('balance-before') || 0));
            $('#walletmodalBalanceAfter').text(formatter.format(row.data('balance-after') || 0));
            
            // Update refund button
            const refundBtn = $('#walletrefundBtn');
            refundBtn.data('id', row.data('id'));
            refundBtn.prop('disabled', row.data('status').toLowerCase() === 'refunded');
        });

        // Refund button
        $(document).on('click', '#walletrefundBtn', function(e) {
            e.preventDefault();
            const transactionId = $(this).data('id');
            if (transactionId) {
                handleRefund(transactionId);
            }
        });
    }

    // Initial load
    initEventListeners();
    loadWalletTransactions();
});