<?php
	require_once(__DIR__ . "/../../core.php");

    $awards = $db->getRows("awards");
    $returnedAwards = array();
    foreach ($awards as $award) {
        $returnedAward = new stdClass();
        $returnedAward->year = intval($award->year);
        $returnedAward->type = $award->type;
        $returnedAward->memberId = intval($award->memberid);
        $returnedAwards[] = $returnedAward;
    }

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedAwards);
?>
