define(['jquery', 'autocomplete'], function ($) {
    return {
        defaults: {
            format: function (element) {
                return element.text;
            },
            formatSelection: function (element) {
                return element.text;
            },
            data: function (params) {
                return {
                    term: params.term
                };
            },
            url: null
        },
        run: function (wrap, options) {
            options = $.extend(true, {}, this.defaults, options);

            // Surcharge via les attributs data
            var optionsFromData = $(wrap).data();
            options = $.extend(true, {}, options, optionsFromData);
            $(wrap).select2({
                ajax: {
                    url: options.url,
                    dataType: 'json',
                    delay: 250,
                    data: options.data,
                    processResults: function (data, page) {
                        return {
                            results: data.items
                        };
                    },
                },
                escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                minimumInputLength: 0,
                templateResult: options.format,
                templateSelection: options.formatSelection
            })
        }
    };
});
