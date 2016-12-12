<?php
	date_default_timezone_set("Europe/Sofia");

	$tpl = new template("views");

	$tpl->tpl_file(array(
		"overview_head" => "overview_head.html",
		"overview_footer" => "overview_footer.html",
		"tpl-index" => "tpl-index.html",
	));

	$tpl->a_var(array(
		"TIME" => date("d.m.Y - H:i", time()),
        "SITE_URL" => $site_url,
        "PUBLIC_URL" => $site_url . "public",
	));
?>