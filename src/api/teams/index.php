<?php
	require_once(__DIR__ . "/../../core.php");

	$teams = $db->getRows("teams");

	$returnedTeams = array();
	foreach ($teams as $team) {
		$returnedTeam = new stdClass();
		$returnedTeam->id = intval($team->id);
		$returnedTeam->name = $team->name;
		$returnedTeams[] = $returnedTeam;
	}

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedTeams);
?>
