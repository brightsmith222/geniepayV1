$(document).ready(function () {
    
// Show/hide specific users dropdown based on radio selection
$('input[name="sendTo"]').on('change', function () {
    if ($('#sendToSpecific').is(':checked')) {
        $('#specificUsersGroup').show();
    } else {
        $('#specificUsersGroup').hide();
    }
});

// Show/hide image upload input based on toggle
$('#includeImageToggle').on('change', function () {
    if ($(this).is(':checked')) {
        $('#imageUploadGroup').show();
    } else {
        $('#imageUploadGroup').hide();
    }
});

// Filter users based on search input
$('#userSearch').on('input', function () {
    const searchTerm = $(this).val().toLowerCase();
    $('#userList .form-check').each(function () {
        const userText = $(this).text().toLowerCase();
        if (userText.includes(searchTerm)) {
            $(this).removeClass('d-none'); // Show matching users
        } else {
            $(this).addClass('d-none'); // Hide non-matching users
        }
    });
});

// Display selected users
$('#userList').on('change', '.user-checkbox', function () {
    const selectedUsers = [];
    $('#userList .user-checkbox:checked').each(function () {
        selectedUsers.push($(this).next('label').text());
    });
    $('#selectedUsersList').html(selectedUsers.join(', '));
});


});

// Initialize file input label
document.querySelector('.custom-file-input')?.addEventListener('change', function(e) {
    var fileName = this.files[0]?.name || 'Choose file';
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

let selectedUsers = [];

    function searchUsers() {
        let searchTerm = document.getElementById("userSearch").value;

        if (searchTerm.length < 1) {
            document.getElementById("userList").innerHTML = "";
            return;
        }

        fetch(`/search-users?query=${encodeURIComponent(searchTerm)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(users => {
                let userListHTML = "";

                if (users.length > 0) {
                    users.forEach(user => {
                        userListHTML += `
                            <div class="form-check">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input" 
                                    id="user${user.id}" 
                                    onclick="toggleUser(${user.id}, '${user.username}')">
                                <label class="form-check-label" for="user${user.id}">${user.username}</label>
                            </div>`;
                    });
                } else {
                    userListHTML = "<p class='text-muted'>No users found.</p>";
                }

                document.getElementById("userList").innerHTML = userListHTML;
            })
            .catch(error => console.error("Fetch error:", error));
    }

    function toggleUser(userId, username) {
    let index = selectedUsers.findIndex(user => user.id === userId);
    
    if (index !== -1) {
        selectedUsers.splice(index, 1); // Remove user if already selected
    } else {
        selectedUsers.push({ id: userId, username: username }); // Add user
    }

    // Update the hidden input field with the new selected users list
    document.getElementById("selectedUsersInput").value = JSON.stringify(selectedUsers);

    // Update the UI to show selected users
    updateSelectedUsersUI();
}


    function updateSelectedUsersUI() {
        let selectedUsersList = document.getElementById("selectedUsersList");
        selectedUsersList.innerHTML = "";

        selectedUsers.forEach(user => {
            selectedUsersList.innerHTML += `
                <span class="badge badge-primary mr-2">${user.username}</span>
            `;
        });
    }

    function submitForm() {
        document.getElementById("selectedUsersInput").value = JSON.stringify(selectedUsers);
    }