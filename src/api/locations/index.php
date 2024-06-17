<?php
	require_once(__DIR__ . "/../../core.php");

	$locations = $db->getRows("locations");

	$returnedLocations = array();
	foreach ($locations as $location) {
        $returnedLocation = new stdClass();
        $returnedLocation->id = intval($location->id);
        $returnedLocation->name = $location->name;
        $returnedLocations[] = $returnedLocation;
	}

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedLocations);
?>
