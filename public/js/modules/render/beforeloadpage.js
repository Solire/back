define(['jquery', 'modules/helper/dialog'], function ($, helperDialog) {
    return {
        run : function(wrap, response){
            if ($('#modalMore').length == 0) {
                return false;
            }
            var dialogParams = {
                'html': $('#modalMore').removeClass('hidden')
            }
            var dialogOptions = {
                'modalClasses': 'modal-dialog'
            }
            helperDialog.run(dialogOptions, dialogParams);
        }
    };
});