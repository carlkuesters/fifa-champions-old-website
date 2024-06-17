<?php
	require_once(__DIR__ . "/../../core.php");

    $tournamentsCount = intval($db->getRowCount("tournaments"));
    $matchesCount = intval($db->getRowCount("tournaments_matches", "(goals1 <> -1) AND (goals2 <> -1)"));
    $goalsCountRow = $db->sendQuery("SELECT SUM(goals1) as goals1, SUM(goals2) as goals2 FROM tournaments_matches WHERE (goals1 <> -1) AND (goals2 <> -1)");
    $goalsCount = ($goalsCountRow->goals1 + $goalsCountRow->goals2);

    $homeMessage = $administrationMeta["home_message"]->value;

    $returnedInfo = new stdClass();
    $returnedInfo->tournaments = $tournamentsCount;
    $returnedInfo->matches = $matchesCount;
    $returnedInfo->goals = $goalsCount;

    $returnedInfo->homeMessage = $homeMessage;

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedInfo);
?>
