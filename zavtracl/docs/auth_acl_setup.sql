-- -------------------------------------
-- Auth_Acl CakePHP Plugin
-- Written by Arthur Ketcham - 2010-04-01
-- 
-- This inserts user, group, auth_token, and auth_acl tables.
-- The names and foreign keys should match that of your cake models and configuration in app_controller.php
-- The name of user, group, and acl tables/models can be changed to anything,
-- with only an update to config vars in app_controller.php necessary.
-- 
-- -------------------------------------
-- -------------------------------------

--
-- Table structure for table 'auth_acls'
--

CREATE TABLE IF NOT EXISTS auth_acls (
  id int(10) unsigned NOT NULL auto_increment,
  auth_group_id varchar(150) NOT NULL default '*',
  controller varchar(150) NOT NULL default '*',
  `action` varchar(150) NOT NULL default '*',
  permission varchar(100) default NULL,
  description varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY controller (controller,`action`),
  KEY `action` (`action`),
  KEY auth_group_id (auth_group_id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO auth_acls (id, auth_group_id, controller, action, permission, description) VALUES
(NULL, '1', 'auth_acl', '*', 'allow', 'Always allow admins access to the AuthACL tools'),
(NULL, '1', '*', '*', 'allow', 'Always allow admins full access to the App');

-- --------------------------------------------------------

--
-- Table structure for table 'auth_groups'
--

CREATE TABLE IF NOT EXISTS auth_groups (
  id int(10) unsigned NOT NULL auto_increment,
  `name` varchar(40) NOT NULL,
  description varchar(255) default NULL,
  created datetime default NULL,
  modified datetime default NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Dumping data for table 'auth_groups'
--

INSERT INTO auth_groups (id, name, description, created, modified) VALUES
(1, 'administrators', 'Full Access to all functions', NOW(), NOW());

-- --------------------------------------------------------

--
-- Table structure for table 'auth_tokens'
--

CREATE TABLE IF NOT EXISTS auth_tokens (
  id int(11) NOT NULL auto_increment,
  auth_user_id int(11) NOT NULL,
  token char(32) NOT NULL,
  duration varchar(32) NOT NULL,
  used tinyint(1) NOT NULL default '0',
  created datetime NOT NULL,
  expires datetime NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table 'auth_users'
--

CREATE TABLE IF NOT EXISTS auth_users (
  id int(10) unsigned NOT NULL auto_increment,
  username varchar(40) NOT NULL,
  `password` varchar(40) NOT NULL,
  auth_group_id int(11) NOT NULL,
  email varchar(40) default NULL,
  active tinyint(1) unsigned NOT NULL default '0',
  force_pass_change tinyint(1) NOT NULL default '1',
  created datetime default NULL,
  modified datetime default NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Dumping data for table 'auth_users'
--

-- Create 'admin' user. It is necessary to generate a hashed password using your App's salt (Hint: call Security::hash('yourPassword', 'sha1', true) somewhere to generate, and paste it here)

INSERT INTO auth_users (id, username, password, auth_group_id, email, active, force_pass_change, created, modified) VALUES
(1, 'admin', '[salted-password]', 1, 'your-email@example.com', 1, 0, now(), now());
