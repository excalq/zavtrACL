<?php

class AuthAcl extends AppModel {

	var $name = 'AuthAcl';
	
	var $validate = array(
		'name' => array('notempty'),
	);
	
	var $HasMany = array(
		'AuthGroup' => array(
			'className' => 'AuthGroup',
			'foreignKey' => 'auth_group_id',
			'dependent' => false
		)
	);

}
?>