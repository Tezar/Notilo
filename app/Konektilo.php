<?
include WEB_DIR."/app/NotORM.php";

try{
    $konekto = new PDO("sqlite2:".DB_DOSIERO);
    $konekto->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $konekto->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    
    $strukturo = new NotORM_Structure_Convention(
        $primary = "id", // id
        $foreign = "%s_id", // id_$tablo
        $table = "%sj" // {$tablo}j
    );
    
    $DB = new NotORM($konekto,$strukturo);
    $DB->debug = "notormdebug";
    
}catch(PDOException $e){
    mesagxu("Eraro dum konektado al datumbazo ({$e->getMessage()})","eraro");
}


 