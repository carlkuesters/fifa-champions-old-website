<?php
	require_once("core.php");
	head("Rangliste erstellen", 2);
	
	echo '
		<h1 id="page_title">Weltrangliste erstellen</h1>
		<form id="create_ranking_form" action="edit.php?action=createRanking" method="post">
			<ul id="tournaments_list" class="style2">
				<li>
					<span class="tournaments_list_label">Datum</span>
					<input type="text" name="date" value="' . strftime("%d.%m.%Y", time()) . '"/>
				</li>
				<li>
					<span class="tournaments_list_label">Textform<br/>[Spieler1: Text1,<br/>&nbsp;Spieler2: Text2, ...]</span>
					<textarea name="text"></textarea>
				</li>
				<li style="padding-bottom:0px;"></li>
			</ul>
			<a href="javascript:submitForm(\'create_ranking_form\')" class="button button-style1">Erstellen</a>
		</form>
	';
	
	footer();
?>