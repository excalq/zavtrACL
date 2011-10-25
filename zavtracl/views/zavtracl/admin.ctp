<?php

// Plugin stylesheet
echo $html->css('/zaftracl/css/auth_acl_styles.css');


?>

<h3>ACL Administration Panel</h3>


<div id="id">
<?php
	echo "<p><img src=\"/zaftracl/img/users.png\" /> ".$html->link('User Administration', array('controller' => 'auth_acl' ,'action' => 'user_admin'))."</p>";
	echo "<p><img src=\"/zaftracl/img/acl.png\" /> ".$html->link('ACL Rule Administration', array('controller' => 'auth_acl' ,'action' => 'acl_admin'))."</p>";
?>
</div>