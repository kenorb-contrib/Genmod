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

abstract class GedcomConfig {

	public $classname = "GedcomConfig";
	public static $GEDCONF = array();
	private static $lastmail = null;
	private static $cachenames = array("upcoming", "today", "stats", "plotdata");
	
	/** Read the Gedcom configuration settings from the database
	 *
	 * The function reads the GEDCOM configuration settings from the database.
	 * It also sets the max execution time to the new value, if it's the current GEDCOM.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$gedcom		GEDCOM name for which the values are to be retrieved.
	 * @return 	boolean		true if success, false if failed
	**/
	public function ReadGedcomConfig($gedcomid) {
		global $GEDCOMID, $GM_BASE_DIRECTORY;
	
		if (isset(self::$GEDCONF[$gedcomid])) {
			foreach (self::$GEDCONF[$gedcomid] as $var => $value) {
				global $$var;
				$$var = $value;
			}
		}
		else {
			$sql = "SELECT * FROM ".TBLPREFIX."gedconf WHERE (gc_gedcomid='".$gedcomid."')";
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
				self::$GEDCONF[$gedcomid] = $gc;
				$res->FreeResult($res->result);
			}
		}
		// If the pinyin table wasn't previously loaded and is required, load it now
		if ($DISPLAY_PINYIN) {
			global $pinyin;
			require_once($GM_BASE_DIRECTORY."includes/values/pinyin.php");
		}
		//-- This is copied from the config_gedcom.php
		if ($gedcomid == $GEDCOMID) @set_time_limit($TIME_LIMIT);
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
		global $GEDCOMID;
		
		// Clear the cache
		self::$GEDCONF = array();
	
		// -- First see if the settings already exist
		$sql = "SELECT gc_gedcom FROM ".TBLPREFIX."gedconf WHERE (gc_gedcomid='".$settings["gedcomid"]."')";
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
			$sql = "INSERT INTO ".TBLPREFIX."gedconf ".$col." VALUES ".$val;
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
			$sql = "UPDATE ".TBLPREFIX."gedconf SET ".$str." WHERE gc_gedcomid='".$settings["gedcomid"]."'";
	  		$res = NewQuery($sql);
		}
		//-- This is copied from the config_gedcom.php. Added: only re-set the limit 
		//-- when it's the current gedcom.
		if ($settings["gedcom"] == get_gedcom_from_id($GEDCOMID)) @set_time_limit($TIME_LIMIT);
		
		return;
	}
		
	/** Delete Gedcom configuration settings from the database
	 *
	 * The function deletes GEDCOM configuration settings from the database.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$gedcom		GEDCOM name for which the values are to be deleted.
	**/
	public function DeleteGedcomConfig($gedcomid) {
		global $DBCONN;
	
		if (!$DBCONN->connected) return false;
		unset(self::$GEDCONF[$gedcom]);
		$sql = "DELETE FROM ".TBLPREFIX."gedconf WHERE gc_gedcomid='".$gedcomid."'";
		$res = NewQuery($sql);
		return;
	}
	
	public function GetHighMaxTime() {
		global $TIME_LIMIT;

		// Retrieve the maximum maximum execution time from the gedcom settings
		$sql = "SELECT max(gc_time_limit) FROM ".TBLPREFIX."gedconf";
		$res = NewQuery($sql);
		if ($res) while($row = $res->FetchRow()) return $row["0"];
		else return $TIME_LIMIT;
	}

	public function GetLastNotifMail() {
		
		if (is_null(self::$lastmail)) {
			$sql = "SELECT gc_gedcom, gc_last_change_email from ".TBLPREFIX."gedconf";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc()) {
					self::$lastmail[$row["gc_gedcom"]] = $row["gc_last_change_email"];
				}
			}
		}
		return self::$lastmail;
	}
	
	public function SetLastNotifMail($ged) {
		
		$time = time();
		$sql = "UPDATE ".TBLPREFIX."gedconf SET gc_last_change_email='".$time."' WHERE gc_gedcomid='".$ged."'";
		$res = NewQuery($sql);
		self::$lastmail[$ged] = $time;
		return true;
	}
	
	public function SetPedigreeRootId($id, $ged) {
	
		$sql = "UPDATE ".TBLPREFIX."gedconf SET gc_pedigree_root_id='".$id."' WHERE gc_gedcomid='".$ged."'";
		$res = NewQuery($sql);
		return true;
	}

	public function GetLastCacheDate($cache, $ged) {

		if (!in_array($cache, self::$cachenames)) return false;
		$sql = "SELECT gc_gedcom, gc_last_".$cache." FROM ".TBLPREFIX."gedconf WHERE gc_gedcomid='".$ged."'";
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
	
	public function GetAllLastCacheDates($gedid="") {
		global $GEDCOMID;
	
		if (empty($gedid)) $gedid = $GEDCOMID;
		$sql = "SELECT gc_last_upcoming, gc_last_today, gc_last_stats FROM ".TBLPREFIX."gedconf WHERE gc_gedcomid='".$gedid."'";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->fetchAssoc();
			return $row;
		}
		else return false;
	}
	
	public function SetLastCacheDate($cache, $value, $ged) {
		
		if (!in_array($cache, self::$cachenames)) return false;
		$sql = "UPDATE ".TBLPREFIX."gedconf SET gc_last_".$cache."='".$value."' WHERE gc_gedcomid='".$ged."'";
		$res = NewQuery($sql);
		return true;
	}

	public function ResetCaches($gedid="") {
	
		// Reset todays events cache
		$sql = "UPDATE ".TBLPREFIX."gedconf SET gc_last_today='0', gc_last_upcoming='0', gc_last_stats='0', gc_last_plotdata='0' ";
		if (!empty($ged)) $sql .= "WHERE gc_gedcomid='".$gedid."'";
		$res = NewQuery($sql);
		return true;
	}
	
	public function GetGedcomLanguage($gedid) {
	
		$sql = "SELECT gc_gedcomlang FROM ".TBLPREFIX."gedconf WHERE gc_gedcomid='".$gedid."'";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return false;
		$lang = $res->FetchAssoc();
		return $lang["gc_gedcomlang"];
	}
}
?>