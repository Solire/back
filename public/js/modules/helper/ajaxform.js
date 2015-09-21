define(['jquery', 'jqueryControle', 'modules/helper/wysiwyg'], function($, jqueryControle, HelperWysiwyg){
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
                }
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
                    elmt.parents('.form-group:first').removeClass('error');
                },
                ifFirstWrong : function(elmt){
                    elmt.focus();
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
