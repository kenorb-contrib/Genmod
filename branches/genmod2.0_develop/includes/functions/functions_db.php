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
function db_cleanup($item) {
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
	global $COMBIKEY;
	global $GEDCOMID, $famlist, $COMBIKEY;
	
	
	if (DEBUG) print "Old function called: FindFamilyRecord for ".$famid."<br />".pipo;
	
	if (empty($famid)) return false;
	if (empty($gedfile)) $gedfile = $GEDCOMID;
	
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
	global $GEDCOMID;
	global $indilist;
	
	if (DEBUG) print "Old function called: FindPersonRecord for ".$pid."<br />".pipo;
	
	if (empty($pid)) return false;
	if (empty($gedfile)) $gedfile = $GEDCOMID;
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
		$indi["names"] = GetIndiNames($row["i_gedrec"]);
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
	global $GEDCOMID, $indilist, $famlist, $sourcelist, $otherlist, $repolist, $medialist;
	
 	if (DEBUG) print "hit on findgecomrecord for: ".$pid."<br />".pipo;
	if (empty($pid)) return false;
	if (empty($gedfile)) $gedfile = $GEDCOMID;
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
	global $GEDCOMID, $otherlist;
	
	if (DEBUG) print "Old function called: FindOtherRecord for ".$oid."<br />".pipo;

	if ($oid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOMID;

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
	global $GEDCOMID, $sourcelist;
	
	if (DEBUG) print "Old function called: FindSourceRecord for ".$sid."<br />".pipo;

	if ($sid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOMID;
	
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
	global $GEDCOMID;

	if (empty($pid)) return false;
	$gedrec = "";
	if (in_array($type, array("INDI", "FAM", "SOUR", "REPO", "OBJE", "NOTE"))) {
		if ($type == "INDI") $gedrec = FindPersonRecord($pid);
		else if ($type == "FAM") $gedrec = FindFamilyRecord($pid);
		else if ($type == "SOUR") $gedrec = FindSourceRecord($pid);
		else if ($type == "OBJE") $gedrec = FindMediaRecord($pid);
		else if ($type == "REPO" || $type == "NOTE") $gedrec = $gedrec = FindOtherRecord($pid, "", false, $type);
	}
	else $gedrec = FindGedcomRecord($pid);
	
	if (GetChangeData(true, $pid, false, "", "")) {
		$rec = GetChangeData(false, $pid, false, "gedlines", "");
		$gedrec = $rec[$GEDCOMID][$pid];
		// There are changes. So the record may be new or changed (not empty) or deleted (empty)
		if (empty($gedrec)) return false;
		else return true;
	}
	// No changes present. So, if gedrec is empty, the pid clearly doesn't exist.
	if (empty($gedrec)) return false;
	else return true;
}

/**
 * Find a repository record by its ID
 * @param string $rid	the record id
 * @param string $gedfile	the gedcom file id
 */
function FindRepoRecord($rid, $gedfile="") {
	global $GEDCOMID, $repolist;
	
	if (DEBUG) print "Old function called: FindRepoRecord for ".$rid."<br />".pipo;

	if ($rid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOMID;
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
	global $medialist, $GEDCOMID;
	
	if (DEBUG) print "Old function called: FindMediaRecord for ".$rid."<br />".pipo;
	
	if ($rid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOMID;
	
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


function FindSubmitter($gedid) {
	global $GEDCOMID;
	
	if (!isset($gedid)) $gedid = $GEDCOMID;
	$sql = "SELECT o_id FROM ".TBLPREFIX."other WHERE o_file='".$gedid."' AND o_type='SUBM'";
	$res = NewQuery($sql);
	if (!$res || $res->NumRows() == 0) {
		// If there is a new unapproved submitter record, is has the default pid
		if (GetChangeData(true, "SUB1", false, "", "")) {
			$rec = GetChangeData(false, "SUB1", false, "gedlines", "");
			if (isset($rec[$gedid]["SUB1"])) return "SUB1";
			else return "";
		}
	}
	$row = $res->FetchAssoc();
	$res->FreeResult();
	return $row["o_id"];
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
	global $GEDCOMID;
	
	$sql = "UPDATE ".TBLPREFIX."individuals SET i_isdead=-1 WHERE i_file='".$GEDCOMID."'";
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
	global $GEDCOMID;
	
	$resets = array();
	if ($type == "FAM") {
		$sql = "SELECT if_pkey FROM ".TBLPREFIX."individual_family WHERE if_role='S' AND if_fkey='".JoinKey($pid, $GEDCOMID)."'";
		$res =  NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$resets[] = $row["if_pkey"];
		}
	}
	
	if ($type == "INDI") {
		$parents = array();
	
		// Get the ID's in the surrounding families. Also save the parents ID's for getting the grandparents
		$sql = "SELECT n.if_pkey, n.if_role, m.if_role FROM ".TBLPREFIX."individual_family as m LEFT JOIN ".TBLPREFIX."individual_family as n ON n.if_fkey=m.if_fkey WHERE m.if_pkey='".DbLayer::EscapeQuery(JoinKey($pid, $GEDCOMID))."' AND n.if_pkey<>m.if_pkey";
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
 * Add a new calculated name to the individual names table
 *
 * this function will add a new name record for the given individual, this function is called from the
 * importgedcom.php script stage 5
 * @param string $gid	gedcom xref id of individual to update
 * @param string $newname	the new calculated name to add
 * @param string $surname	the surname for this name
 * @param string $letter	the letter for this name
 */
function AddNewName($gid, $newname, $letter, $surname, $indirec) {
	global $indilist, $FILE, $GEDCOMID;

	$indilist[$gid]["names"][] = array($newname, $letter, $surname, 'C');
	$indilist[$gid]["gedcom"] = $indirec;
	$kgid = JoinKey($gid, $GEDCOMID);
	$sql = "INSERT INTO ".TBLPREFIX."names VALUES('0', '".DbLayer::EscapeQuery($kgid)."','".DbLayer::EscapeQuery($gid)."','".$GEDCOMID."','".DbLayer::EscapeQuery($newname)."','".DbLayer::EscapeQuery($letter)."','".DbLayer::EscapeQuery($surname)."','C')";
	$res = NewQuery($sql);
	
	$soundex_codes = GetSoundexStrings($indilist[$gid]["names"], false, $indirec);
	$sql = "DELETE FROM ".TBLPREFIX."soundex WHERE s_gid='".$kgid."'";
	$sql = "INSERT INTO ".TBLPREFIX."soundex VALUES ";
	foreach ($soundex_codes as $type => $ncodes) {
		foreach ($ncodes as $nametype => $tcodes) {
			foreach ($tcodes as $key => $code) {
				$sql .= "(NULL, '".$kgid."', '".$GEDCOMID."', '".$type."', '".$nametype."', '".$code."'), ";
			}
		}
	}
	$sql = substr($sql, 0, strlen($sql)-2);
	$res = NewQuery($sql);
	if ($res) $res->FreeResult();
	$sql = "UPDATE ".TBLPREFIX."individuals SET i_gedrec='".DbLayer::EscapeQuery($indirec)."' WHERE i_id='".DbLayer::EscapeQuery($gid)."' AND i_file='".get_id_from_gedcom($FILE)."'";
	$res = NewQuery($sql);
}

/*
* Return the media ID based on the FILE and TITLE value, depending on the gedcom setting
* If check for existing media is disabled, return false.
*/
function CheckDoubleMedia($file, $title, $gedid) {
	
	if (GedcomConfig::$MERGE_DOUBLE_MEDIA == 0) return false;
	
	$sql = "SELECT m_media FROM ".TBLPREFIX."media WHERE m_file='".$gedid."' AND m_mfile LIKE '".DbLayer::EscapeQuery($file)."'";
	if (GedcomConfig::$MERGE_DOUBLE_MEDIA == "2") $sql .= " AND m_titl LIKE '".DbLayer::EscapeQuery($title)."'";
	$res = NewQuery($sql);
	if ($res->NumRows() == 0) return false;
	else {
		$row = $res->FetchAssoc();
		return $row["m_media"];
	}
}

/**
 * get a list of all the source titles
 *
 * returns an array of all of the sourcetitles in the database.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#sources
 * @return array the array of source-titles
 */
function GetSourceAddTitleList() {
	global $sourcelist, $GEDCOMID;

	$sourcelist = array();

 	$sql = "SELECT s_id, s_file, s_file as s_name, s_gedrec FROM ".TBLPREFIX."sources WHERE s_file='".$GEDCOMID."' and ((s_gedrec LIKE '% _HEB %') || (s_gedrec LIKE '% ROMN %'));";

	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$source = array();
		$row = db_cleanup($row);
		$ct = preg_match("/\d ROMN (.*)/", $row["s_gedrec"], $match);
 		if ($ct==0) $ct = preg_match("/\d _HEB (.*)/", $row["s_gedrec"], $match);
		$source["name"] = $match[1];
		$source["gedcom"] = $row["s_gedrec"];
		$source["gedfile"] = $row["s_file"];
		$sourcelist[$row["s_id"]] = $source;
	}
	$res->FreeResult();

	return $sourcelist;
}

/**
 * get a list of all the sources
 *
 * returns an array of all of the sources in the database.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#sources
 * @return array the array of sources
 */
function GetSourceList($selection="") {
	global $sourcelist, $GEDCOMID;

	$sourcelist = array();

	$sql = "SELECT * FROM ".TBLPREFIX."sources WHERE s_file='".$GEDCOMID."'";
	if (!empty($selection)) $sql .= " AND s_id IN (".$selection.") ";
	$sql .= " ORDER BY s_name";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$source = array();
		$source["name"] = $row["s_name"];
		$source["gedcom"] = $row["s_gedrec"];
		$row = db_cleanup($row);
		$source["gedfile"] = $row["s_file"];
//		$source["nr"] = 0;
		$sourcelist[$row["s_id"]] = $source;
	}
	$res->FreeResult();

	return $sourcelist;
}

//-- get the repositorylist from the datastore
function GetRepoList($filter = "", $selection="") {
	global $GEDCOMID;
	
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

	$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_file='".$GEDCOMID."' AND o_type='REPO'";
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
	global $GEDCOMID;

	$repo_id_list = array();

	$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_file='".$GEDCOMID."' AND o_type='REPO' ORDER BY o_id";
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
		$row = db_cleanup($row);
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
	global $GEDCOMID;

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

 	$sql = "SELECT o_id, o_file, o_file as o_name, o_type, o_gedrec FROM ".TBLPREFIX."other WHERE o_type='REPO' AND o_file='".$GEDCOMID."' and ((o_gedrec LIKE '% _HEB %') || (o_gedrec LIKE '% ROMN %'));";

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
			$row = db_cleanup($row);
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
	global $indilist, $GEDCOMID, $COMBIKEY;
	global $INDILIST_RETRIEVED;
	
	if ($renew) {
		if ($INDILIST_RETRIEVED && $allgeds=="no") return $indilist;
		$indilist = array();
	}
	
	$sql = "SELECT i_key, i_gedrec, i_isdead, i_id, i_file, n_name, n_surname, n_letter, n_type ";
	$sql .= "FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE n_key=i_key ";
	if ($allgeds == "no") {
		$sql .= "AND i_file = ".$GEDCOMID." ";
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
//	$sql .= "ORDER BY i_letter, i_surname ASC";
	$res = NewQuery($sql);
	$ct = $res->NumRows($res->result);
	while($row = $res->FetchAssoc($res->result)){
		if ($COMBIKEY) $key = $row["i_key"];
		else $key = $row["i_id"];
		if (!isset($indilist[$key])) {
			$indi = array();
			$indi["gedcom"] = $row["i_gedrec"];
			$row = db_cleanup($row);
			$indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$indi["isdead"] = $row["i_isdead"];
			$indi["gedfile"] = $row["i_file"];
			$indilist[$key] = $indi;
		}
		else $indilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
	}
	$res->FreeResult();
	if ($allgeds == "no" && $selection = "") $INDILIST_RETRIEVED = true;
	return $indilist;
}


//-- get the assolist from the datastore
function GetAssoList($type = "all", $id="") {
	global $assolist, $GEDCOMID;
	global $ASSOLIST_RETRIEVED, $COMBIKEY, $ftminwlen, $ftmaxwlen;

	if ($ASSOLIST_RETRIEVED) return $assolist;
	$type = str2lower($type);
	$assolist = array();
	$resnvalues = array(""=>"", "n"=>"none", "l"=>"locked", "p"=>"privacy", "c"=>"confidential");
	$oldgedid = $GEDCOMID;
	if (($type == "all") || ($type == "fam")) {
		$sql = "SELECT f_key, f_id, f_husb, f_wife, f_file, f_gedrec, as_pid, as_fact, as_rela, as_resn FROM ".TBLPREFIX."asso, ".TBLPREFIX."families WHERE f_key=as_of AND as_type='F'"; 
		if (!empty($id)) $sql .= " AND as_pid LIKE '".JoinKey($id, $GEDCOMID)."'";
		$sql .= "ORDER BY as_id";
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		while($row = $res->FetchAssoc()){
			$asso = array();
			$asso["type"] = "FAM";
			$asso["pid2"] = $row["as_pid"];
			$asso["gedcom"] = $row["f_gedrec"];
			$asso["gedfile"] = $row["f_file"];
			$asso["fact"] = $row["as_fact"];
			$asso["resn"] = $resnvalues[$row["as_resn"]];
			$asso["role"] = $row["as_rela"];
			// Get the family names
			$GEDCOMID = $row["f_file"];
			$assolist[$row["f_key"]][] = $asso;
		}
		$res->FreeResult();
	}

	if (($type == "all") || ($type == "indi")) {
		
		$sql = "SELECT i_key, i_id, i_file, i_gedrec, as_pid, as_fact, as_rela, as_resn FROM ".TBLPREFIX."asso, ".TBLPREFIX."individuals WHERE i_key=as_of AND as_type='I'";	
		if (!empty($id)) $sql .= " AND as_pid LIKE '".JoinKey($id, $GEDCOMID)."'";
		$sql .= "ORDER BY as_id";
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		while($row = $res->FetchAssoc()) {
			$asso = array();
			$asso["type"] = "indi";
			$asso["pid2"] = $row["as_pid"];
			$asso["gedcom"] = $row["i_gedrec"];
			$asso["gedfile"] = $row["i_file"];
			$asso["name"] = GetIndiNames($row["i_gedrec"]);
			$asso["fact"] = $row["as_fact"];
			$asso["resn"] = $resnvalues[$row["as_resn"]];
			$asso["role"] = $row["as_rela"];
			$assolist[$row["i_key"]][] = $asso;
		}
		$res->FreeResult();
	}
	
	$GEDCOMID = $oldgedid;
	$ASSOLIST_RETRIEVED = true;
	return $assolist;
}

//-- get the famlist from the datastore
function GetFamList($allgeds="no", $selection="", $renew=true, $trans=array()) {
	global $famlist, $GEDCOMID, $indilist;
	global $FAMLIST_RETRIEVED, $COMBIKEY;

	if ($renew) {
		if ($FAMLIST_RETRIEVED && $allgeds=="no" && $selection=="") return $famlist;
		$famlist = array();
	}
	
	$sql = "SELECT * FROM ".TBLPREFIX."families";
	if ($allgeds != "yes") {
		if (!empty($selection)) $sql .= " WHERE f_key IN (".$selection.") ";
		else $sql .= " WHERE f_file='".$GEDCOMID."'";
	}
	else if (!empty($selection)) $sql .= " WHERE f_key IN (".$selection.") ";

	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$fam = array();
		$fam["gedcom"] = $row["f_gedrec"];
		$row = db_cleanup($row);
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
		$oldgedid = $GEDCOMID;
		foreach ($famlist as $key => $fam) {
			if (!isset($famlist[$key]["name"])) {
				if ($COMBIKEY) {
					$GEDCOMID = SplitKey($key, "gedid");
				}
				if (isset($trans[$key])) {
					// First check the husband. If both have the same selected letter/name, 
					// only the name with the husband first will appear
					if (JoinKey($fam["HUSB"], $GEDCOMID) == $trans[$key]["id"]) {
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
		$GEDCOMID = $oldgedid;
	}
	if ($allgeds == "no" && $selection = "") $FAMLIST_RETRIEVED = true;
	return $famlist;
}

//-- get the otherlist from the datastore
/**
 @todo Check if function is still needed
 **/
function GetOtherList() {
	global $otherlist, $GEDCOMID;

	$otherlist = array();

	$sql = "SELECT * FROM ".TBLPREFIX."other WHERE o_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$source = array();
		$source["gedcom"] = $row["o_gedrec"];
		$row = db_cleanup($row);
		$source["type"] = $row["o_type"];
		$source["gedfile"] = $row["o_file"];
		$otherlist[$row["o_id"]]= $source;
	}
	$res->FreeResult();
	return $otherlist;
}
	//-- find all of the places
	function FindPlaceList($place) {
		global $placelist, $GEDCOMID;
		
		$sql = "SELECT p_id, p_place, p_parent_id  FROM ".TBLPREFIX."places WHERE p_file='".$GEDCOMID."' ORDER BY p_parent_id, p_id";
		$res = NewQuery($sql);
		while($row = $res->fetchRow()) {
			if ($row[2]==0) $placelist[$row[0]] = $row[1];
			else {
				$placelist[$row[0]] = $placelist[$row[2]].", ".$row[1];
			}
		}
		if (!empty($place)) {
			$found = array();
			foreach($placelist as $indexval => $pplace) {
				if (preg_match("/$place/i", $pplace)>0) {
					$upperplace = Str2Upper($pplace);
					if (!isset($found[$upperplace])) {
						$found[$upperplace] = $pplace;
					}
				}
			}
			$placelist = array_values($found);
		}
	}

//-- get the first character in the list
function GetFamAlpha($allgeds="no") {
	global $LANGUAGE, $famalpha, $GEDCOMID;

	$famalpha = array();

	$sql = "SELECT DISTINCT n_letter as alpha FROM ".TBLPREFIX."names, ".TBLPREFIX."individual_family WHERE if_pkey=n_key and if_role='S'";	
	if ($allgeds == "no") $sql .= " AND n_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	
	$hungarianex = array("DZS", "CS", "DZ" , "GY", "LY", "NY", "SZ", "TY", "ZS");
	$danishex = array("OE", "AE", "AA");
	while($row = $res->FetchAssoc()){
		$letter = $row["alpha"];
		if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian"){
			if (in_array(strtoupper($letter), $danishex)) {
				if (strtoupper($letter) == "OE") $letter = "Ø";
				else if (strtoupper($letter) == "AE") $letter = "Æ";
				else if (strtoupper($letter) == "AA") $letter = "Å";
			}
		}
		if (strlen($letter) > 1){
			if (ord($letter) < 92){
				if ($LANGUAGE != "hungarian" && in_array($letter, $hungarianex)) $letter = substr($letter, 0, 1);
				if (($LANGUAGE != "danish" || $LANGUAGE != "norwegian") && in_array($letter, $danishex)) $letter = substr($letter, 0, 1);
			}
		}

		if (!isset($famalpha[$letter])) $famalpha[$letter]=$letter;
	}
	$res->FreeResult();
	$sql = "SELECT f_id FROM ".TBLPREFIX."families WHERE (f_husb='' || f_wife='')";
	if ($allgeds == "no") $sql .= " AND f_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	if ($res->NumRows()>0) {
		$famalpha["@"] = "@";
	}
	$res->FreeResult();
	return $famalpha;
}

/**
 * Get Individuals Starting with a letter
 *
 * This function finds all of the individuals who start with the given letter
 *
 * @param string $letter	The letter to search on
 * @return array	$indilist array
 */
function GetAlphaIndis($letter, $allgeds="no") {
	global $LANGUAGE, $indilist, $surname;
	global $GEDCOMID, $COMBIKEY;

	$tindilist = array();
	$search_letter = "";
	
	// NOTE: Determine what letter to search for depending on the active language
	if ($LANGUAGE == "hungarian"){
		if (strlen($letter) >= 2) $search_letter = "'".DbLayer::EscapeQuery($letter)."' ";
		else {
			if ($letter == "C") $text = "CS";
			else if ($letter == "D") $text = "DZ";
			else if ($letter == "G") $text = "GY";
			else if ($letter == "L") $text = "LY";
			else if ($letter == "N") $text = "NY";
			else if ($letter == "S") $text = "SZ";
			else if ($letter == "T") $text = "TY";
			else if ($letter == "Z") $text = "ZS";
			if (isset($text)) $search_letter = "(n_letter = '".DbLayer::EscapeQuery($letter)."' AND n_letter != '".DbLayer::EscapeQuery($text)."') ";
			else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
		}
	}
	else if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
		if ($letter == "Ø") $text = "OE";
		else if ($letter == "Æ") $text = "AE";
		else if ($letter == "Å") $text = "AA";
		if (isset($text)) $search_letter = "(n_letter = '".DbLayer::EscapeQuery($letter)."' OR n_letter = '".DbLayer::EscapeQuery($text)."') ";
		else if ($letter=="A") $search_letter = "i_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
		else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
	}
	else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
	
	// NOTE: Select the records from the individual table
	$sql = "";
	// NOTE: Select the records from the names table
	$sql .= "SELECT i_key, i_id, i_isdead, n_letter, i_gedrec, n_file, n_type, n_name, n_surname, n_letter ";
	$sql .= "FROM ".TBLPREFIX."names, ".TBLPREFIX."individuals ";
	$sql .= "WHERE n_key = i_key ";
	$sql .= "AND ".$search_letter;
	// NOTE: Add some optimization if the surname is set to speed up the lists
	if (!empty($surname)) $sql .= "AND n_surname LIKE '%".DbLayer::EscapeQuery($surname)."%' ";
	// NOTE: Do not retrieve married names if the user does not want to see them
	if (!GedcomConfig::$SHOW_MARRIED_NAMES) $sql .= "AND n_type NOT LIKE 'C' ";
	// NOTE: Make the selection on the currently active gedcom
	if ($allgeds != "yes") $sql .= "AND n_file = '".$GEDCOMID."' ";
	if ($allgeds != "yes") $sql .= "AND i_file = '".$GEDCOMID."'";

	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()) {
		$row = db_cleanup($row);
		if (substr($row["n_letter"], 0, 1) == substr($letter, 0, 1) || (isset($text) ? substr($row["n_letter"], 0, 1) == substr($text, 0, 1) : FALSE)){
			if ($COMBIKEY) $key = $row["i_key"];
			else $key = $row["i_id"];
			if (!isset($indilist[$key])) {
				$indi = array();
				if ($row["n_type"] != "C" || ($row["n_type"] == "C" && GedcomConfig::$SHOW_MARRIED_NAMES)) $indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$indi["isdead"] = $row["i_isdead"];
				$indi["gedcom"] = $row["i_gedrec"];
				$indi["gedfile"] = $row["n_file"];
				$tindilist[$key] = true;
				// NOTE: Cache the item in the $indilist for improved speed
				$indilist[$key] = $indi;
			}
			else {
				if ($row["n_type"] != "C" || ($row["n_type"] == "C" && GedcomConfig::$SHOW_MARRIED_NAMES)) $indilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);

			}
		}
	}
	$res->FreeResult();
	return $tindilist;
}

/**
 * Get Individuals with a given surname
 *
 * This function finds all of the individuals who have the given surname
 * @param string $surname	The surname to search on
 * @return array	$indilist array
 */
function GetSurnameIndis($surname, $allgeds="no") {
	global $LANGUAGE, $indilist, $GEDCOMID, $COMBIKEY;

	$tindilist = array();
	$sql = "SELECT i_key, i_id, i_file, i_isdead, i_gedrec, n_letter, n_name, n_surname, n_type FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND n_surname LIKE '".DbLayer::EscapeQuery($surname)."' ";
	if (!GedcomConfig::$SHOW_MARRIED_NAMES) $sql .= "AND n_type!='C' ";
	if ($allgeds == "no") $sql .= "AND i_file='".$GEDCOMID."'";
	$sql .= " ORDER BY n_surname";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$row = db_cleanup($row);
		if (GedcomConfig::$SHOW_NICK) {
			$n = NameFunctions::GetNicks($row["i_gedrec"]);
			if (count($n) > 0) {
				$ct = preg_match("~(.*)/(.*)/(.*)~", $row["n_name"], $match);
				if ($ct>0) $row["n_name"] = $match[1].substr(GedcomConfig::$NICK_DELIM, 0, 1).$n[0].substr(GedcomConfig::$NICK_DELIM, 1, 1)."/".$match[2]."/".$match[3];
//				$ct = preg_match("~(.*)/(.*)/(.*)~", $row["i_name"], $match);
//				$row["i_name"] = $match[1].substr(GedcomConfig::$NICK_DELIM, 0, 1).$n[0].substr(GedcomConfig::$NICK_DELIM, 1, 1)."/".$match[2]."/".$match[3];
			}
		}
		if (!$COMBIKEY) $key = $row["i_id"];
		else $key = $row["i_key"];
		if (isset($indilist[$key])) {
			if ($row["n_type"] != "C" || ($row["n_type"] == "C" && GedcomConfig::$SHOW_MARRIED_NAMES)) $indilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		}
		else {
			$indi = array();
			if ($row["n_type"] != "C" || ($row["n_type"] == "C" && GedcomConfig::$SHOW_MARRIED_NAMES)) $indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$indi["isdead"] = $row["i_isdead"];
			$indi["gedcom"] = $row["i_gedrec"];
			$indi["gedfile"] = $row["i_file"];
			$indilist[$key] = $indi;
			$tindilist[$key] = true;
		}
	}
	$res->FreeResult();
	return $tindilist;
}

function GetAlphaFamSurnames($letter, $allgeds="no") {
	global $GEDCOMID, $famlist, $indilist, $LANGUAGE, $COMBIKEY;

	$temp = GedcomConfig::$SHOW_MARRIED_NAMES;
	GedcomConfig::$SHOW_MARRIED_NAMES = false;
	$search_letter = "";
	
	// NOTE: Determine what letter to search for depending on the active language
	if (!empty($letter)) {
		if ($LANGUAGE == "hungarian"){
			if (strlen($letter) >= 2) $search_letter = "'".DbLayer::EscapeQuery($letter)."' ";
			else {
				if ($letter == "C") $text = "CS";
				else if ($letter == "D") $text = "DZ";
				else if ($letter == "G") $text = "GY";
				else if ($letter == "L") $text = "LY";
				else if ($letter == "N") $text = "NY";
				else if ($letter == "S") $text = "SZ";
				else if ($letter == "T") $text = "TY";
				else if ($letter == "Z") $text = "ZS";
				if (isset($text)) $search_letter = "(i_letter = '".DbLayer::EscapeQuery($letter)."' AND i_letter != '".DbLayer::EscapeQuery($text)."') ";
				else $search_letter = "i_letter LIKE '".DbLayer::EscapeQuery($letter)."%' ";
			}
		}
		else if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
			if ($letter == "Ø") $text = "OE";
			else if ($letter == "Æ") $text = "AE";
			else if ($letter == "Å") $text = "AA";
			if (isset($text)) $search_letter = "(i_letter = '".DbLayer::EscapeQuery($letter)."' OR i_letter = '".DbLayer::EscapeQuery($text)."') ";
			else if ($letter=="A") $search_letter = "i_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
			else $search_letter = "i_letter LIKE '".DbLayer::EscapeQuery($letter)."%' ";
		}
		else $search_letter = "i_letter LIKE '".DbLayer::EscapeQuery($letter)."%' ";
	}
	
	$namelist = array();
	$sql = "SELECT DISTINCT n_surname, count(DISTINCT if_fkey) as fams FROM ".TBLPREFIX."names, ".TBLPREFIX."individual_family WHERE n_key=if_pkey AND if_role='S' ";
	if (!empty($search_letter)) $sql .= "AND ".str_replace("i_letter", "n_letter", $search_letter);
	if ($allgeds != "yes") $sql .= " AND n_file = '".$GEDCOMID."' ";
	$sql .= "GROUP BY n_surname";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()) {
		$namelist[] = array("name"=>$row["n_surname"], "count"=>$row["fams"]);		
	}
	GedcomConfig::$SHOW_MARRIED_NAMES = $temp;
	return $namelist;
}

/**
 * Get Families Starting with a letter
 *
 * This function finds all of the families who start with the given letter
 * @param string $letter	The letter to search on
 * @return array	$indilist array
 * @see GetAlphaIndis()
 */
function GetAlphaFams($letter, $allgeds="no") {
	global $GEDCOMID, $famlist, $indilist, $LANGUAGE, $COMBIKEY;
	
	$search_letter = "";
	
	// NOTE: Determine what letter to search for depending on the active language
	if ($LANGUAGE == "hungarian"){
		if (strlen($letter) >= 2) $search_letter = "'".DbLayer::EscapeQuery($letter)."' ";
		else {
			if ($letter == "C") $text = "CS";
			else if ($letter == "D") $text = "DZ";
			else if ($letter == "G") $text = "GY";
			else if ($letter == "L") $text = "LY";
			else if ($letter == "N") $text = "NY";
			else if ($letter == "S") $text = "SZ";
			else if ($letter == "T") $text = "TY";
			else if ($letter == "Z") $text = "ZS";
			if (isset($text)) $search_letter = "(n_letter = '".DbLayer::EscapeQuery($letter)."' AND n_letter != '".DbLayer::EscapeQuery($text)."') ";
			else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."%' ";
		}
	}
	else if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
		if ($letter == "Ø") $text = "OE";
		else if ($letter == "Æ") $text = "AE";
		else if ($letter == "Å") $text = "AA";
		if (isset($text)) $search_letter = "(n_letter = '".DbLayer::EscapeQuery($letter)."' OR n_letter = '".DbLayer::EscapeQuery($text)."') ";
		else if ($letter=="A") $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
		else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
	}
	else $search_letter = "n_letter LIKE '".DbLayer::EscapeQuery($letter)."' ";
	
	$select = array();
	// This table is to determine which of the indis for a family has the desired letter.
	// Later, when building the famlist, it is used to place that person first in the familydescriptor
	$trans = array();
	$temp = GedcomConfig::$SHOW_MARRIED_NAMES;
	GedcomConfig::$SHOW_MARRIED_NAMES = false;
	
	$sql = "SELECT DISTINCT if_fkey, if_pkey, n_name FROM ".TBLPREFIX."names, ".TBLPREFIX."individual_family WHERE n_key=if_pkey AND if_role='S' ";
	$sql .= "AND n_type NOT LIKE 'C' ";
	$sql .= "AND ".$search_letter;
	if ($allgeds != "yes") $sql .= " AND n_file = '".$GEDCOMID."'";
	$sql .= " GROUP BY if_fkey";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()) {
		$select[] = $row["if_fkey"];
		$trans[$row["if_fkey"]]["id"] = $row["if_pkey"];
		$trans[$row["if_fkey"]]["name"] = $row["n_name"];
	}
	$select = "'".implode("', '", $select)."'";
	$f = GetFamlist($allgeds, $select, false, $trans);
	GedcomConfig::$SHOW_MARRIED_NAMES = $temp;
	return $f;
}

/**
 * Get Families with a given surname
 *
 * This function finds all of the individuals who have the given surname
 * @param string $surname	The surname to search on
 * @return array	$indilist array
 */
function GetSurnameFams($surname, $allgeds="no") {
	global $GEDCOMID, $famlist, $indilist, $COMBIKEY;
	
	$trans = array();
	$select = array();
	$temp = GedcomConfig::$SHOW_MARRIED_NAMES;
	GedcomConfig::$SHOW_MARRIED_NAMES = false;

	$sql = "SELECT DISTINCT if_fkey, if_pkey, n_name FROM ".TBLPREFIX."names, ".TBLPREFIX."individual_family WHERE n_key=if_pkey AND if_role='S' AND n_surname='".DbLayer::EscapeQuery($surname)."'";
	if ($allgeds != "yes") $sql .= " AND n_file = '".$GEDCOMID."' ";
	$sql .= "GROUP BY if_fkey";
	// The previous query works for all surnames, including @N.N.
	// But families with only one spouse (meaning the other is not known) are missing.
	// In that case, we also select families with one role Spouse.
	// What we exclude, is families where no spouses exist and which only consist of children.
	if ($surname == "@N.N.") {
		$sql .= " UNION SELECT if_fkey, '' AS if_pkey, '' AS n_name FROM ".TBLPREFIX."individual_family WHERE if_role='S' ";
		if ($allgeds != "yes") $sql .= " AND if_file = '".$GEDCOMID."'";
		$sql .= " GROUP BY if_fkey HAVING count(if_fkey)=1";
	}
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()) {
		$select[] = $row["if_fkey"];
		if (!empty($row["if_pkey"])) {
			$trans[$row["if_fkey"]]["id"] = $row["if_pkey"];
			$trans[$row["if_fkey"]]["name"] = $row["n_name"];
		}
	}
	$select = "'".implode("', '", $select)."'";
	if ($select != "''") $f = GetFamlist($allgeds, $select, false, $trans);
	else $f = array();
	GedcomConfig::$SHOW_MARRIED_NAMES = $temp;
	return $f;
}

//-- function to find the gedcom id for the given rin
function FindRinId($rin) {
	global $GEDCOMID;

	$sql = "SELECT i_id FROM ".TBLPREFIX."individuals WHERE i_rin='".$rin."' AND i_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		return $row["i_id"];
	}
	return $rin;
}

function DeleteGedcom($gedid) {

	$sql = "DELETE FROM ".TBLPREFIX."blocks WHERE b_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."changes WHERE ch_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."dates WHERE d_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."eventcache WHERE ge_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."families WHERE f_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."favorites WHERE fv_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."individual_family WHERE if_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."individuals WHERE i_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."asso WHERE as_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."log WHERE l_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."media WHERE m_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."media_mapping WHERE mm_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."names WHERE n_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	NewsController::DeleteUserNews($gedid);
	$sql = "DELETE FROM ".TBLPREFIX."other WHERE o_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."placelinks WHERE pl_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."places WHERE p_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."sources WHERE s_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."statscache WHERE gs_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."counters WHERE c_id LIKE '%[".DbLayer::EscapeQuery($gedid)."]%'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."users_gedcoms WHERE ug_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."actions WHERE a_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."pdata WHERE pd_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."soundex WHERE s_file='".DbLayer::EscapeQuery($gedid)."'";
	$res = NewQuery($sql);
}

//-- return the current size of the given list
//- list options are indilist famlist sourcelist and otherlist
function GetListSize($list) {
	global $GEDCOMID;

	switch($list) {
		case "indilist":
			$sql = "SELECT count(i_id) FROM ".TBLPREFIX."individuals WHERE i_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "famlist":
			$sql = "SELECT count(f_id) FROM ".TBLPREFIX."families WHERE f_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "sourcelist":
			$sql = "SELECT count(s_id) FROM ".TBLPREFIX."sources WHERE s_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "otherlist":
			$sql = "SELECT count(o_id) FROM ".TBLPREFIX."other WHERE o_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "medialist":
			$sql = "SELECT count(m_id) FROM ".TBLPREFIX."media WHERE m_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "notelist":
			$sql = "SELECT count(o_id) FROM ".TBLPREFIX."other WHERE o_file='".$GEDCOMID."' AND o_type='NOTE'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
	}
	return 0;
}

/**
 * Accept changed gedcom record into database
 *
 * This function gets an updated record from the gedcom file and replaces it in the database
 * @author 	Genmod Development Team
 * @param		string	$cid		The change id of the record to accept
 * @return 	boolean	true if changes were processed correctly, false if there was a problem
 */
function AcceptChange($cid, $gedfile, $all=false) {
	global $GEDCOMID, $FILE, $gm_user, $chcache;
	
	$cidchanges = array();
	if ($all) $sql = "SELECT ch_id, ch_cid, ch_gid, ch_file, ch_old, ch_new, ch_type, ch_user, ch_time FROM ".TBLPREFIX."changes WHERE ch_file = '".$gedfile."' ORDER BY ch_id ASC";
	else $sql = "SELECT ch_id, ch_cid, ch_gid, ch_file, ch_old, ch_new, ch_type, ch_user, ch_time FROM ".TBLPREFIX."changes WHERE ch_cid = '".$cid."' AND ch_file = '".$gedfile."' ORDER BY ch_id ASC";
	$res = NewQuery($sql);
	while ($row = $res->FetchAssoc()) {
		$cidchanges[$row["ch_id"]]["cid"] = $row["ch_cid"];
		$cidchanges[$row["ch_id"]]["gid"] = $row["ch_gid"];
		$cidchanges[$row["ch_id"]]["file"] = $row["ch_file"];
		$cidchanges[$row["ch_id"]]["old"] = $row["ch_old"];
		$cidchanges[$row["ch_id"]]["new"] = $row["ch_new"];
		$cidchanges[$row["ch_id"]]["type"] = $row["ch_type"];
		$cidchanges[$row["ch_id"]]["user"] = $row["ch_user"];
		$cidchanges[$row["ch_id"]]["time"] = $row["ch_time"];
	}
	if (count($cidchanges) > 0) {
		foreach ($cidchanges as $id => $details) {
			$FILE = $details["file"];
			$gedrec = FindGedcomRecord($details["gid"], $FILE, true);
			// print "Old value of gedrec: ".$gedrec."<br />";
			// NOTE: Import the record
			$update_id = "";
			if (empty($gedrec)) $gedrec = "";
			
			// NOTE: Add anything to existing ID
			// NOTE: If old is empty, just add the new data makes sure it is not new record
			if (empty($details["old"]) && !empty($details["new"]) && preg_match("/0\s@(.*)@/", $details["new"]) == 0) {
				$gedrec .= "\r\n".$details["new"];
				// print "New value of gedrec (add to existing): ".$gedrec."<br />";
				$update_id = UpdateRecord(EditFunctions::CheckGedcom($gedrec, true, $details["user"], $details["time"]));
			}
			
			// NOTE: Add new ID
			// NOTE: If the old is empty and the new is a new record make sure we just store the new record
			else if (empty($details["old"]) && preg_match("/0\s@(.*)@/", $details["new"]) > 0) {
				// print "New gedrec: ".$details["new"]."<br />";
				$update_id = UpdateRecord(EditFunctions::CheckGedcom($details["new"], true, $details["user"], $details["time"]));
			}
			
			// Note: Delete ID
			// NOTE: if old is not empty and new is  empty, AND it's 0-level, the record needs to be deleted
			else if (!empty($details["old"]) && empty($details["new"])&& preg_match("/0\s@(.*)@/", $details["old"]) > 0) {
				$update_id = UpdateRecord(EditFunctions::CheckGedcom(FindGedcomRecord($details["gid"]), true, $details["user"], $details["time"]), true);
				
				// NOTE: Delete change records related to this record
				$sql = "select ch_cid from ".TBLPREFIX."changes where ch_gid = '".$details["gid"]."' AND ch_file = '".$details["file"]."'";
				$res = NewQuery($sql);
				while ($row = $res->FetchAssoc()) {
					RejectChange($row["ch_cid"], $details["file"]);
				}
				
			}
			
			// NOTE: Change anything on an existing ID
			// NOTE: If new is empty or filled, the old needs to be replaced
			else {
				if ($details["type"] == "raw_edit") $gedrec = $details["new"];
				else {
					$gedrec = str_replace(trim($details["old"]), trim($details["new"]), $gedrec);
				}
//				print "Acceptchange: ".$gedrec;
				$update_id = UpdateRecord(EditFunctions::CheckGedcom($gedrec, true, $details["user"], $details["time"]));
			}
			WriteToLog("AcceptChange-> Accepted change for ".$details["gid"].". ->".$gm_user->username."<-", "I", "G", $gedfile);
		}
		GedcomConfig::ResetCaches($GEDCOMID);
		ResetChangeCaches();
	}
	// NOTE: record has been imported in DB, now remove the change
	foreach ($cidchanges as $id => $value) {
		$sql = "DELETE from ".TBLPREFIX."changes where ch_cid = '".$value["cid"]."'";
		$res = NewQuery($sql);
	}
	return true;
}

/**
 * Reject a change
 *
 * This function will remove a change from the changes table. When the user
 * has chosen to reject all changes, they will all be removed
 *
 * @author	Genmod Development Team
 * @param		string 	$cid		The change id of the form gid_gedcom
 * @param		int 		$gedfile	The file to which the changes belong
 * @param		boolean	$all		Whether to reject all changes or not
 * @return 	boolean	true if undo successful
 */
function RejectChange($cid, $gedfile, $all=false) {
	global $manual_save, $gm_user;
	
	// NOTE: Get the details of the change id, to check if we need to unlock any records
	$sql = "SELECT ch_type, ch_gid from ".TBLPREFIX."changes where ch_cid = '".$cid."' AND ch_file = '".$gedfile."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()) {
		$unlock_changes = array("raw_edit", "reorder_families", "reorder_children", "delete_source", "delete_indi", "delete_family", "delete_repo");
		if (in_array($row["ch_type"], $unlock_changes)) {
			$sql = "select ch_cid, ch_type from ".TBLPREFIX."changes where ch_gid = '".$row["ch_gid"]."' and ch_file = '".$gedfile."' order by ch_cid ASC";
			$res2 = NewQuery($sql);
			while($row2 = $res2->FetchAssoc()) {
				$sqlcid = "UPDATE ".TBLPREFIX."changes SET ch_delete = '0' WHERE ch_cid = '".$row2["ch_cid"]."'";
				$rescid = NewQuery($sqlcid);
			}
		}
	}
	
	if ($all) {
		$sql = "DELETE from ".TBLPREFIX."changes where ch_file = '".$gedfile."'";
		if ($res = NewQuery($sql)) {
			WriteToLog("RejectChange-> Rejected all changes for $gedfile "." ->" . $gm_user->username ."<-", "I", "G", $gedfile);
			ResetChangeCaches();
			return true;
		}
		else return false;
	}
	else {
		$sql = "DELETE from ".TBLPREFIX."changes where ch_cid = '".$cid."' AND ch_file = '".$gedfile."'";
		if ($res = NewQuery($sql)) {
			WriteToLog("RejectChange-> Rejected change $cid - $gedfile "." ->" . $gm_user->username ."<-", "I", "G", $gedfile);
			ResetChangeCaches();
			return true;
		}
		else return false;
	}
}

/**
 * update a record in the database
 * @param string $indirec
 */
function UpdateRecord($indirec, $delete=false) {
	global $GEDCOMID;

	$tt = preg_match("/0 @(.+)@ (.+)/", $indirec, $match);
	if ($tt>0) {
		$gid = trim($match[1]);
		$type = trim($match[2]);
	}
	else {
		$ct2 = preg_match("/0 HEAD/", $indirec, $match2);
		if ($ct2 == 0) {
			print "ERROR: Invalid gedcom record.<br />";
			print "<pre>".$indirec."</pre>";
			return false;
		}
		else {
			$type = "HEAD";
			$gid = "HEAD";
		}
	}
	$kgid = JoinKey($gid, $GEDCOMID);
	
	$sql = "SELECT pl_p_id FROM ".TBLPREFIX."placelinks WHERE pl_gid='".DbLayer::EscapeQuery($gid)."' AND pl_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	$placeids = array();
	while($row = $res->fetchRow()) {
		$placeids[] = $row[0];
	}
	$sql = "DELETE FROM ".TBLPREFIX."placelinks WHERE pl_gid='".DbLayer::EscapeQuery($gid)."' AND pl_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".TBLPREFIX."dates WHERE d_key='".DbLayer::EscapeQuery(JoinKey($gid, $GEDCOMID))."'";
	$res = NewQuery($sql);

	//-- delete any unlinked places
	foreach($placeids as $indexval => $p_id) {
		$sql = "SELECT count(pl_p_id) FROM ".TBLPREFIX."placelinks WHERE pl_p_id=$p_id AND pl_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$row = $res->fetchRow();
		if ($row[0]==0) {
			$sql = "DELETE FROM ".TBLPREFIX."places WHERE p_id=$p_id AND p_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
	}

	//-- delete any MM links to this pid
		$sql = "DELETE FROM ".TBLPREFIX."media_mapping WHERE mm_gid='".DbLayer::EscapeQuery($gid)."' AND mm_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
	
	if ($type=="INDI") {
		// First reset the isdead status for the surrounding records. 
		ResetIsDeadLinked($gid, "INDI");
		$sql = "DELETE FROM ".TBLPREFIX."individuals WHERE i_id='".DbLayer::EscapeQuery($gid)."' AND i_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."asso WHERE as_of='".DbLayer::EscapeQuery(JoinKey($gid, $GEDCOMID))."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."names WHERE n_gid='".DbLayer::EscapeQuery($gid)."' AND n_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."soundex WHERE s_gid='".DbLayer::EscapeQuery($kgid)."'";
		$res = NewQuery($sql);
		// Only delete the fam-indi info if the whole individual is deleted. 
		// Otherwise the info does not get reconstructed as some of it is in the family records (order).
		if ($delete) {
			$sql = "DELETE FROM ".TBLPREFIX."individual_family WHERE if_pkey='".JoinKey(DbLayer::EscapeQuery($gid), $GEDCOMID)."'";
			$res = NewQuery($sql);
		}
		$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_gid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_gid='".DbLayer::EscapeQuery($gid)."' AND om_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
	}
	else if ($type=="FAM") {
		// First reset the isdead status for the surrounding records. 
		ResetIsDeadLinked($gid, "FAM");
		$sql = "DELETE FROM ".TBLPREFIX."families WHERE f_id='".DbLayer::EscapeQuery($gid)."' AND f_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."asso WHERE as_of='".DbLayer::EscapeQuery(JoinKey($gid, $GEDCOMID))."'";
		$res = NewQuery($sql);
		// Only delete the fam-indi info if the whole family is deleted. 
		// Otherwise the info does not get reconstructed as most of it is in the individual records.
		if ($delete) {
			$sql = "DELETE FROM ".TBLPREFIX."individual_family WHERE if_fkey='".JoinKey(DbLayer::EscapeQuery($gid), $GEDCOMID)."'";
			$res = NewQuery($sql);
		}
		$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_gid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_gid='".DbLayer::EscapeQuery($gid)."' AND om_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
	}
	else if ($type=="SOUR") {
		$sql = "DELETE FROM ".TBLPREFIX."sources WHERE s_id='".DbLayer::EscapeQuery($gid)."' AND s_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		// We must preserve the links if the record is just changed and not deleted. 
		if ($delete) {
			$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_sid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
	}
	else if ($type == "OBJE") {
		$sql = "DELETE FROM ".TBLPREFIX."media WHERE m_media='".DbLayer::EscapeQuery($gid)."' AND m_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_gid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_gid='".DbLayer::EscapeQuery($gid)."' AND om_file='".$GEDCOMID."'";
	}
	else {
		$sql = "DELETE FROM ".TBLPREFIX."other WHERE o_id='".DbLayer::EscapeQuery($gid)."' AND o_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".TBLPREFIX."source_mapping WHERE sm_gid='".DbLayer::EscapeQuery($gid)."' AND sm_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		// We must preserve the links if the record is just changed and not deleted. 
		if ($delete) {
			$sql = "DELETE FROM ".TBLPREFIX."other_mapping WHERE om_gid='".DbLayer::EscapeQuery($gid)."' AND om_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
	}
	if ($delete) {
		if ($type == "FAM" || $type = "INDI" || $type == "SOUR" || $type == "OBJE") {
			// Delete favs
			$sql = "DELETE FROM ".TBLPREFIX."favorites WHERE fv_gid='".$gid."' AND fv_type='".$type."' AND fv_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
		if ($type == "INDI") {
			// Clear users
			UserController::ClearUserGedcomIDs($gid, $GEDCOMID);
			if (GedcomConfig::$PEDIGREE_ROOT_ID == $gid) {
				GedcomConfig::$PEDIGREE_ROOT_ID = "";
				GedcomConfig::SetPedigreeRootId("", $GEDCOMID);
			}
		}
		// Clear privacy
		PrivacyController::ClearPrivacyGedcomIDs($gid, $GEDCOMID);
	}

	if (!$delete) {
		ImportFunctions::ImportRecord($indirec, true);
	}
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
		$sql = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($gm_user->username)."', '".addslashes($LogString)."', '', '".$new."')";
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
	
function IsChangedFact($gid, $oldfactrec) {
	global $GEDCOMID, $show_changes, $gm_user;
	
//print "checking ".$gid." ".$oldfactrec."<br />";
	if ($show_changes && $gm_user->UserCanEditOwn($gid) && GetChangeData(true, $gid, true)) {
		$string = trim($oldfactrec);
		if (empty($string)) return false;
		$sql = "SELECT ch_old, ch_new FROM ".TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_file = '".$GEDCOMID."' ORDER BY ch_time ASC";
		$res = NewQuery($sql);
		if (!$res) return false;
		while ($row = $res->FetchRow()) {
			if (trim($row[0]) == trim($oldfactrec)) {
				return true;
			}
		}
	}
	return false;
}


function RetrieveChangedFact($gid, $fact, $oldfactrec) {
	global $GEDCOMID, $show_changes, $gm_user;
	
	if ($show_changes && $gm_user->UserCanEditOwn($gid) && GetChangeData(true, $gid, true)) {
		$sql = "SELECT ch_old, ch_new FROM ".TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_fact = '".$fact."' AND ch_file = '".$GEDCOMID."' ORDER BY ch_time ASC";
		$res = NewQuery($sql);
		$factrec = $oldfactrec;
		$found = false;
		while ($row = $res->FetchAssoc()) {
			if (trim($row["ch_old"]) == trim($factrec)) {
				$factrec = trim($row["ch_new"]);
				$found = true;
			}
		}
		if ($found) return $factrec;
	}
	return false;
}

function RetrieveNewFacts($gid, $includeall=false) {
	global $GEDCOMID, $show_changes;
	
	$facts = array();
	$newfacts = array();
	if ($show_changes && GetChangeData(true, $gid, true)) {
		$sql = "SELECT ch_old, ch_new FROM ".TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_file = '".$GEDCOMID."' ORDER BY ch_time ASC";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchAssoc()){
				if ($row["ch_old"] == "" && preg_match("/0 @.*@/", $row["ch_new"], $match) > 0) {
					$subs = getallsubrecords($row["ch_new"], "", false, false, false);
					foreach ($subs as $key => $sub) {
						$ct = preg_match("/\d (\w+) /", $sub, $match);
						$tag = $match[1];
						$facts[] = array("tag"=> $tag, "old"=>"", "new"=>$sub);
					}
				}
				else {
					$found = false;
					$ct = preg_match("/1\s+(\w+).*/", $row["ch_old"], $match);
					if ($ct == 0) $ct = preg_match("/1\s+(\w+).*/", $row["ch_new"], $match);
					if ($ct != 0) {
						$tag = $match[1];
						foreach ($facts as $key => $fact) {
							if (isset($fact["old"]) && trim($fact["new"]) == trim($row["ch_old"]) && $fact["tag"] == $tag) {
								$facts[$key]["new"] = $row["ch_new"];
								$found = true;
								break;
							}
						}
						if (!$found) $facts[] = array("tag"=>$tag, "old"=>$row["ch_old"], "new"=>$row["ch_new"]);
					}
				}
			}
			foreach($facts as $key => $fact) {
				if (empty($fact["old"])) {
					//print "Added--->".$fact["new"]."<BR>";
					$newfacts[] = $fact["new"];
				} else if (empty($fact["new"])) {
					//print "Deleted--->".$fact["old"]."<BR>";
					if ($includeall) {
						$pos = strpos ($fact["old"], "\n");
						if ($pos!==false) {
							$fact["old"] = substr($fact["old"], 0, $pos);
						}
						$fact["old"] .= "\n2 DATE (".strtolower(GM_LANG_delete).")";
						$newfacts[] = $fact["old"];
					}
				} else {
					//print "Modified--->".$fact["new"]."<BR>";
					if ($includeall) {
						$newfacts[] = $fact["new"];
					}
				}
			}
		}
	}
	return $newfacts;
}

function HasChangedMedia($gedrec) {
	
	if (empty($gedrec)) return false;
	$ct = preg_match_all("/\d\sOBJE\s@(.*)@/", $gedrec, $match);
	for ($i=0;$i<$ct;$i++) {
		if (GetChangeData(true, $match[1][$i], true)) return true;
	}
	return false;
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


function GetNewFams($pid) {
	global $GEDCOMID;
	
	$newfams = array();

	if (!empty($pid) && GetChangeData(true, $pid, true, "","")) {
		$rec = GetChangeData(false, $pid, true, "gedlines","");
		$gedrec = $rec[$GEDCOMID][$pid];
		$ct = preg_match_all("/1\s+FAMS\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
		if ($ct>0) {
			$oldfams = FindSfamilyIds($pid);
			$i=0;
			for ($j = 0; $j < $ct; $j++) {
				$found = false;
				foreach ($oldfams as $key => $oldfam) {
					if ($oldfam["famid"] == $fmatch[$j][1]) {
						$found = true;
						break;
					}
				}
				if (!$found) $newfams[]=$fmatch[$j][1];
			}
		}
	}
	return $newfams;
}
	
/*
* $status true: 	false/true returned: there are changes 
					$gid for this gid
* 					$thisged false/true: in all/current gedcoms
*					$fact for this fact
*					($data is N/A)
* $status false:	$data="gedlines": return array with gedcom/gedcom lines
					$data="gedcoms": return array with gedcoms with changes
					$thisged false/true: in all/current gedcom ($thisged and $data="gedcom" will have <= 1 result)
					$gid: for this gid
					$fact for this fact
*/
function GetChangeData($status=false, $gid="", $thisged=false, $data="gedlines", $fact="") {
	global $changes, $GEDCOMID;
	global $chcache, $chstatcache;
	
	// NOTE: If the file does not have an ID, go back
	if (!isset($GEDCOMID)) return false;
	
	// Initialise the results cache
	if (!isset($chcache)) $chcache = array();
	
	// Initialise the status cache
	if (!isset($chstatcache)) {
		$chstatcache = array();
		$sql = "SELECT ch_gid, ch_file, ch_gid_type FROM ".TBLPREFIX."changes";
		$resc = NewQuery($sql);
		if($resc) {
			while ($row = $resc->FetchAssoc()) {
				$chstatcache[$row["ch_gid"]][$row["ch_file"]] = true;
			}
		}
	}
	
	// Check in the cache if this gid has any changes. If not, no need to get anything from the DB	
	if ($status) {
		// Specific gid
		if (!empty($gid)) {
			// Specific gid, current gedcom
			if ($thisged) {
				if (!isset($chstatcache[$gid][$GEDCOMID])) return 0;
			}
			// Specific gid, all gedcoms
			else if (!isset($chstatcache[$gid])) return 0;
		}
		else {
			// No gid, current gedcom
			if ($thisged) {
				$has = false;
				foreach ($chstatcache as $gkey => $gged) {
					if (isset($gged[$GEDCOMID])) {
						$has = true;
						break;
					}
				}
				if (!$has) return 0;
			}
			// No gid, all gedcoms
			else if (count($chstatcache) == 0) return 0;
		}
	}
	
	$whereclause = "";
	if ($thisged) $whereclause .= "ch_file = '".$GEDCOMID."'";
	if (!empty($gid)) {
		if (!empty($whereclause)) $whereclause .= " AND ";
		$whereclause .= "ch_gid = '".$gid."'";
	}
	if (!empty($fact)) {
		if (!empty($whereclause)) $whereclause .= " AND ";
		$factarr = preg_split("/(,)/", $fact);
		$or = false;
		if (count($factarr) >1) $whereclause .="(";
		foreach($factarr as $key => $fact) {
			if ($or) {
				$whereclause .= " OR ";
			}
			$or = true;
			$whereclause .= "ch_fact = '".trim($fact)."'";
		}
		if (count($factarr) >1) $whereclause .=")";
	}

	if ($status) $selectclause = "SELECT COUNT(ch_id) ";
	else {
		if ($data == "gedcoms") $selectclause = "SELECT ch_file ";
		else {
			$selectclause = "SELECT ch_gid, ch_type, ch_fact, ch_file, ch_old, ch_new, ch_gid_type";
			$whereclause .= " ORDER BY";
			if ($data == "gedlinesCHAN") {
				$data = "gedlines";
				$selectclause .= ", ch_user, ch_time";
				$whereclause .= " ch_time,";
			}
			$selectclause .= " ";
			$whereclause .= " ch_gid, ch_id ";
		}
	}

	$sql = $selectclause."FROM ".TBLPREFIX."changes";
	if (!empty($whereclause)) $sql .= " WHERE ".$whereclause;

	if (array_key_exists($sql, $chcache)) return $chcache[$sql];
	$res = NewQuery($sql);
	if (!$res) return false;	
	
	if($status) {
		$row = $res->FetchRow();
		$chcache[$sql] = $row[0];
		return $row[0];
	}
	else {
		if ($data == "gedcoms") {
			// NOTE: Return gedcoms which have changes
			$gedfiles = array();
			while ($row = $res->FetchAssoc($res->result)) {
				$gedfiles[$row["ch_file"]] = $row["ch_file"];
			}
			$chcache[$sql] = $gedfiles;
			return $gedfiles;
		}
		else {
			// NOTE: Construct the changed gedcom record
			$gedlines = array();
			while ($row = $res->FetchAssoc($res->result)) {
				$gedname = $row["ch_file"];
				$chgid = $row["ch_gid"];
				$gidtype = $row["ch_gid_type"];
				if (!isset($gedlines[$gedname][$chgid])) {
					$gedlines[$gedname][$chgid] = trim(ReadGedcomRecord($chgid, $gedname, $gidtype));
				}

				// NOTE: Add to existing ID
				// NOTE: If old is empty, just add the new data, make sure it is not new record
				if (empty($row["ch_old"]) && !empty($row["ch_new"]) && preg_match("/0\s@(.*)@/", $row["ch_new"]) == 0) {
					$gedlines[$gedname][$chgid] .= "\r\n".$row["ch_new"];
				}
				
				// NOTE: Add new ID
				// NOTE: If the old is empty and the new is a new record make sure we just store the new record
				else if (empty($row["ch_old"]) && preg_match("/0\s@(.*)@/", $row["ch_new"]) > 0) {
					$gedlines[$gedname][$chgid] = $row["ch_new"];
				}
				
				// NOTE: Delete ID
				// NOTE: if old is not empty and new is empty, AND new pid, the record needs to be deleted
				else if (!empty($row["ch_old"]) && empty($row["ch_new"])&& preg_match("/0\s@(.*)@/", $row["ch_new"]) > 0) {
					$gedlines[$gedname][$chgid] = GM_LANG_record_to_be_deleted;
				}
				
				// NOTE: Replace any other, change or delete from ID
				// NOTE: If new is empty or filled, the old needs to be replaced
				else $gedlines[$gedname][$chgid] = str_replace(trim($row["ch_old"]), $row["ch_new"], $gedlines[$gedname][$chgid]);

				if (isset($row["ch_user"]) && isset($row["ch_time"])) {
					$gedrecord = $gedlines[$gedname][$chgid];
					if (empty($gedrecord)) {
						// deleted record
						$gedrecord = trim(FindGedcomRecord($chgid, $gedname));
					}
					//LERMAN
					$gedrecord = EditFunctions::CheckGedcom($gedrecord, true, $row["ch_user"], $row["ch_time"]);
					$gedlines[$gedname][$chgid] = trim($gedrecord);
				}
			}
			$chcache[$sql] = $gedlines;
			return $gedlines;
		}
	}
}

function ResetChangeCaches() {
	
	// Use globals here, otherwise the cache won't be reset.
	unset($GLOBALS['chcache']);
	unset($GLOBALS['chstatcache']);
}

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
	global $TOTAL_COUNT, $GEDCOMID, $indilist;
	
	$uindilist = array();
	
	$sql = "SELECT i_id, i_gedrec, i_file, i_isdead, n_name, n_letter, n_surname, n_type FROM ".TBLPREFIX."individuals LEFT JOIN ".TBLPREFIX."names ON i_key=n_key LEFT JOIN ".TBLPREFIX."individual_family ON i_key=if_pkey WHERE if_pkey IS NULL AND i_file='".$GEDCOMID."'";

	$res = NewQuery($sql);
	if ($res) {
		$TOTAL_COUNT++;
		while($row = $res->FetchAssoc()){
			$row = db_cleanup($row);
			if (!isset($indilist[$row["i_id"]])) {
				$indi = array();
				$indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$indi["isdead"] = $row["i_isdead"];
				$indi["gedcom"] = $row["i_gedrec"];
				$indi["gedfile"] = $row["i_file"];
				$indilist[$row["i_id"]] = $indi;
			}
			else {
				$indilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			}
			$uindilist[$row["i_id"]] = $indilist[$row["i_id"]];
		}
	}
	return $uindilist;
}

function GetFemalesWithFAMS() {
	global $GEDCOMID;

	
	$flist = array();
	$sql = "SELECT i_gedrec, i_id FROM ".TBLPREFIX."individuals WHERE i_file = '".$GEDCOMID."' AND i_gedrec LIKE '%1 SEX F%' AND i_gedrec LIKE '%1 FAMS%'";
	$res = NewQuery($sql);
	$ct = $res->NumRows($res->result);
	while($row = $res->FetchAssoc($res->result)){
		$fem = array();
		$fem["gedcom"] = $row["i_gedrec"];
		$row = db_cleanup($row);
		$flist[$row["i_id"]] = $fem;
	}
	$res->FreeResult();
	return $flist;
}

//-- get the famlist from the datastore
function GetFamListWithMARR() {
	global $GEDCOMID;

	$famlist = array();
	$sql = "SELECT f_id, f_gedrec FROM ".TBLPREFIX."families WHERE f_file='".$GEDCOMID."' AND f_gedrec LIKE '%1 MARR%'";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	if ($ct > 0) {
		while($row = $res->FetchAssoc()){
			$famlist[$row["f_id"]]["gedcom"] = $row["f_gedrec"];
		}
	}
	$res->FreeResult();
	return $famlist;
}

function HasOtherChanges($pid, $change_id, $gedid="") {
	global $GEDCOMID;
	
	if (empty($gedid)) $gedid = $GEDCOMID;
	if (GetChangeData(true, $pid, true)) {
		$sql = "SELECT count(ch_id) FROM ".TBLPREFIX."changes WHERE ch_file='".$gedid."' AND ch_gid='".$pid."' AND ch_cid<>'".$change_id."'";
		$res = NewQuery($sql);
		$row = $res->FetchRow();
		return $row[0];
	}
	else return false;
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
	global $GEDCOMID;

	if (empty($oid)) return false;
	
	$links = array();
	$sql = 	"SELECT DISTINCT om_gid, om_type FROM ".TBLPREFIX."other_mapping WHERE om_oid='".$oid."' AND om_file='".$GEDCOMID."'";
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
	global $GEDCOMID;
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
	$sql = "SELECT DISTINCT sm_gid, sm_type FROM ".TBLPREFIX."source_mapping WHERE sm_sid='".$pid."' AND sm_file='".$GEDCOMID."'";
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
			$indisel = "'".implode("[".$GEDCOMID."]','", $indisel)."[".$GEDCOMID."]'";
			GetIndiList("no", $indisel, false);
		}
		if (count($famsel) > 0) {
			$famsel = array_flip(array_flip($famsel));
			$famsel = "'".implode ("[".$GEDCOMID."]','", $famsel)."[".$GEDCOMID."]'";
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
	global $GEDCOMID, $alllinks, $LINK_PRIVACY;
	
	if (!isset($alllinks)) $alllinks = array();
	if ($LINK_PRIVACY) {
		$famsel = array();
		$indisel = array();	
	}
	$sql = "SELECT sm_sid, sm_gid, sm_type FROM ".TBLPREFIX."source_mapping WHERE sm_file='".$GEDCOMID."'";
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
			$indisel = "'".implode("[".$GEDCOMID."]','", $indisel)."[".$GEDCOMID."]'";
			GetIndiList("no", $indisel, false);
		}
		if (count($famsel) > 0) {
			array_flip(array_flip($famsel));
			$famsel = "'".implode ("[".$GEDCOMID."]','", $famsel)."[".$GEDCOMID."]'";
			GetFamList("no", $famsel, false);
		}
	}
}

function GetMediaLinks($pid, $type="", $applypriv=true) {
	global $GEDCOMID;
	global $allmlinks, $indilist, $famlist, $LINK_PRIVACY;
	
	if (empty($pid)) return false;
	
	if (!isset($allmlinks)) $allmlinks = array();
	if (isset($allmlinks[$pid][$type][$applypriv])) return $allmlinks[$pid][$type][$applypriv];

	$links = array();	
	$indisel = array();
	$famsel = array();	
	$sql = "SELECT DISTINCT mm_gid, mm_type FROM ".TBLPREFIX."media_mapping WHERE mm_media='".$pid."'";
	if (!empty($type)) $sql .= " AND mm_type='".$type."'";
	$sql .= " AND mm_file='".$GEDCOMID."'";
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
		$indisel = "'".implode("[".$GEDCOMID."]','", $indisel)."[".$GEDCOMID."]'";
		$famsel = "'".implode ("[".$GEDCOMID."]','", $famsel)."[".$GEDCOMID."]'";
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

function ReadGedcomRecord($id, $gedid, $type) {
	
	if ($type == "INDI") $sql = "SELECT i_gedrec FROM ".TBLPREFIX."individuals WHERE i_key='".DbLayer::EscapeQuery(JoinKey($id, $gedid))."'";
	else if ($type == "FAM") $sql = "SELECT f_gedrec FROM ".TBLPREFIX."families WHERE f_key='".DbLayer::EscapeQuery(JoinKey($id, $gedid))."'";
	else if ($type == "SOUR") $sql = "SELECT s_gedrec FROM ".TBLPREFIX."sources WHERE s_key='".DbLayer::EscapeQuery(JoinKey($id, $gedid))."'";
	else if ($type == "REPO" || $type == "NOTE" || $type == "HEAD" || $type == "SUBM") $sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_key='".DbLayer::EscapeQuery(JoinKey($id, $gedid))."'";
	else if ($type == "OBJE") $sql = "SELECT m_gedrec FROM ".TBLPREFIX."media WHERE m_media LIKE '".DbLayer::EscapeQuery($id)."' AND m_file='".$gedid."'";
	$res = NewQuery($sql);
	if ($res->NumRows() == 0) return false;
	else {
		$row = $res->FetchRow();
		return $row[0];
	}
}

?>