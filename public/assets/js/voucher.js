$(document).ready(function () {
    // ******* START OF TAB FOR DATA SETTING ******

    function setActiveDataTabs(tabId) {
        $('.voucher-nav-link').removeClass('active'); // Remove active class from all tab links
        $('.voucher-tab-pane').removeClass('show active'); // Remove active from all tab content

        $(`#${tabId}-tab`).addClass('active'); // Activate the selected tab link
        $(`#${tabId}`).addClass('show active'); // Show the selected tab content
    }

    function saveActiveVoucherTabs(tabId) {
        sessionStorage.setItem('activeVoucherTabs', tabId);
    }

    function loadActiveDataTabs() {
        const activeVoucherTabs = sessionStorage.getItem('activeVoucherTabs');
        if (activeVoucherTabs) {
            setActiveDataTabs(activeVoucherTabs);
            // Emit the active tab to Livewire
            window.Livewire.emit('setActiveTab', activeVoucherTabs);
        } else {
            setActiveDataTabs('esim'); // Default to esim
            window.Livewire.emit('setActiveTab', 'esim');
        }
    }

    // Update the click event to save the correct tab ID
    $('.voucher-nav-link').on('click', function () {
        const tabId = $(this).attr('id').replace('-tab', ''); // Extract the tab ID
        saveActiveVoucherTabs(tabId);
        window.Livewire.emit('setActiveTab', tabId); // Emit the active tab to Livewire
    });

    loadActiveDataTabs();

    // ******* END OF TAB FOR DATA SETTING ******
});