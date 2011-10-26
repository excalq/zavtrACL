# ZavtrACL
2010-04-01 - Arthur Ketcham/Kaskadia Software Studios

_(Formerly known as AuthAcl and Dysentery (Was part of our "Oregon Trail" application))_


ZavtrACL (pronouced "zaf-trakle" [Russian for "having had breakfast"]) is an Access Control List and User Management plugin for CakePHP.
It is design to work with Authsome, the best CakePHP authentication system known to man and beast.

Currently it is compatible with 1.2.5. It has not been evalutated with 1.3 at this time.

## Notes from the Main Component File:

### AuthAcl - Arthur's ACL CakePHP Plugin for CakePHP 1.2

 __Note:__ October 2011: These directions haven't been updated for the name changes this project has undergone. References to "AuthAcl", "auth_acl" and "DynsenteryACL" 
 should be updated with "ZavtrACL" or "zavtracl".

 This is a simple ACL sytem that uses database driven configuration for users, groups, and accessible objects.

 Accessible objects currently are only controllers and actions
 
 There are also custom functions defined in here, to validate permissions for any action or process in the application.


### General Concepts:

1. This was written to accompany the "authsome" cakePHP plugin. This is because authsome is in fact, awesome, and easy to setup and configure.
    see http://github.com/felixge/cakephp-authsome for details on setting it up.

2. Setup users and groups in the app database. See the dystentery_users.sql file for db structure.
    Each user needs a group_id set. The groups table gives a name for each group.

3. the auth_acl table defines the controllers and actions a group or user has access to. 

    * To grant access, enter the name of the controller and optionally the action. 
    * If only a controller name is present, full access will be granted to that controller's actions.
    * You may also use the * keyword for access to any controllers or actions (such as for an admin user)
    * Permission levels currently are either "allow" or "deny". They may eventually be extended to CRUD level permissions.

    <pre>
    Admin example:
    | id   | group_id  |  controller  |  action  |  permission  |  description              |  created             |  modified             |
    | ---- |---------- | ------------ | -------- | ------------ | ------------------------- | -------------------- | --------------------- |
    | 1    |   1       |      *       |      *   |      allow   |  Admins have full access  |  2010-03-30 11:35:50 |  2010-03-30 11:35:50  |
    </pre>

4. Multiple rules may govern the same users, controllers, and action. See below for notes on precendence of rules.

5. Define custom permission validation functions in this file. Define them as aclcustom_myfunction() in this file,
    and from your controllers call $this->DystenteryACL->aclcustom_myfunction($params) or DystenteryACL::aclcustom_myfunction($params) to validate ACL permissions.

    * A custom function should do any tests desired, and should return true or false for whether the action is allowed.
    * A custom function may optionally halt execution and execute redirect to an allowed location using "self::bounce_home($flash_msg)"
    * Make sure to run exit(); after any redirects, to prevent further execution of unauthorized code below where the ACL check was called.
 	
6. ACL Policy and Rule Precedence:
    * Resources are inherently accessible if no rules exist, or if no rules expressly deny a group/resource.
    * To change this behavior, add a rule of (\*/\*/\*/deny) [for ( group / controller / action / permission )]

    * Specific ACL rules take precedence over general (wildcard) rules.
    * Precedence takes the following order:
      * User-Group (name then \*)
      * Controller (name then \*)
      * Action (name then *)
      * Allow by default, then deny

    * ACL Search Algorithm: (Stops at first rule found)

      * If acl table empty, then allow.
      * If (user/controller/action) rule found, perms = result
      * Otherwise, if (user/controller/*) rule found, perms = result
      * Otherwise, if (user/\*/\*) rule found, perms = result
      * Otherwise, if (*/controller/action) rule found, perms = result
      * Otherwise, if (\*/controller/\*) rule found, perms = result
      * Otherwise, if (\*/\*/*) rule found, perms = result
      * Otherwise, allow.


7. Usage in app:

   a) Add these lines to a controller


   ```php
     if ($this->AuthAcl->acl_verify_access()) { do protected stuff }
   ```
    
    
   b) __OR__, put these lines in the app_controller, which governs access to all controllers/actions:

   ```php
    if (!$this->Authsome->get('id')) {
                
        if (!($this->params['controller'] == 'auth_users')) {
            $this->redirect(array('controller' => 'auth_users', 'action' => 'login'));
        }
    } else {
        if (!$this->AuthAcl->acl_verify_access()) {
            $this->AuthAcl->bounce_home('Access is denied to ' . $this->params['controller'] . '/'. $this->params['action'] . '.');
            exit();
        }
    }
   ```