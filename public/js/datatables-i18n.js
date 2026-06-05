/**
 * DataTables i18n — legge le stringhe iniettate da Blade via meta tag
 * e restituisce l'oggetto language per l'inizializzazione di DataTables.
 *
 * Uso in Blade (dentro @push('scripts')):
 *   <script>
 *     if (typeof $.fn.dataTable !== 'undefined') {
 *       $.fn.dataTable.defaults.oLanguage = window.DataTablesI18n.get();
 *     }
 *   </script>
 *
 * Il meta tag viene iniettato da layouts/admin.blade.php (o dal file che include
 * questa feature). Se il meta non è presente usa le stringhe di default EN.
 */
(function () {
    'use strict';

    var meta = document.querySelector('meta[name="datatables-i18n"]');
    var strings = {};

    if (meta) {
        try {
            strings = JSON.parse(meta.getAttribute('content'));
        } catch (e) {
            strings = {};
        }
    }

    function g(key, fallback) {
        return strings[key] !== undefined ? strings[key] : fallback;
    }

    window.DataTablesI18n = {
        get: function () {
            return {
                sSearch:           g('search', 'Search:'),
                sLengthMenu:       g('length_menu', 'Show _MENU_ entries'),
                sInfo:             g('info', 'Showing _START_ to _END_ of _TOTAL_ entries'),
                sInfoEmpty:        g('info_empty', 'Showing 0 to 0 of 0 entries'),
                sInfoFiltered:     g('info_filtered', '(filtered from _MAX_ total entries)'),
                sZeroRecords:      g('zero_records', 'No matching records found'),
                sLoadingRecords:   g('loading_records', 'Loading...'),
                sProcessing:       g('processing', 'Processing...'),
                oPaginate: {
                    sFirst:    g('paginate_first', 'First'),
                    sLast:     g('paginate_last', 'Last'),
                    sPrevious: g('paginate_previous', 'Previous'),
                    sNext:     g('paginate_next', 'Next')
                }
            };
        }
    };
})();
