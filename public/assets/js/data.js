$(document).ready(function () {

      // ******* START OF TAB FOR DATA SETTING ******
    function setActiveDataTab(tabId) {
        $(`#${tabId}-tab`).addClass('active');
        $(`#${tabId}`).addClass('show active');
    }

    function saveActiveDataTab(tabId) {
        sessionStorage.setItem('activeDataTab', tabId);
    }

    function loadActiveDataTab() {
        const activeDataTab = sessionStorage.getItem('activeDataTab');
        if (activeDataTab) {
            setActiveDataTab(activeDataTab);
        } else {
            setActiveDataTab('net-mtn-tab');
        }
    }

    $('.data-nav-link').on('click', function () {
        const tabId = $(this).attr('aria-controls');
        saveActiveDataTab(tabId);
    });

    loadActiveDataTab();

    // ******* END OF TAB FOR DATA SETTING ******

});