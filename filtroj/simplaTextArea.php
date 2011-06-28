<?
function simplaTextArea($enhavo){
    if(empty($enhavo))
        $enhavo = "Nova paĝo";
    
    $vicoj = min(40,max(15,floor(substr_count($enhavo,"\n")*1.5)));
    return <<<simplaTextArea
    <textarea style='width:100%;' rows='$vicoj' id='simplaTextAreaRedaktilo' 
                                    
                                  onkeydown='if(event.ctrlKey && event.keyCode==13){
                                                $(this).fadeTo(250,0.25);
                                                konservuEnhavon(this.value);
                                                return false;
                                                
                                            }else if(event.keyCode==27){
                                                rekomencu();   
                                                return false;
                                            }'>$enhavo</textarea>
                                            <script>$('#simplaTextAreaRedaktilo').focus();</script>
    <div class='eta'>Klavpremu CTRL+Enter por konservi ŝanĝojn | ESC por forĵeti ŝanĝojn</div>                                            
simplaTextArea;
}



Pagxo::aldonuRedaktFiltron("simplaTextArea");
 