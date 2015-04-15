define(['jquery', 'modules/helper/dialog'], function ($, helperDialog) {
    return {
        run : function(wrap, response){
            if ($('#modalMore').length == 0) {
                return false;
            }
            var dialogParams = {
                'html': $('#modalMore').removeClass('hidden')
            }
            helperDialog.run(null, dialogParams);
        }
    };
});