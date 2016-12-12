<?php
$load_file = $_GET['w'];
$tpl->a_var(array(
    "PAGE_TITLE" => "",
    "LOAD_FILE" => $load_file,
));

$tpl->a_block("LOAD", array());

if (isset($_POST['fileToDownload'])){
    $content = $_POST['outputPhp'];

    $thefile = fopen("public/uploads/".$load_file.".txt", "w") or die();
    fwrite($thefile, $content);
    fclose($thefile);
    header("Location: ".$site_url."p/download/".$load_file."/");
}