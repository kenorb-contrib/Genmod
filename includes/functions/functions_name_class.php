<?php
/**
 * Name Specific Functions
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
 * @version $Id$
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class NameFunctions {
	
	public function GetFamilyDescriptor(&$family, $rev = false, $changes = false, $starred=true) {
		global $gm_lang;

		if (is_object($family->husb)) {
			if ($family->husb->disp_name)
				$hname = self::GetSortableName($family->husb, "", "", false, $rev, $changes);
			else $hname = $gm_lang["private"];
		}
		else {
			if ($rev) $hname = "@P.N. @N.N.";
			else $hname = "@N.N., @P.N.";
		}
		if (is_object($family->wife)) {
			if ($family->wife->disp_name)
				$wname = self::GetSortableName($family->wife, "", "", false, $rev, $changes);
			else $wname = $gm_lang["private"];
		}
		else {
			if ($rev) $wname = "@P.N. @N.N.";
			else $wname = "@N.N., @P.N.";
		}
	
		if (!empty($hname) && !empty($wname)) return CheckNN($hname,$starred)." + ".CheckNN($wname,$starred);
		else if (!empty($hname) && empty($wname)) return CheckNN($hname,$starred);
		else if (empty($hname) && !empty($wname)) return CheckNN($wname,$starred);
	}
	
	public function GetFamilyAddDescriptor($family, $rev = false, $changes = false) {
		global $gm_lang;
		
		if (is_object($family->husb)) {
			if ($family->husb->disp_name)
				$hname = self::GetSortableAddName($family->husb, $rev, $changes);
			else $hname = $gm_lang["private"];
		}
		else {
			if ($rev) $hname = "@P.N. @N.N.";
			else $hname = "@N.N., @P.N.";
		}
		if (is_object($family->wife)) {
			if ($family->wife->disp_name)
				$wname = self::GetSortableAddName($family->wife, $rev, $changes);
			else $wname = $gm_lang["private"];
		}
		else {
			if ($rev) $wname = "@P.N. @N.N.";
			else $wname = "@N.N., @P.N.";
		}
	
		if (!empty($hname) && !empty($wname)) return CheckNN($hname,$starred)." + ".CheckNN($wname,$starred);
		else if (!empty($hname) && empty($wname)) return CheckNN($hname,$starred);
		else if (empty($hname) && !empty($wname)) return CheckNN($wname,$starred);
	}
		
	/**
	 * get the person's name as surname, given names
	 *
	 * This function will return the given person's name is a format that is good for sorting
	 * Surname, given names
	 * @param string $pid the gedcom xref id for the person
	 * @param string $alpha	only get the name that starts with a certain letter
	 * @param string $surname only get the name that has this surname
	 * @param boolean $allnames true returns all names in an array
	 * @param boolean $rev reverse the name order
	 * @param boolean $changes include unapproved changes
	 * @return string the sortable name
	 */
	public function GetSortableName($person, $alpha="", $surname="", $allnames=false, $rev = false, $changes = false) {
		global $SHOW_LIVING_NAMES, $PRIV_PUBLIC, $GEDCOMID, $COMBIKEY;
		global $indilist, $gm_lang, $GEDCOMID, $NAME_REVERSE;
	
		$mynames = array();
	
		if (!is_object($person)) {
			if ($allnames == false) {
				if ($rev) return "@P.N. @N.N.";
				else return "@N.N., @P.N.";
			}
			else {
				if ($rev) $mynames[] = "@P.N. @N.N.";
				else $mynames[] = "@N.N., @P.N.";
				return $mynames;
			}
		}
	
		$names = $person->name_array;
		if ($allnames == true) {
			$mynames = array();
			foreach ($names as $key => $name) {
				if ($NAME_REVERSE) $mynames[] = SortableNameFromName(ReverseName($name[0]), $rev);
				else $mynames[] = SortableNameFromName($name[0], $rev);
			}
			return $mynames;
		}
		foreach($names as $indexval => $name) {
			if ($surname!="" && $name[2]==$surname) {
				if ($NAME_REVERSE) return SortableNameFromName(ReverseName($name[0]), $rev);
				else return SortableNameFromName($name[0], $rev);
			}
			else if ($alpha!="" && $name[1]==$alpha) {
				if ($NAME_REVERSE) return SortableNameFromName(ReverseName($name[0]), $rev);
				else return SortableNameFromName($name[0], $rev);
			}
		}
		if ($NAME_REVERSE) return SortableNameFromName(ReverseName($names[0][0]), $rev);
		else return SortableNameFromName($names[0][0], $rev);
	}
	
	// -- find and return a given individual's second name in sort format: familyname, firstname
	public function GetSortableAddName($person, $rev = false, $changes = false) {
		global $NAME_REVERSE;
		global $NAME_FROM_GEDCOM, $GEDCOMID;
	
		//-- get the name from the indexes
		if ($changes) $record = $person->changedgedrec;
		else $record = $person->gedrec;
		$name_record = GetSubRecord(1, "1 NAME", $record);
		$name = "";
	
		// Check for ROMN name
		$romn = preg_match("/(2 ROMN (.*)|2 _HEB (.*))/", $name_record, $romn_match);
		if ($romn == 0) {
			$orgname = GetNameInRecord($name_record);
			if (HasChinese($orgname)){
				$romn_match[0] = GetPinYin($orgname);
				$romn = 1;
			}
		}
		if ($romn > 0){
	    	$names = preg_split("/\//", $romn_match[count($romn_match)-1]);
			if ($names[0] == "") $names[0] = "@P.N.";	//-- MA
			if (empty($names[1])) $names[1] = "@N.N.";	//-- MA
			if (count($names)>1) {
				$fullname = trim($names[1]).",";
				$fullname .= ",# ".trim($names[0]);
				if (count($names)>2) $fullname .= ",% ".trim($names[2]);
			}
			else $fullname=$romn_match[1];
			if (!$NAME_REVERSE) {
				if ($rev) $name = trim($names[0])." ".trim($names[1]);
				else $name = trim($names[1]).", ".trim($names[0]);
			}
			else {
				if ($rev) $name = trim($names[1])." ".trim($names[0]);
				else $name = trim($names[0])." ,".trim($names[1]);
			}
		}
	
	//	else $name = GetSortableName($pid, "", "", false, $rev, $changes);
	
		return $name;
	}
	
}
?>