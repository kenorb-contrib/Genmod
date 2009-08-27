<?php
/**
 * Class file for gedcom config
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
 * @subpackage Admin
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class GedcomConfig {

	var $classname = "GedcomConfig";
	var $current_gedcom = "";
	var $GEDCONF = array();
	var $lastmail = array();
	var $cachenames = array("upcoming", "today", "stats", "plotdata");
	
	public function __construct($ged="") {
		if (!empty($ged)) $this->ReadGedcomConfig($ged);
	}
	
	/** Read the Gedcom configuration settings from the database
	 *
	 * The function reads the GEDCOM configuration settings from the database.
	 * It also sets the max execution time to the new value, if it's the current GEDCOM.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$gedcom		GEDCOM name for which the values are to be retrieved.
	 * @return 	boolean		true if success, false if failed
	**/
	public function ReadGedcomConfig($gedcom) {
		global $TBLPREFIX, $GEDCOM;
	
		if (isset($this->GEDCONF[$gedcom])) {
			foreach ($this->GEDCONF[$gedcom] as $var => $value) {
				global $$var;
				$$var = $value;
			}
		}
		else {
			$sql = "SELECT * FROM ".$TBLPREFIX."gedconf WHERE (gc_gedcom='".$gedcom."')";
			$res = NewQuery($sql);
			if ($res) {
				$ct = $res->NumRows($res->result);
				if ($ct == "0") return false;
				$gc = array();
				while($row = $res->FetchAssoc($res->result)){
					foreach ($row as $key => $value) {
						$var = strtoupper(substr($key, 3));
						global $$var;
						$$var = $value;
						$gc[$var] = $value;
					}
				}
				$this->GEDCONF[$gedcom] = $gc;
				$res->FreeResult($res->result);
			}
		}
		//-- This is copied from the config_gedcom.php
		if ($gedcom == $GEDCOM) @set_time_limit($TIME_LIMIT);
		return true;
	}
	
	/** Store the Gedcom configuration settings in the database
	 *
	 * The function stores all GEDCOM configuration settings in the database.
	 * It also sets the execution time limit to the new value.
	 *
	 * @author	Genmod Development Team
	 * @param		array	$settings	Array with GEDCOM settings
	**/
	public function SetGedcomConfig($settings) {
		global $TBLPREFIX, $GEDCOM;
		
		// Clear the cache
		$this->GEDCONF = array();
	
		// -- First see if the settings already exist
		$sql = "SELECT gc_gedcom FROM ".$TBLPREFIX."gedconf WHERE (gc_gedcom='".$settings["gedcom"]."')";
		$res = NewQuery($sql);
		$ct = $res->NumRows($res->result);
		if ($ct == "0") {
			// -- New config. We will insert it in the database.
			$col = "(";
			$val = "(";
			$i = "0";
			foreach ($settings as $key => $value) {
				if ($i > 0) {
					$col .= ", ";
					$val .= ", ";
				}
				$col .= "gc_";
				$col .= $key;
				$val .= "'".$value."'";
				$i++;
			}
			$col .= ")";
			$val .= ")";
			$sql = "INSERT INTO ".$TBLPREFIX."gedconf ".$col." VALUES ".$val;
	  		$res = NewQuery($sql);
		}
		else {
			$i = "0";
			$str = "";
			foreach ($settings as $key => $value) {
				if ($i > 0) $str .= ", ";
				$str .= "gc_".$key."='".$value."'";
				$i++;
			}
			$sql = "UPDATE ".$TBLPREFIX."gedconf SET ".$str." WHERE gc_gedcom='".$settings["gedcom"]."'";
	  		$res = NewQuery($sql);
		}
		//-- This is copied from the config_gedcom.php. Added: only re-set the limit 
		//-- when it's the current gedcom.
		if ($settings["gedcom"] == $GEDCOM) @set_time_limit($TIME_LIMIT);
		
		return;
	}
		
	/** Delete Gedcom configuration settings from the database
	 *
	 * The function deletes GEDCOM configuration settings from the database.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$gedcom		GEDCOM name for which the values are to be deleted.
	**/
	public function DeleteGedcomConfig($gedcom) {
		global $TBLPREFIX, $DBCONN;
	
		if (!$DBCONN->connected) return false;
		$this->GEDCONF = array();
		$sql = "DELETE FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$gedcom."'";
		$res = NewQuery($sql);
		return;
	}
	
	public function GetHighMaxTime() {
		global $TBLPREFIX, $TIME_LIMIT;

		// Retrieve the maximum maximum execution time from the gedcom settings
		$sql = "SELECT max(gc_time_limit) FROM ".$TBLPREFIX."gedconf";
		$res = NewQuery($sql);
		if ($res) while($row = $res->FetchRow()) return $row["0"];
		else return $TIME_LIMIT;
	}

	public function GetLastNotifMail() {
		global $TBLPREFIX;
		
		$sql = "SELECT gc_gedcom, gc_last_change_email from ".$TBLPREFIX."gedconf";
		$res = NewQuery($sql);
		if ($res) {
			while ($row = $res->FetchAssoc()) {
				$this->lastmail[$row["gc_gedcom"]] = $row["gc_last_change_email"];
			}
		}
		return $this->lastmail;
	}
	
	public function SetLastNotifMail($ged) {
		global $TBLPREFIX;
		
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_change_email='".time()."' WHERE gc_gedcom='".$ged."'";
		$res = NewQuery($sql);
		return true;
	}
	
	public function SetPedigreeRootId($id, $ged) {
		global $TBLPREFIX;
	
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_pedigree_root_id='".$id."' WHERE gc_gedcom='".$ged."'";
		$res = NewQuery($sql);
		return true;
	}

	public function GetLastCacheDate($cache, $ged) {
		global $TBLPREFIX;

		if (!in_array($cache, $this->cachenames)) return false;
		$sql = "SELECT gc_gedcom, gc_last_".$cache." FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() == 0) return false;
			else {
				while ($row = $res->FetchAssoc()) {
					return $row["gc_last_".$cache];
				}
			}
		}
		else return false;
	}
	
	public function GetAllLastCacheDates($ged="") {
		global $TBLPREFIX, $GEDCOM;
	
		if (empty($ged)) $ged = $GEDCOM;
		$sql = "SELECT gc_last_upcoming, gc_last_today, gc_last_stats FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->fetchAssoc();
			return $row;
		}
		else return false;
	}
	
	public function SetLastCacheDate($cache, $value, $ged) {
		global $TBLPREFIX;
		
		if (!in_array($cache, $this->cachenames)) return false;
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_".$cache."='".$value."' WHERE gc_gedcom='".$ged."'";
		$res = NewQuery($sql);
		return true;
	}

	public function ResetCaches($ged="") {
		global $TBLPREFIX;
	
		// Reset todays events cache
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_today='0', gc_last_upcoming='0', gc_last_stats='0', gc_last_plotdata='0' ";
		if (!empty($ged)) $sql .= "WHERE gc_gedcom='".$ged."'";
		$res = NewQuery($sql);
		return true;
	}
	
	public function GetGedcomLanguage($ged) {
		global $TBLPREFIX;
	
		$sql = "SELECT gc_gedcomlang FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$ged."'";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return false;
		$lang = $res->FetchAssoc();
		return $lang["gc_gedcomlang"];
	}
}
?>