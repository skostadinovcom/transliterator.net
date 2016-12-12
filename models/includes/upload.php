<?php
include("../../core/config.php");
$cookie_hash = hash('ripemd160', rand(1111,9999));
$target_dir = "../../public/uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);

$old_name = basename( $_FILES["fileToUpload"]["name"]);
$new_name = $cookie_hash . ".txt";
rename($target_dir . $old_name, $target_dir . $new_name);
header("Location: ".$site_url."p/load/".$cookie_hash."/");
?>