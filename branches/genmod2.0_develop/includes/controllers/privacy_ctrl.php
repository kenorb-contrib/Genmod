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
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class PrivacyController {

	var $classname = "Privacy";
	var $GEDPRIV = array();
	
	// This reads the settings for a specified gedcomid.
	// If the ID is empty, it will return settings from the default values in the PrivacyObject class.
	public function __construct($gedcomid="") {
		$this->ReadPrivacy($gedcomid);
	}
	
	// This actually reads the settings from the object array or, if not present, creates a new object.
	// The settings from the specified object are globalized here.
	function ReadPrivacy($gedcomid="", $user_override=true) {
		global $GEDCOMS, $GEDCOMID;

		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		
		// If we read the settings with no user overrides, we must renew. Cached are the ones read from session.php, WITH override
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
	
	public function GetPrivacyObject($gedid) {
		return new PrivacyObject($gedid);
	}
}
?>