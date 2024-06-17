<?php
	require_once("core.php");
	if (!$session->user) {
		linkToMessage("Kein Zugriff", "Du musst angemeldet sein, um diese Seite betrachten zu können.");
	}
	head("Administration");
	
	echo '
		<h1 id="page_title">Administration</h1>
		<form id="edit_administration_form" action="edit.php?action=editAdministration" method="post">
			<ul id="tournaments_list" class="style2">
				<li>
					<span class="tournaments_list_label">Startseiten-Nachricht</span>
					<textarea name="meta_home_message">' . $administrationMeta["home_message"]->value . '</textarea>
				</li>
				<li style="padding-bottom:0px;"></li>
			</ul>
			<a href="javascript:submitForm(\'edit_administration_form\')" class="button button-style1">Änderungen speichern</a>
		</form>
	';
	
	footer();
?>