<?php
	require_once(__DIR__ . "/../../core.php");
	require_once(__DIR__ . "/../util.php");

    $metaType = getURLParameter("type");
    $metaRows = $db->sendQuery("
        SELECT
          tournaments_meta.playerid as playerid,
          tournaments_meta.text as text,
          tournaments.id as tournamentId,
          tournaments.type as tournamentType,
          tournaments.date as tournamentDate,
          locations.name as locationName
        FROM tournaments_meta
        JOIN tournaments ON tournaments.id = tournaments_meta.tournamentid
        JOIN locations ON locations.id = tournaments.locationid
        WHERE tournaments_meta.type = '" . $db->escape($metaType) . "'
    ");

    $returnedMeta = array();
    foreach ($metaRows as $metaRow) {
        $metaInfo = parseMetaText($metaRow->text);

        $returnedMetaEntry = new stdClass();
        $returnedMetaEntry->playerId = intval($metaRow->playerid);
        $returnedMetaEntry->text = $metaInfo->text;
        $returnedMetaEntry->youtubeVideoId = $metaInfo->youtubeVideoId;
        $returnedMetaEntry->tournamentId = intval($metaRow->tournamentId);
        $returnedMetaEntry->tournamentType = $metaRow->tournamentType;
        $returnedMetaEntry->tournamentDate = intval($metaRow->tournamentDate);
        $returnedMetaEntry->locationName = $metaRow->locationName;
        $returnedMeta[] = $returnedMetaEntry;
    }

    header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedMeta);
?>
