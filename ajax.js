    $(function(){
            $.ajaxSetup({timeout: 4000,
                         url :window.location.href,
                         type: "POST",
                         success:traktuRespondon,
                         error: function (a){ alert(a);} });   
            $.tempolimo = 0;
            
            
            $("#sxargilo").ajaxStart(function(){
                                        $(this).show(); })
                          .ajaxComplete(function(){
                                        $(this).hide();
                            });
    
    		
            $("#nomo").click(aldonuEdit);
            $("#enhavo").dblclick(function(){
                             $.ajax(  { beforeSend: function(){  $("#enhavo").fadeTo(250,0.1); },
                                        data: {ago:"redakti"} }
                                    );    
                            })
            });
            


//aldonas redakteblan titolon 
var aldonuEdit = function(event){
                var valoro = $(event.target).text();
                var patro = $(event.target).parent();
                $(event.target).remove();
                
                el = document.createElement("input");
                el.onkeypress = function(e){
                                    var el = e.target;
                                    if(e.keyCode=='13'){
                                        redonu(el, el.value);
                                        agu( $("#nomo") );
                                        }
                                    else if(e.keyCode=='27'){
                                        redonu(el, el.origValoro );
                                        } 
                                };
                el.onblur = function (e){ var el = e.target; redonu(el, el.origValoro); };
                                                
                el.id="nomo";                
                el.type="text";
                el.value = valoro;
                el.origValoro = valoro;
                patro.append(el);
                el.focus();
        }
        
// redonas redakteblan titolon reen al h1titolo         
var redonu = function (el, str ){
   var patro = $(el).parent();
   el.onblur = null ;
   $(el).remove();
   
   elspan = document.createElement("span");    
   elspan.id="nomo";
   elspan.innerHTML = str;   
   elspan.onclick = aldonuEdit;    
   $(patro).append(elspan);     
}            

/****************************************************/
var traktu = function(event) {
                    if (!event) event = window.event;
                   	if (event.target) elemento = event.target;
                  	else if (event.srcElement) elemento = event.srcElement;

    
                    if (event.which == 0 || event.charCode == 0) { //ignoru klavarojn kiu neskribas signojn
                            return true;
                    }

    
            		clearTimeout($.tempolimo);
                    
            		$.tempolimo = setTimeout( function(){ agu($(elemento));  }, 800);
            		return true;
            	};

/****************************************************/
function agu(sender) {
    if(! $("#pagxo_id").val()  ){
        //nova pagxo
        konservu();
    }else{
        $.ajax(
            { data: {celo:(sender.attr('id') ) ,
                     pagxo_id: $("#pagxo_id").val(), 
                     enhavo: $(sender).html(),
                     ago: "konservi" }});    
    }
}

function konservu(){
        $.ajax(
            { data: {nomo:$("#nomo").html(),
                     enhavo: $("#enhavo").html(),
                     pagxo_id:$("#pagxo_id").val(),
                     ago: "konservi" }});    
}

function traktuRespondon(data){
        
        data =  $.parseJSON(data);

        if(data.loko && data.loko !== window.location.pathname){
            window.location.pathname = data.loko ;
            return;
        }
                         
        if( data.nomo ){
            $('#nomo').html( data.nomo );
            document.title = data.nomo;
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
        $('#mesagxoj').fadeTo(100, 0.01, function () {
                            $(this).html(data.mesagxoj).fadeTo(100, 1);
                        });      
	     
        if( data.sxangxita ){
            $('#lasta_sxangxo').html( data.sxangxita);    
        }
}
