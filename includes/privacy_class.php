<?php
/**
 * Class file for privacy
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

class Privacy {

	var $GEDPRIV = array();
	
	// This reads the settings for a specified gedcomid.
	// If the ID is empty, it will return settings from the default values in the PrivacyObject class.
	function Privacy($gedcomid="") {
		$this->ReadPrivacy($gedcomid);
	}
	
	// This actually reads the settings from the object array or, if not present, creates a new object.
	// The settings from the specified object are globalized here.
	function ReadPrivacy($gedcomid="", $user_override=true) {
		global $GEDCOMS, $GEDCOMID;

		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		
		// If we read the settings with no user ovverrides, we must renew. Cached are the ones read from session.php, WITH override
		if (!$user_override || !isset($this->GEDPRIV[$gedcomid])) $this->GEDPRIV[$gedcomid] = new PrivacyObject($gedcomid, $user_override);
		
		$vars = Get_Object_vars($this->GEDPRIV[$gedcomid]);

		foreach ($vars as $name => $value) {
			if ($name != "GEDCOM" && $name != "GEDCOMID") {
				global $$name;
				$$name = $value;
			}
		}
		return true;
	}
	
	// This deletes settings for a specific gedcomid from cache and from the DB
	function DeletePrivacy($gedcomid) {
		global $TBLPREFIX, $DBLAYER, $GEDCOMS;

		if (!isset($gedcomid)) return false;
		if (!$DBLAYER->connected) return false;
		
		if (!isset($this->GEDPRIV[$gedcomid])) return false;
		
		unset($this->GEDPRIV[$gedcomid]);

		$sql = "DELETE FROM ".$TBLPREFIX."privacy WHERE p_gedcomid='".$gedcomid."'";
		$res = NewQuery($sql);
		$ct = $res->NumRows($res->result);
		if ($ct == "0") return false;
		$res->FreeResult();
		return true;
	}
	
	// Here a new set of values is stored in the DB and in the cache.
	function StorePrivacy($settings) {
		
		$gedcomid = $settings->GEDCOMID;
		$this->DeletePrivacy($gedcomid);
		$settings->WritePrivacy();
		$this->ReadPrivacy($gedcomid);
	}
	
	function ClearPrivacyGedcomIDs($pid, $gedcom) {
		
		$gedid = get_id_from_gedcom($gedcom);
		if (!$gedid) return false;
		$priv = new PrivacyObject($gedid);
		$update = false;
		foreach($priv->user_privacy as $user => $id) {
			foreach($id as $key => $value) {
				if ($key == $pid) {
					unset($priv->user_privacy[$user][$key]);
					$update = true;
				}
			}
		}
		if (isset($priv->person_privacy[$pid])) {
			unset($priv->person_privacy[$pid]);
			$update = true;
		}
		if (isset($priv->person_facts[$pid])) {
			unset($priv->person_facts[$pid]);
			$update = true;
		}
		$priv->StoreArrays();
	}
}
		

 
 
class PrivacyObject {

	// These vars are the same as (uppercase) the fields in the DB.
	// The values for $PRIV_XX are filled in here as defaults.
	var $GEDCOMID = "";
	var $GEDCOM = "";
	var $PRIVACY_VERSION = "3.3";
	var $PRIV_HIDE = -1;
	var $PRIV_PUBLIC = 2;
	var $PRIV_USER = 1;
	var $PRIV_NONE = 0;
	var $SHOW_DEAD_PEOPLE = 2;
	var $HIDE_LIVE_PEOPLE = 1;
	var $SHOW_LIVING_NAMES = 1;
	var $SHOW_SOURCES = 1;
	var $LINK_PRIVACY = 1;
	var $MAX_ALIVE_AGE = 120;
	var $ENABLE_CLIPPINGS_CART = 1;
	var $SHOW_ACTION_LIST = 0;
	var $USE_RELATIONSHIP_PRIVACY = 0;
	var $MAX_RELATION_PATH_LENGTH = 3;
	var $CHECK_MARRIAGE_RELATIONS = 1;
	var $CHECK_CHILD_DATES = 1;
	var $PRIVACY_BY_YEAR = 0;
	var $PRIVACY_BY_RESN = 1;
	var $person_privacy = array();
	var $user_privacy = array();
	var $global_facts = array();
	var $person_facts = array();
	
	function PrivacyObject($gedcomid, $user_override=true) {
		
		// Initialize some values, which cannot be done with var, as in PHP4 it only accepts constants
		$this->GEDCOMID = $gedcomid;
		$this->global_facts["SSN"]["show"] = $this->PRIV_NONE;
		$this->global_facts["SSN"]["details"] = $this->PRIV_NONE;
		$this->GetPrivacy($gedcomid, $user_override);
	}
	
	function GetPrivacy($gedcomid="", $user_override) {
		global $TBLPREFIX, $GEDCOMS, $DBLAYER, $gm_username, $Users;
		
		if (!$DBLAYER->connected) return false;
		
		if (!empty($gedcomid)) {
			$sql = "SELECT * FROM ".$TBLPREFIX."privacy WHERE (p_gedcomid='".$gedcomid."')";
			$res = NewQuery($sql);
			if ($res) {
				$ct = $res->NumRows();
				if ($ct != 0) {
					while($row = $res->FetchAssoc($res->result)){
						foreach ($row as $key => $value) {
							$var = substr($key, 2);
							if ($var == "global_facts") {
								$$var = unserialize($value);
								$this->$var = $$var;
								$temp = array();
								foreach ($$var as $key1 => $value1) {
									foreach ($value1 as $key2 => $value2) {
										$temp[$key1][$key2] = $$value2;
									}
								}
								$$var = $temp;
								$this->$var = $$var;
							}
							else {
								if ($var == "user_privacy") {
									$$var = unserialize($value);
									$this->$var = $$var;
									$temp = array();
									foreach ($$var as $key1 => $value1) {
										foreach ($value1 as $key2 => $value2) {
											$temp[$key1][$key2] = $value2;
										}
									}
									$$var = $temp;
									$this->$var = $$var;
								}
								else {
									if ($var == "person_privacy") {
										$$var = unserialize($value);
										$this->$var = $$var;
										$temp = array();
										foreach ($$var as $key1 => $value1) {
											$temp[$key1] = $$value1;
									}
										$$var = $temp;
										$this->$var = $$var;
									}
									else {
										if ($var == "person_facts") {
											$$var = unserialize($value);
											$this->$var = $$var;
											$temp = array();
											foreach ($$var as $key1 => $value1) {
												foreach ($value1 as $key2 => $value2) {
													foreach($value2 as $key3 => $value3) {
														$temp[$key1][$key2][$key3] = $$value3;
													}
												}
											}
											$$var = $temp;
											$this->$var = $$var;
										}
										else {
											$var = strtoupper($var);
											$a = substr($value, 0, 5);
											if ($a == "PRIV_") $$var = $$value;
											else $$var = $value;
											$this->$var = $$var;
										}
									}
								}
							}
						}
					}
				}
				$res->FreeResult();
			}
		}
		if ($user_override && !empty($gm_username)) {
			$ged = get_gedcom_from_id($gedcomid);
			$us = $Users->GetUser($gm_username);
			if (isset($us->relationship_privacy[$ged]) && !empty($us->relationship_privacy[$ged])) {
				$this->USE_RELATIONSHIP_PRIVACY = ($us->relationship_privacy[$ged] == "Y" ? true : false);
			}
			if (isset($us->max_relation_path_length[$ged]) && $us->max_relation_path_length[$ged] > 0) {
				$this->MAX_RELATION_PATH_LENGTH = $us->relationship_privacy[$ged];
			}
			if (isset($us->hide_live_people[$ged]) && !empty($us->hide_live_people[$ged])) {
				// If yes, give HIDE_LIVE_PEOPLE the lowest possible value. This will always hide them to any user with this override, no matter what status this user has (except site admin)
				// If no, give HIDE_LIVE_PEOPLE the highest possible value. This will always show them to any user with this override
				$this->HIDE_LIVE_PEOPLE = ($us->hide_live_people[$ged] == "N" ? $this->PRIV_PUBLIC : $this->PRIV_HIDE);
				$this->SHOW_LIVING_NAMES = ($us->hide_live_people[$ged] == "N" ? $this->PRIV_PUBLIC : $this->PRIV_HIDE);
			}
		}
		return $this;
	}
	
	function WritePrivacy() {
		global $TBLPREFIX;
	
		$settings = Get_Object_vars($this);
		
		$settings["PRIVACY_VERSION"] = $this->PRIVACY_VERSION;
	
		$col = "(";
		$val = "(";
		$i = "0";
		foreach ($settings as $key => $value) {
			if ($i > 0) {
				$col .= ", ";
				$val .= ", ";
			}
			$col .= "p_".strtolower($key);
			$i++;
			switch($key) {
				case "person_facts":
					$val .= "'".serialize($value)."'";
					break;
				case "global_facts":
					$val .= "'".serialize($value)."'";
					break;
				case "user_privacy":
					$val .= "'".serialize($value)."'";
					break;
				case "person_privacy":
					$val .= "'".serialize($value)."'";
					break;
				default:
					$val .= "'".$value."'";
			}				
		}
		$col .= ")";
		$val .= ")";
		$sql = "INSERT INTO ".$TBLPREFIX."privacy ".$col." VALUES ".$val;
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
	
	function StoreArrays() {
		global $TBLPREFIX;
		
		$PRIVACY_CONSTANTS = array();
		$PRIVACY_CONSTANTS[$this->PRIV_HIDE] = "PRIV_HIDE";
		$PRIVACY_CONSTANTS[$this->PRIV_PUBLIC] = "PRIV_PUBLIC";
		$PRIVACY_CONSTANTS[$this->PRIV_USER] = "PRIV_USER";
		$PRIVACY_CONSTANTS[$this->PRIV_NONE] = "PRIV_NONE";
		
		foreach($this->person_facts as $key1 => $value1) {
			foreach($value1 as $key2 => $value2) {
				foreach($value2 as $key3 => $value3) {
					$this->person_facts[$key1][$key2][$key3] = $PRIVACY_CONSTANTS[$value3];
				}
			}
		}
		
		// We don't do user privacy, as the values are stored as they are (0 and 1)
		
		foreach($this->person_privacy as $key => $value) {
			$this->person_privacy[$key] = $PRIVACY_CONSTANTS[$value];
		}
		
		
		$sql = "UPDATE ".$TBLPREFIX."privacy SET p_person_privacy='".serialize($this->person_privacy)."', p_user_privacy='".serialize($this->user_privacy)."', p_person_facts='".serialize($this->person_facts)."' WHERE p_gedcomid='".$this->GEDCOMID."'";
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
}