<?php
// AuthAcl - Arthur's ACL CakePHP Plugin for CakePHP 1.2
// 2010-04-01 - Arthur Ketcham

//
// This is a simple ACL sytem that uses database driven configuration for users, groups, and accessible objects.
//
// Accessible objects currently are only controllers and actions
// There are also custom functions defined in here, to validate permissions for any action or process in the application.
//
// General Concepts:
//
// 1. This was written to accompany the "authsome" cakePHP plugin. This is because authsome is in fact, awesome, and easy to setup and configure.
//    see http://github.com/felixge/cakephp-authsome for details on setting it up.
//
// 2. Setup users and groups in the app database. See the dystentery_users.sql file for db structure.
//    Each user needs a group_id set. The groups table gives a name for each group.
//
// 3. the auth_acl table defines the controllers and actions a group or user has access to. 
//    To grant access, enter the name of the controller and optionally the action.
//    If only a controller name is present, full access will be granted to that controller's actions.
//    You may also use the * keyword for access to any controllers or actions (such as for an admin user)
//    Permission levels currently are either "allow" or "deny". They may eventually be extended to CRUD level permissions.
//
//    Admin example: | id | group_id | controller | action | permission | description             | created            | modified           |
//                   ------------------------------------------------------------------------------------------------------------------------
//                     1    1          *            *        allow        Admins have full access   2010-03-30 11:35:50  2010-03-30 11:35:50
//
// 4. Multiple rules may govern the same users, controllers, and action. See below for notes on precendence of rules.
//
// 5. Define custom permission validation functions in this file. Define them as aclcustom_myfunction() in this file,
//    and from your controllers call $this->DystenteryACL->aclcustom_myfunction($params) or DystenteryACL::aclcustom_myfunction($params) to validate ACL permissions.
//
//    - A custom function should do any tests desired, and should return true or false for whether the action is allowed.
//    - A custom function may optionally halt execution and execute redirect to an allowed location using "self::bounce_home($flash_msg)"
//    - Make sure to run exit(); after any redirects, to prevent further execution of unauthorized code below where the ACL check was called.
//
//
// 	
// 6. ACL Policy and Rule Precedence:
// Resources are inherently accessible if no rules exist, or if no rules expressly deny a group/resource.
// To change this behavior, add a rule of (*/*/*/deny) [for (group / controller / action / permission)]
//
// Specific ACL rules take precedence over general (wildcard) rules.
// Precedence takes the following order:
//   - User-Group (name then *)
//   - Controller (name then *)
//   - Action (name then *)
//   - Allow by default, then deny
//
// ACL Search Algorithm:
// (Stop at first rule found)
//
// If acl table empty, then allow.
// If (user/controller/action) rule found, perms = result
// Otherwise, if (user/controller/*) rule found, perms = result
// Otherwise, if (user/*/*) rule found, perms = result
// Otherwise, if (*/controller/action) rule found, perms = result
// Otherwise, if (*/controller/*) rule found, perms = result
// Otherwise, if (*/*/*) rule found, perms = result
// Otherwise, allow.
//
//
// 7. Usage in app:
//   a) Add these lines to a controller:
//		if ($this->AuthAcl->acl_verify_access()) { do protected stuff }
//
//  b) Or, put these lines in the app_controller, which governs access to all controllers/actions:
//		if (!$this->Authsome->get('id')) {
//					
//			if (!($this->params['controller'] == 'auth_users')) {
//				$this->redirect(array('controller' => 'auth_users', 'action' => 'login'));
//			}
//		} else {
//			if (!$this->AuthAcl->acl_verify_access()) {
//				$this->AuthAcl->bounce_home('Access is denied to ' . $this->params['controller'] . '/'. $this->params['action'] . '.');
//				exit();
//			}
//		}
//
class AuthAclComponent extends Object {

	public $settings = array(
		'user_model' => 'User',
		'group_model' => 'Group',
		'acl_model' => 'AuthAcl'
	);
		
	private $controller;
	

	/** 
	 * Startup - Link the component to the controller. 
	 * 
	 * @param controller 
	 */
	public function initialize(&$controller, $settings = array()) {
		$this->settings = Set::merge($this->settings, $settings); // Save custom set model names
		
		$this->controller = $controller; // Set reference to controller
	}

	// --- Custom AuthACL user methods ---
	
	/**
	 * Returns list of allowed web apps (sites) user may operate upon or modify
	 *
	 * @param string $group - The group which the user is a member of
	 * @param array(strings) $build_assoc_array - If false (default), a simple array of sites is returned.
	 * 	 If true, then an associative array of sites with their human readable description is returned (for populating select menu options)
	 *
	 * @return - depends on $return_assoc_array. Either array of sites, or array of (site => description).
	 *   If no permissions (sites) were granted, then false is returned.
	 *
	 *   TODO: Use Application Config in Bootstrap
	 */
	public function acl_custom_get_allowed_sites_list($group, $return_assoc_array = false) {
		$std_available_sites = array(
			''         => 'Select Application'    ,
			'apb_beta' => 'APB Beta'              ,
			'apb_cms'  => 'APB CMS'               ,
			'apb_www'  => 'APB.com'               ,
			'rtw_login'=> 'RTW Login'             ,
			'keymaster'=> 'RTW Keymaster'         ,
			'rtw_www'  => 'Realtimeworlds.com'
		);
		
		// All apps are available to all users, however OT admins, also can operate with OregonTrail
		switch ($group) {
			case 'operations':
			case 'producers':
				$available_sites = $std_available_sites;
				break;
			case 'developers':
				$available_sites = $std_available_sites;
				break;
			case 'administrators':
				$available_sites = $std_available_sites;
				// Add oregon trail to deployables list
				$available_sites = array_merge($available_sites, array('oregontrail' => 'Oregon Trail'));
				break;
			default:
				return false;
				break;
		}
		
		if ($return_assoc_array) {
			return $available_sites;
		} else {
			return array_keys($available_sites); // Discard the Human Readable "descriptions"	
		}
	}
	
	/**
	 * Returns list of allowed environments user may operate upon or modify
	 *
	 * @param string $group - The group which the user is a member of
	 * @param array(strings) $build_assoc_array - If false (default), a simple array of environments is returned.
	 * 	 If true, then an associative array of environments with their human readable description is returned (for populating select menu options)
	 *
	 * @return - depends on $return_assoc_array. Either array of environments, or array of (environments => description)
	 *   If no permissions (environments) were granted, then false is returned.
	 */
	public function acl_custom_get_allowed_environments_list($group, $return_assoc_array = false) {
		
		
		$std_available_environments = array(
			'' => 'Select Environment'    ,
			'development1'  => 'Development 1',
			'development2' => 'Development 2',
			'qa1'          => 'QA 1',
			'qa2'          => 'QA 2 / Security',
			'qa3'          => 'QA 3',
			'qa3'          => 'QA 3',
		);
		
		$production_environments = array(
			'production_eu' => 'EU Production',
			'production_na' => 'NA Production',
		);
		
		$development_environments = array(
			'development1' => 'Development 1',
			'development2' => 'Development 2'
		);
			
		$oregontrail_environments = array(
			'capistrano' => 'Oregon Trail',
		);
		
		
		// Available Sites and Environments, determined by ACL
		switch ($group) {
			case 'operations':
			case 'producers':
				$available_environments = $std_available_environments;
				$available_environments = array_merge($available_environments, $production_environments); // Add production to list
				break;
			case 'developers': // Only Dev Environments
				$available_environments = $development_environments;
				break;
			case 'administrators':
				$available_environments = $std_available_environments;
				$available_environments = array_merge($available_environments, $production_environments); // Add production to list
				$available_environments = array_merge($available_environments, $oregontrail_environments); // Add oregon trail to list
				break;
			default: // They should have been redirected out of here, so this should not happen
				return false;
				break;
		}
		
		if ($return_assoc_array) {
			return $available_environments;
		} else {
			return array_keys($available_environments);	// Discard the Human Readable "descriptions"
		}
	}


	/**
	 * Verifies that the user has access to operate with the specififed site/environment combination.
	 * Called from several places in oregon trail to verify operations conducted with various sites/environments.
	 *
	 * @param string $group - The group which the user is a member of.
	 * @param string $site - Site which will be operated upon.
	 * @param string $environment - Environment which will be operated upon.
	 *
	 * @return - depends on $return_assoc_array. Either array of environments, or array of (environments => description)
	 *   If no permissions (environments) were granted, then false is returned.
	 */
	public function acl_custom_verify_allowed_envsite($group, $site, $environment) {
			
			// Dynamically build list of allowed sites/envs. from existing ACL function.
			$allowed_sites = self::acl_custom_get_allowed_sites_list($group, false);
			$allowed_environments = self::acl_custom_get_allowed_environments_list($group, false);
			
			if (!is_array($allowed_sites) || !is_array($allowed_environments)) {
				self::bounce_home('There was an error retrieving site/environment data.');
			}
			
			$site_permitted = false;
			$environment_permitted = false;
			
			// Site must be allowed
			if (in_array($site, $allowed_sites)) {
				$site_permitted = true;
			}
			
			// Environment must be allowed, or not be specified
			if (in_array($environment, $allowed_environments) || (!$environment) ) {
				$environment_permitted = true;
			}
			
			if (!$site_permitted || !$environment_permitted) {
				
				// Exit page and redirect to Oregon Trail home.
				$flash_msg = 'User does not have access to requested site or environment.';
				self::bounce_home($flash_msg);
			} else {
				return true;
			}
	}
	
	

	
	
	
	// --- Standard AuthACL methods ---
	
	
	// Verify privilages for the current controller/action
	//
	// If group is not supplied, then the current Authsome logged in user is checked.
	
	/**
	 * Verify privilages for the current user-group, controller, and action
	 *
	 * @param string $group (optional) - The group which the user is a member of. If not supplied, current authsome user-group is detected.
	 *
	 * @return boolean - Whether this group is allowed access to the current controller and action.
	 *   This is determined by a set of rules with a precedence explained in the header of this file.
	 */
	public function acl_verify_access($group = false) {
		
		//debug($this->controller);
		
		
		if (!$group) {
			$username = $this->controller->Authsome->get('username');
			$group_name = $this->controller->Authsome->get($this->settings['group_model'].'.name');
			$group_id = $this->controller->Authsome->get($this->settings['group_model'].'.id');
		}
		
		$controller = $this->controller->params['controller'];
		$action = $this->controller->params['action'];

		//////////////////////////////////////////////////////////
		// Original SQL Query (sorts in order of precedence explained in header, gets first match)
		// Result is the most specific ACL rule.
		//
		// SELECT permission FROM auth_acls 
		//   WHERE auth_group_id IN ('1', '*') AND controller IN ('deployments', "*") AND action IN ('run', "*") 
		//   ORDER BY auth_group_id DESC, controller DESC, action DESC
		//   LIMIT 1
		//////////////////////////////////////////////////////////

		$acl_model = ClassRegistry::init($this->settings['acl_model']);

		//////////////////////////////////////////////////////////
		// Special Debugging for ACL DB Data
		//
		$DEBUG_SQL = FALSE;
		
		if ($DEBUG_SQL) {
			$params_debug = array(
				// 'fields' => array('permission'), // <------- COMMENTED FOR DEBUG
				'conditions' => array('auth_group_id' => array($group_id, '*'),
									  'controller' => array($controller, '*'),
									  'action' => array($action, '*'),
									  ),
				'order' => array('auth_group_id DESC', 'controller DESC', 'action DESC', 'permission ASC'),
				//'limit' => '1' // <------- COMMENTED FOR DEBUG
			);
			
			// DEBUG: SHOW FULL ACL MATCH SET (NOT JUST FIRST RESULT)
			debug($acl_model->find('all', $params_debug));
			exit();
		}
		//////////////////////////////////////////////////////////

		$params = array(
			'fields' => array('permission'),
			'conditions' => array('auth_group_id' => array($group_id, '*'),
								  'controller' => array($controller, '*'),
								  'action' => array($action, '*'),
								  ),
			'order' => array('auth_group_id DESC', 'controller DESC', 'action DESC', 'permission ASC'),
			'limit' => '1'
		);
		
		// TESTING: SHOW FULL ACL MATCH SET
		$result = $acl_model->find('first', $params);
		$acl_model_name = $this->settings['acl_model'];
		
		// Permit if no ACL entry found
		if (empty($result[$acl_model_name]['permission'])) {
			$acl_permitted = true;
		
		} else if ($result[$acl_model_name]['permission'] == 'allow') {
			// Permit if ACL allowed
			$acl_permitted = true;
		} else {
			$acl_permitted = false;
		}
		
		// Return boolean value of whether user-group is permitted here.
		return $acl_permitted;
	}
	
	public function bounce_home($flash_msg = false) {
		if ($flash_msg) {
			$this->controller->Session->setFlash($flash_msg);
		}
		
		$this->controller->redirect('/');
		exit; // prevent any further loading
	}

	public function bounce_to_login($flash_msg = false) {
		Authsome::logout();
		if ($flash_msg) {
			$this->controller->Session->setFlash($flash_msg);
		}
		
		$this->controller->redirect(array('controller' => $this->settings['user_model'],'action'=>'login'));
		exit(); // prevent any further loading
	}
	
	


} // end class
?>