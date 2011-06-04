<?
// absolute filesystem path to the web root
define('WEB_DIR', dirname(__FILE__));


include WEB_DIR."/app/Konektilo.php";
include WEB_DIR."/app/Utilaj.php";
include WEB_DIR."/app/Pagxo.php";

$vojo = trim($_SERVER["REQUEST_URI"],"/");

//todo: cxu ni povas esti en subdosieruo?
//unuigo de "/" kaj "/index.php"
if( $vojo == "index.php"){
    header("Location: http://{$_SERVER["HTTP_HOST"]}/",TRUE,307);
    exit;    
}

//unue ni asertos(?) ke ni havas bonan seo adreson
$vojoSeo = SEOigu($vojo);

if($vojo != $vojoSeo){
    //todo: kontroli protokolon cxu vere http
    header("Location: http://{$_SERVER["HTTP_HOST"]}/$vojoSeo",TRUE,307);
    exit;
} 



if($vojo == ""){ //<------------------ CXEFA PAGXO
    $cxio = Pagxo::akiruCxiujn();
    $enhavo = faruListon($cxio);
    
}elseif( false){ //<------------------ AGORDOJ
    
}else{ //<---------------------------- ALIAJ PAGXO
    //akiru pagxon per vojo(normale) aux per id (akirita trans la post, cxar nomo povis sxangxi) aux priparu novan
    try{
        $pagxo = new Pagxo( isset($_POST["pagxo_id"])?$_POST["pagxo_id"]:$vojo );
        
    }catch(PagxoException $e){
        //se oni okazais aliaeraro ol ke pagxo neekzistas nedauxrigu
        if($e->getCode() != Pagxo::NEEKZISTAS) throw $e;
        $pagxo = new Pagxo();
        $vojeroj = explode("/",$vojo);
        $pagxo["nomo"] = array_pop($vojeroj);
        $pagxo["teksto"] = "Nova pagxo";
        
        if(!empty($vojeroj)){
            $pagxo["patro"] = implode("/",$vojeroj);
        }
    }
    
    
    if($_POST){
        //todo: redoni json nur se estas AJAX demando
        switch($_POST["ago"]){
            case "konservi":
                        switch($_POST["celo"]){
                            case "nomo": $pagxo["nomo"] = strip_tags($_POST["enhavo"]); break;
                            case "teksto": $pagxo["teksto"] = $_POST["enhavo"]; break; 
                            default: die("chyba");
                        }
                        
                        $pagxo->konservu();
                       
                        //todo: cxu ni vere bezonas ajax/post? nesuficxas nur ajax?
                        if($_SERVER["HTTP_X_REQUESTED_WITH"] == 'XMLHttpRequest'){//<- demandita per AJAX
                            echo json_encode( Array("nomo"=>$pagxo["nomo"], "teksto"=>$pagxo["teksto"], "debug" => kreuDebugTablon($DB_DEBUG) )); 
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
    
    $enhavo = "<div id='nomo'><h1>{$pagxo["nomo"]}</h1></div>";
    $enhavo .= "<div id='teksto'>{$pagxo["teksto"]}sdfasf<br/></div>";
    $enhavo .= "<input type='hidden' id='pagxo_id' value='{$pagxo["id"]}' />";
    
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
<div class="eta"><?= $_SERVER["REMOTE_ADDR"] ?></div>
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


