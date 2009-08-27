<?php
/**
 * Class file for user
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2008 Genmod Development Team
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
 *
 * @package Genmod
 * @subpackage DataModel
 * @version $Id$
 */

if (strstr($_SERVER["SCRIPT_NAME"],"user_class")) {
	require "../intrusion.php";
}

class Users {

	var $userobjects = array();
	var $userobjsortfields = array("username", "");
	var $userobjsortorder = "asc";
	
	/**
	* Get a user array
	*
	* Finds a user from the given username and returns a user array 
	* @param string $username the username of the user to return
	* @return array the user array to return
	*/
	function GetUser($username="", $userfields="") {
		global $TBLPREFIX, $DBLAYER;

		if (!$DBLAYER->connected) return false;
		if (empty($username)) return new user("");
		if (!isset($this->userobjects[$username])) {
			$this->userobjects[$username] = new user($username, $userfields);
		}
		if (isset($this->userobjects[$username])) return $this->userobjects[$username];
		else return false;
	}

	/**
	 * get a user from a gedcom id
	 *
	 * finds a user from their gedcom id
	 * @param string $id	the gedcom id to to search on
	 * @param string $gedcom	the gedcom filename to match
	 * @return string 	returns a username
	 */
	function getUserByGedcomId($id, $gedcom) {
		global $TBLPREFIX, $users, $REGEXP_DB, $DBCONN, $GEDCOMS;

		if (empty($id) || empty($gedcom)) return false;
	
		$user = false;
		$id = $DBCONN->EscapeQuery($id);
		$sql = "SELECT ug_username FROM ".$TBLPREFIX."users_gedcoms WHERE ";
		$sql .= "ug_gedfile='".$GEDCOMS[$gedcom]["id"]."'";
		$sql .= "AND ug_gedcomid='".$id."'";
		$res = NewQuery($sql, false);
		if (!$res) return false;
		if ($res->NumRows()==0) return false;
		if ($res) {
			$row = $res->FetchAssoc();
			$username = $row["ug_username"];
			return $username;
		}
		return false;
	}

	/**
	* clear the rootid and gedcom id when an id is deleted
	* @param string $id		the gedcom id to be cleared
	* @param string gedcom 	the gedcom filename to match
	* @return boolean		true if any update took place
	*/
	function ClearUserGedcomIDs($gid, $gedcom) {
		
		$users = $this->GetUsers();
		foreach ($users as $username => $user) {
			$changed = false;
			if (isset($user->gedcomid[$gedcom]) && $user->gedcomid[$gedcom] == $gid) {
				$user->gedcomid[$gedcom] = "";
				$changed = true;
			}
			if (isset($user->rootid[$gedcom]) && $user->rootid[$gedcom] == $gid) {
				$user->rootid[$gedcom] = "";
				$changed = true;
			}
			if ($changed) {
				$this->DeleteUser($user->username, "changed");
				$this->AddUser($user, "changed");
				$this->userobjects[$username] = $user;
			}
		}
	}
	
			 
	/**
	* does an admin user exits
	* 
	* Checks to see if an admin user has been created 
	* @return boolean true if an admin user has been defined
	*/ 
	function AdminUserExists() {
		global $TBLPREFIX, $DBCONN;
		
		if (isset($this->adm_user_exists)) return $this->adm_user_exists;
		
		$sql = "SELECT COUNT(u_username) as admins FROM ".$TBLPREFIX."users WHERE u_canadmin = 'Y'";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->FetchRow();
			if ($row[0] > 0) {
				$this->adm_user_exists = true;
				return true;
			}
		}
		$this->adm_user_exists = false;
		return false;
	}			
	
	/**
	* get the current username
	* 
	* gets the username for the currently active user
	* 1. first checks the session
	* 2. then checks the remember cookie
	* @return string 	the username of the user or an empty string if the user is not logged in
	*/
	function GetUserName() {
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
				$user = $this->GetUser($_COOKIE["gm_rem"]);
				if (!empty($user->username)) {
					if (time() - $user->sessiontime < 60*60*24*7) {
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
	* authenticate a username and password
	*
	* This function takes the given <var>$username</var> and <var>$password</var> and authenticates
	* them against the database.  The passwords are encrypted using the crypt() function.
	* The username is stored in the <var>$_SESSION["gm_user"]</var> session variable.
	* @param string $username the username for the user attempting to login
	* @param string $password the plain text password to test
	* @return bool return true if the username and password credentials match a user in the database return false if they don't
	*/
	function AuthenticateUser($username, $password) {
		global $TBLPREFIX, $GEDCOM, $gm_lang;

		$user = $this->GetUser($username);
		if (!empty($user->username)) {
			if (crypt($password, $user->password) == $user->password) {
	        	if ((($user->verified == "yes") and ($user->verified_by_admin == "yes")) or ($user->canadmin != "")){
					$sql = "UPDATE ".$TBLPREFIX."users SET u_loggedin='Y', u_sessiontime='".time()."' WHERE u_username='$username'";
					$res = NewQuery($sql);
					unset($this->userobjects[$username]);
					$user = $this->GetUser($username);
					
					//-- reset the user's session
						$_SESSION = array();
					$_SESSION['gm_user'] = $username;
					
					// -- set the IP on which authentication took place.
					$_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
					
					//-- unset the cookie_login session var to show that they have logged in with their password
					$_SESSION['cookie_login'] = false;
					
					// -- The session vars MUST be set BEFORE writing to the log.
					WriteToLog("Users->AuthenticateUser: Login Successful -> " . $username ." <-", "I", "S");
					if (isset($gm_lang[$user->language])) $_SESSION['CLANGUAGE'] = $user->language;
					
					//-- only change the gedcom if the user does not have an gedcom id
					//-- for the currently active gedcom
					if (empty($user->gedcomid[$GEDCOM])) {
						//-- if the user is not in the currently active gedcom then switch them
						//-- to the first gedcom for which they have an ID
						foreach($user->gedcomid as $ged=>$id) {
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
		WriteToLog("Users->AuthenticateUser: Login Failed -> " . $username ." <-", "W", "S");
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
	function UserLogout($username, $logtext = "") {
		global $TBLPREFIX, $GEDCOM, $LANGUAGE, $gm_username;

		if ($username=="") {
			if (isset($_SESSION["gm_user"])) $username = $_SESSION["gm_user"];
			else if (isset($_COOKIE["gm_rem"])) $username = $_COOKIE["gm_rem"];
			else return;
		}
		$sql = "UPDATE ".$TBLPREFIX."users SET u_loggedin='N' WHERE BINARY u_username='".$username."'";
		$res = NewQuery($sql);
		if ($logtext == "") WriteToLog("Users->UserLogout: Succesful logout for " . $username . " <-", "I", "S");
		else WriteToLog("Users->UserLogout: ".$logtext." -> " . $username . " <-", "I", "S");

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
		$gm_username = $this->GetUserName();
	}
	
	/**
	* 	return a sorted array of user
 	*
	* returns a sorted array of the users in the system
	* @link http://Genmod.sourceforge.net/devdocs/arrays.php#users
	* @param string $field the field in the user array to sort on
	* @param string $order asc or dec
	* @return array returns a sorted array of users
	*/
	function GetUsers($field = "username", $order = "asc", $sort2 = "firstname", $select="") {
		global $TBLPREFIX;
	
		$selusers = array();
		$users = array();
		$sql = "SELECT * FROM ".$TBLPREFIX."users_gedcoms ug RIGHT JOIN ".$TBLPREFIX."users u ON BINARY u.u_username = BINARY ug.ug_username";
		if (!empty($select)) $sql .= " WHERE ".$select;
		$res = NewQuery($sql);
		if ($res) {
			while($user_row = $res->FetchAssoc()){
				$users[$user_row["u_username"]][] = $user_row;
			}
		}
		foreach ($users as $user => $data) {
			$selusers[$user] = $this->GetUser($user, $data);
		}
		if (!empty($field)) $this->userobjsortfields = array($field);
		if (!empty($sort2)) $this->userobjsortfields[] = $sort2;
		if (!empty($order)) $this->userobjsortorder = $order;
		uasort($selusers, array($this, "UserObjSort"));
		return $selusers;
	}
	
	/**
	* Count the number of users present in Genmod
	* Returns either the number or false
	*/
	function CountUsers() {
		global $TBLPREFIX;
	
		$sql = "SELECT count(u_username) FROM ".$TBLPREFIX."users";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->FetchRow();
			return $row[0];
		}
		return false;
	}

	
	/**
	* check if the given user has access privileges on this gedcom
	*
	* takes a username and checks if the user has access privileges to view the private
	* gedcom data.
	* @param string $username the username of the user to check
	* @return boolean true if user can access false if they cannot
	*/
	function userPrivAccess($username) {
		global $GEDCOM;

		if (empty($username)) return false;
		if ($this->userIsAdmin($username)) return true;
		$user = $this->GetUser($username);
		if (empty($user->username)) return false;
		if (isset($user->privgroup[$GEDCOM])) {
			if ($user->privgroup[$GEDCOM]!="none") return true;
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
	function userCanEdit($username, $ged="") {
		global $ALLOW_EDIT_GEDCOM, $GEDCOM;

		if (empty($ged)) $ged = $GEDCOM;

		if (!$ALLOW_EDIT_GEDCOM) return false;
		if (empty($username)) return false;
		
		// Site admins can edit all
		if ($this->userIsAdmin($username)) return true;
		
		$user = $this->GetUser($username);
		if (empty($user->username)) return false;
		if (isset($user->canedit[$ged])) {
			// yes and true are old values
			if ($user->canedit[$ged]=="yes" || $user->canedit[$ged]=="edit" || $user->gedcomadmin[$ged] || $user->canedit[$ged]=="accept" || $user->canedit[$ged]===true) return true;
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
	function userCanAccept($username, $ged="") {
		global $ALLOW_EDIT_GEDCOM, $GEDCOM;

		if (empty($ged)) $ged = $GEDCOM;

		if (!$ALLOW_EDIT_GEDCOM) return false;
		if (empty($username)) return false;
		
		// Site admins can accept all
		if ($this->userIsAdmin($username)) return true;
		
		$user = $this->GetUser($username);
		if (empty($user->username)) return false;
		if (isset($user->gedcomadmin[$ged]) && $user->gedcomadmin[$ged]) return true;
		if (isset($user->canedit[$ged])) {
			if ($user->canedit[$ged]=="accept") return true;
			else return false;
		}
		else return false;
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
		if (empty($username)) return false;
		if ($this->userIsAdmin($username)) return true;
		$user = $this->GetUser($username);
		if (empty($user->username)) return false;
		if (isset($user->gedcomadmin[$ged]) && $user->gedcomadmin[$ged]) return true;
		else return false;
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
		$user = $this->GetUser($username);
		if (empty($user->username)) return false;
		return $user->canadmin;
	}
	
	/**
	* Should user's changed automatically be accepted 
	* @param string $username	the user name of the user to check
	* @return boolean 		true if the changes should automatically be accepted
	*/
	function userAutoAccept($username = "") {
		if (empty($username)) $username = $this->GetUserName();
		if (empty($username)) return false;
		
		if (!$this->userCanAccept($username)) return false;
		$user = $this->GetUser($username);
		if ($user->auto_accept) return true;
	}
	
	/**
	* Add a new user
	* 
	* Adds a new user to the data store
	* @param array $newuser	The new user array to add
	* @param string $msg		The log message to write to the log
	*/
	function AddUser($newuser, $msg = "added") {
		global $TBLPREFIX, $DBCONN, $USE_RELATIONSHIP_PRIVACY, $MAX_RELATION_PATH_LENGTH, $GEDCOMS;

//		if (!isset($newuser->relationship_privacy)) {
//			if ($USE_RELATIONSHIP_PRIVACY) $newuser->relationship_privacy = "Y";
//			else $newuser->relationship_privacy = "N";
//		} 
		if (!isset($newuser->auto_accept)) $newuser->auto_accept = "N";
		$newuser->firstname = preg_replace("/\//", "", $newuser->firstname);
		$newuser->lastname = preg_replace("/\//", "", $newuser->lastname);
		$sql = "INSERT INTO ".$TBLPREFIX."users VALUES('".$newuser->username."','".$newuser->password."','".$DBCONN->EscapeQuery($newuser->firstname)."','".$DBCONN->EscapeQuery($newuser->lastname)."'";
		if ($newuser->canadmin) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->email)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->verified)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->verified_by_admin)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->language)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->pwrequested)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->reg_timestamp)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->reg_hashcode)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->theme)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->loggedin)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->sessiontime)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->contactmethod)."'";
		if ($newuser->visibleonline) $sql .= ",'Y'";
		else $sql .= ",'N'";
		if ($newuser->editaccount) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->default_tab)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->comment)."'";
		$sql .= ",'".$DBCONN->EscapeQuery($newuser->comment_exp)."'";
		if (isset($newuser->sync_gedcom)) $sql .= ",'".$DBCONN->EscapeQuery($newuser->sync_gedcom)."'";
		else $sql .= ",'N'";
		if (isset($newuser->auto_accept) && $newuser->auto_accept==true) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ")";
		if ($res = NewQuery($sql)) {
			// Now write the rights
			foreach ($GEDCOMS as $id=>$value) {
				$ged = get_gedcom_from_id($id);
				$sql = "INSERT INTO ".$TBLPREFIX."users_gedcoms VALUES('0','";
				$sql .= $newuser->username."','".$value["id"]."','";
				if (isset($newuser->gedcomid[$ged])) $sql .= $newuser->gedcomid[$ged];
				$sql .= "','";
				if (isset($newuser->rootid[$ged])) $sql .= $newuser->rootid[$ged];
				$sql .= "','";
				if (isset($newuser->canedit[$ged])) $sql .= $newuser->canedit[$ged]."','";
				else $sql .= "none','";
				if (isset($newuser->gedcomadmin[$ged]) && $newuser->gedcomadmin[$ged] == true) $sql .= "Y','";
				else $sql .= "N','";
				if (isset($newuser->privgroup[$ged])) $sql .= $newuser->privgroup[$ged]."','";
				else $sql .= "access','";
				if (isset($newuser->relationship_privacy[$ged])) $sql .= $newuser->relationship_privacy[$ged]."','";
				else $sql .= "','";
				if (isset($newuser->max_relation_path[$ged])) $sql .= $newuser->max_relation_path[$ged]."','";
				else $sql .= $MAX_RELATION_PATH_LENGTH."','";
				if (isset($newuser->hide_live_people[$ged])) $sql .= $newuser->hide_live_people[$ged]."','";
				else $sql .= "','";
				if (isset($newuser->show_living_names[$ged])) $sql .= $newuser->show_living_names[$ged]."')";
				else $sql .= "')";
				
				$res = NewQuery($sql);
			}
			$activeuser = $this->GetUserName();
			if ($activeuser == "") $activeuser = "Anonymous user";
			$this->userobjects[$newuser->username] = $newuser;
			WriteToLog("Users->AddUser: ".$activeuser." ".$msg." user -> ".$newuser->username." <-", "I", "S");
			return true;
		}
		else return false;
	}

	/**
	* deletes the user with the given username.
	* @param string $username	the username to delete
	* @param string $msg		a message to write to the log file
	*/
	function DeleteUser($username, $msg = "deleted") {
		global $TBLPREFIX, $DBCONN;
	
		if (isset($this->userobjects[$username])) unset($this->userobjects[$username]);
		$username = $DBCONN->EscapeQuery($username);
		$sql = "DELETE FROM ".$TBLPREFIX."users WHERE BINARY u_username='$username'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."users_gedcoms WHERE BINARY ug_username='$username'";
		$res = NewQuery($sql);
		$activeuser = $this->GetUserName();
		if ($activeuser == "") $activeuser = "Anonymous user";
		if (($msg != "changed") && ($msg != "requested password for") && ($msg != "verified")) WriteToLog("Users->DeleteUser: ".$activeuser." ".$msg." user -&gt; ".$username." &lt;-", "I", "S");
		if ($res) return true;
		else return false;
	}

	/**
	* creates a user as reference for a gedcom export
	* @param string $export_accesslevel
	*/
	function CreateExportUser($export_accesslevel) {
		global $GEDCOM;
		
		$u = $this->GetUser("export");
		if (!$u->is_empty) $this->DeleteUser("export");
		$newuser = new User("");
		$newuser->firstname = "Export";
		$newuser->lastname = "useraccount";
		$newuser->username = "export";
		$allow = "abcdefghijkmnpqrstuvwxyz23456789"; 
		srand((double)microtime()*1000000);
		$password = ""; 
		for($i=0; $i<8; $i++) { 
			$password .= $allow[rand()%strlen($allow)]; 
		} 
		$newuser->password = $password;
		$newuser->gedcomid = "";
		$newuser->rootid = "";
		if ($export_accesslevel == "admin") $newuser->canadmin = true;
		else $newuser->canadmin = false;
		if ($export_accesslevel == "gedadmin") $newuser->canedit[$GEDCOM] = "admin";
		elseif ($export_accesslevel == "user") $newuser->canedit[$GEDCOM] = "access";
		else $newuser->canedit[$GEDCOM] = "none";
		$newuser->email = "";
		$newuser->verified = "yes";
		$newuser->verified_by_admin = "yes";
		$newuser->language = "english";
		$newuser->pwrequested = "";
		$newuser->reg_timestamp = "";
		$newuser->reg_hashcode = "";
		$newuser->theme = "";
		$newuser->loggedin = "";
		$newuser->sessiontime = "";
		$newuser->contactmethod = "none";
		$newuser->visibleonline = false;
		$newuser->editaccount = false;
		$newuser->default_tab = 9;
		$newuser->comment = "";
		$newuser->comment_exp = "Dummy user for export purposes";
		$newuser->sync_gedcom = "N";
		$newuser->gedcomadmin = array();
		$newuser->relationship_privacy = array();
		$newuser->max_relation_path = array();
		$newuser->show_living_names = array();
		$newuser->hide_live_people = array();
		$newuser->canedit = array();
		$newuser->privgroup = array();
		$newuser->auto_accept = "N";
		$this->AddUser($newuser);
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
	function UpdateSessiontime($username) {
		global $TBLPREFIX, $users, $GM_SESSION_TIME;
	
		if (isset($this->userobjects[$username]) && time() - $this->userobjects[$username]->sessiontime > $GM_SESSION_TIME) {
			$this->UserLogout($username);
			return false;
		}
		else {
			if ($this->GetUser($username)) {
				$sql = "UPDATE ".$TBLPREFIX."users SET u_loggedin='Y', u_sessiontime='".time()."' WHERE BINARY u_username='".$username."'";
				$res = NewQuery($sql);
				if ($res) {
					$this->userobjects[$username]->sessiontime = time();
					return true;
				}
				else return false;
			}
			else return false;
		}
	}

	/**
	* Determine if the user can view raw GEDCOM lines
	* @author	Genmod Development Team
	* @param		string	$username		The username or if not set, the current user
	* @param		string	$ged			The GEDCOM file or if not set, the current user
	* @return 		boolean					Return true or false as a result
	*/ 
	function userCanViewGedlines($username="", $ged="") {
		global $gm_username, $GEDCOM, $GEDCOMID, $SHOW_GEDCOM_RECORD;
		
		if (empty($username)) $username = $gm_username;

		if ($SHOW_GEDCOM_RECORD == -1) return false;
		if ($SHOW_GEDCOM_RECORD == 0) return true;
		if ($SHOW_GEDCOM_RECORD == 1 && $this->UserPrivAccess($username, $GEDCOM)) return true;
		if ($SHOW_GEDCOM_RECORD == 2 && $this->UserCanEdit($username, $GEDCOM)) return true;
		if ($SHOW_GEDCOM_RECORD == 3 && $this->UserCanAccept($username, $GEDCOM)) return true;
		if ($SHOW_GEDCOM_RECORD == 4 && $this->UserGedcomAdmin($username, $GEDCOM)) return true;
		if ($SHOW_GEDCOM_RECORD == 5 && $this->UserIsAdmin($username)) return true;
		return false;
	}

	/**
	* Determine if the user can edit raw GEDCOM lines
	* @author	Genmod Development Team
	* @param		string	$username		The username or if not set, the current user
	* @param		string	$ged			The GEDCOM file or if not set, the current user
	* @return 		boolean					Return true or false as a result
	*/ 
	function userCanEditGedlines($username="", $ged="") {
		global $gm_username, $GEDCOM, $GEDCOMID, $ALLOW_EDIT_GEDCOM, $EDIT_GEDCOM_RECORD;
		
		if (!$ALLOW_EDIT_GEDCOM) return false;
	
		if (empty($username)) $username = $gm_username;
		
		// Note: options 0 and 1 are not configurable in the settings.
		if ($EDIT_GEDCOM_RECORD == -1) return false;
		if ($EDIT_GEDCOM_RECORD == 0) return true;
		if ($EDIT_GEDCOM_RECORD == 1 && $this->UserPrivAccess($username, $GEDCOM)) return true;
		if ($EDIT_GEDCOM_RECORD == 2 && $this->UserCanEdit($username, $GEDCOM)) return true;
		if ($EDIT_GEDCOM_RECORD == 3 && $this->UserCanAccept($username, $GEDCOM)) return true;
		if ($EDIT_GEDCOM_RECORD == 4 && $this->UserGedcomAdmin($username, $GEDCOM)) return true;
		if ($EDIT_GEDCOM_RECORD == 5 && $this->UserIsAdmin($username)) return true;
		return false;
	}
	
	function UserCanEditOwn($username, $pid) {
		global $GEDCOM, $USE_QUICK_UPDATE;
	
		if ($this->UserCanEdit($username)) return true;
		if (!$USE_QUICK_UPDATE) return false;
		if (empty($pid)) return false;

		$user = $this->GetUser($username);	
		if (!empty($user->gedcomid[$GEDCOM])) {
			if ($pid==$user->gedcomid[$GEDCOM]) return true;
			else {
				$famids = Array_Merge(FindSfamilyIds($user->gedcomid[$GEDCOM]), FindFamilyIds($user->gedcomid[$GEDCOM]));
				foreach($famids as $indexval => $fam) {
					$famid = $fam["famid"];
					if (GetChangeData(true, $famid, true)) {
						$rec = GetChangeData(false, $famid, true, "gedlines");
						$famrec = $rec[$GEDCOM][$famid];
					}
					else $famrec = FindFamilyRecord($famid);
					if (preg_match("/1 HUSB @$pid@/", $famrec)>0) return true;
					if (preg_match("/1 WIFE @$pid@/", $famrec)>0) return true;
					if (preg_match("/1 CHIL @$pid@/", $famrec)>0) return true;
				}
			}
		}
		return false;
	}	 
	
	/**
	* get current user's access level
	*
	* checks the current user and returns their privacy access level
	* @return int		their access level
	*/
	function getUserAccessLevel($username="") {
		global $PRIV_PUBLIC, $PRIV_NONE, $PRIV_USER, $GEDCOM, $gm_username;

		if (empty($username)) $username = $gm_username;
		if (empty($username)) return $PRIV_PUBLIC;

		if ($this->userGedcomAdmin($username)) return $PRIV_NONE;
		if ($this->userPrivAccess($username)) return $PRIV_USER;
		return $PRIV_PUBLIC;
	}
	
	function UserObjSort($a, $b) {
		
		foreach($this->userobjsortfields as $ind=>$field) {
			if (!isset($a->$field)) $aname = "";
			else $aname = str2upper($a->$field);
			if (!isset($b->$field)) $bname = "";
			else $bname = str2upper($b->$field);
			if ($aname != $bname) {
				if (is_numeric($aname) && is_numeric($bname)) {
					if ($this->userobjsortorder == "asc") return ($aname > $bname);
					else return ($bname > $aname);
				}
				else if ($this->userobjsortorder == "asc") return StringSort($aname, $bname);
				else return !StringSort($aname, $bname);
				break;
			}
		}
		return 0;
	}
	
	function CheckPrivacyOverrides($gedid) {
		global $TBLPREFIX;
		
		$sql = "SELECT count(ug_username) FROM ".$TBLPREFIX."users_gedcoms WHERE (ug_relationship_privacy<>'' OR ug_hide_live_people<>'' OR ug_show_living_names<>'') AND ug_gedfile='".$gedid."'";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->FetchRow();
			return ($row[0] == 0 ? false : true);
		}
		return false;
	}
	
	function ShowActionLog($username) {
		global $SHOW_ACTION_LIST;

		if ($SHOW_ACTION_LIST >= $this->getUserAccessLevel($username)) return true;
		else return false;
	}
}

 
class User {

	var $username = "";
	var $password = "";
	var $firstname = "";
	var $lastname = "";
	var $canadmin = false;
	var $canedit = array();
	var $email = "";
	var $verified = "";
	var $verified_by_admin = "";
	var $language = "";
	var $pwrequested = "";
	var $reg_timestamp = "";
	var	$reg_hashcode = "";
	var	$theme = "";
	var	$loggedin = "";
	var	$sessiontime = "";
	var	$contactmethod = "";
	var	$visibleonline = false;
	var	$editaccount = false;
	var	$default_tab = "9";
	var	$comment = "";
	var	$comment_exp = "";
	var	$sync_gedcom = "";
	var	$auto_accept = false;
	var $u_username = "";
	var $gedcomid = array();
	var $rootid = array();
	var $privgroup = array();
	var $gedcomadmin = array();
	var	$relationship_privacy = array();
	var	$max_relation_path = array();
	var $hide_live_people = array();
	var $show_living_names = array();
	var $is_empty = true;
	
	function User($username="", $userfields="") {
		return $this->GetUser($username, $userfields);
	}
	
	function GetUser($username, $userfields="") {
		global $TBLPREFIX, $userobjects, $REGEXP_DB, $GEDCOMS, $DBLAYER, $DBCONN;

		if (empty($username)) return false;

		if (!is_array($userfields)) {
			if (!$DBLAYER->connected) return false;
			$username = $DBCONN->EscapeQuery($username);
			$sql = "SELECT * FROM ".$TBLPREFIX."users_gedcoms ug RIGHT JOIN ".$TBLPREFIX."users u ON BINARY u.u_username = BINARY ug.ug_username WHERE BINARY u_username='".$username."'";
//			$sql = "SELECT * FROM ".$TBLPREFIX."users ";
			$res = NewQuery($sql);
			if ($res===false) return false;
			if ($res->NumRows()==0) return false;
			if ($res) {
				$user = array();
				while($user_row = $res->FetchAssoc()) {
					if ($user_row) {
						$user[] = $user_row;
					}
				}
				$res->FreeResult();
				$this->FillUser($username, $user);
			
				return true;
			}
			else return false;
		}
		else {
			$this->FillUser($username, $userfields);
			return true;
		}
	}

	function FillUser($username, $user_data) {
		global $userobjects, $TBLPREFIX;
		
		foreach ($user_data as $key => $user_row) {
			if (empty($this->username) && !empty($username)) {
				$this->username = $user_row["u_username"];
				$this->firstname = stripslashes($user_row["u_firstname"]);
				$this->lastname = stripslashes($user_row["u_lastname"]);
				$this->password = $user_row["u_password"];
				if ($user_row["u_canadmin"]=='Y') $this->canadmin = true;
				$this->email = $user_row["u_email"];
				$this->verified = $user_row["u_verified"];
				$this->verified_by_admin = $user_row["u_verified_by_admin"];
				$this->language = $user_row["u_language"];
				$this->pwrequested = $user_row["u_pwrequested"];
				$this->reg_timestamp = $user_row["u_reg_timestamp"];
				$this->reg_hashcode = $user_row["u_reg_hashcode"];
				$this->theme = $user_row["u_theme"];
				$this->loggedin = $user_row["u_loggedin"];
				$this->sessiontime = $user_row["u_sessiontime"];
				$this->contactmethod = $user_row["u_contactmethod"];
				if ($user_row["u_visibleonline"]!='N') $this->visibleonline = true;
				else $this->visibleonline = false;
				if ($user_row["u_editaccount"]!='N' || $this->canadmin) $this->editaccount = true;
				else $this->editaccount = false;
				$this->default_tab = $user_row["u_defaulttab"];
				$this->comment = stripslashes($user_row["u_comment"]);
				$this->comment_exp = $user_row["u_comment_exp"];
				$this->sync_gedcom = $user_row["u_sync_gedcom"];
				if ($user_row["u_auto_accept"]!='Y') $this->auto_accept = false;
				else $this->auto_accept = true;
				$this->is_empty = false;
			}
		
			// Now get the rights
			$ged = get_gedcom_from_id($user_row["ug_gedfile"]);
			if (isset($user_row["ug_canedit"])) $this->canedit[$ged] = $user_row["ug_canedit"];
			if (isset($user_row["ug_privgroup"])) $this->privgroup[$ged] = $user_row["ug_privgroup"];
			if (isset($user_row["ug_gedcomadmin"])) {
				if ($user_row["ug_gedcomadmin"] == "Y") $this->gedcomadmin[$ged] = true;
				else $this->gedcomadmin[$ged] = false;
			}
			if (isset($user_row["ug_relationship_privacy"])) $this->relationship_privacy[$ged] = $user_row["ug_relationship_privacy"];
			if (isset($user_row["ug_max_relation_path"])) $this->max_relation_path[$ged] = $user_row["ug_max_relation_path"];
			if (isset($user_row["ug_hide_live_people"])) $this->hide_live_people[$ged] = $user_row["ug_hide_live_people"];
			if (isset($user_row["ug_show_living_names"])) $this->show_living_names[$ged] = $user_row["ug_show_living_names"];
			if (isset($user_row["ug_gedcomid"])) $this->gedcomid[$ged] = $user_row["ug_gedcomid"];
			if (isset($user_row["ug_rootid"])) $this->rootid[$ged] = $user_row["ug_rootid"];
		}
	}
}