<?php
	require_once("core.php");
	
	if (getURLParameter("random")) {
		$id = $members[array_rand($members)]->id;
		linkTo("member.php?id=" . $id);
	}
	$memberID1 = getURLParameter("memberID1");
	$memberID2 = getURLParameter("memberID2");
	$member1 = $members[$memberID1];
	$member2 = $members[$memberID2];
	$matches = $db->sendQuery("
		SELECT tournaments_matches.*, tournaments.date as date FROM tournaments_matches
		JOIN tournaments ON tournaments.id = tournaments_matches.tournamentid
		WHERE (tournaments.type <> 'team_tournament') AND
			  ((playerid1 = " . $member1->id . " AND playerid2 = " . $member2->id . ")
			OR (playerid1 = " . $member2->id . " AND playerid2 = " . $member1->id . "))
	", true);
	$matchesCount = count($matches);
	$wins1 = 0;
	$wins2 = 0;
	$draws = 0;
	$goals1 = 0;
	$goals2 = 0;
	if ($matchesCount > 0) {
		$newestMatch = false;
		foreach ($matches as $match) {
			if ($match->playerid1 == $member1->id) {
				if ($match->goals1 > $match->goals2) {
					$wins1++;
				} else if ($match->goals1 < $match->goals2) {
					$wins2++;
				} else {
					$draws++;
				}
				$goals1 += $match->goals1;
				$goals2 += $match->goals2;
			} else {
				if ($match->goals2 > $match->goals1) {
					$wins1++;
				} else if ($match->goals2 < $match->goals1) {
					$wins2++;
				} else {
					$draws++;
				}
				$goals1 += $match->goals2;
				$goals2 += $match->goals1;
			}
			if ((!$newestMatch) || ($match->date > $newestMatch->date)) {
				$newestMatch = $match;
			}
		}
		if ($wins1 > $wins2) {
			$favoriteWins = $wins1;
			$favoriteLosses = $wins2;
		} else {
			$favoriteWins = $wins2;
			$favoriteLosses = $wins1;
		}
		$goalsPerGame1 = ($goals1 / $matchesCount);
		$goalsPerGame2 = ($goals2 / $matchesCount);
		if ($newestMatch->playerid1 == $member1->id) {
			$newestMatchGoals1 = $newestMatch->goals1;
			$newestMatchGoals2 = $newestMatch->goals2;
		} else {
			$newestMatchGoals1 = $newestMatch->goals2;
			$newestMatchGoals2 = $newestMatch->goals1;
		}
	}
	
	head("Duell-Bilanz - " . $member1->name . " : " . $member2->name);
	
	echo '
		<div style="float:right;">
			<form id="duel_form" action="duel.php">
				<select name="memberID1">
		';
		foreach ($members as $member) {
			echo '<option value="' . $member->id . '" style="background-image:url(' . getMemberIcon($member->id) . ');"' . (($member->id == $member1->id) ? ' selected="selected"' : '') . '>' . escapeHTML($member->name) . '</option>';
		}
		echo '
				</select>
				<span>:</span>
				<select name="memberID2">
		';
		foreach ($members as $member) {
			echo '<option value="' . $member->id . '" style="background-image:url(' . getMemberIcon($member->id) . ');"' . (($member->id == $member2->id) ? ' selected="selected"' : '') . '>' . escapeHTML($member->name) . '</option>';
		}
		echo '
				</select>
				<a href="javascript:submitForm(\'duel_form\')" class="button button-style1">Anzeigen</a>
			</form>
		</div>
		<h1 id="page_title"><img src="' . getMemberIcon($member1->id) . '" class="member_icon member_icon_big"/>' . escapeHTML($member1->name). ' : <img src="' . getMemberIcon($member2->id) . '" class="member_icon member_icon_big"/>' . escapeHTML($member2->name). '</h1>
		<div style="display:inline-block; vertical-align:top; margin:0px 50px 15px 0px;">
			<h2>Spiele: ' . $matchesCount . '</h2>
			<h3>Siege (' . escapeHTML($member1->name) . '): ' . $wins1 . '</h3>
			<h3>Unentschieden: ' . $draws . '</h3>
			<h3>Siege (' . escapeHTML($member2->name) . '): ' . $wins2 . '</h3>
		</div>
		<div style="display:inline-block; vertical-align:top;">
			<h2>Tore: ' . ($goals1 + $goals2) . '</h2>
			<h3>' . escapeHTML($member1->name) . ' schoss bisher: ' . $goals1 . ' Tore</h3>
			<h3>' . escapeHTML($member2->name) . ' schoss bisher: ' . $goals2 . ' Tore</h3>
		</div>
	';
	if ($matchesCount > 0) {
		echo '
			<h2>Durchschn. Ergebnis: ' . round($goalsPerGame1, 0) . ' : ' . round($goalsPerGame2, 0) . '</h2>
			<h2>Letztes Ergebnis: ' . $newestMatchGoals1 . ' : ' . $newestMatchGoals2 . ' <span style="font-size:14px;">(' . strftime("%d. %B %Y", $newestMatch->date) . ')</span></h2>
			<h2>Favorit: ' . (($wins1 == $wins2)?"Beide":escapeHTML(($wins1 > $wins2)?$member1->name:$member2->name)) . ', mit einer Bilanz von<br/>
			<span class="duel_favorite_probability" style="margin-top:5px;">' . round(($favoriteWins / $matchesCount) * 100, 2) . '%</span>Siegen<br/>
			<span class="duel_favorite_probability">' . round(($draws / $matchesCount) * 100, 2) . '%</span>Unentschieden<br/>
			<span class="duel_favorite_probability">' . round(($favoriteLosses / $matchesCount) * 100, 2) . '%</span>Niederlagen</h2>
		';
	} else {
		echo '
			<h2>Diese beiden Spieler haben noch bei<br>keinem Turnier gegeneinander gespielt.</h2>
		';
	}
	
	footer();
?>