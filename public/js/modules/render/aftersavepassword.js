define(['jquery'], function ($) {
    return {
        run : function(wrap, response){
            $("body").delay(1500).queue(function(){
                if (response.status == "success") {
                    window.location.reload();
                }
                $("body").dequeue()
            });
        }
    };
});