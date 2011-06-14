    $(function(){
            $.ajaxSetup({timeout: 4000});   
            $.tempolimo = 0;
    
    		
            $("#nomo, #enhavo").keydown(traktilo).keypress(traktilo);
            $("#nomo, #enhavo").dblclick(function(){
                            $(this).attr("contentEditable",'true'); 
                            })
            });
            

/****************************************************/
var traktilo = function(event) {
                    if (!event) event = window.event;
                   	if (event.target) elemento = event.target;
                  	else if (event.srcElement) elemento = event.srcElement;

    
    
            		clearTimeout($.tempolimo);
                    
            		$.tempolimo = setTimeout( function(){ agu(elemento); }, 800);
            		return true;
            	};

/****************************************************/

function konservu(){
        $.ajax(
            { url :window.location.href, 
              type: "POST",
              data: {nomo:$("#nomo").html(),
                     enhavo: $("#enhavo").html(),
                     pagxo_id:$("#pagxo_id").val(),
                     ago: "konservi" },
              success:traktuRespondon});    
}

            
function agu(sender) {
    if(! $("#pagxo_id").val()  ){
        //nova pagxo
        konservu();
    }else{
        $.ajax(
            { url :window.location.href,
              type: "POST",
              data: {celo:(sender.id) ,
                     pagxo_id: $("#pagxo_id").val(), 
                     enhavo: $(sender).html(),
                     ago: "konservi" },
              success:traktuRespondon});    
    }
}


function traktuRespondon(data){
        data = $.parseJSON(data);   
                         
        if( data.titolo ){
            $('#titolo').html( data.titolo );
            document.title = data.titolo;
        }

        if(data.enhavo){
            $('#enhavo').fadeTo(100, 0.01, function () {
                            $(this).html(data.enhavo).fadeTo(100, 1);
                        });    
        }
        
        if(data.debug){
            $('#debug').fadeTo(100, 0.01, function () {
                            $(this).html(data.debug).fadeTo(100, 1);
                        });    
        } 
        
        if(data.id){
            $('#pagxo_id').val(data.id);
        }
            
        //cxiam faru mesagxojn
        $('#mesagxoj').html( data.mesagxoj );    
	     
        if( data.sxangxita ){
            $('#lasta_sxangxo').html( data.sxangxita);    
        }
}
