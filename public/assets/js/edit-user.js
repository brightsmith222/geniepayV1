$(document).ready(function() {
    const $wrapper = $('#userEditWrapper');
    const transactionsUrl = $wrapper.data('transactions-url');

    // Load transactions via AJAX
    $.get(transactionsUrl, function(html) {
        $('#transactionsContainer').html(html);
    });

    // Handle form submission normally
    $(document).on('submit', '#userEditForm', function(e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Confirm Update',
            text: "Are you sure you want to update this user's details?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = $(form).serialize();

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            location.reload(); 
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error', 'Failed to update user', 'error');
                    }
                });
            }
        });
    });

    // Bootstrap select picker
    $('.selectpicker').selectpicker();
});