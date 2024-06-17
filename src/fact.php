<?php
	require_once("core.php");

	function tryNewFact() {
		linkTo("fact.php?random=1");
	}

	if (getURLParameter("random")) {
		$memberID = array_rand($members);
		$type = rand(0, 3);
		$sorts = array();
		$sorts[0] = rand(0, 1);
		if ($type == 4) {
			$sorts[1] = rand(0, 1);
		}
		$link = "fact.php?memberID=" . $memberID . "&type=" . $type;
		foreach ($sorts as $i=>$sort) {
			$link .= "&sort" . $i . "=" . $sort;
		}
		linkTo($link);
	}
	$type = getURLParameter("type");

	$member = $members[getURLParameter("memberID")];
	$memberNameGenitiveEnd = (endsWith($member->name, "s")?"'":"s ");
	$type = getURLParameter("type");
	$sort = getURLParameter("sort0");
	$sort2 = getURLParameter("sort1");
	switch ($type) {
		// Date
		case 0:
			$dateRow = $db->sendQuery("
				SELECT date FROM tournaments
				JOIN tournaments_teams ON tournaments.id = tournaments_teams.tournamentid
				WHERE tournaments_teams.playerid = " . $member->id . "
				ORDER BY date " . ($sort?"ASC":"DESC") . "
				LIMIT 1
			");
			if (!$dateRow) {
				tryNewFact();
			}
			$text = escapeHTML($member->name) . $memberNameGenitiveEnd . " " . ($sort?"erstes":"letztes") . " Turnier war am " . strftime("%d. %B %Y", $dateRow->date) . ".";
			break;

		// Wins
		case 1:
			$matches1 = $db->sendQuery("
				SELECT COUNT(id) as count FROM tournaments_matches
				WHERE ((playerid1 = " . $member->id . ") AND (goals1 " . ($sort?"<":">") . " goals2))
			")->count;
			$matches2 = $db->sendQuery("
				SELECT COUNT(id) as count FROM tournaments_matches
				WHERE ((playerid2 = " . $member->id . ") AND (goals2 " . ($sort?"<":">") . " goals1))
			")->count;
			$matches = $matches1 + $matches2;
			$text = escapeHTML($member->name) . " hat insgesamt " . $matches . " Turnierspiel" . (($matches == 1)?"":"e") . " " . ($sort?"verloren":"gewonnen") . ".";
			break;

		// Team
		case 2:
			$row = $db->sendQuery("
				SELECT teamid, COUNT(id) as count FROM tournaments_teams
				WHERE playerid = " . $member->id . "
				GROUP BY teamid
				ORDER BY count " . ($sort?"ASC":"DESC") . " LIMIT 1
			");
			if (!$row) {
				tryNewFact();
			}
			$text = escapeHTML($member->name) . $memberNameGenitiveEnd . " " . ($sort?"wenigst":"meist") . "gespielte Mannschaft bei Turnieren ist " . escapeHTML($teams[$row->teamid]->name) . " (" . $row->count . "x).";
			break;

		// Highest win
		case 3:
			$sort2 = (rand(0,1) == 0);
			$match = getBestMatch($member->id, $sort, $sort2);
			if (!$match) {
				tryNewFact();
			}
			$isPlayer1 = ($match->playerid1 == $member->id);
			$opponent = $members[$isPlayer1?$match->playerid2:$match->playerid1];
			$date = $db->getValue("tournaments", "date", array("id"=>$match->tournamentid));
			$text = escapeHTML($member->name) . $memberNameGenitiveEnd . " " . ($sort2?"höchste":"niedrigste") . ($sort?"r":"") . " Turnier" . ($sort?"sieg":"niederlage") . " ist ein " . ($isPlayer1 ? ($match->goals1 . " : " . $match->goals2) : ($match->goals2 . " : " . $match->goals1)) . " gegen " . escapeHTML($opponent->name) . ". (" . strftime("%d. %B %Y", $date) .")";
			break;
	}

	head("Interessanter Fakt", 4);

	echo '
		<a id="random_result_button" href="fact.php?random=1" class="button button-style1">Zufälliger Fakt</a>
		<h1 id="page_title">Interessanter Fakt</h1>
		<h2 style="padding-bottom:0px;"><img src="' . getMemberIcon($member->id) . '" class="member_icon member_icon_small"/>' . $text . '</h2>
	';

	footer();
?>