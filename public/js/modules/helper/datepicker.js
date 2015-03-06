define(['jquery', 'bootstrapDatepicker', 'bootstrapDatepickerFr'], function ($) {
    return {
        run: function (wrap, response) {
            $(wrap).datepicker({
                format: 'mm/dd/yyyy',
                language: 'fr'
            })
        }
    };
});
