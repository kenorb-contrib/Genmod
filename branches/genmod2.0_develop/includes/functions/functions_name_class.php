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
			if ($family->husb->disp_name) {
				$hname = self::GetSortableAddName($family->husb, $rev, $changes);
			}
			else $hname = $gm_lang["private"];
		}
		else {
			if ($rev) $hname = "@P.N. @N.N.";
			else $hname = "@N.N., @P.N.";
		}
		if (is_object($family->wife)) {
			if ($family->wife->disp_name) {
				$wname = self::GetSortableAddName($family->wife, $rev, $changes);
			}
			else $wname = $gm_lang["private"];
		}
		else {
			if ($rev) $wname = "@P.N. @N.N.";
			else $wname = "@N.N., @P.N.";
		}

		if (!empty($hname) && !empty($wname)) return CheckNN($hname)." + ".CheckNN($wname);
		else if (!empty($hname) && empty($wname)) return CheckNN($hname)." + ".CheckNN(self::GetSortableName($family->wife, "", "", false, $rev, $changes));
		else if (empty($hname) && !empty($wname)) return CheckNN(self::GetSortableName($family->husb, "", "", false, $rev, $changes))." + ".CheckNN($wname);
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
	public function GetSortableName(&$person, $alpha="", $surname="", $allnames=false, $rev = false, $changes = false) {
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
				if ($NAME_REVERSE) $mynames[] = self::SortableNameFromName(self::ReverseName($name[0]), $rev);
				else $mynames[] = self::SortableNameFromName($name[0], $rev);
			}
			return $mynames;
		}
		foreach($names as $indexval => $name) {
			if ($surname!="" && $name[2]==$surname) {
				if ($NAME_REVERSE) return self::SortableNameFromName(self::ReverseName($name[0]), $rev);
				else return self::SortableNameFromName($name[0], $rev);
			}
			else if ($alpha!="" && $name[1]==$alpha) {
				if ($NAME_REVERSE) return self::SortableNameFromName(self::ReverseName($name[0]), $rev);
				else return self::SortableNameFromName($name[0], $rev);
			}
		}
		if ($NAME_REVERSE) return self::SortableNameFromName(self::ReverseName($names[0][0]), $rev);
		else return self::SortableNameFromName($names[0][0], $rev);
	}
	
	// -- find and return a given individual's second name in sort format: familyname, firstname
	public function GetSortableAddName($person, $rev = false, $changes = false) {
		global $NAME_REVERSE;
		global $GEDCOMID;
	
		//-- get the name from the indexes
		if ($changes) $record = $person->changedgedrec;
		else $record = $person->gedrec;
		$name_record = GetSubRecord(1, "1 NAME", $record);
		$name = "";
	
		// Check for ROMN name
		$romn = preg_match("/(2 ROMN (.*)|2 _HEB (.*))/", $name_record, $romn_match);
		if ($romn == 0) {
			$orgname = self::GetNameInRecord($name_record);
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
	/**
	 * Get the name from the raw gedcom record
	 *
	 * @param string $indirec the raw gedcom record to get the name from
	 */
	public function GetNameInRecord($indirec, $import=false) {
		
		$name = "";
		$nt = preg_match("/1 NAME (.*)/", $indirec, $ntmatch);
		if ($nt>0) {
			$name = trim($ntmatch[1]);
			$name = preg_replace(array("/__+/", "/\.\.+/", "/^\?+/"), array("", "", ""), $name);
	
			//-- check for a surname
			$ct = preg_match("~/(.*)/~", $name, $match);
			if ($ct > 0) {
				$surname = trim($match[1]);
				$surname = preg_replace(array("/__+/", "/\.\.+/", "/^\?+/"), array("", "", ""), $surname);
				if (empty($surname)) $name = preg_replace("~/(.*)/~", "/@N.N./", $name);
			}
			else {
				//-- check for the surname SURN tag
				$ct = preg_match("/2 SURN (.*)/", $indirec, $match);
				if ($ct>0) {
					$pt = preg_match("/2 SPFX (.*)/", $indirec, $pmatch);
					if ($pt>0) $name .=" ".trim($pmatch[1]);
					$surname = trim($match[1]);
					$surname = preg_replace(array("/__+/", "/\.\.+/", "/^\?+/"), array("", "", ""), $surname);
					if (empty($surname)) $name .= " /@N.N./";
					else $name .= " /".$surname."/";
				}
				else $name .= " /@N.N./";
			}
			
			$givens = preg_replace("~/.*/~", "", $name);
			if (empty($givens)) $name = "@P.N. ".$name;
		}
		else {
			/*-- this is all extraneous to the 1 NAME tag and according to the gedcom spec
			-- the 1 NAME tag should take preference
			*/
			$name = "";
			//-- check for the given names
			$gt = preg_match("/2 GIVN (.*)/", $indirec, $gmatch);
			if ($gt>0) $name .= trim($gmatch[1]);
			else $name .= "@P.N.";
	
			//-- check for the surname
			$ct = preg_match("/2 SURN (.*)/", $indirec, $match);
			if ($ct>0) {
				$pt = preg_match("/2 SPFX (.*)/", $indirec, $pmatch);
				if ($pt>0) $name .=" ".trim($pmatch[1]);
				$surname = trim($match[1]);
				if (empty($surname)) $name .= " /@N.N./";
				else $name .= " /".$surname."/";
			}
			if (empty($name)) $name = "@P.N. /@N.N./";
	
			$st = preg_match("/2 NSFX (.*)/", $indirec, $smatch);
			if ($st>0) $name.=" ".trim($smatch[1]);
			$pt = preg_match("/2 SPFX (.*)/", $indirec, $pmatch);
			if ($pt>0) $name =strtolower(trim($pmatch[1]))." ".$name;
		}
		// handle PAF extra NPFX [ 961860 ]
		$ct = preg_match("/2 NPFX (.*)/", $indirec, $match);
		if ($ct>0) {
			$npfx = trim($match[1]);
			if (strpos($name, $npfx)===false) $name = $npfx." ".$name;
		}
		// Insert the nickname if the option is set
		if (GedcomConfig::$SHOW_NICK && !$import) {
			$n = GetNicks($indirec);
			if (count($n) > 0) {
				$ct = preg_match("~(.*)/(.*)/(.*)~", $name, $match);
				$name = $match[1].substr(GedcomConfig::$NICK_DELIM, 0, 1).$n[0].substr(GedcomConfig::$NICK_DELIM, 1, 1)."/".$match[2]."/".$match[3];
			}
		}
		return $name;
	}
	
	/**
	 * Get the sortable name from the gedcom name
	 *
	 * @param string $name 	the name from the 1 NAME gedcom line including the /
	 * @return string 	The new name in the form Surname, Given Names
	 */
	public function SortableNameFromName($name, $rev = false) {
		global $NAME_REVERSE;
	
		// NOTE: Remove any unwanted characters from the name
		if (preg_match("/^\.(\.*)$|^\?(\?*)$|^_(_*)$|^,(,*)$/", $name)) $name = preg_replace(array("/,/","/\./","/_/","/\?/"), array("","","",""), $name);
	
		$ct = preg_match("~(.*)/(.*)/(.*)~", $name, $match);
		if ($ct>0) {
			$surname = trim($match[2]);
			if (empty($surname)) $surname = "@N.N.";
			$givenname = trim($match[1]);
			$othername = trim($match[3]);
			if (HasChinese($name, true)) $add = "";
			else $add = " ";
			if (empty($givenname)&&!empty($othername)) {
				$givenname = $othername;
				$othername = "";
			}
			if ($rev) {
				if (empty($givenname)) $givenname = "@P.N.";
				if ($NAME_REVERSE || HasChinese($name, true)) $name = $surname.$add.$givenname;
				else $name = $givenname.$add.$surname;
				if (!empty($othername)) $name .= $add.$othername;
			}
			else {
				if (empty($givenname)) $givenname = "@P.N.";
				$name = $surname;
				if (!empty($othername)) $name .= $add.$othername;
				if ($NAME_REVERSE || HasChinese($name, true)) $name .= $add.$givenname;
				else $name .= ", ".$givenname;
			}
		}
		if (!empty($name)) return $name;
		else {
			if ($rev) return "@P.N. @N.N.";
			else return "@N.N., @P.N.";
		}
	}
	
	/**
	 * reverse a name
	 * this function will reverse a name for languages that
	 * prefer last name first such as hungarian and chinese
	 * @param string $name	the name to reverse, must be gedcom encoded as if from the 1 NAME line
	 * @return string		the reversed name
	 */
	public function ReverseName($name) {
		$ct = preg_match("~(.*)/(.*)/(.*)~", $name, $match);
		if ($ct>0) {
			if (HasChinese($name, true)) $add = "";
			else $add = " ";
			$surname = trim($match[2]);
			if (empty($surname)) $surname = "@N.N.";
			$givenname = trim($match[1]);
			$othername = trim($match[3]);
			if (empty($givenname)&&!empty($othername)) {
				$givenname = $othername;
				$othername = "";
			}
			if (empty($givenname)) $givenname = "@P.N.";
			$name = $surname;
			$name .= $add.$givenname;
			if (!empty($othername)) $name .= $add.$othername;
		}
		
		return $name;
	}
	
}
?>