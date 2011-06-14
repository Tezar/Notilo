<?
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <Tezar@solajpafistoj.net> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return
 * ----------------------------------------------------------------------------
 */

@mkdir("mini",0777);

if(! is_writable("mini") ){
    die("Dosierujo 'mini'neestas skribebla!");
}


/**
 * Minimunigilo
 * 
 * bazita sur ideoj kaj kelkaj kodoj de Jakub Vrana (http://www.adminer.org/, http://www.vrana.cz/)
 * kaj http://latrine.dgx.cz/jak-zredukovat-php-skripty
 * 
 * 
 * @package Redaktema
 * @author Ales Tomecek
 * @copyright 2011
 * @version $Id$
 * @access public
 */
class Minimunigilo{
    const TYPO = 0, ENHAVO=1, LINIO = 2;

    
    public $difinoj=array();

    /* inkluzivaj dosieroj */
    private $inkluzivaj = Array();
    /* spuro pro trakti eblajn erarojn */
    private $spuro = Array();
    /* kiajn dosierojn minimunigili */
    private $dosieroj=array();
    
    /* cxu konfuzi */
    public $konfuzi = true;
    
    /* kiajn variablojn nekonfuzigi */
    public $nekonfuzendaj = Array();
    
    
    public function __construct($dosieroj){
        if(!is_array($dosieroj)){
            $dosieroj = array($dosieroj);
        }
        
        $this->dosieroj = $dosieroj;
    }
    
    
    /**
     * Minimunigilo::minimunigu()
     * redonas minimunigitan tekston
     * 
     * @return string
     */
    public function minimunigu(){
        $redono = "";
        foreach($this->dosieroj as $dosiero){
            $this->spuro[] = $dosiero;
            $redono .= $this->unuapasxo( file_get_contents($dosiero) );
            //$redono .= file_get_contents($dosiero) ;
            $this->spuro= Array();
        }
        
        
        
        return $this->duaPasxo($redono);
    }
    
    /**
     * Minimunigilo::etaVariablo()
     * 
     * bazita de Adminer kodo  
     * 
     * @param mixed $signoj
     * @return
     */
    function etaVariablo($signoj=Null) {
        static $numero = 0;
        
        $lokaNumero = $numero;
        
        if($signoj===Null) $signoj = implode(range('a', 'z')) . '_' . implode(range('A', 'Z'));
        
    	$redono = '';
    	while ($lokaNumero >= 0) {
    		$redono .= $signoj[$numero % strlen($signoj)];
    		$lokaNumero = floor($lokaNumero / strlen($signoj)) - 1;
    	}
        
        $numero++;
    	return $redono;
    }

    
    
    /**
     * Minimunigilo::konsumuGxis()
     * konsumas enigon gxis gxia fino aux gxis $gxis inkluzive
     * 
     * @param array $kion
     * @param mixed $gxis array aux string
     * @return array konsumitaj eroj
     */
    private function konsumuGxis($kion, $gxis){
        if(! is_array($gxis)){
            $gxis = array($gxis);
        }
        
        $konsumita = Array();
        
        $porFermi = 0;
        
        $i=0;
        while( list(,$jxetono) = each($kion) ){
            if( $jxetono[ENHAVO] == "(") $porFermi++;
            if( $jxetono[ENHAVO] == ")" && $porFermi>0) $porFermi--;

            $konsumita [] = $jxetono;
            
            if( $porFermi==0 && ($jxetono[TYPO] === $gxis || in_array($jxetono[ENHAVO],$gxis))  ) break;
        }
        
        return $konsumita;
    }
    
    
    /**
     * Minimunigilo::statikaTaksu()
     * provas taksi enigon, konas bazajn php funkciojn, jam taksitajn difinojn kaj __FILE__
     * 
     * @param array $jxetonoj
     * @return mixed string aux Null se neeblas taksi
     */
    public function statikaTaksu($jxetonoj){
        $taksita = "";
        for(; list(,$jxetono) = each($jxetonoj);){
        
             if( !is_array($jxetono) ){
                $jxetono = array(0,$jxetono,0);
            }

//            echo "taksu-".htmlspecialchars(token_name((int)$jxetono[self::TYPO] )." - ".$jxetono[self::ENHAVO])."\n"; 

            switch($jxetono[self::TYPO]){
                case T_WHITESPACE:
                    break;
                case T_FILE:
                    //php konstanto
                    $taksita .= realpath(end($this->spuro));
                    break;                 
                case T_STRING:
                    //funkcio
                    if( function_exists($jxetono[self::ENHAVO]) ){
                        $par = $this->akiruParametrojn(&$jxetonoj);
                        if($par === Null) return Null;
                        $taksita .= call_user_func_array($jxetono[self::ENHAVO], $par);
                        break;
                    }
                
                    //alia konstanto
                    if( isset($this->difinoj[$jxetono[self::ENHAVO]])){
                        $taksita .= $this->difinoj[ $jxetono[self::ENHAVO] ];
                        break;
                    }
                    return Null;


                case T_VARIABLE:                    
                    //alia teksto, kiun ni nepovas taksi
                    return Null;
                    
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                    $taksita .= trim($jxetono[self::ENHAVO],"\"'");                                
                    break;
                                    
                case ".": //kungluigo de du cxenoj
                    continue;
                case ",": //fino de taksado
                case ";": //fino de taksado
                    break 2;                                        
                default:
                    $taksita .= $jxetono[self::ENHAVO];
            }
        }
        
        
        return $taksita;    
    }


    /**
     * Minimunigilo::akiruParametrojn()
     * konsumas enigon kaj faras de gxi parametrojn, kiujn oni provas taksi, se neeblas redonas Null
     * 
     * @param array $jxetonoj
     * @return mixed Null se nesukcesis aux array kun parametroj
     */
    public function akiruParametrojn($jxetonoj){
        //transaltu spacojn
        while( (list(,$j) = each($jxetonoj)) && is_array($j) && $j[self::TYPO]==T_WHITESPACE);
        
        
        if($j != "(" ) $this->eraro(0,"Atendita '(' ricevita '".(is_array($j)?htmlspecialchars(token_name((int)$j[self::TYPO] )." - ".$j[self::ENHAVO]):$j)."'");
        
        $parametroj = Array();
        while(true){
            $konsumita = $this->konsumuGxis(&$jxetonoj, array(",",")") );
            
            
            $parametroj[] = $a = $this->statikaTaksu(&$konsumita);
            
            if($a === Null) return Null; 

            if(end($konsumita) == ")") break;
            if($i>100) die("eraro");
        }
        
        return $parametroj;
    }
    
    /**
     * Minimunigilo::unuapasxo()
     * unua pasxo - minimunigas enigon, analizas difinojn, provas inkluzi dosierojn
     * 
     * @param string $enigo
     * @return string
     */
    private function unuaPasxo($enigo){
        $redono = "";
        $spacu = false;
        
        
        $nespacendaj = array_flip(str_split('!"#$&\'()*+,-./:;<=>?@[\]^`{|}'));
        
        
        $jxetonoj = token_get_all($enigo);
        for (reset($jxetonoj); list($i, $jxetono) = each($jxetonoj); ){
            //unuigo de tokenoj
            if( !is_array($jxetono) ){
                $jxetono = array($jxetono,$jxetono,0);
            }
            
            list($typo,$enhavo,$linio ) = $jxetono;
       
            
            switch($typo){
                case T_OPEN_TAG:
                    //ni cxiam uzas longan malfermon
                    $enhavo = "<?php ";
                    $spacu = true;
                    break;
                
                
                case T_INCLUDE:
                case T_REQUIRE:
                    echo "requerie"."$linio\n";
                    $dosiero = $this->statikaTaksu($this->konsumuGxis($jxetonoj,";") ); //spite & cxar ni nevolas ke require/include restu
                    if($dosiero===Null){
                        //todo: rimarki potencialan problemon
                        //$this->[]$linio,"Oni nepovis iklizi dosieron kun variabla nomo");
                        break;
                    }
                    $enhavo = ""; //forvisxu include
                    $this->konsumuGxis(&$jxetonoj,";");
                    echo "bezonas ".$dosiero;                    
                    $this->inkluzivaj[]=$dosiero;
                    array_push($this->spuro,$dosiero);
                    $redono .= "?>";
                    $redono .= "<? /* $dosiero */ ?>";
                    $redono .= $this->unuapasxo( file_get_contents($dosiero) );
                    array_pop($this->spuro);
                    
                    break;

                //todo: include:once
                
                case T_COMMENT:
                case T_ML_COMMENT:
                case T_DOC_COMMENT:
                case T_WHITESPACE:
                    $spacu = true; 
                    continue 2;
                    
                case T_STRING:
                    if($enhavo == "define"){
                        list($nomo, $difino) = $this->akiruParametrojn($jxetonoj);

                        if( !isset($this->difinoj[$nomo])){
                            $this->difinoj[$nomo] = $difino;
                        }else{
                            print_r($d->difinoj);
                            $this->eraro($jxetono[self::LINIO],"Redifinita konstanto '{$nomo}'");
                            
                        }
                        
                    }
                    break;
            }
            
            if( isset($nespacendaj[substr($redono,-1)]) ||  //se redono finas per nespacenda signo 
                isset($nespacendaj[$enhavo[0]])             //aux se nova aldonajxo komencas pre gxi
                    ) $spacu= false;                        //ni ne devas fari gxin
            
            $redono .=   ($spacu?"\n":'').$enhavo;          //novaj linioj estas same grandaj kiel spacoj kaj pli bonaj dum malfermado ktp..
            $spacu= false; //suficxas nur unu spaco
            
            
            
            
            
        }
        return $redono;
    }
  
  
  
    public function duaPasxo($enigo){
        $redono = "";
        $transsalto = 0; //kioam da jxetonoj transsalti
        
        $nekonfuzi = array_flip( array_merge(array('$this', '$GLOBALS', '$_GET', '$_POST', '$_FILES', '$_COOKIE', '$_SESSION', '$_SERVER'),$this->nekonfuzendaj) );
        $konfuzaro =Array();


        $amplekso = array(array("pinto",0)); //kie ni laboras
        $plua = "";  

        $jxetonoj = token_get_all($enigo);
        
        for (reset($jxetonoj); list($i, $jxetono) = each($jxetonoj); ){
            if($transsalto){ $transsalto--; continue; }
            
            //unuigo de tokenoj
            if( !is_array($jxetono) ){
                $jxetono = array($jxetono,$jxetono,0);
            }
            
            list($typo,$enhavo,$linio ) = $jxetono;
            
            //forjxetu ? >< php (kreita dum kungluado de dosieroj)
            if( $typo === T_CLOSE_TAG && $jxetonoj[$i+1][self::TYPO] === T_OPEN_TAG ){
                $transsalto = 1;
                continue;
            }


            //ni spuras cxu ni estas en klaso aux funkcio, se ni estas en klaso ni nesxangxas variablojn
            if( $typo === T_CLASS){
                $amplekso[] = Array("class",-1,$jxetonoj[$i+2][self::ENHAVO]);
            }elseif( $typo === T_FUNCTION){
                $amplekso[] = Array("function",-1,$jxetonoj[$i+2][self::ENHAVO]);
            }
            
            list($kie,$profundeco) = end($amplekso);
            
            if( $enhavo === '{' ){
                $ero = array_pop($amplekso);
                $ero[1]++;
                array_push($amplekso,$ero);
                
            }else if( $enhavo === '}' ){
                $ero = array_pop($amplekso);
                $ero[1]--;
                if($ero[1] >= 0) array_push($amplekso,$ero);
            }else if( $enhavo === ';' && $kie==="function" &&  $profundeco===(-1) ){ //prototypoj de funkcioj
                array_pop($amplekso);
            }
            
            list($kie,$profundeco) = end($amplekso);


            //echo htmlspecialchars( token_name((int)$typo)."-".$enhavo)."<br/>";
            //echo "$kie - $profundeco \n";
            
            
            // se estas variablo kaj oni permesas gxin konfuzi
            if($this->konfuzi && $kie != "class" && $typo === T_VARIABLE && !isset($nekonfuzi[$enhavo]) ){
                if(! isset($konfuzaro[$enhavo])){ //spektita unuan tempon
                    $konfuzaro[$enhavo] = '$'.$this->etaVariablo();                                        
                }
                $enhavo = $konfuzaro[$enhavo];
            }
            
            $redono .= $enhavo;
            
            
            
        }
        
        print_r($amplekso);
        return $redono;
        
        
    }
    
    /**
     * Minimunigilo::eraro()
     * jxetas eraron
     * @param int $linio
     * @param string $kialo
     * @return void
     */
    public function eraro($linio, $kialo){
        throw new Exception("$kialo (".array_pop($this->spuro).":{$linio})");
    }
    
}



$d = new Minimunigilo("index.php");
echo "<pre>";
file_put_contents("mini/test.php",$d->minimunigu());

