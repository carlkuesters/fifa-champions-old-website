<?php
	require_once("core.php");

	$allowedTournamentTypes = array("monthly_tournament", "european_championship", "world_championship", "dfc_cup");

	$recordType = getURLParameter("type", "winrate");
	$tournamentType = getURLParameter("tournamentType");
	$time = getURLParameter("time");
	$sort = getURLParameter("sort", true);

	if ($tournamentType && !in_array($tournamentType, $allowedTournamentTypes)) {
		linkToMessage("Unerlaubter Turnier-Typ");
	}
	$tournamentRestriction = ($tournamentType?"tournaments.type = '" . $tournamentType . "'":"tournaments.type <> 'team_tournament'");
	if ($recordType == "member_since") {
		$sort = !$sort;
	}
	$orderBy = ($sort ? "DESC" : "ASC");
	switch ($recordType) {
		case "winrate":
			$records = $db->sendQuery("
				SELECT memberid, (SUM(wins) / SUM(matches)) AS value FROM(
					(SELECT playerid1 AS memberid, SUM(goals1 > goals2) AS wins, COUNT(*) AS matches FROM tournaments_matches
					JOIN tournaments ON tournaments_matches.tournamentid = tournaments.id
					WHERE (" . $tournamentRestriction . ")
					  AND (tournaments_matches.playerid1 <> 0)
					  AND (tournaments_matches.playerid2 <> 0)
					  AND (tournaments_matches.goals1 <> -1)
					  AND (tournaments_matches.goals2 <> -1)
					GROUP BY memberid)
					UNION ALL
					(SELECT playerid2 AS memberid, SUM(goals2 > goals1) AS winrate, COUNT(*) AS matches FROM tournaments_matches
					JOIN tournaments ON tournaments_matches.tournamentid = tournaments.id
					WHERE (" . $tournamentRestriction . ")
					  AND (tournaments_matches.playerid1 <> 0)
					  AND (tournaments_matches.playerid2 <> 0)
					  AND (tournaments_matches.goals1 <> -1)
					  AND (tournaments_matches.goals2 <> -1)
					GROUP BY memberid)
				) unionalias GROUP BY memberid ORDER BY value " . $orderBy . "
			");
			break;

		case "tournament_participations":
			$records = $db->sendQuery("
				SELECT tournaments_teams.playerid AS memberid, COUNT(*) AS value FROM tournaments
				JOIN tournaments_teams ON tournaments.id = tournaments_teams.tournamentid
				WHERE " . $tournamentRestriction . "
				GROUP BY memberid ORDER BY value " . $orderBy . "
			");
			break;

		case "matches":
		case "wins":
		case "draws":
		case "losses":
			if ($recordType == "matches") {
				$goalsOperator = false;
			} else if ($recordType == "wins") {
				$goalsOperator = ">";
			} else if ($recordType == "draws") {
				$goalsOperator = "=";
			} else {
				$goalsOperator = "<";
			}
			$records = $db->sendQuery("
				SELECT memberid, SUM(matches) AS value FROM(
					(SELECT playerid1 AS memberid, COUNT(*) AS matches FROM tournaments_matches
					JOIN tournaments ON tournaments_matches.tournamentid = tournaments.id
					WHERE (" . $tournamentRestriction . ")
					  AND (tournaments_matches.playerid1 <> 0)
					  AND (tournaments_matches.playerid2 <> 0)
					  AND (tournaments_matches.goals1 <> -1)
					  AND (tournaments_matches.goals2 <> -1)
					  " . ($goalsOperator?"AND (goals1 " . $goalsOperator . " goals2)":"") . "
					GROUP BY memberid)
					UNION ALL
					(SELECT playerid2 as memberid, COUNT(*) AS matches FROM tournaments_matches
					JOIN tournaments ON tournaments_matches.tournamentid = tournaments.id
					WHERE (" . $tournamentRestriction . ")
					  AND (tournaments_matches.playerid1 <> 0)
					  AND (tournaments_matches.playerid2 <> 0)
					  AND (tournaments_matches.goals1 <> -1)
					  AND (tournaments_matches.goals2 <> -1)
					  " . ($goalsOperator?"AND (goals2 " . $goalsOperator . " goals1)":"") . "
					GROUP BY memberid)
				) unionalias GROUP BY memberid ORDER BY value " . $orderBy . "
			");
			break;

		case "goals_shot":
		case "goals_shot_per_game":
		case "goals_shot_in_1_game":
		case "goals_received":
		case "goals_received_per_game":
		case "goals_received_in_1_game":
			$isGoalsShot = startsWith($recordType, "goals_shot");
			$isPerGame = endsWith($recordType, "per_game");
			if ($isPerGame) {
				$valueExpression = "(SUM(goals) / SUM(matches))";
				$subValueOperation = "SUM";
			} else if (endsWith($recordType, "in_1_game")) {
				$valueExpression = "MAX(goals)";
				$subValueOperation = "MAX";
			} else {
				$valueExpression = "SUM(goals)";
				$subValueOperation = "SUM";
			}
			$records = $db->sendQuery("
				SELECT memberid, " . $valueExpression . " AS value FROM(
					(SELECT playerid1 AS memberid, " . $subValueOperation . "(goals" . ($isGoalsShot?1:2) . ") AS goals" . ($isPerGame?", COUNT(*) as matches":"") . " FROM tournaments_matches
					JOIN tournaments ON tournaments_matches.tournamentid = tournaments.id
					WHERE (" . $tournamentRestriction . ")
					  AND (tournaments_matches.playerid1 <> 0)
					  AND (tournaments_matches.playerid2 <> 0)
					  AND (tournaments_matches.goals1 <> -1)
					  AND (tournaments_matches.goals2 <> -1)
					GROUP BY memberid)
					UNION ALL
					(SELECT playerid2 as memberid, " . $subValueOperation . "(goals" . ($isGoalsShot?2:1) . ") AS goals" . ($isPerGame?", COUNT(*) as matches":"") . " FROM tournaments_matches
					JOIN tournaments ON tournaments_matches.tournamentid = tournaments.id
					WHERE (" . $tournamentRestriction . ")
					  AND (tournaments_matches.playerid1 <> 0)
					  AND (tournaments_matches.playerid2 <> 0)
					  AND (tournaments_matches.goals1 <> -1)
					  AND (tournaments_matches.goals2 <> -1)
					GROUP BY memberid)
				) unionalias GROUP BY memberid ORDER BY value " . $orderBy . "
			");
			break;

		case "best_ranking":
			$records = $db->sendQuery("
				SELECT playerid AS memberid, MIN(`rank`) AS value FROM rankings_players
				GROUP BY memberid ORDER BY value " . $orderBy . "
			");
			break;

		case "awards":
			$records = $db->sendQuery("
				SELECT memberid, COUNT(*) as value FROM awards
				GROUP BY memberid ORDER BY value " . $orderBy . "
			");
			break;

		case "member_since":
			$records = $db->sendQuery("
				SELECT tournaments_teams.playerid AS memberid, MIN(tournaments.date) AS value FROM tournaments
				JOIN tournaments_teams ON tournaments.id = tournaments_teams.tournamentid
				GROUP BY memberid ORDER BY value " . $orderBy . "
			");
			break;

		default:
			linkToMessage("Unbekannte Rekord-Kategorie");
	}

	head("Rekorde");

	echo '
		<div id="records_left">
			<h1 id="page_title">Rekorde</h1>
			<form id="records_form" action="records.php">
				<div class="records_form_section">
					<span class="records_form_label">Kategorie</span>
						<select name="type">
	';
	$recordTypes = array(
		"winrate"=>"Siegesrate",
		"tournament_participations"=>"Turnierteilnahmen",
		"matches"=>"Spiele",
		"wins"=>"Siege",
		"draws"=>"Unentschieden",
		"losses"=>"Niederlagen",
		"goals_shot"=>"Tore",
		"goals_shot_per_game"=>"Tore / Spiel",
		"goals_shot_in_1_game"=>"Tore in 1 Spiel",
		"goals_received"=>"Gegentore",
		"goals_received_per_game"=>"Gegentore / Spiel",
		"goals_received_in_1_game"=>"Gegentore in 1 Spiel",
		"best_ranking"=>"Höhepunkt Weltrangl.",
		"awards"=>"FC Awards",
		"member_since"=>"Mitglied seit"
	);
	foreach ($recordTypes as $type=>$recordTitle) {
		echo '
			<option value="' . $type . '"' . (($type == $recordType) ? ' selected="selected"' : '') . '>' . $recordTitle . '</option>
		';
	}
	echo '
					</select>
				</div>
				<div class="records_form_section">
					<span class="records_form_label">Turnier-Typ</span>
					<select name="tournamentType">
						<option value=""' . ($tournamentType ? '' : ' selected="selected"') . '>Alle</option>
	';
	foreach ($allowedTournamentTypes as $type) {
		echo '<option value="' . $type . '"' . (($type == $tournamentType) ? ' selected="selected"' : '') . '>' . $tournamentTypes[$type]["title"] . '</option>';
	}
	echo '
					</select>
				</div>
				<div class="records_form_section">
					<span class="records_form_label">Zeitraum</span>
					<select name="time" disabled="disabled">
	';
	$timeChoices = array(
		""=>"Gesamt",
		(60*60*24*365)=>"Letztes Jahr",
		(60*60*24*183)=>"Letztes Halbjahr",
		(60*60*24*91)=>"Letztes Vierteljahr",
		"last_tournament"=>"Letztes Turnier"
	);
	foreach ($timeChoices as $timeValue=>$title) {
		echo '
			<option value="' . $timeValue . '"' . (($timeValue == $time) ? ' selected="selected"' : '') . '>' . $title . '</option>
		';
	}
	echo '
					</select>
				</div>
				<div class="records_form_section">
					<span class="records_form_label">Sortierung</span>
					<select name="sort">
	';
	$sortTypes = array(
		0=>"Aufsteigend",
		1=>"Absteigend"
	);
	foreach ($sortTypes as $sortValue=>$title) {
		echo '
			<option value="' . $sortValue . '"' . (($sortValue == $sort) ? ' selected="selected"' : '') . '>' . $title . '</option>
		';
	}
	echo '
					</select>
				</div>
				<a href="javascript:submitForm(\'records_form\')" class="button button-style1">Anzeigen</a><br/>
			</form>
		</div>
		<div id="records_right">
	';
	$i = 0;
	$place = 0;
	foreach ($records as $record) {
		if (($i % 2) == 0) {
			$backgroundColor = "#EEE";
			$textColor = "#333";
		} else {
			$backgroundColor = "#333";
			$textColor = "#FFF";
		}
		$member = $members[$record->memberid];
		if (($place == 0) || ($record->value != $lastValue)) {
			$place++;
			$lastValue = $record->value;
			$displayPlace = true;
		} else {
			$displayPlace = false;
		}
		if ($recordType == "winrate") {
			$displayedValue = (round(($record->value * 100), 2) . "%");
		} else if (endsWith($recordType, "per_game")) {
			$displayedValue = round($record->value, 2);
		} else if ($recordType == "member_since") {
			$displayedValue = strftime("%d.%m.%Y", $record->value);
		} else {
			$displayedValue = $record->value;
		}
		echo '
			<div class="record" style="background:' . $backgroundColor . '; color:' . $textColor . ';">
				<span class="record_member_place">' . ($displayPlace ? $place . '. ' : '') . '</span>
				<img src="' . getMemberIcon($member->id) . '" class="record_member_icon"/>
				<a href="member.php?id=' . $member->id . '" class="record_member_name" style="color:' . $textColor . ';">' . escapeHTML($member->name) . '</a>
				<span class="record_value">' . $displayedValue . '</span>
			</div>
		';
		$i++;
	}
	echo '
		</div>
	';
	
	footer();
?>