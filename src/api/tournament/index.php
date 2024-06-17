<?php
	require_once(__DIR__ . "/../../core.php");
    require_once(__DIR__ . "/../util.php");

	$tournamentId = getURLParameter('id');
    $tournament = $db->getRow("tournaments", "*", array("id"=>$tournamentId));

	$returnedTournament = new stdClass();
    $returnedTournament->id = intval($tournament->id);
    $returnedTournament->type = $tournament->type;
    $returnedTournament->location = ($tournament->locationid ? $locations[$tournament->locationid]->name : null);
    $returnedTournament->date = intval($tournament->date);

    $tournamentPlayerRows = $db->sendQuery("
        SELECT
          tournaments_teams.playerid as playerId,
          teams.id as teamId,
          teams.name as teamName
        FROM tournaments_teams
        JOIN teams ON teams.id = tournaments_teams.teamid
        WHERE tournaments_teams.tournamentid = " . intval($tournament->id) . "
    ");
    $returnedTournament->players = array();
    foreach ($tournamentPlayerRows as $tournamentPlayerRow) {
        $returnedTournamentPlayer = new stdClass();
        $returnedTournamentPlayer->playerId = intval($tournamentPlayerRow->playerId);
        $returnedTournamentPlayer->team = new stdClass();
        $returnedTournamentPlayer->team->id = intval($tournamentPlayerRow->teamId);
        $returnedTournamentPlayer->team->name = $tournamentPlayerRow->teamName;
        $returnedTournament->players[] = $returnedTournamentPlayer;
    }

    $returnedTournament->meta = array("news"=>array(),"match"=>array(),"goal"=>array(),"scorer"=>array(),"action"=>array(),"scandal"=>array(),"quote"=>array());
    $metaRows = $db->getRows("tournaments_meta", "*", array("tournamentid"=>$tournament->id));
    foreach ($metaRows as $metaRow) {
        $metaInfo = parseMetaText($metaRow->text);

        $returnedMetaEntry = new stdClass();
        $returnedMetaEntry->playerId = ($metaRow->playerid ? intval($metaRow->playerid) : null);
        $returnedMetaEntry->text = $metaInfo->text;
        $returnedMetaEntry->youtubeVideoId = $metaInfo->youtubeVideoId;

        $returnedTournament->meta[$metaRow->type][] = $returnedMetaEntry;
    }

    $tournamentInformation = calculateTournamentInformation($tournament->id);
    $results = new stdClass();
    $results->matches = array();
    $results->groups = array();
    foreach ($tournamentInformation['groups'] as $group) {
        $returnedGroup = array();
        foreach ($group['results'] as $memberResult) {
            $returnedMemberResult = new stdClass();
            $returnedMemberResult->playerId = intval($memberResult['member']->id);
            $returnedMemberResult->games = $memberResult['games'];
            $returnedMemberResult->wins = $memberResult['wins'];
            $returnedMemberResult->draws = $memberResult['draws'];
            $returnedMemberResult->losses = $memberResult['losses'];
            $returnedMemberResult->goals_scored = $memberResult['goals_scored'];
            $returnedMemberResult->goals_obtained = $memberResult['goals_obtained'];
            $returnedMemberResult->points = $memberResult['points'];
            $returnedMemberResult->goals_difference = $memberResult['goals_difference'];
            $returnedGroup[] = $returnedMemberResult;
        }
        $results->groups[] = $returnedGroup;
    }
    foreach ($tournamentInformation['matches'] as $match) {
        $returnedMatch = new stdClass();
        $returnedMatch->id = intval($match->id);
        $returnedMatch->type = $match->type;
        $returnedMatch->players = array();
        $player1 = new stdClass();
        $player2 = new stdClass();
        $player1->id = ($match->playerid1 ? intval($match->playerid1) : null);
        $player2->id = ($match->playerid2 ? intval($match->playerid2) : null);
        $player1->goals = (($match->goals1 !== '-1') ? intval($match->goals1) : null);
        $player2->goals = (($match->goals2 !== '-1') ? intval($match->goals2) : null);
        $returnedMatch->players[] = $player1;
        $returnedMatch->players[] = $player2;
        $results->matches[] = $returnedMatch;
    }
    $returnedTournament->results = $results;

    header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($returnedTournament);
?>
