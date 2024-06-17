<?php
	require_once("core.php");
	head("Turniere", 2);

	function getTournamentInfoBox($type) {
		global $tournamentTypes;
		$html = '
			<div class="tournament_info_box">
		';
		if ($type != "fun_tournament") {
			$html .= '
				<img src="./images/tournaments/' . $type . '_300.png" class="tournament_info_box_image"/><br/>
			';
		}
		$html .= '
				<span class="tournament_info_box_description">' . $tournamentTypes[$type]["description"] . '</span>
			</div>
		';
		return $html;
	}

	$type = getURLParameter("type");
	$tournaments = $db->getRows("tournaments", "*", ($type ? array("type"=>$type) : false), "date DESC");
	echo '
		<div id="tournaments_content">
			<div id="tournament_info_sidebar">
	';
	if ($type) {
		echo getTournamentInfoBox($type);
	} else {
		foreach ($tournamentTypes as $tournamentType=>$tournamentInfo) {
			echo getTournamentInfoBox($tournamentType);
		}
	}
	echo '
			</div>
			<h1 id="page_title">' . ($type?$tournamentTypes[$type]["title"]:'Alle Turniere') . ' (' . count($tournaments) . ')</h1>
			<ul id="tournaments_list" class="style2">
	';
	foreach ($tournaments as $i=>$tournament) {
		echo '
			<li>
				<span class="tournaments_list_label">' . strftime((in_array($tournament->type, $cupTournamentTypes)?"%Y":"%Y, %B"), $tournament->date) . '</span>
				<a href="tournament.php?id=' . $tournament->id . '">' . escapeHTML($tournamentTypes[$tournament->type]["title"]) . '</a>
			</li>
		';
	}
	echo '
				<li style="padding-bottom:0px;"></li>
			</ul>
	';
	if ($session->user) {
		echo '
			<a id="create_tournament_button" href="#" class="button button-style1" style="margin-top:15px;" onclick="
				var playersCount = prompt(\'Spieleranzahl eingeben:\', \'8\');
				if (playersCount) {
					window.location = \'create_tournament.php?players=\' + playersCount;
				} else {
					return false;
				}
			">Turnier erstellen</a>
		';
	}

	echo '
		</div>
	';

	footer();
?>