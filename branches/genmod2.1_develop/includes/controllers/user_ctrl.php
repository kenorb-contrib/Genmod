<?php
/**
 * Class controller for user
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
 * @version $Id: user_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class UserController {

	public $classname = "UserController";						// Name of this class
	
	private static $userobjsortfields = array("username", "");	// Default sort fields for user lists
	private static $userobjsortorder = "asc";					// Default sort order for user lists
	private static $adm_user_exists = null;						// If an admin user exists
	
	/**
	 * get a user from a gedcom id
	 *
	 * finds a user from their gedcom id
	 * @param string $id	the gedcom id to to search on
	 * @param string $gedcom	the gedcom filename to match
	 * @return string 	returns a username
	 */
	public function getUserByGedcomId($id, $gedcomid) {

		if (empty($id) || empty($gedcomid)) return false;
	
		$user = false;
		$id = DbLayer::EscapeQuery($id);
		$sql = "SELECT ug_username FROM ".TBLPREFIX."users_gedcoms WHERE ";
		$sql .= "ug_file='".$gedcomid."'";
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
	public function ClearUserGedcomIDs($gid, $gedcomid) {
		
		$users = self::GetUsers();
		foreach ($users as $username => $user) {
			$changed = false;
			if (isset($user->gedcomid[$gedcomid]) && $user->gedcomid[$gedcomid] == $gid) {
				$user->gedcomid[$gedcomid] = "";
				$changed = true;
			}
			if (isset($user->rootid[$gedcomid]) && $user->rootid[$gedcomid] == $gid) {
				$user->rootid[$gedcomid] = "";
				$changed = true;
			}
			if ($changed) {
				self::DeleteUser($user->username, "changed");
				self::AddUser($user, "changed");
			}
		}
	}
	
			 
	/**
	* does an admin user exits
	* 
	* Checks to see if an admin user has been created 
	* @return boolean true if an admin user has been defined
	*/ 
	public static function AdminUserExists() {
		
		if (is_null(self::$adm_user_exists)) {
			$sql = "SELECT COUNT(u_username) as admins FROM ".TBLPREFIX."users WHERE u_canadmin = 'Y'";
			$res = NewQuery($sql);
			if ($res) {
				$row = $res->FetchRow();
				if ($row[0] > 0) {
					self::$adm_user_exists = true;
				}
			}
			else self::$adm_user_exists = false;
		}
		return self::$adm_user_exists;
	}			
	
	/**
	* get the current username
	* 
	* gets the username for the currently active user
	* 1. first checks the session
	* 2. then checks the remember cookie
	* @return string 	the username of the user or an empty string if the user is not logged in
	*/
	public function GetUserName() {
		global $logout, $DBCONN;
		//-- this section checks if the session exists and uses it to get the username
		if (isset($_SESSION)) {
			if (!empty($_SESSION['gm_user'])) return $_SESSION['gm_user'];
		}
		if (isset($HTTP_SESSION_VARS)) {
			if (!empty($HTTP_SESSION_VARS['gm_user'])) return $HTTP_SESSION_VARS['gm_user'];
		}
		if (SystemConfig::$ALLOW_REMEMBER_ME) {
			$tSERVER_URL = preg_replace(array("'https?://'", "'www.'", "'/$'"), array("","",""), SERVER_URL);
			if ((isset($_SERVER['HTTP_REFERER'])) && (stristr($_SERVER['HTTP_REFERER'],$tSERVER_URL)!==false)) $referrer_found=true;
			if (!empty($_COOKIE["gm_rem"])&& (empty($referrer_found)) && empty($logout)) {
				if (!is_object($DBCONN)) return $_COOKIE["gm_rem"];
				$user =& User::GetInstance($_COOKIE["gm_rem"]);
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
	* them against the database.  The passwords are encrypted using the password_hash() function.
	* The username is stored in the <var>$_SESSION["gm_user"]</var> session variable.
	* @param string $username the username for the user attempting to login
	* @param string $password the plain text password to test
	* @return bool return true if the username and password credentials match a user in the database return false if they don't
	*/
	public function AuthenticateUser($username, $password) {

		$user =& User::GetInstance($username);

		if (!empty($user->username)) {
			if (password_verify($password, $user->password)) {
	        	if ((($user->verified == "Y") and ($user->verified_by_admin == "Y")) or ($user->canadmin != "")){
					$sql = "UPDATE ".TBLPREFIX."users SET u_loggedin='Y', u_sessiontime='".time()."' WHERE u_username='$username'";
					$res = NewQuery($sql);
					$user =& User::GetInstance($username);
					
					//-- reset the user's session
					$_SESSION = array();
					$_SESSION['gm_user'] = $username;
					
					// -- set the IP on which authentication took place.
					$_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
					
					//-- unset the cookie_login session var to show that they have logged in with their password
					$_SESSION['cookie_login'] = false;
					
					// -- The session vars MUST be set BEFORE writing to the log.
					WriteToLog("Users-&gt;AuthenticateUser-&gt; Login Successful -&gt; " . $username ." &lt;-", "I", "S");
					if (defined("GM_LANG_".$user->language)) $_SESSION['CLANGUAGE'] = $user->language;
					
					//-- only change the gedcom if the user does not have a gedcom id
					//-- for the currently active gedcom
					if (empty($user->gedcomid[GedcomConfig::$GEDCOMID])) {
						//-- if the user is not in the currently active gedcom then switch them
						//-- to the first gedcom for which they have an ID
						foreach($user->gedcomid as $ged=>$id) {
							if (!empty($id)) {
								$_SESSION['GEDCOMID']=$ged;
								break;
							}
						}
					}
					return true;
				}
			}
		}
		WriteToLog("Users-&gt;AuthenticateUser-&gt; Login Failed -&gt; " . $username ." &lt;-", "W", "S");
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
	public function UserLogout($username, $logtext = "") {
		global $LANGUAGE, $gm_username;

		if ($username=="") {
			if (isset($_SESSION["gm_user"])) $username = $_SESSION["gm_user"];
			else if (isset($_COOKIE["gm_rem"])) $username = $_COOKIE["gm_rem"];
			else return;
		}
		$sql = "UPDATE ".TBLPREFIX."users SET u_loggedin='N' WHERE BINARY u_username='".$username."'";
		$res = NewQuery($sql);
		if ($logtext == "") WriteToLog("UserController-&gt;UserLogout-&gt; Succesful logout for -&gt; " . $username . " &lt;-", "I", "S");
		else WriteToLog("UserController-&gt;UserLogout-&gt; ".$logtext." -&gt; " . $username . " &lt;-", "I", "S");

		if ((isset($_SESSION['gm_user']) && ($_SESSION['gm_user']==$username)) || (isset($_COOKIE['gm_rem'])&&$_COOKIE['gm_rem']==$username)) {
			if ($_SESSION['gm_user']==$username) {
				$_SESSION['gm_user'] = "";
				unset($_SESSION['gm_user']);
				if (isset($_SESSION["gm_counter"])) $tmphits = $_SESSION["gm_counter"];
				else $tmphits = -1;
				@session_destroy();
				$_SESSION["GEDCOMID"]=GedcomConfig::$GEDCOMID;
				$_SESSION["show_context_help"]="yes";
				@setcookie("gm_rem", "", -1000);
				if($tmphits>=0) $_SESSION["gm_counter"]=$tmphits; //set since it was set before so don't get double hits
			}
		}
		$gm_username = self::GetUserName();
	}
	
	/**
	* 	return a sorted array of user
 	*
	* returns a sorted array of the users in the system
	* @link https://Genmod.sourceforge.net/devdocs/arrays.php#users
	* @param string $field the field in the user array to sort on
	* @param string $order asc or dec
	* @return array returns a sorted array of users
	*/
	public function GetUsers($field = "username", $order = "asc", $sort2 = "firstname", $select="") {
	
		$selusers = array();
		$users = array();
		$sql = "SELECT * FROM ".TBLPREFIX."users_gedcoms ug RIGHT JOIN ".TBLPREFIX."users u ON BINARY u.u_username = BINARY ug.ug_username";
		if (!empty($select)) $sql .= " WHERE ".$select;
		$res = NewQuery($sql);
		if ($res) {
			while($user_row = $res->FetchAssoc()){
				$users[$user_row["u_username"]][] = $user_row;
			}
		}
		foreach ($users as $user => $data) {
			$selusers[$user] =& User::GetInstance($user, $data);
		}
		if (!empty($field)) self::$userobjsortfields = array($field);
		if (!empty($sort2)) self::$userobjsortfields[] = $sort2;
		if (!empty($order)) self::$userobjsortorder = $order;
		uasort($selusers, array("UserController", "UserObjSort"));
		return $selusers;
	}
	
	/**
	* Count the number of users present in Genmod
	* Returns either the number or false
	*/
	public function CountUsers() {
	
		$sql = "SELECT count(u_username) FROM ".TBLPREFIX."users";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->FetchRow();
			return $row[0];
		}
		return false;
	}
	
	/**
	* Add a new user
	* 
	* Adds a new user to the data store
	* @param array $newuser	The new user array to add
	* @param string $msg		The log message to write to the log
	*/
	public function AddUser($newuser, $msg = "added") {
		global $USE_RELATIONSHIP_PRIVACY, $MAX_RELATION_PATH_LENGTH, $GEDCOMS;

		$success = true;
		if (!isset($newuser->auto_accept)) $newuser->auto_accept = "N";
		$newuser->firstname = preg_replace("/\//", "", $newuser->firstname);
		$newuser->lastname = preg_replace("/\//", "", $newuser->lastname);
		$sql = "INSERT INTO ".TBLPREFIX."users VALUES('".$newuser->username."','".$newuser->password."','".DbLayer::EscapeQuery($newuser->firstname)."','".DbLayer::EscapeQuery($newuser->lastname)."'";
		if ($newuser->canadmin) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->email)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->verified)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->verified_by_admin)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->language)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->reg_timestamp)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->reg_hashcode)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->theme)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->loggedin)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->sessiontime)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->contactmethod)."'";
		if ($newuser->visibleonline) $sql .= ",'Y'";
		else $sql .= ",'N'";
		if ($newuser->editaccount) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->default_tab)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->comment)."'";
		$sql .= ",'".DbLayer::EscapeQuery($newuser->comment_exp)."'";
		if (isset($newuser->sync_gedcom)) $sql .= ",'".DbLayer::EscapeQuery($newuser->sync_gedcom)."'";
		else $sql .= ",'N'";
		if (isset($newuser->auto_accept) && $newuser->auto_accept==true) $sql .= ",'Y'";
		else $sql .= ",'N'";
		$sql .= ")";
		if ($res = NewQuery($sql)) {
			// Now write the rights
			foreach ($GEDCOMS as $id=>$value) {
				$sql = "INSERT INTO ".TBLPREFIX."users_gedcoms VALUES('0','";
				$sql .= $newuser->username."','".$id."','";
				if (isset($newuser->gedcomid[$id])) $sql .= $newuser->gedcomid[$id];
				$sql .= "','";
				if (isset($newuser->rootid[$id])) $sql .= $newuser->rootid[$id];
				$sql .= "','";
				if (isset($newuser->canedit[$id])) $sql .= $newuser->canedit[$id]."','";
				else $sql .= "none','";
				if (isset($newuser->gedcomadmin[$id]) && $newuser->gedcomadmin[$id] == true) $sql .= "Y','";
				else $sql .= "N','";
				if (isset($newuser->privgroup[$id])) $sql .= $newuser->privgroup[$id]."','";
				else $sql .= "access','";
				if (isset($newuser->relationship_privacy[$id])) $sql .= $newuser->relationship_privacy[$id]."','";
				else $sql .= "','";
				if (isset($newuser->max_relation_path[$id])) $sql .= $newuser->max_relation_path[$id]."','";
				else $sql .= $MAX_RELATION_PATH_LENGTH."','";
				if (isset($newuser->check_marriage_relations[$id])) $sql .= $newuser->check_marriage_relations[$id]."','";
				else $sql .= "','";
				if (isset($newuser->hide_live_people[$id])) $sql .= $newuser->hide_live_people[$id]."','";
				else $sql .= "','";
				if (isset($newuser->show_living_names[$id])) $sql .= $newuser->show_living_names[$id]."')";
				else $sql .= "')";
				
				if (!$res = NewQuery($sql)) $success = false;
			}
			$activeuser = self::GetUserName();
			if ($activeuser == "") $activeuser = "Anonymous user";
			$newuser =& User::RenewInstance($newuser->username);
			WriteToLog("UserController-&gt;AddUser-&gt; ".$activeuser." ".$msg." user -&gt; ".$newuser->username." &lt;-", "I", "S");

		}
		else $success = false;
		return $success;
	}

	/**
	* deletes the user with the given username.
	* @param string $username	the username to delete
	* @param string $msg		a message to write to the log file
	*/
	public function DeleteUser($username, $msg = "deleted") {
	
		$username = DbLayer::EscapeQuery($username);
		$sql = "DELETE FROM ".TBLPREFIX."users WHERE BINARY u_username='$username'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."users_gedcoms WHERE BINARY ug_username='$username'";
		$res = NewQuery($sql);
		$activeuser = self::GetUserName();
		if ($activeuser == "") $activeuser = "Anonymous user";
		if (($msg != "changed") && ($msg != "requested password for") && ($msg != "verified")) WriteToLog("UserController-&gt;DeleteUser-&gt; ".$activeuser." ".$msg." user -&gt; ".$username." &lt;-", "I", "S");
		if ($res) {
			User::DeleteInstance($username);
			return true;
		}
		else return false;
	}

	/**
	* creates a user as reference for a gedcom export
	* @param string $export_accesslevel
	*/
	public function CreateExportUser($export_accesslevel) {
	
		$u =& User::GetInstance("export");
		if (!$u->is_empty) self::DeleteUser("export");
		else User::DeleteInstance("export");
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
		$newuser->privgroup = array();
		if ($export_accesslevel == "gedadmin") $newuser->privgroup[GedcomConfig::$GEDCOMID] = "admin";
		elseif ($export_accesslevel == "user") $newuser->privgroup[GedcomConfig::$GEDCOMID] = "access";
		else $newuser->privgroup[GedcomConfig::$GEDCOMID] = "none";
		$newuser->email = "";
		$newuser->verified = "Y";
		$newuser->verified_by_admin = "Y";
		$newuser->language = "english";
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
		$newuser->check_marriage_relations = array();
		$newuser->show_living_names = array();
		$newuser->hide_live_people = array();
		$newuser->canedit = array();
		$newuser->auto_accept = "N";
		self::AddUser($newuser);
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
	public function UpdateSessiontime() {
		global $gm_user;
	
		if (!$gm_user->is_empty) {
			if(time() - $gm_user->sessiontime > GM_SESSION_TIME) {
				self::UserLogout($gm_user->username);
				return false;
			}
			else {
				$sql = "UPDATE ".TBLPREFIX."users SET u_loggedin='Y', u_sessiontime='".time()."' WHERE BINARY u_username='".$gm_user->username."'";
				$res = NewQuery($sql);
				if ($res) {
					User::RenewInstance($gm_user->username);
					return true;
				}
				else return false;
			}
		}
	}

	private static function UserObjSort($a, $b) {
		
		foreach(self::$userobjsortfields as $ind=>$field) {
			if (!isset($a->$field)) $aname = "";
			else $aname = str2upper($a->$field);
			if (!isset($b->$field)) $bname = "";
			else $bname = str2upper($b->$field);
			if ($aname != $bname) {
				if (is_numeric($aname) && is_numeric($bname)) {
					if (self::$userobjsortorder == "asc") return ($aname > $bname);
					else return ($bname > $aname);
				}
				else if (self::$userobjsortorder == "asc") return StringSort($aname, $bname);
				else return !StringSort($aname, $bname);
				break;
			}
		}
		return 0;
	}
	
	public function CheckPrivacyOverrides($gedid) {
		
		$sql = "SELECT count(ug_username) FROM ".TBLPREFIX."users_gedcoms WHERE (ug_relationship_privacy<>'' OR ug_hide_live_people<>'' OR ug_show_living_names<>'') AND ug_file='".$gedid."'";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->FetchRow();
			return ($row[0] == 0 ? false : true);
		}
		return false;
	}
}
?>