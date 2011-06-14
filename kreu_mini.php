<?
require "minimumigi/minimumigi.php";

@mkdir("mini",0777);

if(! is_writable("mini") ){
    die("Dosierujo 'mini'neestas skribebla!");
}

$d = new Minimunigilo("index.php");
echo "<pre>";
file_put_contents("mini/index.php",$d->minimunigu());

