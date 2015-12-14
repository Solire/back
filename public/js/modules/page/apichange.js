define(['jquery', 'jqueryCookie'], function ($, jqueryCookie) {
    return {
        run: function (wrap, response) {
             $('a', wrap).click(function() {
                 $.cookie('api', $(this).data('apiName'), {path : '/'});
             });
        }
    };
});
