define(['jquery', 'bootstrapDatepicker', 'bootstrapDatepickerFr'], function ($) {
    return {
        run: function (wrap, response) {
            $(wrap).datepicker({
                format: 'dd/mm/yyyy',
                language: 'fr'
            })
        }
    };
});
