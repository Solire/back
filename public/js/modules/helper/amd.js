define(['jquery'], function ($) {
    return {
        run: function (wrap, response) {
            $('[data-amd]', wrap).each(function () {
                var elem    = $(this),
                    modules = elem.data('amd').split(',');
                require(modules, function () {
                    $.each(arguments, function (ii, module) {
                        module.run(elem);
                    });
                });
            });
        }
    };
});
