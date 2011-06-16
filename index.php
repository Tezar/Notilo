<?
// absouta vojo al dosierujo

define('WEB_DIR', dirname(__FILE__));


//por pli facilago de mult-uzantaj sistemoj
define('DB_DOSIERO',WEB_DIR.'\testdb');
//se uzita devas havi finan '/'
define('PREFIX_LIGILOJ', "");

////////////////////////////////////////////////////////////

include WEB_DIR."/app/Konektilo.php";
include WEB_DIR."/app/Utilaj.php";
include WEB_DIR."/app/Pagxo.php";

////////////////////////////////////////////////////////////





include WEB_DIR."/filtroj/texyFiltro.php";
include WEB_DIR."/filtroj/simplaTextArea.php";


while(true){
    $vojo = trim($_SERVER["REQUEST_URI"],"/");

    //------------------------------------------------------- KONTROLO DE PREFIKSO 
    if( strlen(PREFIX_LIGILOJ) ){ 
        //------------------ MALBONA VOJO
        if( substr($vojo,0, strlen( trim(PREFIX_LIGILOJ,"/"))) != trim(PREFIX_LIGILOJ,"/") ){ 
            $enhavo = "Malbona vojo - (".PREFIX_LIGILOJ." x {$vojo})";
            break;    
        }
        
        //prefikso estas bona, fortranĉu ĝin
        $vojo = substr($vojo,strlen(PREFIX_LIGILOJ));
        
    }    


    //------------------------------------------------------- unuigo de "/" kaj "/index.php"
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



    //------------------------------------------------------- BAZA MENUO
    $menuaro[] = Array('Ĉefa paĝo',' ','hejmo');
    $menuaro[] = Array('Agordoj','agordoj','agordoj');

    
    //-------------------------------------------------------  CXEFA PAGXO
    if($vojo == ""){ 
        $cxio = Pagxo::akiruCxiujn();
        
        $enhavo .= "<h1>Notilo</h1>";
        $enhavo .= faruListon($cxio);
        break;
    }


    //---------------------------- ALIAJ PAGXO
    
    //menuero
    $menuaro[] = Array('forviŝi','#','forvisxi','if(confirm("Ĉu vi certas?")){ forvisxu(); } return false;');

    //akiru pagxon per vojo(normale) aux per id (akirita trans la post, cxar nomo povis sxangxi)
    //aux priparu novan
    try{
        $pagxo = new Pagxo( !empty($_POST["pagxo_id"])?$_POST["pagxo_id"]:$vojo );
    }catch(PagxoException $e){ //...aux priparu novan
        //se oni okazais aliaeraro ol ke pagxo neekzistas nedauxrigu
        if($e->getCode() != Pagxo::NEEKZISTAS) throw $e;
        
        $pagxo = new Pagxo();
        $vojeroj = explode("/",$vojo);
        $pagxo["nomo"] = array_pop($vojeroj);
        $pagxo["enhavo"] = "Nova pagxo";
        
        
        mesagxu("Tiuĉi paĝo estas nova");
        
        $DB_DEBUG[] = Array("vojeroj",implode("/",$vojeroj));
        if(!empty($vojeroj)){
            $pagxo["patro"] = implode("/",$vojeroj);
        }
    }
    
    
    if($_POST){
        //sleep(1); // por testkialoj
        $dataro = Array();
        
        switch($_POST["ago"]){
            case "akiru": //kiam oni forĵetas ŝangoĵn
                $dataro = Array( "nomo"=>$pagxo["nomo"], 
                                 "loko" => "/".PREFIX_LIGILOJ.$pagxo["vojo"],
                                 "enhavo"=>$pagxo["enhavo"],
                                 "id"=>$pagxo["id"],
                                 "sxangxita" => ($pagxo["sxangxita"]?date("H:i:s d.m.Y",$pagxo["sxangxita"]):"Nova"));
            break;
            
            
            
            case "konservi":
                    $DB_DEBUG[] = Array("",var_export($_POST,true));
                    //kiam estas kreita nova pagxo, ni ricevas kaj nomon kaj tekston kune
                    if( isset($_POST["nomo"]) ){
                        $pagxo["nomo"] = strip_tags($_POST["nomo"] );    
                    }
                    
                    if( isset($_POST["enhavo"])  ){
                        $pagxo["enhavo"] = $_POST["enhavo"];    
                    }
                    
                    try{                        
                        $pagxo->konservu();
                         mesagxu("Sukcese konservita","sukceso");
                    }catch(PagxoException $e){
                        mesagxu("Okazis eraro, samnoma paĝo jam verŝajne ekzistas","eraro");
                    }
                   
                    $dataro = Array("nomo"=>$pagxo["nomo"], 
                                    "loko" => "/".PREFIX_LIGILOJ.$pagxo["vojo"],
                                    "enhavo"=>$pagxo["enhavo"],
                                    "id"=>$pagxo["id"],
                                    "sxangxita" => ($pagxo["sxangxita"]?date("H:i:s d.m.Y",$pagxo["sxangxita"]):"Nova"));
                    
                    break;

            case "forvisxi":
                    try{
                        $pagxo->forvisxu();
                        $dataro["loko"] = "/".PREFIX_LIGILOJ;
                        mesagxu("Sukcese forviŝita","sukceso");
                    }catch(PagxoException $e){
                        mesagxu($e->getMessage(),"eraro");    
                    }
                    
            
            
                    break;

            case "redakti":
                    $dataro["enhavo"] = $pagxo->redaktilo(); 
                    break;
                    
            case "idoj":
                    $idoj = "";
                    foreach( $pagxo->akiruIdojn() as $vojo => $nomo){
                            $idoj .= "<li><a href='/".PREFIX_LIGILOJ."$vojo'>$nomo</a></li>";
                    }

                                
                    $dataro["idoj"] = empty($idoj)?"Neniaj idoj":"<ul>".$idoj."</ul>";
                        
                    break;   
                                       
            default:
                    mesagxu("Nekonata ago '{$_POST["ago"]}'", 'eraro');
                    break;                             
            
        }
        
        $dataro["mesagxoj"] = mesagxoj();
        $dataro["debug"] = kreuDebugTablon($DB_DEBUG) ;
        echo json_encode($dataro); 
        exit;    

        
    }
    
      
    $enhavo = "<h1>&raquo;<span id='nomo'>{$pagxo["nomo"]}</span></h1>";
    $enhavo .= "<div id='enhavo'>{$pagxo["enhavo"]}<br/></div>";
    $enhavo .= "<div id='lasta_sxangxo'>".($pagxo["sxangxita"]?date("H:i:s d.m.Y",$pagxo["sxangxita"]):"Nova")."<br/></div>";
    $enhavo .= "<input type='hidden' id='pagxo_id' value='{$pagxo["id"]}' />";
    $enhavo .= "<div id='idoj'><a href='#' onclick='akiruIdojn(); return false;'>idoj&hellip;</a></div>";

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
<noscript><div class="msg eraro">Por uzado de tiuĉi aplikacio oni bezonas ŝaltitan JavaScript</div></noscript>
<!-- <div class="eta" style="float: right;"><?= $_SERVER["REMOTE_ADDR"] ?></div> -->
<div>
    <div id="menuo">
        <?
        foreach($menuaro as $ero ){
            list($nomo, $ligilo, $bildo, $skripto) = $ero;
            echo "<a ".($ligilo?"href='/".PREFIX_LIGILOJ.$ligilo."' ":"").($skripto?"onclick='$skripto'":"").">".($bildo?"<img src='/bild/$bildo.png' alt='$nomo' />":$nomo)."</a>\n";    
        }
        ?>
        <img id='sxargilo' src="/bild/ajax-sxargilo.gif" />
    </div>
</div>

<div id="mesagxoj"><?= mesagxoj() ?></div>
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


