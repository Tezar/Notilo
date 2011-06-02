    function texy() {
		$.post(window.location.href, { teksto: $('#teksto').val(),ago: "konservi" }, 
                function(data){
                     $('#ujo').fadeTo(100, 0.01, function () {
                                        $(this).html(data).fadeTo(100, 1);
                                    });
                     
                    data = $.parseJSON(data);   
                                     
                    if( data.titolo ){
                        $('#titolo').html( data.titolo );
                        document.title = data.titolo;
                    }
            
                    if(data.teksto){
                        $('#ujo').fadeTo(100, 0.01, function () {
                                        $(this).html(data.teksto).fadeTo(100, 1);
                                    });    
                    }
    			     
                    if( data.lasta ){
                        $('#lasta').html( data.lasta);    
                    }
        		}
        );
	}

	var traktilo = function(event) {
			clearTimeout($.tempolimo);
            
			$.tempolimo = setTimeout(texy, 800);
			return true;
		};

	$(function(){
        $.ajaxSetup({timeout: 4000});   
        $.tempolimo = 0;

		$('#teksto').focus().keydown(traktilo).keypress(traktilo);
        
        $("#ujo").dblclick(function(){
            fr = $("<iframe />").contentEditable='true';
            fr.designMode='on'; 
            fr.html=$(this).html();
            $(this).after( fr );
            //$(fr).after(this);
           //.after($(this));
            //$(this).contentEditable='true';
            //document.designMode='on'; 
            
        })
	});