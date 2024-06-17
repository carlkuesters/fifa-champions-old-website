<?php
	require_once("core.php");
	head("Ranglisten", 3);

	$rankings = $db->getRows("rankings", "*", false, "date DESC");
	echo '
		<a href="rankings_diagram.php" class="button button-style1" style="float:right;">Diagramm</a>
		<h1 id="page_title">Ranglisten</h1>
		<ul id="tournaments_list" class="style2">
	';
	foreach ($rankings as $i=>$ranking) {
		echo '
			<li>
				<span class="tournaments_list_label">' . strftime("%d.%m.%Y", $ranking->date) . '</span>
				<a href="ranking.php?id=' . $ranking->id . '">';
		$topRankingPlayerRows = $db->getRows("rankings_players", array("playerid","rank"), array("rankingid"=>$ranking->id), "`rank`", 5);
		foreach ($topRankingPlayerRows as $i=>$topRankingPlayerRow) {
			echo ($topRankingPlayerRow->rank) . '. ' . escapeHTML($members[$topRankingPlayerRow->playerid]->name) . ', ';
		}
		echo '...</a>
			</li>
		';
	}
	echo '
			<li style="padding-bottom:0px;"></li>
		</ul>
	';
	if ($session->user) {
		echo '<a href="create_ranking.php" class="button button-style1" style="margin-top:15px;">Neue Weltrangliste</a>';
	}

	footer();
?>