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


//todo: se estas nova paĝo, ni devas sendi ankoraŭ nomon kaj daŭre ni devas plenigi pagxo_id
            
function agu(sender) {
    if(! $("#pagxo_id").val()  ){
        //nova pagxo
        $.post(window.location.href, {"nomo":$("#nomo").html(),"enhavo": $("#enhavo").html(),ago: "konservi" },traktuRespondon);        
    }else{
        $.post(window.location.href, {"celo":(sender.id) ,"pagxo_id": $("#pagxo_id").val(), "enhavo": $(sender).html(),ago: "konservi" },traktuRespondon);    
    }
}


function traktuRespondon(data){
        alert(data);
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
	     
        if( data.lasta ){
            $('#lasta').html( data.lasta);    
        }
}
