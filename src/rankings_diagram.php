<?php
	require_once("core.php");
	head("Ranglisten-Diagramm", 2);
	
	echo '
		<h1 id="page_title">Ranglisten-Diagramm</h1>
		<div id="chart" style="width:100%; height:500px;"/>
	';
	$rankingDiagram = new RankingDiagram();
	$rankingDiagram->axisX_Title = "Zeit";
	$rankingDiagram->axisY_Title = "Platzierung";
	$rankingDiagram->axisX_Text = true;
	$rankingDiagram->displayLegend = true;
	echo $rankingDiagram->generateHTML("chart");
	
	footer();
?>