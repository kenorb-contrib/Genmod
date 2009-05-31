<?php
/**
 * MySQL User and Authentication functions
 *
 * This file contains the MySQL specific functions for working with users and authenticating them.
 * It also handles the internal mail messages, favorites, news/journal, and storage of MyGedView
 * customizations.  Assumes that a database connection has already been established.
 *
 * You can extend Genmod to work with other systems by implementing the functions in this file.
 * Other possible options are to use LDAP for authentication.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * @package Genmod
 * @subpackage DB
 * @version $Id: authentication.php,v 1.26 2006/05/07 11:35:56 roland-d Exp $
 */
if (strstr($_SERVER["SCRIPT_NAME"],"authentication")) {
	print "Now, why would you want to do that.  You're not hacking are you?";
	exit;
}

/**
 * authenticate a username and password
 *
 * This function takes the given <var>$username</var> and <var>$password</var> and authenticates
 * them against the database.  The passwords are encrypted using the crypt() function.
 * The username is stored in the <var>$_SESSION["gm_user"]</var> session variable.
 * @param string $username the username for the user attempting to login
 * @param string $password the plain text password to test
 * @return bool return true if the username and password credentials match a user in the database return false if they don't
 */
function authenticateUser($username, $password) {
	global $TBLPREFIX, $GEDCOM, $gm_lang;
	checkTableExists();
	$user = GetUser($username);
	if ($user!==false) {
		if (crypt($password, $user["password"])==$user["password"]) {
			if (!isset($user["verified"])) $user["verified"] = "";
	        	if (!isset($user["verified_by_admin"])) $user["verified_by_admin"] = "";
	        	if ((($user["verified"] == "yes") and ($user["verified_by_admin"] == "yes")) or ($user["canadmin"] != "")){
				$sql = "UPDATE ".$TBLPREFIX."users SET u_loggedin='Y', u_sessiontime='".time()."' WHERE u_username='$username'";
				$res = dbquery($sql);
				//-- reset the user's session
				$_SESSION = array();
				$_SESSION['gm_user'] = $username;
				// -- set the IP on which authentication took place.
				$_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
				//-- unset the cookie_login session var to show that they have logged in with their password
				$_SESSION['cookie_login'] = false;
				// -- The session vars MUST be set BEFORE writing to the log.
				WriteToLog("Login Successful -> " . $username ." <-", "I", "S");
				if (isset($gm_lang[$user["language"]])) $_SESSION['CLANGUAGE'] = $user['language'];
				//-- only change the gedcom if the user does not have an gedcom id
				//-- for the currently active gedcom
				if (empty($user["gedcomid"][$GEDCOM])) {
					//-- if the user is not in the currently active gedcom then switch them
					//-- to the first gedcom for which they have an ID
					foreach($user["gedcomid"] as $ged=>$id) {
						if (!empty($id)) {
							$_SESSION['GEDCOM']=$ged;
							break;
						}
					}
				}
				return true;
			}
		}
	}
	WriteToLog("Login Failed -> " . $username ." <-", "E", "S");
	return false;
}

/**
 * Logs a user out of the system
 *
 * A user gets logged out either by its own choice or by 
 * session expiration. The function takes the username and logs the user out
 * and writes a message to the system log. Optionally the reason for logout
 * can be specified when calling the function. This will then be written to
 * the system log including the username that was logged out.
 *
 * @author	Genmod Development Team
 * @param		string	$username		The user to be logged out
 * @param		string	$logtext		Optional text to write to the log for reason of user logout
 */
function userLogout($username, $logtext = "") {
	global $TBLPREFIX, $GEDCOM, $LANGUAGE, $gm_username, $TOTAL_QUERIES;
	if ($username=="") {
		if (isset($_SESSION["gm_user"])) $username = $_SESSION["gm_user"];
		else if (isset($_COOKIE["gm_rem"])) $username = $_COOKIE["gm_rem"];
		else return;
	}
	$sql = "UPDATE ".$TBLPREFIX."users SET u_loggedin='N' WHERE u_username='".$username."'";
	$res = dbquery($sql);
	$TOTAL_QUERIES++;
	if ($logtext == "") WriteToLog("Logout -> " . $username . " <-", "I", "S");
	else WriteToLog($logtext." -> " . $username . " <-", "I", "S");

	if ((isset($_SESSION['gm_user']) && ($_SESSION['gm_user']==$username)) || (isset($_COOKIE['gm_rem'])&&$_COOKIE['gm_rem']==$username)) {
		if ($_SESSION['gm_user']==$username) {
			$_SESSION['gm_user'] = "";
			unset($_SESSION['gm_user']);
			if (isset($_SESSION["gm_counter"])) $tmphits = $_SESSION["gm_counter"];
			else $tmphits = -1;
			@session_destroy();
			$_SESSION["gedcom"]=$GEDCOM;
			$_SESSION["show_context_help"]="yes";
			@setcookie("gm_rem", "", -1000);
			if($tmphits>=0) $_SESSION["gm_counter"]=$tmphits; //set since it was set before so don't get double hits
		}
	}
	$gm_username = getUserName();
}

/**
 * return a sorted array of user
 *
 * returns a sorted array of the users in the system
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#users
 * @param string $field the field in the user array to sort on
 * @param string $order asc or dec
 * @return array returns a sorted array of users
 */
function getUsers($field = "username", $order = "asc", $sort2 = "firstname", $select="") {
	global $TBLPREFIX, $usersortfields;
	$sql = "SELECT * FROM ".$TBLPREFIX."users";
	if (!empty($select)) $sql .= " WHERE ".$select;
	$sql .= " ORDER BY u_".$field." ".strtoupper($order).", u_".$sort2;
	$res = dbquery($sql);
	$users = array();
	if ($res) {
		while($user_row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			$user = array();
			$user["username"]=$user_row["u_username"];
			$user["firstname"]=stripslashes($user_row["u_firstname"]);
			$user["lastname"]=stripslashes($user_row["u_lastname"]);
			$user["gedcomid"]=unserialize($user_row["u_gedcomid"]);
			$user["rootid"]=unserialize($user_row["u_rootid"]);
			$user["password"]=$user_row["u_password"];
			if ($user_row["u_canadmin"]=='Y') $user["canadmin"]=true;
			else $user["canadmin"]=false;
			$user["canedit"]=unserialize($user_row["u_canedit"]);
			//-- convert old <3.1 access levels to the new 3.2 access levels
			foreach($user["canedit"] as $key=>$value) {
				if ($value=="no") $user["canedit"][$key] = "access";
				if ($value=="yes") $user["canedit"][$key] = "edit";
			}
			$user["email"] = $user_row["u_email"];
			$user["verified"] = $user_row["u_verified"];
			$user["verified_by_admin"] = $user_row["u_verified_by_admin"];
			$user["language"] = $user_row["u_language"];
			$user["pwrequested"] = $user_row["u_pwrequested"];
			$user["reg_timestamp"] = $user_row["u_reg_timestamp"];
			$user["reg_hashcode"] = $user_row["u_reg_hashcode"];
			$user["theme"] = $user_row["u_theme"];
			$user["loggedin"] = $user_row["u_loggedin"];
			$user["sessiontime"] = $user_row["u_sessiontime"];
			$user["contactmethod"] = $user_row["u_contactmethod"];
			if ($user_row["u_visibleonline"]!='N') $user["visibleonline"] = true;
			else $user["visibleonline"] = false;
			if ($user_row["u_editaccount"]!='N') $user["editaccount"] = true;
			else $user["editaccount"] = false;
			$user["default_tab"] = $user_row["u_defaulttab"];
			$user["comment"] = stripslashes($user_row["u_comment"]);
			$user["comment_exp"] = $user_row["u_comment_exp"];
			$user["sync_gedcom"] = $user_row["u_sync_gedcom"];
			$user["relationship_privacy"] = $user_row["u_relationship_privacy"];
			$user["max_relation_path"] = $user_row["u_max_relation_path"];
			if ($user_row["u_auto_accept"]!="Y") $user["auto_accept"] = false;
			else $user["auto_accept"] = true;
			$users[$user_row["u_username"]] = $user;
		}
		$res->free();
	}
	return $users;
}

/**
 * get the current username
 * 
 * gets the username for the currently active user
 * 1. first checks the session
 * 2. then checks the remember cookie
 * @return string 	the username of the user or an empty string if the user is not logged in
 */
function getUserName() {
	global $ALLOW_REMEMBER_ME, $DBCONN, $logout, $SERVER_URL;
	//-- this section checks if the session exists and uses it to get the username
	if (isset($_SESSION)) {
		if (!empty($_SESSION['gm_user'])) return $_SESSION['gm_user'];
	}
	if (isset($HTTP_SESSION_VARS)) {
		if (!empty($HTTP_SESSION_VARS['gm_user'])) return $HTTP_SESSION_VARS['gm_user'];
	}
	if ($ALLOW_REMEMBER_ME) {
		$tSERVER_URL = preg_replace(array("'https?://'", "'www.'", "'/$'"), array("","",""), $SERVER_URL);
		if ((isset($_SERVER['HTTP_REFERER'])) && (stristr($_SERVER['HTTP_REFERER'],$tSERVER_URL)!==false)) $referrer_found=true;
		if (!empty($_COOKIE["gm_rem"])&& (empty($referrer_found)) && empty($logout)) {
			if (!is_object($DBCONN)) return $_COOKIE["gm_rem"];
			$user = GetUser($_COOKIE["gm_rem"]);
			if ($user) {
				if (time() - $user["sessiontime"] < 60*60*24*7) {
					$_SESSION['gm_user'] = $_COOKIE["gm_rem"];
					$_SESSION['cookie_login'] = true;
					return $_COOKIE["gm_rem"];
				}
			}
		}
	}
	return "";
}

/**
 * check if given username is an admin
 *
 * takes a username and checks if the
 * user has administrative privileges
 * to change the configuration files
 */
function userIsAdmin($username) {
	if (empty($username)) return false;
	if ($_SESSION['cookie_login']) return false;
	$user = GetUser($username);
	if (!$user) return false;
	return $user["canadmin"];
}

/**
 * check if given username is an admin for the current gedcom
 *
 * takes a username and checks if the
 * user has administrative privileges
 * to change the configuration files for the currently active gedcom
 */
function userGedcomAdmin($username, $ged="") {
	global $GEDCOM;

	if (empty($ged)) $ged = $GEDCOM;

	if ($_SESSION['cookie_login']) return false;
	if (userIsAdmin($username)) return true;
	if (empty($username)) return false;
	$user = GetUser($username);
	if (!$user) return false;
	if (isset($user["canedit"][$ged])) {
		if ($user["canedit"][$ged]=="admin") return true;
		else return false;
	}
	else return false;
}

/**
 * check if the given user has access privileges on this gedcom
 *
 * takes a username and checks if the user has access privileges to view the private
 * gedcom data.
 * @param string $username the username of the user to check
 * @return boolean true if user can access false if they cannot
 */
function userCanAccess($username) {
	global $GEDCOM;

	if (userIsAdmin($username)) return true;
	if (empty($username)) return false;
	$user = GetUser($username);
	if (!$user) return false;
	if (isset($user["canedit"][$GEDCOM])) {
		if ($user["canedit"][$GEDCOM]!="none" || $user["canadmin"]) return true;
		else return false;
	}
	else return false;
}

/**
 * check if the given user has write privileges on this gedcom
 *
 * takes a username and checks if the user has write privileges to change
 * the gedcom data. First check if the administrator has turned on editing privileges for this gedcom
 * @param string $username the username of the user to check
 * @return boolean true if user can edit false if they cannot
 */
function userCanEdit($username) {
	global $ALLOW_EDIT_GEDCOM, $GEDCOM;

	if (!$ALLOW_EDIT_GEDCOM) return false;
	if (userIsAdmin($username)) return true;
	if (empty($username)) return false;
	$user = GetUser($username);
	if (!$user) return false;
	if ($user["canadmin"]) return true;
	if (isset($user["canedit"][$GEDCOM])) {
		if ($user["canedit"][$GEDCOM]=="yes" || $user["canedit"][$GEDCOM]=="edit" || $user["canedit"][$GEDCOM]=="admin"|| $user["canedit"][$GEDCOM]=="accept" || $user["canedit"][$GEDCOM]===true) return true;
		else return false;
	}
	else return false;
}

/**
 * Can user accept changes
 * 
 * takes a username and checks if the user has write privileges to 
 * change the gedcom data and accept changes
 * @param string $username	the username of the user check privileges
 * @return boolean true if user can accept false if user cannot accept
 */ 
function userCanAccept($username) {
	global $ALLOW_EDIT_GEDCOM, $GEDCOM;

	if ($_SESSION['cookie_login']) return false;
	if (userIsAdmin($username)) return true;
	if (!$ALLOW_EDIT_GEDCOM) return false;
	if (empty($username)) return false;
	$user = GetUser($username);
	if (!$user) return false;
	if (isset($user["canedit"][$GEDCOM])) {
		if ($user["canedit"][$GEDCOM]=="accept") return true;
		if ($user["canedit"][$GEDCOM]=="admin") return true;
		else return false;
	}
	else return false;
}

/**
 * Should user's changed automatically be accepted 
 * @param string $username	the user name of the user to check
 * @return boolean 		true if the changes should automatically be accepted
 */
function userAutoAccept($username = "") {
	if (empty($username)) $username = getUserName();
	if (empty($username)) return false;
	
	if (!userCanAccept($username)) return false;
	$user = GetUser($username);
	if ($user["auto_accept"]) return true;
}

/**
 * does an admin user exits
 * 
 * Checks to see if an admin user has been created 
 * @return boolean true if an admin user has been defined
 */ 
function adminUserExists() {
	global $TBLPREFIX, $DBCONN;
	if (checkTableExists()) {
		$sql = "SELECT u_username FROM ".$TBLPREFIX."users WHERE u_canadmin='Y'";
		$res = dbquery($sql);
		if ($res) {
			$count = $res->numRows();
			while($row = $res->fetchRow());
			$res->free();
			if ($count==0) return false;
			return true;
		}
	}
	return false;
}

/**
 * store users array
 * 
 * store the array of users to a file
 * this funciton is not implemented in DB mode
 * @see authentication.php
 */
function storeUsers() {
	return true;
}

/**
 * check if the user database tables exist
 * 
 * If the tables don't exist then create them
 * If the tables do exist check if they need to be upgraded 
 * to the latest version of the database schema.
 */
function checkTableExists() {
	global $TBLPREFIX, $DBCONN, $CHECKED_TABLES;

	//-- make sure we only run this function once
	if (!empty($CHECKED_TABLES) && $CHECKED_TABLES==true) return true;
	$CHECKED_TABLES = true;
	
	$has_gedcomid = false;
	$has_email = false;
	$has_messages = false;
	$has_favorites = false;
	$has_sessiontime = false;
	$has_blocks = false;
	$has_contactmethod = false;
	$has_news = false;
	$has_visible = false;
	$has_account = false;
	$has_defaulttab = false;
	$has_blockconfig = false;
	$has_comment = false;
	$has_comment_exp = false;
	$has_sync_gedcom = false;
	$has_first_name = false;
	$has_relation_privacy = false;
	$has_fav_note = false;
	$has_auto_accept = false;

	$data = $DBCONN->getListOf('tables');
	if (count($data)>0) {
		foreach($data as $indexval => $table) {
			if ($table==$TBLPREFIX."users") {
				$info = $DBCONN->tableInfo($TBLPREFIX."users");
				if (DB::isError($info)) {
					print "<span class=\"error\"><b>ERROR:".$info->getCode()." ".$info->getMessage()." <br />SQL:</b>".$info->getUserInfo()."</span><br /><br />\n";
					exit;
				}
				foreach($info as $indexval => $field) {
					if (($field["name"]=="u_gedcomid")&&(preg_match("/(text)|(blob)|(str)/i", $field["type"])>0)) $has_gedcomid = true;
					if (($field["name"]=="u_email")&&(preg_match("/(text)|(blob)|(str)/i", $field["type"])>0)) $has_email = true;
					if ($field["name"]=="u_sessiontime") $has_sessiontime = true;
					if ($field["name"]=="u_contactmethod") $has_contactmethod = true;
					if ($field["name"]=="u_visibleonline") $has_visible = true;
					if ($field["name"]=="u_editaccount") $has_account = true;
					if ($field["name"]=="u_defaulttab") $has_defaulttab = true;
					if ($field["name"]=="u_comment") $has_comment = true;
					if ($field["name"]=="u_comment_exp") $has_comment_exp = true;
					if ($field["name"]=="u_sync_gedcom") $has_sync_gedcom = true;
					if ($field["name"]=="u_firstname") $has_first_name = true;
					if ($field["name"]=="u_relationship_privacy") $has_relation_privacy = true;
					if ($field["name"]=="u_auto_accept") $has_auto_accept = true;
				}
				if (!$has_gedcomid) {
					$asql = "DROP TABLE ".$TBLPREFIX."users";
					$ares = dbquery($asql);
					if (!$ares) {
						print "<span class=\"error\">Unable to update <i>Users</i> table.</span><br />\n";
						return false;
					}
				}
				else if (!$has_email) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_email TEXT, u_verified VARCHAR(20), u_verified_by_admin VARCHAR(20), u_language VARCHAR(50), u_pwrequested VARCHAR(20), u_reg_timestamp VARCHAR(50), u_reg_hashcode VARCHAR(255), u_theme VARCHAR(50), u_loggedin VARCHAR(1), u_sessiontime INT)";
					$pres = dbquery($sql);
				}
				else if (!$has_sessiontime) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_sessiontime INT)";
					$pres = dbquery($sql);
				}
				else if (!$has_contactmethod) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_contactmethod VARCHAR(20))";
					$pres = dbquery($sql);
				}
				if (!$has_visible) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_visibleonline VARCHAR(2))";
					$pres = dbquery($sql);
				}
				if (!$has_account) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_editaccount VARCHAR(2))";
					$pres = dbquery($sql);
				}
				if (!$has_defaulttab) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_defaulttab INT)";
					$pres = dbquery($sql);
				}
				if (!$has_comment) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_comment VARCHAR(255))";
					$pres = dbquery($sql);
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_comment_exp VARCHAR(20))";
					$pres = dbquery($sql);
				}
				if (!$has_sync_gedcom) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_sync_gedcom VARCHAR(2))";
					$pres = dbquery($sql);
				}
				if (!$has_first_name) {
					//-- add new first and last name fields
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN u_firstname VARCHAR(255) AFTER u_fullname";
					$pres = dbquery($sql);
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN u_lastname VARCHAR(255) AFTER u_firstname";
					$pres = dbquery($sql);
					//-- convert the old fullname to first and last names
					$sql = "UPDATE ".$TBLPREFIX."users SET u_lastname=SUBSTRING_INDEX(u_fullname, ' ', -1), u_firstname=SUBSTRING_INDEX(u_fullname, ' ', 1)";
					$pres = dbquery($sql);
					//-- drop the old fullname field
					$sql = "ALTER TABLE ".$TBLPREFIX."users DROP u_fullname";
					$pres = dbquery($sql);
				}
				if (!$has_relation_privacy) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN (u_relationship_privacy VARCHAR(2), u_max_relation_path INT)";
					$pres = dbquery($sql);
				}
				if (!$has_auto_accept) {
					$sql = "ALTER TABLE ".$TBLPREFIX."users ADD COLUMN u_auto_accept VARCHAR(2)";
					$pres = dbquery($sql);
				}
				$sql = "ALTER TABLE ".$TBLPREFIX."users CHANGE `u_username` `u_username` VARCHAR( 30 ) BINARY NOT NULL";
				$pres = dbquery($sql);
			}
			if ($table==$TBLPREFIX."messages") $has_messages = true;
			if ($table==$TBLPREFIX."favorites") {
				$has_favorites = true;
				$info = $DBCONN->tableInfo($TBLPREFIX."favorites");
				if (DB::isError($info)) {
					print "<span class=\"error\"><b>ERROR:".$info->getCode()." ".$info->getMessage()." <br />SQL:</b>".$info->getUserInfo()."</span><br /><br />\n";
					exit;
				}
				foreach($info as $indexval => $field) {
					if ($field["name"]=="fv_note") $has_fav_note = true;
				}
				if (!$has_fav_note) {
					$sql = "ALTER TABLE ".$TBLPREFIX."favorites ADD COLUMN (fv_url VARCHAR(255), fv_title VARCHAR(255), fv_note TEXT)";
					$pres = dbquery($sql);
				}
			}
			if ($table==$TBLPREFIX."blocks") {
				$has_blocks = true;
				$info = $DBCONN->tableInfo($TBLPREFIX."blocks");
				if (DB::isError($info)) {
					print "<span class=\"error\"><b>ERROR:".$info->getCode()." ".$info->getMessage()." <br />SQL:</b>".$info->getUserInfo()."</span><br /><br />\n";
					exit;
				}
				foreach($info as $indexval => $field) {
					if ($field["name"]=="b_config") $has_blockconfig = true;
				}
				
				if (!$has_blockconfig) {
					$sql = "ALTER TABLE ".$TBLPREFIX."blocks ADD COLUMN (b_config TEXT)";
					$res = dbquery($sql);
				}
			}
			if ($table==$TBLPREFIX."news") $has_news = true;
		}
	}
	if (!$has_gedcomid) {
		$sql = "CREATE TABLE ".$TBLPREFIX."users (
			`u_username` varchar(30) binary NOT NULL default '',
			`u_password` varchar(255) default NULL,
			`u_firstname` varchar(255) default NULL,
			`u_lastname` varchar(255) default NULL,
			`u_gedcomid` text,
			`u_rootid` text,
			`u_canadmin` char(2) default NULL,
			`u_canedit` text,
			`u_email` text,
			`u_verified` varchar(20) default NULL,
			`u_verified_by_admin` varchar(20) default NULL,
			`u_language` varchar(50) default NULL,
			`u_pwrequested` varchar(20) default NULL,
			`u_reg_timestamp` varchar(50) default NULL,
			`u_reg_hashcode` varchar(255) default NULL,
			`u_theme` varchar(50) default NULL,
			`u_loggedin` char(2) default NULL,
			`u_sessiontime` int(11) default NULL,
			`u_contactmethod` varchar(20) default NULL,
			`u_visibleonline` char(2) default NULL,
			`u_editaccount` char(2) default NULL,
			`u_defaulttab` int(11) default NULL,
			`u_comment` varchar(255) default NULL,
			`u_comment_exp` varchar(20) default NULL,
			`u_sync_gedcom` char(2) default NULL,
			`u_relationship_privacy` char(2) default NULL,
			`u_max_relation_path` int(11) default NULL,
			`u_auto_accept` char(2) default NULL,
			PRIMARY KEY  (`u_username`)
			)";
		$res = dbquery($sql);
	}
	if (!$has_messages) {
		$sql = "CREATE TABLE ".$TBLPREFIX."messages (m_id INT NOT NULL, m_from VARCHAR(255), m_to VARCHAR(30), m_subject VARCHAR(255), m_body TEXT, m_created VARCHAR(255), PRIMARY KEY(m_id))";
		$res = dbquery($sql);
	}
	if (!$has_favorites) {
		$sql = "CREATE TABLE ".$TBLPREFIX."favorites (fv_id INT NOT NULL, fv_username VARCHAR(30), fv_gid VARCHAR(10), fv_type VARCHAR(10), fv_file VARCHAR(100), fv_url VARCHAR(255), fv_title VARCHAR(255), fv_note TEXT, PRIMARY KEY(fv_id))";
		$res = dbquery($sql);
	}
	if (!$has_blocks) {
		$sql = "CREATE TABLE ".$TBLPREFIX."blocks (b_id INT NOT NULL, b_username VARCHAR(100), b_location VARCHAR(30), b_order INT, b_name VARCHAR(255), b_config TEXT, PRIMARY KEY(b_id))";
		$res = dbquery($sql);
	}
	if (!$has_news) {
		$sql = "CREATE TABLE ".$TBLPREFIX."news (n_id INT NOT NULL, n_username VARCHAR(100), n_date INT, n_title VARCHAR(255), n_text TEXT, PRIMARY KEY(n_id))";
		$res = dbquery($sql);
	}
	return true;
}

/**
 * Add a new user
 * 
 * Adds a new user to the data store
 * @param array $newuser	The new user array to add
 * @param string $msg		The log message to write to the log
 */
function addUser($newuser, $msg = "added") {
	global $TBLPREFIX, $DBCONN, $USE_RELATIONSHIP_PRIVACY, $MAX_RELATION_PATH_LENGTH;

	if (checkTableExists()) {
		if (!isset($newuser["relationship_privacy"])) {
			if ($USE_RELATIONSHIP_PRIVACY) $newuser["relationship_privacy"] = "Y";
			else $newuser["relationship_privacy"] = "N";
		} 
		if (!isset($newuser["max_relation_path"])) $newuser["max_relation_path"] = $MAX_RELATION_PATH_LENGTH;
		if (!isset($newuser["auto_accept"])) $newuser["auto_accept"] = "N";
		$newuser = db_prep($newuser);
		$newuser["firstname"] = preg_replace("/\//", "", $newuser["firstname"]);
		$newuser["lastname"] = preg_replace("/\//", "", $newuser["lastname"]);
		$sql = "INSERT INTO ".$TBLPREFIX."users VALUES('".$newuser["username"]."','".$newuser["password"]."','".$DBCONN->escapeSimple($newuser["firstname"])."','".$DBCONN->escapeSimple($newuser["lastname"])."','".$DBCONN->escapeSimple(serialize($newuser["gedcomid"]))."','".$DBCONN->escapeSimple(serialize($newuser["rootid"]))."'";
		if ($newuser["canadmin"]) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ",'".$DBCONN->escapeSimple(serialize($newuser["canedit"]))."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["email"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["verified"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["verified_by_admin"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["language"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["pwrequested"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["reg_timestamp"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["reg_hashcode"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["theme"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["loggedin"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["sessiontime"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["contactmethod"])."'";
		if ($newuser["visibleonline"]) $sql .= ",'Y'";
		else $sql .= ",'N'";
		if ($newuser["editaccount"]) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["default_tab"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["comment"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["comment_exp"])."'";
		if (isset($newuser["sync_gedcom"])) $sql .= ",'".$DBCONN->escapeSimple($newuser["sync_gedcom"])."'";
		else $sql .= ",'N'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["relationship_privacy"])."'";
		$sql .= ",'".$DBCONN->escapeSimple($newuser["max_relation_path"])."'";
		if (isset($newuser["auto_accept"]) && $newuser["auto_accept"]==true) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ")";
		$res = dbquery($sql);
		$activeuser = getUserName();
		if ($activeuser == "") $activeuser = "Anonymous user";
		WriteToLog($activeuser." ".$msg." user -> ".$newuser["username"]." <-", "I", "S");
		if ($res) return true;
	}
	return false;
}

/**
 * deletes the user with the given username.
 * @param string $username	the username to delete
 * @param string $msg		a message to write to the log file
 */
function deleteUser($username, $msg = "deleted") {
	global $TBLPREFIX, $users;
	unset($users[$username]);
	$username = db_prep($username);
	$sql = "DELETE FROM ".$TBLPREFIX."users WHERE u_username='$username'";
	$res = dbquery($sql);
	$activeuser = getUserName();
	if ($activeuser == "") $activeuser = "Anonymous user";
	if (($msg != "changed") && ($msg != "reqested password for") && ($msg != "verified")) WriteToLog($activeuser." ".$msg." user -&gt; ".$username." &lt;-", "I", "S");
	if ($res) return true;
	else return false;
}

/**
 * creates a user as reference for a gedcom export
 * @param string $export_accesslevel
 */
function create_export_user($export_accesslevel) {
	global $GEDCOM;
	
	if (GetUser("export")) deleteUser("export");

	$newuser = array();
	$newuser["firstname"] = "Export";
	$newuser["lastname"] = "useraccount";
	$newuser["username"] = "export";
	$allow = "abcdefghijkmnpqrstuvwxyz23456789"; 
	srand((double)microtime()*1000000);
	$password = ""; 
	for($i=0; $i<8; $i++) { 
		$password .= $allow[rand()%strlen($allow)]; 
	} 
	$newuser["password"] = $password;
	$newuser["gedcomid"] = "";
	$newuser["rootid"] = "";
	if ($export_accesslevel == "admin") $newuser["canadmin"] = true;
	else $newuser["canadmin"] = false;
	if ($export_accesslevel == "gedadmin") $newuser["canedit"][$GEDCOM] = "admin";
	elseif ($export_accesslevel == "user") $newuser["canedit"][$GEDCOM] = "access";
	else $newuser["canedit"][$GEDCOM] = "none";
	$newuser["email"] = "";
	$newuser["verified"] = "yes";
	$newuser["verified_by_admin"] = "yes";
	$newuser["language"] = "english";
	$newuser["pwrequested"] = "";
	$newuser["reg_timestamp"] = "";
	$newuser["reg_hashcode"] = "";
	$newuser["theme"] = "";
	$newuser["loggedin"] = "";
	$newuser["sessiontime"] = "";
	$newuser["contactmethod"] = "none";
	$newuser["visibleonline"] = false;
	$newuser["editaccount"] = false;
	$newuser["default_tab"] = 0;
	$newuser["comment"] = "";
	$newuser["comment_exp"] = "Dummy user for export purposes";
	$newuser["sync_gedcom"] = "N";
	$newuser["relationship_privacy"] = "N";
	$newuser["max_relation_path"] = 0;
	$newuser["auto_accept"] = "N";
	addUser($newuser);
}

/**
 * Get a user array
 *
 * Finds a user from the given username and returns a user array 
 * @param string $username the username of the user to return
 * @return array the user array to return
 */
function GetUser($username) {
	global $TBLPREFIX, $users, $REGEXP_DB, $GEDCOMS;

	if (empty($username)) return false;
	if (isset($users[$username])) return $users[$username];
	$username = db_prep($username);
	$sql = "SELECT * FROM ".$TBLPREFIX."users WHERE u_username='".$username."'";
	$res = dbquery($sql);
	if ($res===false || DB::isError($res)) return false;
	if ($res->numRows()==0) return false;
	if ($res) {
		while($user_row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if ($user_row) {
				$user = array();
				$user["username"]=$user_row["u_username"];
				$user["firstname"]=stripslashes($user_row["u_firstname"]);
				$user["lastname"]=stripslashes($user_row["u_lastname"]);
				$user["gedcomid"]=unserialize($user_row["u_gedcomid"]);
				$user["rootid"]=unserialize($user_row["u_rootid"]);
				$user["password"]=$user_row["u_password"];
				if ($user_row["u_canadmin"]=='Y') $user["canadmin"]=true;
				else $user["canadmin"]=false;
				$user["canedit"]=unserialize($user_row["u_canedit"]);
				//-- convert old <3.1 access levels to the new 3.2 access levels
				foreach($user["canedit"] as $key=>$value) {
					if ($value=="no") $user["canedit"][$key] = "access";
					if ($value=="yes") $user["canedit"][$key] = "edit";
				}
				foreach($GEDCOMS as $ged=>$gedarray) {
					if (!isset($user["canedit"][$ged])) $user["canedit"][$ged] = "access";
				}
				$user["email"] = $user_row["u_email"];
				$user["verified"] = $user_row["u_verified"];
				$user["verified_by_admin"] = $user_row["u_verified_by_admin"];
				$user["language"] = $user_row["u_language"];
				$user["pwrequested"] = $user_row["u_pwrequested"];
				$user["reg_timestamp"] = $user_row["u_reg_timestamp"];
				$user["reg_hashcode"] = $user_row["u_reg_hashcode"];
				$user["theme"] = $user_row["u_theme"];
				$user["loggedin"] = $user_row["u_loggedin"];
				$user["sessiontime"] = $user_row["u_sessiontime"];
				$user["contactmethod"] = $user_row["u_contactmethod"];
				if ($user_row["u_visibleonline"]!='N') $user["visibleonline"]=true;
				else $user["visibleonline"]=false;
				if ($user_row["u_editaccount"]!='N' || $user["canadmin"]) $user["editaccount"]=true;
				else $user["editaccount"]=false;
				$user["default_tab"] = $user_row["u_defaulttab"];
				$user["comment"] = stripslashes($user_row["u_comment"]);
				$user["comment_exp"] = $user_row["u_comment_exp"];
				$user["sync_gedcom"] = $user_row["u_sync_gedcom"];
				$user["relationship_privacy"] = $user_row["u_relationship_privacy"];
				$user["max_relation_path"] = $user_row["u_max_relation_path"];
				if ($user_row["u_auto_accept"]!='Y') $user["auto_accept"]=false;
				else $user["auto_accept"]=true;
				$users[$user_row["u_username"]] = $user;
			}
		}
		$res->free();
		return $user;
	}
	return false;
}

/**
 * get a user from a gedcom id
 *
 * finds a user from their gedcom id
 * @param string $id	the gedcom id to to search on
 * @param string $gedcom	the gedcom filename to match
 * @return array 	returns a user array
 */
function getUserByGedcomId($id, $gedcom) {
	global $TBLPREFIX, $users, $REGEXP_DB;

	if (empty($id) || empty($gedcom)) return false;
	
	$user = false;
	$id = db_prep($id);
	$sql = "SELECT * FROM ".$TBLPREFIX."users WHERE ";
	$sql .= "u_gedcomid LIKE '%".$id."%'";
	$res = dbquery($sql, false);
	if (DB::isError($res)) return false;
	if ($res->numRows()==0) return false;
	if ($res) {
		while($user_row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if ($user_row) {
				$gedcomid=unserialize($user_row["u_gedcomid"]);
				if (isset($gedcomid[$gedcom]) && ($gedcomid[$gedcom]==$id)) {
					$user = array();
					$user["username"]=$user_row["u_username"];
					$user["firstname"]=stripslashes($user_row["u_firstname"]);
					$user["lastname"]=stripslashes($user_row["u_lastname"]);
					$user["gedcomid"]=$gedcomid;
					$user["rootid"]=unserialize($user_row["u_rootid"]);
					$user["password"]=$user_row["u_password"];
					if ($user_row["u_canadmin"]=='Y') $user["canadmin"]=true;
					else $user["canadmin"]=false;
					$user["canedit"]=unserialize($user_row["u_canedit"]);
					//-- convert old <3.1 access levels to the new 3.2 access levels
					foreach($user["canedit"] as $key=>$value) {
						if ($value=="no") $user["canedit"][$key] = "access";
						if ($value=="yes") $user["canedit"][$key] = "edit";
					}
					$user["email"] = $user_row["u_email"];
					$user["verified"] = $user_row["u_verified"];
					$user["verified_by_admin"] = $user_row["u_verified_by_admin"];
					$user["language"] = $user_row["u_language"];
					$user["pwrequested"] = $user_row["u_pwrequested"];
					$user["reg_timestamp"] = $user_row["u_reg_timestamp"];
					$user["reg_hashcode"] = $user_row["u_reg_hashcode"];
					$user["theme"] = $user_row["u_theme"];
					$user["loggedin"] = $user_row["u_loggedin"];
					$user["sessiontime"] = $user_row["u_sessiontime"];
					$user["contactmethod"] = $user_row["u_contactmethod"];
					if ($user_row["u_visibleonline"]!='N') $user["visibleonline"]=true;
					else $user["visibleonline"]=false;
					if ($user_row["u_editaccount"]!='N' || $user["canadmin"]) $user["editaccount"]=true;
					else $user["editaccount"]=false;
					$user["default_tab"] = $user_row["u_defaulttab"];
					$user["comment"] = stripslashes($user_row["u_comment"]);
					$user["comment_exp"] = $user_row["u_comment_exp"];
					$user["sync_gedcom"] = $user_row["u_sync_gedcom"];
					$user["relationship_privacy"] = $user_row["u_relationship_privacy"];
					$user["max_relation_path"] = $user_row["u_max_relation_path"];
					if ($user_row["u_auto_accept"]!='Y') $user["auto_accept"]=false;
					else $user["auto_accept"]=true;
					$users[$user_row["u_username"]] = $user;
				}
			}
		}
		$res->free();
		return $user;
	}
	return false;
}

/**
 * Stores a new message in the database
 *
 * 	$message["to"]
 *	$message["from"]
 *	$message["subject"]
 *	$message["body"]
 *	$message["method"]
 *	$message["url"]
 *	$message["no_from"]
 *	$message["from_name"]
 *
 * @author	Genmod Development Team
 * @param		array	$message		The text to be added to the message
 * @return 	boolean	True if mail has been stored/send or false if this failed
 */
function addMessage($message) {
	global $TBLPREFIX, $CONTACT_METHOD, $gm_lang,$CHARACTER_SET, $LANGUAGE, $GM_STORE_MESSAGES, $SERVER_URL, $gm_language, $GM_BASE_DIRECTORY, $GM_SIMPLE_MAIL, $WEBMASTER_EMAIL, $DBCONN;
	global $TEXT_DIRECTION, $TEXT_DIRECTION_array, $DATE_FORMAT, $DATE_FORMAT_array, $TIME_FORMAT, $TIME_FORMAT_array, $WEEK_START, $WEEK_START_array, $NAME_REVERSE, $NAME_REVERSE_array;
	
	// NOTE: Strip slashes from the body text
	$message["body"] = stripslashes($message["body"]);
	
	// NOTE: Details e-mail for the sender
	$message_sender = "";
	
	// NOTE: Check if it is a message from an unregistered user (from_name)
	if (isset($message["from_name"])) {
		// NOTE: Username
		$message_sender = $gm_lang["message_from_name"]." ".$message["from_name"]."<br />";
		// NOTE: Email address
		$message_sender .= $gm_lang["message_from"]." ".$message["from_email"]."<br /><br />";
	}
	
	// NOTE: Message body
	$message_sender .= $message["body"]."<br /><br />";
		
	// NOTE: Check the URL where the message is sent from and add it to the message
	if (!empty($message["url"])) {
		$message_sender .= "--------------------------------------<br />";
		$message_sender .= $gm_lang["viewing_url"].$SERVER_URL.$message["url"]."<br />";
	}
	
	// NOTE: Add system details
	$message_sender .= "=--------------------------------------=<br />";
	$message_sender .= "IP ADDRESS: ".$_SERVER['REMOTE_ADDR']."<br />";
	$message_sender .= "DNS LOOKUP: ".gethostbyaddr($_SERVER['REMOTE_ADDR'])."<br />";
	$message_sender .= "LANGUAGE: ".$LANGUAGE."<br />";
	
	// NOTE: E-mail Subject
	$subject_sender = "[".$gm_lang["Genmod_message"]."] ".stripslashes($message["subject"]);
	
	// NOTE: E-mail from
	$from ="";
	$fuser = GetUser($message["from"]);
	if (!$fuser) {
		$from = $message["from"];
		$message_sender = $gm_lang["message_email3"]."<br /><br />".stripslashes($message_sender);
	}
	else {
		if (!$GM_SIMPLE_MAIL) $from = "'".stripslashes($fuser["firstname"]." ".$fuser["lastname"]). "' <".$fuser["email"].">";
		else $from = $fuser["email"];
		$message_sender = $gm_lang["message_email2"]."<br /><br />".stripslashes($message_sender);

	}

	// NOTE: Details e-mail for the recipient
	$message_recipient = "";
	
	$tuser = GetUser($message["to"]);
	
	// NOTE: Load the recipients language
	$oldlanguage = $LANGUAGE;
	if (($tuser)&&(!empty($tuser["language"]))&&($tuser["language"]!=$LANGUAGE)) {
		$LANGUAGE = $tuser["language"];
		if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);
		$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
		$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
		$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
		$WEEK_START	= $WEEK_START_array[$LANGUAGE];
		$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
	}
	
	// NOTE: Check if it is a message from an unregistered user (from_name)
	if (isset($message["from_name"])) {
		$messagefrom = $gm_lang["message_from_name"]." ".$message["from_name"]."<br />";
		$messagefrom .= $gm_lang["message_from"]." ".$message["from_email"]."<br /><br />";
	}
	
	// NOTE: Check the URL where the message is sent from and add it to the message
	if (!empty($message["url"])) {
		$messagebody = "<br /><br />--------------------------------------<br />";
		$messagebody .= $gm_lang["viewing_url"]."<br />".$SERVER_URL.$message["url"]."<br />";
	}
	
	// NOTE: Add system details
	$messagebody .= "=--------------------------------------=<br />";
	$messagebody .= "IP ADDRESS: ".$_SERVER['REMOTE_ADDR']."<br />";
	$messagebody .= "DNS LOOKUP: ".gethostbyaddr($_SERVER['REMOTE_ADDR'])."<br />";
	$messagebody .= "LANGUAGE: $LANGUAGE<br />";
	
	// NOTE: If admin wants the messages to be stored, we do it here
	if (!isset($message["created"])) $message["created"] = gmdate ("M d Y H:i:s");
	if ($GM_STORE_MESSAGES && ($message["method"] != "messaging3" && $message["method"] != "mailto" && $message["method"] != "none")) {
		$messagestore = "";
		if (isset($messagefrom)) $messagestore .= $messagefrom;
		$messagestore .= $message["body"].$messagebody;
		$sql = "INSERT INTO ".$TBLPREFIX."messages VALUES ('0', '".$DBCONN->escapeSimple($message["from"])."','".$DBCONN->escapeSimple($message["to"])."','".$DBCONN->escapeSimple($message["subject"])."','".$DBCONN->escapeSimple($messagestore)."','".$DBCONN->escapeSimple($message["created"])."')";
		$res = dbquery($sql);
	}
	
	if ($message["method"] != "messaging") {
		// NOTE: E-mail subject recipient
		$subject_recipient = "[".$gm_lang["Genmod_message"]."] ".stripslashes($message["subject"]);
		
		// NOTE: E-mail from recipient
		if (!$fuser) {
			$message_recipient = $gm_lang["message_email1"];
			if (!empty($message["from_name"])) $message_recipient .= $message["from_name"]."<br /><br />".stripslashes($message["body"]);
			else $message_recipient .= $gm_lang["message_from_name"].$from."<br /><br />".stripslashes($message["body"]);
		}
		else {
			$message_recipient = $gm_lang["message_email1"]."<br /><br />";
			$message_recipient .= stripslashes($fuser["firstname"]." ".$fuser["lastname"])."<br /><br />".stripslashes($message["body"]);
		}
		// NOTE: Message body
		$message_recipient .= $messagebody."<br /><br />";
		
		$tuser = GetUser($message["to"]);
		// NOTE: the recipient must be a valid user in the system before it will send any mails
		if (!$tuser) return false;
		else {
			if (!$GM_SIMPLE_MAIL) $to = "'".stripslashes($tuser["firstname"]." ".$tuser["lastname"]). "' <".$tuser["email"].">";
			else $to = $tuser["email"];
		}
		if (!$fuser) {
			$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
			$header_sender = "From: Genmod-noreply@".$host;
		} 
		else $header_sender = "From: ".$to;
		
		// All prepared, if the recipient has an e-mail address send the email
		if (!empty($tuser["email"])) gmMail($to, $subject_recipient, $message_recipient, "From: ".$from);
	}
	
	// NOTE: Unload the recipients language and load the users language
	if (($tuser)&&(!empty($LANGUAGE))&&($oldlanguage!=$LANGUAGE)) {
		$LANGUAGE = $oldlanguage;
		if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
		$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
		$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
		$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
		$WEEK_START	= $WEEK_START_array[$LANGUAGE];
		$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
	}
	if ($message["method"] != "messaging") {
		if (!isset($message["no_from"])) {
			if (stristr($from, "Genmod-noreply@")){
				$admuser = GetUser($WEBMASTER_EMAIL);
				$from = $admuser["email"];
			}
			gmMail($from, $subject_sender, $message_sender, $header_sender);
		}
	}
	return true;
}

//----------------------------------- deleteMessage
//-- deletes a message in the database
function deleteMessage($message_id) {
	global $TBLPREFIX;

	$sql = "DELETE FROM ".$TBLPREFIX."messages WHERE m_id=".$message_id;
	$res = dbquery($sql);
	if ($res) return true;
	else return false;
}

//----------------------------------- getUserMessages
//-- Return an array of a users messages
function getUserMessages($username) {
	global $TBLPREFIX;

	$messages = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."messages WHERE m_to='$username' ORDER BY m_id DESC";
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$row = db_cleanup($row);
		$message = array();
		$message["id"] = $row["m_id"];
		$message["to"] = $row["m_to"];
		$message["from"] = $row["m_from"];
		$message["subject"] = stripslashes($row["m_subject"]);
		$message["body"] = stripslashes($row["m_body"]);
		$message["created"] = $row["m_created"];
		$messages[] = $message;
	}
	return $messages;
}

/**
 * stores a new favorite in the database
 * @param array $favorite	the favorite array of the favorite to add
 */
function addFavorite($favorite) {
	global $TBLPREFIX, $DBCONN;
	
	// -- make sure a favorite is added
	if (empty($favorite["gid"]) && empty($favorite["url"])) return false;
	
	//-- make sure this is not a duplicate entry
	$sql = "SELECT * FROM ".$TBLPREFIX."favorites WHERE ";
	if (!empty($favorite["gid"])) $sql .= "fv_gid='".$DBCONN->escapeSimple($favorite["gid"])."' ";
	if (!empty($favorite["url"])) $sql .= "fv_url='".$DBCONN->escapeSimple($favorite["url"])."' ";
	$sql .= "AND fv_file='".$DBCONN->escapeSimple($favorite["file"])."' AND fv_username='".$DBCONN->escapeSimple($favorite["username"])."'";
	$res = dbquery($sql);
	if ($res->numRows()>0) return false;
	
	//-- get the next favorite id number for the primary key
	$newid = get_next_id("favorites", "fv_id");
	//-- add the favorite to the database
	$sql = "INSERT INTO ".$TBLPREFIX."favorites VALUES ($newid, '".$DBCONN->escapeSimple($favorite["username"])."'," .
			"'".$DBCONN->escapeSimple($favorite["gid"])."','".$DBCONN->escapeSimple($favorite["type"])."'," .
			"'".$DBCONN->escapeSimple($favorite["file"])."'," .
			"'".$DBCONN->escapeSimple($favorite["url"])."'," .
			"'".$DBCONN->escapeSimple($favorite["title"])."'," .
			"'".$DBCONN->escapeSimple($favorite["note"])."')";
	$res = dbquery($sql);
	if ($res) return true;
	else return false;
}

/**
 * deleteFavorite
 * deletes a favorite in the database
 * @param int $fv_id	the id of the favorite to delete
 */
function deleteFavorite($fv_id) {
	global $TBLPREFIX;

	$sql = "DELETE FROM ".$TBLPREFIX."favorites WHERE fv_id=".$fv_id;
	$res = dbquery($sql);
	if ($res) return true;
	else return false;
}

/**
 * Get a user's favorites
 * Return an array of a users messages
 * @param string $username		the username to get the favorites for
 */
function getUserFavorites($username) {
	global $TBLPREFIX, $GEDCOMS, $DBCONN, $CONFIGURED;

	$favorites = array();
	//-- make sure we don't try to look up favorites for unconfigured sites
	if (!$CONFIGURED || DB::isError($DBCONN)) return $favorites;
	
	$sql = "SELECT * FROM ".$TBLPREFIX."favorites WHERE fv_username='".$DBCONN->escapeSimple($username)."'";
	$res = dbquery($sql);
	if (!$res) return $favorites;
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$row = db_cleanup($row);
		if (isset($GEDCOMS[$row["fv_file"]])) {
			$favorite = array();
			$favorite["id"] = $row["fv_id"];
			$favorite["username"] = $row["fv_username"];
			$favorite["gid"] = $row["fv_gid"];
			$favorite["type"] = $row["fv_type"];
			$favorite["file"] = $row["fv_file"];
			$favorite["title"] = $row["fv_title"];
			$favorite["note"] = $row["fv_note"];
			$favorite["url"] = $row["fv_url"];
			$favorites[] = $favorite;
		}
	}
	$res->free();
	return $favorites;
}

/**
 * Get blocks for the given username
 *
 * Retrieve the block configuration for the given user
 * if no blocks have been set yet, and the username is a valid user (not a gedcom) then try and load
 * the default user blocks.
 * @param string $username	the username or gedcom name for the blocks
 * @return array	an array of the blocks.  The two main indexes in the array are "main" and "right"
 */
function GetBlocks($username) {
	global $TBLPREFIX, $GEDCOMS, $DBCONN;

	$blocks = array();
	$blocks["main"] = array();
	$blocks["right"] = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."blocks WHERE b_username='".$DBCONN->escapeSimple($username)."' ORDER BY b_location, b_order";
	$res = dbquery($sql);
	if ($res->numRows() > 0) {
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			$row = db_cleanup($row);
			if (!isset($row["b_config"])) $row["b_config"]="";
			if ($row["b_location"]=="main") $blocks["main"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]));
			if ($row["b_location"]=="right") $blocks["right"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]));
		}
	}
	else {
		$user = GetUser($username);
		if ($user) {
			//-- if no blocks found, check for a default block setting
			$sql = "SELECT * FROM ".$TBLPREFIX."blocks WHERE b_username='defaultuser' ORDER BY b_location, b_order";
			$res2 = dbquery($sql);
			while($row = $res2->fetchRow(DB_FETCHMODE_ASSOC)){
				$row = db_cleanup($row);
				if (!isset($row["b_config"])) $row["b_config"]="";
				if ($row["b_location"]=="main") $blocks["main"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]));
				if ($row["b_location"]=="right") $blocks["right"][$row["b_order"]] = array($row["b_name"], unserialize($row["b_config"]));
			}
			$res2->free();
		}
	}
	$res->free();
	return $blocks;
}

/**
 * Set Blocks
 *
 * Sets the blocks for a gedcom or user portal
 * the $setdefault parameter tells the program to also store these blocks as the blocks used by default
 * @param String $username the username or gedcom name to update the blocks for
 * @param array $ublocks the new blocks to set for the user or gedcom
 * @param boolean $setdefault	if true tells the program to also set these blocks as the blocks for the defaultuser
 */
function setBlocks($username, $ublocks, $setdefault=false) {
	global $TBLPREFIX, $DBCONN;

	$sql = "DELETE FROM ".$TBLPREFIX."blocks WHERE b_username='".$DBCONN->escapeSimple($username)."'";
	$res = dbquery($sql);
	foreach($ublocks["main"] as $order=>$block) {
		$newid = get_next_id("blocks", "b_id");
		$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, '".$DBCONN->escapeSimple($username)."', 'main', '$order', '".$DBCONN->escapeSimple($block[0])."', '".$DBCONN->escapeSimple(serialize($block[1]))."')";
		$res = dbquery($sql);
		if ($setdefault) {
			$newid = get_next_id("blocks", "b_id");
			$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, 'defaultuser', 'main', '$order', '".$DBCONN->escapeSimple($block[0])."', '".$DBCONN->escapeSimple(serialize($block[1]))."')";
			$res = dbquery($sql);
		}
	}
	foreach($ublocks["right"] as $order=>$block) {
		$newid = get_next_id("blocks", "b_id");
		$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, '".$DBCONN->escapeSimple($username)."', 'right', '$order', '".$DBCONN->escapeSimple($block[0])."', '".$DBCONN->escapeSimple(serialize($block[1]))."')";
		$res = dbquery($sql);
		if ($setdefault) {
			$newid = get_next_id("blocks", "b_id");
			$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, 'defaultuser', 'right', '$order', '".$DBCONN->escapeSimple($block[0])."', '".$DBCONN->escapeSimple(serialize($block[1]))."')";
			$res = dbquery($sql);
		}
	}
}

/**
 * Adds a news item to the database
 *
 * This function adds a news item represented by the $news array to the database.
 * If the $news array has an ["id"] field then the function assumes that it is
 * as update of an older news item.
 *
 * @author John Finlay
 * @param array $news a news item array
 */
function addNews($news) {
	global $TBLPREFIX, $DBCONN;

	if (!isset($news["date"])) $news["date"] = time()-$_SESSION["timediff"];
	//$sql = "CREATE TABLE ".$TBLPREFIX."news (n_id INT NOT NULL auto_increment, n_username VARCHAR(100), n_date INT, n_text TEXT, PRIMARY KEY(n_id))";
	if (!empty($news["id"])) {
		// In case news items are added from usermigrate, it will also contain an ID.
		// So we check first if the ID exists in the database. If not, insert instead of update.
		$sql = "SELECT * FROM ".$TBLPREFIX."news where n_id=".$news["id"];
		$res = dbquery($sql);
		if ($res->numRows() == 0) {
			$sql = "INSERT INTO ".$TBLPREFIX."news VALUES (".$news["id"].", '".$DBCONN->escapeSimple($news["username"])."','".$DBCONN->escapeSimple($news["date"])."','".$DBCONN->escapeSimple($news["title"])."','".$DBCONN->escapeSimple($news["text"])."')";
		}
		else {
			$sql = "UPDATE ".$TBLPREFIX."news SET n_date='".$DBCONN->escapeSimple($news["date"])."', n_title='".$DBCONN->escapeSimple($news["title"])."', n_text='".$DBCONN->escapeSimple($news["text"])."' WHERE n_id=".$news["id"];
		}
		$res->free();
	}
	else {
		$newid = get_next_id("news", "n_id");
		$sql = "INSERT INTO ".$TBLPREFIX."news VALUES ($newid, '".$DBCONN->escapeSimple($news["username"])."','".$DBCONN->escapeSimple($news["date"])."','".$DBCONN->escapeSimple($news["title"])."','".$DBCONN->escapeSimple($news["text"])."')";
	}
	$res = dbquery($sql);
	if ($res) return true;
	else return false;
}

/**
 * Deletes a news item from the database
 *
 * @author John Finlay
 * @param int $news_id the id number of the news item to delete
 */
function deleteNews($news_id) {
	global $TBLPREFIX;

	$sql = "DELETE FROM ".$TBLPREFIX."news WHERE n_id=".$news_id;
	$res = dbquery($sql);
	if ($res) return true;
	else return false;
}

/**
 * Gets the news items for the given user or gedcom
 *
 * @param String $username the username or gedcom file name to get news items for
 */
function getUserNews($username) {
	global $TBLPREFIX, $DBCONN;

	$news = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."news WHERE n_username='".$DBCONN->escapeSimple($username)."' ORDER BY n_date DESC";
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$row = db_cleanup($row);
		$n = array();
		$n["id"] = $row["n_id"];
		$n["username"] = $row["n_username"];
		$n["date"] = $row["n_date"];
		$n["title"] = stripslashes($row["n_title"]);
		$n["text"] = stripslashes($row["n_text"]);
		$n["anchor"] = "article".$row["n_id"];
		$news[$row["n_id"]] = $n;
	}
	$res->free();
	return $news;
}

/**
 * Gets the news item for the given news id
 *
 * @param int $news_id the id of the news entry to get
 */
function getNewsItem($news_id) {
	global $TBLPREFIX;

	$news = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."news WHERE n_id='$news_id'";
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$row = db_cleanup($row);
		$n = array();
		$n["id"] = $row["n_id"];
		$n["username"] = $row["n_username"];
		$n["date"] = $row["n_date"];
		$n["title"] = stripslashes($row["n_title"]);
		$n["text"] = stripslashes($row["n_text"]);
		$n["anchor"] = "article".$row["n_id"];
		$res->free();
		return $n;
	}
}

/**
 * Update user sessiontime
 *
 * Update the user sessiontime whenever a page request
 * has been made. From the session.php this function is
 * called and it updates the users sessontime in the user table.
 * This ensures the user is not accidentally logged out.
 *
 * @author	Genmod Development Team
 * @param		string	$username		The username that needs to be updated
 * @return 	boolean	Return true or false as a result of the update
 */
function update_sessiontime($username) {
	global $TBLPREFIX, $TOTAL_QUERIES, $users, $GM_SESSION_TIME;
	
	if (isset($users[$username]) && time() - $users[$username]["sessiontime"] > $GM_SESSION_TIME) {
		userLogout($username);
		return false;
	}
	else {
		if (GetUser($username)) {
			$sql = "UPDATE ".$TBLPREFIX."users SET u_loggedin='Y', u_sessiontime='".time()."' WHERE u_username='".$username."'";
			$res = mysql_query($sql);
			$TOTAL_QUERIES++;
			if ($res) {
				$users[$username]["sessiontime"] = time();
				return true;
			}
			else return false;
		}
		else return false;
	}
 }
?>
