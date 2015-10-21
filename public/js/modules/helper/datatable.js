define(['jquery', 'modules/helper/amd', 'datatablesMaterialDesign'], function ($, helperAmd) {
    return {
        datatables: [],
        defaults: {
            urlConfig: 'back/datatable/listconfig.html',
            additionalDrawCallback: null
        },
        run: function (wrap, response) {
            var currentModule = this;

            if (typeof $(wrap).attr('id') == 'undefined') {
                $(wrap).attr('id', 'datatable-' + Math.floor((Math.random() * 10000)))
            }
            if (currentModule.datatables[$(wrap).attr('id')] && $(wrap).hasClass('dataTable')) {
                currentModule.datatables[$(wrap).attr('id')].draw();
            } else {
                var options = $.extend({}, this.defaults, response);
                $.getJSON(options.urlConfig, {name: $(wrap).data('datatable-name')}, function (response) {
                    response.config.drawCallback = function () {
                        helperAmd.run($(wrap));
                        if (options.additionalDrawCallback && typeof options.additionalDrawCallback == "function") {
                            options.additionalDrawCallback($(wrap));
                        }
                    }

                    /* Fix theme with requireJS which doesnt use DataTable.defaults */
                    response.config.dom = "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row row-footer'<'col-sm-5'i><'col-sm-7'p>>";

                    currentModule.datatables[$(wrap).attr('id')] = $(wrap).DataTable(response.config);
                });
            }
        },
        reload: function (wrap, response) {
            if (this.datatables[$(wrap).attr('id')]) {
                this.datatables[$(wrap).attr('id')].ajax.reload();
            }
        },
        addRow: function (wrap, row) {
            if (this.datatables[$(wrap).attr('id')]) {
                this.datatables[$(wrap).attr('id')].row.add(row);
            }
        }
    };
});
