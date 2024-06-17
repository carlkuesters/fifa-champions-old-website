<?php
	$isGeneralAccessSite = true;
	require_once("core.php");
	head("Fifa-Champions");

	$title = getURLParameter("title");
	$description = getURLParameter("description");

	echo '
		<h1 style="font-size:3em; padding-bottom:15px;">' . escapeHTML($title) . '</h1>
		<h2 style="font-size:2em; padding-bottom:23px;">' . escapeHTML($description) . '</h2>
	';

	footer();
?>