<?php

//Load the jQuery core
$javascript->link('/auth_acl/js/jquery.min.js', false);

//and now... some file that will be specific to this view (page)
$javascript->link('/auth_acl/js/acl_admin.js', false);

// Plugin stylesheet
echo $html->css('/auth_acl/css/auth_acl_styles.css');

$session->flash(); // this line displays our flash messages
	
if ($show_homepage_link) {
	echo "<p><a href=\"/\">Return to Application Homepage</a></p>";
}

echo "<fieldset>\n";
	echo "<legend>Change User Password</legend>";
	echo "<p>Please enter your old and new passwords to complete the change.</p>";
	
	echo $form->create(null, array('name' => 'acl_mod_user', 'class' => 'acl_add', 'action' => 'change_password'));
		echo $form->hidden('form.acl_admin_task', array('value' => 'change_password'));
		echo $form->label('username', 'For user: '.$username);
		echo $form->input('old_passwd', array('type' => 'password', 'label' => 'Current Password', 'autocomplete' => 'off'));
		echo $form->input('new_passwd1', array('type' => 'password', 'label' => 'New Password', 'autocomplete' => 'off'));
		echo $form->input('new_passwd2', array('type' => 'password', 'label' => 'Retype New Password', 'autocomplete' => 'off'));
		
	echo $form->end('Change Password');

echo "</fieldset>\n";


?>