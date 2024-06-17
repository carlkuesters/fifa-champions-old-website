<?php
	require_once(__DIR__ . "/../../core.php");

	$tournaments = $db->getRows("tournaments");

	$returnedTournaments = array();
	foreach ($tournaments as $tournament) {
        $returnedTournament = new stdClass();
        $returnedTournament->id = intval($tournament->id);
        $returnedTournament->type = $tournament->type;
        $returnedTournament->location = ($tournament->locationid ? $locations[$tournament->locationid]->name : null);
        $returnedTournament->date = intval($tournament->date);

        $returnedTournaments[] = $returnedTournament;
	}

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedTournaments);
?>
