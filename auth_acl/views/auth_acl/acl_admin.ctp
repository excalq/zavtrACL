<?php

	//Load the jQuery core
	$javascript->link('/auth_acl/js/jquery.min.js', false);

    //and now... some file that will be specific to this view (page)
    $javascript->link('/auth_acl/js/acl_admin.js', false);
	
	// Plugin stylesheet
	echo $html->css('/auth_acl/css/auth_acl_styles.css');

	$session->flash(); // this line displays our flash messages
?>

<?php
	
echo "<h2>ACL Administration Panel</h2>";

echo "<p><img src=\"/auth_acl/img/users.png\" /> ".$html->link('User Administration', array('controller' => 'auth_acl' ,'action' => 'user_admin'))."</p>";

echo "<h3>ACL Rules</h3>";


// Build "Groups" select menu.
// Include wildcard option
$groups_wildcard_opt = array('*' => 'Any Group (*)');
$groups_nowildcard = $groups_list;
//$groups_wildcard = array_merge($groups_wildcard_opt, $groups_list);
$groups_wildcard = ($groups_wildcard_opt + $groups_list); // Alternative to array_merge(), but keeps indexes

// Build "Controllers" select menu.
// This is in the format array(controllers => controllers) (since option values must be valid names, not ids)
// Because of this, we need to extract just names from the original data array.
$controllers_wildcard = array('*' => 'Any Controller (*)');
$controllers_list = array_combine(array_keys($controllers_actions), array_keys($controllers_actions));
$controllers = ($controllers_wildcard + $controllers_list); // Alternative to array_merge(), but keeps indexes

// Actions select menu is built with AJAX


// This select list gets populated by AJAX
$actions = array(); 


// Add ACL records
echo "<fieldset>\n";
	echo "<legend>Add ACL Record</legend>\n";
	echo "<p>Tips:
			<ul>
				<li>Rules are applied in order of specific detail.</li>
				<li>A rule stating a specific user, controller, and action takes precedence over a more generic contrary rule.</li>
				<li>Create rules allowing access to admins and access to login/logout before adding restrictive rules!</li>
			</ul>
			</p>";
	echo $form->create(null, array('name' => 'acl_add', 'class' => 'acl_add', 'action' => 'acl_admin'));
		echo $form->hidden('form.acl_admin_task', array('value' => 'add_acl_rule'));
		echo $form->label('group', 'Select Group');
		echo $form->select('group', $groups_wildcard, null, null, false);
		
		echo $form->label('controller', 'Select Controller');
		
		echo $form->select('controller', $controllers, null, null, false);
		
		// This hidden div is shown when AJAX data is received
		echo '<div id="AuthACLActionDiv" style="display: none; margin: 0; padding: 0;">';
			echo $form->label('action', 'Select Action');
			echo $form->select('action', $actions);
		echo '</div>';
		
		echo $form->input('description', array('label' => 'Basic Description of ACL rule'));
		
		echo $form->label('permission');
		echo "<div class=\"permissionRadios\">";
			echo $form->radio('permission', array('allow' => 'allow', 'deny' => 'deny'), array('legend' => false, 'value' => 'allow'));
		echo "</div>";
	
	echo $form->end('Add Rule');
echo "</fieldset>\n";






// List of existing ACL records
echo "<fieldset>\n";
echo "<legend>ACL Record</legend>\n";

echo "<p>These are the current ACL records. They are in order from general to most specific. Rule precedence follows that order.</p>";
echo "<table class=\"records_table\" callpadding=\"0\" cellspacing=\"0\">";
echo "<tr>
		<th>id</th>
		<th>Group</th>
		<th>Controller</th>
		<th>Action</th>
		<th>Allow/Deny</th>
		<th>Description</th>
		<th>Delete</th>
	  </tr>";
	  
if (!empty($acl_records)) {
	foreach ($acl_records as $record) {
		
		$group_id = $record['AuthAcl'][$group_fkey];
		
		if ($group_id != '*') {
			$record['AuthAcl'][$group_fkey] = $groups_list[$group_id]; // Translate group id to group name
		}
		
		echo "<tr>";
			echo "<td>{$record['AuthAcl']['id']}</td>";
			echo "<td>{$record['AuthAcl'][$group_fkey]}</td>";
			echo "<td>{$record['AuthAcl']['controller']}</td>";
			echo "<td>{$record['AuthAcl']['action']}</td>";
			echo "<td>{$record['AuthAcl']['permission']}</td>";
			echo "<td>{$record['AuthAcl']['description']}</td>";
			echo "<td>". $html->link($html->image("/auth_acl/img/delete.png", array('title' => 'Delete', 'alt' => 'Del'))." ",
									 array('action' => 'delete_item', 'acl_rule', $record['AuthAcl']['id'], $delete_key),
									 false,
									 'Confirm deletion of this ACL record?',
									 false);
			
			echo $html->link("Delete", array('action' => 'delete_item' , 'acl_rule', $record['AuthAcl']['id'], $delete_key), false, 'Confirm deletion of this ACL record?', false) . "</td>";
		echo "</tr>";
	}
} else {
	echo "<tr><td colspan=\"6\">No ALC Records have been created.</td></tr>";
}

echo "</table><br />";

echo "</fieldset>\n";
?>