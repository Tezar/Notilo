<?php

class Pagxo implements arrayaccess{
        
    const NEEKZISTAS = 0;
    const DUPLIKA = 1;
    const MANKAS_PATRO= 2;
    
    private $rikordo = Array();
    private $gxisdatigaro = Array();
    
    static $enFiltroj = Array();
    static $redaktFiltroj = Array();
    static $elFiltroj = Array();
    
    /**
    * Pagxo::__construct()
    * kreas pagxon baze de rikordo, id, vojo aux kreas malplenan
    * @param mixed $rikordo
    * @return
    */
    public function __construct($parametro = False){
        if(is_array($parametro)){
            $this->rikordo = $parametro;
        }elseif( is_numeric($parametro)){
             $this->akiruPerId($parametro);
        }elseif(!empty($parametro)){
            $this->akiruPerVojo($parametro);
        }
        //else malplena pagxo.... 
    }
    
    /**
     * Pagxo::akiruPerVojo()
     * 
     * @param mixed $vojo
     * @return
     */    
     function akiruPerVojo($vojo){
        global $DB;
        $pagxo = $DB->pagxoj("vojo = ?",$vojo)->limit(1)->fetch();
        
        if(empty($pagxo)){
            throw new PagxoException("Neekzistas ($vojo)", self::NEEKZISTAS);
        }

        $this->rikordo = $pagxo;
    }

    /**
     * Pagxo::akiruPerId()
     * 
     * @param mixed $id
     * @return
     */    
     function akiruPerId($id){
        global $DB;
        $this->rikordo = $DB->pagxoj[$id];
    }    
    
/**
     * Pagxo::konservu()
     * konservas sxangxojn, se
     * 
     * @param mixed $datoj
     * @return
     */
    public function konservu($datoj = Array() ){
        global $DB, $DB_DEBUG;
        
        $gxisdatigaro = array_merge($this->gxisdatigaro,$datoj);

        $DB_DEBUG[] = Array("konservu", $this->gxisdatigaro);
        
        if(empty($gxisdatigaro)){  //povas okazi ke estas nenio por gxisdatigi  
            return;                //ekzample kiam oni provas agordi saman valoron kia estas en db
        }
        
        if(isset($gxisdatigaro['patro'])){  //permesu konservi al patron krom id ankaux Pagxon kaj vojon
            if( $gxisdatigaro['patro'] instanceof Pagxo ){
                $gxisdatigaro['patro'] = $gxisdatigaro['patro']["id"];  
            }
            elseif( ! is_numeric($gxisdatigaro['patro']) ){ //traduku vojon al id
                $patro = $DB->pagxoj("vojo = ?",$gxisdatigaro['patro'])->select("id")->limit(1)->fetch();
                if(empty($patro)) throw PagxoException("Mankas patro ({$gxisdatigaro['patro']})", MANKAS_PATRO);
                $gxisdatigaro['patro'] = $patro["id"];
            }
        }
        
        
        //cxiam gxisdatigxu tempon
        $gxisdatigaro['sxangxita'] = time();
        

        
        //cxu krei aux gxisdatigi?
        if( isset($this->rikordo['id']) ){ //<----------------- GXISDATIGO
             
             
             //todo: unue ŝanĝi nomon, kaj poste provi ŝanĝi idojn!
            if(isset($gxisdatigaro["nomo"]) ){
                if(empty($gxisdatigaro["nomo"])){ //se ni gxisdtigas nomon de pagxo,gxi devas esti plenigita
                    throw new PagxoException("Pagxo devas havi nomon");
                }else{ //anstatuxigu novan nomon en la vojo
                    $vojo = explode("/",$this->rikordo["vojo"]);
                    array_pop($vojo);
                    $vojo[] = SEOigu($gxisdatigaro["nomo"]);
                    $vojo = implode("/",$vojo);
                    
                    $gxisdatigaro["vojo"] = $vojo;
                    
                    self::gxisdatiguVojojn($this->rikordo["id"], $vojo);
                }
            }
            
            try{
                 //update redona            
                $this->rikordo->update($gxisdatigaro);

                foreach($gxisdatigaro as $nomo => $valoro){
                    $this->rikordo[$nomo] = $valoro;
                }

            }catch(PDOException $e){
                if($e->getCode() != "HY000") throw $e;
                
                //forĵetu novan nomon
                unset($this->gxisdatigaro["nomo"]);
                
                //versxajne duplika nomo
                throw new PagxoException("Eraro! Versxajne duplika rikordo ({$e->getMessage()})",self::DUPLIKA);
            }                
            
            
            

            
        }else{ //<--------------------------------------------- NOVA
            //se ni kreas novan pagxon gxi devas havi nomon
            if(empty($gxisdatigaro["nomo"])){
                throw new PagxoException("Pagxo devas havi nomon");    
            }
            
            //akiro de propra vojo
            if( $gxisdatigaro["patro"] ){
                $gxisdatigaro["vojo"] = $DB->pagxoj[  $gxisdatigaro["patro"] ]["vojo"]."/".SEOigu($gxisdatigaro["nomo"]);
            }else{
                $gxisdatigaro["vojo"] = SEOigu($gxisdatigaro["nomo"]);
            }
            try{
                $this->rikordo = $DB->pagxoj()->insert($gxisdatigaro);
            }
            catch(PDOException $e){
                if($e->getCode() != "HY000") throw $e;
                //versxajne duplika nomo
                throw new PagxoException("Eraro! Versxajne duplika rikordo ({$e->getMessage()})",self::DUPLIKA);
                
            }                
        }
        
        $this->gxisdatigaro = Array();
    }
    
    
        //todo: popis
    public function akiruIdojn(){
        global $DB;
        
        if(!isset($this["id"])){ //se ni ne ekzitas ni na povas havi idojn
            return Array();
        }
        
        return $DB->pagxoj("patro = ?", $this["id"])->fetchPairs("vojo", "nomo");
    }
    
    public function redaktilo(){
        $teksto = $this->rikordo["enhavo"];
        
        foreach(self::$redaktFiltroj as $filtro){
            $teksto = call_user_func($filtro,$teksto);             
        }
        
        return $teksto; 
    }
    
    
    public function forvisxu(){
        if(!isset($this["id"])){ //se ni ne ekzitas ni na povas havi idojn
            return true;
        }        
        
        $idoj = $this->akiruIdojn();
        
        if($kiom = count($idoj) > 0){
            throw new PagxoException("Ne eblas forviŝi paĝon - ĝi havas idojn ($kiom)");
        }
        
        
        $this->rikordo->delete();
                
    }
    
    
    /**
     * Pagxo::gxisdatiguVojojn()
     * 
     * @param integer $id
     * @param strig vojo
     * @return
     */
    static function gxisdatiguVojojn($patro = 0, $vojoPatro = ""){
        global $DB;

        $vojoPatro .= "/";

        //rikure gxisdatigu cxiujn idajn pagxojn
        foreach($DB->pagxoj("patro = ?",$patro)->select("id,nomo") as $pagxo ){
            $vojo = $vojoPatro.SEOigu($pagxo["nomo"]) ;
            $pagxo->update( Array("vojo" =>$vojo ) );
            self::gxisdatiguVojojn($pagxo["id"],$vojo);
        }
    }    
    
    
    /**
     * Pagxo::aldonuElFiltron()
     * aldonas enigan filtron 
     * @param callback $filtro
     * @return void
     */
    static function aldonuElFiltron($filtro){
        if( !is_callable($filtro) )
            throw new PagxoException("Filtro devas esti alvokebla.");
        self::$elFiltroj[] = $filtro;
    }
    
    /**
     * Pagxo::aldonuRedaktFiltron()
     * aldonas enigan filtron 
     * @param callback $filtro
     * @return void
     */
    static function aldonuRedaktFiltron($filtro){
        if( !is_callable($filtro) )
            throw new PagxoException("Filtro devas esti alvokebla.");
        self::$redaktFiltroj[] = $filtro;
    }
    
    
    /**
     * Pagxo::aldonuEnFiltron()
     * aldonas enigan filtron 
     * @param callback $filtro
     * @return void
     */
    static function aldonuEnFiltron($filtro){
        if( !is_callable($filtro) )
            throw new PagxoException("Filtro devas esti alvokebla.");
        self::$enFiltroj[] = $filtro;
    }    
    
        


    /**
     * Pagxo::akiruCxiujn()
     * redonas cxiujn idojn de patro
     * @param integer $patro
     * @return
     */
    static public function akiruCxiujn($patro = Null){
        global $DB;
        //todo: akiri nur per unu demando
        $redono = Array();
        foreach( $DB->pagxoj(Array("patro"=>$patro))->select("vojo, id, nomo")->order("vojo") as $rikordo){
            $redono[] = Array($rikordo["vojo"],$rikordo["nomo"],self::akiruCxiujn((int) $rikordo["id"])); 
        }
        return $redono;   
    }
    
    
    

    
    
    
    
         /**
     * Pagxo::offsetSet()
     * 
     * @param mixed $desxovo
     * @param mixed $valoro
     * @return
     */
    public function offsetSet($desxovo, $valoro) {
        global $DB_DEBUG;
        if($valoro != $this->rikordo[$desxovo]){
            $DB_DEBUG[] = Array("konservu $desxovo", $valoro);
            $this->gxisdatigaro[$desxovo] = $valoro;
        }
    }
    
    /**
     * Pagxo::offsetExists()
     * 
     * @param mixed $desxovo
     * @return
     */
    public function offsetExists($desxovo) {
        return isset($this->rikordo[$desxovo]) || $this->gxisdatigaro[$desxovo];
    }
    
    /**
     * Pagxo::offsetUnset()
     * 
     * @param mixed $offset
     * @return
     */
    public function offsetUnset($offset) {
        throw new DBException("Nesubtenita");
    }
    
    /**
     * Pagxo::offsetGet()
     * 
     * @param mixed $desxovo
     * @return
     */
    public function offsetGet($desxovo) {
        if( isset($this->gxisdatigaro[$desxovo]) ){
            $redono = $this->gxisdatigaro[$desxovo];
        }else{
            $redono = $this->rikordo[$desxovo];
        }            
        
        if($desxovo === "enhavo"){
            foreach(self::$elFiltroj as $filtro){
                $redono = call_user_func($filtro,$redono);             
            }
        }
        
        return $redono;
        
    }
    
}


class PagxoException extends Exception{}
