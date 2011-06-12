<?
// absouta vojo al dosierujo
define('WEB_DIR', dirname(__FILE__));


//por pli facilago de mult-uzantaj sistemoj
define('DB_DOSIERO',WEB_DIR.'\testdb');
//se uzita devas havi finan '/'
define('PREFIX_LIGILOJ', "tezar/");

////////////////////////////////////////////////////////////

include WEB_DIR."/app/Konektilo.php";
include WEB_DIR."/app/Utilaj.php";
include WEB_DIR."/app/Pagxo.php";

while(true){
    $vojo = trim($_SERVER["REQUEST_URI"],"/");

    //---------------- KONTROLO DE PREFIKSO 
    if( strlen(PREFIX_LIGILOJ) ){ 
        //------------------ MALBONA VOJO
        if( substr($vojo,0, strlen( trim(PREFIX_LIGILOJ,"/"))) != trim(PREFIX_LIGILOJ,"/") ){ 
            $enhavo = "Malbona vojo - (".PREFIX_LIGILOJ." x {$vojo})";
            break;    
        }
        
        //prefikso estas bona, fortranĉu ĝin
        $vojo = substr($vojo,strlen(PREFIX_LIGILOJ));
        
    }    


    //unuigo de "/" kaj "/index.php"
    if( $vojo == "index.php"){
        header("Location: http://{$_SERVER["HTTP_HOST"]}/".PREFIX_LIGILOJ,TRUE,307);
        exit;    
    }


    //unue ni asertos(?) ke ni havas bonan seo adreson
    $vojoSeo = SEOigu($vojo);
    
    if($vojo != $vojoSeo){
        //todo: kontroli protokolon cxu vere http
        header("Location: http://{$_SERVER["HTTP_HOST"]}/".PREFIX_LIGILOJ."$vojoSeo",TRUE,307);
        exit;
    } 


    
    //------------------ CXEFA PAGXO
    if($vojo == ""){ 
        $cxio = Pagxo::akiruCxiujn();
        
        $enhavo .= "<h1>Notilo</h1>";
        $enhavo .= faruListon($cxio);
        break;
    }


    //---------------------------- ALIAJ PAGXO
    //akiru pagxon per vojo(normale) aux per id (akirita trans la post, cxar nomo povis sxangxi)
    //aux priparu novan
    try{
        $pagxo = new Pagxo( isset($_POST["pagxo_id"])?$_POST["pagxo_id"]:$vojo );
        
    }catch(PagxoException $e){ //...aux priparu novan
        //se oni okazais aliaeraro ol ke pagxo neekzistas nedauxrigu
        if($e->getCode() != Pagxo::NEEKZISTAS) throw $e;
        $pagxo = new Pagxo();
        $vojeroj = explode("/",$vojo);
        $pagxo["nomo"] = array_pop($vojeroj);
        $pagxo["enhavo"] = "Nova pagxo";
        
        if(!empty($vojeroj)){
            $pagxo["patro"] = implode("/",$vojeroj);
        }
    }
    
    
    if($_POST){
        //todo: redoni json nur se estas AJAX demando
        switch($_POST["ago"]){
            case "konservi":
            
                        //kiam estas kreita nova pagxo, ni ricevas kaj nomon kaj tekston kune
                        if( ($_POST["celo"]=="nomo") or isset($_POST["nomo"]) ){
                            $pagxo["nomo"] = strip_tags( isset($_POST["nomo"])? $_POST["nomo"] : $_POST["enhavo"] );    
                        }
                        
                        if( ($_POST["celo"]=="enhavo") or ( !isset($_POST["celo"]) and isset($_POST["enhavo"]) ) ){
                            $pagxo["enhavo"] = strip_tags( isset($_POST["enhavo"])? $_POST["enhavo"] : $_POST["enhavo"] );    
                        }

                        $pagxo->konservu();
                       
                        //todo: cxu ni vere bezonas ajax/post? nesuficxas nur ajax?
                        if($_SERVER["HTTP_X_REQUESTED_WITH"] == 'XMLHttpRequest'){//<- demandita per AJAX
                            echo json_encode( Array("nomo"=>$pagxo["nomo"], "enhavo"=>$pagxo["enhavo"],"id"=>$pagxo["id"], "debug" => kreuDebugTablon($DB_DEBUG) )); 
                            exit;    
                        }
                        
                        break;
            case "idoj":
                        $idoj = $pagxo->akiruIdojn();
                       
                        if($_SERVER["HTTP_X_REQUESTED_WITH"] == 'XMLHttpRequest'){//<- demandita per AJAX
                            echo json_encode( Array("idoj"=>$idoj));
                            exit;    
                        }
                        break;                        
            
        }

        
    }
    
    $enhavo = "<h1>&raquo;<span id='nomo'>{$pagxo["nomo"]}</span></h1>";
    $enhavo .= "<div id='enhavo'>{$pagxo["enhavo"]}sdfasf<br/></div>";
    $enhavo .= "<div id='lasta_sxangxo'>".($pagxo["sxangxita"]?date("H:i:s d.m.Y",$pagxo["sxangxita"]):"Nova")."<br/></div>";
    $enhavo .= "<input type='text' id='pagxo_id' value='{$pagxo["id"]}' />";

    break;
}




header('Content-Type: text/html; charset=utf-8');
 
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta name="robots" content="noindex, nofollow" />
        <!-- <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script> -->
        <script type="text/javascript" src="/jquery.min.js"></script>
        <script type="text/javascript" src="/ajax.js"></script>

        <link rel="stylesheet" type="text/css" href="/styloj.css" /> 

        <title><?= $nomo ?></title>
</head>
<body>
<div class="eta" style="float: right;"><?= $_SERVER["REMOTE_ADDR"] ?></div>

<div id="menuo">
<a href="/<?=PREFIX_LIGILOJ?>">Ĉefa paĝo</a>
<a href="/<?=PREFIX_LIGILOJ?>agordoj">Agordoj</a>
</div>


<?= $enhavo ?>

<?


/*******************************************/
// transkribu debug informaron plenigitan per Konktilo.php/notormdebug
  
$i=0;
echo "<hr /><div id='debug'><table style='clear:both;'>";
echo kreuDebugTablon($DB_DEBUG);
echo "</div>";
?>
</body>
</html>


