<?php

//Load the jQuery core
$javascript->link('/auth_acl/js/jquery.min.js', false);

//and now... some file that will be specific to this view (page)
$javascript->link('/auth_acl/js/acl_admin.js', false);

// Plugin stylesheet
echo $html->css('/auth_acl/css/auth_acl_styles.css');

$session->flash(); // this line displays our flash messages
	

echo "<h2>ACL Administration Panel</h2>";

echo "<p><img src=\"/auth_acl/img/acl.png\" /> ".$html->link('ACL Rule Administration', array('controller' => 'auth_acl' ,'action' => 'acl_admin'))."</p>";

echo "<h3>Users and Groups</h3>";

// Build "Groups" select menu.
// Include wildcard option
$groups_wildcard_opt = array('*' => 'Any Group (*)');
$groups_nowildcard = $groups;
$groups_wildcard = array_merge($groups_wildcard_opt, $groups);

// Add users and groups
echo "<fieldset>\n";
echo "<legend>Add Users and Groups</legend>\n";

	echo "<fieldset>\n";
		echo "<legend>Add New User</legend>\n";
		echo $form->create(null, array('name' => 'acl_add_user', 'class' => 'acl_user_add', 'action' => 'user_admin')); // model has to be null to avoid form action having wrong url
			echo $form->hidden('form.acl_admin_task', array('value' => 'add_user'));
			echo $form->input($user_model.'.username', array('autocomplete' => 'off')); // Prevent browser from populating with logged in user data
			echo $form->input($user_model.'.password', array('autocomplete' => 'off'));
			echo $form->input($user_model.'.email');
			echo "<div class=\"input select\">";
			echo $form->label($user_model.'.auth_group_id', 'Group');
			echo $form->select($user_model.'.auth_group_id', $groups_nowildcard, null);
			echo "</div>";
		echo $form->end('Add User');
	echo "</fieldset>\n";

	echo "<fieldset>\n";
		echo "<legend>Add New Group</legend>\n";
		echo $form->create(null, array('name' => 'acl_add_group', 'class' => 'acl_user_add', 'action' => 'user_admin'));
			echo $form->hidden('form.acl_admin_task', array('value' => 'add_group'));
			echo $form->input('Group.name');
			echo $form->input('Group.description');
		echo $form->end('Add Group');
	echo "</fieldset>\n";
echo "</fieldset>\n";


// Existing Users
echo "<fieldset>\n";
	echo "<legend>Existing Users</legend>\n";
	echo "<p>These are the current ACL records. They are in order from general to most specific. Rule precedence follows that order.</p>";
	
	echo $form->create(null, array('name' => 'acl_mod_user', 'class' => 'acl_user_add', 'action' => 'user_admin'));
		echo $form->hidden('form.acl_admin_task', array('value' => 'update_users'));
		echo "<table class=\"records_table\" callpadding=\"0\" cellspacing=\"0\">";
		echo "<tr>
				<th>id</th>
				<th>User</th>
				<th>Group</th>
				<th>Delete</th>
				<th>Reset Pw</th>
			  </tr>";
		
		if (!empty($users)) {
			foreach ($users as $user_record) {
				$user_id = $user_record[$user_model]['id'];
				$username = $user_record[$user_model]['username'];
				$group_id = $user_record[$group_model]['id'];
				
				echo "<tr>";
					echo "<td>{$user_id}</td>";
					echo "<td>{$username}</td>";
					echo "<td>";
					
					echo $form->hidden("$user_model.$user_id.id", array('value' => $user_id));
					echo $form->select("$user_model.$user_id.$group_fkey", $groups_nowildcard, $group_id); // User.id.auth_group_id field
					
					echo "</td>";
					
					echo "<td>". $html->link($html->image("/auth_acl/img/delete.png", array('title' => 'Delete', 'alt' => 'Del'))." ",
											 array('action' => 'delete_item', 'user', $user_record[$user_model]['id'], $delete_key),
											 false,
											 'Confirm deletion of this ACL record?',
											 false);
					
					echo $html->link("Delete", array('action' => 'delete_item', 'user', $user_record[$user_model]['id'], $delete_key), false, 'Confirm deletion of this ACL record?', false) . "</td>";
					
					// Warning: This is insecure! It is meant only as a measure to temporarily reset a user's password. However, it passes it via the URL!
					echo "<td>". $html->link($html->image("/auth_acl/img/reset-pw.png", array('title' => 'Reset Password', 'alt' => 'ResetPw'))." ",
											 "javascript:var temppw=prompt('Set the new temporary password:'); window.location.href = '/auth_acl/reset_password/{$user_id}/'+temppw+'/{$delete_key}';",
											 false,
											 null,
											 false);
					
					echo $html->link("Reset Password",
									 "javascript:var temppw=prompt('Set the new temporary password:'); window.location.href = '/auth_acl/reset_password/{$user_id}/'+temppw+'/{$delete_key}';",
									 false,
									 null,
									 false);
					echo "</td>";
					
					
					
				echo "</tr>";
			}
		} else {
			echo "<tr><td colspan=\"6\">No User Records have been created.</td></tr>";
		}
		
		echo "</table><br />";
	echo $form->end('Update Users');

echo "</fieldset>\n";


?>