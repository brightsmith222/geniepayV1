$(document).ready(function () {

// ********** START OF REFUND REPORTED TRANSACTION *********

$(document).on('click', '.refunds-btns', function () {
    let transactionId = $(this).data('id');

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
                url: `/reported/${transactionId}/reportrefund`,
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

//Currency formatter function
let formatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2
});

    // Reported Modal Functionality
    $(document).on("click", ".reportedvisibility", function () {
        let row = $(this).closest("tr");
        $("#reportedmodalInvoice").text(row.data("invoice"));
        $("#reportedmodalStatus").text(row.data("status"));
        $("#reportedmodalUsername").text(row.data("username"));
        $("#reportedmodalType").text(row.data("type"));
        $("#reportedmodalService").text(row.data("service"));
        $("#reportedmodalSender").text(row.data("sender-email"));
        $("#reportedmodalReceiver").text(row.data("receiver-email"));
        $("#reportedmodalAmount").text(formatter.format(row.data("amount") || 0));
        $("#reportedmodalBalanceBefore").text(formatter.format(row.data("balance-before") || 0));
        $("#reportedmodalBalanceAfter").text(formatter.format(row.data("balance-after") || 0));
        $('#reportedrefundBtn').data('id', row.data('id'));
        if (row.data('status').toLowerCase() === 'refunded') {
            $('#reportedrefundBtn').prop('disabled', true);
        } else {
            $('#reportedrefundBtn').prop('disabled', false);
        }
        $("#reportedtransactionModal").modal("show");
    });

});