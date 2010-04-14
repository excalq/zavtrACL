<?php
////////////////////
// 
// 
// 
// 
/////////////////////


App::import('Sanitize');

class AuthAclController extends AppController {

    public $name = 'AuthAcl';
	public $components = array ('AuthAcl', 'RequestHandler');
	public $uses = null;
	public $helpers = array('Html', 'Form', 'Javascript'); // For acl admin page
	
	
	private $delete_key; // hash key to verify with "delete" link
	
	// Display blank page if user requests this page
	public function index() {
		$this->header('HTTP/1.1 403 Forbidden');
		exit();
	}

	public function admin() {
		
		// Static page, which has everything in view
	}
	
	// Page to add, delete, and edit ACL Rules
	public function acl_admin() {
		
		// Pass submitted form data onto the back end _acl_admin_tasks() method
		$acl_admin_vars = $this->_acl_admin_tasks($this->data);
		
		$this->set('users', $acl_admin_vars['users']);
		$this->set('groups', $acl_admin_vars['groups']);
		$this->set('group_fkey', $acl_admin_vars['group_fkey']);
		$this->set('user_model', $this->AuthAcl->settings['user_model']);
		$this->set('acl_model', $this->AuthAcl->settings['acl_model']);
		
		$this->set('controllers_actions', $acl_admin_vars['controllers_actions']);
		$this->set('acl_records', $acl_admin_vars['acl_records']);
		
		// hash key to provide with "delete" link
		$delete_key = md5($this->RequestHandler->getClientIP() . Configure::read('Security.salt'));
		$this->set('delete_key', $delete_key);
		
		// TODO: Paginate acl_records
		
	}
	
	// Page to add, delete, and edit users and groups for ACL and Authsome
	public function user_admin() {
		
		// Pass submitted form data onto the back end _acl_admin_tasks() method
		$acl_admin_vars = $this->_acl_admin_tasks($this->data);
		
		$this->set('users', $acl_admin_vars['users']);
		$this->set('groups', $acl_admin_vars['groups']);
		$this->set('user_model', $this->AuthAcl->settings['user_model']);
		$this->set('group_model', $this->AuthAcl->settings['group_model']);
		$this->set('group_fkey', $acl_admin_vars['group_fkey']);
		
		// hash key to provide with "delete" link
		$this->_generate_delete_key();
		$this->set('delete_key', $this->delete_key);
		
	}
	
	// Public interface to delete items
	// Protected by key generated with client IP and app's salt
	public function delete_item($type = false, $id = false, $key = false) {

		if (!$type || !$id || !$key) {
			
			$this->Session->setFlash('Error: There was an error deleting the item.', 'default', array('class' => 'error-message'));
		} else {
			$this->_generate_delete_key();
			
			$deleted_ok = false;
			if ($key == $this->delete_key) {
				
				// Handle with private task handling function
				$deleted_ok = $this->_acl_admin_delete($type, $id);
			}
			
			// Humanize inflection of 'acl_rule"
			$type = ($type == 'acl_rule') ? 'ACL rule' : $type;
			
			if ($deleted_ok)
				$this->Session->setFlash("The $type has been deleted.");
			else
				$this->Session->setFlash("There was an error deleting the $type.", 'default', array('class' => 'error-message'));
		}
		
		if ($type == 'ACL rule' || $type == 'acl_rule') {
			$action = 'acl_admin';
		} else {
			$action = 'user_admin';
		}
		
		$this->redirect(array('action' => $action));
		exit();
	}
	
	// Quick, Dirty hack to reset user passwords.
	// On the view, the admin was prompted for a new temporary password. It is then sent here.
	// Warning: This is insecure! It is meant only as a measure to temporarily reset a user's password. However, it passes it via the URL!
	//
	// This was written fairly crappily due to it being late in the workday, and having no really good, secure way to have the admin enter temporary pw's
	// without requiring extra complexity (ssl, mcrypt, input forms, javascript encryption, etc.). So it should probably be redone.
	public function reset_password($id, $temp_passwd, $key) {

		if (!$id || !$temp_passwd || !$key) {
			
			$this->Session->setFlash('Error: There was an error resetting the users password.', 'default', array('class' => 'error-message'));
		} else {
			$this->_generate_delete_key();
			

			$reset_ok = false;
			if ($key == $this->delete_key) {
				
				// Handle with private task handling function
				
				$user_model_name = $this->AuthAcl->settings['user_model'];
				$user_model = ClassRegistry::init($user_model_name);
				$user = $user_model->read(null, $id);
				$user_name = $user[$user_model_name]['username'];
				// Save updated password
				$user_model->set('password', $this->Authsome->hash($temp_passwd));
				$user_model->set('force_pass_change', 'true');
				$reset_ok = $user_model->save();
			}
			
			if ($reset_ok)
				$this->Session->setFlash("The password for \"{$user_name}\" has been reset");
			else
				$this->Session->setFlash("There was an error deleting the $type.", 'default', array('class' => 'error-message'));
		}
		
		$this->redirect(array('action' => 'user_admin'));
	}
	
	// AJAX Interface to retreive available 'actions' inside a given controller.
	public function ajax_acl_admin_get_actions($controller) {

		Configure::write('debug', 0);
		$this->autoRender = false;
		
		$acl_actions = $this->_acl_admin_tasks(false, $controller);
		
		$actions_list = $acl_actions['controllers_actions'][$controller];
		
		// Print out JSON data
		$JSONdata = "{";
		foreach ($actions_list as $i => $action) {
			$JSONdata .= "\"$i\":\"$action\",";
		}
		if ($i) {
			$JSONdata = substr($JSONdata, 0, -1); // Chop off the last comma
		}
		$JSONdata .= "}";
		echo $JSONdata;
		
	}


	// Generate security key to use with deletion links
	// This key is based on the client ip address and the app's security salt
	// The ACL itself provides a level of security, however this provides a secondary level of security.
	private function _generate_delete_key() {
		// hash key to verify with "delete" link
		
		if (empty($this->delete_key)) {
			$this->delete_key = md5($this->RequestHandler->getClientIP() . Configure::read('Security.salt'));
		}
		
		return $this->delete_key;
	}

	//**** Private Admin Methods ****

	/////////////////////////////////////////////////////////////////////
	// 1) Execute form requested passed from public interface functions.
	// 2) Return data listing all controllers and actions to display in the view.
	//
	// This method is kind of a mess, since it was once part of a monolithic component.
	// It should probably be broken into a few separate methods
	/////////////////////////////////////////////////////////////////////
	public function _acl_admin_tasks($data, $ajax_get_actions_for_controller = false) {
		
		// Verify access to this tool (only admins)
		$ACL_GROUP = $this->Authsome->get('AuthGroup.name');
		if ($ACL_GROUP != 'administrators') {
			self::bounce_home("Only Administrators may edit ACL privileges");
			exit();
		}

		$return_data = array();
		
		// Register 'User', 'Group', 'ACL' models to to DB operations.
		$user_model = ClassRegistry::init($this->AuthAcl->settings['user_model']);
		$group_model = ClassRegistry::init($this->AuthAcl->settings['group_model']);
		$acl_model = ClassRegistry::init($this->AuthAcl->settings['acl_model']);
		
		$group_fkey = (Inflector::underscore($this->AuthAcl->settings['group_model']) . '_id'); // Foreign key from auth_acl to auth_group (in this case auth_group_id)
		$user_model_name = $this->AuthAcl->settings['user_model'];
		$group_model_name = $this->AuthAcl->settings['group_model'];
		$acl_model_name = $this->AuthAcl->settings['acl_model'];
		
		// No data submitted
		if (empty($data)) {
			
			
		} else {
			// ACL permissions update submitted
			
			// Save form data
			if (!empty($data['form']['acl_admin_task'])) {
				
				switch ($data['form']['acl_admin_task']) {
					case 'add_user':
						
						// Add calculated fields, and hash password
						$user_model->set('active', '1');
						$user_model->set('created', gmdate("Y-m-d H:i:s"));
						$user_model->set('modified', gmdate("Y-m-d H:i:s"));
						$data[$user_model_name]['password'] = $this->Authsome->hash($data[$user_model_name]['password']);
						
						$result = $user_model->save($data[$user_model_name]);
						
						if ($result) {
							$this->Session->setFlash('New User "'. $result[$user_model_name]['username'] . '" has been created.');
						} else {
							$this->Session->setFlash('Error: There was a problem saving the new user.', 'default', array('class' => 'error-message'));
						}
						
						break;
					case 'add_group':
					
						// Add calculated fields, and hash password
						$group_model->set('created', gmdate("Y-m-d H:i:s"));
						$group_model->set('modified', gmdate("Y-m-d H:i:s"));
						
						$result = $group_model->save($data['Group']);
						
						if ($result) {
							$this->Session->setFlash('New group "'. $result[$group_model_name]['name'] . '" has been created.');
						} else {
							$this->Session->setFlash('Error: There was a problem saving the new group.', 'default', array('class' => 'error-message'));
						}
						break;
					case 'update_users':
						
						$result = $user_model->saveAll($data[$user_model_name]);
						
						if ($result) {
							$this->Session->setFlash('Users have been updated.');
						} else {
							$this->Session->setFlash('Error: There was a problem updating users.', 'default', array('class' => 'error-message'));
						}
						
						break;
					case 'add_acl_rule':
						
						// Transform controller and action names from CamelCase to userscore_format (url format)
						$data['controller'] = Inflector::underscore($data['controller']);
						$data['action'] = Inflector::underscore($data['action']);
						
						if (empty($data['action'])) {
							$data['action'] = '*'; // "*" wildcard is used if value was empty
						}
						
						// Save to database
						$result = $acl_model->save($data);
						
						if ($result) {
							$this->Session->setFlash('New ACL rule has been created.');
						} else {
							$this->Session->setFlash('Error: There was a problem saving the new ACL rule.', 'default', array('class' => 'error-message'));
						}
						
						break;
				}
			}
		}
		
		// This method has a special mode, to just return actions in response to an ajax request
		// So the only thing returned is $return_data['controllers_actions']
		if ($ajax_get_actions_for_controller) {
			
			// Get list of controllers => actions
			$controllers_actions = array();
			$controllers_actions = self::_acladmin_get_controllers($ajax_get_actions_for_controller);
			$return_data['controllers_actions'] = $controllers_actions;
			
		} else {
			
			// Get data to populate forms in view
			$users = array();
			$groups = array();
			
			// Get list of controllers => actions
			$controllers_actions = array();
			$controllers_actions = self::_acladmin_get_controllers();
			
			// Unbind the AuthTokens table from $user_model
			$user_model->unbindModel(array('hasMany' => array('AuthToken')));
			
			$users = $user_model->find('all', array('fields' => array('id', 'username', $this->AuthAcl->settings['group_model'].'.name', $this->AuthAcl->settings['group_model'].'.id'))); // id, user-name, group-name
			
			$groups = $group_model->find('list', array('fields' => array('id', 'name')));
			$groups = array_map('ucwords', $groups); // Convert names to Title Case
			
			// Register ACL lists
			$acl_model = ClassRegistry::init('AuthAcl');
			$acl_records = $acl_model->find('all', array('order' => array('auth_group_id ASC', 'controller ASC', 'action ASC', 'permission DESC')));
			
			$return_data['users'] = $users;
			$return_data['groups'] = $groups;
			$return_data['group_fkey'] = $group_fkey;
			$return_data['controllers_actions'] = $controllers_actions;
			$return_data['acl_records'] = $acl_records;
			
		}
		
		return $return_data;
	}
	
	
	/////////////////////////////////////////////////////////////////////
	// Handle deletion requests for users, groups, and acl_rules
	/////////////////////////////////////////////////////////////////////
	public function _acl_admin_delete($type, $id) {
		$user_model = ClassRegistry::init($this->AuthAcl->settings['user_model']);
		$group_model = ClassRegistry::init($this->AuthAcl->settings['group_model']);
		$acl_model = ClassRegistry::init($this->AuthAcl->settings['acl_model']);
		
		switch ($type) {
			case 'user':
				$result = $user_model->delete($id, false);
				break;
			case 'group':
				$result = $group_model->delete($id, false);
				break;
			case 'acl_rule':
				$result = $acl_model->delete($id, false);
				break;
			default:
				$result = false;
				break;
		}
		
		return $result;
	}
	
	/////////////////////////////////////////////////////////////////////
	// Get list of controllers and their public actions
	// This may get all controllers, or a specific one
	/////////////////////////////////////////////////////////////////////
	private function _acladmin_get_controllers($specific_controller = false) {
		
		$controllers = array();
		$list = array();
		$this->filter['methods'] = get_class_methods('Controller'); // CakePHP system controller methods
		$this->filter['controller'] = array('App', 'Pages');   // CakePHP system controllers
		
		// Get all controllers/actions or just actions for a specific controller?
		if (!$specific_controller) {
			// Find all App controllers
			$controllers = Configure::listObjects('controller');
			
			sort($controllers);
			
		} else {
			// Use only specified controller - Used to get actions
			$controllers = array($specific_controller);
		}
		
		// Find controller methods 
		foreach($controllers AS $ctlr) { 
			
			if(in_array($ctlr, $this->filter['controller'])) {
				continue;  // Skip system controllers
			}
		
			// Import each controller to inspect
			if(!App::import('Controller', $ctlr)) {
				continue; // Skip if import failed
			}
			
			$controller_sysname = $ctlr . "Controller";
			
			$list[$ctlr] = $this->_getMethods($controller_sysname, 'methods');
		   
		}
		
		return $list;
	}
	
	function _getMethods($className, $filter = 'methods') {
		$c_methods = get_class_methods($className);
		sort($c_methods);
		
		$c_methods = array_diff($c_methods,$this->filter[$filter]); // Exclude cake system methods
		$c_methods = array_filter($c_methods,array($this,"_removePrivateMethods")); 
		
		
		return $c_methods; 
   }
   
	function _removePrivateMethods($var) { 
		 if(substr($var,0,1) == '_')
			 return false; 
		 else
			 return true; 
	}
	
	// Generate random password for password resets
	private function _generate_rand_password($length=6,$level=2) {
		list($usec, $sec) = explode(' ', microtime());
		srand((float) $sec + ((float) $usec * 100000));
		
		$validchars[1] = "0123456789abcdfghjkmnpqrstvwxyz";
		$validchars[2] = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$validchars[3] = "0123456789_#&*-=+abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		
		$password  = "";
		$counter   = 0;
		
		while ($counter < $length) {
		  $actChar = substr($validchars[$level], rand(0, strlen($validchars[$level])-1), 1);
		
		  // All character must be different
		  if (!strstr($password, $actChar)) {
			 $password .= $actChar;
			 $counter++;
		  }
		}
		
		return $password;

	}


}

?>