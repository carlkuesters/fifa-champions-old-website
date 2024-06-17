<?php
	require_once(__DIR__ . "/../../core.php");
    require_once(__DIR__ . "/../util.php");

    function mapHighestMatch($match, $member) {
        if ($match) {
            $returnedMatch = new stdClass();
            $returnedMatch->id = intval($match->id);
            $returnedMatch->tournamentId = intval($match->tournamentid);
            if ($match->playerid1 === $member->id) {
                $returnedMatch->opponentId = intval($match->playerid2);
                $returnedMatch->goalsOwn = intval($match->goals1);
                $returnedMatch->goalsOpponent = intval($match->goals2);
            } else {
                $returnedMatch->opponentId = intval($match->playerid1);
                $returnedMatch->goalsOwn = intval($match->goals2);
                $returnedMatch->goalsOpponent = intval($match->goals1);
            }
            return $returnedMatch;
        }
        return null;
    }

    $memberId = getURLParameter("id");
	$member = $db->getRow("members", "*", array("id"=>$memberId));

    $joinDateRow = $db->sendQuery("
        SELECT date FROM tournaments
        JOIN tournaments_teams ON tournaments.id = tournaments_teams.tournamentid
        WHERE tournaments_teams.playerid = " . $member->id . "
        ORDER BY date
        LIMIT 1
    ");
    $joinDate = ($joinDateRow ? intval($joinDateRow->date) : null);
    $tournamentsCount = intval($db->sendQuery("
        SELECT COUNT(id) as count FROM tournaments_teams
        WHERE playerid = " . $member->id . "
    ")->count);
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

    $returnedRankings = array();
    $rankings = $db->getRows("rankings");
    foreach ($rankings as $ranking) {
        $playerRanking = $db->getRow("rankings_players", array("rank"), array("rankingid"=>$ranking->id,"playerid"=>$member->id));
        if ($playerRanking) {
            $returnedRanking = new stdClass();
            $returnedRanking->date = intval($ranking->date);
            $returnedRanking->rank = intval($playerRanking->rank);
            $returnedRankings[] = $returnedRanking;
        }
    }

    $awards = $db->getRows("awards", array("type","year"), array("memberid"=>$member->id));

    $tournamentResults = [];
    $allTournamentResults = getAllTournamentResults();
    foreach ($allTournamentResults as $tournamentResult) {
        foreach ($tournamentResult["players"] as $playerResult) {
            if ($playerResult["playerId"] === intval($member->id)) {
                $tournamentResults[] = array(
                    "tournamentId" => $tournamentResult["tournamentId"],
                    "place" => $playerResult["place"]
                );
            }
        }
    }

    $highestWin = getBestMatch($member->id, true, true);
    $highestLoss = getBestMatch($member->id, false, true);

    $elos = getElos();

    $returnedMember = new stdClass();
    $returnedMember->id = intval($member->id);
    $returnedMember->name = $member->name;
    $returnedMember->description = parseMemberDescription($member->description);
    $returnedMember->guest = $member->guest === "1";

    $returnedMember->joinDate = $joinDate;

    $returnedMember->tournaments = $tournamentsCount;
    $returnedMember->matches = $matchesCount;
    $returnedMember->wins = $winsCount;
    $returnedMember->draws = $drawsCount;
    $returnedMember->losses = $lossesCount;
    $returnedMember->goalsShot = $goalsShot;
    $returnedMember->goalsReceived = $goalsReceived;

    $returnedMember->rankings = $returnedRankings;

    $returnedAwards = array();
    foreach ($awards as $award) {
        $returnedAward = new stdClass();
        $returnedAward->type = $award->type;
        $returnedAward->year = intval($award->year);
        $returnedAwards[] = $returnedAward;
    }
    $returnedMember->awards = $returnedAwards;

    $returnedMember->tournamentResults = $tournamentResults;

    $returnedMember->highestWin = mapHighestMatch($highestWin, $member);
    $returnedMember->highestLoss = mapHighestMatch($highestLoss, $member);

    $returnedMember->elo = $elos[$member->id];

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedMember);
?>
