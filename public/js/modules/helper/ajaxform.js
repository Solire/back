define(['jquery', 'jqueryControle', 'jqueryScrollTo', 'modules/helper/wysiwyg'], function($, jqueryControle, jqueryScrollTo, HelperWysiwyg){
    return {
        run : function(wrap, response){
            var regExps = {
                atleastonenotnul : function(value, elmt, champObj){
                    var name = elmt.attr('name').replace(/\[\d+\]$/, ''),
                        number = 0;

                    $('input[name^=' + name + ']', this).each(function(){
                        if ($(this).val() != '' && $(this).val() != 0) {
                            number++;
                        }
                    });

                    return (number > 0);
                },
                verification : function(value, elmt, champObj){
                    if (value && value == $('.form-pass', this).val()) {
                        return true;
                    }
                    return false;
                },
                gmap_point_lat		: /\-?[0-9]+[\.]{0,1}[0-9]*/, // latitude
                gmap_point_lng		: /\-?[0-9]+[\.]{0,1}[0-9]*/, // longitude
                gmap_point_zoom		: /^[0-9]{1,2}$/, // num de 0 à 99 (zoom gmap)
                txt		: /^[a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s]{2,}$/, // texte uniquement
                tel		: /^[ /\()+.0-9]{10,20}$/, // No tél
                cp		: /^[0-9]{4,5}$/, // code postal
                heure	: /^[0-9]{2,2}:[0-9]{2,2}$/, // date
                date	: /^[0-9]{2,2}\/[0-9]{2,2}\/[0-9]{4,4}$/, // date
                mail	: /^[a-z0-9._-]+@[a-z0-9.-]{2,}[.][a-z]{2,3}$/, // email
                url		: /^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/, // adresse url
                rew		: /^[0-9a-z-]{1,50}$/, // rewriting
                file    : function(value) {
                    return (value.length > 2);
                },
            };

            $(wrap).controle({
                ajax : true,
                elmtEvents : {
                    focusout : function(e, obj) {
                        obj.check();
                    }
                },
                ifWrong : function(elmt){
                    elmt.parents('.form-group:first').addClass('has-error');
                },
                ifRight : function(elmt){
                    elmt.parents('.form-group:first').removeClass('has-error');
                },
                ifFirstWrong : function(elmt){
                    $(elmt.parents('fieldset').get().reverse()).each(function() {
                        if($(this).find('div:first').is(':hidden')) {
                            $(this).find('legend:first').click();
                            setTimeout(function() {
                                $.scrollTo(elmt, 500, {offset:-70}, function() {
                                    if (elmt.data('select2')) {
                                        elmt.select2('open');
                                    } else {
                                        elmt.focus();
                                    }
                                });
                            }, 500);
                        } else {
                            $.scrollTo(elmt, 500, {offset:-70}, function() {
                                if (elmt.data('select2')) {
                                    elmt.select2('open');
                                } else {
                                    elmt.focus();
                                }
                            });
                        }
                    });
                },
                afterSubmit : function(response){
                    if ('after' in response) {
                        require(response.after, function(){
                            $.each(arguments, function(ii, module){
                                module.run(null, response);
                            });
                        });
                    }
                }
            }, regExps);
        }
    };
});
