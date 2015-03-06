define(['jquery', 'jquerySoModal'], function($){
    return {
        run : function(wrap, response){
            $.soModal.close();
            $(wrap).parents('fieldset:first').remove();
        }
    };
});