<?php
/**
 * Database functions file
 *
 * This file implements the datastore functions necessary for Genmod to use an SQL database as its
 * datastore. This file also implements array caches for the database tables.  Whenever data is
 * retrieved from the database it is stored in a cache.  When a database access is requested the
 * cache arrays are checked first before querying the database.
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
 * @version $Id$
 * @package Genmod
 * @subpackage DB
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

function NewQuery($sql, $noreport=false) {
	global $DBCONN;
	
	$res = new Result();
	if ($res->result = $DBCONN->Query($sql, $noreport)) return $res;
	else return false;
}

/**
 * Clean up an item retrieved from the database
 *
 * Clean the slashes and convert special
 * html characters to their entities for
 * display and entry into form elements.
 * @param  mixed $item	the item to cleanup
 * @return mixed the cleaned up item
 */
/*function db_cleanup($item) {
	if (is_array($item)) {
		foreach($item as $key=>$value) {
			if ($key != "gedcom") $item[$key]=stripslashes($value);
			else $key=$value;
		}
		return $item;
	}
	else {
		return stripslashes($item);
	}
}
*/
/**
 * Check if a gedcom has been imported into the database
 *
 * This function checks the database to see if the given GEDCOM has been imported yet.
 *
 * @author	Genmod Development Team
 * @param 	string 	$ged 	the filename or id of the gedcom to check for import
 * @return 	bool 	return true if the gedcom has been imported otherwise returns false
 */
function CheckForImport($gedid) {
	
	$sql = "SELECT COUNT(i_id) FROM ".TBLPREFIX."individuals WHERE i_file='";
	if (is_int($gedid)) $sql .= $gedid;
	else $sql .= DbLayer::EscapeQuery(get_id_from_gedcom($gedid));
	$sql .= "' LIMIT 1";
	if ($res = NewQuery($sql)) {
		$row = $res->FetchRow();
		$res->FreeResult();
		if ($row[0]>0) return true;
	}
	return false;
}

/**
 * find the gedcom record for a family
 *
 * This function first checks the <var>$famlist</var> cache to see if the family has already
 * been retrieved from the database.  If it hasn't been retrieved, then query the database and
 * add it to the cache.
 * also lookup the husb and wife so that they are in the cache
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#family
 * @param string $famid the unique gedcom xref id of the family record to retrieve
 * @return string the raw gedcom record is returned
 */
function FindFamilyRecord($famid, $gedfile="", $renew = false) {
	global $famlist, $COMBIKEY;
	
	
	if (DEBUG) print "Old function called: FindFamilyRecord for ".$famid."<br />".pipo;
	
	if (empty($famid)) return false;
	if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
	
	if ($COMBIKEY) $key = JoinKey($famid, $gedfile);
	else $key = $famid;

	if (!$renew && isset($famlist[$key]["gedfile"])&&($famlist[$key]["gedfile"] == $gedfile)) return $famlist[$key]["gedcom"];

	$sql = "SELECT f_gedrec, f_file, f_husb, f_wife FROM ".TBLPREFIX."families WHERE f_key='".DbLayer::EscapeQuery(JoinKey($famid, $gedfile))."'";

	$res = NewQuery($sql);
	if (!$res || $res->NumRows()==0) {
		return "";
	}
	$row = $res->fetchAssoc();

	$famlist[$key]["gedcom"] = $row["f_gedrec"];
	$famlist[$key]["gedfile"] = $row["f_file"];
	$famlist[$key]["HUSB"] = SplitKey($row["f_husb"], "id");
	$famlist[$key]["WIFE"] = SplitKey($row["f_wife"], "id");
//	FindPersonRecord($row["f_husb"], $gedfile);
//	FindPersonRecord($row["f_wife"], $gedfile);
	$res->FreeResult();
	return $row["f_gedrec"];
}

/**
 * find the gedcom record for an individual
 *
 * This function first checks the <var>$indilist</var> cache to see if the individual has already
 * been retrieved from the database.  If it hasn't been retrieved, then query the database and
 * add it to the cache.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#indi
 * @param string $pid the unique gedcom xref id of the individual record to retrieve
 * @return string the raw gedcom record is returned
 */
function FindPersonRecord($pid, $gedfile="", $renew = false, $nocache = false) {
	global $COMBIKEY;
	global $indilist;
	
	if (DEBUG) print "Old function called: FindPersonRecord for ".$pid."<br />".pipo;
	
	if (empty($pid)) return false;
	if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
	if (empty($gedfile)) return "";
	
	// print $pid." ".$gedfileid."<br />";
	//-- first check the indilist cache
	if (!$renew && isset($indilist[$pid]["gedfile"]) && $indilist[$pid]["gedfile"]==$gedfile) return $indilist[$pid]["gedcom"];
	if (!$renew && isset($indilist[JoinKey($pid, $gedfile)]["gedfile"])) return $indilist[JoinKey($pid, $gedfile)]["gedcom"];

	$sql = "SELECT i_key, i_gedrec, i_isdead, i_file FROM ".TBLPREFIX."individuals WHERE i_key='".DbLayer::EscapeQuery(JoinKey($pid, $gedfile))."'";
	$res = NewQuery($sql);
	if ($res) {
		if ($res->NumRows()==0) {
			return "";
		}
		$row = $res->fetchAssoc();
		$indi = array();
		$indi["gedcom"] = $row["i_gedrec"];
		if ($nocache) return $indi["gedcom"];
		$indi["names"] = NameFunctions::GetIndiNames($row["i_gedrec"]);
		$indi["isdead"] = $row["i_isdead"];
		$indi["gedfile"] = $row["i_file"];
		$res->FreeResult();
		if ($COMBIKEY) $indilist[JoinKey($pid, $indi["gedfile"])] = $indi;
		else $indilist[$pid] = $indi;
		return $row["i_gedrec"];
	}
}

/**
 * find the gedcom record
 *
 * This function first checks the caches to see if the record has already
 * been retrieved from the database.  If it hasn't been retrieved, then query the database and
 * add it to the cache.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#other
 * @todo change $gedfile to id instead of name
 * @param string $pid the unique gedcom xref id of the record to retrieve
 * @return string the raw gedcom record is returned
 */
function FindGedcomRecord($pid, $gedfile = "", $renew = false, $nocache = false) {
	global $indilist, $famlist, $sourcelist, $otherlist, $repolist, $medialist;
	
 	if (DEBUG) print "hit on findgecomrecord for: ".$pid."<br />".pipo;
	if (empty($pid)) return false;
	if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
	if (!$renew) {
		if (isset($indilist[$pid."[".$gedfile."]"]["gedcom"])) {
//			print "Hit on indilist for ".$pid."<br />";
			return $indilist[$pid."[".$gedfile."]"]["gedcom"];
		}
		if ((isset($indilist[$pid]["gedcom"]))&&($indilist[$pid]["gedfile"]==$gedfile)) {
//			print "Hit on indilist for ".$pid."<br />";
			return $indilist[$pid]["gedcom"];
		}
		if ((isset($famlist[$pid]["gedcom"]))&&($famlist[$pid]["gedfile"]==$gedfile)) {
//			print "Hit on famlist for ".$pid."<br />";
			return $famlist[$pid]["gedcom"];
		}
		if ((isset($sourcelist[$pid]["gedcom"]))&&($sourcelist[$pid]["gedfile"]==$gedfile)) {
//			print "Hit on sourcelist for ".$pid."<br />";
			return $sourcelist[$pid]["gedcom"];
		}
		if ((isset($repolist[$pid]["gedcom"])) && ($repolist[$pid]["gedfile"]==$gedfile)) {
//			print "Hit on repolist for ".$pid."<br />";
			return $repolist[$pid]["gedcom"];
		}
		if ((isset($otherlist[$pid]["gedcom"]))&&($otherlist[$pid]["gedfile"]==$gedfile)) {
//			print "Hit on otherlist for ".$pid."<br />";
			return $otherlist[$pid]["gedcom"];
		}
		if ((isset($medialist[$pid]["gedcom"]))&&($medialist[$pid]["gedfile"]==$gedfile)) {
//			print "Hit on medialist for ".$pid."<br />";
			return $medialist[$pid]["gedcom"];
		}
	
	}

	// To minimize queries, we first start to guess what record type we must retrieve
	$tried = "";
	if (substr($pid,0,strlen(GedcomConfig::$GEDCOM_ID_PREFIX)) == GedcomConfig::$GEDCOM_ID_PREFIX) {
		$gedrec = FindPersonRecord($pid, $gedfile, $renew, $nocache);
		$tried = "indi";
	}
	else {
		if (substr($pid,0,strlen(GedcomConfig::$FAM_ID_PREFIX)) == GedcomConfig::$FAM_ID_PREFIX) {
			$gedrec = FindFamilyRecord($pid, $gedfile, $renew);
			$tried = "fam";
		}
		else {
			if (substr($pid,0,strlen(GedcomConfig::$SOURCE_ID_PREFIX)) == GedcomConfig::$SOURCE_ID_PREFIX) {
				$gedrec = FindSourceRecord($pid, $gedfile, $renew);
				$tried = "sour";
			}
			else {
				if (substr($pid,0,strlen(GedcomConfig::$MEDIA_ID_PREFIX)) == GedcomConfig::$MEDIA_ID_PREFIX) {
					$gedrec = FindMediaRecord($pid, $gedfile, $renew);
					$tried = "media";
				}
				else {
					if (substr($pid,0,strlen(GedcomConfig::$NOTE_ID_PREFIX)) == GedcomConfig::$NOTE_ID_PREFIX) {
						$gedrec = FindOtherRecord($pid, $gedfile, $renew);
						$tried = "note";
					}
				}
			}
		}
	}
	
	// Not found, try it on the DB
	if (empty($gedrec) && $tried != "indi") $gedrec = FindPersonRecord($pid, $gedfile, $renew, $nocache);
	if (empty($gedrec) && $tried != "fam") 	$gedrec = FindFamilyRecord($pid, $gedfile, $renew);
	if (empty($gedrec) && $tried != "sour") $gedrec = FindSourceRecord($pid, $gedfile, $renew);
	if (empty($gedrec) && $tried != "media") $gedrec = FindMediaRecord($pid, $gedfile, $renew);
	if (empty($gedrec) && $tried != "note") $gedrec = FindOtherRecord($pid, $gedfile, $renew);
	if (empty($gedrec)) {
	}
	return $gedrec;
}


/**
 * find the gedcom record for a note
 *
 * This function first checks the <var>$otherlist</var> cache to see if the note has already
 * been retrieved from the database.  If it hasn't been retrieved, then query the database and
 * add it to the cache.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#source
 * @param string $oid the unique gedcom xref id of the note record to retrieve
 * @return string the raw gedcom record is returned
 */
function FindOtherRecord($oid, $gedfile="", $renew = false, $type="") {
	global $otherlist;
	
	if (DEBUG) print "Old function called: FindOtherRecord for ".$oid."<br />".pipo;

	if ($oid=="") return false;
	if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;

	if (!$renew && isset($otherlist[$oid]["gedcom"]) && ($otherlist[$oid]["gedfile"] == $gedfile)) return $otherlist[$oid]["gedcom"];

	$sql = "SELECT o_gedrec, o_file FROM ".TBLPREFIX."other WHERE o_id LIKE '".DbLayer::EscapeQuery($oid)."' AND o_file='".($gedfile)."'";
	if (!empty($type)) $sql .= " AND o_type='".$type."'";
	$res = NewQuery($sql);
	if ($res->NumRows()!=0) {
		$row = $res->fetchAssoc();
		$res->FreeResult();
		$otherlist[$oid]["gedcom"] = $row["o_gedrec"];
		$otherlist[$oid]["gedfile"] = $row["o_file"];
		$gedrec = $row["o_gedrec"];
	}
	if (empty($gedrec)) return false;
	else return $gedrec;
}

/**
 * find the gedcom record for a source
 *
 * This function first checks the <var>$sourcelist</var> cache to see if the source has already
 * been retrieved from the database.  If it hasn't been retrieved, then query the database and
 * add it to the cache.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#source
 * @param string $sid the unique gedcom xref id of the source record to retrieve
 * @return string the raw gedcom record is returned
 */
function FindSourceRecord($sid, $gedfile="", $renew = false) {
	global $sourcelist;
	
	if (DEBUG) print "Old function called: FindSourceRecord for ".$sid."<br />".pipo;

	if ($sid=="") return false;
	if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
	
	if (!$renew && isset($sourcelist[$sid]["gedcom"]) && ($sourcelist[$sid]["gedfile"] == $gedfile)) return $sourcelist[$sid]["gedcom"];

	$sql = "SELECT s_gedrec, s_name, s_file FROM ".TBLPREFIX."sources WHERE s_id LIKE '".DbLayer::EscapeQuery($sid)."' AND s_file='".$gedfile."'";
	$res = NewQuery($sql);
	if ($res->NumRows()!=0) {
		$row = $res->fetchAssoc();
		$sourcelist[$sid]["name"] = stripslashes($row["s_name"]);
		$sourcelist[$sid]["gedcom"] = $row["s_gedrec"];
		$sourcelist[$sid]["gedfile"] = $row["s_file"];
		$res->FreeResult();
		return $row["s_gedrec"];
	}
	else {
		return "";
	}
}
// This function checks if a record exists. It also considers pending changes.
function CheckExists($pid, $type="") {

	if (empty($pid)) return false;
	$gedrec = "";
	if (in_array($type, array("INDI", "FAM", "SOUR", "REPO", "OBJE", "NOTE"))) {
		$object = ConstructObject($pid, $type, GedcomConfig::$GEDCOMID);
		return $object->isempty;
	}
	else return false;
}

/**
 * Find a repository record by its ID
 * @param string $rid	the record id
 * @param string $gedfile	the gedcom file id
 */
function FindRepoRecord($rid, $gedfile="") {
	global $repolist;
	
	if (DEBUG) print "Old function called: FindRepoRecord for ".$rid."<br />".pipo;

	if ($rid=="") return false;
	if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
	if (isset($repolist[$rid]["gedcom"]) && ($repolist[$rid]["gedfile"] == $gedfile)) return $repolist[$rid]["gedcom"];
	
	$sql = "SELECT o_id, o_gedrec, o_file FROM ".TBLPREFIX."other WHERE o_type='REPO' AND o_id LIKE '".DbLayer::EscapeQuery($rid)."' AND o_file='".$gedfile."'";
	$res = NewQuery($sql);
	if ($res->NumRows()!=0) {
		$row = $res->fetchAssoc();
		$tt = preg_match("/1 NAME (.*)/", $row["o_gedrec"], $match);
		if ($tt == "0") $name = $row["o_id"]; else $name = $match[1];
		$repolist[$rid]["name"] = stripslashes($name);
		$repolist[$rid]["gedcom"] = $row["o_gedrec"];
		$repolist[$rid]["gedfile"] = $row["o_file"];
		$res->FreeResult();
		return $row["o_gedrec"];
	}
	else {
		return false;
	}
}

/**
 * Find a media record by its ID
 * @param string $rid	the record id
 */
function FindMediaRecord($rid, $gedfile='', $renew = false) {
	global $medialist;
	
	if (DEBUG) print "Old function called: FindMediaRecord for ".$rid."<br />".pipo;
	
	if ($rid=="") return false;
	if (empty($gedfile)) $gedfile = GedcomConfig::$GEDCOMID;
	
	//-- first check for the record in the cache
	if (!$renew && isset($medialist[$rid]["gedcom"]) && ($medialist[$rid]["gedfile"]==$gedfile)) return $medialist[$rid]["gedcom"];

	$sql = "SELECT * FROM ".TBLPREFIX."media WHERE m_media LIKE '".DbLayer::EscapeQuery($rid)."' AND m_file='".$gedfile."'";
	$res = NewQuery($sql);
	if ($res->NumRows()!=0) {
		$row = $res->FetchAssoc();
		$medialist[$rid]["ext"] = $row["m_ext"];
		$medialist[$rid]["title"] = $row["m_titl"];
		$medialist[$rid]["file"] = MediaFS::CheckMediaDepth($row["m_mfile"]);
		$medialist[$rid]["gedcom"] = $row["m_gedrec"];
		$medialist[$rid]["gedfile"] = $row["m_file"];
		$res->FreeResult();
		return $row["m_gedrec"];
	}
	else {
		return "";
	}
}


/**
 * update the is_dead status in the database
 *
 * this function will update the is_dead field in the individuals table with the correct value
 * calculated by the is_dead() function.  To improve import performance, the is_dead status is first
 * set to -1 during import.  The first time the is_dead status is retrieved this function is called to update
 * the database.  This makes the first request for a person slower, but will speed up all future requests.
 * @param string $gid	gedcom xref id of individual to update
 * @param array $indi	the $indi array struction for the individal as used in the <var>$indilist</var>
 * @return int	1 if the person is dead, 0 if living
 */
function UpdateIsDead($gid, $indi) {
	global $indilist;
	$isdead = 0;
	$isdead = PrivacyFunctions::IsDead($indi["gedcom"]);
	if (empty($isdead)) $isdead = 0;
	$sql = "UPDATE ".TBLPREFIX."individuals SET i_isdead=$isdead WHERE i_id LIKE '".DbLayer::EscapeQuery($gid)."' AND i_file='".DbLayer::EscapeQuery($indi["gedfile"])."'";
	$res = NewQuery($sql);
	if (isset($indilist[$gid])) $indilist[$gid]["isdead"] = $isdead;
	if (isset($indilist[$gid."[".$indi["gedfile"]."]"])) $indilist[$gid."[".$indi["gedfile"]."]"]["isdead"] = $isdead;
	return $isdead;
}

/**
 * reset the i_isdead column
 * 
 * This function will reset the i_isdead column with the default -1 so that all is dead status
 * items will be recalculated.
 */
function ResetIsDead() {
	
	$sql = "UPDATE ".TBLPREFIX."individuals SET i_isdead=-1 WHERE i_file='".GedcomConfig::$GEDCOMID."'";
	$res = NewQuery($sql);
	return $res;
}

/**
 * Reset the Isdead status for those individuals, that could have relied on this record for determining their isdead status.
 * For indi's, reset grandparents, parents, partners and children.
 * For fams, reset husband and wife.
 *
 */
function ResetIsDeadLinked($pid, $type="INDI") {
	
	$resets = array();
	if ($type == "FAM") {
		$sql = "SELECT if_pkey FROM ".TBLPREFIX."individual_family WHERE if_role='S' AND if_fkey='".JoinKey($pid, GedcomConfig::$GEDCOMID)."'";
		$res =  NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$resets[] = $row["if_pkey"];
		}
	}
	
	if ($type == "INDI") {
		$parents = array();
	
		// Get the ID's in the surrounding families. Also save the parents ID's for getting the grandparents
		$sql = "SELECT n.if_pkey, n.if_role, m.if_role FROM ".TBLPREFIX."individual_family as m LEFT JOIN ".TBLPREFIX."individual_family as n ON n.if_fkey=m.if_fkey WHERE m.if_pkey='".DbLayer::EscapeQuery(JoinKey($pid, GedcomConfig::$GEDCOMID))."' AND n.if_pkey<>m.if_pkey";
		$res = NewQuery($sql);
		
		while ($row = $res->FetchRow()) {
			$resets[] = $row[0];
			if ($row[1] == "S" && $row[2] == "C") $parents[] = $row[0];
		}
		
		// Get the grandparents
		if (count($parents) > 0) {
			$listfams = "'".implode("', '", $parents)."'";
			$sql = "SELECT n.if_pkey, n.if_role, m.if_role FROM ".TBLPREFIX."individual_family as m LEFT JOIN ".TBLPREFIX."individual_family as n ON n.if_fkey=m.if_fkey WHERE m.if_pkey IN (".$listfams.") AND m.if_role='C' AND n.if_role='S'";
			$res = NewQuery($sql);
			while ($row = $res->FetchRow()) {
				$resets[] = $row[0];
			}
		}
		
		// Now reset the isdead status for these individuals
		if (count($resets) > 0) {
			$sql = "UPDATE ".TBLPREFIX."individuals SET i_isdead='-1' WHERE i_key IN ('".implode("', '", $resets)."')";
			$res = NewQuery($sql);
		}
	}	
}


/**
 * get a list of all the sources
 *
 * returns an array of all of the sources in the database.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#sources
 * @return array the array of sources
 */
function GetSourceList($selection="") {
	global $sourcelist;

	$sourcelist = array();

	$sql = "SELECT * FROM ".TBLPREFIX."sources WHERE s_file='".GedcomConfig::$GEDCOMID."'";
	if (!empty($selection)) $sql .= " AND s_id IN (".$selection.") ";
	$sql .= " ORDER BY s_name";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$source = array();
		$source["name"] = $row["s_name"];
		$source["gedcom"] = $row["s_gedrec"];
		$source["gedfile"] = $row["s_file"];
//		$source["nr"] = 0;
		$sourcelist[$row["s_id"]] = $source;
	}
	$res->FreeResult();

	return $sourcelist;
}

//-- get the repositorylist from the datastore
function GetRepoList($filter = "", $selection="") {
	
	$repolist = array();
	$repoaction = array();
	$actionlist = ActionController::GetActionList();
	foreach ($actionlist as $key => $action) {
		if ($action->status == 0) {
			if (isset($repoaction[$action->repo][0])) $repoaction[$action->repo][0]++;
			else $repoaction[$action->repo][0] = 1;
		}
	}
	foreach ($actionlist as $key => $action) {
		if ($action->status == 1) {
			if (isset($repoaction[$action->repo][1])) $repoaction[$action->repo][1]++;
			else $repoaction[$action->repo][1] = 1;
		}
	}

	$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_file='".GedcomConfig::$GEDCOMID."' AND o_type='REPO'";
	if (!empty($filter)) $sql .= " AND o_gedrec LIKE '%".DbLayer::EscapeQuery($filter)."%'";
	if (!empty($selection)) $sql .= "AND o_id IN (".$selection.") ";
	$resr = NewQuery($sql);
	$ct = $resr->NumRows();
	while($row = $resr->FetchAssoc()){
		$repo = array();
		$tt = preg_match("/1 NAME (.*)/", $row["o_gedrec"], $match);
		if ($tt == "0") $name = $row["o_id"]; else $name = trim($match[1]);
		$repo["id"] = $row["o_id"];
		$repo["gedfile"] = $row["o_file"];
		$repo["type"] = $row["o_type"];
		$repo["gedcom"] = $row["o_gedrec"];
		if (isset($repoaction[$repo["id"]][0])) $repo["actioncnt"][0] = $repoaction[$repo["id"]][0];
		else $repo["actioncnt"][0] = 0;
		if (isset($repoaction[$repo["id"]][1])) $repo["actioncnt"][1] = $repoaction[$repo["id"]][1];
		else $repo["actioncnt"][1] = 0;
		$repolist[$name]= $repo;
	}
	$resr->FreeResult();
	ksort($repolist);
	return $repolist;
}
// This function doesn't seem to be used (11 may 2008)
//-- get the repositorylist from the datastore
function GetRepoIdList() {

	$repo_id_list = array();

	$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_file='".GedcomConfig::$GEDCOMID."' AND o_type='REPO' ORDER BY o_id";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$repo = array();
		$tt = preg_match("/1 NAME (.*)/", $row["o_gedrec"], $match);
		if ($tt>0) $repo["name"] = $match[1];
		else $repo["name"] = "";
		$repo["gedfile"] = $row["o_file"];
		$repo["type"] = $row["o_type"];
		$repo["gedcom"] = $row["o_gedrec"];
		$repo_id_list[$row["o_id"]] = $repo;
	}
	$res->FreeResult();
	return $repo_id_list;
}

/**
 * Get a list of all the repository titles
 *
 * returns an array of all of the repositorytitles in the database.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#repositories
 * @return array the array of repository-titles
 */
function GetRepoAddTitleList() {

	$addrepolist = array();
	$repoaction = array();
	$actionlist = ActionController::GetActionList();
	foreach ($actionlist as $key => $action) {
		if ($action->status == 0) {
			if (isset($repoaction[$action->repo][0])) $repoaction[$action->repo][0]++;
			else $repoaction[$action->repo][0] = 1;
		}
	}
	foreach ($actionlist as $key => $action) {
		if ($action->status == 1) {
			if (isset($repoaction[$action->repo][1])) $repoaction[$action->repo][1]++;
			else $repoaction[$action->repo][1] = 1;
		}
	}

 	$sql = "SELECT o_id, o_file, o_file as o_name, o_type, o_gedrec FROM ".TBLPREFIX."other WHERE o_type='REPO' AND o_file='".GedcomConfig::$GEDCOMID."' and ((o_gedrec LIKE '% _HEB %') || (o_gedrec LIKE '% ROMN %'));";

	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$repo = array();
		$repo["gedcom"] = $row["o_gedrec"];
		$ct = preg_match("/\d ROMN (.*)/", $row["o_gedrec"], $match);
 		if ($ct==0) $ct = preg_match("/\d _HEB (.*)/", $row["o_gedrec"], $match);
 		if ($ct != 0) {
			$repo["name"] = $match[1];
			$repo["id"] = "@".$row["o_id"]."@";
			$repo["gedfile"] = $row["o_file"];
			$repo["type"] = $row["o_type"];
			if (isset($repoaction[$repo["id"]][0])) $repo["actioncnt"][0] = $repoaction[$repo["id"]][0];
			else $repo["actioncnt"][0] = 0;
			if (isset($repoaction[$repo["id"]][1])) $repo["actioncnt"][1] = $repoaction[$repo["id"]][1];
			else $repo["actioncnt"][1] = 0;
			$addrepolist[$match[1]] = $repo;
		}

	}
	$res->FreeResult();
	return $addrepolist;
}

/**
 * Get the indilist from the datastore
 * An array is build up with several elements to the individuals name
 * <code>
 * [I2374] => Array
 *  [gedcom] => Complete person record
 *  [names] => Array
 *   [0] => Array
 *	 [0] => Full name
 *	 [1] => First letter
 *	 [2] => Surname
 *	 [3] => A
 *  [isdead] => 0 if dead, 1 if alive
 *  [gedfile] => ID of Gedcom person belongs to
 *</code>
 *
 * @author	Genmod Development Team
 * @return 	array	Array of all individuals of the active GEDCOM
 */
function GetIndiList($allgeds="", $selection = "", $renew=true) {
	global $indilist, $COMBIKEY;
	global $INDILIST_RETRIEVED;
	
	if ($renew) {
		if ($INDILIST_RETRIEVED && $allgeds=="no") return $indilist;
		$indilist = array();
	}
	
	$sql = "SELECT i_key, i_gedrec, i_isdead, i_id, i_file, n_name, n_surname, n_nick, n_letter, n_fletter, n_type ";
	$sql .= "FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE n_key=i_key ";
	if ($allgeds == "no") {
		$sql .= "AND i_file = ".GedcomConfig::$GEDCOMID." ";
		if (!empty($selection)) $sql .= "AND i_key IN (".$selection.") ";
	}
	else if (is_array($allgeds)) {
		$sql .= "AND (";
		$first = true;
		foreach ($allgeds as $key => $ged) {
			if (!$first) $sql .= " OR ";
			$sql .= "i_file='".$ged."'";
		}
		$sql .= ")";
	}
	else if (!empty($selection)) $sql .= "AND i_key IN (".$selection.") ";
	$sql .= "ORDER BY i_key, n_id ASC";
	$res = NewQuery($sql);
	$ct = $res->NumRows($res->result);
	while($row = $res->FetchAssoc($res->result)){
		if ($COMBIKEY) $key = $row["i_key"];
		else $key = $row["i_id"];
		if (!isset($indilist[$key])) {
			$indi = array();
			$indi["gedcom"] = $row["i_gedrec"];
			$indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
			$indi["isdead"] = $row["i_isdead"];
			$indi["gedfile"] = $row["i_file"];
			$indilist[$key] = $indi;
		}
		else $indilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
	}
	$res->FreeResult();
	if ($allgeds == "no" && $selection = "") $INDILIST_RETRIEVED = true;
	return $indilist;
}



//-- get the famlist from the datastore
function GetFamList($allgeds="no", $selection="", $renew=true, $trans=array()) {
	global $famlist, $indilist;
	global $FAMLIST_RETRIEVED, $COMBIKEY;

	if ($renew) {
		if ($FAMLIST_RETRIEVED && $allgeds=="no" && $selection=="") return $famlist;
		$famlist = array();
	}
	
	$sql = "SELECT * FROM ".TBLPREFIX."families";
	if ($allgeds != "yes") {
		if (!empty($selection)) $sql .= " WHERE f_key IN (".$selection.") ";
		else $sql .= " WHERE f_file='".GedcomConfig::$GEDCOMID."'";
	}
	else if (!empty($selection)) $sql .= " WHERE f_key IN (".$selection.") ";

	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$fam = array();
		$fam["gedcom"] = $row["f_gedrec"];
		$fam["HUSB"] = SplitKey($row["f_husb"], "id");
		$fam["WIFE"] = SplitKey($row["f_wife"], "id");
		$fam["CHIL"] = $row["f_chil"];
		$fam["gedfile"] = $row["f_file"];
		if ($COMBIKEY) $famlist[$row["f_key"]] = $fam;
		else $famlist[$row["f_id"]] = $fam;
	}
	$res->FreeResult();

	if (count($famlist) > 0) {
		$select = array();
		foreach ($famlist as $key => $fam) {
			if (!empty($fam["HUSB"])) $select[JoinKey($fam["HUSB"], $fam["gedfile"])] = true;
			if (!empty($fam["WIFE"])) $select[JoinKey($fam["WIFE"], $fam["gedfile"])] = true;
		}
		if (count($select) > 0) {
			$selection = "";
			foreach ($select as $id => $value) {
				$selection .= "'".$id."', ";
			}
			$selection = substr($selection, 0, strlen($selection)-2);
			GetIndilist($allgeds, $selection, false);
		}
		$oldgedid = GedcomConfig::$GEDCOMID;
		foreach ($famlist as $key => $fam) {
			if (!isset($famlist[$key]["name"])) {
				if ($COMBIKEY) {
					GedcomConfig::$GEDCOMID = SplitKey($key, "gedid");
				}
				if (isset($trans[$key])) {
					// First check the husband. If both have the same selected letter/name, 
					// only the name with the husband first will appear
					if (JoinKey($fam["HUSB"], GedcomConfig::$GEDCOMID) == $trans[$key]["id"]) {
						$hname = NameFunctions::SortableNameFromName($trans[$key]["name"]);
						$wname = GetSortableName($fam["WIFE"]);
					}
					else {
						$hname = NameFunctions::SortableNameFromName($trans[$key]["name"]);
						$wname = GetSortableName($fam["HUSB"]);
					}
				}
				else {
					$hname = GetSortableName($fam["HUSB"]);
					$wname = GetSortableName($fam["WIFE"]);
				}
				$name = "";
				if (!empty($hname)) $name = $hname;
				else $name = "@N.N., @P.N.";
	
				if (!empty($wname)) $name .= " + ".$wname;
				else $name .= " + @N.N., @P.N.";
//print "husb: ".$hname." wife: ".$wname." fam: ".$name."<br />";	
				$famlist[$key]["name"] = $name;
			}
		}
		GedcomConfig::$GEDCOMID = $oldgedid;
	}
	if ($allgeds == "no" && $selection = "") $FAMLIST_RETRIEVED = true;
	return $famlist;
}

//-- get the otherlist from the datastore
/**
 @todo Check if function is still needed
 **/
function GetOtherList() {
	global $otherlist;

	$otherlist = array();

	$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_file='".GedcomConfig::$GEDCOMID."'";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$source = array();
		$source["gedcom"] = $row["o_gedrec"];
		$source["type"] = $row["o_type"];
		$source["gedfile"] = $row["o_file"];
		$otherlist[$row["o_id"]]= $source;
	}
	$res->FreeResult();
	return $otherlist;
}




//-- function to find the gedcom id for the given rin
function FindRinId($rin) {

	$sql = "SELECT i_id FROM ".TBLPREFIX."individuals WHERE i_rin='".$rin."' AND i_file='".GedcomConfig::$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		return $row["i_id"];
	}
	return $rin;
}

//-- return the current size of the given list
//- list options are indilist famlist sourcelist and otherlist
function GetListSize($list) {

	switch($list) {
		case "indilist":
			$sql = "SELECT count(i_id) FROM ".TBLPREFIX."individuals WHERE i_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "famlist":
			$sql = "SELECT count(f_id) FROM ".TBLPREFIX."families WHERE f_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "sourcelist":
			$sql = "SELECT count(s_id) FROM ".TBLPREFIX."sources WHERE s_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "otherlist":
			$sql = "SELECT count(o_id) FROM ".TBLPREFIX."other WHERE o_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "medialist":
			$sql = "SELECT count(m_id) FROM ".TBLPREFIX."media WHERE m_file='".GedcomConfig::$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "notelist":
			$sql = "SELECT count(o_id) FROM ".TBLPREFIX."other WHERE o_file='".GedcomConfig::$GEDCOMID."' AND o_type='NOTE'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
	}
	return 0;
}




/**
 * get next unique id for the given table
 * @param string $table 	the name of the table
 * @param string $field		the field to get the next number for
 * @return int the new id
 */
function GetNextId($table, $field) {

	$newid = 0;
	$sql = "SELECT MAX(".$field.") FROM ".TBLPREFIX.$table;
	$res = NewQuery($sql);
	if ($res) {
		$row = $res->FetchRow();
		$res->FreeResult();
		$newid = $row[0];
	}
	$newid++;
	return $newid;
}


/**
 * Write a Log record to the database
 *
 * The function writes the records that are logged for
 * either the Syetem Log, the Gedcom Log or the Search
 * Log. 
 *
 * @author	Genmod Development Team
 * @param		string	$LogString	Message to be stored
 * @param		string	$type		
 * <pre>Type of record:
 * I = Information
 * W = Warning
 * E = Error</pre>
 * @param		string	$cat		 
 * <pre>Category of log records:
 * S = System Log
 * G = Gedcom Log
 * F = Search Log</pre>
 * @param		string	$ged		Used with Gedcom Log and Search Log
 *							Gedcom the Log record applies to
 */
function WriteToLog($LogString, $type="I", $cat="S", $gedid="", $chkconn = true) {
	global $gm_user;
	global $DBCONN;
	
	
	// -- Remove the " from the logstring, as this disturbs the export
	$LogString = str_replace("\"", "'", $LogString);
	
	// If Type is error, set to new for warning on admin pages
	if ($type == "E") $new = "1";
	else $new = "0";
	
	if ($chkconn && (!is_object($DBCONN) || (isset($DBCONN->connected) && !$DBCONN->connected))) {
		if ($cat == "S") {
			$emlog = INDEX_DIRECTORY."emergency_syslog.txt";
			$string = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($gm_user->username)."', '".addslashes($LogString)."', '', '".$new."')\r\n";
			$fp = fopen($emlog, "ab");
			flock($fp, 2);
			fwrite($fp, $string);
			flock($fp, 3);
			fclose($fp);
			header("Content-Type: text/html; charset=UTF-8");
			print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
			print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\t<head>\n\t\t";
			print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF8\" />\n\t\t";
			print "<title>Genmod</title>";
			print "</head>\n\t<body>";
			print "<div style=\"color: #FF0000; border: 1px solid #000000;\">";
				print "Fatal error encountered: Database cannot be reached.<br /><br />Error information: ".$LogString."<br />Genmod is terminating.";
			print "\n\t</div></body>\n</html>";
			exit;
		}
	}
	
	if ($cat == "S") {
		$sql = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."',".time().", '".$_SERVER['REMOTE_ADDR']."', '".addslashes($gm_user->username)."', '".addslashes($LogString)."', '', '".$new."')";
		$res = NewQuery($sql);
		return;
	}
	if ($cat == "G") {
		$sql = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($gm_user->username)."', '".addslashes($LogString)."', '".$gedid."', '".$new."')";
		$res = NewQuery($sql);
		return;
	}
	if ($cat == "F") {
		if (!isset($gedid)) return;
		if (count($gedid) == 0) return;
		foreach($gedid as $indexval => $value) {
			$sql = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($gm_user->username)."', '".addslashes($LogString)."', '".$value."', '".$new."')";
			$res = NewQuery($sql);
		}
		return;
	}
}
	
/**
 * Read the GEDCOMS array from the database
 *
 * The function reads the GEDCOMS array from the database,
 * including DEFAULT_GEDCOM. If there is no indication which
 * GEDCOM is default, the GEDCOM with the lowest ID becomes 
 * the default GEDCOM.
 *
 * @author	Genmod Development Team
 */
function ReadGedcoms() {
	global $GEDCOMS, $DEFAULT_GEDCOM, $DEFAULT_GEDCOMID;
	$GEDCOMS=array();
	$DEFAULT_GEDCOM = "";
	$DEFAULT_GEDCOMID = "";
	$sql = "SELECT * FROM ".TBLPREFIX."gedcoms ORDER BY g_title";
	$res = NewQuery($sql);
	if ($res) {
		$ct = $res->NumRows();
		$i = "0";
		if ($ct) {
			while($row = $res->FetchAssoc()){
				$g = array();
				$g["gedcom"] = $row["g_gedcom"];
				$g["title"] = $row["g_title"];
				$g["path"] = str_replace("[INDEX_DIRECTORY]/", INDEX_DIRECTORY, $row["g_path"]);
				$g["id"] = $row["g_file"];
				$g["commonsurnames"] = $row["g_commonsurnames"];
				if ($row["g_isdefault"] == "Y") {
					$DEFAULT_GEDCOM = $row["g_gedcom"];
					$DEFAULT_GEDCOMID = $row["g_file"];
				}
				$GEDCOMS[$row["g_file"]] = $g;
				if ($i == "0") {
					$DEFAULT_GEDCOM = $row["g_gedcom"];
					$DEFAULT_GEDCOMID = $row["g_file"];
				}
				$i++;
			}
			$res->FreeResult();
		}
	}
}

//
//function GetNewFams($pid) {
//	global GedcomConfig::$GEDCOMID;
//	
//	$newfams = array();
//
//	if (!empty($pid) && GetChangeData(true, $pid, true, "","")) {
//		$rec = GetChangeData(false, $pid, true, "gedlines","");
//		$gedrec = $rec[GedcomConfig::$GEDCOMID][$pid];
//		$ct = preg_match_all("/1\s+FAMS\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
//		if ($ct>0) {
//			$oldfams = FindSfamilyIds($pid);
//			$i=0;
//			for ($j = 0; $j < $ct; $j++) {
//				$found = false;
//				foreach ($oldfams as $key => $oldfam) {
//					if ($oldfam["famid"] == $fmatch[$j][1]) {
//						$found = true;
//						break;
//					}
//				}
//				if (!$found) $newfams[]=$fmatch[$j][1];
//			}
//		}
//	}
//	return $newfams;
//}
//	

/**
 * Retrieves all unlinked individuals
 *
 * This function retrieves all unlinked individuals 
 * and families. The result is returned as an array.
 *
 * @author	Genmod Development Team
 * @param		<type>	<varname>		<description>
 * @return 	<type>	<description>
 */
function GetUnlinked() {
	global $TOTAL_COUNT, $indilist;
	
	$uindilist = array();
	
	$sql = "SELECT i_id, i_gedrec, i_file, i_isdead, n_name, n_letter, n_fletter, n_surname, n_nick, n_type FROM ".TBLPREFIX."individuals LEFT JOIN ".TBLPREFIX."names ON i_key=n_key LEFT JOIN ".TBLPREFIX."individual_family ON i_key=if_pkey WHERE if_pkey IS NULL AND i_file='".GedcomConfig::$GEDCOMID."' ORDER BY i_key, n_id";

	$res = NewQuery($sql);
	if ($res) {
		$TOTAL_COUNT++;
		while($row = $res->FetchAssoc()){
			if (!isset($indilist[$row["i_id"]])) {
				$indi = array();
				$indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
				$indi["isdead"] = $row["i_isdead"];
				$indi["gedcom"] = $row["i_gedrec"];
				$indi["gedfile"] = $row["i_file"];
				$indilist[$row["i_id"]] = $indi;
			}
			else {
				$indilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
			}
			$uindilist[$row["i_id"]] = $indilist[$row["i_id"]];
		}
	}
	return $uindilist;
}

/* This function returns the state of a given language variable
** -1 = undetermined
**  0 = existing and translated
**  1 = existing and not translated
**  2 = not existing
*/
function GetLangvarStatus($var, $language, $type="help") {

	if ($type == "help") {
		$sql = "SELECT lg_english, lg_".$language." FROM ".TBLPREFIX."language_help WHERE lg_string='".$var."'";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return 2;
		$lang = $res->FetchRow();
		if (empty($lang[1])) return 1;
		else return 0;
	}
	return -1;
}

function GetLangVarString($var, $value, $type) {

	// This gets the langvar in the gedcom's language
	if ($type == "gedcom" || $type = "gedcomid") {
		if ($type = "gedcom") $value = get_id_from_gedcom($value);
		$language = GedcomConfig::GetGedcomLanguage($value);
		if (!$language) return false;
		$type = "lang";
	}
	else $language = $value;
	// This gets the langvar in the parameter language
	if ($type == "lang") {
		$sql = "SELECT lg_english, lg_".$language." FROM ".TBLPREFIX."language WHERE lg_string='".$var."'";
		$res = NewQuery($sql);
		$lang = $res->FetchRow();
		if (!empty($lang[1])) return $lang[1];
		else return $lang[0];
	}
}

function GetNoteLinks($oid, $type="", $applypriv=true) {

	if (empty($oid)) return false;
	
	$links = array();
	$sql = 	"SELECT DISTINCT om_gid, om_type FROM ".TBLPREFIX."other_mapping WHERE om_oid='".$oid."' AND om_file='".GedcomConfig::$GEDCOMID."'";
	if (!empty($type)) $sql .= " AND om_type='".$type."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		if (!$applypriv || PrivacyFunctions::showFact("NOTE", $row["om_gid"], $row["om_type"])) {
			if (empty($type)) $links[] = array($row["om_gid"], $row["om_type"]);
			else $links[] = $row["om_gid"];
		}
	}
	return $links;
}
	
function GetSourceLinks($pid, $type="", $applypriv=true, $getfamindi=true) {
	global $alllinks, $indilist, $famlist, $LINK_PRIVACY;
	
	if (empty($pid)) return false;

	if (!isset($alllinks)) $alllinks = array();
	if ($type=="" && isset($alllinks[$pid])) {
		if ($applypriv && isset($alllinks[$pid][1])) return $alllinks[$pid][1];
		else if (isset($alllinks[$pid][0])) return $alllinks[$pid][0];
	}
	$links = array();
	$indisel = array();
	$famsel = array();	
	$sql = "SELECT DISTINCT sm_gid, sm_type FROM ".TBLPREFIX."source_mapping WHERE sm_sid='".$pid."' AND sm_file='".GedcomConfig::$GEDCOMID."'";
	if (!empty($type)) $sql .= " AND sm_type='".$type."'";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()){
		$added = false;
		if (!$applypriv) {
			$links[0][] = $row[0];
			$added = true;
		}
		else {
			if (PrivacyFunctions::showFact("SOUR", $row[0], $type)) {
				$links[1][] = $row[0];
				$added = true;
			}
		}
		if ($LINK_PRIVACY && $added && $getfamindi) {
			if ($row[1] == "INDI") {
				if (!isset($indilist[$row[0]])) $indisel[] = $row[0];
			}
			else {
				if ($row[1] == "FAM") {
					if (!isset($famlist[$row[0]])) $famsel[] = $row[0];
				}
			}
		}
	}
	if ($LINK_PRIVACY && $getfamindi) {
		if (count($indisel) > 0) {
			$indisel = array_flip(array_flip($indisel));
			$indisel = "'".implode("[".GedcomConfig::$GEDCOMID."]','", $indisel)."[".GedcomConfig::$GEDCOMID."]'";
			GetIndiList("no", $indisel, false);
		}
		if (count($famsel) > 0) {
			$famsel = array_flip(array_flip($famsel));
			$famsel = "'".implode ("[".GedcomConfig::$GEDCOMID."]','", $famsel)."[".GedcomConfig::$GEDCOMID."]'";
			GetFamList("no", $famsel, false);
		}
	}
	$alllinks[$pid] = $links;
	if ($applypriv) {
		if (isset($links[1])) return $links[1];
		else return array();
	}
	else {
		if (isset($links[0])) return $links[0];
		else return array();
	}
}

// This creates the full cache for GetSourceLinks
// It also creates the gedcom caches for indis and fams at once, if link privacy is enabled
function GetAllSourceLinks($applypriv=true) {
	global $alllinks, $LINK_PRIVACY;
	
	if (!isset($alllinks)) $alllinks = array();
	if ($LINK_PRIVACY) {
		$famsel = array();
		$indisel = array();	
	}
	$sql = "SELECT sm_sid, sm_gid, sm_type FROM ".TBLPREFIX."source_mapping WHERE sm_file='".GedcomConfig::$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$alllinks[$row["sm_sid"]][0][] = $row["sm_gid"];
		if (PrivacyFunctions::showFact("SOUR", $row["sm_sid"], $row["sm_type"])) {
			$alllinks[$row["sm_sid"]][1][] = $row["sm_gid"];
		}
		if ($LINK_PRIVACY) {
			if ($row["sm_type"] == "INDI") $indisel[] = $row["sm_gid"];
			else if ($row["sm_type"] == "FAM") $famsel[] = $row["sm_gid"];
		}
	}
	if ($LINK_PRIVACY) {
		if (count($indisel) > 0) {
			array_flip(array_flip($indisel));
			$indisel = "'".implode("[".GedcomConfig::$GEDCOMID."]','", $indisel)."[".GedcomConfig::$GEDCOMID."]'";
			GetIndiList("no", $indisel, false);
		}
		if (count($famsel) > 0) {
			array_flip(array_flip($famsel));
			$famsel = "'".implode ("[".GedcomConfig::$GEDCOMID."]','", $famsel)."[".GedcomConfig::$GEDCOMID."]'";
			GetFamList("no", $famsel, false);
		}
	}
}

function GetMediaLinks($pid, $type="", $applypriv=true) {
	global $allmlinks, $indilist, $famlist, $LINK_PRIVACY;
	
	if (empty($pid)) return false;
	
	if (!isset($allmlinks)) $allmlinks = array();
	if (isset($allmlinks[$pid][$type][$applypriv])) return $allmlinks[$pid][$type][$applypriv];

	$links = array();	
	$indisel = array();
	$famsel = array();	
	$sql = "SELECT DISTINCT mm_gid, mm_type FROM ".TBLPREFIX."media_mapping WHERE mm_media='".$pid."'";
	if (!empty($type)) $sql .= " AND mm_type='".$type."'";
	$sql .= " AND mm_file='".GedcomConfig::$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()){
		$added = false;
		if (!$applypriv) {
			$links[] = $row[0];
			$added = true;
		}
		else {
			if (PrivacyFunctions::showFact("OBJE", $row[0], $type)) {
				$links[] = $row[0];
				$added = true;
			}
		}
		if ($LINK_PRIVACY && $added) {
			if ($row[1] == "INDI") {
				if (!isset($indilist[$row[0]])) $indisel[] = $row[0];
			}
			else {
				if ($row[1] == "FAM") {
					if (!isset($famlist[$row[0]])) $famsel[] = $row[0];
				}
			}
		}
	}
	if ($LINK_PRIVACY) {
		$indisel = "'".implode("[".GedcomConfig::$GEDCOMID."]','", $indisel)."[".GedcomConfig::$GEDCOMID."]'";
		$famsel = "'".implode ("[".GedcomConfig::$GEDCOMID."]','", $famsel)."[".GedcomConfig::$GEDCOMID."]'";
		GetIndiList("no", $indisel, false);
		GetFamList("no", $famsel);
	}
	$allmlinks[$pid][$type][$applypriv] = $links;
	return $links;
}

function GetLastChangeDate($type, $pid, $gedid, $head=false) {
	
	$object = ConstructObject($pid, $type, $gedid);

	// pid does not exist
	if ($object->isempty) return false;
	
	// return the date/time from the CHAN record
	if ($object->lastchanged != "") return $object->lastchanged;
	
	// if not found, take the header record of the gedcom
	if ($head) $object =& Header::GetInstance("HEAD", "", $gedid); 
	// WriteToLog("Retrieved date and time from HEAD record", "I", "S");
	return $object->lastchanged;
}


?>