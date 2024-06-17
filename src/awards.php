<?php
	require_once("core.php");
	head("Awards");
	
	$type = getURLParameter("type");
	$tournaments = $db->getRows("tournaments", "*", ($type?array("type"=>$type) : false), "date DESC");
	echo '
		<h1 id="page_title">FC Awards</h1>
		<ul class="awards_years_list" "class="style2">
	';
	$yearRows = $db->sendQuery("SELECT year FROM awards GROUP BY year ORDER BY year DESC");
	foreach ($yearRows as $yearRow) {
		echo '
			<li>
				<span class="awards_years_list_year">' . $yearRow->year . '</span>
				<div class="awards">
		';
		$awards = $db->getRows("awards", "*", array("year"=>$yearRow->year));
		foreach ($awards as $award) {
			echo '
				<div class="award">
					<img class="award_icon" src="./images/award_100.png"/><br/>
					<span class="award_title">' . escapeHTML($awardTypes[$award->type]) . '</span><br/>
					<a class="award_member_name" href="member.php?id=' . $award->memberid . '">' . escapeHTML($members[$award->memberid]->name) . '</a>
					<img class="award_member_icon" src="' . getMemberIcon($award->memberid, 32) . '"/>
				</div>
			';
		}
		echo '
				</div>
			</li>
		';
	}
	echo '
			<li style="padding-bottom:0px;"></li>
		</ul>
	';
	
	footer();
?>