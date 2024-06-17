<?php
	require_once("core.php");

	$id = getURLParameter("id");
	$tournament = $db->getRow("tournaments", "*", array("id"=>$id));
	$isCupTournament = in_array($tournament->type, $cupTournamentTypes);
	$tournamentName = (($isCupTournament ? "" : "FC-") . $tournamentTypes[$tournament->type]["title"] . " (" . strftime(($isCupTournament?"%Y":"%B %Y"), $tournament->date) . ")");
	head($tournamentName, 2);

	$tournamentMeta = array("news"=>array(),"match"=>array(),"goal"=>array(),"scorer"=>array(),"action"=>array(),"scandal"=>array(),"quote"=>array());
	$metaRows = $db->getRows("tournaments_meta", "*", array("tournamentid"=>$tournament->id));
	foreach ($metaRows as $metaRow) {
		$tournamentMeta[$metaRow->type][] = $metaRow;
	}
	$teams = array();
	$tournamentTeamRows = $db->getRows("tournaments_teams", "*", array("tournamentid"=>$tournament->id));
	foreach ($tournamentTeamRows as $tournamentTeamRow) {
		$teams[$tournamentTeamRow->playerid] = $db->getRow("teams", "*", array("id"=>$tournamentTeamRow->teamid));
	}

	$isEditMode = getURLParameter("edit");

	function getMetaListHTML($types) {
		$html = '';
		$isFirst = true;
		foreach ($types as $type=>$typeTitle) {
			$partHTML = getMetaHTML($type, $typeTitle, $isFirst);
			if ($partHTML) {
				$html .= $partHTML;
				$isFirst = false;
			}
		}
		return $html;
	}

	function getMetaHTML($type, $typeTitle, $isFirst) {
		global $tournamentMeta;
		global $isEditMode;
		$metaCount = count($tournamentMeta[$type]);
		$html = '
			<li' . ($isFirst ? ' class="first"' : '') . '>
				<h3>' . $typeTitle[($metaCount > 1)?1:0] . ' des Abends</h3>
		';
		if ($isEditMode) {
			$html .= '<textarea name="' . $type . '" class="tournament_edit_input">' . getMetasText($tournamentMeta[$type]) . '</textarea>';
		} else if ($metaCount > 0) {
			$html .= '
				<ul class="tournament_meta">
			';
			foreach ($tournamentMeta[$type] as $meta) {
				$html .= '<li' . (endsWith($meta->text, ":") ? ' class="subtitle"' : '') . '>' . getMetaTextHTML($meta) . '</li>';
			}
			$html .= '
				</ul>
			';
		} else {
			$html .= '
				<span style="font-style:italic;">Nicht vorhanden</span>
			';
		}
		$html .= '
			</li>
		';
		return $html;
	}

	function getMetaTextHTML($meta) {
		global $members;
		$html = '<p>';
		if ($meta->playerid) {
			$html .= '<img src="' . getMemberIcon($meta->playerid) . '" class="meta_member_icon" title="' . $members[$meta->playerid]->name . '"/>';
		}
		$displayedText = $meta->text;
		$youtubeVideoID = false;
		$matches = array();
		preg_match("/(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/watch\?v=(.+)$/", $meta->text, $matches);
		if ($matches) {
			$textLength = strlen($displayedText);
			$removedTextLength = strlen($matches[0]);
			// Remove additional space/return, if there is text before the link
			if ($textLength > $removedTextLength) {
				$removedTextLength++;
			}
			$displayedText = substr($displayedText, 0, ($textLength - $removedTextLength));
			$youtubeVideoID = $matches[4];
		}
		$html .= escapeHTML($displayedText) . '</p>';
		if ($youtubeVideoID) {
			$html .= '<iframe src="https://www.youtube.com/embed/' . $youtubeVideoID . '" class="tournament_meta_video" frameborder="0" allowfullscreen></iframe>';
		}
		return $html;
	}

	function getMetasText($metas) {
		global $members;
		$text = "";
		foreach ($metas as $i=>$meta) {
			if ($i != 0) {
				$text .= "\n";
			}
			if ($meta->playerid) {
				$text .= $members[$meta->playerid]->name . ": ";
			}
			$text .= $meta->text;
		}
		return $text;
	}
?>
<div id="content" class="8u">
	<?php
		if ($isEditMode) {
			echo '
				<form id="edit_tournament_form" action="edit.php?action=editTournament&id=' . $tournament->id . '" method="post">
			';
		}
	?>
	<section>
		<?php
			$tournamentInformation = calculateTournamentInformation($tournament->id);
			$groupsCount = count($tournamentInformation["groups"]);
			
			if ($isEditMode) {
				echo '
					<select name="type" class="tournament_edit_input">
				';
				foreach ($tournamentTypes as $type=>$tournamentInfo) {
					echo '<option ' . (($type == $tournament->type) ? ' selected="selected"' : '') . ' value="' . $type . '">' . $tournamentInfo["title"] . '</option>';
				}
				echo '
					</select>
					<input name="date" type="text" class="tournament_edit_input" value="' . strftime("%d.%m.%Y", $tournament->date) . '"/>
				';
				if (!$isCupTournament) {
					echo '
						<select name="locationID" class="tournament_edit_input">
					';
					foreach ($locations as $location) {
						echo '<option ' . (($location->id == $tournament->locationid) ? ' selected="selected"' : '') . ' value="' . $location->id . '">' . $location->name . '</option>';
					}
					echo '
						</select>
					';
				}
				echo '
					<textarea name="news" class="tournament_edit_input">' . getMetasText($tournamentMeta["news"]) . '</textarea>
				';
			} else {
				echo '
					<h2 id="tournament_name" class="main-title">' . escapeHTML($tournamentName) . '</h2>
				';
				if (!$isCupTournament) {
					echo '
						<h3 id="tournament_location">' . escapeHTML($locations[$tournament->locationid]->name) . '</h3>
					';
				}
				echo '
					<ul id="tournament_news">
				';
				foreach ($tournamentMeta["news"] as $i=>$meta) {
					$classes = array();
					if ($i == 0) {
						$classes[] = "first";
					}
					if (endsWith($meta->text, ":")) {
						$classes[] = "subtitle";
					}
					echo '<li';
					if ($classes) {
						echo ' class="';
						foreach ($classes as $i=>$class) {
							if ($i != 0) {
								echo ' ';
							}
							echo $class;
						}
						echo '"';
					}
					echo '>' . getMetaTextHTML($meta) . '</li>';
				}
				echo '
					</ul>
				';
				foreach ($tournamentInformation["groups"] as $i=>$group) {
					if ($groupsCount > 1) {
						echo '<h3 class="tournament_group_name"' . (($i > 0) ? ' style="margin-top:20px;"' : '') . '>Gruppe ' . $groupNames[$i] . '</h3>';
					}
					echo '
						<table class="tournament_table">
							<tr>
								<td style="width:30px;"></td>
								<td style="width:40px;"></td>
								<td style="text-align:left;"></td>
								<td style="width:30px;">SP</td>
								<td style="width:30px;"></td>
								<td style="width:30px;">S</td>
								<td style="width:30px;">U</td>
								<td style="width:30px;">N</td>
								<td style="width:30px;"></td>
								<td style="width:40px;">T</td>
								<td style="width:40px;">GT</td>
								<td style="width:50px;">TD</td>
								<td style="width:30px;"></td>
								<td style="width:30px;">PKT</td>
							</tr>
					';
					foreach ($group["results"] as $i=>$result) {
						$team = $teams[$result["member"]->id];
						echo '
							<tr>
								<td>' . ($i + 1) . '.</td>
								<td><img src="./images/teams/' . $team->id . '_32.png" class="tournament_team_icon" title="' . escapeHTML($team->name) . '"/></td>
								<td style="text-align:left;"><a href="member.php?id=' . $result["member"]->id . '">' . escapeHTML($result["member"]->name) . '</a></td>
								<td>' . $result["games"] . '</td>
								<td></td>
								<td>' . $result["wins"] . '</td>
								<td>' . $result["draws"] . '</td>
								<td>' . $result["losses"] . '</td>
								<td></td>
								<td>' . $result["goals_scored"] . '</td>
								<td>' . $result["goals_obtained"] . '</td>
								<td>' . (($result["goals_difference"] > 0)?"+":"") . $result["goals_difference"] . '</td>
								<td></td>
								<td>' . $result["points"] . '</td>
							</tr>
						';
					}
					echo '
						</table>
					';
				}
				$hasKORound = false;
				foreach ($matchTypes as $matchType=>$matchTypeName) {
					if (($matchType != "group") && (count($tournamentInformation["rounds"][$matchType]) > 0)) {
						$hasKORound = true;
						break;
					}
				}
				if ($hasKORound) {
					if (count($tournamentInformation["groups"]) > 0) {
						echo '
							<h3 class="tournament_group_name" style="margin-top:20px;">K.O.-Phase</h3>
						';
					}
					echo '
						<ul id="ko_diagram">
					';
					foreach ($matchTypes as $matchType=>$matchTypeName) {
						if ($matchType != "group") {
							echo '
								<li>
							';
							foreach ($tournamentInformation["rounds"][$matchType] as $match) {
								echo '
									<div class="ko_diagram_match">
										<div class="ko_diagram_team1 ko_diagram_' . (($match->goals1 > $match->goals2)?"winner":"loser") . '">
											<span class="ko_diagram_player">' . getMatchMemberName($match, 1) . '</span>
											<span class="ko_diagram_goals">' . (($match->goals1 != -1)?$match->goals1:"?") . '</span>
										</div>
										<div class="ko_diagram_team2 ko_diagram_' . (($match->goals2 > $match->goals1)?"winner":"loser") . '">
											<span class="ko_diagram_player">' . getMatchMemberName($match, 2) . '</span>
											<span class="ko_diagram_goals">' . (($match->goals2 != -1)?$match->goals2:"?") . '</span>
										</div>
									</div>
								';
							}
							echo '
								</li>
							';
						}
					}
					echo '
						</ul>
					';
				}
			}
		?>
	</section>
	
	<?php if (!$isCupTournament) { ?>
	<section>
		<div id="two-column" class="5grid">
			<div class="row">
				<div class="6u">
					<section>
						<h2>Sportliche Höhepunke</h2>
						<ul class="style4">
							<?php
								$sportTypes = array(
									"match"=>array("Spiel","Spiele"),
									"goal"=>array("Tor","Tore"),
									"scorer"=>array("Torjäger","Torjäger")
								);
								echo getMetaListHTML($sportTypes);
							?>
						</ul>
					</section>
				</div>
				<div class="6u">
					<section>
						<h2>Gesellschaftliche Höhepunkte</h2>
						<ul class="style4">
							<?php
								$socialTypes = array(
									"action"=>array("Aktion","Aktionen"),
									"scandal"=>array("Skandal","Skandale"),
									"quote"=>array("Spruch","Sprüche")
								);
								echo getMetaListHTML($socialTypes);
							?>
						</ul>
					</section>
				</div>
			</div>
		</div>
	</section>
	<?php } ?>
	
	<?php
		if ($isEditMode) {
			echo '
				<a href="javascript:submitForm(\'edit_tournament_form\')" class="button button-style1">Änderungen speichern</a>
			';
			if ($tournament->type != "dfc_cup") {
				echo '
					<input type="checkbox" name="evaluateRounds"/><span class="checkbox_label">K.O.-Runden aktualisieren</span><br/>
				';
			}
			echo '
				<a href="edit.php?action=deleteUnplayedTournamentMatches&id=' . $tournament->id . '" class="button button-style1" onclick="return confirm(\'Möchtest du die ungespielten Spiele wirklich löschen?\');">Ungespielte Spiele löschen</a>
			';
		} else if ($session->user) {
			echo '
				<a href="tournament.php?id=' . $tournament->id . '&edit=1" class="button button-style1" style="margin-right:5px;">Bearbeiten</a>
				<a href="edit.php?action=deleteTournament&id=' . $tournament->id . '" class="button button-style1" onclick="return confirm(\'Möchtest du diese Turnier wirklich löschen?\');">Löschen</a>
			';
		}
	?>
</div>
<div id="sidebar" class="4u">
	<section id="box1">
		<div id="tournament_matches" class="5grid">
			<?php
				function getRoundHTML($matches) {
					$html = '';
					$matchIndex = 0;
					for ($i = 0; $i < count($matches); $i++) {
						$html .= getMatchHTML($matches[$i], false, ($matchIndex < 2));
						$matchIndex++;
						if ($matches[$i]->playerid1 != 0) {
							$returnMatchIndex = -1;
							for ($r = 0; $r < count($matches); $r++) {
								if (($matches[$r]->playerid1 == $matches[$i]->playerid2) && ($matches[$r]->playerid2 == $matches[$i]->playerid1)) {
									$returnMatchIndex = $r;
								}
							}
							if ($returnMatchIndex != -1) {
								$html .= getMatchHTML($matches[$returnMatchIndex], true, ($matchIndex < 2));
								$matchIndex++;
								array_splice($matches, $returnMatchIndex, 1);
							}
						}
					}
					return $html;
				}

				function getMatchHTML($match, $isReturnMatch, $isFirst) {
					global $isEditMode;
					if ($isReturnMatch) {
						$leftSide = 2;
						$rightSide = 1;
					} else {
						$leftSide = 1;
						$rightSide = 2;
					}
					$html = '
						<li' . ($isFirst ? ' class="first"' : '') . '>
						<a>' . getMatchMemberName($match, $leftSide) . ' : ' . getMatchMemberName($match, $rightSide) . '<br/></a>
						<span class="tournament_match_scores">
					';
					if ($isEditMode) {
						$html .= '
							<input name="goals_' . $match->id . '_' . $leftSide . '" type="text" class="tournament_edit_input_goals" value="' . $match->{"goals" . $leftSide} . '"/>
							<span class="tournament_edit_input_goals_seperator">:</span>
							<input name="goals_' . $match->id . '_' . $rightSide . '" type="text" class="tournament_edit_input_goals" value="' . $match->{"goals" . $rightSide} . '"/>
						';
					} else if (($match->goals1 != -1) && ($match->goals2 != -1)) {
						$html .= $match->{"goals" . $leftSide} . ':' . $match->{"goals" . $rightSide};
					} else {
						$html .= '<span style="font-style:italic;">Nicht gespielt</span>';
					}
					$html .= '
							</span>
						</li>
					';
					return $html;
				}

				function getMatchMemberName($match, $side) {
					global $members;
					$memberID = $match->{"playerid" . $side};
					if ($memberID != 0) {
						return escapeHTML($members[$memberID]->name);
					}
					return "[???]";
				}

				foreach ($matchTypes as $matchType=>$matchTypeName) {
					if (count($tournamentInformation["rounds"][$matchType]) > 0) {
						echo '
							<h2 style="display:inline-block;">' . (($groupsCount == 1)?"Spiele":$matchTypeName) . '</h2>
						';
						echo '
							<div class="row">
								<div>
									<ul class="style4">' . getRoundHTML($tournamentInformation["rounds"][$matchType]) . '</ul>
								</div>
							</div>
						';
					}
				}
			?>
		</div>
	</section>
	<?php
		if ($isEditMode) {
			echo '
				</form>
			';
		}
	?>
</div>
<?php
	footer();
?>