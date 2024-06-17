<?php
	require_once(__DIR__ . "/../../core.php");
	require_once(__DIR__ . "/../util.php");

    $recordType = getURLParameter("type");
    $tournamentType = getURLParameter("tournamentType");
    $sort = getURLParameter("sort", true);

    $tournamentRestriction = ($tournamentType ? "tournaments.type = '" . $tournamentType . "'" : "tournaments.type <> 'team_tournament'");
    if ($recordType == "member_since") {
        $sort = !$sort;
    }
    $orderBy = ($sort ? "DESC" : "ASC");

    function sortRecordsByValue($record1, $record2) {
        global $sort;
        if ($record1->value > $record2->value) {
            return ($sort ? -1 : 1);
        } else if ($record2->value > $record1->value) {
            return ($sort ? 1 : -1);
        }
        return 0;
    }

    switch ($recordType) {
        case "elo":
            $elos = getElos();
            $records = [];
            foreach ($elos as $playerId=>$elo) {
                $record = new stdClass();
                $record->memberid = $playerId;
                $record->value = $elo;
                $records[] = $record;
            }
            usort($records, "sortRecordsByValue");
            break;

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

        case "tournament_place":
            $allTournamentResults = getAllTournamentResults();
            $players = [];
            foreach ($allTournamentResults as $tournamentResult) {
                foreach ($tournamentResult["players"] as $tournamentPlayerResult) {
                    $playerId = $tournamentPlayerResult["playerId"];
                    if (!isset($players[$playerId])) {
                        $players[$playerId] = array("placeSum"=>0,"tournaments"=>0);
                    }
                    $players[$playerId]["placeSum"] += $tournamentPlayerResult["place"];
                    $players[$playerId]["tournaments"]++;
                }
            }
            $records = [];
            foreach ($players as $playerId=>$player) {
                $record = new stdClass();
                $record->memberid = $playerId;
                $record->value = $player["placeSum"] / $player["tournaments"];
                $records[] = $record;
            }
            usort($records, "sortRecordsByValue");
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
    }

    switch ($recordType) {
        case "tournament_place":
        case "winrate":
        case "goals_shot_per_game":
        case "goals_received_per_game":
            foreach ($records as $record) {
                $record->value = round(floatval($record->value), 4);
            }
            break;

        case "tournament_participations":
        case "matches":
        case "wins":
        case "draws":
        case "losses":
        case "goals_shot":
        case "goals_shot_in_1_game":
        case "goals_received":
        case "goals_received_in_1_game":
        case "best_ranking":
        case "awards":
        case "member_since":
            foreach ($records as $record) {
                $record->value = intval($record->value);
            }
            break;
    }

    $returnedRecords = array();
    foreach ($records as $record) {
        $returnedRecord = new stdClass();
        $returnedRecord->memberId = intval($record->memberid);
        $returnedRecord->value = $record->value;
        $returnedRecords[] = $returnedRecord;
    }

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedRecords);
?>
