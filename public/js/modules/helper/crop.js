define(['jquery', 'jcrop'], function ($) {
    return {
        defaults: {
            boxWidth: 540,
            boxHeight: 400,
            url: 'back/media/crop.html',
            onclickCrop: '.exec-onclick-crop'
        },
        getOptions: function(wrap, options) {
            options = $.extend(true, {}, this.defaults, options);

            // Surcharge via les attributs data
            var optionsFromData = $(wrap).data();
            options = $.extend(true, {}, options, optionsFromData);

            return options;
        },
        run: function (wrap, options) {
            var currentModule = this;

            options = this.getOptions(wrap, options);
            $(wrap).data('helper-crop-options', options)
            $(wrap).Jcrop(options);

            $(wrap).Jcrop('api').container.on('cropmove cropend',function(e,s,c){
                $(wrap).data('crop', c);
            });

            $(document.body).off('click', options.onclickCrop).on('click', options.onclickCrop, function() {
                currentModule.crop(wrap, options);
            })
        },
        crop: function(wrap) {
            var options = $(wrap).data('helper-crop-options'),
                cropData = $(wrap).data('crop'),
                cropData = $.extend(true, {}, $(wrap).data('params'), cropData);

            cropData.src = options.src;
            cropData.dest = options.dest;

            $.post(options.url, cropData, function(response) {
                $(wrap).trigger( "croped.helper.crop", response );
            }, 'json');
        }
    };
});
