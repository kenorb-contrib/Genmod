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

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class User {

	public $classname = "User";				// Name of this class
	private static $usercache = array();	// Holder of the instances for this class
	public $username = "";					// Name of the user (used to logon)
	public $password = "";					// Encrypted password of the user
	public $firstname = "";					// Firstname of the user
	public $lastname = "";					// Lastname of the user
	public $canadmin = false;				// If the user is administrator (sitewide)
	public $canedit = array();				// If the user has edit rights (per gedcom)
	public $email = "";						// Email address of the user
	public $verified = "";					// If the user has verified his/her request for an account
	public $verified_by_admin = "";			// If the admin has approved the user's request for an account
	public $language = "";					// Default language for the user
	public $pwrequested = "";				// 
	public $reg_timestamp = "";
	public $reg_hashcode = "";
	public $theme = "";
	public $loggedin = "";
	public $sessiontime = "";
	public $contactmethod = "";
	public $visibleonline = false;
	public $editaccount = false;
	public $default_tab = "9";
	public $comment = "";
	public $comment_exp = "";
	public $sync_gedcom = "";
	public $auto_accept = false;
	public $u_username = "";
	public $gedcomid = array();
	public $rootid = array();
	public $privgroup = array();
	public $gedcomadmin = array();
	public $relationship_privacy = array();
	public $max_relation_path = array();
	public $hide_live_people = array();
	public $show_living_names = array();
	public $show_action_log = null;
	public $is_empty = true;
	
	public static function getInstance($username, $userdata="") {
		global $gm_username;
		
		if (empty($username)) $username = $gm_username;
		
		if (empty($username)) $cachename = "empty";
		else $cachename = $username;
		
		if (!isset(self::$usercache[$cachename])) {
			self::$usercache[$cachename] = new User($username, $userdata="");
		}
		return self::$usercache[$cachename];
	}
	
	public static function RenewInstance($username) {
		
		if (isset(self::$usercache[$username])) unset(self::$usercache[$username]);
		if (!empty($username)) self::$usercache[$username] = new User($username);

	}
		
	public static function DeleteInstance($username) {
		
		if (isset(self::$usercache[$username])) unset(self::$usercache[$username]);

	}
	
	public function __construct($username="", $userfields="") {
		return $this->GetUser($username, $userfields);
	}

	public function ObjCount() {
		return count(self::$usercache);
	}	
	
	private function GetUser($username, $userfields="") {
		global $userobjects, $GEDCOMS, $DBCONN;

		if (empty($username) || $username == "empty") return false;

		if (!is_array($userfields)) {
			if (!$DBCONN->connected) return false;
			$username = DbLayer::EscapeQuery($username);
			$sql = "SELECT * FROM ".TBLPREFIX."users_gedcoms ug RIGHT JOIN ".TBLPREFIX."users u ON BINARY u.u_username = BINARY ug.ug_username WHERE BINARY u_username='".$username."'";
//			$sql = "SELECT * FROM ".TBLPREFIX."users ";
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

	private function FillUser($username, $user_data) {
		global $userobjects;
		
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
	
	public function ShowActionLog() {
		global $SHOW_ACTION_LIST;
		
		if ($SHOW_ACTION_LIST >= $this->getUserAccessLevel()) return true;
		else return false;
	}
	
	/**
	* get current user's access level
	*
	* checks the current user and returns their privacy access level
	* @return int		their access level
	*/
	public function getUserAccessLevel() {
		global $PRIV_PUBLIC, $PRIV_NONE, $PRIV_USER, $GEDCOM;

		if ($this->username == "") return $PRIV_PUBLIC;

		if ($this->userGedcomAdmin()) return $PRIV_NONE;
		if ($this->userPrivAccess()) return $PRIV_USER;
		return $PRIV_PUBLIC;
	}
	
	/**
	* check if the given user has access privileges on this gedcom
	*
	* takes a username and checks if the user has access privileges to view the private
	* gedcom data.
	* @param string $username the username of the user to check
	* @return boolean true if user can access false if they cannot
	*/
	public function userPrivAccess() {
		global $GEDCOM;

		if ($this->username == "") return false;
		if ($this->userIsAdmin()) return true;
		if (isset($this->privgroup[$GEDCOM])) {
			if ($this->privgroup[$GEDCOM]!="none") return true;
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
	public function userGedcomAdmin($ged="") {
		global $GEDCOM;

		if (empty($ged)) $ged = $GEDCOM;

		if ($_SESSION['cookie_login']) return false;
		if ($this->username == "") return false;
		if ($this->userIsAdmin()) return true;
		if (isset($this->gedcomadmin[$ged]) && $user->gedcomadmin[$ged]) return true;
		else return false;
	}
	
	/**
	* check if given username is an admin
	*
	* takes a username and checks if the
	* user has administrative privileges
	* to change the configuration files
	*/
	public function userIsAdmin() {
	
		if ($this->username == "") return false;
		if ($_SESSION['cookie_login']) return false;
		return $this->canadmin;
	}
	
	/**
	* check if the given user has write privileges on this gedcom
	*
	* takes a username and checks if the user has write privileges to change
	* the gedcom data. First check if the administrator has turned on editing privileges for this gedcom
	* @param string $username the username of the user to check
	* @return boolean true if user can edit false if they cannot
	*/
	public function userCanEdit($ged="") {
		global $ALLOW_EDIT_GEDCOM, $GEDCOM;

		if (empty($ged)) $ged = $GEDCOM;

		if (!$ALLOW_EDIT_GEDCOM) return false;
		if ($this->username == "empty" || $this->username == "") return false;
		
		// Site admins can edit all
		if ($this->userIsAdmin()) return true;
		
		if (isset($this->canedit[$ged])) {
			// yes and true are old values
			if ($this->canedit[$ged]=="yes" || $this->canedit[$ged]=="edit" || $this->gedcomadmin[$ged] || $this->canedit[$ged]=="accept" || $this->canedit[$ged]===true) return true;
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
	public function userCanAccept($ged="") {
		global $ALLOW_EDIT_GEDCOM, $GEDCOM;

		if (empty($ged)) $ged = $GEDCOM;

		if (!$ALLOW_EDIT_GEDCOM) return false;
		if ($this->username == "") return false;
		
		// Site admins can accept all
		if ($this->userIsAdmin()) return true;
		
		if (isset($this->gedcomadmin[$ged]) && $this->gedcomadmin[$ged]) return true;
		if (isset($this->canedit[$ged])) {
			if ($this->canedit[$ged]=="accept") return true;
			else return false;
		}
		else return false;
	}

	/**
	* Should user's changed automatically be accepted 
	* @param string $username	the user name of the user to check
	* @return boolean 		true if the changes should automatically be accepted
	*/
	public function userAutoAccept() {
		
		if ($this->username == "") return false;
		if (!$this->userCanAccept()) return false;
		if ($this->auto_accept) return true;
	}
	
	/**
	* Determine if the user can view raw GEDCOM lines
	* @author	Genmod Development Team
	* @param		string	$username		The username or if not set, the current user
	* @param		string	$ged			The GEDCOM file or if not set, the current user
	* @return 		boolean					Return true or false as a result
	*/ 
	public function userCanViewGedlines($ged="") {
		global $GEDCOM, $SHOW_GEDCOM_RECORD;
		
		if ($this->username == "empty" || $this->username == "") return false;

		if ($SHOW_GEDCOM_RECORD == -1) return false;
		if ($SHOW_GEDCOM_RECORD == 0) return true;
		if ($SHOW_GEDCOM_RECORD == 1 && $this->UserPrivAccess($GEDCOM)) return true;
		if ($SHOW_GEDCOM_RECORD == 2 && $this->UserCanEdit($GEDCOM)) return true;
		if ($SHOW_GEDCOM_RECORD == 3 && $this->UserCanAccept($GEDCOM)) return true;
		if ($SHOW_GEDCOM_RECORD == 4 && $this->UserGedcomAdmin($GEDCOM)) return true;
		if ($SHOW_GEDCOM_RECORD == 5 && $this->UserIsAdmin()) return true;
		return false;
	}

	/**
	* Determine if the user can edit raw GEDCOM lines
	* @author	Genmod Development Team
	* @param		string	$username		The username or if not set, the current user
	* @param		string	$ged			The GEDCOM file or if not set, the current user
	* @return 		boolean					Return true or false as a result
	*/ 
	public function userCanEditGedlines($username="", $ged="") {
		global $GEDCOM, $ALLOW_EDIT_GEDCOM, $EDIT_GEDCOM_RECORD;
		
		if (!$ALLOW_EDIT_GEDCOM) return false;
	
		if ($this->username == "empty" || $this->username == "") return false;
		
		// Note: options 0 and 1 are not configurable in the settings.
		if ($EDIT_GEDCOM_RECORD == -1) return false;
		if ($EDIT_GEDCOM_RECORD == 0) return true;
		if ($EDIT_GEDCOM_RECORD == 1 && $this->UserPrivAccess($GEDCOM)) return true;
		if ($EDIT_GEDCOM_RECORD == 2 && $this->UserCanEdit($GEDCOM)) return true;
		if ($EDIT_GEDCOM_RECORD == 3 && $this->UserCanAccept($GEDCOM)) return true;
		if ($EDIT_GEDCOM_RECORD == 4 && $this->UserGedcomAdmin($GEDCOM)) return true;
		if ($EDIT_GEDCOM_RECORD == 5 && $this->UserIsAdmin()) return true;
		return false;
	}
	
	public function UserCanEditOwn($pid) {
		global $GEDCOM, $USE_QUICK_UPDATE;
	
		if ($this->UserCanEdit()) return true;
		if (!$USE_QUICK_UPDATE) return false;
		if (empty($pid)) return false;

		if (!empty($this->gedcomid[$GEDCOM])) {
			if ($pid == $this->gedcomid[$GEDCOM]) return true;
			else {
				$famids = Array_Merge(FindSfamilyIds($this->gedcomid[$GEDCOM]), FindFamilyIds($this->gedcomid[$GEDCOM]));
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
	
}
?>