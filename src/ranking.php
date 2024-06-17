<?php
	require_once("core.php");
	head("Rangliste", 3);

	$id = getURLParameter("id");
	$ranking = $db->getRow("rankings", "*", array("id"=>$id));
	$playerRankings = $db->getRows("rankings_players", "*", array("rankingid"=>$ranking->id), "`rank`");
	$previousPlayerRankings = array();
	$previousRankingID = $db->getValue("rankings", "id", "date < " . $ranking->date, "date DESC");
	if ($previousRankingID) {
		$previousPlayerRankings = $db->getRows("rankings_players", "*", array("rankingid"=>$previousRankingID));
	}
	echo '
		<a href="rankings.php" class="button button-style1" style="float:right;">Alle Ranglisten</a>
		<h1 id="page_title">FC-Weltrangliste (' . strftime("%d. %B %Y", $ranking->date) . ')</h1>
	';
	$isEditMode = getURLParameter("edit");
	if ($isEditMode) {
		echo '
			<form id="edit_ranking_form" action="edit.php?action=editRanking&id=' . $ranking->id . '" method="post">
		';
	}
	echo '
		<ul id="tournaments_list" class="style2">
	';
	if ($isEditMode) {
		echo '
			<li>
				<span class="tournaments_list_label">Datum</span>
				<input type="text" name="date" value="' . strftime("%d.%m.%Y", $ranking->date) . '"/>
			</li>
			<li>
				<span class="tournaments_list_label">Textform<br/>[Spieler1: Text1,<br/>&nbsp;Spieler2: Text2, ...]</span>
				<textarea name="text">';
		foreach ($playerRankings as $i=>$playerRanking) {
			if ($i != 0) {
				echo '
';
			}
			echo $members[$playerRanking->playerid]->name . ": " . $playerRanking->text;
		}
		echo '</textarea>
			</li>
		';
	} else {
		foreach ($playerRankings as $playerRanking) {
			$previousRank = false;
			foreach ($previousPlayerRankings as $previousPlayerRanking) {
				if ($previousPlayerRanking->playerid == $playerRanking->playerid) {
					$previousRank = $previousPlayerRanking->rank;
					break;
				}
			}
			echo '
				<li>
					<table>
						<tr>
							<td class="tournaments_list_label"><span style="color:#CFCFCF;">' . $playerRanking->rank . '.</span> <img src="' . getMemberIcon($playerRanking->playerid) . '" class="member_icon member_icon_small" style="float:none; margin-right:10px;"/><a href="member.php?id=' . $playerRanking->playerid . '" style="">' . escapeHTML($members[$playerRanking->playerid]->name) . '</a>';
			if ($previousRank) {
				$rankChange = ($previousRank - $playerRanking->rank);
				if ($rankChange != 0) {
					echo '<span style="font-size:16px;"> (';
					if ($rankChange > 0) {
						echo '+';
					}
					echo $rankChange . ')</span>';
				}
			}
			echo '</td>
							<td>' . escapeHTML($playerRanking->text) . '</td>
						</tr>
					</table>
				</li>
			';
		}
	}	
	echo '
			<li style="padding-bottom:0px;"></li>
		</ul>
	';
	if ($isEditMode) {
		echo '
				<a href="javascript:submitForm(\'edit_ranking_form\')" class="button button-style1">Änderungen speichern</a>
			</form>
		';
	} else if ($session->user) {
		echo '
			<a href="ranking.php?id=' . $ranking->id . '&edit=1" class="button button-style1" style="margin-right:5px;">Bearbeiten</a>
			<a href="edit.php?action=deleteRanking&id=' . $ranking->id . '" class="button button-style1" onclick="return confirm(\'Möchtest du diese Rangliste wirklich löschen?\');">Löschen</a>
		';
	}

	footer();
?>