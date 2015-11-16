define(['jquery', 'modules/config/datepicker'], function ($) {
    return {
        run: function (wrap, response) {
            $(wrap).datepicker()
        }
    };
});
