<?php
	require_once(__DIR__ . "/../../core.php");

	$returnedRankings = array();

    $rankings = $db->getRows("rankings");
	foreach ($rankings as $ranking) {
        $playerRankings = $db->getRows("rankings_players", array("playerid","rank","text"), array("rankingid"=>$ranking->id));
        $returnedRanking = new stdClass();
        $returnedRanking->id = intval($ranking->id);
        $returnedRanking->date = intval($ranking->date);
        $returnedPlayerRankings = array();
		foreach ($playerRankings as $playerRanking) {
            $returnedPlayerRanking = new stdClass();
            $returnedPlayerRanking->playerId = intval($playerRanking->playerid);
            $returnedPlayerRanking->rank = intval($playerRanking->rank);
            $returnedPlayerRanking->text = ($playerRanking->text ? $playerRanking->text : null);
            $returnedPlayerRankings[] = $returnedPlayerRanking;
		}
        $returnedRanking->players = $returnedPlayerRankings;
        $returnedRankings[] = $returnedRanking;
	}

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedRankings);
?>
