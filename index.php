<?php
require_once("core/engine.php");
require_once("core/config.php");
require_once("core/functions.php");

@$page = $_GET['page'];
if (strlen($page) > 0) {
	if (include("models/".$page.".php")) {
		$tpl->tpl_file(array(
			"tpl-".@page."" => "tpl-".$page.".html",
		));
		$tpl->parse("tpl-".@page."");
	}else{
		header("Location: ".$site_url."");
	}
} else {
    include("models/index.php");
	$tpl->parse("tpl-index");
}