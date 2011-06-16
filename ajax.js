    $(function(){
            $.ajaxSetup({timeout: 4000,
                         url :window.location.href,
                         type: "POST",
                         success:traktuRespondon,
                         error: function (a,err){alert(a.responseText); alert(err);} });   
            $.tempolimo = 0;
            
            
            $("#sxargilo").ajaxStart(function(){
                                        $(this).show(); })
                          .ajaxComplete(function(){
                                        $(this).hide();
                            });
    
    		
            $("#nomo").click(aldonuEdit);
            
            $("#enhavo").dblclick(function(){
                             $.ajax(  { beforeSend: function(){  $("#enhavo").fadeTo(250,0.25); },
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
                                        konservuNomon();
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
function rekomencu(){
    $.ajax({data:{ago:"akiru",pagxo_id:$("#pagxo_id").val()}});
}

function forvisxu(){
    $.ajax({data:{ago:"forvisxi",pagxo_id:$("#pagxo_id").val()}});
}


function konservuNomon(){
        $.ajax(
            { data: {nomo:$("#nomo").html(),
                     pagxo_id:$("#pagxo_id").val(),
                     ago: "konservi" }});    
}


function konservuEnhavon(enhavo,memgxisdatigu){
    if(memgxisdatigu == null){
        memgxisdatigu = true;
    }
    var sendi = {nomo:$("#nomo").html(), //ĉiam sendu nomond
                 enhavo: enhavo ,
                 pagxo_id:$("#pagxo_id").val(),
                 ago: "konservi" };
                 
    var demando = {data:sendi}                 
    if(memgxisdatigu==false)
        demando.success = function(data){traktuRespondon(data,false);}; 
    
    $.ajax( demando );    
}


function traktuRespondon(data,memgxisdatigu){
        if(memgxisdatigu == null){
            memgxisdatigu = true;
        }
    
        
        data =  $.parseJSON(data);

        if(data.loko && data.loko !== window.location.pathname){
            window.location.pathname = data.loko ;
            return;
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
        
        
        
        if( ! memgxisdatigu ){ //se ni nerajtas mem ĝisdatigi ni finas
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

}
