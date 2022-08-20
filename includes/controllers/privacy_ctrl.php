<?php
/**
 * Class controller for privacy
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
 * @version $Id: privacy_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class PrivacyController {

	public $classname = "PrivacyController";
		
	// This reads the settings for a specified gedcomid.
	// If the ID is empty, it will return settings from the default values in the PrivacyObject class.
	// The settings from the specified object are globalized here.
	public function ReadPrivacy($gedcomid="", $user_override=true) {

		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		
		// If we read the settings with no user overrides, we must renew it. Cached are the ones read from session.php, WITH overrides from the user privacy settings.
		$priv =& PrivacyObject::GetInstance($gedcomid, $user_override);
		$vars = Get_Object_vars($priv);
		foreach ($vars as $name => $value) {
			if ($name != "GEDCOM" && $name != "GEDCOMID" && $name != "classname" && $name != "is_empty") {
				global $$name;
				$$name = $value;
			}
		}
		return true;
	}
	
	// This deletes settings for a specific gedcomid from cache and from the DB
	public function DeletePrivacy($gedcomid) {
		global $DBCONN;

		if (!$DBCONN->connected) return false;
		
		$priv =& PrivacyObject::GetInstance($gedcomid);
		
		if ($priv->is_empty) return false;
		
		PrivacyObject::UnsetInstance($gedcomid);

		$sql = "DELETE FROM ".TBLPREFIX."privacy WHERE p_gedcomid='".$gedcomid."'";
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		if ($ct == "0") return false;
		$res->FreeResult();
		return true;
	}
	
	// Here a new set of values is stored in the DB and in the cache.
	public function StorePrivacy($settings) {
		
		$gedcomid = $settings->GEDCOMID;
		self::DeletePrivacy($gedcomid);
		$settings->WritePrivacy();
		self::ReadPrivacy($gedcomid);
	}
	
	public function ClearPrivacyGedcomIDs($pid, $gedcom) {
		
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
	
	public function GetPrivacyObject($gedid) {
		return new PrivacyObject($gedid);
	}
}
?>