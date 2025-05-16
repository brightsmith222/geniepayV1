$(document).ready(function() {
    $('#categoryFilter').change(function() {
        const category = $(this).val();
        if (category) {
            $('#productsTable tbody tr').hide();
            $('#productsTable tbody tr[class="' + category + '"]').show();
        } else {
            $('#productsTable tbody tr').show();
        }
    });

    $('.category-filter').change(function() {
        const typeId = $(this).data('type');
        const category = $(this).val();
        const table = $(`.products-table[data-type="${typeId}"]`);
        
        if (category) {
            table.find('tbody tr').hide();
            table.find(`tbody tr[class="${category}"]`).show();
        } else {
            table.find('tbody tr').show();
        }
    });
});