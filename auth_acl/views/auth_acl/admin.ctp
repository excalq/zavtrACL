<?php

// Plugin stylesheet
echo $html->css('/auth_acl/css/auth_acl_styles.css');


?>

<h3>ACL Administration Panel</h3>


<div id="id">
<?php
	echo "<p><img src=\"/auth_acl/img/users.png\" /> ".$html->link('User Administration', array('controller' => 'auth_acl' ,'action' => 'user_admin'))."</p>";
	echo "<p><img src=\"/auth_acl/img/acl.png\" /> ".$html->link('ACL Rule Administration', array('controller' => 'auth_acl' ,'action' => 'acl_admin'))."</p>";
?>
</div>