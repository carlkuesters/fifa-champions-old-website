<?php
	require_once("core.php");

	if (getURLParameter("random")) {
		$id = $members[array_rand($members)]->id;
		linkTo("member.php?id=" . $id);
	}
	$id = getURLParameter("id");
	$member = $members[$id];
	$joinDateRow = $db->sendQuery("
		SELECT date FROM tournaments
		JOIN tournaments_teams ON tournaments.id = tournaments_teams.tournamentid
		WHERE tournaments_teams.playerid = " . $member->id . "
		ORDER BY date
		LIMIT 1
	");
	$joinDate = ($joinDateRow?$joinDateRow->date:false);
	$tournamentsCount = $db->sendQuery("
		SELECT COUNT(id) as count FROM tournaments_teams
		WHERE playerid = " . $member->id . "
	")->count;
	$currentRanking = $db->sendQuery("
		SELECT rankings_players.rank as `rank` FROM rankings_players
		JOIN rankings ON rankings_players.rankingid = rankings.id
		WHERE rankings_players.playerid = " . $member->id . "
		ORDER BY rankings.date DESC
		LIMIT 1
	");
	$bestRanking = $db->sendQuery("
		SELECT rankings_players.rank as `rank`, rankings.date as date FROM rankings_players
		JOIN rankings ON rankings_players.rankingid = rankings.id
		WHERE rankings_players.playerid = " . $member->id . "
		ORDER BY rankings_players.rank ASC, rankings.date DESC
		LIMIT 1
	");
	$matches = $db->sendQuery("
		SELECT * FROM tournaments_matches
		JOIN tournaments ON tournaments_matches.tournamentid = tournaments.id
		WHERE (tournaments.type <> 'team_tournament')
		 AND ((tournaments_matches.playerid1 = " . $member->id . ") OR (tournaments_matches.playerid2 = " . $member->id . "))
		 AND (tournaments_matches.goals1 <> -1)
		 AND (tournaments_matches.goals2 <> -1)
	", true);
	$matchesCount = count($matches);
	$winsCount = 0;
	$drawsCount = 0;
	$lossesCount = 0;
	$goalsShot = 0;
	$goalsReceived = 0;
	foreach ($matches as $match) {
		if ($match->playerid1 == $member->id) {
			if ($match->goals1 > $match->goals2) {
				$winsCount++;
			} else if ($match->goals1 == $match->goals2) {
				$drawsCount++;
			} else {
				$lossesCount++;
			}
			$goalsShot += $match->goals1;
			$goalsReceived += $match->goals2;
		} else {
			if ($match->goals2 > $match->goals1) {
				$winsCount++;
			} else if ($match->goals1 == $match->goals2) {
				$drawsCount++;
			} else {
				$lossesCount++;
			}
			$goalsShot += $match->goals2;
			$goalsReceived += $match->goals1;
		}
	}
	$highestWin = getBestMatch($member->id, true, true);
	$highestLoss = getBestMatch($member->id, false, true);

	function getMatchText($match) {
		global $members;
		global $member;
		if ($match) {
			if ($match->playerid1 == $member->id) {
				$goals1 = $match->goals1;
				$goals2 = $match->goals2;
				$opponentID = $match->playerid2;
			} else {
				$goals1 = $match->goals2;
				$goals2 = $match->goals1;
				$opponentID = $match->playerid1;
			}
			return ($goals1 . ' : ' . $goals2 . ' <span class="member_note">(gegen <a href="member.php?id=' . $opponentID . '">' . escapeHTML($members[$opponentID]->name) . '</a>)</span>');
		}
		return '-';
	}

	head("FIFA-Champion - " . $member->name);

	echo '
		<div id="profile_left">
			<span id="profile_name">' . escapeHTML($member->name) . '</span><br/>
			<img id="profile_image" src="' . getMemberIcon($member->id, 80) . '"/>
			<ul class="profile_stats">
				<li><span class="profile_stats_column_label">Turniere:</span>' . $tournamentsCount . '</li>
				<li><span class="profile_stats_column_label">Spiele:</span>' . $matchesCount . '</li>
				<li><span class="profile_stats_column_label">Tore:</span>' . $goalsShot . ($matchesCount ? ' <span class="member_note">(' . round(($goalsShot / $matchesCount), 2) . ' pro Spiel)</span>' : '') . '</li>
				<li><span class="profile_stats_column_label">Gegentore:</span>' . $goalsReceived . ($matchesCount ? ' <span class="member_note">(' . round(($goalsReceived / $matchesCount), 2) . ' pro Spiel)</span>' : '') . '</li>
				<li><span class="profile_stats_column_label">Siege:</span>' . $winsCount . ($matchesCount ? ' <span class="member_note">(' . round(($winsCount / $matchesCount) * 100, 2) . '%)</span>' : '') . '</li>
				<li><span class="profile_stats_column_label">Unentsch.:</span>' . $drawsCount . ($matchesCount ? ' <span class="member_note">(' . round(($drawsCount / $matchesCount) * 100, 2) . '%)</span>' : '') . '</li>
				<li><span class="profile_stats_column_label">Niederl.:</span>' . $lossesCount . ($matchesCount ? ' <span class="member_note">(' . round(($lossesCount / $matchesCount) * 100, 2) . '%)</span>' : '') . '</li>
			</ul>
			<form id="duel_form" action="duel.php" style="margin-top:20px;">
				<input name="memberID1" type="hidden" value="' . $member->id . '"/>
				<span class="profile_stats_column_label" style="font-size:18px;">Bilanz vs:</span>
				<select name="memberID2">
	';
	foreach ($members as $member2) {
		if ($member2->id != $member->id) {
			echo '<option value="' . $member2->id . '" style="background-image:url(' . getMemberIcon($member2->id) . ');">' . escapeHTML($member2->name) . '</option>';
		}
	}
	echo '
				</select>
				<a href="javascript:submitForm(\'duel_form\')" class="button button-style1" style="width:85px; margin:5px 0px 0px 104px;">Anzeigen</a>
			</form>
		</div>
		<div id="profile_right">
			<div class="profile_box_2 profile_box" style="float:left;">
				<span class="profile_box_title profile_column_label" style="margin-bottom:5px;">Weltrangliste:</span>Platz ' . ($currentRanking?$currentRanking->rank:'-') . '<br/>
				<span class="profile_box_title profile_column_label">Höhepunkt:</span>Platz ' . ($bestRanking ? $bestRanking->rank . ' <span class="member_note">(' . strftime("%d.%m.%Y", $bestRanking->date) . ')</span>' : '-') . '
			</div>
			<div class="profile_box_2 profile_box" style="float:right;">
				<div id="ranking_diagram" style="height:97px;"></div>
	';
	$rankingDiagram = new RankingDiagram($member->id);
	echo $rankingDiagram->generateHTML("ranking_diagram");
	echo '
			</div>
			<div class="profile_box_2 profile_box" style="float:left;">
				<span class="profile_box_title profile_column_label">Mitglied seit:</span>' . ($joinDate ? strftime("%d. %B %Y", $joinDate) : "-") . '
			</div>
			<div style="clear:both;"></div>
	';
	if ($member->description) {
		$memberDescription = escapeHTML($member->description);
		$memberDescription = str_replace("Stärken:", '<span class="profile_box_title">Stärken:</span>', $memberDescription);
		$memberDescription = str_replace("Schwächen:", '<span class="profile_box_title">Schwächen:</span>', $memberDescription);
		echo '
			<div class="profile_box">' . $memberDescription . '</div>
		';
	}
	/*echo '
			<div class="profile_box">
				<span class="profile_box_title">Größte Erfolge</span> [In Arbeit]
			</div>
	';*/
	$awards = $db->getRows("awards", "*", array("memberid"=>$member->id), "year DESC");
	if (count($awards) > 0) {
		echo '
				<div class="profile_box_items profile_box">
					<span class="profile_box_title">FC Awards</span>
					<ul class="profile_box_items_list">
		';
		foreach ($awards as $award) {
			echo '<li><img src="./images/award_100.png" class="profile_box_items_list_icon"/>' . $awardTypes[$award->type] . ' ' . $award->year . '</li>';
		}
		echo '
				</ul>
			</div>
		';
	}
	echo '
			<div class="profile_box_2 profile_box" style="float:left;">
				<span class="profile_box_title_line profile_box_title">Höchster Sieg:</span><br/>' . getMatchText($highestWin) . '
			</div>
			<div class="profile_box_2 profile_box" style="float:right;">
				<span class="profile_box_title_line profile_box_title">Höchste Niederlage:</span><br/>' . getMatchText($highestLoss) . '
			</div>
		</div>
	';
	
	footer();
?>