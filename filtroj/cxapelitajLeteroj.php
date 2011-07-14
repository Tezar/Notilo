<?


function cxapelitajLeteroj($teksto){
    return preg_replace_callback('/([cghjsu]x)(?!x)/i', 
                                    create_function('$kio', '
                                                    $leteroj = array (
                                                			"Cx" => "\xc4\x88" , "CX" => "\xc4\x88" ,
                                                			"cx" => "\xc4\x89" , "cX" => "\xc4\x89" ,
                                                			"Gx" => "\xc4\x9c" , "GX" => "\xc4\x9c" ,
                                                			"gx" => "\xc4\x9d" , "gX" => "\xc4\x9d" ,
                                                			"Hx" => "\xc4\xa4" , "HX" => "\xc4\xa4" ,
                                                			"hx" => "\xc4\xa5" , "hX" => "\xc4\xa5" ,
                                                			"Jx" => "\xc4\xb4" , "JX" => "\xc4\xb4" ,
                                                			"jx" => "\xc4\xb5" , "jX" => "\xc4\xb5" ,
                                                			"Sx" => "\xc5\x9c" , "SX" => "\xc5\x9c" ,
                                                			"sx" => "\xc5\x9d" , "sX" => "\xc5\x9d" ,
                                                			"Ux" => "\xc5\xac" , "UX" => "\xc5\xac" ,
                                                			"ux" => "\xc5\xad" , "uX" => "\xc5\xad"
                                                		);
                                                     return $leteroj[$kio[1]];'), 
                            $teksto);
}


Pagxo::aldonuEnFiltron("cxapelitajLeteroj");