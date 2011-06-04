<?php

/**
 * SEOigu()
 * faras novan cxenon amikeman al ligiloj
 * 
 * @param string $cxeno
 * @return string
 */
function SEOigu($cxeno){
    $url = $cxeno;
    $url = preg_replace('~[^\\pL0-9_\/]+~u', '-', $url);
    $url = trim($url, "-");
    $url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
    $url = strtolower($url);
    $url = preg_replace('~[^-a-z0-9_/]+~', '', $url);
    return $url;
}     




function faruListon($datoj){
    $redono = "<ul>\n";
    
    foreach($datoj as $ero){
        list($vojo,$titolo,$idoj) = $ero;
        $redono .= "<li>";
        $redono .= "<a href='$vojo'>$titolo</a>";
        
        if(!empty($idoj)){
            $redono .= faruListon($idoj);
        }
        $redono .= "</li>";
    }
    return $redono .= "</ul>\n";
};


function kreuDebugTablon($dataro){
    $redono = "<table style='clear:both; width:100%;'>";
    $i=0;
    foreach($dataro as $paro){
        list($demando,$par) = $paro;
        $redono .= "<tr><td>".(++$i)."</td><td>$demando</td><td>".var_export($par,true)."</td></tr>";
    }
    return $redono .= "</table>";
}


