(function($){
    $.sticky=function(note,options,callback){
        return $.fn.sticky(note,options,callback)
        };

    $.fn.sticky=function(note,options,callback){
        var position="top-right";
        var settings={
            speed:"fast",
            type:"notice", // notice, success, warning, error
            duplicates:true,
            autoclose:5000
//            autoclose:false

        };

        if(options && typeof(options)=='string') {
            var type = options
            options = {
                type : type
            }
        }

        if(!note){
            note=this.html()
            }
            if(options){
            $.extend(settings,options)
            }
            var display=true;
        var duplicate="no";
        var uniqID=Math.floor(Math.random()*99999);
        $(".sticky-note").each(function(){
            if($(this).html()==note&&$(this).is(":visible")){
                duplicate="yes";
                if(!settings.duplicates){
                    display=false
                    }
                }
            if($(this).attr("id")==uniqID){
            uniqID=Math.floor(Math.random()*9999999)
            }
        });
if(!$("body").find(".sticky-queue").html()){
    $("body").append('<div class="sticky-queue '+position+'"></div>')
    }
    if(display){
    $(".sticky-queue").prepend('<div class="sticky border-'+position+' sticky-'+settings.type+'" id="'+uniqID+'"></div>');
    $("#"+uniqID).append('<img src="public/default/back/css/close.png" class="sticky-close" rel="'+uniqID+'" title="Close" />');
    $("#"+uniqID).append('<div class="sticky-note" rel="'+uniqID+'">'+note+"</div>");
    var height=$("#"+uniqID).height();
    $("#"+uniqID).css("height",height);
    $("#"+uniqID).slideDown(settings.speed);
    display=true
    }
    $(".sticky").ready(function(){
    if(settings.autoclose){
        $("#"+uniqID).delay(settings.autoclose).fadeOut(settings.speed)
        }
    });
$(".sticky-close").click(function(){
    $("#"+$(this).attr("rel")).dequeue().fadeOut(settings.speed)
    });
var response={
    id:uniqID,
    duplicate:duplicate,
    displayed:display,
    position:position
};

if(callback){
    callback(response)
    }else{
    return(response)
    }
}
})(jQuery);