define(['jquery', 'typeahead'], function ($) {
    return {
        defaults: {
            url: null
        },
        run: function (wrap, options) {
            options = $.extend(true, {}, this.defaults, options);

            // Surcharge via les attributs data
            var optionsFromData = $(wrap).data();
            options = $.extend(true, {}, options, optionsFromData);

            $(wrap).typeahead({
                minLength : 1,
                highlight : false,
                hint      : false
            },
            {
                name: 'page',
                source    : function(query, process){
                    var url = options.url;

                    $.getJSON(
                        url,
                        {
                            term  : query
                        },
                        function(response) {
                            process(response);
                        }
                    );
                },
                templates : {
                    suggestion : function(page) {
                        var pageHtml = '<span class="page-type">' + page.gabarit_label + '</span>'
                            + '<span class="page-label">' + page.label + '</span>';
                        return pageHtml;
                    }
                }
            }).on('typeahead:selected', function($e, page){
                window.location.href = 'back/page/display.html?id_gab_page=' + page.id;
            });
        }
    };
});
