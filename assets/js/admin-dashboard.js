jQuery(document).ready(function($) {
    // Initialize DataTables if the tables exist on the page
    if ($.fn.DataTable) {
        if ($('#volunteerTable table').length) {
            $('#volunteerTable table').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                info: true
            });
        }
        
        if ($('#reliefTable table').length) {
            $('#reliefTable table').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                info: true
            });
        }
    }
});
