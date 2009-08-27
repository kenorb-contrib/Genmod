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
if (strstr($_SERVER["SCRIPT_NAME"],"functions_name.php")) {
	require "../intrusion.php";
}

/**
 * Get array of common surnames from index
 *
 * This function returns a simple array of the most common surnames
 * found in the individuals list.
 * @param int $min the number of times a surname must occur before it is added to the array
 */
function GetCommonSurnamesIndex($ged) {
	global $GEDCOMS, $GEDCOM, $COMMON_NAMES_THRESHOLD, $TBLPREFIX, $DBCONN;

	if (empty($GEDCOMS[$ged]["commonsurnames"])) {
		SwitchGedcom($ged);
		$surnames = GetCommonSurnames($COMMON_NAMES_THRESHOLD);
		if (count($surnames) != 0) {
			$sns = "";
			foreach($surnames as $indexval => $surname) {
				$sns .= $surname["name"].", ";
			}
			$sql = "UPDATE ".$TBLPREFIX."gedcoms SET g_commonsurnames='".$DBCONN->EscapeQuery($sns)."' WHERE g_gedcom='".$ged."'";
			$res = NewQuery($sql);
			$GEDCOMS[$ged]["commonsurnames"] = $sns;
		}
		SwitchGedcom();
	}
	$surnames = array();
	if (empty($GEDCOMS[$ged]["commonsurnames"]) || ($GEDCOMS[$ged]["commonsurnames"]==",")) return $surnames;
	$names = preg_split("/[,;]/", $GEDCOMS[$ged]["commonsurnames"]);
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
	global $TBLPREFIX, $GEDCOM, $indilist, $CONFIGURED, $GEDCOMS, $COMMON_NAMES_ADD, $COMMON_NAMES_REMOVE, $gm_lang, $HNN, $ANN, $Users;

	$surnames = array();
	if (!$CONFIGURED || !$Users->AdminUserExists() || (count($GEDCOMS)==0) || (!CheckForImport($GEDCOM))) return $surnames;
	//-- this line causes a bug where the common surnames list is not properly updated
	// if ((!isset($indilist))||(!is_array($indilist))) return $surnames;
	$surnames = GetTopSurnames(100);
	arsort($surnames);
	$topsurns = array();
	$i=0;
	foreach($surnames as $indexval => $surname) {
		$surname["name"] = trim($surname["name"]);
		if (!empty($surname["name"]) 
				&& stristr($surname["name"], "@N.N")===false
				&& stristr($surname["name"], $HNN)===false
				&& stristr($surname["name"], $ANN.",")===false
				&& stristr($COMMON_NAMES_REMOVE, $surname["name"])===false ) {
			if ($surname["match"]>=$min) {
				$topsurns[Str2Upper($surname["name"])] = $surname;
			}
			$i++;
		}
	}
	$addnames = preg_split("/[,;] /", $COMMON_NAMES_ADD);
	if ((count($addnames)==0) && (!empty($COMMON_NAMES_ADD))) $addnames[] = $COMMON_NAMES_ADD;
	foreach($addnames as $indexval => $name) {
		if (!empty($name)) {
			$topsurns[$name]["name"] = $name;
			$topsurns[$name]["match"] = $min;
		}
	}
	$delnames = preg_split("/[,;] /", $COMMON_NAMES_REMOVE);
	if ((count($delnames)==0) && (!empty($COMMON_NAMES_REMOVE))) $delnames[] = $COMMON_NAMES_REMOVE;
	foreach($delnames as $indexval => $name) {
		if (!empty($name)) {
			unset($topsurns[$name]);
		}
	}

	uasort($topsurns, "ItemSort");
	return $topsurns;
}

/**
 * Get the name from the raw gedcom record
 *
 * @param string $indirec the raw gedcom record to get the name from
 */
function GetNameInRecord($indirec, $import=false) {
	global $SHOW_NICK, $NICK_DELIM;
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
	if ($SHOW_NICK && !$import) {
		$n = GetNicks($indirec);
		if (count($n) > 0) {
			$ct = preg_match("~(.*)/(.*)/(.*)~", $name, $match);
			$name = $match[1].substr($NICK_DELIM, 0, 1).$n[0].substr($NICK_DELIM, 1, 1)."/".$match[2]."/".$match[3];
		}
	}
	return $name;
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
	global $TBLPREFIX, $SHOW_LIVING_NAMES, $PRIV_PUBLIC, $GEDCOM, $GEDCOMID, $COMBIKEY;
	global $indilist, $gm_lang, $GEDCOMID, $NAME_REVERSE;

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
		if (GetChangeData(true, $pid, true, "", "")) {
			$rec = GetChangeData(false, $pid, true, "", "");
			$gedrec = $rec[$GEDCOM][$pid];
			if (!empty($gedrec)) $names = GetIndiNames($gedrec);
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
			else $names = GetIndiNames($gedrec);
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

/**
 * Get the sortable name from the gedcom name
 *
 * @param string $name 	the name from the 1 NAME gedcom line including the /
 * @return string 	The new name in the form Surname, Given Names
 */
function SortableNameFromName($name, $rev = false) {
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
	global $NAME_FROM_GEDCOM;
	global $indilist, $GEDCOMID, $GEDCOM;

	if ($COMBIKEY) $key = JoinKey($pid, $GEDCOMID);
	else $key = $pid;
	
	$name = "";
	//-- get the name from the gedcom record
	if ($NAME_FROM_GEDCOM) {
		if ($indirec == "") $indirec = FindPersonRecord($pid);
		$name = GetNameInRecord($indirec);
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
				$names = GetIndiNames($indirec);
				$name = $names[0][0];
			}
		}
		else {
//			if (!empty($pid) && GetChangeData(true, $pid, true, "", "INDI")) {
//					$rec = GetChangeData(false, $pid, true, "gedlines", "INDI");
//					$names = GetIndiNames($rec[$GEDCOM][$pid]);
//					$name = $names[0][0];
//				}
//				else {
//					if (!empty($pid) && GetChangeData(true, $pid, true, "", "FAMC")) {
//						$rec = GetChangeData(false, $pid, true, "gedlines", "FAMC");
//						$names = GetIndiNames($rec[$GEDCOM][$pid]);
//						$name = $names[0][0];
//					}
//				}
			$names = GetIndiNames($indirec);
			$name = $names[0][0];
		}
	}
	if ($NAME_REVERSE ||HasChinese($name, true)) $name = ReverseName($name);
	$name = CheckNN($name, $starred);
	return $name;
}

/**
 * reverse a name
 * this function will reverse a name for languages that
 * prefer last name first such as hungarian and chinese
 * @param string $name	the name to reverse, must be gedcom encoded as if from the 1 NAME line
 * @return string		the reversed name
 */
function ReverseName($name) {
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

/**
 * get the descriptive title of the source
 *
 * @param string $sid the gedcom xref id for the source to find
 * @return string the title of the source
 */
function GetSourceDescriptor($sid, $gedrec="") {
	global $TBLPREFIX, $WORD_WRAPPED_NOTES;
	global $GEDCOM, $sourcelist, $show_changes, $gm_lang;

	if ($sid=="") return false;

	if (empty($gedrec)) {
		$gedrec = FindSourceRecord($sid);
		if ((!isset($show_changes) || $show_changes != "no") && GetChangeData(true, $sid, true)) {
			$rec = GetChangeData(false, $sid, true, "gedlines");
			$gedrec = $rec[$GEDCOM][$sid];
		}
	}
	if (!empty($gedrec)) {
		$tt = preg_match("/1 TITL (.*)/", $gedrec, $smatch);
		if ($tt>0) {
			if (!ShowFact("TITL", $sid, "SOUR") || !ShowFactDetails("TITL", $sid, "SOUR")) return $gm_lang["private"];
			return $smatch[1];
		}
		$et = preg_match("/1 ABBR (.*)/", $gedrec, $smatch);
		if ($et>0) {
			if (!ShowFact("ABBR", $sid, "SOUR") || !ShowFactDetails("ABBR", $sid, "SOUR")) return $gm_lang["private"];
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
	global $TBLPREFIX, $WORD_WRAPPED_NOTES;
	global $GEDCOM, $repo_id_list, $show_changes, $gm_lang;

	if ($rid=="") return false;

	$gedrec = FindRepoRecord($rid);
	if ((!isset($show_changes) || $show_changes != "no") && GetChangeData(true, $rid, true)) {
		$rec = GetChangeData(false, $rid, true, "gedlines");
		$gedrec = $rec[$GEDCOM][$rid];
	}
	if (!empty($gedrec)) {
		$tt = preg_match("/1 NAME (.*)/", $gedrec, $smatch);
		if ($tt>0) {
			if (!ShowFact("NAME", $rid, "REPO") || !ShowFactDetails("NAME", $rid, "REPO")) return $gm_lang["private"];
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
	global $TBLPREFIX, $WORD_WRAPPED_NOTES;
	global $GEDCOM, $sourcelist, $show_changes;
	$title = "";
	if ($sid=="") return false;

	$gedrec = FindSourceRecord($sid);
	if ((!isset($show_changes) || $show_changes != "no") && GetChangeData(true, $sid, true)) {
		$rec = GetChangeData(false, $sid, true, "gedlines");
		$gedrec = $rec[$GEDCOM][$sid];
	}
	if (!empty($gedrec)) {
		$ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);
 		if ($ct>0) {
			if (!ShowFact("ROMN", $sid, "SOUR") || !ShowFactDetails("ROMN", $sid, "SOUR")) return false;
	 		return($match[1]);
 		}
		$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
 		if ($ct>0) {
			if (!ShowFact("_HEB", $sid, "SOUR")|| !ShowFactDetails("_HEB", $sid, "SOUR")) return false;
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
	global $TBLPREFIX, $WORD_WRAPPED_NOTES;
	global $GEDCOM, $repolist, $show_changes;
	$title = "";
	if ($rid=="") return false;

	$gedrec = FindRepoRecord($rid);
	if ((!isset($show_changes) || $show_changes != "no") && GetChangeData(true, $rid, true)) {
		$rec = GetChangeData(false, $rid, true, "gedlines");
		$gedrec = $rec[$GEDCOM][$rid];
	}
	if (!empty($gedrec)) {
		$ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);
 		if ($ct>0) {
			if (!ShowFact("ROMN", $rid, "REPO") || !ShowFactDetails("ROMN", $rid, "REPO")) return false;
	 		return($match[1]);
 		}
		$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
 		if ($ct>0) {
			if (!ShowFact("_HEB", $rid, "REPO")|| !ShowFactDetails("_HEB", $rid, "REPO")) return false;
	 		return($match[1]);
 		}
 	}
	return false;
}


function GetFamilyDescriptor($fid, $rev = false, $famrec="", $changes = false, $starred=true) {
	global $gm_lang;
	
	if (empty($famrec)) $parents = FindParents($fid);
	else $parents = FindParentsInRecord($famrec);

	if ($parents["HUSB"]) {
		if (showLivingNameById($parents["HUSB"]))
			$hname = GetSortableName($parents["HUSB"], "", "", false, $rev, $changes);
		else $hname = $gm_lang["private"];
	}
	else {
		if ($rev) $hname = "@P.N. @N.N.";
		else $hname = "@N.N., @P.N.";
	}
	if ($parents["WIFE"]) {
		if (showLivingNameById($parents["WIFE"]))
			$wname = GetSortableName($parents["WIFE"], "", "", false, $rev, $changes);
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

function GetFamilyAddDescriptor($fid, $rev = false, $famrec="", $changes = false) {
	global $gm_lang;
	
	if (empty($famrec)) $parents = FindParents($fid);
	else $parents = FindParentsInRecord($famrec);
	
	if ($parents["HUSB"]) {
		if (showLivingNameById($parents["HUSB"]))
			$hname = GetSortableAddName($parents["HUSB"], $rev, $changes);
		else $hname = $gm_lang["private"];
	}
	else {
		if ($rev) $hname = "@P.N. @N.N.";
		else $hname = "@N.N., @P.N.";
	}
	if ($parents["WIFE"]) {
		if (showLivingNameById($parents["WIFE"]))
			$wname = GetSortableAddName($parents["WIFE"], $rev, $changes);
		else $wname = $gm_lang["private"];
	}
	else {
		if ($rev) $wname = "@P.N. @N.N.";
		else $wname = "@N.N., @P.N.";
	}
	if (!empty($hname) && !empty($wname)) return CheckNN($hname)." + ".CheckNN($wname);
	else if (!empty($hname) && empty($wname)) return CheckNN($hname);
	else if (empty($hname) && !empty($wname)) return CheckNN($wname);
}

/**
 * get the descriptive title of the media object
 *
 * @param string $sid the gedcom xref id for the object to find
 * @return string the title of the object
 */
function GetMediaDescriptor($mid, $gedrec="") {
	global $TBLPREFIX;
	global $GEDCOM, $show_changes;
	$title = "";
	if ($mid=="") return false;

	if (empty($gedrec)) {
		$gedrec = FindMediaRecord($mid);
		if ((!isset($show_changes) || $show_changes != "no") && GetChangeData(true, $mid, true)) {
			$rec = GetChangeData(false, $mid, true, "gedlines");
			$gedrec = $rec[$GEDCOM][$mid];
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
	global $NAME_FROM_GEDCOM;

	//-- get the name from the indexes
	if (empty($record)) $record = FindPersonRecord($pid);
	$name_record = GetSubRecord(1, "1 NAME", $record);
	$name = GetAddPersonNameInRecord($name_record, false, $import);
	return $name;
}

function GetAddPersonNameInRecord($name_record, $keep_slash=false, $import=false) {
	global $NAME_REVERSE;
	global $NAME_FROM_GEDCOM;

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
		$orgname = GetNameInRecord($name_record);
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
	if ($NAME_REVERSE) $name = ReverseName($name);
	return $name;
}

// -- find and return a given individual's second name in sort format: familyname, firstname
function GetSortableAddName($pid, $rev = false, $changes = false) {
	global $NAME_REVERSE;
	global $NAME_FROM_GEDCOM, $GEDCOM;

	//-- get the name from the indexes
	$record = FindPersonRecord($pid);
	if ($changes) {
		if (GetChangeData(true, $pid, true, "", "")) {
			$rec = GetChangeData(false, $pid, true, "", "");
			$record = $rec[$GEDCOM][$pid];
		}
	}
	$name_record = GetSubRecord(1, "1 NAME", $record);

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

	else $name = GetSortableName($pid, "", "", false, $rev, $changes);

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
function ExtractSurname($indiname, $count=true) {
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
	if ($count) SurnameCount($nsurname);
	return $nsurname;
}

/**
 * Add a surname to the surnames array for counting
 *
 * @param string $nsurname
 * @return string
 */
function SurnameCount($nsurname, $sort_letter="") {
	global $surnames, $alpha, $surname, $show_all, $i, $testname;
	
	if ($sort_letter == "") $sort_letter = GetFirstLetter($nsurname);
	$lname = StripPrefix($nsurname);
	if (empty($lname)) $lname = $nsurname;
	if (($show_all=="yes") || empty($alpha) || ($alpha==$sort_letter)) {
//		$tsurname = preg_replace(array("/ [jJsS][rR]\.?/", "/ I+/"), array("",""), $nsurname);
		$tsurname = Str2Upper(StripPrefix(preg_replace("/([^ ]+)\*/", "$1", $nsurname)));
		if (empty($surname) || (Str2Upper($surname)==$tsurname)) {
			if (!isset($surnames[$tsurname])) {
				$surnames[$tsurname] = array();
				$surnames[$tsurname]["name"] = preg_replace("/([^ ]+)\*/", "$1", $nsurname);
				$surnames[$tsurname]["match"] = 1;
				$surnames[$tsurname]["fam"] = 1;
				$surnames[$tsurname]["alpha"] = $sort_letter;
			}
			else {
				$surnames[$tsurname]["match"]++;
				if ($i==0 || $testname != $tsurname) $surnames[$tsurname]["fam"]++;
			}
			if ($i==0) $testname = $tsurname;
		}
		return $nsurname;
	}
	return false;
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
	global $LANGUAGE, $CHARACTER_SET;

	$text=trim(Str2Upper($text));
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
	else $letter = substr($text, 0, 1);

	//-- if the letter is an other character then A-Z or a-z
	//-- define values of characters to look for
	$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
	$ord_value3 = array(228, 229, 230, 231, 232, 233);
	$ord = ord(substr($letter, 0, 1));
	if (in_array($ord, $ord_value2)) $letter = stripslashes(substr($text, 0, 2));
	else if (in_array($ord, $ord_value3)) $letter = stripslashes(substr($text, 0, 3));

	return $letter;
}

/**
 * This function replaces @N.N. and @P.N. with the language specific translations
 * @param mixed $names	$names could be an array of name parts or it could be a string of the name
 * @return string
 */
function CheckNN($names, $starred=true) {
	global $gm_lang, $HNN, $ANN, $UNDERLINE_NAME_QUOTES;

	$fullname = "";
	$NN = $gm_lang["NN"];
 	$PN = $gm_lang["PN"];

	if (!is_array($names)){
		if (hasRTLText($names)) {
			$NN = RTLUndefined($names);
			$PN = $NN;
		}
		$names = stripslashes($names);
		if (HasChinese($names, true)) $names = preg_replace(array("~ /~","~/,~","~/~"), array("", ",", ""), $names);
		else $names = preg_replace(array("~ /~","~/,~","~/~"), array(" ", ",", " "), $names);
		$names = preg_replace(array("/@N.N.?/","/@P.N.?/"), array($NN,$PN), trim($names));
		if ($UNDERLINE_NAME_QUOTES) {
			if ($starred) $names = preg_replace("/\"(.+)\"/", "<span class=\"starredname\">$1</span>", $names);
			else $names = preg_replace("/\"(.+)\"/", "$1", $names);
		}
		//-- underline names with a * at the end
		//-- see this forum thread http://sourceforge.net/forum/forum.php?thread_id=1223099&forum_id=185165
		if ($starred) $names = preg_replace("/([^ ]+)\*/", "<span class=\"starredname\">$1</span>", $names);
		else $names = preg_replace("/([^ ]+)\*/", "$1", $names);
		return $names;
	}
	if (count($names) == 2 && stristr($names[0], "@N.N") && stristr($names[1], "@N.N")){
		$fullname = $gm_lang["NN"]. " + ". $gm_lang["NN"];
	}
	else {
		for($i=0; $i<count($names); $i++) {
			if (hasRTLText($names[$i])) {
				$NN = RTLUndefined($names[$i]);
				$PN = $NN;
			}
			
			for($i=0; $i<count($names); $i++) {
				if ($UNDERLINE_NAME_QUOTES) {
					if ($starred) $names[$i] = preg_replace("/\"(.+)\"/", "<span class=\"starredname\">$1</span>", $names[$i]);
					else $names[$i] = preg_replace("/\"(.+)\"/", "$1", $names[$i]);
				}
				//-- underline names with a * at the end
				//-- see this forum thread http://sourceforge.net/forum/forum.php?thread_id=1223099&forum_id=185165
				if ($starred) $names[$i] = preg_replace("/([^ ]+)\*/", "<span class=\"starredname\">$1</span>", $names[$i]);
				else $names[$i] = preg_replace("/([^ ]+)\*/", "$1", $names[$i]);

				if (stristr($names[$i], "@N.N")) $names[$i] = preg_replace("/@N.N.?/", $NN, trim($names[$i]));
                if (stristr($names[$i], "@P.N")) $names[$i] = $PN;
				if (substr(trim($names[$i]), 0, 5) == "@P.N." && strlen(trim($names[$i])) > 5) {
					$names[$i] = substr(trim($names[$i]), 5, (strlen($names[$i])-5));
				}
 				if ($i==1 && (stristr($names[0], $gm_lang["NN"]) || stristr($names[0],$HNN) || stristr($names[0],$ANN)) && count($names) == 3) $fullname .= ", ";
 				else if ($i==2 && (stristr($names[2], $gm_lang["NN"])||stristr($names[2],$HNN)||stristr($names[2],$ANN)) && count($names) == 3) $fullname .= " + ";
				else if ($i==2 && stristr($names[2], "Individual ") && count($names) == 3) $fullname .= " + ";
				else if ($i==2 && count($names) > 3) $fullname .= " + ";
				else $fullname .= ", ";
				$fullname .= trim($names[$i]);
			}
		}
	}
	if (empty($fullname)) return $gm_lang["NN"];
	if (substr(trim($fullname),-1) === ",") $fullname = substr($fullname,0,strlen(trim($fullname))-1);
	if (substr(trim($fullname),0,2) === ", ") $fullname = substr($fullname,2,strlen(trim($fullname)));

	return $fullname;
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
	$len = strlen($value);

	//-- loop through all of the letters in the value and find their position in the
	//-- upper case alphabet.  Then use that position to get the correct letter from the
	//-- lower case alphabet.
	$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
	$ord_value3 = array(228, 229, 230, 232, 233);
	for($i=0; $i<$len; $i++) {
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
	$len = strlen($value);

	//-- loop through all of the letters in the value and find their position in the
	//-- lower case alphabet.  Then use that position to get the correct letter from the
	//-- upper case alphabet.
	$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
	$ord_value3 = array(228, 229, 230, 232, 233);
	for($i=0; $i<$len; $i++) {
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
 * get an array of names from an indivdual record
 * @param string $indirec	The raw individual gedcom record
 * @return array	The array of individual names
 */
function GetIndiNames($indirec, $import=false, $marr_names=true) {
	$names = array();
	//-- get all names
	$namerec = GetSubRecord(1, "1 NAME", $indirec, 1);
	if (empty($namerec)) $names[] = array("@P.N. /@N.N./", "@", "@N.N.", "A");
	else {
		$j = 1;
		while(!empty($namerec)) {
			$name = GetNameInRecord($namerec, $import);
			$surname = ExtractSurname($name, false);
			if (empty($surname)) $surname = "@N.N.";
			$lname = preg_replace("/^[a-z0-9 \.]+/", "", $surname);
			if (empty($lname)) $lname = $surname;
			$letter = GetFirstLetter($lname, $import);
			$letter = Str2Upper($letter);
			if (empty($letter)) $letter = "@";
			if (preg_match("~/~", $name)==0) $name .= " /@N.N./";
			if ($j == 1) $names[] = array($name, $letter, $surname, "P");
			else $names[] = array($name, $letter, $surname, "A");

			//-- check for _HEB or ROMN name sub tags
			$addname = GetAddPersonNameInRecord($namerec, true);
			if (!empty($addname)) {
				$surname = ExtractSurname($addname, false);
				if (empty($surname)) $surname = "@N.N.";
				$lname = preg_replace("/^[a-z0-9 \.]+/", "", $surname);
				if (empty($lname)) $lname = $surname;
				$letter = GetFirstLetter($lname, $import);
				$letter = Str2Upper($letter);
				if (empty($letter)) $letter = "@";
				if (preg_match("~/~", $addname)==0) $addname .= " /@N.N./";
				$names[] = array($addname, $letter, $surname, "A");
			}
			//-- check for _MARNM name subtags
			if ($marr_names) {
				$ct = preg_match_all("/\d _MARNM (.*)/", $namerec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$marriedname = trim($match[$i][1]);
					$surname = ExtractSurname($marriedname, false);
					if (empty($surname)) $surname = "@N.N.";
					$lname = preg_replace("/^[a-z0-9 \.]+/", "", $surname);
					if (empty($lname)) $lname = $surname;
					$letter = GetFirstLetter($lname, $import);
					$letter = Str2Upper($letter);
					if (empty($letter)) $letter = "@";
					if (preg_match("~/~", $marriedname)==0) $marriedname .= " /@N.N./";
					$names[] = array($marriedname, $letter, $surname, "C");
				}
			}
			//-- check for _AKA name subtags
			$ct = preg_match_all("/\d _AKA (.*)/", $namerec, $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				$marriedname = trim($match[$i][1]);
				$surname = ExtractSurname($marriedname, false);
				if (empty($surname)) $surname = "@N.N.";
				$lname = preg_replace("/^[a-z0-9 \.]+/", "", $surname);
				if (empty($lname)) $lname = $surname;
				$letter = GetFirstLetter($lname, $import);
				$letter = Str2Upper($letter);
				if (empty($letter)) $letter = "@";
				if (preg_match("~/~", $marriedname)==0) $marriedname .= " /@N.N./";
				$names[] = array($marriedname, $letter, $surname, "A");
			}
			$j++;
			$namerec = GetSubRecord(1, "1 NAME", $indirec, $j);
		}
	}
	return $names;
}

/**
 * determine the Daitch-Mokotoff Soundex code for a name
 * @param string $name	The name
 * @return array		The array of codes
 */

function DMSoundex($name, $option = "") {
	global $GM_BASE_DIRECTORY, $dmsoundexlist, $dmcoding, $maxchar, $INDEX_DIRECTORY, $cachecount, $cachename;
	
//	Check for empty string
	if (empty($name)) return array();
	
	// If the code tables are not loaded, reload! Keep them global!
	if (!isset($dmcoding)) {
		$fname = $GM_BASE_DIRECTORY."includes/dmarray.full.utf-8.php";
		require($fname);
	}

	// Load the previously saved cachefile and return. Keep the cache global!
	if ($option == "opencache") {
		$cachename = $INDEX_DIRECTORY."DM".date("mdHis", filemtime($GM_BASE_DIRECTORY."includes/dmarray.full.utf-8.php")).".dat";
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
			$handle = opendir($INDEX_DIRECTORY);
			while (($file = readdir ($handle)) != false) {
				if ((substr($file, 0, 2) == "DM") && (substr($file, -4) == ".dat")) unlink($INDEX_DIRECTORY.$file);
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
		$nicks = GetNicks($indirec);
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
	global $DISPLAY_PINYIN, $LANGUAGE;
	
	if ((!$DISPLAY_PINYIN || $LANGUAGE == "chinese") && !$import) return false;
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

	if (!isset($pinyin) && $import) require_once($GM_BASE_DIRECTORY."includes/pinyin.php");
	
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
	global $gm_lang;

	if ($pedi == "birth" || $pedi == "") return "";
	if ($pedi == "adopted") {
		if ($gender == "M") return $gm_lang["adopted_son"];
		if ($gender == "F") return $gm_lang["adopted_daughter"];
		return $gm_lang["adopted_child"];
	}
	if ($pedi == "foster") {
		if ($gender == "M") return $gm_lang["foster_son"];
		if ($gender == "F") return $gm_lang["foster_daughter"];
		return $gm_lang["foster_child"];
	}
	if ($pedi == "sealing") {
		if ($gender == "M") return $gm_lang["sealed_son"];
		if ($gender == "F") return $gm_lang["sealed_daughter"];
		return $gm_lang["sealed_child"];
	}
	return "";
}

function GetNicks($namerec) {
	
	$nicks = array();
	if (!empty($namerec)) {
		$i = 1;
		while($n = GetSubrecord(2, "2 NICK", $namerec, $i)) {
			$nicks[] = GetGedcomValue("NICK", 2, $n);
			$i++;
		}
	}
	return $nicks;
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

function SearchAddAssos() {
	global $assolist, $indi_printed, $fam_printed, $printindiname, $printfamname, $famlist;

	// Step 1: Pull the relations to the printed results from the assolist
	$toadd = array();
	foreach ($assolist as $p1key => $assos) {
		// Get the person who might be a relation
		// Check if we can show him/her
		SwitchGedcom(SplitKey($p1key, "gedid"));
		foreach ($assos as $key => $asso) {
			$p2key = $asso["pid2"];
			// Design choice: we add to->from and from->to
			// Check if he/she  and the persons/fams he/she is related to, actually exist
			// Also respect name privacy for all related individuals. If one is hidden, it prevents the relation to be displayed
			// p1key can be either indi or fam, p2key can only be indi.
			if (array_key_exists($p2key, $indi_printed) || array_key_exists($p1key, $indi_printed) || array_key_exists($p1key, $fam_printed)) {
				// p2key is always an indi, so check this first
				$disp = true;
				if (!ShowLivingNameByID(SplitKey($p2key, "id"))) $disp = false;
				else {
					if (!empty($asso["fact"]) &&!ShowFact($asso["fact"], SplitKey($p2key, "id"))) $disp = false;
					else {
						// assotype is the type of p1key
						if ($asso["type"] == "indi") {
							if (!ShowLivingNameByID(SplitKey($p1key, "id"))) $disp = false;
						}
						else {
							$parents = FindParentsInRecord($asso["gedcom"]);
							if (!ShowLivingNameByID($parents["HUSB"]) || !ShowLivingNameByID($parents["WIFE"])) $disp = false;
						}
					}
				}
				if ($disp && !empty($asso["resn"])) {
					if (!empty($asso["fact"])) {
						$rec = "1 ".$fact."\r\n2 ASSO @".SplitKey($p1key, "id")."@\r\n2 RESN ".$asso["resn"]."\r\n";
						$disp = FactViewRestricted(SplitKey($p2key, "id"), $rec, 2);
					}
					else {
						$rec = "1 ASSO @".SplitKey($p1key, "id")."@\r\n2 RESN ".$asso["resn"]."\r\n";
						$disp = FactViewRestricted(SplitKey($p2key, "id"), $rec, 2);
					}
				}
				// save his relation to existing search results
				if ($disp) {
					$toadd[$p1key][] = array($p2key, "indi", $asso["fact"], $asso["role"]);
					$toadd[$p2key][] = array($p1key, $asso["type"], $asso["fact"], $asso["role"]);
				}
			}
		}
	}

	// Step 2: Add the relations who are not printed themselves
	foreach ($toadd as $add => $links) {
		$arec = FindGedcomRecord(SplitKey($add, "id"));
		$type = GetRecType($arec);
		if ($type == "INDI") {
			if (!array_key_exists($add, $indi_printed)) {
				$indi_printed[$add] = "1";
				$names = GetIndiNames($arec);
				foreach ($names as $nkey => $namearray) {
					$printindiname[] = array(SortableNameFromName($namearray[0]), SplitKey($add, "id"), SplitKey($add, "ged"), "");
				}
			}
		}
		else {
			if (!array_key_exists($add, $fam_printed)) {
				$fam_printed[$add] = "1";
				$fam = $famlist[$add];
				$hname = GetSortableName($fam["HUSB"], "", "", true);
				$wname = GetSortableName($fam["WIFE"], "", "", true);
				if (empty($hname)) $hname = "@N.N.";
				if (empty($wname)) $wname = "@N.N.";
				$name = array();
				foreach ($hname as $hkey => $hn) {
					foreach ($wname as $wkey => $wn) {
						$name[] = $hn." + ".$wn;
						$name[] = $wn." + ".$hn;
					}
				}
				foreach ($name as $namekey => $famname) {
					$famsplit = preg_split("/(\s\+\s)/", trim($famname));
					// Both names have to have the same direction and combination of chinese/not chinese
					if (hasRTLText($famsplit[0]) == hasRTLText($famsplit[1]) && HasChinese($famsplit[0], true) == HasChinese($famsplit[1], true)) {
						$printfamname[]=array(CheckNN($famname), SplitKey($add, "id"), get_gedcom_from_id($fam["gedfile"]),"");
					}
				}
			}
		}
	}

	// Step 3: now cycle through the indi search results to add a relation link
	foreach ($printindiname as $pkey => $printindi) {
		$pikey = JoinKey($printindi[1], get_id_from_gedcom($printindi[2]));
		if (isset($toadd[$pikey])) {
			foreach ($toadd[$pikey] as $rkey => $asso) {
				SwitchGedcom($printindi[2]);
				$printindiname[$pkey][3][] = $asso;
			}
		}
	}
	// Step 4: now cycle through the fam search results to add a relation link
	foreach ($printfamname as $pkey => $printfam) {
		$pikey = JoinKey($printfam[1], get_id_from_gedcom($printfam[2]));
		if (isset($toadd[$pikey])) {
			foreach ($toadd[$pikey] as $rkey => $asso) {
				SwitchGedcom($printfam[2]);
				$printfamname[$pkey][3][] = $asso;
			}
		}
	}
	SwitchGedcom();
}
?>