define(['jquery', 'autocomplete', 'autocompleteFr'], function ($) {
    return {
        defaults: {
            templateResult: function (element) {
                return element.text;
            },
            templateSelection: function (element) {
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
                language: 'fr',
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
                templateResult: options.templateResult,
                templateSelection: options.formatSelection
            })
        }
    };
});
