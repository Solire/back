define(['jquery', 'sortable'], function ($, Sortable) {
    return {
        run: function (wrap, response) {
            Sortable.create(wrap.get(0), {
                animation: 150,
                handle: '.block-to-sort-handle',
                items: '.block-to-sort',
                // dragging started
                onStart: function (evt) {
                    $('.block-to-sort', wrap).each(function() {
                         $('div:first', this).hide();
                    })
                },

                // dragging ended
                onEnd: function (/**Event*/evt) {
//                    evt.oldIndex;  // element's old index within parent
//                    evt.newIndex;  // element's new index within parent
                },
            });

        }
    };
});
