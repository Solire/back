define(['jquery'], function ($) {
    return {
        run : function(wrap, response){
            $("body").delay(1500).queue(function(){
                if (response.status == "success") {
                    if (typeof response.id_gab_page != "undefined") {
                        $.cookie("id_gab_page", response.id_gab_page, {
                            path : '/'
                        });
                        $('[name=id_gab_page]').val(response.id_gab_page);

                    }

                    if (response.search) {
                        document.location.search = response.search;
                    }
                }
                $("body").dequeue()
            });
        }
    };
});