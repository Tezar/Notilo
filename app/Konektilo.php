<?
include WEB_DIR."/app/NotORM.php";

$connection = new PDO("sqlite2:".WEB_DIR.'\testdb');
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);



$structure = new NotORM_Structure_Convention(
    $primary = "id", // id_$table
    $foreign = "%s_id", // id_$table
    $table = "%sj" // {$table}s
);

$DB = new NotORM($connection,$structure);
$DB->debug = "notormdebug";



$DB_DEBUG = Array();

function notormdebug($query, $parameters){
    global $DB_DEBUG;
    $DB_DEBUG[] = Array($query,$parameters);
    return True;
}
 