define(['jquery', 'datatables', 'datatables-responsive'], function ($) {
    return {
        datatables: [],
        run: function (wrap, response) {
            if (this.datatables[$(wrap).attr('id')] && $(wrap).hasClass('dataTable')) {
                this.datatables[$(wrap).attr('id')].draw();
            } else {
                this.datatables[$(wrap).attr('id')] = $(wrap).DataTable({
                    'responsive' : true
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
