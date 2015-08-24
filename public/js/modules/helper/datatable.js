define(['jquery', 'datatablesBootstrap'], function ($) {
    return {
        datatables: [],
        run: function (wrap, response) {
            var currentModule = this;

            if (typeof $(wrap).attr('id') == 'undefined') {
                $(wrap).attr('id', 'datatable-' + Math.floor((Math.random() * 10000)))
            }
            if (currentModule.datatables[$(wrap).attr('id')] && $(wrap).hasClass('dataTable')) {
                currentModule.datatables[$(wrap).attr('id')].draw();
            } else {
                $.getJSON('back/datatable/listconfig.html', {name: $(wrap).data('datatable-name')}, function(response){
                    currentModule.datatables[$(wrap).attr('id')] = $(wrap).DataTable(response.config);
                });
            }
        },
        addRow: function (wrap, row) {
            if (this.datatables[$(wrap).attr('id')]) {
                this.datatables[$(wrap).attr('id')].row.add(row);
            }
        }
    };
});
