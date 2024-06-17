<?php
	require_once(__DIR__ . "/../../core.php");
	require_once(__DIR__ . "/../util.php");

	$members = $db->getRows("members");

	$returnedMembers = array();
	foreach ($members as $member) {
        $returnedMember = new stdClass();
        $returnedMember->id = intval($member->id);
        $returnedMember->name = $member->name;
		$returnedMember->description = parseMemberDescription($member->description);
		$returnedMember->guest = $member->guest === "1";
        $returnedMembers[] = $returnedMember;
	}

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedMembers);
?>
