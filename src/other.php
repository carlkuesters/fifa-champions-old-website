<?php
	require_once("core.php");
	head("Sonstiges", 4);

	echo '
		<h1 id="page_title">Sonstiges</h1>
		<div id="other">
			<a href="records.php" class="button button-style1">Rekorde</a><br/>
			<a href="awards.php" class="button button-style1">FC Awards</a><br/>
			<a href="quote.php?random=1" class="button button-style1">Zufälliger Spruch</a><br/>
			<a href="fact.php?random=1" class="button button-style1">Interessanter Fakt</a>
		</div>
	';

	footer();
?>