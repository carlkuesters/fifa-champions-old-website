<?php
    require_once(__DIR__ . "/../../core.php");
	require_once(__DIR__ . "/../util.php");

	function mapMatch($match, $memberId1) {
        if ($match) {
            $returnedMatch = new stdClass();
            $returnedMatch->tournamentId = intval($match->tournamentid);
            $returnedMatch->type = $match->type;
            if ($match->playerid1 == $memberId1) {
                $returnedMatch->goals1 = intval($match->goals1);
                $returnedMatch->goals2 = intval($match->goals2);
            } else {
                $returnedMatch->goals1 = intval($match->goals2);
                $returnedMatch->goals2 = intval($match->goals1);
            }
            return $returnedMatch;
        }
        return null;
    }

    $memberId1 = getURLParameter("memberId1");
    $memberId2 = getURLParameter("memberId2");
    $member1 = $members[$memberId1];
    $member2 = $members[$memberId2];
    $matches = $db->sendQuery("
        SELECT tournaments_matches.*, tournaments.date as date FROM tournaments_matches
        JOIN tournaments ON tournaments.id = tournaments_matches.tournamentid
        WHERE (tournaments.type <> 'team_tournament') AND
              ((playerid1 = " . $member1->id . " AND playerid2 = " . $member2->id . ")
            OR (playerid1 = " . $member2->id . " AND playerid2 = " . $member1->id . "))
        ORDER BY date DESC
    ", true);
    $wins1 = 0;
    $wins2 = 0;
    $draws = 0;
    $goals1 = 0;
    $goals2 = 0;
    $newestMatch = null;
    $highestWin1 = null;
    $highestWin1_Score = 0;
    $highestWin2 = null;
    $highestWin2_Score = 0;
    $playedMatches = array();
    foreach ($matches as $match) {
        if (($match->goals1 != -1) && ($match->goals2 != -1)) {
            if ($match->playerid1 == $member1->id) {
                $matchGoals1 = $match->goals1;
                $matchGoals2 = $match->goals2;
            } else {
                $matchGoals1 = $match->goals2;
                $matchGoals2 = $match->goals1;
            }
            if ($matchGoals1 > $matchGoals2) {
                $wins1++;
                $win1Score = ((1000000 * ($matchGoals1 - $matchGoals2)) + $matchGoals1);
                if ($win1Score > $highestWin1_Score) {
                    $highestWin1 = $match;
                    $highestWin1_Score = $win1Score;
                }
            } else if ($matchGoals2 > $matchGoals1) {
                $wins2++;
                $win2Score = ((1000000 * ($matchGoals2 - $matchGoals1)) + $matchGoals2);
                if ($win2Score > $highestWin2_Score) {
                    $highestWin2 = $match;
                    $highestWin2_Score = $win2Score;
                }
            } else {
                $draws++;
            }
            $goals1 += $matchGoals1;
            $goals2 += $matchGoals2;
            $playedMatches[] = $match;
        }
    }

    $elos = getElos();
    $eloWinProbability1 = getEloWinProbability($elos[$member1->id], $elos[$member2->id]);
    $eloWinProbability2 = (1 - $eloWinProbability1);

    $returnedDuel = new stdClass();
    $returnedDuel->memberId1 = intval($member1->id);
    $returnedDuel->memberId2 = intval($member2->id);
    $returnedDuel->wins1 = $wins1;
    $returnedDuel->draws = $draws;
    $returnedDuel->wins2 = $wins2;
    $returnedDuel->goals1 = $goals1;
    $returnedDuel->goals2 = $goals2;
    $returnedDuel->highestWin1 = mapMatch($highestWin1, $memberId1);
    $returnedDuel->highestWin2 = mapMatch($highestWin2, $memberId1);
    $returnedDuel->eloWinProbability1 = $eloWinProbability1;
    $returnedDuel->eloWinProbability2 = $eloWinProbability2;

    $returnedMatches = [];
    foreach ($playedMatches as $match) {
        $returnedMatches[] = mapMatch($match, $memberId1);
    }
    $returnedDuel->matches = $returnedMatches;

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedDuel);
?>
