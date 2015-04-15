
require.config({
    baseUrl                 : './',
    urlArgs                 : 'bust=' +  (new Date()).getTime(),
    waitSeconds             : 15,
    paths : requireJsConfig.paths,
    shim : requireJsConfig.shim
});


require(
    ['jquery', 'bootstrap', 'material', 'ripples'],
    function ($) {
        $(function(){
            $.material.init();
            $('[data-amd]').each(function(){
                var wrap = $(this),
                    modules = $(wrap).data('amd').split(',');
                require(modules, function(){
                    $.each(arguments, function(ii, module){
                        module.run(wrap);
                    });
                });
            });
        });
    }
);
