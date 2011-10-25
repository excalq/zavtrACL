<?php

class Zavtracl extends AppModel {

	var $name = 'Zavtracl';
	
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
