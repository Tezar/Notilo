<?
// include Texy!
require WEB_DIR."/filtroj/texy.min.php";


function filtroTexy($enhavo){
    $texy = new Texy();
    return $texy->process($enhavo);      
}

Pagxo::aldonuElFiltron("filtroTexy");


