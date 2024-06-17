<?php
	require_once(__DIR__ . "/core/core.php");

	$db = new MySQLDatabase("db", 3306, "root", $_ENV["DB_ROOT_PASSWORD"], "fifa-champions");
	$db->getObjects_constructorParameter = '$row';
	$session = Session::getCurrentSession($db, "sessions", "fifa_champions");

	define("PARAMETER_PREFIX_ATTRIBUTE", "attribute_");
	define("ADMINISTRATION_PASSWORD", $_ENV["ADMIN_PASSWORD"]);
	setlocale(LC_TIME, "de_DE.UTF-8", "deu_deu");

	loadRows("administration_meta", $administrationMeta, "'key'", "key");
	loadRows("members", $members, "name");
	loadRows("locations", $locations, "name");
	loadRows("teams", $teams, "name");
	$tournamentTypes = array(
		"monthly_tournament"=>array(
			"title"=>"Monatsturnier",
			"description"=>"Fast jeden Monat spielen wir ein Turnier, in dem man zeigen kann, wer den Wanderpokal verdient hat."
		),
		"fun_tournament"=>array(
			"title"=>"Funturnier",
			"description"=>'Einmal im Jahr gibt es ein Fun-Turnier mit lustigen Regeln und <span style="font-style:italic;">etwas</span> Alkohol.'
		),
		"team_tournament"=>array(
			"title"=>"Teamturnier",
			"description"=>"Im Januar jedes Jahres findet das \"Team-Turnier\" statt,  bei dem 2 gegen 2 Spieler antreten."
		),
		"european_championship"=>array(
			"title"=>"Europameisterschaft",
			"description"=>"Die Europameisterschaft findet jährlich (im Juni oder Juli) abwechselnd mit der Weltmeisterschaft statt."
		),
		"world_championship"=>array(
			"title"=>"Weltmeisterschaft",
			"description"=>"Die Weltmeisterschaft findet jährlich (im Juni oder Juli) abwechselnd mit der Europameisterschaft statt."
		),
		"dfc_cup"=>array(
			"title"=>"DFC-Pokal",
			"description"=>"Einmal im Jahr (im Zeitraum von Januar bis Dezember) spielen wir um den Deutschen-Fifa-Champions-Pokal."
		),
		"champions_league"=>array(
			"title"=>"Champions League",
			"description"=>"Champions League (jährlich)"
		),
		"euro_league"=>array(
			"title"=>"Euro League",
			"description"=>"Euro League (jährlich)"
		)
	);
	$cupTournamentTypes = array("dfc_cup", "champions_league", "euro_league");
	$matchTypes = array(
		"group"=>"Gruppenphase",
		"eighthfinal"=>"Achtelfinale",
		"quarterfinal"=>"Viertelfinale",
		"semifinal"=>"Halbfinale",
		"final"=>"Finale"
	);
	$groupNames = array("A","B","C","D","E","F","G","H");
	$koRounds = array(8=>"eighthfinal",4=>"quarterfinal",2=>"semifinal",1=>"final");
	$awardTypes = array(
		"player"=>"Bester Spieler",
		"goal"=>"Tor des Jahres",
		"newcomer"=>"Bester Newcomer",
		"surprise"=>"Überraschung des Jahres",
		"special_achievement"=>"Besondere Leistung",
		"fighter"=>"Kämpfer des Jahres",
		"loyalty"=>"Treue Award",
		"golden_shoe"=>"Goldener Schuh",
		"player_of_the_century"=>"Spieler des Jahrzents"
	);

	class RankingDiagram {

		public function __construct($playerID=false) {
			$this->playerID = $playerID;
			$this->axisX_Title = false;
			$this->axisY_Title = false;
			$this->axisX_Text = false;
			$this->axisY_Text = false;
			$this->displayLegend = false;
		}

		public function generateHTML($elementID) {
			global $db;
			global $members;
			$html = '
				<script type="text/javascript" src="https://www.google.com/jsapi"></script>
				<script type="text/javascript">
			' . "
					$(window).load(function() {
						google.load('visualization', '1', {
							packages: ['corechart', 'line'],
					        callback: function() {
								var data = new google.visualization.DataTable();
								data.addColumn('date', 'Datum');
			";

			$rankings = $db->getRows("rankings", "*", false, "date DESC");
			$displayedPlayerIDs = array();
			foreach ($rankings as $i=>$ranking) {
				$playerRankings = $db->getRows("rankings_players", array("playerid","rank"), array("rankingid"=>$ranking->id), "`rank`");
				if (!$displayedPlayerIDs) {
					$maximumRank = count($playerRankings);
					if ($this->playerID) {
						$displayedPlayerIDs[] = $this->playerID;
					} else {
						foreach ($playerRankings as $playerRanking) {
							$displayedPlayerIDs[] = $playerRanking->playerid;
						}
					}
				}
				if ($i == 0) {
					foreach ($displayedPlayerIDs as $playerID) {
						$html .= "data.addColumn('number', '" . $members[$playerID]->name . "');";
					}
					$html .= "data.addRows([";
				} else {
					$html .= ", ";
				}
				$html .= "[new Date(" . ($ranking->date * 1000) . ")";
				$r = 0;
				foreach ($displayedPlayerIDs as $playerID) {
					$rank = $maximumRank;
					foreach ($playerRankings as $playerRanking) {
						if ($playerRanking->playerid == $playerID) {
							$rank = $playerRanking->rank;
							break;
						}
					}
					$html .= ", " . $rank;
					$r++;
				}
				$html .= "]";
			}
			$html .= "]);
			";
			$options = array(
				"hAxis"=>array(),
				"vAxis"=>array(
					"viewWindow"=>array("min"=>0.5,"max"=>$maximumRank),
					"direction"=>-1
				)
			);
			if ($this->axisX_Title) {
				$options["hAxis"]["title"] = "'" . $this->axisX_Title . "'";
			}
			if ($this->axisY_Title) {
				$options["vAxis"]["title"] = "'" . $this->axisY_Title . "'";
			}
			if (!$this->axisX_Text) {
				$options["hAxis"]["textPosition"] = "'none'";
			}
			if (!$this->axisY_Text) {
				$options["vAxis"]["textPosition"] = "'none'";
			}
			if (!$this->displayLegend) {
				$options["legend"] = array("position"=>"'none'");
			}
			$html .= "
								var options = " . self::getOptionsJS($options) . ";
								var chart = new google.visualization.LineChart(document.getElementById('" . $elementID . "'));
								chart.draw(data, options);
							}
						});
					});
				</script>
			";
			return $html;
		}

		private static function getOptionsJS($options) {
			if (is_array($options)) {
				$js = "{";
				$i = 0;
				foreach ($options as $optionKey=>$optionValue) {
					if ($i != 0) {
						$js .= ", ";
					}
					$js .= $optionKey . ": " . self::getOptionsJS($optionValue);
					$i++;
				}
				$js .= "}";
			} else {
				$js = $options;
			}
			return $js;
		}
	}

	function loadRows($table, &$array, $order="id", $indexColumn="id") {
		global $db;
		$array = array();
		$rows = $db->getRows($table, "*", false, $order);
		foreach ($rows as $row) {
			$array[$row->$indexColumn] = $row;
		}
	}

	function getBestMatch($memberID, $winOrLoss, $highestOrLowest) {
		global $db;
		$match1 = $db->sendQuery("
			SELECT * FROM tournaments_matches
			WHERE ((playerid1 = " . $memberID . ") AND (goals1 " . ($winOrLoss?">":"<") . " goals2))
			ORDER BY (goals" . ($winOrLoss?1:2) . " - goals" . ($winOrLoss?2:1) . ") " . ($highestOrLowest?"DESC":"ASC") . " LIMIT 1
		");
		$match2 = $db->sendQuery("
			SELECT * FROM tournaments_matches
			WHERE ((playerid2 = " . $memberID . ") AND (goals2 " . ($winOrLoss?">":"<") . " goals1))
			ORDER BY (goals" . ($winOrLoss?2:1) . " - goals" . ($winOrLoss?1:2) . ") " . ($highestOrLowest?"DESC":"ASC") . " LIMIT 1
		");
		$match = false;
		if ($match1 && $match2) {
			$difference1 = ($winOrLoss ? ($match1->goals1 - $match1->goals2) : ($match1->goals2 - $match1->goals1));
			$difference2 = ($winOrLoss ? ($match2->goals2 - $match2->goals1) : ($match2->goals1 - $match2->goals2));
			$match = ((($difference1 > $difference2) == $highestOrLowest)?$match1:$match2);
		} else if ($match1) {
			$match = $match1;
		} else if ($match2) {
			$match = $match2;
		}
		return $match;
	}

	function getMemberTexts($text) {
		global $members;
		$texts = explode("\n", $text);
		$memberTexts = array();
		foreach ($texts as $playerText) {
			$playerID = -1;
			$playerText = trim($playerText);
			if (!$playerText) {
				$playerText = false;
			}
			$matches = array();
			foreach ($members as $member) {
				if ($member->name == $playerText) {
					$playerID = $member->id;
					$playerText = "";
					break;
				} else if (preg_match("/^" . $member->name . ":(.*)/", $playerText, $matches)) {
					$playerID = $member->id;
					$playerText = trim($matches[1]);
					break;
				}
			}
			if ($playerText !== false) {
				$memberTexts[] = array("playerID"=>$playerID,"text"=>$playerText);
			}
		}
		return $memberTexts;
	}

	function calculateTournamentInformation($tournamentID) {
		global $db;
		global $matchTypes;
		$matches = $db->getRows("tournaments_matches", "*", array("tournamentid"=>$tournamentID), "id");
		$rounds = array();
		foreach ($matchTypes as $matchType=>$matchTypeName) {
			$rounds[$matchType] = array();
		}
		foreach ($matches as $match) {
			$rounds[$match->type][] = $match;
		}
		return array("matches"=>$matches,"rounds"=>$rounds,"groups"=>calculateGroups($matches));
	}

	function calculateGroups($matches) {
		$groups = array();
		foreach ($matches as $match) {
			if ($match->type == "group") {
				$groupIndex = -1;
				foreach ($groups as $i=>$group) {
					foreach ($group["players"] as $playerID) {
						if (($match->playerid1 == $playerID) || ($match->playerid2 == $playerID)) {
							$groupIndex = $i;
							break;
						}
					}
					if ($groupIndex != -1) {
						break;
					}
				}
				if ($groupIndex == -1) {
					$groupIndex = count($groups);
					$groups[] = array("players"=>array(),"matches"=>array());
				}
				if (!in_array($match->playerid1, $groups[$groupIndex]["players"])) {
					$groups[$groupIndex]["players"][] = $match->playerid1;
				}
				if (!in_array($match->playerid2, $groups[$groupIndex]["players"])) {
					$groups[$groupIndex]["players"][] = $match->playerid2;
				}
				$groups[$groupIndex]["matches"][] = $match;
			}
		}
		foreach ($groups as $i=>$group) {
			$groups[$i]["results"] = calculateGroupResults($group);
		}
		return $groups;
	}

	function calculateGroupResults($group) {
		global $members;
		$results = array();
		foreach ($group["players"] as $playerID) {
			$results[$playerID] = array("member"=>$members[$playerID],"games"=>0,"wins"=>0,"draws"=>0,"losses"=>0,"goals_scored"=>0,"goals_obtained"=>0,"points"=>0);
		}
		foreach ($group["matches"] as $match) {
			if (($match->goals1 != -1) && ($match->goals2 != -1)) {
				$results[$match->playerid1]["games"]++;
				$results[$match->playerid2]["games"]++;
				$results[$match->playerid1]["goals_scored"] += $match->goals1;
				$results[$match->playerid2]["goals_scored"] += $match->goals2;
				$results[$match->playerid1]["goals_obtained"] += $match->goals2;
				$results[$match->playerid2]["goals_obtained"] += $match->goals1;
				if ($match->goals1 > $match->goals2) {
					$results[$match->playerid1]["wins"]++;
					$results[$match->playerid2]["losses"]++;
					$results[$match->playerid1]["points"] += 3;
				} else if ($match->goals1 < $match->goals2) {
					$results[$match->playerid2]["wins"]++;
					$results[$match->playerid1]["losses"]++;
					$results[$match->playerid2]["points"] += 3;
				} else {
					$results[$match->playerid1]["draws"]++;
					$results[$match->playerid2]["draws"]++;
					$results[$match->playerid1]["points"] += 1;
					$results[$match->playerid2]["points"] += 1;
				}
			}
		}
		foreach ($results as $playerID=>$result) {
			$results[$playerID]["goals_difference"] = ($result["goals_scored"] - $result["goals_obtained"]);
		}
		usort($results, "sortTournamentResults");
		return $results;
	}

	function sortTournamentResults($result1, $result2) {
		return sortTournamentResults_Keys($result1, $result2, array("points","goals_difference","goals_scored","wins"));
	}

	function sortTournamentResults_Keys($result1, $result2, $keys, $keyIndex=0) {
		if ($keyIndex < count($keys)) {
			if ($result1[$keys[$keyIndex]] == $result2[$keys[$keyIndex]]) {
				return sortTournamentResults_Keys($result1, $result2, $keys, $keyIndex + 1);
			}
			return (($result1[$keys[$keyIndex]] > $result2[$keys[$keyIndex]])?-1:1);
		}
		return 0;
	}

	function createMetaArray() {
		return array("news"=>array(),"match"=>array(),"goal"=>array(),"scorer"=>array(),"action"=>array(),"scandal"=>array(),"quote"=>array());
	}

	function getMemberIcon($memberID, $size=32) {
		$memberIconsPath = "./images/members/";
		$sizeSuffix = ($size?"_" . $size:"");
		$path = ($memberIconsPath . $memberID . $sizeSuffix . ".png");
		if (file_exists($path)) {
			return $path;
		}
		return ($memberIconsPath . "icon" . $sizeSuffix . ".png");
	}

	function linkToMessage($title, $description=false) {
		linkTo("message.php?title=" . urlencode($title) . ($description ? "&description=" . urlencode($description) : ""));
	}

	function escapeHTML($text) {
		$text = htmlspecialchars($text);
		$charsToReplace = array();
		foreach ($charsToReplace as $search=>$replace) {
			$text = str_replace($search, $replace, $text);
		}
		$text = nl2br($text);
		return $text;
	}

	function head($title, $selectedMenuIndex=false) {
		global $db;
		global $administrationMeta;
		global $members;
		$isStartPage = ($selectedMenuIndex === 0);
		$head = '
			<!DOCTYPE HTML>
			<html>
				<head>
					<title>' . $title . '</title>
					<meta charset="utf-8">
					<link rel="stylesheet" href="css/5grid/core.css">
					<link rel="stylesheet" href="css/5grid/core-desktop.css">
					<link rel="stylesheet" href="css/5grid/core-1200px.css">
					<link rel="stylesheet" href="css/5grid/core-noscript.css">
					<link rel="stylesheet" href="css/style.css">
					<link rel="stylesheet" href="css/style-desktop.css">
					<script src="css/5grid/jquery.js"></script>
					<script src="css/5grid/init.js?use=mobile,desktop,1000px&amp;mobileUI=1&amp;mobileUI.theme=none"></script>
					<!--[if IE 9]>
						<link rel="stylesheet" href="css/style-ie9.css">
					<![endif]-->
					<link rel="stylesheet" href="css/custom.css">
					<script type="text/javascript">
						function submitForm(formID) {
							document.forms[formID].submit();
						}
						
						$(document).ready(function() {
							$(\'#menu_item_2\').hover(
								function() {
									$(\'ul#tournaments_dropdown\').slideDown(\'medium\');
						  		},
						  		function() {
									$(\'ul#tournaments_dropdown\').slideUp(\'medium\');
						  		}
							);
						});
					</script>
				</head>
				<body>
					<div id="header-wrapper">
						<header id="header">
							<div class="5grid-layout">
								<div class="row">
									<div class="4u" id="logo">
										<h1><a href="index.php" class="mobileUI-site-name">FIFA-Champions</a></h1>
									</div>
									<div class="8u" id="menu">
										<nav class="mobileUI-site-nav">
											<ul>
												' . getMenuListItems($selectedMenuIndex) . '
											</ul>
										</nav>
									</div>
								</div>
							</div>
						</header>
		';
		if ($isStartPage) {
			$head .= '
						<div id="banner" class="5grid-layout">
							<div class="row">
								<div class="12u">
									<section id="banner_text">
										<h2>Die ' . count($members) . ' besten FIFA-Spieler<br/>der Welt in einer Gruppe!</h2>
										<p>Fast jeden Monat spielen wir ein Turnier, in dem man zeigen kann, wer den Wanderpokal verdient hat. Einmal im Jahr spielen wir um den DFC-Pokal, die abwechselnde Europa- und Weltmeisterschaft und ein Fun-Turnier mit lustigen Regeln und <span style="font-style:italic;">etwas</span> Alkohol.</p>
									</section>
			';
			$homeMessage = $administrationMeta["home_message"]->value;
			if ($homeMessage) {
				$head .= '	
						<section id="home_message">
							<p>' . escapeHTML($homeMessage) . '</p>
						</section>
				';
			}
			$head .= '
								</div>
							</div>
						</div>
			';
		}
		$head .= '
					</div>
		';
		if ($isStartPage) {
			$tournamentsCount = $db->getRowCount("tournaments");
			$matchesCount = $db->getRowCount("tournaments_matches", "(goals1 <> -1) AND (goals2 <> -1)");
			$goalsCountRow = $db->sendQuery("SELECT SUM(goals1) as goals1, SUM(goals2) as goals2 FROM tournaments_matches WHERE (goals1 <> -1) AND (goals2 <> -1)");
			$goalsCount = ($goalsCountRow->goals1 + $goalsCountRow->goals2);
			$head .= '
					<style>
						#copyright{
							border-top: none;
						}
					</style>
					<div id="featured-wrapper">
						<div id="featured-content" class="5grid-layout">
							<div class="row">
								<div class="4u">
									<section>
										<h2>Detaillierte Turnierberichte</h2>
										<div class="features_icon_wrapper"><img src="./css/images/featured-icon01.png" width="91" height="68"/></div>
										<p>Jedes unserer ' . $tournamentsCount . ' Turniere wurde genauestens dokumentiert. Wie ging das Topspiel aus? Wer schoss das schönste Tor? Was war DER Spruch des Abends?</p>
										<a href="tournaments.php" class="button button-style1">Mehr dazu</a>
									</section>
								</div>
								<div class="4u">
									<section>
										<h2>Spielerprofile</h2>
										<div class="features_icon_wrapper"><img src="./css/images/featured-icon02.png" width="91" height="68"/></div>
										<p>Vom Neuzugang bis zum Jahrhundert-<br/>stürmer: Hier finden sich alle Mitglieder der FIFA-Champions, ihre Erfolge, Niederlagen und Geschichten!</p>
										<a href="members.php" class="button button-style1">Mehr dazu</a>
									</section>
								</div>
								<div class="4u">
									<section>
										<h2>Auswertungen & Fakten</h2>
										<div class="features_icon_wrapper"><img src="./css/images/featured-icon03.png" width="91" height="68"/></div>
										<p>Harte Zahlen: ' . $matchesCount . ' Spiele, ' . $goalsCount . ' Tore und eine stets aktuelle Weltrangliste bieten genug Daten für die Analyse des nächsten Turniergegners!</p>
										<a href="rankings.php" class="button button-style1">Mehr dazu</a>
									</section>
								</div>
							</div>
						</div>
					</div>
			';
		}
		$head .= '
					<div id="wrapper" class="5grid-layout"' . ($isStartPage ? ' style="padding:0px;"' : '') . '>
						<div id="page" class="row">
		';
		header("Content-Type:text/html; charset=utf-8");
    	echo $head;
	}

	function footer() {
		global $session;
		$footer = '
						</div>
					</div>
					<div class="5grid-layout" id="copyright">
						<div class="row">
							<div class="12u">
								<section>
									<p>FIFA-Champions &copy; ' . date("Y") . ' | ';
		if ($session->user) {
			$footer .= '<a href="administration.php">Administration</a> | <a href="edit.php?action=logout">Abmelden</a>';
		} else {
			$footer .= '<a href="login.php">Anmelden</a>';
		}
		$footer .= '</p>
								</section>
							</div>
						</div>
					</div>
				</body>
			</html>
		';
		echo $footer;
	}

	function getMenuListItems($selectedMenuIndex=false) {
		global $db;
		global $tournamentTypes;
		$html = "";
		$currentRankingID = $db->getValue("rankings", "id", false, "date DESC");
		$menuEntries = array(array("link"=>"index.php","title"=>"Startseite"),
							 array("link"=>"members.php","title"=>"Mitglieder"),
							 array("link"=>"tournaments.php","title"=>"Turniere"),
							 array("link"=>"ranking.php?id=" . $currentRankingID,"title"=>"Weltrangliste"),
							 array("link"=>"other.php","title"=>"Sonstiges"));
		foreach ($menuEntries as $i=>$menuEntry) {
			$html .= '
				<li id="menu_item_' . $i . '" class="menu_item' . (($i === $selectedMenuIndex) ? ' current_page_item' : '') . '">
					<a ' . ($menuEntry["link"] ? 'href="' . $menuEntry["link"] . '"' : 'href="#" onclick="return false;"') . '>' . $menuEntry["title"] . '</a>
			';
			if ($i == 2) {
				$html .= '
					<div style="position:absolute; margin-top:38px; z-index:999;">
						<ul id="tournaments_dropdown">
							<li style="border-top:none;"><a href="tournaments.php">Alle Turniere</a></li>
				';
				foreach ($tournamentTypes as $type=>$tournamentInfo) {
					$html .= '
						<li><a href="tournaments.php?type=' . $type . '">' . $tournamentInfo["title"] . '</a></li>
					';
				}
				$html .= '
						</ul>
					</div>
				';
			}
			$html .= '
				</li>
			';
		}
		return $html;
	}
?>
