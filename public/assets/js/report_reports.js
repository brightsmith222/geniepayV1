$(document).ready(function() {
    // Initialize CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });


    // Handle refund action
function handleRefund(transactionId) {
    // Check if button is disabled
    const refundBtn = $('#reportedrefundBtn');
    if (refundBtn.prop('disabled')) {
        Swal.fire({
            title: 'Already Refunded',
            text: 'This transaction has already been refunded',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }

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
                url: `/reported/${transactionId}/reportrefund`,
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
                location.reload(); 
            });
        }
    });
}

// Handle Resolve button click
$(document).on('click', '.resolve-btn', function(e) {
    e.preventDefault();
    const $thisBtn = $(this);
    const requestId = $thisBtn.data('id');
    
    if ($thisBtn.prop('disabled')) {
        Swal.fire({
            title: 'Already Resolved',
            text: 'This transaction has already been resolved',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }

    Swal.fire({
        title: 'Confirm Resolution',
        text: 'This will mark the transaction as resolved without refunding. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6c757d',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, resolve',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return resolveTransaction(requestId)
                .then(response => response)
                .catch(error => {
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
                // Disable all buttons with the same data-id (e.g., mobile + desktop)
                $('.resolve-btn[data-id="' + requestId + '"]').prop('disabled', true);
                $('.reportedrefundBtn[data-id="' + requestId + '"]').prop('disabled', true);

                // Optionally update status
                $('.status-display[data-id="' + requestId + '"]')
                    .text(result.value.new_status)
                    .removeClass('pending refunded')
                    .addClass('resolved');
                    location.reload();
            });
        }
    });
});

// AJAX function to handle resolution
function resolveTransaction(requestId) {
    return $.ajax({
        url: '/reported/' + requestId + '/resolve',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        }
    });
}


    // Refund button handler
    $(document).on('click', '#reportedrefundBtn', function(e) {
        e.preventDefault();
        const transactionId = $(this).data('id');
        if (transactionId) {
            handleRefund(transactionId);
        }
    });

    

    //Handle copying transaction ID
    $(".copy-clipboard").on("click", function () {
        const text = $(this).data("clipboard-text");

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showToast("Transaction ID copied!");
            }).catch(err => {
                console.error("Clipboard write failed:", err);
                showToast("Failed to copy. Try again.");
            });
        } else {
            showToast("Clipboard not supported in this browser.");
        }
    });
});
function showToast(message = "Copied to clipboard!") {
    const $toast = $('#custom-toast');
    $toast.text(message).fadeIn(200);

    setTimeout(() => {
        $toast.fadeOut(300);
    }, 5000);
}