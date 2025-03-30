$(document).ready(function () {

   // ******** START OF WALLET REFUND **********

   $(document).on('click', '.refund-btns', function () {
    let transactionId = $(this).data('id');
    let refundButton = $(this);

// Show a confirmation dialog before proceeding
Swal.fire({
    title: 'Are you sure?',
    text: 'You are about to refund this transaction. This action cannot be undone!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, refund it!',
    cancelButtonText: 'Cancel'
}).then((result) => {
    if (result.isConfirmed) {
        // Proceed with the refund if the user confirms
        $.ajax({
            url: `/wallet_transac/${transactionId}/walletrefund`,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                // Display success or error message using SweetAlert2
                if (response.type === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonColor: '#3085d6',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reload the page or update the UI
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message,
                        confirmButtonColor: '#d33',
                    });
                }
            },
            error: function (xhr) {
                // Handle AJAX errors
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: xhr.responseJSON.message || 'An error occurred. Please try again.',
                    confirmButtonColor: '#d33',
                });
            }
        });
    }
});
});

// ******** END OF WALLET REFUND **********

//Currency formatter function
let formatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2
});

// Wallet Modal Functionality
    $(document).on("click", ".walletvisibility", function () {
        let row = $(this).closest("tr");
        $("#walletmodalInvoice").text(row.data("invoice"));
        $("#walletmodalStatus").text(row.data("status"));
        $("#walletmodalUsername").text(row.data("username"));
        $("#walletmodalType").text(row.data("type"));
        $("#walletmodalService").text(row.data("service"));
        $("#walletmodalSender").text(row.data("sender-email"));
        $("#walletmodalReceiver").text(row.data("receiver-email"));
        $("#walletmodalAmount").text(formatter.format(row.data("amount") || 0));
        $("#walletmodalBalanceBefore").text(formatter.format(row.data("balance-before") || 0));
        $("#walletmodalBalanceAfter").text(formatter.format(row.data("balance-after") || 0));
        $('#walletrefundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#walletrefundBtn').prop('disabled', true);
        } else {
            $('#walletrefundBtn').prop('disabled', false);
        }
        $("#wallettransactionModal").modal("show");
    });

});