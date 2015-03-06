define(['jquery', 'jqueryPluploader'], function($){
    return {
        run : function(wrap, givenParams){
            $(wrap).pluploader(givenParams);
        }
    };
});
