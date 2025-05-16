

    document.addEventListener('DOMContentLoaded', function () {
        // Initialize file input label
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        // SweetAlert2 Delete Confirmation
        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'This slider will be permanently deleted!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });


    function loadPhoto(event) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.getElementById('photo');
            output.src = reader.result;
            output.classList.add('img-thumbnail');
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    // Bootstrap Custom File Input
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        var fileName = document.getElementById('image').files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
    });

    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();