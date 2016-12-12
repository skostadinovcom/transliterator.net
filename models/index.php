<?php
$tpl->a_var(array(
    "PAGE_TITLE" => "",
));

if (isset($_POST['fileToDownload'])){
    $content = $_POST['outputPhp'];
    $cookie_hash = hash('ripemd160', rand(1111,9999));
    $thefile = fopen("public/uploads/".$cookie_hash.".txt", "w") or die();
    fwrite($thefile, $content);
    fclose($thefile);
    header("Location: ".$site_url."p/download/".$cookie_hash."/");
}