define(['jquery', 'noty'], function ($) {
    return {
        run : function(wrap, response){
            noty({text: response.text, type: response.status});
        }
    };
});
