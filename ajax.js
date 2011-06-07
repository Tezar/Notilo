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
            
function agu(sender) {
	$.post(window.location.href, {"celo":(sender.id) ,"pagxo_id": $("#pagxo_id").val(), "enhavo": $(sender).html(),ago: "konservi" }, 
            function(data){                
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
			     
                if( data.lasta ){
                    $('#lasta').html( data.lasta);    
                }
    		}
    );
}
