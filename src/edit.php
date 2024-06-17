<?php
	require_once("core.php");

	function getSendedAttributes ($parameterArray) {
		$attributes = array();
		foreach ($parameterArray as $key=>$value) {
			if (startsWith($key, PARAMETER_PREFIX_ATTRIBUTE)) {
				$attributeName = substr($key, strlen(PARAMETER_PREFIX_ATTRIBUTE));
				$attributes[$attributeName] = $value;
			}
		}
		return $attributes;
	}

	function getSendedText() {
		global $isAjaxRequest;
		if ($isAjaxRequest) {
			return getURLParameter("text");
		}
		return getPOSTParameter("text");
	}

	function onUnkownAction() {
		die("Unknown action.");
	}

	function xmlResponse($content="") {
		header("Cache-Control:no-cache,must-revalidate");
		header("Pragma:no-cache");
		header("Content-Type:text/xml;");
		echo '<response>' . $content . '</response>';
		exit();
	}

	function insertGroupMatches($tournamentID, $group, $returnMatches) {
		global $db;
		$groupSize = count($group);
		for ($i = 0; $i < $groupSize; $i++) {
			for ($r = ($returnMatches ? 0 : ($i + 1)); $r < $groupSize; $r++) {
				if ($group[$i] != $group[$r]) {
					$db->insertRow("tournaments_matches", array("tournamentid"=>$tournamentID,"type"=>"group","playerid1"=>$group[$i],"playerid2"=>$group[$r],"goals1"=>-1,"goals2"=>-1));
				}
			}
		}
	}

	function evaluateTournament($tournamentID) {
		global $db;
		global $koRounds;
		$tournamentInformation = calculateTournamentInformation($tournamentID);
		$groupsCount = count($tournamentInformation["groups"]);
		if ($groupsCount > 1) {
			switch ($groupsCount) {
				case 2: $pairings = array(1,0); break;
				case 4: $pairings = array(2,3,0,1); break;
			}
			$round = $koRounds[$groupsCount];
			for ($i = 0; $i < $groupsCount; $i++) {
				$first = $tournamentInformation["groups"][$i]["results"][0]["member"];
				$second = $tournamentInformation["groups"][$pairings[$i]]["results"][1]["member"];
				$matchIDs = $db->getValues("tournaments_matches", "id", array("tournamentid"=>$tournamentID,"type"=>$round), "id",  $i . ", 1");
				$db->updateRow("tournaments_matches", array("playerid1"=>$first->id,"playerid2"=>$second->id), array("id"=>$matchIDs[0]));
			}
			evaluateKORoundPlayers($tournamentID, $round);
		}
	}

	function evaluateKORoundPlayers($tournamentID, $round) {
		global $db;
		$matches = $db->getRows("tournaments_matches", "*", array("tournamentid"=>$tournamentID,"type"=>$round), "id");
		switch ($round) {
			case "quarterfinal":
				$winner1 = getKORoundWinner($matches[0]);
				$winner2 = getKORoundWinner($matches[1]);
				$winner3 = getKORoundWinner($matches[2]);
				$winner4 = getKORoundWinner($matches[3]);
				$matchIDs = $db->getValues("tournaments_matches", "id", array("tournamentid"=>$tournamentID,"type"=>"semifinal"), "id", 2);
				$db->updateRow("tournaments_matches", array("playerid1"=>$winner1,"playerid2"=>$winner4), array("id"=>$matchIDs[0]));
				$db->updateRow("tournaments_matches", array("playerid1"=>$winner2,"playerid2"=>$winner3), array("id"=>$matchIDs[1]));
				evaluateKORoundPlayers($tournamentID, "semifinal");
				break;

			case "semifinal":
				$winner1 = getKORoundWinner($matches[0]);
				$winner2 = getKORoundWinner($matches[1]);
				$matchID = $db->getValue("tournaments_matches", "id", array("tournamentid"=>$tournamentID,"type"=>"final"));
				$db->updateRow("tournaments_matches", array("playerid1"=>$winner1,"playerid2"=>$winner2), array("id"=>$matchID));
				break;
		}
	}

	function getKORoundWinner($match) {
		return (($match->goals1 > $match->goals2) ? $match->playerid1 : $match->playerid2);
	}

	function insertPlayerRankings($rankingID, $text) {
		global $db;
		$rankingTexts = getMemberTexts($text);
		foreach ($rankingTexts as $i=>$rankingText) {
			$db->insertRow("rankings_players", array("rankingid"=>$rankingID,"playerid"=>$rankingText["playerID"],"rank"=>($i + 1),"text"=>$rankingText["text"]));
		}
	}

	$action = getURLParameter("action");
	$id = getURLParameter("id");
	$isAjaxRequest = getURLParameter("isAjaxRequest");

	if ($session->user) {
		// Actions, that require a user
		switch ($action) {
			case "editAdministration":
				foreach ($administrationMeta as $meta) {
					$value = getPOSTParameter("meta_" . $meta->key);
					if ($value !== false) {
						$db->updateRow("administration_meta", array("value"=>$value), array("administration_meta.key"=>$meta->key));
					}
				}
				linkTo("index.php");
				break;

			case "editMembers":
				foreach ($members as $member) {
					$description = getPOSTParameter("description_" . $member->id);
					if ($description !== false) {
						$db->updateRow("members", array("description"=>$description), array("id"=>$member->id));
					}
				}
				linkTo("members.php");
				break;

			case "createTournament":
				$tournamentType = getPOSTParameter("type");
				$players = array();
				$teams = array();
				$playersCount = getPOSTParameter("playersCount");
				if ($tournamentType && $playersCount) {
					for ($i = 0; $i < $playersCount; $i++) {
						$playerID = getPOSTParameter("player_" . $i);
						$teamID = getPOSTParameter("team_" . $i);
						if (in_array($playerID, $players)) {
							linkToMessage("Mehrmals derselbe Spieler (" . $members[$playerID]->name . ")", "Ein Spieler kann nicht mehrfach an einem Turnier teilnehmen.");
						}
						$players[] = $playerID;
						$teams[] = $teamID;
					}
					$db->insertRow("tournaments", array("type"=>$tournamentType,"locationid"=>2,"date"=>time()));
					$tournamentID = $db->getInsertID();
					for ($i = 0; $i < $playersCount; $i++) {
						$db->insertRow("tournaments_teams", array("tournamentid"=>$tournamentID,"playerid"=>$players[$i],"teamid"=>$teams[$i]));
					}
					if ($tournamentType != "fun_tournament") {
						$groupSize = getPOSTParameter("groupSize");
						$koMatchsCount = false;
						if ($groupSize == "ko") {
							$koMatchsCount = ($playersCount / 2);
						} else {
							if ($groupSize == -1) {
								$groupSize = $playersCount;
							}
							$createReturnMatches = getPOSTParameter("returnMatches");
							$matchsCount = $playersCount;
							$groupsCount = 0;
							$group = array();
							foreach ($players as $playerID) {
								$group[] = $playerID;
								if (count($group) >= $groupSize) {
									insertGroupMatches($tournamentID, $group, $createReturnMatches);
									$groupsCount++;
									$group = array();
								}
							}
							if (count($group) > 0) {
								insertGroupMatches($tournamentID, $group, $createReturnMatches);
								$groupsCount++;
							}
							if ($groupsCount > 1) {
								$koMatchsCount = $groupsCount;
							}
						}
						if ($koMatchsCount) {
							do {
								$round = $koRounds[$koMatchsCount];
								for ($i = 0; $i < $koMatchsCount; $i++) {
									$db->insertRow("tournaments_matches", array("tournamentid"=>$tournamentID,"type"=>$round,"playerid1"=>0,"playerid2"=>0,"goals1"=>-1,"goals2"=>-1));
								}
								$koMatchsCount /= 2;
							} while ($koMatchsCount >= 1);
							evaluateTournament($tournamentID);
						}
					}
					linkTo("tournament.php?id=" . $tournamentID);
				}
				linkToMessage("Ungenügend Daten", "Bitte fülle alle Felder aus und versuche es noch einmal.");
				break;

			case "editTournament":
				$type = getPOSTParameter("type");
				$locationID = getPOSTParameter("locationID");
				$date = strtotime(getPOSTParameter("date"));
				if ($id && $type) {
					$db->updateRow("tournaments", array("type"=>$type,"locationid"=>$locationID,"date"=>$date), array("id"=>$id));
					$db->deleteRows("tournaments_meta", array("tournamentid"=>$id));
					$tournamentMeta = createMetaArray();
					foreach ($tournamentMeta as $type=>$metas) {
						$text = getPOSTParameter($type);
						$metaTexts = getMemberTexts($text);
						foreach ($metaTexts as $metaText) {
							$db->insertRow("tournaments_meta", array("tournamentid"=>$id,"type"=>$type,"playerid"=>$metaText["playerID"],"text"=>$metaText["text"]));
						}
					}
					$matches = array();
					foreach ($_POST as $key=>$value) {
						if (preg_match("/goals_(.*)_(.*)/", $key, $matches)) {
							$matchID = intval($matches[1]);
							$side = intval($matches[2]);
							if (($side == 1) || ($side == 2)) {
								$goals = intval($value);
								$db->updateRow("tournaments_matches", array(("goals" . $side)=>$goals), array("id"=>$matchID,"tournamentid"=>$id));
							}
						}
					}
					if (getPOSTParameter("evaluateRounds")) {
						evaluateTournament($id);
					}
					linkTo("tournament.php?id=" . $id);
				}
				linkToMessage("Ungenügend Daten", "Bitte fülle alle Felder aus und versuche es noch einmal.");
				break;

			case "deleteUnplayedTournamentMatches":
				if ($id) {
					$db->deleteRows("tournaments_matches", array("tournamentid"=>$id,"goals1"=>-1,"goals2"=>-1));
					linkTo("tournament.php?id=" . $id);
				}
				linkToMessage("Ungenügend Daten", "Keine Turnier-ID angegeben.");
				break;

			case "deleteTournament":
				if ($id) {
					$db->deleteRow("tournaments", array("id"=>$id));
					$db->deleteRows("tournaments_teams", array("tournamentid"=>$id));
					$db->deleteRows("tournaments_matches", array("tournamentid"=>$id));
					$db->deleteRows("tournaments_meta", array("tournamentid"=>$id));
					linkTo("tournaments.php");
				}
				linkToMessage("Ungenügend Daten", "Keine Turnier-ID angegeben.");
				break;

			case "createRanking":
				$text = getPOSTParameter("text");
				if ($text) {
					$db->insertRow("rankings", array("date"=>time()));
					$rankingID = $db->getInsertID();
					insertPlayerRankings($rankingID, $text);
					linkTo("ranking.php?id=" . $rankingID);
				}
				linkToMessage("Ungenügend Daten", "Bitte fülle alle Felder aus und versuche es noch einmal.");
				break;

			case "editRanking":
				$text = getPOSTParameter("text");
				$date = strtotime(getPOSTParameter("date"));
				if ($id && $text) {
					$db->updateRow("rankings", array("date"=>$date), array("id"=>$id));
					$db->deleteRows("rankings_players", array("rankingid"=>$id));
					insertPlayerRankings($id, $text);
					linkTo("ranking.php?id=" . $id);
				}
				linkToMessage("Ungenügend Daten", "Bitte fülle alle Felder aus und versuche es noch einmal.");
				break;

			case "deleteRanking":
				if ($id) {
					$db->deleteRow("rankings", array("id"=>$id));
					$db->deleteRows("rankings_players", array("rankingid"=>$id));
					linkTo("rankings.php");
				}
				linkToMessage("Ungenügend Daten", "Keine Ranglisten-ID angegeben.");
				break;

			case "logout":
				$session->user = false;
				linkTo("index.php");
				break;
		}
	} else {
		// Actions, that require no user
		switch ($action) {
			case "login":
				$password = getPOSTParameter("password");
				if ($password == ADMINISTRATION_PASSWORD) {
					$session->user = true;
					linkTo("index.php");
				}
				linkToMessage("Falsches Passwort");
				break;
		}
	}
?>
