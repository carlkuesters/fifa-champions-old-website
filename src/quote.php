<?php
	require_once("core.php");

	if (getURLParameter("random")) {
		$id = $db->getValue("tournaments_meta", "id", array("type"=>"quote"), "RAND()", 1);
		linkTo("quote.php?id=" . $id);
	}
	$id = getURLParameter("id");
	$quote = $db->getRow("tournaments_meta", "*", array("id"=>$id));

	head("Spruch", 4);

	echo '
		<a id="random_result_button" href="quote.php?random=1" class="button button-style1">Zufälliger Spruch</a>
		<h1 id="page_title">Spruch' . ($quote->playerid ? ' von ' . escapeHTML($members[$quote->playerid]->name) : '') . '</h1>
		<h2>';
	if ($quote->playerid) {
		echo '<img src="' . getMemberIcon($members[$quote->playerid]->id) . '" class="member_icon member_icon_small"/>';
	}
	$tournament = $db->getRow("tournaments", "*", array("id"=>$quote->tournamentid));
	echo $quote->text . '</h2>
		<h3>(Beim Turnier am ' . strftime("%d. %B %Y", $tournament->date) . ')</h3>
	';

	footer();
?>