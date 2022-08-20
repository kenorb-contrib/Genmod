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
 * @version $Id: privacyobject_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class PrivacyObject {
	
	public $classname = "PrivacyObject";		// Name of this class
	private static $GEDPRIV = array();			// Holder of the instances for this class

	// These vars are the same as (uppercase) the fields in the DB.
	// The values for $PRIV_XX are filled in here as defaults.
	public $GEDCOMID = "";						// Gedcom ID to which these settings apply
	public $GEDCOM = "";						// Gedcom name (obsolete)
	public $PRIVACY_VERSION = "3.3";			// Version of this class (not used anywhere)
	
	public $PRIV_HIDE = -1;						// Level for no access at all
	public $PRIV_PUBLIC = 2;					// Level for visitor access
	public $PRIV_USER = 1;						// Level for user access
	public $PRIV_NONE = 0;						// Level for admin access
	public $SHOW_DEAD_PEOPLE = 2;				// On or below this level users can see data of deceased people
	public $HIDE_LIVE_PEOPLE = 1;				// On or below this level users can see data of alive people
	public $SHOW_LIVING_NAMES = 1;				// On or below this level users can see the names of alive people
	public $SHOW_SOURCES = 1;					// On or below this level users can see data of sources
	public $LINK_PRIVACY = 1;					// Check if other hidden records prevent display (implemented for sources, media and notes)
	public $MAX_ALIVE_AGE = 120;				// Number of years after which a person is assumed to be dead
	public $ENABLE_CLIPPINGS_CART = 1;			// User level on or below which people may use the clippings cart
	public $SHOW_ACTION_LIST = 0;				// User level on or below which people may see and use the action list
	public $USE_RELATIONSHIP_PRIVACY = 0;		// Use relationship privacy or not
	public $MAX_RELATION_PATH_LENGTH = 3;		// Path length if set to on. Paths go via parents, siblings and children
	public $CHECK_MARRIAGE_RELATIONS = 1;		// A relationship path may follow spouses or not
	public $CHECK_CHILD_DATES = 1;				// Check child dates for determining the is dead status
	public $PRIVACY_BY_YEAR = 0;				// Check the age of events instead of the isdead status
	public $PRIVACY_BY_RESN = 1;				// Check the level 1 RESN tag or not
	public $person_privacy = array();			// Array of ID's to show on a certain user level
	public $user_privacy = array();				// Array of ID's to hide or show to a specific user
	public $global_facts = array();				// Restrict access to certain facts
	public $person_facts = array();				// Restrict access to certain facts of specific ID's
	public $is_empty = false;					// True if it's an empty (non existent) user
	

	public static function GetInstance($gedcomid, $user_override=true) {
	
		if ($user_override == false) $override = "0";
		else $override = "1";
		if (!isset(self::$GEDPRIV[$gedcomid][$override])) {
			self::$GEDPRIV[$gedcomid][$override] = new PrivacyObject($gedcomid, $user_override);
		}
		return self::$GEDPRIV[$gedcomid][$override];
	}

	public static function UnsetInstance($gedcomid) {
		
		if(isset(self::$GEDPRIV[$gedcomid][0])) unset(self::$GEDPRIV[$gedcomid][0]);
		if(isset(self::$GEDPRIV[$gedcomid][1])) unset(self::$GEDPRIV[$gedcomid][1]);
	}
		
	public function __construct($gedcomid, $user_override=true) {
		
		// Initialize some values, which cannot be done with var, as in PHP4 it only accepts constants
		$this->GEDCOMID = $gedcomid;
		$this->global_facts["SSN"]["show"] = "PRIV_NONE";
		$this->global_facts["SSN"]["details"] = "PRIV_NONE";
		$this->GetPrivacy($gedcomid, $user_override);
	}
	
	public function GetPrivacy($gedcomid="", $user_override) {
		global $DBCONN, $gm_user;
		
		if (!$DBCONN->connected) return false;
		
		if (!empty($gedcomid)) {
			$sql = "SELECT * FROM ".TBLPREFIX."privacy WHERE (p_gedcomid='".$gedcomid."')";
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
				else $this->is_empty = true;
				$res->FreeResult();
			}
		}
		if ($user_override && $gm_user->username != "") {
			$ged = $gedcomid;
			// Blank is default for this user setting
			if (isset($gm_user->relationship_privacy[$ged]) && $gm_user->relationship_privacy[$ged] != "") {
				$this->USE_RELATIONSHIP_PRIVACY = ($gm_user->relationship_privacy[$ged] == "Y" ? true : false);
			}
			if (isset($gm_user->max_relation_path[$ged]) && $gm_user->max_relation_path[$ged] > 0) {
				$this->MAX_RELATION_PATH_LENGTH = $gm_user->max_relation_path[$ged];
			}
			if (isset($gm_user->check_marriage_relations[$ged]) && $gm_user->check_marriage_relations[$ged] != "") {
				$this->CHECK_MARRIAGE_RELATIONS = ($gm_user->check_marriage_relations[$ged] == "Y" ? true : false);
			}
			// Blank is default for these user settings
			if (isset($gm_user->hide_live_people[$ged]) && $gm_user->hide_live_people[$ged] != "") {
				// If yes, give HIDE_LIVE_PEOPLE the lowest possible value. This will always hide them to any user with this override, no matter what status this user has (except site admin)
				// If no, give HIDE_LIVE_PEOPLE the highest possible value. This will always show them to any user with this override
				$this->HIDE_LIVE_PEOPLE = ($gm_user->hide_live_people[$ged] == "N" ? $this->PRIV_PUBLIC : $this->PRIV_HIDE);
				$this->SHOW_LIVING_NAMES = ($gm_user->hide_live_people[$ged] == "N" ? $this->PRIV_PUBLIC : $this->PRIV_HIDE);
			}
		}
		return $this;
	}
	
	public function WritePrivacy() {
	
		$settings = Get_Object_vars($this);
		
		$settings["PRIVACY_VERSION"] = $this->PRIVACY_VERSION;
		unset($settings["classname"]);
		unset($settings["is_empty"]);
		
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
		$sql = "INSERT INTO ".TBLPREFIX."privacy ".$col." VALUES ".$val;
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
	
	public function StoreArrays() {
		
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
		
		
		$sql = "UPDATE ".TBLPREFIX."privacy SET p_person_privacy='".serialize($this->person_privacy)."', p_user_privacy='".serialize($this->user_privacy)."', p_person_facts='".serialize($this->person_facts)."' WHERE p_gedcomid='".$this->GEDCOMID."'";
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
}
?>