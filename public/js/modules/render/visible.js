define(['jquery'], function ($) {
    return {
        run : function(wrap, response){
            console.log(wrap.data());
            var others = $('[data-url="' + wrap.data('url') + '"][data-id="' + wrap.data('id') + '"][data-id_version="' + wrap.data('id_version') + '"]' );
            console.log(others);
            if (response.visible) {
                var title  = wrap.attr("title");

                wrap.add(others).removeClass("btn-default")
                    .addClass("btn-success")
                    .attr("title", title.substr(0, title.length - 19) + "invisible sur le site")
                    .data('visible', response.visible)

                wrap.find("i").removeClass("fa-eye-slash").addClass("fa-eye")
            } else {
                var title = wrap.attr("title");
                wrap.add(others).removeClass("btn-success")
                    .addClass("btn-default")
                    .attr("title", title.substr(0, title.length - 21) + "visible sur le site")
                    .data('visible', response.visible)

                wrap.find("i").removeClass("fa-eye").addClass("fa-eye-slash")
            }
        }
    };
});