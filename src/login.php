<?php
	require_once("core.php");
	head("Login");
	
	echo '
		<form id="login_form" class="password_page" action="edit.php?action=login" method="post">
			<h1 id="page_title">Anmelden</h1>
			<input class="password_page_input" type="password" name="password"/>
			<a href="javascript:submitForm(\'login_form\')" class="button button-style1">Einloggen</a>
		</form>
	';
	
	footer();
?>