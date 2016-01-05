define(['jquery', 'modules/helper/amd', 'datatablesMaterialDesign', 'datatablesResponsive', 'datatables-light-columnfilter', 'datatablesLCFBootstrap3'], function ($, helperAmd) {
    return {
        datatables: [],
        defaults: {
            urlConfig: 'back/datatable/listconfig.html',
            additionalDrawCallback: null,
            config: null
        },
        run: function (wrap, options) {
            var currentModule = this;

            if (typeof $(wrap).attr('id') == 'undefined') {
                $(wrap).attr('id', 'datatable-' + Math.floor((Math.random() * 10000)))
            }
            if (currentModule.datatables[$(wrap).attr('id')] && $(wrap).hasClass('dataTable')) {
                currentModule.datatables[$(wrap).attr('id')].draw();
            } else {
                var options = $.extend({}, this.defaults, options);
                $.getJSON(options.urlConfig, {name: $(wrap).data('datatable-name')}, function (response) {
                    response.config = $.extend({}, response.config, options.config);

                    response.config.drawCallback = function () {
                        helperAmd.run($(wrap));
                        if (options.additionalDrawCallback && typeof options.additionalDrawCallback == "function") {
                            options.additionalDrawCallback($(wrap));
                        }
                    }

                    response.config.initComplete = function () {
                        var datatableWrapper = $(wrap).parents('.datatable-wrapper:first');
                        datatableWrapper.removeClass('hidden');
                        var height = datatableWrapper.outerHeight();
                        datatableWrapper.css({'height': '100px', opacity: 0.2}).animate({'height': height, opacity: 1}, 350, function() {
                            $(this).css({'height': 'auto'})
                        });
                    }

                    response.config.responsive = true;
                    response.config.autoWidth = false;

                    /* Fix theme with requireJS which doesnt use DataTable.defaults */
                    response.config.dom = "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row row-footer'<'col-sm-5'i><'col-sm-7'p>>";

                    currentModule.datatables[$(wrap).attr('id')] = $(wrap).DataTable(response.config);
                    new $.fn.dataTable.ColumnFilter(currentModule.datatables[$(wrap).attr('id')], response.columnFilterConfig);
                });
            }
        },
        reload: function (wrap) {
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
