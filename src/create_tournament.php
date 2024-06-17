<?php
	require_once("core.php");
	head("Turnier erstellen", 1);
	
	$playersCount = getURLParameter("players");
	
	echo '
		<h1 id="page_title">Turnier erstellen</h1>
		<form id="create_tournament_form" action="edit.php?action=createTournament" method="post">
			<input type="hidden" name="playersCount" value="' . $playersCount . '"/>
			<ul id="tournaments_list" class="style2">
				<li>
					<span class="tournaments_list_label">Typ</span>
					<select name="type">
	';
	foreach ($tournamentTypes as $type=>$tournamentInfo) {
		echo '
						<option value="' . $type . '">' . $tournamentInfo["title"] . '</option>
		';
	}
	echo '
					</select>
					<select name="groupSize">
						<option value="-1">Jeder gegen Jeden</option>
	';
	for ($i = 2; $i < 9; $i++) {
		echo '
						<option value="' . $i . '">' . $i . 'er Gruppen</option>
		';
	}				
	echo '
						<option value="ko">K.O.-Turnier</option>
					</select>
					<input type="checkbox" name="returnMatches"/><span class="checkbox_label">Hin- & Rückrunde</span>
				</li>
				<li>
					<span class="tournaments_list_label">Spieler</span>
					<ul class="tournaments_list_players">
	';
	for ($i = 0; $i < $playersCount; $i++) {
		echo '
			<li>
				<select name="player_' . $i . '">
		';
		$r = 0;
		foreach ($members as $member) {
			echo '<option value="' . $member->id . '"' . (($i == $r) ? ' selected="selected"' : '') . ' style="background-image:url(' . getMemberIcon($member->id) . ');">' . escapeHTML($member->name) . '</option>';
			$r++;
		}
		echo '
				</select>
				<select name="team_' . $i . '">
		';
		foreach ($teams as $team) {
			echo '<option value="' . $team->id . '" style="background-image:url(./images/teams/' . $team->id . '_32.png);"' . (($team->id == 47) ? ' selected="selected"' : '') . '>' . escapeHTML($team->name) . '</option>';
		}
		echo '
				</select>
		';
	}
	echo '
					</ul>
				</li>
				<li style="padding-bottom:0px;"></li>
			</ul>
			<!--<a href="#" class="button button-style1" style="margin:15px 5px 0px 0px;" onclick="return false;">Mannschaften verwalten</a>-->
			<a href="javascript:submitForm(\'create_tournament_form\')" class="button button-style1">Erstellen</a>
		</form>
	';
	
	footer();
?>