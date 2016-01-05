define(['jquery', 'sortable'], function ($, Sortable) {
    return {
        run: function (wrap, response) {
            $(wrap).on('click', '.block-to-sort-handle', function (e) {
                e.preventDefault()
            })

            /** @todo Limiter le tri dans la zone du parent */
            Sortable.create(wrap.get(0), {
                animation: 150,
                handle: '.block-to-sort-handle',
                items: '.block-to-sort',
                // dragging started
                onStart: function (evt) {
                    $('.block-to-sort', wrap).each(function() {
                         $('div:first', this).hide();
                    })
                }
            });

        }
    };
});
