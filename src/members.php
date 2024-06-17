<?php
	require_once("core.php");
	head("Mitglieder", 1);

	echo '
		<h1 id="page_title" style="margin-bottom:15px;">Mitglieder (' . count($members) . ')</h1>
	';
	$isEditMode = getURLParameter("edit");
	if ($isEditMode) {
		echo '
			<form id="edit_members_form" action="edit.php?action=editMembers" method="post">
		';
	}
	echo '
		<ul id="members_list">
	';
	foreach ($members as $member) {
		echo '
			<li>
				<table>
					<tr>
						<td style="width:100px;"><a href="member.php?id=' . $member->id . '"><img src="' . getMemberIcon($member->id, 80) . '" class="members_icon"/></a></td>
						<td>
							<a href="member.php?id=' . $member->id . '" class="members_name">' . escapeHTML($member->name) . '</a><br/>
		';
		if ($isEditMode) {
			echo '
							<textarea name="description_' . $member->id . '">' . $member->description . '</textarea>
			';
		} else {
			echo '
							<p class="members_description">' . ($member->description ? escapeHTML($member->description) : '<span style="font-style:italic;">Keine Beschreibung verfügbar</span>') . '</p>
			';
		}
		echo '			
						</td>
					</tr>
				</table>
			</li>
		';
	}
	if ($isEditMode) {
		echo '
				<a href="javascript:submitForm(\'edit_members_form\')" class="button button-style1">Änderungen speichern</a>
			</form>
		';
	} else if ($session->user) {
		echo '
			<a href="members.php?&edit=1" class="button button-style1">Bearbeiten</a>
		';
	}

	footer();
?>