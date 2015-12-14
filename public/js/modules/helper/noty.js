define(['jquery', 'noty'], function ($) {
    return {
        run : function(wrap, response){
            require(['modules/config/noty'], function() {
                noty({text: response.text, type: response.status});
            })
        }
    };
});
