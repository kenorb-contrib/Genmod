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
 * @version $Id: user_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class User {

	public $classname = "User";					// Name of this class
	private static $cache = array();			// Holder of the instances for this class

	public $username = "";						// Name of the user (used to logon)
	public $password = "";						// Encrypted password of the user
	public $firstname = "";						// Firstname of the user
	public $lastname = "";						// Lastname of the user
	public $canadmin = false;					// If the user is administrator (sitewide), true/false
	public $email = "";							// Email address of the user
	public $verified = "";						// If the user has verified his/her request for an account, blank or Y
	public $verified_by_admin = "";				// If the admin has approved the user's request for an account, blank or Y
	public $language = "";						// Default language for the user
	public $reg_timestamp = "";					// Timestamp of creation of this user
	public $reg_hashcode = "";					// Hashcode for verification of the user, empty if added by an admin
	public $theme = "";							// Theme for this user, empty if site default, otherwise the path to the theme
	public $loggedin = "";						// If the user is currently logged in (until reset after timeout or logoff), Y or N
	public $sessiontime = "";					// Timestamp of last access to a page
	public $contactmethod = "";					// Contact method for this user:
												//		messaging	-	Internal messages only
												//		messaging2	-	Internal messages and emails
												//		messaging3	-	Emails only
												//		none		-	No messaging at all
	public $visibleonline = false;				// If the user is visible in the logged in block (always is to admins), true or false
	public $editaccount = false;				// If the user can edit his own settings, true or false
	public $default_tab = "9";					// Default tab to show on individual pages. 9 is gedcom default, otherwise it overrides the gedcom default
	public $comment = "";						// Admin's comment on this user
	public $comment_exp = "";					// Date on which the admin is warned that this date is expired
	public $sync_gedcom = "";					// Synchronise the users email address to his ID's email address in the gedcom, Y or N
	public $auto_accept = false;				// If changes by this user are automatically accepted (if possible), Y or N
	public $is_empty = true;					// if the user doesn't exist, this is true
	
	// Settings per gedcom ID
	public $gedcomid = array();					// Own ID of the user in the gedcom
	public $rootid = array();					// Root ID for various pages for this user
	public $privgroup = array();				// Access level of the user: none, access or admin
	public $gedcomadmin = array();				// User can administer this gedcom, true or false
	public $canedit = array();					// If the user has edit rights
	public $relationship_privacy = array();		// Override for the privacy settings, blank is default, Y or N the overrides
	public $max_relation_path = array();		// Override for the privacy settings, if the above is Y, length of the path
	public $check_marriage_relations = array();	// Override for the privacy settings, use spouse paths in determining path lengths
	public $hide_live_people = array();			// Override for the privacy settings, blank is default, Y or N the overrides
	public $show_living_names = array();		// Override for the privacy settings, blank is default, Y or N the overrides

		
	public static function getInstance($username, $userdata="") {
		global $gm_username;
		
		if (empty($username)) $username = $gm_username;
		
		if (empty($username)) $cachename = "empty";
		else $cachename = $username;
		
		if (!isset(self::$cache[$cachename])) {
			self::$cache[$cachename] = new User($username, $userdata="");
		}
		return self::$cache[$cachename];
	}
	
	public static function RenewInstance($username) {
		
		if (isset(self::$cache[$username])) unset(self::$cache[$username]);
		if (!empty($username)) self::$cache[$username] = new User($username);
		return self::$cache[$username];

	}
		
	public static function DeleteInstance($username) {
		
		if (isset(self::$cache[$username])) unset(self::$cache[$username]);

	}
	
	public function __construct($username="", $userfields="") {
		$this->GetUser($username, $userfields);
	}

	public function ObjCount() {
		return count(self::$cache);
	}	
	
	private function GetUser($username, $userfields="") {
		global $DBCONN;

		if (empty($username) || $username == "empty") return false;

		if (!is_array($userfields)) {
			if (!$DBCONN->connected) return false;
			$username = DbLayer::EscapeQuery($username);
			$sql = "SELECT * FROM ".TBLPREFIX."users u LEFT JOIN ".TBLPREFIX."users_gedcoms ug ON BINARY u.u_username = BINARY ug.ug_username WHERE BINARY u.u_username='".$username."'";
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
		global $MAX_RELATION_PATH_LENGTH, $GEDCOMS;
		
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
			if (!is_null($user_row["ug_file"])) {
				$ged = $user_row["ug_file"];
				
				if (isset($user_row["ug_canedit"])) $this->canedit[$ged] = $user_row["ug_canedit"];
				if (isset($user_row["ug_privgroup"])) $this->privgroup[$ged] = $user_row["ug_privgroup"];
				if (isset($user_row["ug_gedcomadmin"])) {
					if ($user_row["ug_gedcomadmin"] == "Y") $this->gedcomadmin[$ged] = true;
					else $this->gedcomadmin[$ged] = false;
				}
				if (isset($user_row["ug_relationship_privacy"])) $this->relationship_privacy[$ged] = $user_row["ug_relationship_privacy"];
				if (isset($user_row["ug_max_relation_path"])) $this->max_relation_path[$ged] = $user_row["ug_max_relation_path"];
				if (isset($user_row["ug_check_marriage_relations"])) $this->check_marriage_relations[$ged] = $user_row["ug_check_marriage_relations"];
				if (isset($user_row["ug_hide_live_people"])) $this->hide_live_people[$ged] = $user_row["ug_hide_live_people"];
				if (isset($user_row["ug_show_living_names"])) $this->show_living_names[$ged] = $user_row["ug_show_living_names"];
				if (isset($user_row["ug_gedcomid"])) $this->gedcomid[$ged] = $user_row["ug_gedcomid"];
				if (isset($user_row["ug_rootid"])) $this->rootid[$ged] = $user_row["ug_rootid"];
			}
		}
		foreach ($GEDCOMS as $ged => $settings) {
			if (!isset($this->gedcomid[$ged])) $this->gedcomid[$ged] = "";
			if (!isset($this->rootid[$ged])) $this->rootid[$ged] = "";
			if (!isset($this->canedit[$ged])) $this->canedit[$ged] = "none";
			if (!isset($this->gedcomadmin[$ged])) $this->gedcomadmin[$ged] = false;
			if (!isset($this->privgroup[$ged])) $this->privgroup[$ged] = "access";
			if (!isset($this->relationship_privacy[$ged])) $this->relationship_privacy[$ged] = "";
			// Gedcom admins and admins are excluded from relationship privacy
			if ($this->UserGedcomAdmin($ged)) $this->relationship_privacy[$ged] = "N";
			if (!isset($this->max_relation_path[$ged])) $this->max_relation_path[$ged] = $MAX_RELATION_PATH_LENGTH;
			if (!isset($this->check_marriage_relations[$ged])) $this->check_marriage_relations[$ged] = "";
			if (!isset($this->hide_live_people[$ged])) $this->hide_live_people[$ged] = "";
			if (!isset($this->show_living_names[$ged])) $this->show_living_names[$ged] = "";
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
		global $PRIV_PUBLIC, $PRIV_NONE, $PRIV_USER;

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

		if ($this->username == "") return false;
		if ($this->userIsAdmin()) return true;
		if (isset($this->privgroup[GedcomConfig::$GEDCOMID])) {
			if ($this->privgroup[GedcomConfig::$GEDCOMID]!="none") return true;
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
	public function userGedcomAdmin($gedid="") {

		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;

		if ($_SESSION['cookie_login']) return false;
		if ($this->username == "") return false;
		if ($this->userIsAdmin()) return true;
		if (isset($this->gedcomadmin[$gedid]) && $this->gedcomadmin[$gedid]) return true;
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
	public function userCanEdit($gedid="") {

		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;

		if (!GedcomConfig::$ALLOW_EDIT_GEDCOM) return false;
		if ($this->username == "empty" || $this->username == "") return false;
		
		// Site admins can edit all
		if ($this->userIsAdmin()) return true;
		
		if (isset($this->canedit[$gedid])) {
			// yes and true are old values
			if ($this->canedit[$gedid]=="yes" || $this->canedit[$gedid]=="edit" || $this->gedcomadmin[$gedid] || $this->canedit[$gedid]=="accept" || $this->canedit[$gedid]===true) return true;
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
	public function userCanAccept($gedid="") {

		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;

		if (!GedcomConfig::$ALLOW_EDIT_GEDCOM) return false;
		if ($this->username == "") return false;
		
		// Site admins can accept all
		if ($this->userIsAdmin()) return true;
		
		if (isset($this->gedcomadmin[$gedid]) && $this->gedcomadmin[$gedid]) return true;
		if (isset($this->canedit[$gedid])) {
			if ($this->canedit[$gedid]=="accept") return true;
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
	public function userCanViewGedlines($gedid="") {
		
		if ($this->username == "empty" || $this->username == "") return false;

		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;
		
		if (GedcomConfig::$SHOW_GEDCOM_RECORD == -1) return false;
		if (GedcomConfig::$SHOW_GEDCOM_RECORD == 0) return true;
		if (GedcomConfig::$SHOW_GEDCOM_RECORD == 1 && $this->UserPrivAccess($gedid)) return true;
		if (GedcomConfig::$SHOW_GEDCOM_RECORD == 2 && $this->UserCanEdit($gedid)) return true;
		if (GedcomConfig::$SHOW_GEDCOM_RECORD == 3 && $this->UserCanAccept($gedid)) return true;
		if (GedcomConfig::$SHOW_GEDCOM_RECORD == 4 && $this->UserGedcomAdmin($gedid)) return true;
		if (GedcomConfig::$SHOW_GEDCOM_RECORD == 5 && $this->UserIsAdmin()) return true;
		return false;
	}

	/**
	* Determine if the user can use the external search modules
	* @author	Genmod Development Team
	* @param		string	$username		The username or if not set, the current user
	* @param		string	$ged			The GEDCOM file or if not set, the current user
	* @return 		boolean					Return true or false as a result
	*/ 
	public function userExternalSearch($gedid="") {
		
		if ($this->username == "empty" || $this->username == "") return false;

		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;
		
		if (GedcomConfig::$SHOW_EXTERNAL_SEARCH == -1) return false;
		if (GedcomConfig::$SHOW_EXTERNAL_SEARCH == 0) return true;
		if (GedcomConfig::$SHOW_EXTERNAL_SEARCH == 1 && $this->UserPrivAccess($gedid)) return true;
		if (GedcomConfig::$SHOW_EXTERNAL_SEARCH == 2 && $this->UserCanEdit($gedid)) return true;
		if (GedcomConfig::$SHOW_EXTERNAL_SEARCH == 3 && $this->UserCanAccept($gedid)) return true;
		if (GedcomConfig::$SHOW_EXTERNAL_SEARCH == 4 && $this->UserGedcomAdmin($gedid)) return true;
		if (GedcomConfig::$SHOW_EXTERNAL_SEARCH == 5 && $this->UserIsAdmin()) return true;
		return false;
	}
	
	/**
	* Determine if the user can edit raw GEDCOM lines
	* @author	Genmod Development Team
	* @param		string	$username		The username or if not set, the current user
	* @param		string	$ged			The GEDCOM file or if not set, the current user
	* @return 		boolean					Return true or false as a result
	*/ 
	public function userCanEditGedlines($username="", $gedid="") {
		
		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;
		$can = false;
		SwitchGedcom($gedid);
		if (GedcomConfig::$ALLOW_EDIT_GEDCOM) {
			if ($this->username == "empty" || $this->username == "") $can = false;
			// Note: options 0 and 1 are not configurable in the settings.
			elseif (GedcomConfig::$EDIT_GEDCOM_RECORD == -1) $can = false;
			elseif (GedcomConfig::$EDIT_GEDCOM_RECORD == 0) $can = true;
			elseif (GedcomConfig::$EDIT_GEDCOM_RECORD == 1 && $this->UserPrivAccess(GedcomConfig::$GEDCOMID)) $can = true;
			elseif (GedcomConfig::$EDIT_GEDCOM_RECORD == 2 && $this->UserCanEdit(GedcomConfig::$GEDCOMID)) $can = true;
			elseif (GedcomConfig::$EDIT_GEDCOM_RECORD == 3 && $this->UserCanAccept(GedcomConfig::$GEDCOMID)) $can = true;
			elseif (GedcomConfig::$EDIT_GEDCOM_RECORD == 4 && $this->UserGedcomAdmin(GedcomConfig::$GEDCOMID)) $can = true;
			elseif (GedcomConfig::$EDIT_GEDCOM_RECORD == 5 && $this->UserIsAdmin()) $can = true;
		}
		SwitchGedcom();
		return $can;
	}
	
	public function UserCanEditOwn($pid) {
	
		if ($this->UserCanEdit()) return true;
		if (empty($pid)) return false;

		if (!empty($this->gedcomid[GedcomConfig::$GEDCOMID])) {
			if ($pid == $this->gedcomid[GedcomConfig::$GEDCOMID]) return true;
			else {
				$person =& Person::GetInstance($this->gedcomid[GedcomConfig::$GEDCOMID]);
				foreach ($person->childfamilies as $index => $fam) {
					if ($fam->husb_id == $pid || $fam->wife_id == $pid || $fam->husbold_id == $pid || $fam->wifeold_id == $pid) return true;
					foreach ($fam->children_ids as $key => $childid) {
						if ($pid == $childid) return true;
					}
				}
				foreach ($person->spousefamilies as $index => $fam) {
					if ($fam->husb_id == $pid || $fam->wife_id == $pid || $fam->husbold_id == $pid || $fam->wifeold_id == $pid) return true;
					foreach ($fam->children_ids as $key => $childid) {
						if ($pid == $childid) return true;
					}
				}
			}
		}
		return false;
	}
}
?>