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

/**
 * Get array of common surnames from index
 *
 * This function returns a simple array of the most common surnames
 * found in the individuals list.
 * @param int $min the number of times a surname must occur before it is added to the array
 */
function GetCommonSurnamesIndex($gedid) {
	global $GEDCOMS;

	if (empty($GEDCOMS[$gedid]["commonsurnames"])) {
		SwitchGedcom($gedid);
		$surnames = GetCommonSurnames(GedcomConfig::$COMMON_NAMES_THRESHOLD);
		if (count($surnames) != 0) {
			$sns = "";
			foreach($surnames as $indexval => $surname) {
				$sns .= $surname["name"].", ";
			}
			$sql = "UPDATE ".TBLPREFIX."gedcoms SET g_commonsurnames='".DbLayer::EscapeQuery($sns)."' WHERE g_file='".$gedid."'";
			$res = NewQuery($sql);
			$GEDCOMS[$gedid]["commonsurnames"] = $sns;
		}
		SwitchGedcom();
	}
	$surnames = array();
	if (empty($GEDCOMS[$gedid]["commonsurnames"]) || ($GEDCOMS[$gedid]["commonsurnames"]==",")) return $surnames;
	$names = preg_split("/[,;]/", $GEDCOMS[$gedid]["commonsurnames"]);
	foreach($names as $indexval => $name) {
		$name = trim($name);
		if (!empty($name)) $surnames[$name]["name"] = stripslashes($name);
	}
	return $surnames;
}

/**
 * Get array of common surnames
 *
 * This function returns a simple array of the most common surnames
 * found in the individuals list.
 * @param int $min the number of times a surname must occur before it is added to the array
 */
function GetCommonSurnames($min) {
	global $GEDCOMID, $indilist, $GEDCOMS, $HNN, $ANN;

	$surnames = array();
	if (!CONFIGURED || !UserController::AdminUserExists() || (count($GEDCOMS)==0) || (!CheckForImport($GEDCOMID))) return $surnames;
	//-- this line causes a bug where the common surnames list is not properly updated
	// if ((!isset($indilist))||(!is_array($indilist))) return $surnames;
	$surnames = BlockFunctions::GetTopSurnames(100);
	arsort($surnames);
	$topsurns = array();
	$i=0;
	foreach($surnames as $indexval => $surname) {
		$surname["name"] = trim($surname["name"]);
		if (!empty($surname["name"]) 
				&& stristr($surname["name"], "@N.N")===false
				&& stristr($surname["name"], $HNN)===false
				&& stristr($surname["name"], $ANN.",")===false
				&& stristr(GedcomConfig::$COMMON_NAMES_REMOVE, $surname["name"])===false ) {
			if ($surname["match"]>=$min) {
				$topsurns[Str2Upper($surname["name"])] = $surname;
			}
			$i++;
		}
	}
	$addnames = preg_split("/[,;] /", GedcomConfig::$COMMON_NAMES_ADD);
	if ((count($addnames)==0) && (!empty(GedcomConfig::$COMMON_NAMES_ADD))) $addnames[] = GedcomConfig::$COMMON_NAMES_ADD;
	foreach($addnames as $indexval => $name) {
		if (!empty($name)) {
			$topsurns[$name]["name"] = $name;
			$topsurns[$name]["match"] = $min;
		}
	}
	$delnames = preg_split("/[,;] /", GedcomConfig::$COMMON_NAMES_REMOVE);
	if ((count($delnames)==0) && (!empty(GedcomConfig::$COMMON_NAMES_REMOVE))) $delnames[] = GedcomConfig::$COMMON_NAMES_REMOVE;
	foreach($delnames as $indexval => $name) {
		if (!empty($name)) {
			unset($topsurns[$name]);
		}
	}

	uasort($topsurns, "ItemSort");
	return $topsurns;
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
function GetSortableName($pid, $alpha="", $surname="", $allnames=false, $rev = false, $changes = false) {
	global $SHOW_LIVING_NAMES, $PRIV_PUBLIC, $GEDCOMID, $COMBIKEY;
	global $indilist, $GEDCOMID, $NAME_REVERSE;

	$mynames = array();

	if (empty($pid)) {
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
	if ($changes) {
		if (ChangeFunctions::GetChangeData(true, $pid, true, "", "")) {
			$rec = ChangeFunctions::GetChangeData(false, $pid, true, "", "");
			$gedrec = $rec[$GEDCOMID][$pid];
			if (!empty($gedrec)) $names = NameFunctions::GetIndiNames($gedrec);
		}
	}

	if ($COMBIKEY) $key = JoinKey($pid, $GEDCOMID);
	else $key = $pid;
	
	//-- first check if the person is in the cache
	if ((!isset($names) && isset($indilist[$key]["names"]))&&($indilist[$key]["gedfile"]==$GEDCOMID)) {
		$names = $indilist[$key]["names"];
	}
	else {
		//-- cache missed, so load the person into the cache with the FindPersonRecord function
		//-- and get the name from the cache again
		$gedrec = FindPersonRecord($pid);
		if (!empty($gedrec) && !isset($names)) {
			// If called from the sanity check, the indilist doesn't contain the names. In that case we get them separately.
			if (isset($indilist[$key]["names"])) $names = $indilist[$key]["names"];
			else $names = NameFunctions::GetIndiNames($gedrec);
		}
		else {
			if ($allnames == true && !isset($names)) {
				if ($rev) $mynames[] = "@P.N. @N.N.";
				else $mynames[] = "@N.N., @P.N.";
				return $mynames;
			}
			else {
				if (!isset($names)) {
					if ($rev) return "@P.N. @N.N.";
					else return "@N.N., @P.N.";
				}
			}
		}
	}
	if ($allnames == true) {
		$mynames = array();
		foreach ($names as $key => $name) {
			if ($NAME_REVERSE) $mynames[] = NameFunctions::SortableNameFromName(NameFunctions::ReverseName($name[0]), $rev);
			else $mynames[] = NameFunctions::SortableNameFromName($name[0], $rev);
		}
		return $mynames;
	}
	foreach($names as $indexval => $name) {
		if ($surname!="" && $name[2]==$surname) {
			if ($NAME_REVERSE) return NameFunctions::SortableNameFromName(NameFunctions::ReverseName($name[0]), $rev);
			else return NameFunctions::SortableNameFromName($name[0], $rev);
		}
		else if ($alpha!="" && $name[1]==$alpha) {
			if ($NAME_REVERSE) return NameFunctions::SortableNameFromName(NameFunctions::ReverseName($name[0]), $rev);
			else return NameFunctions::SortableNameFromName($name[0], $rev);
		}
	}
	if ($NAME_REVERSE) return NameFunctions::SortableNameFromName(NameFunctions::ReverseName($names[0][0]), $rev);
	else return NameFunctions::SortableNameFromName($names[0][0], $rev);
}


/**
 * get the name for a person
 *
 * returns the name in the form Given Name Surname
 * If the <var>$NAME_FROM_GEDCOM</var> variable is true then the name is retrieved from the
 * gedcom record not from the database index.
 * @param string $pid the xref gedcom id of the person
 * @return string the person's name (Given Name Surname)
 */
function GetPersonName($pid, $indirec="", $starred=true) {
	global $NAME_REVERSE, $COMBIKEY;
	global $indilist, $GEDCOMID;

	if ($COMBIKEY) $key = JoinKey($pid, $GEDCOMID);
	else $key = $pid;
	
	$name = "";
	//-- get the name from the gedcom record
	if (GedcomConfig::$NAME_FROM_GEDCOM) {
		if ($indirec == "") $indirec = FindPersonRecord($pid);
		$name = NameFunctions::GetNameInRecord($indirec);
	}
	else {
		if ($indirec == "") {
			//-- first check if the person is in the cache
			if ((isset($indilist[$key]["names"][0][0]))&&($indilist[$key]["gedfile"]==$GEDCOMID)) {
				$name = $indilist[$key]["names"][0][0];
			}
			else {
				//-- cache missed, so load the person into the cache with the FindPersonRecord function
				//-- and get the name from the cache again
				$indirec = FindPersonRecord($pid);
				$names = NameFunctions::GetIndiNames($indirec);
				$name = $names[0][0];
			}
		}
		else {
//			if (!empty($pid) && ChangeFunctions::GetChangeData(true, $pid, true, "", "INDI")) {
//					$rec = ChangeFunctions::GetChangeData(false, $pid, true, "gedlines", "INDI");
//					$names = GetIndiNames($rec[$GEDCOMID][$pid]);
//					$name = $names[0][0];
//				}
//				else {
//					if (!empty($pid) && ChangeFunctions::GetChangeData(true, $pid, true, "", "FAMC")) {
//						$rec = ChangeFunctions::GetChangeData(false, $pid, true, "gedlines", "FAMC");
//						$names = GetIndiNames($rec[$GEDCOMID][$pid]);
//						$name = $names[0][0];
//					}
//				}
			$names = NameFunctions::GetIndiNames($indirec);
			$name = $names[0][0];
		}
	}
	if ($NAME_REVERSE ||HasChinese($name, true)) $name = NameFunctions::ReverseName($name);
	$name = NameFunctions::CheckNN($name, $starred);
	return $name;
}

//toberemoved
/**
 * get the descriptive title of the source
 *
 * @param string $sid the gedcom xref id for the source to find
 * @return string the title of the source
 */
function GetSourceDescriptor($sid, $gedrec="") {
	global $GEDCOMID, $sourcelist, $show_changes;

	if ($sid=="") return false;

	if (empty($gedrec)) {
		$gedrec = FindSourceRecord($sid);
		if ($show_changes && ChangeFunctions::GetChangeData(true, $sid, true)) {
			$rec = ChangeFunctions::GetChangeData(false, $sid, true, "gedlines");
			$gedrec = $rec[$GEDCOMID][$sid];
		}
	}
	if (!empty($gedrec)) {
		$tt = preg_match("/1 TITL (.*)/", $gedrec, $smatch);
		if ($tt>0) {
			if (!PrivacyFunctions::showFact("TITL", $sid, "SOUR") || !PrivacyFunctions::showFactDetails("TITL", $sid, "SOUR")) return GM_LANG_private;
			return $smatch[1];
		}
		$et = preg_match("/1 ABBR (.*)/", $gedrec, $smatch);
		if ($et>0) {
			if (!PrivacyFunctions::showFact("ABBR", $sid, "SOUR") || !PrivacyFunctions::showFactDetails("ABBR", $sid, "SOUR")) return GM_LANG_private;
			return $smatch[1];
		}
		return $sid;
	}
	return false;
}

/**
 * get the descriptive title of the repository
 *
 * @param string $rid the gedcom xref id for the repository to find
 * @return string the title of the repository
 */
function GetRepoDescriptor($rid) {
	global $GEDCOMID, $repo_id_list, $show_changes;

	if ($rid=="") return false;

	$gedrec = FindRepoRecord($rid);
	if ($show_changes && ChangeFunctions::GetChangeData(true, $rid, true)) {
		$rec = ChangeFunctions::GetChangeData(false, $rid, true, "gedlines");
		$gedrec = $rec[$GEDCOMID][$rid];
	}
	if (!empty($gedrec)) {
		$tt = preg_match("/1 NAME (.*)/", $gedrec, $smatch);
		if ($tt>0) {
			if (!PrivacyFunctions::showFact("NAME", $rid, "REPO") || !PrivacyFunctions::showFactDetails("NAME", $rid, "REPO")) return GM_LANG_private;
			return $smatch[1];
		}
	}
	return false;
}

/**
 * get the additional descriptive title of the source
 *
 * @param string $sid the gedcom xref id for the source to find
 * @return string the additional title of the source
 */
function GetAddSourceDescriptor($sid) {
	global $GEDCOMID, $sourcelist, $show_changes;
	$title = "";
	if ($sid=="") return false;

	$gedrec = FindSourceRecord($sid);
	if ($show_changes && ChangeFunctions::GetChangeData(true, $sid, true)) {
		$rec = ChangeFunctions::GetChangeData(false, $sid, true, "gedlines");
		$gedrec = $rec[$GEDCOMID][$sid];
	}
	if (!empty($gedrec)) {
		$ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);
 		if ($ct>0) {
			if (!PrivacyFunctions::showFact("ROMN", $sid, "SOUR") || !PrivacyFunctions::showFactDetails("ROMN", $sid, "SOUR")) return false;
	 		return($match[1]);
 		}
		$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
 		if ($ct>0) {
			if (!PrivacyFunctions::showFact("_HEB", $sid, "SOUR")|| !PrivacyFunctions::showFactDetails("_HEB", $sid, "SOUR")) return false;
	 		return($match[1]);
 		}
 	}
	return false;
}

/**
 * get the additional descriptive title of the repository
 *
 * @param string $rid the gedcom xref id for the repository to find
 * @return string the additional title of the repository
 */
function GetAddRepoDescriptor($rid) {
	global $GEDCOMID, $repolist, $show_changes;
	$title = "";
	if ($rid=="") return false;

	$gedrec = FindRepoRecord($rid);
	if ($show_changes && ChangeFunctions::GetChangeData(true, $rid, true)) {
		$rec = ChangeFunctions::GetChangeData(false, $rid, true, "gedlines");
		$gedrec = $rec[$GEDCOMID][$rid];
	}
	if (!empty($gedrec)) {
		$ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);
 		if ($ct>0) {
			if (!PrivacyFunctions::showFact("ROMN", $rid, "REPO") || !PrivacyFunctions::showFactDetails("ROMN", $rid, "REPO")) return false;
	 		return($match[1]);
 		}
		$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
 		if ($ct>0) {
			if (!PrivacyFunctions::showFact("_HEB", $rid, "REPO")|| !PrivacyFunctions::showFactDetails("_HEB", $rid, "REPO")) return false;
	 		return($match[1]);
 		}
 	}
	return false;
}


function GetFamilyDescriptor($fid, $rev = false, $famrec="", $changes = false, $starred=true) {
	
	if (empty($famrec)) $parents = FindParents($fid);
	else $parents = FindParentsInRecord($famrec);

	if ($parents["HUSB"]) {
		$person =& Person::GetInstance($parents["HUSB"]);
		if ($person->disp_name)
			$hname = GetSortableName($parents["HUSB"], "", "", false, $rev, $changes);
		else $hname = GM_LANG_private;
	}
	else {
		if ($rev) $hname = "@P.N. @N.N.";
		else $hname = "@N.N., @P.N.";
	}
	if ($parents["WIFE"]) {
		$person = null;
		$person =& Person::GetInstance($parents["WIFE"]);
		if ($person->disp_name)
			$wname = GetSortableName($parents["WIFE"], "", "", false, $rev, $changes);
		else $wname = GM_LANG_private;
	}
	else {
		if ($rev) $wname = "@P.N. @N.N.";
		else $wname = "@N.N., @P.N.";
	}

	if (!empty($hname) && !empty($wname)) return NameFunctions::CheckNN($hname,$starred)." + ".CheckNN($wname,$starred);
	else if (!empty($hname) && empty($wname)) return NameFunctions::CheckNN($hname,$starred);
	else if (empty($hname) && !empty($wname)) return NameFunctions::CheckNN($wname,$starred);
}

function GetFamilyAddDescriptor($fid, $rev = false, $famrec="", $changes = false) {
	
	if (empty($famrec)) $parents = FindParents($fid);
	else $parents = FindParentsInRecord($famrec);
	
	if ($parents["HUSB"]) {
		if (PrivacyFunctions::showLivingNameByID($parents["HUSB"]))
			$hname = GetSortableAddName($parents["HUSB"], "", $rev, $changes);
		else $hname = GM_LANG_private;
	}
	else {
		if ($rev) $hname = "@P.N. @N.N.";
		else $hname = "@N.N., @P.N.";
	}
	if ($parents["WIFE"]) {
		if (PrivacyFunctions::showLivingNameByID($parents["WIFE"])) {
			$wname = GetSortableAddName($parents["WIFE"], "", $rev, $changes);
		}
		else $wname = GM_LANG_private;
	}
	else {
		if ($rev) $wname = "@P.N. @N.N.";
		else $wname = "@N.N., @P.N.";
	}
	if (!empty($hname) && !empty($wname)) return NameFunctions::CheckNN($hname)." + ".CheckNN($wname);
	else if (!empty($hname) && empty($wname)) return NameFunctions::CheckNN($hname);
	else if (empty($hname) && !empty($wname)) return NameFunctions::CheckNN($wname);
}

/**
 * get the descriptive title of the media object
 *
 * @param string $sid the gedcom xref id for the object to find
 * @return string the title of the object
 */
function GetMediaDescriptor($mid, $gedrec="") {
	global $GEDCOMID, $show_changes;
	$title = "";
	if ($mid=="") return false;

	if (empty($gedrec)) {
		$gedrec = FindMediaRecord($mid);
		if ($show_changes && ChangeFunctions::GetChangeData(true, $mid, true)) {
			$rec = ChangeFunctions::GetChangeData(false, $mid, true, "gedlines");
			$gedrec = $rec[$GEDCOMID][$mid];
		}
	}
	$rec = GetSubRecord(1, "1 TITL", $gedrec, 1);
	if (!empty($rec)) $title = GetGedcomValue("TITL", 1, $rec);
	if (!empty($title)) return stripslashes($title);
	$rec = GetSubRecord(1, "1 FILE", $gedrec, 1);
	if (!empty($rec)) {
		$trec = GetSubRecord(2, "2 TITL", $rec, 1);
		if (!empty($trec)) $title = GetGedcomValue("TITL", 2, $rec);
		if (!empty($title)) return stripslashes($title);
		return GetGedcomValue("FILE", 1, $rec);
	}
	return false;
}

// -- find and return a given individual's second name in format: firstname lastname
function GetAddPersonName($pid, $record="", $import=false) {

	//-- get the name from the indexes
	if (empty($record)) $record = FindPersonRecord($pid);
	$name_record = GetSubRecord(1, "1 NAME", $record);
	$name = GetAddPersonNameInRecord($name_record, false, $import);
	return $name;
}

function GetAddPersonNameInRecord($name_record, $keep_slash=false, $import=false) {
	global $NAME_REVERSE;

	// Check for ROMN name
	$romn = preg_match("/(2 ROMN (.*)|2 _HEB (.*))/", $name_record, $romn_match);
	if ($romn > 0){
		if ($keep_slash) return trim($romn_match[count($romn_match)-1]);
		$names = preg_split("/\//", $romn_match[count($romn_match)-1]);
		if (count($names)>1) {
			if ($NAME_REVERSE) {
				$name = trim($names[1])." ".trim($names[0]);
			}
			else {
				$name = trim($names[0])." ".trim($names[1]);
			}
		}
	    else $name = trim($names[0]);
	}
	// If not found, and chinese, generate the PinYin equivalent of the name
	else {
		$orgname = NameFunctions::GetNameInRecord($name_record, $import);
		if (HasChinese($orgname, $import)){
			$name = GetPinYin($orgname, $import);
			if ($keep_slash) return trim($name);
			$names = preg_split("/\//", $name);
			if (count($names)>1) {
				if ($NAME_REVERSE) {
					$name = trim($names[1])." ".trim($names[0]);
				}
				else {
					$name = trim($names[0])." ".trim($names[1]);
				}
			}
		    else $name = trim($names[0]);
		}
		else $name = "";
	}
	if ($NAME_REVERSE) $name = NameFunctions::ReverseName($name);
	return $name;
}

// -- find and return a given individual's second name in sort format: familyname, firstname
function GetSortableAddName($pid, $record="", $rev = false, $changes = false) {
	global $NAME_REVERSE;
	global $GEDCOMID;

	//-- get the name from the indexes
	if (empty($record)) $record = FindPersonRecord($pid);
	if ($changes) {
		if (ChangeFunctions::GetChangeData(true, $pid, true, "", "")) {
			$rec = ChangeFunctions::GetChangeData(false, $pid, true, "", "");
			$record = $rec[$GEDCOMID][$pid];
		}
	}
	$name_record = GetSubRecord(1, "1 NAME", $record);
	$name = "";

	// Check for ROMN name
	$romn = preg_match("/(2 ROMN (.*)|2 _HEB (.*))/", $name_record, $romn_match);
	if ($romn == 0) {
		$orgname = NameFunctions::GetNameInRecord($name_record);
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
 * strip name prefixes
 *
 * this function strips the prefixes of lastnames
 * get rid of jr. Jr. Sr. sr. II, III and van, van der, de lowercase surname prefixes
 * a . and space must be behind a-z to ensure shortened prefixes and multiple prefixes are removed
 * @param string $lastname	The name to strip
 * @return string	The updated name
 */
function StripPrefix($lastname){

	$name = preg_replace(array("/ [jJsS][rR]\.?,/", "/ I+,/", "/^[a-z. ]*/"), array(",",",",""), $lastname);
	$name = trim($name);
	return $name;
}

/**
 * Extract the surname from a name
 *
 * This function will extract the surname from an individual name in the form
 * Surname, Given Name
 * All surnames are stored in the global $surnames array
 * It will only get the surnames that start with the letter $alpha
 * For names like van den Burg, it will only return the "Burg"
 * It will work if the surname is all lowercase
 * @param string $indiname	the name to extract the surname from
 */
function ExtractSurname($indiname) {
	global $surnames, $alpha, $surname, $show_all, $i, $testname;

	if (!isset($testname)) $testname="";

	$nsurname = "";
	//-- get surname from a standard name
	if (preg_match("~/([^/]*)/~", $indiname, $match)>0) {
		$nsurname = trim($match[1]);
	}
	//-- get surname from a sortable name
	else {
		$names = preg_split("/,/", $indiname);
		if (count($names)==1) $nsurname = "@N.N.";
		else $nsurname = trim($names[0]);
		$nsurname = preg_replace(array("/ [jJsS][rR]\.?/", "/ I+/"), array("",""), $nsurname);
		
	}
	return $nsurname;
}


/**
 * Get first letter
 *
 * Get the first letter of a UTF-8 string
 *
 * @author Genmod Development Team
 * @param string $text	the text to get the first letter from
 * @return string 	the first letter UTF-8 encoded
 */
function GetFirstLetter($text, $import=false) {
	global $LANGUAGE;

	$text = trim(Str2Upper($text));

	if ($import == true) {
		$hungarianex = array("CS", "DZ" ,"GY", "LY", "NY", "SZ", "TY", "ZS", "DZS");
		$danishex = array("OE", "AE", "AA");
		if (substr($text, 0, 3)=="DZS") $letter = substr($text, 0, 3);
		else if (in_array(substr($text, 0, 2), $hungarianex) || in_array(substr($text, 0, 2), $danishex)) $letter = substr($text, 0, 2);
		else $letter = substr($text, 0, 1);
	}
	else if ($LANGUAGE == "hungarian"){
		$hungarianex = array("CS", "DZ" ,"GY", "LY", "NY", "SZ", "TY", "ZS", "DZS");
		if (substr($text, 0, 3)=="DZS") $letter = substr($text, 0, 3);
		else if (in_array(substr($text, 0, 2), $hungarianex)) $letter = substr($text, 0, 2);
		else $letter = substr($text, 0, 1);
	}
	else if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian"){
		$danishex = array("OE", "AE", "AA");
		$letter = Str2Upper(substr($text, 0, 2));
		if (in_array($letter, $danishex)) {
			if ($letter == "AA") $text = "Å";
			else if ($letter == "OE") $text = "Ø";
			else if ($letter == "AE") $text = "Æ";
		}
		$letter = substr($text, 0, 1);
	}
	
	if (MB_FUNCTIONS) {
		if (isset($letter) && mb_detect_encoding(mb_substr($text, 0, 1)) == "ASCII") return $letter;
		else return mb_substr($text, 0, 1);
	}
	//-- if the letter is an other character then A-Z or a-z
	//-- define values of characters to look for
	$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
	$ord_value3 = array(228, 229, 230, 231, 232, 233);
	$ord = ord(substr($text, 0, 1));
	if (in_array($ord, $ord_value2)) $letter = stripslashes(substr($text, 0, 2));
	else if (in_array($ord, $ord_value3)) $letter = stripslashes(substr($text, 0, 3));

	return $letter;
}



/**
 * Put all characters in a string in lowercase
 *
 * This function is a replacement for strtolower() and will put all characters in lowercase
 *
 * @author	eikland
 * @param	string $value the text to be converted to lowercase
 * @return	string $value_lower the converted text in lowercase
 * @todo look at function performance as it is much slower than strtolower
 */
function Str2Lower($value) {
	global $language_settings,$LANGUAGE, $ALPHABET_upper, $ALPHABET_lower;
	global $all_ALPHABET_upper, $all_ALPHABET_lower;

	//-- get all of the upper and lower alphabets as a string
	if (!isset($all_ALPHABET_upper)) {
		$all_ALPHABET_upper = "";
		$all_ALPHABET_lower = "";
		foreach ($ALPHABET_upper as $l => $up_alphabet){
			$lo_alphabet = $ALPHABET_lower[$l];
			$ll = strlen($lo_alphabet);
			$ul = strlen($up_alphabet);
			if ($ll < $ul) $lo_alphabet .= substr($up_alphabet, $ll);
			if ($ul < $ll) $up_alphabet .= substr($lo_alphabet, $ul);
			$all_ALPHABET_lower .= $lo_alphabet;
			$all_ALPHABET_upper .= $up_alphabet;
		}
	}

	$value_lower = "";
	if (MB_FUNCTIONS) $len = mb_strlen($value);
	else $len = strlen($value);

	//-- loop through all of the letters in the value and find their position in the
	//-- upper case alphabet.  Then use that position to get the correct letter from the
	//-- lower case alphabet.
	$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
	$ord_value3 = array(228, 229, 230, 232, 233);
	for($i=0; $i<$len; $i++) {
		if (!MB_FUNCTIONS) {
			$letter = substr($value, $i, 1);
			$ord = ord($letter);
			if (in_array($ord, $ord_value2)) {
				$i++;
				$letter .= substr($value, $i, 1);
			}
			else if (in_array($ord, $ord_value3)) {
				$i++;
				$letter .= substr($value, $i, 2);
				$i++;
			}
			$pos = strpos($all_ALPHABET_upper, $letter);
			if ($pos!==false) {
				$letter = substr($all_ALPHABET_lower, $pos, strlen($letter));
			}
			$value_lower .= $letter;
		}
		else {
			$letter = mb_substr($value, $i, 1);
			$pos = mb_strpos($all_ALPHABET_upper, $letter);
			if ($pos!==false) {
				$letter = mb_substr($all_ALPHABET_lower, $pos, 1);
			}
			$value_lower .= $letter;
		}
	}
	return $value_lower;
}

/**
 * Put all characters in a string in uppercase
 *
 * This function is a replacement for strtoupper() and will put all characters in uppercase
 *
 * @author Genmod Development Team
 * @param	string $value the text to be converted to uppercase
 * @return	string $value_upper the converted text in uppercase
 * @todo look at function performance as it is much slower than strtoupper
 */
function Str2Upper($value) {
	global $language_settings,$LANGUAGE, $ALPHABET_upper, $ALPHABET_lower;
	global $all_ALPHABET_upper, $all_ALPHABET_lower;

	//-- get all of the upper and lower alphabets as a string
	if (!isset($all_ALPHABET_upper)) {
		$all_ALPHABET_upper = "";
		$all_ALPHABET_lower = "";
		foreach ($ALPHABET_upper as $l => $up_alphabet){
			$lo_alphabet = $ALPHABET_lower[$l];
			$ll = strlen($lo_alphabet);
			$ul = strlen($up_alphabet);
			if ($ll < $ul) $lo_alphabet .= substr($up_alphabet, $ll);
			if ($ul < $ll) $up_alphabet .= substr($lo_alphabet, $ul);
			$all_ALPHABET_lower .= $lo_alphabet;
			$all_ALPHABET_upper .= $up_alphabet;
		}
	}

	$value_upper = "";
	if (MB_FUNCTIONS) $len = mb_strlen($value);
	else $len = strlen($value);

	//-- loop through all of the letters in the value and find their position in the
	//-- lower case alphabet.  Then use that position to get the correct letter from the
	//-- upper case alphabet.
	$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
	$ord_value3 = array(228, 229, 230, 232, 233);
	for($i=0; $i<$len; $i++) {
		if (!MB_FUNCTIONS) {
			$letter = substr($value, $i, 1);
			$ord = ord($letter);
			if (in_array($ord, $ord_value2)) {
				$i++;
				$letter .= substr($value, $i, 1);
			}
			else if (in_array($ord, $ord_value3)) {
				$i++;
				$letter .= substr($value, $i, 2);
				$i++;
			}
			$pos = strpos($all_ALPHABET_lower, $letter);
			if ($pos!==false) {
				$letter = substr($all_ALPHABET_upper, $pos, strlen($letter));
			}
			$value_upper .= $letter;
		}
		else {
			$letter = mb_substr($value, $i, 1);
			$pos = mb_strpos($all_ALPHABET_lower, $letter);
			if ($pos!==false) {
				$letter = mb_substr($all_ALPHABET_upper, $pos, 1);
			}
			$value_upper .= $letter;
		}
	}
	return $value_upper;
}


/**
 * Convert a string to UTF8
 *
 * This function is a replacement for utf8_decode()
 *
 * @author	http://www.php.net/manual/en/function.utf8-decode.php
 * @param	string $in_str the text to be converted
 * @return	string $new_str the converted text
 */
function SmartUtf8Decode($in_str) {
	$new_str = html_entity_decode(htmlentities($in_str, ENT_COMPAT, 'UTF-8'));
	$new_str = str_replace("&oelig;", "\x9c", $new_str);
	$new_str = str_replace("&OElig;", "\x8c", $new_str);
	return $new_str;
}

/**
 * determine the Daitch-Mokotoff Soundex code for a name
 * @param string $name	The name
 * @return array		The array of codes
 */

function DMSoundex($name, $option = "") {
	global $GM_BASE_DIRECTORY, $dmsoundexlist, $dmcoding, $maxchar, $cachecount, $cachename;
	
//	Check for empty string
	if (empty($name)) return array();
	
	// If the code tables are not loaded, reload! Keep them global!
	if (!isset($dmcoding)) {
		$fname = $GM_BASE_DIRECTORY."includes/values/dmarray.full.utf-8.php";
		require($fname);
	}

	// Load the previously saved cachefile and return. Keep the cache global!
	if ($option == "opencache") {
		$cachename = INDEX_DIRECTORY."DM".date("mdHis", filemtime($GM_BASE_DIRECTORY."includes/values/dmarray.full.utf-8.php")).".dat";
		if (file_exists($cachename) && filesize($cachename) != 0) {
//			print "Opening cache file<br />";
			$fp = fopen($cachename, "rb");
			$fcontents = fread($fp, filesize($cachename));
			fclose($fp);
			$dmsoundexlist = unserialize($fcontents);
			reset($dmsoundexlist);
			unset($fcontents);
			$cachecount = count($dmsoundexlist);
//			print $cachecount." items read.<br />";
			return;
		}
		else {
//			print "Cache file is length 0 or does not exist.<br />";
			$dmsoundexlist = array();
			$cachecount = 0;
			// clean up old cache
			$handle = opendir(INDEX_DIRECTORY);
			while (($file = readdir ($handle)) != false) {
				if ((substr($file, 0, 2) == "DM") && (substr($file, -4) == ".dat")) unlink(INDEX_DIRECTORY.$file);
			}
			closedir($handle);
			return;
		}
	}
	
	// Write the cache to disk after use. If nothing is added, just return.
	if ($option == "closecache") {
//		print "Closing cache file<br />";
		if (count($dmsoundexlist) == $cachecount) return;
//		print "Writing cache file, ".count($dmsoundexlist)." items, to ".$cachename."<br />";
		$fp = fopen($cachename, "wb");
		if ($fp) {
			fwrite($fp, serialize($dmsoundexlist));
			fclose($fp);
			return;
		}
	}

	// Hey, we don't want any CJK here!
	$o = ord($name[0]);
	if ($o >= 224 && $o <= 235) {
		return array();
	}

	// Check if in cache
	$name = Str2Upper($name);
	$name = trim($name);
	if (isset($dmsoundexlist[$name])) return $dmsoundexlist[$name];
	// Define the result array and set the first (empty) result
	$result = array();
	$result[0][0] = "";
	$rescount = 1;
	$nlen = strlen($name);
	$npos = 0;
	
	// Loop here through the characters of the name
	while($npos < $nlen) { 
		// Check, per length of characterstring, if it exists in the array.
		// Start from max to length of 1 character
		$code = array();
		for ($i=$maxchar; $i>=0; $i--) {
			// Only check if not read past the last character in the name
			if (($npos + $i) <= $nlen) {
				// See if the substring exists in the coding array
				$element = substr($name,$npos,$i);
				// If found, add the sets of results to the code array for the letterstring
				if (isset($dmcoding[$element])) {
					$dmcount = count($dmcoding[$element]);
					// Loop here through the codesets
					// first letter? Then store the first digit.
					if ($npos == 0) {
						// Loop through the sets of 3
						for ($k=0; $k<$dmcount/3; $k++) {
							$c = $dmcoding[$element][$k*3];
							// store all results, cleanup later
							$code[] = $c;
						}
						break;
					}
					// before a vowel? Then store the second digit
					// Check if the code for the next letter exists
					if ((isset($dmcoding[substr($name, $npos + $i + 1)]))) {
						// See if it's a vowel
						if ($dmcoding[substr($name, $npos + $i + 1)] == 0) {
							// Loop through the sets of 3
							for ($k=0; $k<$dmcount/3; $k++) {
								$c = $dmcoding[$element][$k*3+1];
								// store all results, cleanup later
								$code[] = $c;
							}
							break;
						}
					}
					// Do this in all other situations
					for ($k=0; $k<$dmcount/3; $k++) {
						$c = $dmcoding[$element][$k*3+2];
						// store all results, cleanup later
						$code[] = $c;
					}
					break;
				}
			}
		}
		// Store the results and multiply if more found
		if (isset($dmcoding[$element])) {
			// Add code to existing results

			// Extend the results array if more than one code is found
			for ($j=1; $j<count($code); $j++) {
				$rcnt = count($result);
				// Duplicate the array
				for ($k=0; $k<$rcnt; $k++) {
					$result[] = $result[$k];
				}
			}

			// Add the code to the existing strings
			// Repeat for every code...
			for ($j=0; $j<count($code); $j++) {
				// and add it to the appropriate block of array elements
				for ($k=0; $k<$rescount; $k++) {
					$result[$j * $rescount + $k][] = $code[$j];
				}
			}
			$rescount=count($result);
			$npos = $npos + strlen($element);
		}
		else {
			// The code was not found. Ignore it and continue.
			$npos = $npos + 1;
		}
	}

	// Kill the doubles and zero's in each result
	// Do this for every result
	for ($i=0, $max=count($result); $i<$max; $i++) {
		$j=1;
		$res = $result[$i][0];
		// and check every code in the result.
		// codes are stored separately in array elements, to keep
		// distinction between 6 and 66.
		while($j<count($result[$i])) {
			if (($result[$i][$j-1] != $result[$i][$j]) && ($result[$i][$j] != -1)) {
				$res .= $result[$i][$j];
			}
			$j++;
		}
		// Fill up to 6 digits and store back in the array
		$result[$i] = substr($res."000000", 0, 6);
	}
			
	// Kill the double results in the array
	if (count($result)>1) {
		sort($result);
		for ($i=0; $i<count($result)-1; $i++) {
			while ((isset($result[$i+1])) && ($result[$i] == $result[$i+1])) {
				unset($result[$i+1]);
				sort($result);
			}
		}
			
	}

	// Store in cache and return
	$dmsoundexlist[$name] = $result;
	return $result;			
}
function GetSoundexStrings($namearray, $import=false, $indirec="") {

	$snsndx = "";
	$sndmsndx = "";
	$fnsndx = "";
	$fndmsndx = "";
	$soundexarray = array();
	$soundexarray["R"] = array();
	$soundexarray["R"]["F"] = array();
	$soundexarray["R"]["L"] = array();
	$soundexarray["R"]["P"] = array();
	$soundexarray["D"] = array();
	$soundexarray["D"]["F"] = array();
	$soundexarray["D"]["L"] = array();
	$soundexarray["D"]["P"] = array();
	foreach ($namearray as $key => $names) {
		if ($names[2] != "@N.N.") {
			if (HasChinese($names[2], $import)) $names[2] = GetPinYin($names[2], $import);
			$nameparts = explode(" ",trim($names[2]));
			foreach ($nameparts as $key3 => $namepart) {
				$sval = soundex($namepart);
				if ($sval != "0000") {
					if (!in_array($sval, $soundexarray["R"]["L"])) $soundexarray["R"]["L"][] = $sval;
				}
				$sval = DMsoundex($namepart);
				if (is_array($sval)) {
					foreach ($sval as $key4 => $dmcode) {
						if (!in_array($dmcode, $soundexarray["D"]["L"])) $soundexarray["D"]["L"][] = $dmcode;
					}
				}
			}
		}
		$lnames = preg_split("/\//",$names[0]);
		$fname = $lnames[0];
		if ($fname != "@P.N." && $fname !="") {
			if (HasChinese($fname, $import)) $fname = GetPinYin($fname, $import);
			$nameparts = explode(" ",trim($fname));
			foreach ($nameparts as $key3 => $namepart) {
			// Added: The nickname is embedded in parenthesis. They must be removed and will be added later
			// In one blow, also remove stars (starred names)
			$namepart = preg_replace(array("/\(.*\)/","/\*/"), array("", ""), $namepart);
				$sval = soundex($namepart);
				if ($sval != "0000") {
					if (!in_array($sval, $soundexarray["R"]["F"])) $soundexarray["R"]["F"][] = $sval;
				}
				$sval = DMsoundex($namepart);
				if (is_array($sval)) {
					foreach ($sval as $key4 => $dmcode) {
						if (!in_array($dmcode, $soundexarray["D"]["F"])) $soundexarray["D"]["F"][] = $dmcode;
					}
				}
			}
		}
		// Now also add the nicks. Only if the indirec is added, we will get any result.
		$nicks = NameFunctions::GetNicks($indirec);
		foreach ($nicks as $key => $nick) {
			$sval = soundex($nick);
			if ($sval != "0000") {
				if (!in_array($sval, $soundexarray["R"]["F"])) $soundexarray["R"]["F"][] = $sval;
			}
			$sval = DMsoundex($nick);
			if (is_array($sval)) {
				foreach ($sval as $key4 => $dmcode) {
					if (!in_array($dmcode, $soundexarray["D"]["F"])) $soundexarray["D"]["F"][] = $dmcode;
				}
			}
		}
	}
	return $soundexarray;
}

// ToDo: test for #bytes in character and determine language. 
// CJK = 3 bytes, 228 <= ord <= 233
// Hebrew = 2 bytes, 214 <= ord <= 215
function HasChinese($name, $import = false) {
	global $LANGUAGE;
	
	if ((!GedcomConfig::$DISPLAY_PINYIN || $LANGUAGE == "chinese") && !$import) return false;
	$l = strlen($name);
	if ($l <3) return false;
	$max = $l - 3;
	for($i=0; $i <= $max; $i++) {
		$o = ord($name[$i]);
		if (($o >= 228 && $o <= 233) && ord($name[$i+1]) >= 128 && ord($name[$i+2]) >= 128) {
//			print "Found chinese: ".ord($name[0])." ".ord($name[1])." ".ord($name[2])." ".$name."<br />";
			return true;
		}
	}
	return false;
}
	
function GetPinYin($name, $import = false) {
	global $pinyin, $GM_BASE_DIRECTORY;

	if (!isset($pinyin) && $import) require_once($GM_BASE_DIRECTORY."includes/values/pinyin.php");
	
	$pyname = "";
	$pos1 = 0;
	$pos2 = 2;
	while ($pos2 < strlen($name)) {
		$char = substr($name, $pos1, $pos2 - $pos1 + 1);
		if (HasChinese($char, $import)) {
			$pyname .= $pinyin[$char];
			$pos1 = $pos1 + 3;
			$pos2 = $pos2 + 3;
		}
		else {
			$pyname .= $char[0];
			$pos1 = $pos1 + 1;
			$pos2 = $pos2 + 1;
		}
	}
	if ($pos1 <= strlen($name)-1) $pyname .= substr($name,$pos1);
	$pyname = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $pyname);
	return $pyname;
}	

function GetGBcode($name) {
	global $GBcode, $GM_BASE_DIRECTORY;
	$gbstr = "";
	$pos1 = 0;
	$pos2 = 2;
	while ($pos2 < strlen($name)) {
		$char = substr($name, $pos1, $pos2 - $pos1 + 1);
		if (HasChinese($char, true)) {
			if (!isset($GBcode)) {
				$fname = $GM_BASE_DIRECTORY."includes/gbcode.php";
				require($fname);
			}
			$gbstr .= chr(hexdec(substr($GBcode[$char],0,2))).chr(hexdec(substr($GBcode[$char],2,2)));
			$pos1 = $pos1 + 3;
			$pos2 = $pos2 + 3;
		}
		else {
			$gbstr .= $char[0];
			$pos1 = $pos1 + 1;
			$pos2 = $pos2 + 1;
		}
	}
	if ($pos1 <= strlen($name)-1) $gbstr .= substr($name,$pos1);
	return $gbstr;
}

function GetGBPersonName($pid) {
	//return GetPersonName($pid);
	print GetGBCode(GetPersonName($pid));
	return GetGBCode(GetPersonName($pid));
}

Function GetPediName($pedi, $gender="") {

	if ($pedi == "birth" || $pedi == "") return "";
	if ($pedi == "adopted") {
		if ($gender == "M") return GM_LANG_adopted_son;
		if ($gender == "F") return GM_LANG_adopted_daughter;
		return GM_LANG_adopted_child;
	}
	if ($pedi == "foster") {
		if ($gender == "M") return GM_LANG_foster_son;
		if ($gender == "F") return GM_LANG_foster_daughter;
		return GM_LANG_foster_child;
	}
	if ($pedi == "sealing") {
		if ($gender == "M") return GM_LANG_sealed_son;
		if ($gender == "F") return GM_LANG_sealed_daughter;
		return GM_LANG_sealed_child;
	}
	return "";
}


function AbbreviateName($name, $length) {
	
	// For now, we can only abbreviate 1 byte character strings
	if (!utf8_isASCII($name)) return $name;
	
	$prevabbrev = false;
	$words = preg_split("/ /", $name);
	$name = $words[count($words)-1];
	for($i=count($words)-2; $i>=0; $i--) {
		$len = strlen($name);
		for($j=$i; $j>=0; $j--) {
			// Added: count the space
			$len += strlen($words[$j]) + 1;
		}
		if ($len>$length) {
			// Added: only convert upper case first letter nameparts to first letters. This prevents a surname as "de Haan" to convert to "D. Haan"
			if (str2lower($words[$i]) == $words[$i]) {
				$name = $words[$i]." ".$name;
			}
			else {
				if ($prevabbrev) $name = GetFirstLetter($words[$i]).".".$name;
				else {
					$name = GetFirstLetter($words[$i]).". ".$name;
					$prevabbrev = true;
				}
			}
		}
		else $name = $words[$i]." ".$name;
	}
	return $name;
}


/**
 * builds and returns sosa relationship name in the active language
 *
 * @param string $sosa sosa number
 */
function GetSosaName($sosa) {
	global $LANGUAGE;

	if ($sosa<2) return "";
	$sosaname = "";
	$sosanr = floor($sosa/2);
	$gen = floor( log($sosanr) / log(2) );

	if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian" || $LANGUAGE == "swedish") {
		$addname = "";
		$father = strtolower(GM_LANG_father);
		$mother = strtolower(GM_LANG_mother);
		$grand = "be".($LANGUAGE == "danish"?"dste":"ste");
		$great = "olde";
		$tip = "tip".($LANGUAGE == "danish"?"-":"p-");
		for($i = $gen; $i > 2; $i--) {
			$sosaname .= $tip;
		}
		if ($gen >= 2) $sosaname .= $great;
		if ($gen == 1) $sosaname .= $grand;

		for ($i=$gen; $i>0; $i--){
			if (!(floor($sosa/(pow(2,$i)))%2)) $addname .= $father;
			else $addname .= $mother;
			if (($gen%2 && !($i%2)) || (!($gen%2) && $i%2)) $addname .= "s ";
		}
		if ($LANGUAGE == "swedish") $sosaname = $addname;
		if (!($sosa%2)){
			$sosaname .= $father;
			if ($gen>0) $addname .= $father;
		}
		else {
			$sosaname .= $mother;
			if ($gen>0) $addname .= $mother;
		}
		$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
		if ($LANGUAGE != "swedish") if (!empty($addname)) $sosaname .= ($gen>5?"<br />&nbsp;&nbsp;&nbsp;&nbsp;":"")." <small>(".$addname.")</small>";
	}
	if ($LANGUAGE == "dutch") {
		if ($gen & 256) $sosaname .= GM_LANG_sosa_11;
		if ($gen & 128) $sosaname .= GM_LANG_sosa_10;
		if ($gen & 64) $sosaname .= GM_LANG_sosa_9;
		if ($gen & 32) $sosaname .= GM_LANG_sosa_8;
		if ($gen & 16) $sosaname .= GM_LANG_sosa_7;
		if ($gen & 8) $sosaname .= GM_LANG_sosa_6;
		if ($gen & 4) $sosaname .= GM_LANG_sosa_5;
		$gen = $gen - floor($gen / 4)*4;
		if ($gen == 3) $sosaname .= GM_LANG_sosa_4.GM_LANG_sosa_3.GM_LANG_sosa_2;
		if ($gen == 2) $sosaname .= GM_LANG_sosa_3.GM_LANG_sosa_2;
		if ($gen == 1) $sosaname .= GM_LANG_sosa_2;
		if ($sosa%2) $sosaname .= strtolower(GM_LANG_mother);
		else $sosaname .= strtolower(GM_LANG_father);
		$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
		return $sosaname;
	}
	if ($LANGUAGE == "english") {
		for($i = $gen; $i > 1; $i--) {
			$sosaname .= "Great-";
		}
		if ($gen >= 1) $sosaname .= "Grand";
		if (!($sosa%2)) $sosaname .= strtolower(GM_LANG_father);
		else $sosaname .= strtolower(GM_LANG_mother);
		$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
	}
	if ($LANGUAGE == "finnish") {
		$father = Str2Lower(GM_LANG_father);
		$mother = Str2Lower(GM_LANG_mother);
//		$father = "isä";
//		$mother = "äiti";
//		GM_LANG_sosa_2= "äidin";	//Grand (mother)
		for ($i=$gen; $i>0; $i--){
			if (!(floor($sosa/(pow(2,$i)))%2)) $sosaname .= $father."n";
//			else $sosaname .= GM_LANG_sosa_2;
			else $sosaname .= substr($mother, 0,3)."din";
		}
		if (!($sosa%2)) $sosaname .= $father;
		else $sosaname .= $mother;
		if (substr($sosaname, 0,1)=="i") $sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
		else $sosaname = Str2Upper(substr($mother, 0,2)).substr($sosaname,2);
	}
	if ($LANGUAGE == "french") {
		if ($gen>4) $sosaname = "Arrière(x". ($gen-1) . ")-";
		else for($i = $gen; $i > 1; $i--) {
			$sosaname .= "Arrière-";
		}
		if ($gen >= 1) $sosaname .= "Grand-";
		if (!($sosa%2)) $sosaname .= GM_LANG_father;
		else $sosaname .= GM_LANG_mother;
		if ($gen == 1){
			if ($sosa<6) $sosaname .= " Pater";
			else $sosaname .= " Mater";
			$sosaname .= "nel";
			if ($sosa%2) $sosaname .= "le";
		}
	}
	if ($LANGUAGE == "german") {
		for($i = $gen; $i > 1; $i--) {
			$sosaname .= "Ur-";
		}
		if ($gen >= 1) $sosaname .= "Groß";
		if (!($sosa%2)) $sosaname .= strtolower(GM_LANG_father);
		else $sosaname .= strtolower(GM_LANG_mother);
		$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
	}
	if ($LANGUAGE == "hebrew") {
		$addname = "";
		$father = GM_LANG_father;
		$mother = GM_LANG_mother;
		$greatf = GM_LANG_sosa_22;
		$greatm = GM_LANG_sosa_21;
		$of = GM_LANG_sosa_23;
		$grandfather = GM_LANG_sosa_4;
		$grandmother = GM_LANG_sosa_5;
//		$father = "Aba";
//		$mother = "Ima";
//		$grandfather = "Saba";
//		$grandmother = "Savta";
//		$greatf = " raba";
//		$greatm = " rabta";
//		$of = " shel ";
		for ($i=$gen; $i>=0; $i--){
			if ($i==0){
				if (!($sosa%2)) $addname .= "f";
				else $addname .= "m";
			}
			else if (!(floor($sosa/(pow(2,$i)))%2)) $addname .= "f";
			else $addname .= "m";
			if ($i==0 || strlen($addname)==3){
				if (strlen($addname)==3){
					if (substr($addname, 2,1)=="f") $addname = $grandfather.$greatf;
					else $addname = $grandmother.$greatm;
				}
				else if (strlen($addname)==2){
					if (substr($addname, 1,1)=="f") $addname = $grandfather;
					else $addname = $grandmother;
				}
				else {
					if ($addname=="f") $addname = $father;
					else $addname = $mother;
				}
				$sosaname = $addname.($i<$gen-2?$of:"").$sosaname;
				$addname="";
			}
		}
	}
	if (!empty($sosaname)) return "$sosaname<!-- sosa=$sosa nr=$sosanr gen=$gen -->";

	if (defined("GM_LANG_sosa_".$sosa)) return constant("GM_LANG_sosa_".$sosa);
	else return (($sosa%2) ? GM_LANG_mother : GM_LANG_father) . " " . floor($sosa/2);
}

?>