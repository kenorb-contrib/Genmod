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

	var $classname = "User";
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
	
	public function __construct($username="", $userfields="") {
		return $this->GetUser($username, $userfields);
	}
	
	public function GetUser($username, $userfields="") {
		global $TBLPREFIX, $userobjects, $REGEXP_DB, $GEDCOMS, $DBCONN;

		if (empty($username)) return false;

		if (!is_array($userfields)) {
			if (!$DBCONN->connected) return false;
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

	public function FillUser($username, $user_data) {
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
?>