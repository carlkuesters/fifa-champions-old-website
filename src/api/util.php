<?php
    function parseMemberDescription($description) {
        $parsedDescription = new stdClass();
        preg_match("/(.+)Stärken:(.+)Schwächen:(.+)/s", $description, $matches);
        if ($matches) {
            $parsedDescription->generally = trim($matches[1]);
            $parsedDescription->strengths = trim($matches[2]);
            $parsedDescription->weaknesses = trim($matches[3]);
        } else {
            $parsedDescription->generally = $description;
            $parsedDescription->strengths = null;
            $parsedDescription->weaknesses = null;
        }
        return $parsedDescription;
    }

	function parseMetaText($text) {
	    $displayedText = $text;
	    $youtubeVideoId = null;
        $youtubeMatches = array();
        preg_match("/(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/watch\?v=(.+)$/", $text, $youtubeMatches);
        if ($youtubeMatches) {
            $textLength = strlen($displayedText);
            $removedTextLength = strlen($youtubeMatches[0]);
            // Remove additional space/return, if there is text before the link
            if ($textLength > $removedTextLength) {
                $removedTextLength++;
            }
            $displayedText = substr($displayedText, 0, ($textLength - $removedTextLength));
            $youtubeVideoId = $youtubeMatches[4];
        }
        $metaInfo = new stdClass();
        $metaInfo->text = $displayedText;
        $metaInfo->youtubeVideoId = $youtubeVideoId;
        return $metaInfo;
	}

    function getAllTournamentResults() {
        global $db;
        global $koRounds;
        $results = [];
        $tournamentIdResults = $db->sendQuery("SELECT id FROM tournaments ORDER BY date DESC");
        foreach ($tournamentIdResults as $tournamentIdResult) {
            $tournamentInformation = calculateTournamentInformation($tournamentIdResult->id);

            $finalMatches = $tournamentInformation["rounds"]["final"];
            $isTournamentFinished = ($finalMatches ? (($finalMatches[0]->goals1 != -1) && ($finalMatches[0]->goals2 != -1)) : true);

            if ($isTournamentFinished) {
                $handledPlayers = [];
                $players = [];
                for ($i = 1; $i <= 8; $i *= 2) {
                    $matchType = $koRounds[$i];
                    $roundMatches = $tournamentInformation["rounds"][$matchType];
                    foreach ($roundMatches as $match) {
                        if ($match->playerid1 && $match->playerid2 && ($match->goals1 != -1) && ($match->goals2 != -1)) {
                            $playerPlaceAddition1 = 0;
                            $playerPlaceAddition2 = 0;
                            if ($match->goals1 > $match->goals2) {
                                $playerPlaceAddition2 = 1;
                            } else if ($match->goals2 > $match->goals1) {
                                $playerPlaceAddition1 = 1;
                            }
                            if (!in_array($match->playerid1, $handledPlayers)) {
                                $players[] = array(
                                    "playerId" => intval($match->playerid1),
                                    "place" => ($i + $playerPlaceAddition1)
                                );
                                $handledPlayers[] = $match->playerid1;
                            }
                            if (!in_array($match->playerid2, $handledPlayers)) {
                                $players[] = array(
                                    "playerId" => intval($match->playerid2),
                                    "place" => ($i + $playerPlaceAddition2)
                                );
                                $handledPlayers[] = $match->playerid2;
                            }
                        }
                    }
                }
                $firstTotalPlaceInGroups = count($handledPlayers) + 1;
                foreach ($tournamentInformation["groups"] as $group) {
                    $place = $firstTotalPlaceInGroups;
                    foreach ($group["results"] as $i=>$playerResult) {
                        $playerId = $playerResult["member"]->id;
                        if (!in_array($playerId, $handledPlayers)) {
                            $players[] = array(
                                "playerId" => intval($playerId),
                                "place" => $place
                            );
                            $handledPlayers[] = $playerId;
                            $place++;
                        }
                    }
                }
                $results[] = array(
                    "tournamentId" => intval($tournamentIdResult->id),
                    "players" => $players
                );
            }
        }
        return $results;
    }

    function getElos() {
        global $db;
        $matches = $db->sendQuery("
            SELECT playerid1, playerid2, goals1, goals2 FROM tournaments_matches
            JOIN tournaments ON tournaments.id = tournaments_matches.tournamentid
            WHERE playerid1 <> 0 AND playerid2 <> 0 AND goals1 <> -1 AND goals2 <> -1
            ORDER BY tournaments.date, tournaments_matches.id
        ");
        $elos = [];
        $initialElo = 1200;
        $k = 20;
        foreach ($matches as $match) {
            if (!isset($elos[$match->playerid1])) {
                $elos[$match->playerid1] = $initialElo;
            }
            if (!isset($elos[$match->playerid2])) {
                $elos[$match->playerid2] = $initialElo;
            }
            // https://de.wikipedia.org/wiki/Elo-Zahl
            if ($match->goals1 > $match->goals2) {
                $S_A = 1;
                $S_B = 0;
            } else if ($match->goals2 > $match->goals1) {
                $S_A = 0;
                $S_B = 1;
            } else {
                $S_A = 0.5;
                $S_B = 0.5;
            }
            $elo1 = $elos[$match->playerid1];
            $elo2 = $elos[$match->playerid2];
            $winProbability1 = getEloWinProbability($elo1, $elo2);
            $winProbability2 = (1 - $winProbability1);
            $newElo1 = $elo1 + ($k * ($S_A - $winProbability1));
            $newElo2 = $elo2 + ($k * ($S_B - $winProbability2));
            $elos[$match->playerid1] = $newElo1;
            $elos[$match->playerid2] = $newElo2;
        }
        return $elos;
    }

    function getEloWinProbability($elo1, $elo2) {
        return (1 / (1 + pow(10, ($elo2 - $elo1) / 400)));
    }
?>
