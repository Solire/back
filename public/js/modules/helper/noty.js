define(['jquery', 'noty', 'modules/config/noty'], function ($) {
    return {
        run : function(wrap, response){
            var layout = 'bottomRight';
            if (typeof response.layout !== 'undefined') {
                layout = response.layout;
            }

            noty({
              text: response.text,
              type: response.status,
              layout: layout
            });
        }
    };
});
