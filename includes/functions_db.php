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
 * @version $Id: functions_db.php,v 1.305 2009/04/05 11:52:34 sjouke Exp $
 * @package Genmod
 * @subpackage DB
 */
if (strstr($_SERVER["SCRIPT_NAME"],"functions")) {
	require "../intrusion.php";
}

//-- set the REGEXP status of databases
$REGEXP_DB = true;

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
function CheckForImport($ged) {
	global $TBLPREFIX, $GEDCOMS, $DBCONN;
	
	$sql = "SELECT COUNT(i_id) FROM ".$TBLPREFIX."individuals WHERE i_file='";
	if (is_int($ged)) $sql .= $ged;
	else $sql .= $DBCONN->EscapeQuery($GEDCOMS[$ged]["id"]);
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
	global $TBLPREFIX, $COMBIKEY;
	global $GEDCOMS, $GEDCOM, $GEDCOMID, $famlist, $DBCONN, $COMBIKEY;

	if (empty($famid)) return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	$gedfileid = $GEDCOMS[$gedfile]["id"];
	
	if ($COMBIKEY) $key = JoinKey($famid, $gedfileid);
	else $key = $famid;

	if (!$renew && isset($famlist[$key]["gedcom"])&&($famlist[$key]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $famlist[$key]["gedcom"];

	$sql = "SELECT f_gedcom, f_file, f_husb, f_wife FROM ".$TBLPREFIX."families WHERE f_key='".$DBCONN->EscapeQuery(JoinKey($famid, $gedfileid))."'";

	$res = NewQuery($sql);
	if (!$res || $res->NumRows()==0) {
		return "";
	}
	$row = $res->fetchAssoc();

	$famlist[$key]["gedcom"] = $row["f_gedcom"];
	$famlist[$key]["gedfile"] = $row["f_file"];
	$famlist[$key]["HUSB"] = $row["f_husb"];
	$famlist[$key]["WIFE"] = $row["f_wife"];
	FindPersonRecord($row["f_husb"], $gedfile);
	FindPersonRecord($row["f_wife"], $gedfile);
	$res->FreeResult();
	return $row["f_gedcom"];
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
	global $gm_lang;
	global $TBLPREFIX, $COMBIKEY;
	global $GEDCOM, $GEDCOMS, $GEDCOMID;
	global $BUILDING_INDEX, $indilist, $DBCONN;

	if (empty($pid)) return false;
	if (is_int($GEDCOM)) $GEDCOM = get_gedcom_from_id($GEDCOM);
	if (empty($gedfile)) $gedfile = $GEDCOM;
	if (empty($gedfile)) return "";
	
	$gedfileid = $GEDCOMS[$gedfile]["id"];
	// print $pid." ".$gedfileid."<br />";
	
	//-- first check the indilist cache
	if (!$renew && isset($indilist[$pid]["gedcom"]) && $indilist[$pid]["gedfile"]==$gedfileid) return $indilist[$pid]["gedcom"];
	if (!$renew && isset($indilist[JoinKey($pid, $gedfileid)]["gedcom"])) return $indilist[JoinKey($pid, $gedfileid)]["gedcom"];

	$sql = "SELECT i_key, i_gedcom, i_isdead, i_file FROM ".$TBLPREFIX."individuals WHERE i_key='".$DBCONN->EscapeQuery(JoinKey($pid, $gedfileid))."'";
	$res = NewQuery($sql);
	if ($res) {
		if ($res->NumRows()==0) {
			return "";
		}
		$row = $res->fetchAssoc();
		$indi = array();
		$indi["gedcom"] = $row["i_gedcom"];
		if ($nocache) return $indi["gedcom"];
		$indi["names"] = GetIndiNames($row["i_gedcom"]);
		$indi["isdead"] = $row["i_isdead"];
		$indi["gedfile"] = $row["i_file"];
		$res->FreeResult();
		if ($COMBIKEY) $indilist[JoinKey($pid, $indi["gedfile"])] = $indi;
		else $indilist[$pid] = $indi;
		return $row["i_gedcom"];
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
	global $gm_lang, $TBLPREFIX, $GEDCOMS, $MEDIA_ID_PREFIX;
	global $GEDCOM, $GEDCOMID, $indilist, $famlist, $sourcelist, $otherlist, $repolist, $medialist, $DBCONN;
	global $GEDCOM_ID_PREFIX, $FAM_ID_PREFIX, $SOURCE_ID_PREFIX, $MEDIA_ID_PREFIX, $NOTE_ID_PREFIX;

	if (empty($pid)) return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	$gedfileid = $GEDCOMS[$gedfile]["id"];
	if (!$renew) {
		if (isset($indilist[$pid."[".$gedfileid."]"]["gedcom"])) return $indilist[$pid."[".$gedfileid."]"]["gedcom"];
		if ((isset($indilist[$pid]["gedcom"]))&&($indilist[$pid]["gedfile"]==$gedfileid)) return $indilist[$pid]["gedcom"];
		if ((isset($famlist[$pid]["gedcom"]))&&($famlist[$pid]["gedfile"]==$gedfileid)) return $famlist[$pid]["gedcom"];
		if ((isset($sourcelist[$pid]["gedcom"]))&&($sourcelist[$pid]["gedfile"]==$gedfileid)) return $sourcelist[$pid]["gedcom"];
		if ((isset($repolist[$pid]["gedcom"])) && ($repolist[$pid]["gedfile"]==$gedfileid)) return $repolist[$pid]["gedcom"];
		if ((isset($otherlist[$pid]["gedcom"]))&&($otherlist[$pid]["gedfile"]==$gedfileid)) return $otherlist[$pid]["gedcom"];
		if ((isset($medialist[$pid]["gedcom"]))&&($medialist[$pid]["gedfile"]==$gedfileid)) return $medialist[$pid]["gedcom"];
	
	}

	// To minimize queries, we first start to guess what record type we must retrieve
	$tried = "";
	if (substr($pid,0,strlen($GEDCOM_ID_PREFIX)) == $GEDCOM_ID_PREFIX) {
		$gedrec = FindPersonRecord($pid, $gedfile, $renew, $nocache);
		$tried = "indi";
	}
	else {
		if (substr($pid,0,strlen($FAM_ID_PREFIX)) == $FAM_ID_PREFIX) {
			$gedrec = FindFamilyRecord($pid, $gedfile, $renew);
			$tried = "fam";
		}
		else {
			if (substr($pid,0,strlen($SOURCE_ID_PREFIX)) == $SOURCE_ID_PREFIX) {
				$gedrec = FindSourceRecord($pid, $gedfile, $renew);
				$tried = "sour";
			}
			else {
				if (substr($pid,0,strlen($MEDIA_ID_PREFIX)) == $MEDIA_ID_PREFIX) {
					$gedrec = FindMediaRecord($pid, $gedfile, $renew);
					$tried = "media";
				}
				else {
					if (substr($pid,0,strlen($NOTE_ID_PREFIX)) == $NOTE_ID_PREFIX) {
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
	global $gm_lang;
	global $TBLPREFIX, $GEDCOMS;
	global $GEDCOM, $otherlist, $DBCONN;

	if ($oid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	
	if (!$renew && isset($otherlist[$oid]["gedcom"]) && ($otherlist[$oid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $otherlist[$oid]["gedcom"];

	$sql = "SELECT o_gedcom, o_file FROM ".$TBLPREFIX."other WHERE o_id LIKE '".$DBCONN->EscapeQuery($oid)."' AND o_file='".$DBCONN->EscapeQuery($GEDCOMS[$gedfile]["id"])."'";
	if (!empty($type)) $sql .= " AND o_type='".$type."'";
	$res = NewQuery($sql);
	if ($res->NumRows()!=0) {
		$row = $res->fetchRow();
		$res->FreeResult();
		$otherlist[$oid]["gedcom"] = $row[0];
		$otherlist[$oid]["gedfile"] = $row[1];
		$gedrec = $row[0];
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
	global $gm_lang;
	global $TBLPREFIX, $GEDCOMS;
	global $GEDCOM, $sourcelist, $DBCONN;

	if ($sid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	
	if (!$renew && isset($sourcelist[$sid]["gedcom"]) && ($sourcelist[$sid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $sourcelist[$sid]["gedcom"];

	$sql = "SELECT s_gedcom, s_name, s_file FROM ".$TBLPREFIX."sources WHERE s_id LIKE '".$DBCONN->EscapeQuery($sid)."' AND s_file='".$DBCONN->EscapeQuery($GEDCOMS[$gedfile]["id"])."'";
	$res = NewQuery($sql);
	if ($res->NumRows()!=0) {
		$row = $res->fetchRow();
		$sourcelist[$sid]["name"] = stripslashes($row[1]);
		$sourcelist[$sid]["gedcom"] = $row[0];
		$sourcelist[$sid]["gedfile"] = $row[2];
		$res->FreeResult();
		return $row[0];
	}
	else {
		return "";
	}
}


/**
 * Find a repository record by its ID
 * @param string $rid	the record id
 * @param string $gedfile	the gedcom file id
 */
function FindRepoRecord($rid, $gedfile="") {
	global $TBLPREFIX, $GEDCOMS;
	global $GEDCOM, $repolist, $DBCONN;

	if ($rid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	
	if (isset($repolist[$rid]["gedcom"]) && ($repolist[$rid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $repolist[$rid]["gedcom"];

	$sql = "SELECT o_id, o_gedcom, o_file FROM ".$TBLPREFIX."other WHERE o_type='REPO' AND o_id LIKE '".$DBCONN->EscapeQuery($rid)."' AND o_file='".$DBCONN->EscapeQuery($GEDCOMS[$gedfile]["id"])."'";
	$res = NewQuery($sql);
	if ($res->NumRows()!=0) {
		$row = $res->fetchRow();
		$tt = preg_match("/1 NAME (.*)/", $row[1], $match);
		if ($tt == "0") $name = $row[0]; else $name = $match[1];
		$repolist[$rid]["name"] = stripslashes($name);
		$repolist[$rid]["gedcom"] = $row[1];
		$repolist[$rid]["gedfile"] = $row[2];
		$res->FreeResult();
		return $row[1];
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
	global $TBLPREFIX, $GEDCOMS;
	global $GEDCOM, $medialist, $DBCONN, $GEDCOMID, $MediaFS;
	
	if ($rid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	
	//-- first check for the record in the cache
	if (!$renew && isset($medialist[$rid]["gedcom"]) && ($medialist[$rid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $medialist[$rid]["gedcom"];

	$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE m_media LIKE '".$DBCONN->EscapeQuery($rid)."' AND m_gedfile='".$GEDCOMID."'";
	$res = NewQuery($sql);
	if ($res->NumRows()!=0) {
		$row = $res->FetchAssoc();
		$medialist[$rid]["ext"] = $row["m_ext"];
		$medialist[$rid]["title"] = $row["m_titl"];
		$medialist[$rid]["file"] = $MediaFS->CheckMediaDepth($row["m_file"]);
		$medialist[$rid]["gedcom"] = $row["m_gedrec"];
		$medialist[$rid]["gedfile"] = $row["m_gedfile"];
		$res->FreeResult();
		return $row["m_gedrec"];
	}
	else {
		return "";
	}
}

/**
 * find and return the id of the first person in the gedcom
 * @return string the gedcom xref id of the first person in the gedcom
 */
function FindFirstPerson() {
	global $TBLPREFIX, $GEDCOMID;
	$sql = "SELECT i_id FROM ".$TBLPREFIX."individuals WHERE i_file='".$GEDCOMID."' ORDER BY i_id LIMIT 1";
	$res = NewQuery($sql);
	$row = $res->FetchAssoc();
	$res->FreeResult();
	return $row["i_id"];
}

function FindSubmitter($gedid) {
	global $TBLPREFIX, $GEDCOMID;
	if (!isset($gedid)) $gedid = $GEDCOMID;
	$sql = "SELECT o_id FROM ".$TBLPREFIX."other WHERE o_file='".$gedid."' AND o_type='SUBM'";
	$res = NewQuery($sql);
	if (!$res || $res->NumRows() == 0) {
		// If there is a new unapproved submitter record, is has the default pid
		if (GetChangeData(true, "SUB1", false, "", "")) {
			$rec = GetChangeData(false, "SUB1", false, "gedlines", "");
			if (isset($rec[get_gedcom_from_id($gedid)]["SUB1"])) return "SUB1";
			else return "";
		}
	}
	$row = $res->FetchAssoc();
	$res->FreeResult();
	return $row["o_id"];
}



//=================== IMPORT FUNCTIONS ======================================

/**
 * import record into database
 *
 * this function will parse the given gedcom record and add it to the database
 * @param string $indirec the raw gedcom record to parse
 * @param boolean $update whether or not this is an updated record that has been accepted
 */
function ImportRecord($indirec, $update=false) {
	global $DBCONN, $gid, $type, $indilist,$famlist,$sourcelist,$otherlist, $prepared_statement;
	global $TBLPREFIX, $GEDCOM_FILE, $FILE, $gm_lang, $USE_RIN, $gdfp, $placecache, $GEDCOM, $GEDCOMID;
	global $ALPHABET_upper, $ALPHABET_lower, $place_id, $WORD_WRAPPED_NOTES, $GEDCOMS, $media_count;
	
	if (strlen(trim($indirec)) ==  0) return false;
	//-- import different types of records
	$ct = preg_match("/0 @(.*)@ ([A-Z_]+)/", $indirec, $match);
	if ($ct > 0) {
		$gid = $match[1];
		$type = trim($match[2]);
	}
	else {
		$ct = preg_match("/0 (.*)/", $indirec, $match);
		if ($ct>0) {
			$gid = trim($match[1]);
			$type = trim($match[1]);
		}
		else {
			print $gm_lang["invalid_gedformat"]; print "<br /><pre>$indirec</pre>\n";
		}
	}

	//-- remove double @ signs
	$indirec = preg_replace("/@+/", "@", $indirec);

	// remove heading spaces
	$indirec = preg_replace("/\n(\s*)/", "\n", $indirec);

	//-- if this is an import from an online update then import the places
	// NOTE: What's the difference? Oh... in uploadgedcom it's also done. So only do it here in case of updates
	if ($update) {
//		UpdatePlaces($gid, $indirec, $update);
		UpdatePlaces($gid, $indirec, true);
		UpdateDates($gid, $indirec);

		//-- Also add the MM links to the DB
		$lines = preg_split("/[\r\n]+/", trim($indirec));
		$ct_lines = count($lines);
		foreach($lines as $key => $line) {
			$ct = preg_match_all("/([1-9])\sOBJE\s@(.+)@/", $line, $match);
			for ($i=0;$i<$ct;$i++) {
				$rec = $match[0][$i];
//				print "rec: ".$rec."<br />";
				$level = $match[1][$i];
//				print "level: ".$level."<br />";
				$media = $match[2][$i];
//				print "media: ".$media."<br />";
				$gedrec = GetSubRecord($level, $rec, $indirec, 1);
//				print "gedrec: ".$gedrec."<br />";
				add_db_link($media, $gid, $gedrec, $GEDCOM, -1, $type);
			}
		}
	}
	$indirec = UpdateMedia($gid, $indirec, $update);
	
	// Insert the source links
	// Recalculate $gid as it may have changed in UpdateMedia
	$ct = preg_match_all("/([1-9])\sSOUR\s@(.+)@/", $indirec, $match);
	if ($ct > 0) {
		$cc = preg_match("/0 @(.*)@ ([A-Z_]+)/", $indirec, $cmatch);
		if ($cc > 0) {
			$gid = $cmatch[1];
			$type = trim($cmatch[2]);
		}
		else {
			$cc = preg_match("/0 (.*)/", $indirec, $cmatch);
			if ($cc>0) {
				$gid = trim($cmatch[1]);
				$type = trim($cmatch[1]);
			}
		}
	}
	$kgid = JoinKey($gid, $GEDCOMID);
	for ($i=0;$i<$ct;$i++) {
		$rec = $match[0][$i];
		$level = $match[1][$i];
		$sour = $match[2][$i];
		$gedrec = GetSubRecord($level, $rec, $indirec, 1);
		$result = AddSourceLink($sour, $gid, $gedrec, $GEDCOMID, $type);
	}
	
	// Insert the other links
	// Recalculate $gid as it may have changed in UpdateMedia
	$ct = preg_match_all("/([1-9])\s(NOTE|REPO)\s@(.+)@/", $indirec, $match);
	if ($ct > 0) {
		$cc = preg_match("/0 @(.*)@ ([A-Z_]+)/", $indirec, $cmatch);
		if ($cc > 0) {
			$gid = $cmatch[1];
			$type = trim($cmatch[2]);
		}
		else {
			$cc = preg_match("/0 (.*)/", $indirec, $cmatch);
			if ($cc>0) {
				$gid = trim($cmatch[1]);
				$type = trim($cmatch[1]);
			}
		}
	}
	for ($i=0;$i<$ct;$i++) {
		$rec = $match[0][$i];
		$level = $match[1][$i];
		$note = $match[3][$i];
		$result = AddOtherLink($note, $gid, $type, $GEDCOMID);
	}
	if ($type == "INDI") {
		$indirec = CleanupTagsY($indirec);
		$ct = preg_match_all("/1 FAMS @(.*)@/", $indirec, $match, PREG_SET_ORDER);
		$sfams = "";
		$order = 1;
		$kgid = JoinKey($gid, $GEDCOMID);
		for($j=0; $j<$ct; $j++) {
			$sql = "INSERT INTO ".$TBLPREFIX."individual_family VALUES(NULL, '".$kgid."', '".JoinKey($match[$j][1], $GEDCOMID)."', '".$order."', 'S', '', '', '', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."') ON DUPLICATE KEY UPDATE if_order='".$order."'";
			$res = NewQuery($sql);
			$sfams .= $match[$j][1].";";
			$order++;
		}
		$ct = preg_match_all("/1 FAMC @(.*)@/", $indirec, $match, PREG_SET_ORDER);
		$cfams = "";
		$i=1;
		for($j=0; $j<$ct; $j++) {
			// Get the primary status
			$famcrec = GetSubRecord(1, "1 FAMC", $indirec, $i);
			$ct2 = preg_match("/2\s+_PRIMARY\s(.+)/", $famcrec, $pmatch);
			if ($ct2>0) $prim = trim($pmatch[1]);
			else $prim = "";
			// Get the pedi status
			$ct2 = preg_match("/2\s+PEDI\s+(adopted|birth|foster|sealing)/", $famcrec, $pmatch);
			$ped = "";
			if ($ct2>0) $ped = substr(trim($pmatch[1]), 0, 1);
			if ($ped == "b") $ped = "";
			// Get the stat status
			$ct2 = preg_match("/2\s+STAT\s+(challenged|proven|disproven)/", $famcrec, $pmatch);
			$stat = "";
			if ($ct2>0) $stat = substr(trim($pmatch[1]),0 ,1);
			// Insert the stuff in the DB
			$sql = "INSERT INTO ".$TBLPREFIX."individual_family VALUES(NULL, '".$kgid."', '".JoinKey($match[$j][1], $GEDCOMID)."', '', 'C', '".$prim."', '".$ped."', '".$stat."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."') ON DUPLICATE KEY UPDATE if_prim='".$prim."', if_pedi='".$ped."', if_stat='".$stat."'";
			$res = NewQuery($sql);
			$cfams .= $match[$j][1].";";
			$i++;
		}
		$isdead = -1;
		$indi = array();
		$names = GetIndiNames($indirec, true);
		$soundex_codes = GetSoundexStrings($names, true, $indirec);
		foreach($names as $indexval => $name) {
			$sql = "INSERT INTO ".$TBLPREFIX."names VALUES('".$DBCONN->EscapeQuery($gid)."[".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."]','".$DBCONN->EscapeQuery($gid)."','".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."','".$DBCONN->EscapeQuery($name[0])."','".$DBCONN->EscapeQuery($name[1])."','".$DBCONN->EscapeQuery($name[2])."','".$DBCONN->EscapeQuery($name[3])."')";
			$res = NewQuery($sql);
			if ($res) $res->FreeResult();
		}
		$indi["names"] = $names;
		$indi["isdead"] = $isdead;
		$indi["gedcom"] = $indirec;
		$indi["gedfile"] = $GEDCOMS[$FILE]["id"];
		$indi["sex"] = GetGedcomValue("SEX", 1, $indirec, '', false);
		if ($USE_RIN) {
			$ct = preg_match("/1 RIN (.*)/", $indirec, $match);
			if ($ct>0) $rin = trim($match[1]);
			else $rin = $gid;
			$indi["rin"] = $rin;
		}
		else $indi["rin"] = $gid;
		
		$sql = "INSERT INTO ".$TBLPREFIX."individuals VALUES ('".$kgid."', '".$DBCONN->EscapeQuery($gid)."','".$DBCONN->EscapeQuery($indi["gedfile"])."','".$DBCONN->EscapeQuery($indi["rin"])."', -1,'".$DBCONN->EscapeQuery($indi["gedcom"])."','".$indi["sex"]."')";
		$res = NewQuery($sql);
		if ($res) $res->FreeResult();
		$sqlstr = "";
		$first = true;
		foreach ($soundex_codes as $stype => $ncodes) {
			foreach ($ncodes as $nametype => $tcodes) {
				foreach ($tcodes as $key => $code) {
					if (!$first) $sqlstr .= ", ";
					$first = false;
					$sqlstr .= "(NULL, '".$kgid."', '".$GEDCOMID."', '".$stype."', '".$nametype."', '".$code."')";
				}
			}
		}
		if (!empty($sqlstr)) {
			$sql = "INSERT INTO ".$TBLPREFIX."soundex VALUES ".$sqlstr;
			$res = NewQuery($sql);
			if ($res) $res->FreeResult();
		}
		else WriteToLog("Import->Soundex: Indo without soundex codes encountered: ".$kgid, "W", "G", $GEDCOM);
	}
	else if ($type == "FAM") {
		$indirec = CleanupTagsY($indirec);
		$parents = array();
		$ct = preg_match("/1 HUSB @(.*)@/", $indirec, $match);
		if ($ct>0) $parents["HUSB"]=$match[1];
		else $parents["HUSB"]=false;
		$ct = preg_match("/1 WIFE @(.*)@/", $indirec, $match);
		if ($ct>0) $parents["WIFE"]=$match[1];
		else $parents["WIFE"]=false;
		$ct = preg_match_all("/\d CHIL @(.*)@/", $indirec, $match, PREG_SET_ORDER);
		$chil = "";
		// NOTE: only the children are added/updated here.
		for($j=0; $j<$ct; $j++) {
			$chil .= $match[$j][1].";";
			$sql = "INSERT INTO ".$TBLPREFIX."individual_family VALUES(NULL, '".Joinkey($match[$j][1], $GEDCOMID)."', '".JoinKey($DBCONN->EscapeQuery($gid), $GEDCOMID)."', '".($j+1)."', 'C', '', '', '', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."') ON DUPLICATE KEY UPDATE if_order='".($j+1)."'";
			$res = NewQuery($sql);
		}
		$fam = array();
		$fam["HUSB"] = $parents["HUSB"];
		$fam["WIFE"] = $parents["WIFE"];
		$fam["CHIL"] = $chil;
		$fam["gedcom"] = $indirec;
		$fam["gedfile"] = $GEDCOMS[$FILE]["id"];
		$sql = "INSERT INTO ".$TBLPREFIX."families (f_key, f_id, f_file, f_husb, f_wife, f_chil, f_gedcom, f_numchil) VALUES ('".$DBCONN->EscapeQuery($gid)."[".$DBCONN->EscapeQuery($fam["gedfile"])."]','".$DBCONN->EscapeQuery($gid)."','".$DBCONN->EscapeQuery($fam["gedfile"])."','".$DBCONN->EscapeQuery($fam["HUSB"])."','".$DBCONN->EscapeQuery($fam["WIFE"])."','".$DBCONN->EscapeQuery($fam["CHIL"])."','".$DBCONN->EscapeQuery($fam["gedcom"])."','".$DBCONN->EscapeQuery($ct)."')";
		$res = NewQuery($sql);
		if ($res) $res->FreeResult();
	}
	else if ($type=="SOUR") {
		$et = preg_match("/1 ABBR (.*)/", $indirec, $smatch);
		if ($et>0) $name = $smatch[1];
		$tt = preg_match("/1 TITL (.*)/", $indirec, $smatch);
		if ($tt>0) $name = $smatch[1];
		if (empty($name)) $name = $gid;
		$subindi = preg_split("/1 TITL /",$indirec);
		if (count($subindi)>1) {
			$pos = strpos($subindi[1], "\n1", 0);
			if ($pos) $subindi[1] = substr($subindi[1],0,$pos);
			$ct = preg_match_all("/2 CON[C|T] (.*)/", $subindi[1], $match, PREG_SET_ORDER);
			for($i=0; $i<$ct; $i++) {
				$name = trim($name);
				if ($WORD_WRAPPED_NOTES) $name .= " ".$match[$i][1];
				else $name .= $match[$i][1];
			}
		}
		$sql = "INSERT INTO ".$TBLPREFIX."sources VALUES ('".$DBCONN->EscapeQuery($gid)."','".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."','".$DBCONN->EscapeQuery($name)."','".$DBCONN->EscapeQuery($indirec)."')";
		$res = NewQuery($sql);
		if ($res) $res->FreeResult();
	}
	else if ($type=="OBJE") {
		//-- don't duplicate OBJE records
		//-- OBJE records are imported by UpdateMedia function
	}
	else if (preg_match("/_/", $type)==0) {
		if ($type=="HEAD") {
			$ct=preg_match("/1 DATE (.*)/", $indirec, $match);
			if ($ct == 0) {
				$indirec = trim($indirec);
				$indirec .= "\r\n1 DATE ".date("d")." ".date("M")." ".date("Y");
			}
		}
		$sql = "INSERT INTO ".$TBLPREFIX."other VALUES ('".$DBCONN->EscapeQuery($gid)."','".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."','".$DBCONN->EscapeQuery($type)."','".$DBCONN->EscapeQuery($indirec)."')";
		$res = NewQuery($sql);
		if ($res) $res->FreeResult();
	}
	return $gid;
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
	global $TBLPREFIX, $USE_RIN, $indilist, $DBCONN;
	$isdead = 0;
	$isdead = IsDead($indi["gedcom"]);
	if (empty($isdead)) $isdead = 0;
	$sql = "UPDATE ".$TBLPREFIX."individuals SET i_isdead=$isdead WHERE i_id LIKE '".$DBCONN->EscapeQuery($gid)."' AND i_file='".$DBCONN->EscapeQuery($indi["gedfile"])."'";
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
	global $TBLPREFIX, $GEDCOMID, $DBCONN;
	
	$sql = "UPDATE ".$TBLPREFIX."individuals SET i_isdead=-1 WHERE i_file='".$GEDCOMID."'";
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
	global $TBLPREFIX, $GEDCOMID, $DBCONN;
	
	$resets = array();
	if ($type == "FAM") {
		$sql = "SELECT if_pkey FROM ".$TBLPREFIX."individual_family WHERE if_role='S' AND if_fkey='".JoinKey($pid, $GEDCOMID)."'";
		$res =  NewQuery($sql);
		while ($row = $res->FetchRow()) {
			$resets[] = $row[0];
		}
	}
	
	if ($type == "INDI") {
		$parents = array();
	
		// Get the ID's in the surrounding families. Also save the parents ID's for getting the grandparents
		$sql = "SELECT n.if_pkey, n.if_role, m.if_role FROM ".$TBLPREFIX."individual_family as m LEFT JOIN ".$TBLPREFIX."individual_family as n ON n.if_fkey=m.if_fkey WHERE m.if_pkey='".$DBCONN->EscapeQuery(JoinKey($pid, $GEDCOMID))."' AND n.if_pkey<>m.if_pkey";
		$res = NewQuery($sql);
		
		while ($row = $res->FetchRow()) {
			$resets[] = $row[0];
			if ($row[1] == "S" && $row[2] == "C") $parents[] = $row[0];
		}
		
		// Get the grandparents
		if (count($parents) > 0) {
			$listfams = "'".implode("', '", $parents)."'";
			$sql = "SELECT n.if_pkey, n.if_role, m.if_role FROM ".$TBLPREFIX."individual_family as m LEFT JOIN ".$TBLPREFIX."individual_family as n ON n.if_fkey=m.if_fkey WHERE m.if_pkey IN (".$listfams.") AND m.if_role='C' AND n.if_role='S'";
			$res = NewQuery($sql);
			while ($row = $res->FetchRow()) {
				$resets[] = $row[0];
			}
		}
		
		// Now reset the isdead status for these individuals
		if (count($resets) > 0) {
			$sql = "UPDATE ".$TBLPREFIX."individuals SET i_isdead='-1' WHERE i_key IN ('".implode("', '", $resets)."')";
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
	global $TBLPREFIX, $USE_RIN, $indilist, $FILE, $DBCONN, $GEDCOMS, $GEDCOMID;

	$indilist[$gid]["names"][] = array($newname, $letter, $surname, 'C');
	$indilist[$gid]["gedcom"] = $indirec;
	$kgid = JoinKey($gid, $GEDCOMID);
	$sql = "INSERT INTO ".$TBLPREFIX."names VALUES('".$DBCONN->EscapeQuery($kgid)."','".$DBCONN->EscapeQuery($gid)."','".$GEDCOMID."','".$DBCONN->EscapeQuery($newname)."','".$DBCONN->EscapeQuery($letter)."','".$DBCONN->EscapeQuery($surname)."','C')";
	$res = NewQuery($sql);
	
	$soundex_codes = GetSoundexStrings($indilist[$gid]["names"], false, $indirec);
	$sql = "DELETE FROM ".$TBLPREFIX."soundex WHERE s_gid='".$kgid."'";
	$sql = "INSERT INTO ".$TBLPREFIX."soundex VALUES ";
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
	$sql = "UPDATE ".$TBLPREFIX."individuals SET i_gedcom='".$DBCONN->EscapeQuery($indirec)."' WHERE i_id='".$DBCONN->EscapeQuery($gid)."' AND i_file='".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."'";
	$res = NewQuery($sql);
}

/**
 * extract all places from the given record and insert them
 * into the places table
 * @param string $indirec
 */
function UpdatePlaces($gid, $indirec, $update=false) {
	global $FILE, $placecache, $TBLPREFIX, $DBCONN, $GEDCOMS;
// NOTE: $update=false causes double places to be added. Force true
$update = true;
	if (!isset($placecache)) $placecache = array();
	//-- import all place locations
	$pt = preg_match_all("/\d PLAC (.*)/", $indirec, $match, PREG_SET_ORDER);
	for($i=0; $i<$pt; $i++) {
		$place = trim($match[$i][1]);
		// Split on chinese comma 239 188 140
		$place = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $place);
		$places = preg_split("/,/", $place);
		$secalp = array_reverse($places);
		$parent_id = 0;
		$level = 0;
		foreach($secalp as $indexval => $place) {
			$place = trim($place);
			$place=preg_replace('/\\\"/', "", $place);
			$place=preg_replace("/[\><]/", "", $place);
			if (empty($parent_id)) $parent_id=0;
			$key = strtolower($place."_".$level."_".$parent_id);
			$addgid = true;
			if (isset($placecache[$key])) {
				$parent_id = $placecache[$key][0];
				if (strpos($placecache[$key][1], $gid.",")===false) {
					$placecache[$key][1] = "$gid,".$placecache[$key][1];
					$sql = "INSERT INTO ".$TBLPREFIX."placelinks VALUES($parent_id, '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."')";
					$res = NewQuery($sql);
				}
			}
			else {
				$skip = false;
				if ($update) {
//					print "Search: ".$place." ".$level."<br />";
					$sql = "SELECT p_id FROM ".$TBLPREFIX."places WHERE p_place LIKE '".$DBCONN->EscapeQuery($place)."' AND p_level=$level AND p_parent_id='$parent_id' AND p_file='".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."'";
					$res = NewQuery($sql);
					if ($res->NumRows()>0) {
//						if ($level == 0) print "Hit on: ".$place." ".$level."<br />";
						$row = $res->FetchAssoc();
						$res->FreeResult();
						$parent_id = $row["p_id"];
						$skip=true;
						$placecache[$key] = array($parent_id, $gid.",");
						$sql = "INSERT INTO ".$TBLPREFIX."placelinks VALUES($parent_id, '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."')";
						$res = NewQuery($sql);
					}
				}
				if (!$skip) {
					if (!isset($place_id)) {
						$place_id = GetNextId("places", "p_id");
					}
					else $place_id++;
//					if ($level == 0) print "Insert: ".$place." ".$level."<br />";
					$sql = "INSERT INTO ".$TBLPREFIX."places VALUES($place_id, '".$DBCONN->EscapeQuery($place)."', $level, '$parent_id', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."')";
					$res = NewQuery($sql);
					$parent_id = $place_id;
					$placecache[$key] = array($parent_id, $gid.",");
					$sql = "INSERT INTO ".$TBLPREFIX."placelinks VALUES($place_id, '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."')";
					$res = NewQuery($sql);
				}
			}
			$level++;
		}
	}
	return $pt;
}

/**
 * extract all date info from the given record and insert them
 * into the dates table
 * @param string $indirec
 */
function UpdateDates($gid, $indirec) {
	global $FILE, $TBLPREFIX, $DBCONN, $GEDCOMS, $GEDCOMID;
	
	$count = 0;
	// NOTE: Check if the record has dates, if not return
	$pt = preg_match("/\d DATE (.*)/", $indirec, $match);
	if ($pt==0) return 0;
	
	// NOTE: Get all facts
	preg_match_all("/(\d)\s(\w+)\r\n/", $indirec, $facts, PREG_SET_ORDER);
	
	$fact_count = array();
	// NOTE: Get all the level 1 records
	foreach($facts as $key => $subfact) {
		$fact = $subfact[2];
		
		if (!isset($fact_count[$fact])) $fact_count[$fact] = 1;
		else $fact_count[$fact]++;
		$subrec = GetSubRecord($subfact[1], $fact, $indirec, $fact_count[$fact]);
		$count_dates = preg_match("/\d DATE (.*)/", $subrec, $dates);
		if ($count_dates > 0) {
			$datestr = trim($dates[1]);
			$date = ParseDate($datestr);
			if (empty($date[0]["day"])) $date[0]["day"] = 0;
			$sql = "INSERT INTO ".$TBLPREFIX."dates VALUES('".$DBCONN->EscapeQuery($date[0]["day"])."','".$DBCONN->EscapeQuery(Str2Upper($date[0]["month"]))."','".$DBCONN->EscapeQuery($date[0]["year"])."','".$DBCONN->EscapeQuery($fact)."','".$DBCONN->EscapeQuery($gid)."','".$DBCONN->EscapeQuery(JoinKey($gid, $GEDCOMID))."','".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."',";
			if (isset($date[0]["ext"])) {
				preg_match("/@#D(.*)@/", $date[0]["ext"], $extract_type);
				$date_types = array("@#DGREGORIAN@","@#DJULIAN@","@#DHEBREW@","@#DFRENCH R@", "@#DROMAN@", "@#DUNKNOWN@");
				if (isset($extract_type[0]) && in_array($extract_type[0], $date_types)) $sql .= "'".$extract_type[0]."')";
				else $sql .= "NULL)";
			}
			else $sql .= "NULL)";
			$res = NewQuery($sql);
			$count++;
		}
	}
	return $count;
}

/**
 * import media items from record
 * @return string	an updated record
 */
function UpdateMedia($gid, $indirec, $update=false) {
	global $GEDCOMS, $FILE, $TBLPREFIX, $DBCONN, $MEDIA_ID_PREFIX, $media_count, $found_ids, $MediaFS;
	global $zero_level_media;
	
	if (!isset($media_count)) $media_count = 0;
	if (!isset($found_ids)) $found_ids = array();
	if (!isset($zero_level_media)) $zero_level_media = false;
	
	// Get the type of record we have here
	$ct = preg_match("/0 @.+@ (\w+)/", $indirec, $tmatch);
	if ($ct) $rectype = $tmatch[1];
	else {
		$r = substr($indirec, 0, 6);
		if ($r != "0 HEAD" && $r != "0 TRLR") WriteToLog("UpdateMedia-> Unknown record type encountered on import: ".$indirec, "E", "G", $FILE);
		return $indirec;
	}
	
	//-- handle level 0 media OBJE seperately
	$ct = preg_match("/0 @(.*)@ OBJE/", $indirec, $match);
	if ($ct>0) {
		$old_m_media = $match[1];
		$found = false;
		// If it's an update from edit, the ID does not change.
		if ($update) {
			$new_m_media = $old_m_media;
		}
		else {
			// It's a new record. If we already assigned a new ID, set it here.
			if (array_key_exists($match[1], $found_ids)) {
				$new_m_media = $found_ids[$match[1]]["new_id"];
				$found = true;
			}
			else {
				// If not, get a new ID
				// Check if the own ID is already assigned
				$exist = false;
				foreach($found_ids as $key => $id) {
					if ($id["new_id"] == $match[1]) {
						$exist = true;
						break;
					}
				}
				// If not, keep the old ID. If assigned, generate a new one						
				if ($exist) $new_m_media = GetNewXref("OBJE");
				else $new_m_media = $match[1];
				$found_ids[$match[1]]["old_id"] = $match[1];
				$found_ids[$match[1]]["new_id"] = $new_m_media;
			}
		}
		// Change the ID of the mediarecord and get some field values
		$indirec = preg_replace("/@".$old_m_media."@/", "@".$new_m_media."@", $indirec);
		$title = GetGedcomValue("TITL", 2, $indirec);
		if (strlen(trim($title)) == 0) $title = GetGedcomValue("TITL", 1, $indirec);
		$file = GetGedcomValue("FILE", 1, $indirec);
		// If the file is a link, normalize it
		if (stristr($file, "://")) $file = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$file);
		// Eliminate a heading dot from the filename
		$file = RelativePathFile($MediaFS->CheckMediaDepth($file));
		// Get the extension
		$et = preg_match("/(\.\w+)$/", $file, $ematch);
		$ext = "";
		if ($et>0) $ext = substr(trim($ematch[1]),1);
		if ($found) {
			// It's the actual values for an inserted stub record. We only update the fields with the true values
			$sql = "UPDATE ".$TBLPREFIX."media SET m_ext = '".$DBCONN->EscapeQuery($ext)."', m_titl = '".$DBCONN->EscapeQuery($title)."', m_file = '".$DBCONN->EscapeQuery($file)."', m_gedrec = '".$DBCONN->EscapeQuery($indirec)."' WHERE m_media = '".$new_m_media."' AND m_gedfile='".$GEDCOMS[$FILE]["id"]."'";
			$res = NewQuery($sql);
		}
		else {
			// It's completely new, we insert a new record
			$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
			$sql .= " VALUES('0', '".$DBCONN->EscapeQuery($new_m_media)."', '".$DBCONN->EscapeQuery($ext)."', '".$DBCONN->EscapeQuery($title)."', '".$DBCONN->EscapeQuery($file)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."', '".$DBCONN->EscapeQuery($indirec)."')";
			$res = NewQuery($sql);
		}
		$found = false;
		return $indirec;
	}
	
	// Here we handle all records BUT level 0 media records.
	//-- check to see if there are any media records
	//-- if there aren't any media records then don't look for them just return
	$pt = preg_match("/\d OBJE/", $indirec, $match);
	if ($pt==0) return $indirec;
	//-- go through all of the lines and replace any local
	//--- OBJE to referenced OBJEs
	$newrec = "";
	$lines = preg_split("/[\r\n]+/", trim($indirec));
	$ct_lines = count($lines);
	$inobj = false;
	$processed = false;
	$objlevel = 0;
	$objrec = "";
	$count = 1;
	foreach($lines as $key => $line) {
		if (!empty($line)) {
			// NOTE: Match lines that resemble n OBJE @0000@
			// NOTE: Renumber the old ID to a new ID and save the old ID
			// NOTE: in case there are more references to it
			if (preg_match("/^[1-9]\sOBJE\s(.*)$/", $line, $match) != 0) {
				// NOTE: Check if objlevel greater is than 0, if so then store the current object record
				if ($objlevel > 0) {
					$title = GetGedcomValue("TITL", $objlevel+1, $objrec);
					if (strlen(trim($title)) == 0) $title = GetGedcomValue("TITL", $objlevel+2, $objrec);
					$file = GetGedcomValue("FILE", $objlevel+1, $objrec);
					// If the file is a link, normalize it
					if (stristr($file, "://")) $file = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$file);
					$file = RelativePathFile($MediaFS->CheckMediaDepth($file));
					
					// Add a check for existing file here
					$em = CheckDoubleMedia($file, $title, $GEDCOMS[$FILE]["id"]);
					if (!$em) $m_media = GetNewXref("OBJE");
					else $m_media = $em;
					
					// Get the extension
					$et = preg_match("/(\.\w+)$/", $file, $ematch);
					$ext = "";
					if ($et>0) $ext = substr(trim($ematch[1]),1);
					// NOTE: Make sure 1 OBJE @M1@ is treated correctly
					if (preg_match("/\d+\s\w+\s@(.*)@/", $objrec) > 0) $objrec = preg_replace("/@(.*)@/", "@".$m_media."@", $objrec);
					else $objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
					$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
					
					// Add the PRIM and THUM tags to the mapping
					$r = GetSubRecord($objlevel, $line, $indirec);
					$rlevel = $objlevel+1;
					$prim = trim(GetSubRecord($rlevel, $rlevel." _PRIM", $r));
					$thum = trim(GetSubRecord($rlevel, $rlevel." _THUM", $r));
					$add = "\r\n";
					if (!empty($prim)) {
						$rec = $objlevel." ".$prim."\r\n";
						$add .= $rlevel." ".$prim."\r\n";
						$objrec = preg_replace("/$rec/", "", $objrec);
					}
					if (!empty($thum)) {
						$rec = $objlevel." ".$thum."\r\n";
						$add .= $rlevel." ".$thum."\r\n";
						$objrec = preg_replace("/$rec/", "", $objrec);
					}
					if (!$em) {
						$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
						$sql .= " VALUES('0', '".$DBCONN->EscapeQuery($m_media)."', '".$DBCONN->EscapeQuery($ext)."', '".$DBCONN->EscapeQuery($title)."', '".$DBCONN->EscapeQuery($file)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."', '".$DBCONN->EscapeQuery($objrec)."')";
						$res = NewQuery($sql);
					}
					$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec, mm_type)";
					$sql .= " VALUES ('0', '".$DBCONN->EscapeQuery($m_media)."', '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($count)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]['id'])."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@'.$add)."', '".$rectype."')";
					$res = NewQuery($sql);
					$media_count++;
					$count++;
					// NOTE: Add the new media object to the record
					$newrec .= $objlevel." OBJE @".$m_media."@".$add."\r\n";
					
					// NOTE: Set the details for the next media record
					$objlevel = $match[0]{0};
					$inobj = true;
					$objrec = $line."\r\n";
				}
				else {
					// NOTE: Set object level
					$objlevel = $match[0]{0};
					$inobj = true;
					$objrec = trim($line)."\r\n";
				}
				// NOTE: Look for the @M00@ reference
				if (stristr($match[1], "@") !== false) {
					// NOTE: Retrieve the old media ID
					$old_mm_media = preg_replace("/@/", "", $match[1]);
					// NOTE: Check if the id already exists and there is a value behind OBJE (n OBJE @M001@)
					if (!array_key_exists($old_mm_media, $found_ids) && !empty($match[1])) {
						//-- use the old id if we are updating from an online edit
						if ($update) {
							$new_mm_media = $old_mm_media;
						}
						else {
							// NOTE: Get a new media ID
							$new_mm_media = GetNewXref("OBJE");
						}
						// NOTE: Put both IDs in the found_ids array in case we later find the 0-level
						// NOTE: The 0-level ID will have to be changed also
						$found_ids[$old_mm_media]["old_id"] = $old_mm_media;
						$found_ids[$old_mm_media]["new_id"] = $new_mm_media;
						
						if (!$update) {
							// NOTE: We found a media reference but no media item yet, we need to create an empty
							// NOTE: media object, so we do not have orhpaned media mapping links
							$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
							$sql .= " VALUES('0', '".$DBCONN->EscapeQuery($new_mm_media)."', '', '', '', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."', '0 @".$DBCONN->EscapeQuery($new_mm_media)."@ OBJE\r\n')";
							$res = NewQuery($sql);
							
							// NOTE: Add the mapping to the media reference
							// The above code "forgets" all subrecords like THUM and PRIM. We therefore get the whole subrecord from the indirec.
							$gedrec = GetSubRecord($objlevel, $line, $indirec);
							$gedrec = preg_replace("/@(.*)@/", "@$new_mm_media@", $gedrec);
							$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
							$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec, mm_type) ";
							$sql .= "VALUES ('0', '".$DBCONN->EscapeQuery($new_mm_media)."', '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($count)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]['id'])."', '".$gedrec."', '".$rectype."')";
							$res = NewQuery($sql);
						}
						else {
							// NOTE: This is an online update. Let's see if we already have a media mapping for this item
							$sql = "SELECT mm_media FROM ".$TBLPREFIX."media_mapping WHERE mm_media = '".$new_mm_media."' AND mm_gedfile = '".$GEDCOMS[$FILE]["id"]."'";
							$res = NewQuery($sql);
							$row = $res->FetchAssoc();
							if (count($row) == 0) {
								$gedrec = GetSubRecord($objlevel, $line, $indirec); // Added
								$gedrec = preg_replace("/@(.*)@/", "@$new_mm_media@", $gedrec); // Added
								// NOTE: Add the mapping to the media reference
								$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
								$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec, mm_type) ";
								$sql .= "VALUES ('0', '".$DBCONN->EscapeQuery($new_mm_media)."', '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($count)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]['id'])."', '".$gedrec."', '".$rectype."')";
								$res = NewQuery($sql);
							}
						}
					}
					else if (array_key_exists($old_mm_media, $found_ids) && !empty($match[1])) {

						$new_mm_media = $found_ids[$old_mm_media]["new_id"];
						if (!$update) {
							$gedrec = GetSubRecord($objlevel, $line, $indirec);
							$gedrec = preg_replace("/@(.*)@/", "@$new_mm_media@", $gedrec);
							$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
							$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec, mm_type) ";
							$sql .= "VALUES ('0', '".$DBCONN->EscapeQuery($new_mm_media)."', '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($count)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]['id'])."', '".$gedrec."', '".$rectype."')";
							$res = NewQuery($sql);
						}
						else {
							// NOTE: This is an online update. Let's see if we already have a media mapping for this item
							$sql = "SELECT mm_media FROM ".$TBLPREFIX."media_mapping WHERE mm_media = '".$new_mm_media."' AND mm_gedfile = '".$GEDCOMS[$FILE]["id"]."'";
							$res = NewQuery($sql);
							$row = $res->FetchAssoc();
							if (count($row) == 0) {
								// NOTE: Add the mapping to the media reference
								$line = preg_replace("/@(.+)@/", "@$new_mm_media@", $line);
								$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec, mm_type) ";
								$sql .= "VALUES ('0', '".$DBCONN->EscapeQuery($new_mm_media)."', '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($count)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]['id'])."', '".$line."', '".$rectype."')";
								$res = NewQuery($sql);
							}
						}
					}
					$media_count++;
					$count++;
					$objlevel = 0;
					$objrec = "";
					$inobj = false;
				}
			}
			// NOTE: Match lines 0 @0000@ OBJE
			else if (preg_match("/^[1-9]\sOBJE$/", $line, $match)) {
				if (!empty($objrec)) {
					$title = GetGedcomValue("TITL", $objlevel+1, $objrec);
					if (strlen(trim($title)) == 0) $title = GetGedcomValue("TITL", $objlevel+2, $objrec);
					$file = GetGedcomValue("FILE", $objlevel+1, $objrec);
					// If the file is a link, normalize it
					if (stristr($file, "://")) $file = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$file);
					$file = RelativePathFile($MediaFS->CheckMediaDepth($file));
					
					// Add a check for existing file here
					$em = CheckDoubleMedia($file, $title, $GEDCOMS[$FILE]["id"]);
					if (!$em) $m_media = GetNewXref("OBJE");
					else $m_media = $em;

					// Get the extension
					$et = preg_match("/(\.\w+)$/", $file, $ematch);
					$ext = "";
					if ($et>0) $ext = substr(trim($ematch[1]),1);
					
					// Add the PRIM and THUM tags to the mapping
					$prim = trim(GetSubRecord($objlevel+1, " _PRIM", $objrec));						
					$thum = trim(GetSubRecord($objlevel+1, " _THUM", $objrec));						
					$add = "\r\n";
					$rlevel = $objlevel+1;
					if (!empty($prim)) {
						$rec = $rlevel." ".$prim."\r\n";
						$add .= $rec;
						$objrec = preg_replace("/$rec/", "", $objrec);
					}
					if (!empty($thum)) {
						$rec = $rlevel." ".$thum."\r\n";
						$add .= $rec;
						$objrec = preg_replace("/$rec/", "", $objrec);
					}
					
					$objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
					$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
					if (!$em) {
						$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
						$sql .= " VALUES('0', '".$DBCONN->EscapeQuery($m_media)."', '".$DBCONN->EscapeQuery($ext)."', '".$DBCONN->EscapeQuery($title)."', '".$DBCONN->EscapeQuery($file)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."', '".$DBCONN->EscapeQuery($objrec)."')";
						$res = NewQuery($sql);
					}

					
					$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec, mm_type)";
					$sql .= " VALUES ('0', '".$DBCONN->EscapeQuery($m_media)."', '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($count)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]['id'])."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@'.$add)."', '".$rectype."')";
					$res = NewQuery($sql);
					$media_count++;
					$count++;
					// NOTE: Add the new media object to the record
					$newrec .= $objlevel." OBJE @".$m_media."@".$add."\r\n";
				}
				// NOTE: Set the details for the next media record
				$objlevel = $match[0]{0};
				$inobj = true;
				$objrec = $line."\r\n";
			}
			else {
				$ct = preg_match("/(\d+)\s(\w+)(.*)/", $line, $match);
				if ($ct > 0) {
					$level = $match[1];
					$fact = $match[2];
					$desc = trim($match[3]);
					if ($inobj && ($level<=$objlevel || $key == $ct_lines-1)) {
						if ($key == $ct_lines-1 && $level>$objlevel) {
							$objrec .= $line."\r\n";
						}
						$title = GetGedcomValue("TITL", $objlevel+1, $objrec);
						if (strlen(trim($title)) == 0) $title = GetGedcomValue("TITL", $objlevel+2, $objrec);
						$file = GetGedcomValue("FILE", $objlevel+1, $objrec);
						// If the file is a link, normalize it
						if (stristr($file, "://")) $file = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$file);
						$file = RelativePathFile($MediaFS->CheckMediaDepth($file));
						// Get the extension
						$et = preg_match("/(\.\w+)$/", $file, $ematch);
						$ext = "";
						if ($et>0) $ext = substr(trim($ematch[1]),1);
						if ($objrec{0} != 0) {
							
							// Add a check for existing file here
							$em = CheckDoubleMedia($file, $title, $GEDCOMS[$FILE]["id"]);
							if (!$em) $m_media = GetNewXref("OBJE");
							else $m_media = $em;
							
							if (preg_match("/^\d+\s\w+\s@(.*)@/", $objrec) > 0) {
								$objrec = preg_replace("/@(.*)@/", "@".$m_media."@", $objrec);
							}
							else $objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
							$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
							
							// Add the PRIM and THUM tags to the mapping
							$prim = trim(GetSubRecord($objlevel, " _PRIM", $objrec));
							$thum = trim(GetSubRecord($objlevel, " _THUM", $objrec));
							$add = "\r\n";
							$rlevel = $objlevel+1;
							if (!empty($prim)) {
								$rec = $objlevel." ".$prim."\r\n";
								$add .= $rlevel." ".$prim."\r\n";
								$objrec = preg_replace("/$rec/", "", $objrec);
							}
							if (!empty($thum)) {
								$rec = $objlevel." ".$thum."\r\n";
								$add .= $rlevel." ".$thum."\r\n";
								$objrec = preg_replace("/$rec/", "", $objrec);
							}

							if (!$em) {
								$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
								$sql .= " VALUES('0', '".$DBCONN->EscapeQuery($m_media)."', '".$DBCONN->EscapeQuery($ext)."', '".$DBCONN->EscapeQuery($title)."', '".$DBCONN->EscapeQuery($file)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"])."', '".$DBCONN->EscapeQuery($objrec)."')";
								$res = NewQuery($sql);
							}
							$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec, mm_type)";
							$sql .= " VALUES ('0', '".$DBCONN->EscapeQuery($m_media)."', '".$DBCONN->EscapeQuery($gid)."', '".$DBCONN->EscapeQuery($count)."', '".$DBCONN->EscapeQuery($GEDCOMS[$FILE]['id'])."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@'.$add)."', '".$rectype."')";
							$res = NewQuery($sql);
						}
						else {
							$oldid = preg_match("/0\s@(.*)@\sOBJE/", $objrec, $newmatch);
							$m_media = $newmatch[1];
							$sql = "UPDATE ".$TBLPREFIX."media SET m_ext = '".$DBCONN->EscapeQuery($ext)."', m_titl = '".$DBCONN->EscapeQuery($title)."', m_file = '".$DBCONN->EscapeQuery($file)."', m_gedrec = '".$DBCONN->EscapeQuery($objrec)."' WHERE m_media = '".$m_media."'";
							$res = NewQuery($sql);
						}
						$media_count++;
						$count++;
						$objrec = "";
						if ($key == $ct_lines-1 && $level>$objlevel) {
							$line = $objlevel." OBJE @".$m_media."@".$add;
						}
						else {
							$line = $objlevel." OBJE @".$m_media."@\r\n".$line;
						}
						$inobj = false;
						$objlevel = 0;
					}
					else {
						if ($inobj) $objrec .= $line."\r\n";
					}
					if ($fact=="OBJE") {
						$inobj = true;
						$objlevel = $level;
						$objrec = "";
					}
				}
			}
			if (!$inobj && !empty($line)) {
				$newrec .= $line."\r\n";
			}
		}
	}
	return $newrec;
}

/*
* Return the media ID based on the FILE and TITLE value, depending on the gedcom setting
* If check for existing media is disabled, return false.
*/
function CheckDoubleMedia($file, $title, $gedid) {
	global $MERGE_DOUBLE_MEDIA, $TBLPREFIX, $DBCONN;
	
	if ($MERGE_DOUBLE_MEDIA == 0) return false;
	
	$sql = "SELECT m_media FROM ".$TBLPREFIX."media WHERE m_gedfile='".$gedid."' AND m_file LIKE '".$DBCONN->EscapeQuery($file)."'";
	if ($MERGE_DOUBLE_MEDIA == "2") $sql .= " AND m_titl LIKE '".$DBCONN->EscapeQuery($title)."'";
	$res = NewQuery($sql);
	if ($res->NumRows() == 0) return false;
	else {
		$row = $res->FetchRow();
		return $row[0];
	}
}


/**
 * delete a gedcom from the database
 *
 * deletes all of the imported data about a gedcom from the database
 * @param string $FILE	the gedcom to remove from the database
 */
function EmptyDatabase($FILE) {
	global $TBLPREFIX, $DBCONN, $GEDCOMS;

	$FILE = $DBCONN->EscapeQuery($GEDCOMS[$FILE]["id"]);
	$sql = "DELETE FROM ".$TBLPREFIX."individuals WHERE i_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."families WHERE f_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."sources WHERE s_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."source_mapping WHERE sm_gedfile='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."other WHERE o_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."other_mapping WHERE om_gedfile='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."places WHERE p_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."placelinks WHERE pl_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."names WHERE n_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."dates WHERE d_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."media WHERE m_gedfile='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."individual_family WHERE if_file='$FILE'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."soundex WHERE s_file='$FILE'";
	$res = NewQuery($sql);
	// Flush the caches
	ResetCaches(get_gedcom_from_id($FILE));
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
	global $TBLPREFIX, $DBCONN;

	$sourcelist = array();

 	$sql = "SELECT s_id, s_file, s_file as s_name, s_gedcom FROM ".$TBLPREFIX."sources WHERE s_file='".$GEDCOMID."' and ((s_gedcom LIKE '% _HEB %') || (s_gedcom LIKE '% ROMN %'));";

	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$source = array();
		$row = db_cleanup($row);
		$ct = preg_match("/\d ROMN (.*)/", $row["s_gedcom"], $match);
 		if ($ct==0) $ct = preg_match("/\d _HEB (.*)/", $row["s_gedcom"], $match);
		$source["name"] = $match[1];
		$source["gedcom"] = $row["s_gedcom"];
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
	global $TBLPREFIX, $DBCONN;

	$sourcelist = array();

	$sql = "SELECT * FROM ".$TBLPREFIX."sources WHERE s_file='".$GEDCOMID."'";
	if (!empty($selection)) $sql .= " AND s_id IN (".$selection.") ";
	$sql .= " ORDER BY s_name";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$source = array();
		$source["name"] = $row["s_name"];
		$source["gedcom"] = $row["s_gedcom"];
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
	global $GEDCOMID, $Users, $Actions, $gm_username;
	global $TBLPREFIX, $DBCONN;
	
	$repolist = array();
	$repoaction = array();
	if (is_object($Actions)) {
		$actionlist = $Actions->GetActionList();
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
	}

	$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_file='".$GEDCOMID."' AND o_type='REPO'";
	if (!empty($filter)) $sql .= " AND o_gedcom LIKE '%".$DBCONN->EscapeQuery($filter)."%'";
	if (!empty($selection)) $sql .= "AND o_id IN (".$selection.") ";
	$resr = NewQuery($sql);
	$ct = $resr->NumRows();
	while($row = $resr->FetchAssoc()){
		$repo = array();
		$tt = preg_match("/1 NAME (.*)/", $row["o_gedcom"], $match);
		if ($tt == "0") $name = $row["o_id"]; else $name = trim($match[1]);
		$repo["id"] = $row["o_id"];
		$repo["gedfile"] = $row["o_file"];
		$repo["type"] = $row["o_type"];
		$repo["gedcom"] = $row["o_gedcom"];
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
	global $GEDCOMID, $TBLPREFIX, $DBCONN;

	$repo_id_list = array();

	$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_file='".$GEDCOMID."' AND o_type='REPO' ORDER BY o_id";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$repo = array();
		$tt = preg_match("/1 NAME (.*)/", $row["o_gedcom"], $match);
		if ($tt>0) $repo["name"] = $match[1];
		else $repo["name"] = "";
		$repo["gedfile"] = $row["o_file"];
		$repo["type"] = $row["o_type"];
		$repo["gedcom"] = $row["o_gedcom"];
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
	global $GEDCOMID, $TBLPREFIX, $DBCONN, $Users, $Actions, $gm_username;

	$addrepolist = array();
	$repoaction = array();
	if (is_object($Actions)) {
		$actionlist = $Actions->GetActionList();
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
	}

 	$sql = "SELECT o_id, o_file, o_file as o_name, o_type, o_gedcom FROM ".$TBLPREFIX."other WHERE o_type='REPO' AND o_file='".$GEDCOMID."' and ((o_gedcom LIKE '% _HEB %') || (o_gedcom LIKE '% ROMN %'));";

	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$repo = array();
		$repo["gedcom"] = $row["o_gedcom"];
		$ct = preg_match("/\d ROMN (.*)/", $row["o_gedcom"], $match);
 		if ($ct==0) $ct = preg_match("/\d _HEB (.*)/", $row["o_gedcom"], $match);
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
	global $indilist, $GEDCOMID, $COMBIKEY, $GEDCOMS;
	global $TBLPREFIX, $INDILIST_RETRIEVED;
	
	if ($renew) {
		if ($INDILIST_RETRIEVED && $allgeds=="no") return $indilist;
		$indilist = array();
	}
	
	$sql = "SELECT i_key, i_gedcom, i_isdead, i_id, i_file, n_name, n_surname, n_letter, n_type ";
	$sql .= "FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE n_key=i_key ";
	if ($allgeds == "no") {
		$sql .= "AND i_file = ".$GEDCOMID." ";
		if (!empty($selection)) $sql .= "AND i_key IN (".$selection.") ";
	}
	else if (is_array($allgeds)) {
		$sql .= "AND (";
		$first = true;
		foreach ($allgeds as $key => $ged) {
			if (!$first) $sql .= " OR ";
			$sql .= "i_file='".$GEDCOMS[$gedcom]["id"]."'";
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
			$indi["gedcom"] = $row["i_gedcom"];
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
function GetAssoList($type = "all", $pid="") {
	global $assolist, $GEDCOM, $DBCONN, $GEDCOMS, $GEDCOMID;
	global $TBLPREFIX, $ASSOLIST_RETRIEVED, $COMBIKEY, $ftminwlen, $ftmaxwlen;

	if ($ASSOLIST_RETRIEVED) return $assolist;
	$assolist = array();
	
	// Get the min and max search word length
	GetFTWordLengths();

	$oldged = $GEDCOM;
	$oldgedid = $GEDCOMID;
	if (($type == "all") || ($type == "fam")) {
		if ($ftminwlen >= 4 && (empty($pid) || strlen($pid) >= $ftminwlen - 2)) {
			$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (MATCH (f_gedcom) AGAINST ('";
			if (empty($pid)) $sql .= "ASSO";
			else $sql .= "\"ASSO @".$pid."@\"";
			$sql .= "' IN BOOLEAN MODE)) AND f_file='".$GEDCOMID."'";
		}
		else {
			$sql = "SELECT f_id, f_file, f_gedcom, f_husb, f_wife FROM ".$TBLPREFIX."families WHERE f_gedcom ";
			if (empty($pid)) $sql .= "LIKE '% ASSO %'";
			else $sql .= "LIKE '% ASSO @".$pid."@%' AND f_file='".$GEDCOMID."'";
		}
			
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		while($row = $res->FetchAssoc()){
			$asso = array();
			$asso["type"] = "fam";
			$pid2 = $row["f_id"]."[".$row["f_file"]."]";
			$asso["gedcom"] = $row["f_gedcom"];
			$asso["gedfile"] = $row["f_file"];
			// Get the family names
			$GEDCOM = get_gedcom_from_id($row["f_file"]);
			$GEDCOMID = $row["f_file"];
			
			$hname = GetSortableName($row["f_husb"], "", "", true);
			$wname = GetSortableName($row["f_wife"], "", "", true);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = array();
			foreach ($hname as $hkey => $hn) {
				foreach ($wname as $wkey => $wn) {
					$name[] = $hn." + ".$wn;
					$name[] = $wn." + ".$hn;
				}
			}
			$asso["name"] = $name;
			$ca = preg_match_all("/\d ASSO @(.*)@/", $row["f_gedcom"], $match, PREG_SET_ORDER);
			for ($i=0; $i<$ca; $i++) {
				$pid = $match[$i][1]."[".$row["f_file"]."]";
				$assolist[$pid][$pid2] = $asso;
			}
			$row = db_cleanup($row);
		}
		$res->FreeResult();
	}

	if (($type == "all") || ($type == "indi")) {
		
		if ($ftminwlen >= 4 && (empty($pid) || strlen($pid) >= $ftminwlen - 2)) {
			$sql = "SELECT i_id, i_file, i_gedcom FROM ".$TBLPREFIX."individuals WHERE (MATCH (i_gedcom) AGAINST ('";
			if (empty($pid)) $sql .= "ASSO";
			else $sql .= "\"ASSO @".$pid."@\"";
			$sql .= "' IN BOOLEAN MODE)) AND i_file='".$GEDCOMID."'";
		}
		else {
		$sql = "SELECT i_id, i_file, i_gedcom FROM ".$TBLPREFIX."individuals WHERE i_gedcom ";
			if (empty($pid)) $sql .= "LIKE '% ASSO %'";
			else $sql .= "LIKE '% ASSO @".$pid."@%' AND i_file='".$GEDCOMID."'";
		}
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		while($row = $res->FetchAssoc()){
			$asso = array();
			$asso["type"] = "indi";
			$pid2 = $row["i_id"]."[".$row["i_file"]."]";
			$asso["gedcom"] = $row["i_gedcom"];
			$asso["gedfile"] = $row["i_file"];
			$asso["name"] = GetIndiNames($row["i_gedcom"]);
			$ca = preg_match_all("/\d ASSO @(.*)@/", $row["i_gedcom"], $match, PREG_SET_ORDER);
			for ($i=0; $i<$ca; $i++) {
				$pid = $match[$i][1]."[".$row["i_file"]."]";
				$assolist[$pid][$pid2] = $asso;
			}
			$row = db_cleanup($row);
		}
		$res->FreeResult();
	}
	
	$GEDCOM = $oldged;
	$GEDCOMID = $oldgedid;

	$ASSOLIST_RETRIEVED = true;
	return $assolist;
}

//-- get the famlist from the datastore
function GetFamList($allgeds="no", $selection="", $renew=true) {
	global $famlist, $GEDCOMID, $indilist, $DBCONN, $GEDCOM;
	global $TBLPREFIX, $FAMLIST_RETRIEVED, $COMBIKEY;

	if ($renew) {
		if ($FAMLIST_RETRIEVED && $allgeds=="no" && $selection=="") return $famlist;
		$famlist = array();
	}
	
	$sql = "SELECT * FROM ".$TBLPREFIX."families";
	if ($allgeds != "yes") {
		$sql .= " WHERE f_file='".$GEDCOMID."'";
		if (!empty($selection)) $sql .= " AND f_key IN (".$selection.") ";
	}
	else if (!empty($selection)) $sql .= " WHERE f_key IN (".$selection.") ";

	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$fam = array();
		$fam["gedcom"] = $row["f_gedcom"];
		$row = db_cleanup($row);
		$fam["HUSB"] = $row["f_husb"];
		$fam["WIFE"] = $row["f_wife"];
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
		$oldged = $GEDCOM;
		$oldgedid = $GEDCOMID;
		foreach ($famlist as $key => $fam) {
			if (!isset($famlist[$key]["name"])) {
				if ($COMBIKEY) {
					$GEDCOM = SplitKey($key, "ged");
					$GEDCOMID = SplitKey($key, "gedid");
				}
				$hname = GetSortableName($fam["HUSB"]);
				$wname = GetSortableName($fam["WIFE"]);
				$name = "";
				if (!empty($hname)) $name = $hname;
				else $name = "@N.N., @P.N.";
	
				if (!empty($wname)) $name .= " + ".$wname;
				else $name .= " + @N.N., @P.N.";
	
				$famlist[$key]["name"] = $name;
			}
		}
		$GEDCOM = $oldged;
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
	global $otherlist, $GEDCOMID, $DBCONN, $TBLPREFIX;

	$otherlist = array();

	$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
		$source = array();
		$source["gedcom"] = $row["o_gedcom"];
		$row = db_cleanup($row);
		$source["type"] = $row["o_type"];
		$source["gedfile"] = $row["o_file"];
		$otherlist[$row["o_id"]]= $source;
	}
	$res->FreeResult();
	return $otherlist;
}

//-- search through the gedcom records for individuals
/**
 * Search the database for individuals that match the query
 *
 * uses a regular expression to search the gedcom records of all individuals and returns an
 * array list of the matching individuals
 *
 * @author	Genmod Development Team
 * @param		string $query a regular expression query to search for
 * @param		boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myindilist array with all individuals that matched the query
 */
function SearchIndis($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMID, $GEDCOMS, $GEDCOM;
	
	$myindilist = array();
	if (!is_array($query)) $sql = "SELECT i_key, i_id, i_file, i_gedcom, i_isdead FROM ".$TBLPREFIX."individuals WHERE (i_gedcom REGEXP '".$DBCONN->EscapeQuery($query)."')";
	else {
		$sql = "SELECT i_key, i_id, i_file, i_gedcom, i_isdead FROM ".$TBLPREFIX."individuals WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(i_gedcom REGEXP '".$DBCONN->EscapeQuery($q)."')";
			$i++;
		}
		$sql .= ")";
	}
	if (!$allgeds) $sql .= " AND i_file='".$GEDCOMID."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		if (count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0; $i<count($allgeds); $i++) {
				$sql .= "i_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
				if ($i < count($allgeds)-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	}
	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchAssoc()){
			$row = db_cleanup($row);
			if (count($allgeds) > 1) $key = $row["i_key"];
			else $key = $row["i_id"];
			$myindilist[$key]["names"] = GetIndiNames($row["i_gedcom"]);
			$myindilist[$key]["gedfile"] = $row["i_file"];
			$myindilist[$key]["gedcom"] = $row["i_gedcom"];
			$myindilist[$key]["isdead"] = $row["i_isdead"];
//			if ($myindilist[$row["i_key"]]["gedfile"] == $GEDCOM) $indilist[$row[0]] = $myindilist[$row[0]."[".$row[2]."]"];
		}
		$res->FreeResult();
	}
	return $myindilist;
}

//-- search through the gedcom records for individuals
/**
 * Search the database for individuals that match the query
 *
 * uses full text search
 *
 * @author	Genmod Development Team
 * @param		string $query a regular expression query to search for
 * @param		boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myindilist array with all individuals that matched the query
 */
function FTSearchIndis($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMID, $GEDCOMS, $GEDCOM, $ftminwlen, $ftmaxwlen, $COMBIKEY;
	
	// Get the min and max search word length
	GetFTWordLengths();

	// if all search terms are below the minimum length, the FT search query will return nothing.
	// if all positive short terms with + are added as AND, nothing will be returned either.
	// so, if all positive search terms are shorter than min len, we must omit FT search anyway.	
	
	$myindilist = array();
	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);

	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (!Utf8_isascii($keyword["term"])) $addsql .= " ".$keyword["operator"]." i_gedcom REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." i_gedcom REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (!Utf8_isascii($keyword["term"])) $addsql .= " AND i_gedcom NOT REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND i_gedcom NOT REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT i_key, i_id, i_file, i_gedcom, i_isdead, n_name, n_letter, n_type, n_surname FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=n_key AND (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT i_key, i_id, i_file, i_gedcom, i_isdead, n_name, n_letter, n_type, n_surname FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=n_key AND (MATCH (i_gedcom) AGAINST ('".$DBCONN->EscapeQuery($query)."' IN BOOLEAN MODE))";
	}
		
	if (!$allgeds) $sql .= " AND i_file='".$GEDCOMID."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		if (count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0; $i<count($allgeds); $i++) {
				$sql .= "i_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
				if ($i < count($allgeds)-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	}

	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchAssoc()){
			$row = db_cleanup($row);
			if ($COMBIKEY) $key = $row["i_key"];
			else $key = $row["i_id"];
			if (!isset($myindilist[$key])) {
				$myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$myindilist[$key]["gedfile"] = $row["i_file"];
				$myindilist[$key]["gedcom"] = $row["i_gedcom"];
				$myindilist[$key]["isdead"] = $row["i_isdead"];
			}
			else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$indilist[$key] = $myindilist[$key];
		}
		$res->FreeResult();
	}
	return $myindilist;
}

/**
 * Search through the gedcom records for individuals in families
 *
 * @package Genmod
 * @subpackage Calendar
**/
function SearchIndisFam($add2myindilist) {
	global $TBLPREFIX, $GEDCOM, $indilist, $myindilist, $GEDCOMID;

	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_type, n_surname, n_letter FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=n_key AND i_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->fetchAssoc()){
		if (isset($add2myindilist[$row["i_id"]])){
			$add2my_fam=$add2myindilist[$row["i_id"]];
			$row = db_cleanup($row);
			if (!isset($myindilist[$row["i_id"]])) {
				$myindilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$myindilist[$row["i_id"]]["gedfile"] = $row["i_file"];
				$myindilist[$row["i_id"]]["gedcom"] = $row["i_gedcom"].$add2my_fam;
				$myindilist[$row["i_id"]]["isdead"] = $row["i_isdead"];
			}
			else $myindilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$indilist[$row["i_id"]] = $myindilist[$row["i_id"]];
		}
	}
	$res->FreeResult();
	return $myindilist;
}

/**
* @package Genmod
* @subpackage Calendar
**/
function SearchIndisYearRange($startyear, $endyear, $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;

	$myindilist = array();
	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_surname, n_letter, n_type FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=n_key AND (";
	$i=$startyear;
	while($i <= $endyear) {
		if ($i > $startyear) $sql .= " OR ";
		if ($REGEXP_DB) $sql .= "i_gedcom REGEXP '".$DBCONN->EscapeQuery("2 DATE[^\n]* ".$i)."'";
		else $sql .= "i_gedcom LIKE '".$DBCONN->EscapeQuery("%2 DATE%".$i)."%'";
		$i++;
	}
	$sql .= ")";
	if (!$allgeds) $sql .= " AND i_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->fetchAssoc()){
		$row = db_cleanup($row);
		if (!isset($myindilist[$row["i_id"]])) {
			$myindilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$myindilist[$row["i_id"]]["gedfile"] = $row["i_file"];
			$myindilist[$row["i_id"]]["gedcom"] = $row["i_gedcom"];
			$myindilist[$row["i_id"]]["isdead"] = $row["i_isdead"];
		}
		else $myindilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		$indilist[$row["i_id"]] = $myindilist[$row["i_id"]];
	}
	$res->FreeResult();
	return $myindilist;
}

/**
 * Search through the gedcom records for individuals
 *
 * @package Genmod
 * @subpackage Find
**/
function SearchIndisNames($query, $allgeds=false, $gedid=0) {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	
	//-- split up words and find them anywhere in the record... important for searching names
	//-- like "givenname surname"
	if (!is_array($query)) {
		$query = preg_split("/[\s,]+/", $query);
		if (!$REGEXP_DB) {
			for($i=0; $i<count($query); $i++){
				$query[$i] = "%".$query[$i]."%";
			}
		}
	}

	$myindilist = array();
	if (!is_array($query)) $sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_letter, n_type, n_surname FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=n_key AND n_name REGEXP '".$DBCONN->EscapeQuery($query)."'";
	else {
		$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_letter, n_type, n_surname FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=n_key AND (";
		$i=0;
		foreach($query as $indexval => $q) {
			if (!empty($q)) {
				if ($i>0) $sql .= " AND ";
				$sql .= "n_name REGEXP '".$DBCONN->EscapeQuery($q)."'";
				$i++;
			}
		}
		$sql .= ")";
	}
	if (!$allgeds) {
		if ($gedid == 0) $sql .= " AND i_file='".$GEDCOMID."'";
		else $sql .= " AND i_file='".$gedid."'";
	}
	$res = NewQuery($sql);
	while($row = $res->fetchAssoc()){
		$row = db_cleanup($row);
		if ($allgeds) $key = $row["i_id"]."[".$row["i_file"]."]";
		else $key = $row["i_id"];
		if (!isset($myindilist[$key])) {
			$myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$myindilist[$key]["gedfile"] = $row["i_file"];
			$myindilist[$key]["gedcom"] = $row["i_gedcom"];
			$myindilist[$key]["isdead"] = $row["i_isdead"];
		}
		else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		$indilist[$key] = $myindilist[$key];
	}
	$res->FreeResult();
	return $myindilist;
}

/**
 * Search the dates table for individuals that had events on the given day
 *
 * @author	yalnifj
 * @param	int $day the day of the month to search for, leave empty to include all
 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
 * @param	int $year the year to search for, leave empty to include all
 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
 * 				prepend the fact with a ! to not include that fact
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myindilist array with all individuals that matched the query
 */
function SearchIndisDates($day="", $month="", $year="", $fact="", $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	$myindilist = array();
	
	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, d_gid, d_fact, n_name, n_surname, n_type, n_letter FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=d_key AND n_key=i_key ";
	if (!empty($day)) $sql .= "AND d_day='".$DBCONN->EscapeQuery($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".$DBCONN->EscapeQuery($month)."' ";
	if (!empty($year)) $sql .= "AND d_year='".$DBCONN->EscapeQuery($year)."' ";
	if (!empty($fact)) {
		$sql .= "AND (";
		$facts = preg_split("/[,:; ]/", $fact);
		$i=0;
		foreach($facts as $fact) {
			if ($i!=0) $sql .= " OR ";
			$ct = preg_match("/!(\w+)/", $fact, $match);
			if ($ct > 0) {
				$fact = $match[1];
				$sql .= "d_fact!='".$DBCONN->EscapeQuery($fact)."'";
			}
			else {
				$sql .= "d_fact='".$DBCONN->EscapeQuery($fact)."'";
			}
			$i++;
		}
		$sql .= ") ";
	}
	if (!$allgeds) $sql .= "AND d_file='".$GEDCOMID."' ";
	$sql .= "GROUP BY i_id ORDER BY d_year, d_month, d_day DESC";
	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchAssoc()){
			$row = db_cleanup($row);
			if ($allgeds) $key = $row["i_id"]."[".$row["i_file"]."]";
			else $key = $row["i_id"];
			if (!isset($myindilist[$key])) {
				$myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$myindilist[$key]["gedfile"] = $row["i_file"];
				$myindilist[$key]["gedcom"] = $row["i_gedcom"];
				$myindilist[$key]["isdead"] = $row["i_isdead"];
			}
			else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			if ($myindilist[$key]["gedfile"] == $GEDCOMID) $indilist[$row["i_id"]] = $myindilist[$key];
		}
		$res->FreeResult();
	}
	return $myindilist;
}

/**
 * Search the dates table for individuals that had events on the given day
 *
 * @param	int $day the day of the month to search for, leave empty to include all
 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
 * @param	int $year the year to search for, leave empty to include all
 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
 * 				prepend the fact with a ! to not include that fact
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myindilist array with all individuals that matched the query
 */
function SearchIndisDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $filter="all", $onlyBDM="no", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $TBLPREFIX, $GEDCOMID, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOM;
	$myindilist = array();
//print "Dstart: ".$dstart."<br />";
//print "Mstart: ".$mstart." ".date("M", mktime(1,0,0,$mstart,1))."<br />";
//print "Dend: ".$dend."<br />";
//print "Mend: ".$mend." ".date("M", mktime(1,0,0,$mend,1))."<br />";
	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead, n_name, n_surname, n_letter, n_type FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=d_key AND n_key=i_key ";
	if ($onlyBDM == "yes") $sql .= " AND d_fact NOT IN ('BIRT', 'DEAT')";
	if ($filter == "alive") $sql .= "AND i_isdead!='0'";

	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
		
	$sql .= "GROUP BY i_id ORDER BY d_year, d_month, d_day DESC";
//print $sql;
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$row = db_cleanup($row);
		if ($allgeds) $key = $row["i_id"]."[".$row["i_file"]."]";
		else $key = $row["i_id"];
		if (!isset($myindilist[$key])) {
			$myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$myindilist[$key]["gedfile"] = $row["i_file"];
			$myindilist[$key]["gedcom"] = $row["i_gedcom"];
			$myindilist[$key]["isdead"] = $row["i_isdead"];
		}
		else $myindilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		if ($myindilist[$key]["gedfile"] == $GEDCOMID) $indilist[$row["i_id"]] = $myindilist[$key];
	}
	$res->FreeResult();
	return $myindilist;
}

function DateRangeforQuery($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $GEDCOMID;
	
	//-- Compute start
	$sql = "";
	// SQL for 1 day
	if ($dstart == $dend && $mstart == $mend && $ystart == $yend) {
		$sql .= " AND d_day=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
		if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$ystart."'";
	}
	// SQL for dates in 1 month
	else if ($mstart == $mend && $ystart == $yend) {
		$sql .= " AND d_day BETWEEN ".$dstart." AND ".$dend." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
		if (!empty($ystart) && !empty($yend)) $sql .= " AND d_year='".$ystart."'";
	}
	// SQL for >=2 months
	else {
		$sql .= " AND d_day!='0' AND ((d_day>=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1));
		if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$ystart;
		$sql .= "')";
		//-- intermediate months
		if (!empty($ystart) && !empty($yend)) {
			if ($mend < $mstart) $mend = $mend + 12*($yend-$ystart);
			else $mend = $mend + 12*($yend-$ystart);
		}
		else if ($mend < $mstart) $mend += 12;
		for($i=$mstart+1; $i<$mend;$i++) {
			if ($i>12) {
				$m = $i%12;
				if (!empty($ystart) && !empty($yend)) $y = $ystart + (($i - ($i % 12)) / 12);
			}
			else {
				$m = $i;
				if (!empty($ystart) && !empty($yend)) $y = $ystart;
			}
			$sql .= " OR (d_month='".date("M", mktime(1,0,0,$m,1))."'";
			if (!empty($ystart) && !empty($yend)) $sql .= " AND d_year='".$y."'";
			$sql .= ")";
		}
		//-- End 
		$sql .= " OR (d_day<=".$dend." AND d_month='".date("M", mktime(1,0,0,$mend,1));
		if (!empty($ystart) && !empty($yend)) $sql .= "' AND d_year='".$yend;
		$sql .= "')";
		$sql .= ")";
	}
	if (!empty($skipfacts)) {
		$skip = preg_split("/[;, ]/", $skipfacts);
		$sql .= " AND d_fact NOT IN (";
		$i = 0;
		foreach ($skip as $key=>$value) {
			if ($i != 0 ) $sql .= ", ";
			$i++; 
			$sql .= "'".$value."'";
		}
	}

	if (!empty($onlyfacts)) {
		$only = preg_split("/[;, ]/", $onlyfacts);
		$sql .= " AND d_fact IN (";
		$i = 0;
		foreach ($only as $key=>$value) {
			if ($i != 0 ) $sql .= ", ";
			$i++; 
			$sql .= "'".$value."'";
		}
		$sql .= ")";
	}
	else $sql .= ")";	
	
	if (!$allgeds) $sql .= " AND d_file='".$GEDCOMID."' ";
	// General part ends here
	
	return $sql;
}

/**
 * Search the dates table for families that had events on the given day
 *
 * @author	yalnifj
 * @param	int $day the day of the month to search for, leave empty to include all
 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
 * @param	int $year the year to search for, leave empty to include all
 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
 * 				prepend the fact with a ! to not include that fact
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchFamsDateRange($dstart="1", $mstart="1", $ystart, $dend="31", $mend="12", $yend, $onlyBDM="no", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $TBLPREFIX, $GEDCOMID, $famlist, $DBCONN, $REGEXP_DB, $GEDCOM, $GEDCOMS, $GEDCOMID;
	$myfamlist = array();
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."families WHERE f_key=d_key ";

	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);

	if ($onlyBDM == "yes") $sql .= " AND d_fact='MARR'";
	$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";

	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
		$hname = GetSortableName($row[1]);
		$wname = GetSortableName($row[2]);
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = $hname." + ".$wname;
		if ($allgeds) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["HUSB"] = $row[1];
			$myfamlist[$row[0]."[".$row[3]."]"]["WIFE"] = $row[2];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["HUSB"] = $row[1];
			$myfamlist[$row[0]]["WIFE"] = $row[2];
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOM = $gedold;
	$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
	$res->FreeResult();
	return $myfamlist;
}

//-- search through the gedcom records for families
function SearchFams($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	
	$myfamlist = array();
	if (!is_array($query)) $sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (f_gedcom REGEXP '".$DBCONN->EscapeQuery($query)."')";
	else {
		$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(f_gedcom REGEXP '".$DBCONN->EscapeQuery($q)."')";
			$i++;
		}
		$sql .= ")";
	}
	
	if (!$allgeds) $sql .= " AND f_file='".$GEDCOMID."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0, $max=count($allgeds); $i<$max; $i++) {
			$sql .= "f_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < $max-1) $sql .= " OR ";
		}
		$sql .= ")";
	}
	
	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
		if ($allnames == true) {
			$hname = GetSortableName($row[1], "", "", true);
			$wname = GetSortableName($row[2], "", "", true);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = array();
			foreach ($hname as $hkey => $hn) {
				foreach ($wname as $wkey => $wn) {
					$name[] = $hn." + ".$wn;
					$name[] = $wn." + ".$hn;
				}
			}
		}
		else {
			$hname = GetSortableName($row[1]);
			$wname = GetSortableName($row[2]);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = $hname." + ".$wname;
		}
		if (count($allgeds) > 1) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$myfamlist[$row[0]."[".$row[3]."]"]["HUSB"] = $row[1];
			$myfamlist[$row[0]."[".$row[3]."]"]["WIFE"] = $row[2];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["HUSB"] = $row[1];
			$myfamlist[$row[0]]["WIFE"] = $row[2];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOM = $gedold;
	$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
	$res->FreeResult();
	return $myfamlist;
}

// Get the minimum and maximum Full Text Search term lengths
function GetFTWordLengths() {
	global $ftminwlen, $ftmaxwlen;
	
	if (!isset($ftminwlen)) {
		$sql = "SHOW VARIABLES LIKE '%ft_min_word_len%'";
		$res = NewQuery($sql);
		$row = $res->FetchRow();
		$ftminwlen = $row[1];
		$res->FreeResult();
	}
	if (!isset($ftmaxwlen)) {
		$sql = "SHOW VARIABLES LIKE '%ft_max_word_len%'";
		$res = NewQuery($sql);
		$row = $res->FetchRow();
		$maxwlen = $row[1];
		$res->FreeResult();
	}
}	

//-- search through the gedcom records for families
function FTSearchFams($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen, $COMBIKEY;

	// Get the min and max search word length
	GetFTWordLengths();
	
	$myfamlist = array();
	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);
	
	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." f_gedcom REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." f_gedcom REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " AND f_gedcom NOT REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND f_gedcom NOT REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (MATCH (f_gedcom) AGAINST ('".$DBCONN->EscapeQuery($query)."' IN BOOLEAN MODE))";
	}
	
	if (!$allgeds) $sql .= " AND f_file='".$GEDCOMID."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
		$sql .= " AND (";
		for ($i=0, $max=count($allgeds); $i<$max; $i++) {
			$sql .= "f_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < $max-1) $sql .= " OR ";
		}
		$sql .= ")";
	}

	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
		if ($allnames == true) {
			$hname = GetSortableName($row[1], "", "", true);
			$wname = GetSortableName($row[2], "", "", true);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = array();
			foreach ($hname as $hkey => $hn) {
				foreach ($wname as $wkey => $wn) {
					$name[] = $hn." + ".$wn;
					$name[] = $wn." + ".$hn;
				}
			}
		}
		else {
			$hname = GetSortableName($row[1]);
			$wname = GetSortableName($row[2]);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = $hname." + ".$wname;
		}
		if (count($allgeds) > 1 || $COMBIKEY) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$myfamlist[$row[0]."[".$row[3]."]"]["HUSB"] = $row[1];
			$myfamlist[$row[0]."[".$row[3]."]"]["WIFE"] = $row[2];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["HUSB"] = $row[1];
			$myfamlist[$row[0]]["WIFE"] = $row[2];
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOM = $gedold;
	$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
	$res->FreeResult();
	return $myfamlist;
}


//-- search through the gedcom records for families
function SearchFamsNames($query, $ANDOR="AND", $allnames=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID, $COMBIKEY;

	$myfamlist = array();
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (";
	$i=0;
	foreach($query as $indexval => $q) {

		if ($i>0) $sql .= " $ANDOR ";
		$sql .= "((f_husb='".$DBCONN->EscapeQuery($q[0])."' OR f_wife='".$DBCONN->EscapeQuery($q[0])."') AND f_file='".$DBCONN->EscapeQuery($GEDCOMS[$q[1]]["id"])."')";
		$i++;
	}
	$sql .= ")";

	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
		if ($allnames == true) {
			$hname = GetSortableName($row[1], "", "", true);
			$wname = GetSortableName($row[2], "", "", true);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = array();
			foreach ($hname as $hkey => $hn) {
				foreach ($wname as $wkey => $wn) {
					$name[] = $hn." + ".$wn;
					$name[] = $wn." + ".$hn;
				}
			}
		}
		else {
			$hname = GetSortableName($row[1]);
			$wname = GetSortableName($row[2]);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = $hname." + ".$wname;
		}
		if ($COMBIKEY) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["HUSB"] = $row[1];
			$myfamlist[$row[0]."[".$row[3]."]"]["WIFE"] = $row[2];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["HUSB"] = $row[1];
			$myfamlist[$row[0]]["WIFE"] = $row[2];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOM = $gedold;
	$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
	$res->FreeResult();
	return $myfamlist;
}

/**
 * Search the families table for individuals are part of that family 
 * either as a husband, wife or child.
 *
 * @author	roland-d
 * @param	string $query the query to search for as a single string
 * @param	array $query the query to search for as an array
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @param	string $ANDOR setting if the sql query should be constructed with AND or OR
 * @param	boolean $allnames true returns all names in an array
 * @return	array $myfamlist array with all families that matched the query
 */
function SearchFamsMembers($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	$myfamlist = array();
	if (!is_array($query)) $sql = "SELECT f_id, f_husb, f_wife, f_file FROM ".$TBLPREFIX."families WHERE (f_husb='$query' OR f_wife='$query' OR f_chil REGEXP '$query;')";
	else {
		$sql = "SELECT f_id, f_husb, f_wife, f_file FROM ".$TBLPREFIX."families WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(f_husb='$query' OR f_wife='$query' OR f_chil REGEXP '$query;')";
			$i++;
		}
		$sql .= ")";
	}
	
	if (!$allgeds) $sql .= " AND f_file='".$GEDCOMID."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0, $max=count($allgeds); $i<$max; $i++) {
			$sql .= "f_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < $max-1) $sql .= " OR ";
		}
		$sql .= ")";
	}
	$res = NewQuery($sql);
	$i=0;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if ($allnames == true) {
			$hname = GetSortableName($row[1], "", "", true);
			$wname = GetSortableName($row[2], "", "", true);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = array();
			foreach ($hname as $hkey => $hn) {
				foreach ($wname as $wkey => $wn) {
					$name[] = $hn." + ".$wn;
					$name[] = $wn." + ".$hn;
				}
			}
		}
		else {
			$hname = GetSortableName($row[1]);
			$wname = GetSortableName($row[2]);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = $hname." + ".$wname;
		}		
		if (count($allgeds) > 1) {
			$myfamlist[$i]["name"] = $name;
			$myfamlist[$i]["gedfile"] = $row[0];
			$myfamlist[$i]["gedcom"] = $row[1];
			$famlist[] = $myfamlist;
		}
		else {
			$myfamlist[$i][] = $name;
			$myfamlist[$i][] = $row[0];
			$myfamlist[$i][] = $row[3];
			$i++;
			$famlist[] = $myfamlist;
		}
	}
	$res->FreeResult();
	return $myfamlist;
}

/**
 * Search through the gedcom records for families with daterange
 *
 * @package Genmod
 * @subpackage Calendar
**/
function SearchFamsYearRange($startyear, $endyear, $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;

	$myfamlist = array();
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (";
	$i=$startyear;
	while($i <= $endyear) {
		if ($i > $startyear) $sql .= " OR ";
		if ($REGEXP_DB) $sql .= "f_gedcom REGEXP '".$DBCONN->EscapeQuery("2 DATE[^\n]* ".$i)."'";
		else $sql .= "f_gedcom LIKE '".$DBCONN->EscapeQuery("%2 DATE%".$i)."%'";
		$i++;
	}
	$sql .= ")";
	if (!$allgeds) $sql .= " AND f_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$hname = GetSortableName($row[1]);
		$wname = GetSortableName($row[2]);
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = $hname." + ".$wname;
		$myfamlist[$row[0]]["name"] = $name;
		$myfamlist[$row[0]]["gedfile"] = $row[3];
		$myfamlist[$row[0]]["HUSB"] = $row[1];
		$myfamlist[$row[0]]["WIFE"] = $row[2];
		$myfamlist[$row[0]]["gedcom"] = $row[4];
		$famlist[$row[0]] = $myfamlist[$row[0]];
	}
	$res->FreeResult();
	return $myfamlist;
}

/**
 * Search the dates table for families that had events on the given day
 *
 * @author	yalnifj
 * @param	int $day the day of the month to search for, leave empty to include all
 * @param	string $month the 3 letter abbr. of the month to search for, leave empty to include all
 * @param	int $year the year to search for, leave empty to include all
 * @param	string $fact the facts to include (use a comma seperated list to include multiple facts)
 * 				prepend the fact with a ! to not include that fact
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchFamsDates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOM, $GEDCOMS, $GEDCOMID;
	$myfamlist = array();
	
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom, d_gid, d_fact FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."families WHERE f_key=d_key ";
	if (!empty($day)) $sql .= "AND d_day='".$DBCONN->EscapeQuery($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".$DBCONN->EscapeQuery(Str2Upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".$DBCONN->EscapeQuery($year)."' ";
	if (!empty($fact)) {
		$sql .= "AND (";
		$facts = preg_split("/[,:; ]/", $fact);
		$i=0;
		foreach($facts as $fact) {
			if ($i!=0) $sql .= " OR ";
			$ct = preg_match("/!(\w+)/", $fact, $match);
			if ($ct > 0) {
				$fact = $match[1];
				$sql .= "d_fact!='".$DBCONN->EscapeQuery(Str2Upper($fact))."'";
			}
			else {
				$sql .= "d_fact='".$DBCONN->EscapeQuery(Str2Upper($fact))."'";
			}
			$i++;
		}
		$sql .= ") ";
	}
	if (!$allgeds) $sql .= "AND d_file='".$GEDCOMID."' ";
	$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
		$hname = GetSortableName($row[1]);
		$wname = GetSortableName($row[2]);
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = $hname." + ".$wname;
		if ($allgeds) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["HUSB"] = $row[1];
			$myfamlist[$row[0]."[".$row[3]."]"]["WIFE"] = $row[2];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["HUSB"] = $row[1];
			$myfamlist[$row[0]]["WIFE"] = $row[2];
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOM = $gedold;
	$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
	$res->FreeResult();
	return $myfamlist;
}

//-- search through the gedcom records for sources
function SearchSources($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	
	$mysourcelist = array();	
	if (!is_array($query)) $sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".$TBLPREFIX."sources WHERE (s_gedcom REGEXP '".$DBCONN->EscapeQuery($query)."')";
	else {
		$sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".$TBLPREFIX."sources WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(s_gedcom REGEXP '".$DBCONN->EscapeQuery($q)."')";
			$i++;
		}
		$sql .= ")";
	}	
	if (!$allgeds) $sql .= " AND s_file='".$GEDCOMID."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "s_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}

	$sql .= " ORDER BY s_name";
	$res = NewQuery($sql);
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if (count($allgeds) > 1) {
			$mysourcelist[$row[0]."[".$row[2]."]"]["name"] = $row[1];
			$mysourcelist[$row[0]."[".$row[2]."]"]["gedfile"] = $row[2];
			$mysourcelist[$row[0]."[".$row[2]."]"]["gedcom"] = $row[3];
		}
		else {
			$mysourcelist[$row[0]]["name"] = $row[1];
			$mysourcelist[$row[0]]["gedfile"] = $row[2];
			$mysourcelist[$row[0]]["gedcom"] = $row[3];
		}
	}
	$res->FreeResult();
	return $mysourcelist;
}


//-- search through the gedcom records for sources, full text
function FTSearchSources($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen;
	
	// Get the min and max search word length
	GetFTWordLengths();

	$mysourcelist = array();	
	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);
	
	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." s_gedcom REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." s_gedcom REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " AND s_gedcom NOT REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND s_gedcom NOT REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".$TBLPREFIX."sources WHERE (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".$TBLPREFIX."sources WHERE (MATCH (s_gedcom) AGAINST ('".$DBCONN->EscapeQuery($query)."' IN BOOLEAN MODE))";
	}

	if (!$allgeds) $sql .= " AND s_file='".$GEDCOMID."'";

	if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "s_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}

	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->fetchRow()){
			$row = db_cleanup($row);
			if (count($allgeds) > 1) {
				$mysourcelist[$row[0]."[".$row[2]."]"]["name"] = $row[1];
				$mysourcelist[$row[0]."[".$row[2]."]"]["gedfile"] = $row[2];
				$mysourcelist[$row[0]."[".$row[2]."]"]["gedcom"] = $row[3];
			}
			else {
				$mysourcelist[$row[0]]["name"] = $row[1];
				$mysourcelist[$row[0]]["gedfile"] = $row[2];
				$mysourcelist[$row[0]]["gedcom"] = $row[3];
			}
		}
		$res->FreeResult();
	}
	return $mysourcelist;
}

//-- search through the gedcom records for repositories, full text
function FTSearchRepos($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen;
	
	// Get the min and max search word length
	GetFTWordLengths();

	$myrepolist = array();	
	$cquery = ParseFTSearchQuery($query);
	$addsql = "";
	
	$mlen = GetFTMinLen($cquery);
	
	if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
		if (isset($cquery["includes"])) {
			foreach ($cquery["includes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." o_gedcom REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " ".$keyword["operator"]." o_gedcom REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		if (isset($cquery["excludes"])) {
			foreach ($cquery["excludes"] as $index => $keyword) {
				if (HasChinese($keyword["term"])) $addsql .= " AND o_gedcom NOT REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
				else $addsql .= " AND o_gedcom NOT REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
			}
		}
		$sql = "SELECT o_id, o_file, o_type, o_gedcom FROM ".$TBLPREFIX."other WHERE (".substr($addsql,4).")";
	}
	else {
		$sql = "SELECT o_id, o_file, o_type, o_gedcom FROM ".$TBLPREFIX."other WHERE (MATCH (o_gedcom) AGAINST ('".$DBCONN->EscapeQuery($query)."' IN BOOLEAN MODE))";
	}

	if (!$allgeds) $sql .= " AND o_file='".$GEDCOMID."'";
	
	$sql .= " AND o_type='REPO'";

	if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "o_file='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}

	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->fetchAssoc()){
			$row = db_cleanup($row);
			$tt = preg_match("/1 NAME (.*)/", $row["o_gedcom"], $match);
			if ($tt == "0") $name = $row["o_id"]; else $name = $match[1];
			if (count($allgeds) > 1) {
				$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["id"] = $row["o_id"];
				$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["name"] = $name;
				$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["gedfile"] = $row["o_file"];
				$myrepolist[$row["o_id"]."[".$row["o_file"]."]"]["gedcom"] = $row["o_gedcom"];
			}
			else {
				$myrepolist[$row["o_id"]]["id"] = $row["o_id"];
				$myrepolist[$row["o_id"]]["name"] = $name;
				$myrepolist[$row["o_id"]]["gedfile"] = $row["o_file"];
				$myrepolist[$row["o_id"]]["gedcom"] = $row["o_gedcom"];
			}
		}
		$res->FreeResult();
	}
	return $myrepolist;
}



/**
 * Search the dates table for sources that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchSourcesDates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	$mysourcelist = array();
	
	$sql = "SELECT s_id, s_name, s_file, s_gedcom, d_gid FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."sources WHERE s_id=d_gid AND s_file=d_file ";
	if (!empty($day)) $sql .= "AND d_day='".$DBCONN->EscapeQuery($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".$DBCONN->EscapeQuery(Str2Upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".$DBCONN->EscapeQuery($year)."' ";
	if (!empty($fact)) $sql .= "AND d_fact='".$DBCONN->EscapeQuery(Str2Upper($fact))."' ";
	if (!$allgeds) $sql .= "AND d_file='".$GEDCOMID."' ";
	$sql .= "GROUP BY s_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if ($allgeds) {
			$mysourcelist[$row[0]."[".$row[2]."]"]["name"] = $row[1];
			$mysourcelist[$row[0]."[".$row[2]."]"]["gedfile"] = $row[2];
			$mysourcelist[$row[0]."[".$row[2]."]"]["gedcom"] = $row[3];
		}
		else {
			$mysourcelist[$row[0]]["name"] = $row[1];
			$mysourcelist[$row[0]]["gedfile"] = $row[2];
			$mysourcelist[$row[0]]["gedcom"] = $row[3];
		}
	}
	$GEDCOM = $gedold;
	$res->FreeResult();
	return $mysourcelist;
}

/**
 * Search the dates table for sources that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchSourcesDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	$mysourcelist = array();
	
	$sql = "SELECT s_id, s_name, s_file, s_gedcom, d_gid FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."sources WHERE s_id=d_gid AND s_file=d_file ";
	
	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
	$sql .= "GROUP BY s_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if ($allgeds) {
			$mysourcelist[$row[0]."[".$row[2]."]"]["name"] = $row[1];
			$mysourcelist[$row[0]."[".$row[2]."]"]["gedfile"] = $row[2];
			$mysourcelist[$row[0]."[".$row[2]."]"]["gedcom"] = $row[3];
		}
		else {
			$mysourcelist[$row[0]]["name"] = $row[1];
			$mysourcelist[$row[0]]["gedfile"] = $row[2];
			$mysourcelist[$row[0]]["gedcom"] = $row[3];
		}
	}
	$GEDCOM = $gedold;
	$res->FreeResult();
	return $mysourcelist;
}

/**
 * Search the dates table for other records that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchOtherDates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	$myrepolist = array();
	
	$sql = "SELECT o_id, o_file, o_type, o_gedcom, d_gid FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."other WHERE o_id=d_gid AND o_file=d_file ";
	if (!empty($day)) $sql .= "AND d_day='".$DBCONN->EscapeQuery($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".$DBCONN->EscapeQuery(Str2Upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".$DBCONN->EscapeQuery($year)."' ";
	if (!empty($fact)) $sql .= "AND d_fact='".$DBCONN->EscapeQuery(Str2Upper($fact))."' ";
	if (!$allgeds) $sql .= "AND d_file='".$GEDCOMID."' ";
	$sql .= "GROUP BY o_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$tt = preg_match("/1 NAME (.*)/", $row[2], $match);
		if ($tt == "0") $name = $row[0]; else $name = $match[1];
		if ($allgeds) {
			$myrepolist[$row[0]."[".$row[1]."]"]["name"] = $name;
			$myrepolist[$row[0]."[".$row[1]."]"]["gedfile"] = $row[1];
			$myrepolist[$row[0]."[".$row[1]."]"]["type"] = $row[2];
			$myrepolist[$row[0]."[".$row[1]."]"]["gedcom"] = $row[3];
		}
		else {
			$myrepolist[$row[0]]["name"] = $name;
			$myrepolist[$row[0]]["gedfile"] = $row[1];
			$myrepolist[$row[0]]["type"] = $row[2];
			$myrepolist[$row[0]]["gedcom"] = $row[3];
		}
	}
	$GEDCOM = $gedold;
	$res->FreeResult();
	return $myrepolist;
}

/**
 * Search the dates table for other records that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function SearchOtherDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	$myrepolist = array();
	
	$sql = "SELECT o_id, o_file, o_type, o_gedcom, d_gid FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."other WHERE o_id=d_gid AND o_file=d_file ";
	
	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
	$sql .= "GROUP BY o_id ORDER BY d_year, d_month, d_day DESC";

	$res = NewQuery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$tt = preg_match("/1 NAME (.*)/", $row[2], $match);
		if ($tt == "0") $name = $row[0]; else $name = $match[1];
		if ($allgeds) {
			$myrepolist[$row[0]."[".$row[1]."]"]["name"] = $name;
			$myrepolist[$row[0]."[".$row[1]."]"]["gedfile"] = $row[1];
			$myrepolist[$row[0]."[".$row[1]."]"]["type"] = $row[2];
			$myrepolist[$row[0]."[".$row[1]."]"]["gedcom"] = $row[3];
		}
		else {
			$myrepolist[$row[0]]["name"] = $name;
			$myrepolist[$row[0]]["gedfile"] = $row[1];
			$myrepolist[$row[0]]["type"] = $row[2];
			$myrepolist[$row[0]]["gedcom"] = $row[3];
		}
	}
	$GEDCOM = $gedold;
	$res->FreeResult();
	return $myrepolist;
}

/**
 * Search the dates table for other records that had events on the given day
 *
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $mymedia array with all individuals that matched the query
 */
function SearchMediaDateRange($dstart="1", $mstart="1", $ystart="", $dend="31", $mend="12", $yend="", $skipfacts="", $allgeds=false, $onlyfacts="") {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID, $MediaFS;
	$mymedia = array();
	
	$sql = "SELECT m_media, m_file, m_gedfile, m_ext, m_titl, m_gedrec FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."media WHERE m_media=d_gid AND m_gedfile=d_file ";
	
	$sql .= DateRangeforQuery($dstart, $mstart, $ystart, $dend, $mend, $yend, $skipfacts, $allgeds, $onlyfacts);
	
	$sql .= "GROUP BY m_media ORDER BY d_year, d_month, d_day DESC";

	$res = NewQuery($sql);
	$gedold = $GEDCOM;

	while($row = $res->fetchAssoc()){
		if ($allgeds) $mid = $row["m_media"]."[".$row["m_gedfile"]."]";
		else $mid = $row["m_media"];
		$mymedia[$mid]["ext"] = $row["m_ext"];
		$mymedia[$mid]["title"] = $row["m_titl"];
		$mymedia[$mid]["file"] = $MediaFS->CheckMediaDepth($row["m_file"]);
		$mymedia[$mid]["gedcom"] = $row["m_gedrec"];
		$mymedia[$mid]["gedfile"] = $row["m_gedfile"];
	}
	$GEDCOM = $gedold;
	$res->FreeResult();
	return $mymedia;
}

/**
 * get place parent ID
 * @param array $parent
 * @param int $level
 * @return int
 */
function GetPlaceParentId($parent, $level) {
	global $DBCONN, $TBLPREFIX, $GEDCOM, $GEDCOMS, $GEDCOMID;

	$parent_id=0;
	for($i=0; $i<$level; $i++) {
		$escparent=preg_replace("/\?/","\\\\\\?", $DBCONN->EscapeQuery($parent[$i]));
		$psql = "SELECT p_id FROM ".$TBLPREFIX."places WHERE p_level=".$i." AND p_parent_id=$parent_id AND p_place LIKE '".$escparent."' AND p_file='".$GEDCOMID."' ORDER BY p_place";
		$res = NewQuery($psql);
		$row = $res->fetchRow();
		$res->FreeResult();
		if (empty($row[0])) break;
		$parent_id = $row[0];
	}
	return $parent_id;
}

/**
 * Find all of the places in the hierarchy
 *
 * The $parent array holds the parent hierarchy of the places
 * we want to get.  The level holds the level in the hierarchy that
 * we are at.
 *
 * @package Genmod
 * @subpackage Places
 */
function GetPlaceList() {
	global $numfound, $j, $level, $parent, $found;
	global $GEDCOM, $TBLPREFIX, $placelist, $positions, $DBCONN, $GEDCOMS, $GEDCOMID;

	// --- find all of the place in the file
	if ($level==0) $sql = "SELECT p_place FROM ".$TBLPREFIX."places WHERE p_level=0 AND p_file='".$GEDCOMID."' ORDER BY p_place";
	else {
		$parent_id = GetPlaceParentId($parent, $level);
		$sql = "SELECT p_place FROM ".$TBLPREFIX."places WHERE p_level=$level AND p_parent_id=$parent_id AND p_file='".$GEDCOMID."' ORDER BY p_place";
	}
	$res = NewQuery($sql);
	while ($row = $res->fetchRow()) {
		$placelist[] = $row[0];
		$numfound++;
	}
	$res->FreeResult();
}

/**
 * get all of the place connections
 * @param array $parent
 * @param int $level
 * @return array
 */
function GetPlacePositions($parent, $level) {
	global $positions, $TBLPREFIX, $GEDCOM, $DBCONN, $GEDCOMS, $GEDCOMID;

	$p_id = GetPlaceParentId($parent, $level);
	$sql = "SELECT DISTINCT pl_gid FROM ".$TBLPREFIX."placelinks WHERE pl_p_id=$p_id AND pl_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while ($row = $res->fetchRow()) {
		$positions[] = $row[0];
	}
	return $positions;
}

//-- find all of the places
function FindPlaceList($place) {
	global $GEDCOM, $TBLPREFIX, $placelist, $indilist, $famlist, $sourcelist, $otherlist, $DBCONN, $GEDCOMS, $GEDCOMID;
	
	$sql = "SELECT p_id, p_place, p_parent_id  FROM ".$TBLPREFIX."places WHERE p_file='".$GEDCOMID."' ORDER BY p_parent_id, p_id";
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

 /**
 * Get all first letters of individual's last names
 *
 * The function takes all the distinct lastname starting letters 
 * found in both the individual and names table. Then some language specific
 * letter substitution is done
 *
 * @see indilist.php
 * @author	Genmod Development Team
 * @return 	array	An array of all letters found in the active gedcom
 */

function GetIndiAlpha($allgeds="no") {
	global $CHARACTER_SET, $TBLPREFIX, $LANGUAGE, $SHOW_MARRIED_NAMES;
	global $GEDCOMID;
	$indialpha = array();
	
	$sql = "SELECT DISTINCT n_letter AS alpha ";
	$sql .= "FROM ".$TBLPREFIX."names ";
	if ($allgeds == "no") $sql .= "WHERE n_file = '".$GEDCOMID."'";
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
		if (!isset($indialpha[$letter])) $indialpha[$letter]=$letter;
	}
	$res->FreeResult();
	return $indialpha;
}

//-- get the first character in the list
function GetFamAlpha($allgeds="no") {
	global $CHARACTER_SET, $TBLPREFIX, $GEDCOM, $LANGUAGE, $famalpha, $DBCONN, $GEDCOMS, $GEDCOMID;

	$famalpha = array();

	$sql = "SELECT DISTINCT n_letter as alpha FROM ".$TBLPREFIX."names, ".$TBLPREFIX."individual_family WHERE if_pkey=n_key and if_role='S'";	
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
	$sql = "SELECT f_id FROM ".$TBLPREFIX."families WHERE (f_husb='' || f_wife='')";
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
	global $TBLPREFIX, $GEDCOM, $LANGUAGE, $indilist, $surname, $SHOW_MARRIED_NAMES;
	global $DBCONN, $GEDCOMS, $GEDCOMID, $COMBIKEY;

	$tindilist = array();
	$search_letter = "";
	
	// NOTE: Determine what letter to search for depending on the active language
	if ($LANGUAGE == "hungarian"){
		if (strlen($letter) >= 2) $search_letter = "'".$DBCONN->EscapeQuery($letter)."' ";
		else {
			if ($letter == "C") $text = "CS";
			else if ($letter == "D") $text = "DZ";
			else if ($letter == "G") $text = "GY";
			else if ($letter == "L") $text = "LY";
			else if ($letter == "N") $text = "NY";
			else if ($letter == "S") $text = "SZ";
			else if ($letter == "T") $text = "TY";
			else if ($letter == "Z") $text = "ZS";
			if (isset($text)) $search_letter = "(i_letter = '".$DBCONN->EscapeQuery($letter)."' AND i_letter != '".$DBCONN->EscapeQuery($text)."') ";
			else $search_letter = "i_letter LIKE '".$DBCONN->EscapeQuery($letter)."%' ";
		}
	}
	else if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
		if ($letter == "Ø") $text = "OE";
		else if ($letter == "Æ") $text = "AE";
		else if ($letter == "Å") $text = "AA";
		if (isset($text)) $search_letter = "(i_letter = '".$DBCONN->EscapeQuery($letter)."' OR i_letter = '".$DBCONN->EscapeQuery($text)."') ";
		else if ($letter=="A") $search_letter = "i_letter LIKE '".$DBCONN->EscapeQuery($letter)."' ";
		else $search_letter = "i_letter LIKE '".$DBCONN->EscapeQuery($letter)."%' ";
	}
	else $search_letter = "i_letter LIKE '".$DBCONN->EscapeQuery($letter)."%' ";
	
	// NOTE: Select the records from the individual table
	$sql = "";
	// NOTE: Select the records from the names table
	$sql .= "SELECT i_key, i_id, i_isdead, n_letter, i_gedcom, n_file, n_type, n_name, n_surname, n_letter ";
	$sql .= "FROM ".$TBLPREFIX."names, ".$TBLPREFIX."individuals ";
	$sql .= "WHERE n_key = i_key ";
	$sql .= "AND ".str_replace("i_letter", "n_letter", $search_letter);
	// NOTE: Add some optimization if the surname is set to speed up the lists
	if (!empty($surname)) $sql .= "AND i_surname LIKE '%".$DBCONN->EscapeQuery($surname)."%' ";
	// NOTE: Do not retrieve married names if the user does not want to see them
	if (!$SHOW_MARRIED_NAMES) $sql .= "AND n_type NOT LIKE 'C' ";
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
				if ($row["n_type"] != "C" || ($row["n_type"] == "C" && $SHOW_MARRIED_NAMES)) $indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$indi["isdead"] = $row["i_isdead"];
				$indi["gedcom"] = $row["i_gedcom"];
				$indi["gedfile"] = $row["n_file"];
				$tindilist[$key] = true;
				// NOTE: Cache the item in the $indilist for improved speed
				$indilist[$key] = $indi;
			}
			else {
				if ($row["n_type"] != "C" || ($row["n_type"] == "C" && $SHOW_MARRIED_NAMES)) $indilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);

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
	global $TBLPREFIX, $LANGUAGE, $indilist, $SHOW_MARRIED_NAMES, $DBCONN, $GEDCOMID, $COMBIKEY, $SHOW_NICK, $NICK_DELIM;

	$tindilist = array();
	$sql = "SELECT i_key, i_id, i_file, i_isdead, i_gedcom, n_letter, n_name, n_surname, n_type FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_key=n_key AND n_surname LIKE '".$DBCONN->EscapeQuery($surname)."' ";
	if (!$SHOW_MARRIED_NAMES) $sql .= "AND n_type!='C' ";
	if ($allgeds == "no") $sql .= "AND i_file='".$GEDCOMID."'";
	$sql .= " ORDER BY n_surname";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$row = db_cleanup($row);
		if ($SHOW_NICK) {
			$n = GetNicks($row["i_gedcom"]);
			if (count($n) > 0) {
				$ct = preg_match("~(.*)/(.*)/(.*)~", $row["n_name"], $match);
				if ($ct>0) $row["n_name"] = $match[1].substr($NICK_DELIM, 0, 1).$n[0].substr($NICK_DELIM, 1, 1)."/".$match[2]."/".$match[3];
//				$ct = preg_match("~(.*)/(.*)/(.*)~", $row["i_name"], $match);
//				$row["i_name"] = $match[1].substr($NICK_DELIM, 0, 1).$n[0].substr($NICK_DELIM, 1, 1)."/".$match[2]."/".$match[3];
			}
		}
		if (!$COMBIKEY) $key = $row["i_id"];
		else $key = $row["i_key"];
		if (isset($indilist[$key])) {
			if ($row["n_type"] != "C" || ($row["n_type"] == "C" && $SHOW_MARRIED_NAMES)) $indilist[$key]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		}
		else {
			$indi = array();
			if ($row["n_type"] != "C" || ($row["n_type"] == "C" && $SHOW_MARRIED_NAMES)) $indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$indi["isdead"] = $row["i_isdead"];
			$indi["gedcom"] = $row["i_gedcom"];
			$indi["gedfile"] = $row["i_file"];
			$indilist[$key] = $indi;
			$tindilist[$key] = true;
		}
	}
	$res->FreeResult();
	return $tindilist;
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
	global $TBLPREFIX, $GEDCOMID, $GEDCOM, $famlist, $indilist, $gm_lang, $LANGUAGE, $SHOW_MARRIED_NAMES, $DBCONN, $GEDCOMS, $COMBIKEY;
	$tfamlist = array();
	$temp = $SHOW_MARRIED_NAMES;
	$SHOW_MARRIED_NAMES = false;
	$myindilist = GetAlphaIndis($letter, $allgeds);
	$SHOW_MARRIED_NAMES = $temp;
	if ($letter=="(" || $letter=="[" || $letter=="?") $letter = "\\".$letter;
	$select = array();
	foreach($myindilist as $gid=>$indi) {
		$indi = $indilist[$gid];
		$ct = preg_match_all("/1 FAMS @(.*)@/", $indi["gedcom"], $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$famid = $match[$i][1];
			if ($COMBIKEY) $thisged = SplitKey($gid, "gedid");
			else $thisged = $GEDCOMID;
			$select[JoinKey($famid, $thisged)] = true;
		}
	}
	
	$selection = "";
	foreach ($select as $id => $value) {
		$selection .= "'".$id."', ";
	}
	if (!empty($selection)) {
		$selection = substr($selection, 0, strlen($selection)-2);
		GetFamlist($allgeds, $selection);
	}
	
	if (count($famlist) > 0) {
		$select = array();
		foreach ($famlist as $key => $fam) {
			if (!empty($fam["HUSB"]) && !isset($indilist[JoinKey($fam["HUSB"], $fam["gedfile"])])) $select[JoinKey($fam["HUSB"], $fam["gedfile"])] = true;
			if (!empty($fam["WIFE"]) && !isset($indilist[JoinKey($fam["WIFE"], $fam["gedfile"])])) $select[JoinKey($fam["WIFE"], $fam["gedfile"])] = true;
		}
		$selection = "";
		if (count($select) > 0) {
			foreach ($select as $id => $value) {
				$selection .= "'".$id."', ";
			}
			$selection = substr($selection, 0, strlen($selection)-2);
			GetIndilist($allgeds, $selection, false);
		}
	}

	foreach($myindilist as $gid=>$indi) {
		$indi = $indilist[$gid];
		$ct = preg_match_all("/1 FAMS @(.*)@/", $indi["gedcom"], $match, PREG_SET_ORDER);
		$surnames = array();
		$oldged = $GEDCOM;
		$oldgedid = $GEDCOMID;
		for($i=0; $i<$ct; $i++) {
			$famid = $match[$i][1];
			$famrec = FindFamilyRecord($famid, splitkey($gid, "ged"));
			$famged = SplitKey($gid, "ged");
			if ($COMBIKEY) $fkey = JoinKey($famid, splitkey($gid, "gedid"));
			else $fkey = $famid;
			if ($famlist[$fkey]["HUSB"] == splitkey($gid, "id")) {
				$HUSB = $famlist[$fkey]["HUSB"];
				$WIFE = $famlist[$fkey]["WIFE"];
			}
			else {
				$HUSB = $famlist[$fkey]["WIFE"];
				$WIFE = $famlist[$fkey]["HUSB"];
			}
			$hname="";
			$surnames = array();
			$GEDCOM = SplitKey($gid, "ged");
			$GEDCOMID = SplitKey($gid, "gedid");
			foreach($indi["names"] as $indexval => $namearray) {
				//-- don't use married names in the family list
				if ($namearray[3]!='C') {
					$text = "";
					if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
						if ($letter == "Ø") $text = "OE";
						else if ($letter == "Æ") $text = "AE";
						else if ($letter == "Å") $text = "AA";
					}
					if ((preg_match("/^$letter/", $namearray[1])>0)||(!empty($text)&&preg_match("/^$text/", $namearray[1])>0)) {
						$surnames[Str2Upper($namearray[2])] = $namearray[2];
						$hname = SortableNameFromName($namearray[0]);
					}
				}
			}
			if (!empty($hname)) {
				$wname = GetSortableName($WIFE);
				if (hasRTLText($hname)) {
					$indirec = FindPersonRecord($WIFE, $famged);
					if (isset($indilist[$WIFE])) {
						foreach($indilist[$WIFE]["names"] as $n=>$namearray) {
							if (hasRTLText($namearray[0])) {
								$wname = SortableNameFromName($namearray[0]);
								break;
							}
						}
					}
				}
				$name = $hname ." + ". $wname;
				$famlist[$fkey]["name"] = $name;
				if (!isset($famlist[$fkey]["surnames"])||count($famlist[$fkey]["surnames"])==0) $famlist[$fkey]["surnames"] = $surnames;
				else GmArrayMerge($famlist[$fkey]["surnames"], $surnames);
				$tfamlist[$fkey] = $famlist[$fkey];
			}
		}
		$GEDCOM = $oldged;
		$GEDCOMID = $oldgedid;
	}

	//-- handle the special case for @N.N. when families don't have any husb or wife
	//-- SHOULD WE SHOW THE UNDEFINED? MA
	if ($letter=="@") {
		$sql = "SELECT * FROM ".$TBLPREFIX."families WHERE f_husb='' AND f_file='".$GEDCOMID."'";
		$sql .= " UNION SELECT * FROM ".$TBLPREFIX."families WHERE f_wife='' AND f_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		if ($res->NumRows()>0) {
			while($row = $res->FetchAssoc()){
				$fam = array();
				$hname = GetSortableName($row["f_husb"]);
				$wname = GetSortableName($row["f_wife"]);
				if (!empty($hname)) $name = $hname;
				else $name = "@N.N., @P.N.";
				if (!empty($wname)) $name .= " + ".$wname;
				else $name .= " + @N.N., @P.N.";
				$fam["name"] = $name;
				$fam["HUSB"] = $row["f_husb"];
				$fam["WIFE"] = $row["f_wife"];
				$fam["CHIL"] = $row["f_chil"];
				$fam["gedcom"] = $row["f_gedcom"];
				$fam["gedfile"] = $row["f_file"];
				$fam["surnames"] = array("@N.N.");
				$tfamlist[$row["f_id"]] = $fam;
				//-- cache the items in the lists for improved speed
				$famlist[$row["f_id"]] = $fam;
			}
		}
		$res->FreeResult();
	}
	return $tfamlist;
}


/**
 * Get Families with a given surname
 *
 * This function finds all of the individuals who have the given surname
 * @param string $surname	The surname to search on
 * @return array	$indilist array
 */
function GetSurnameFams($surname, $allgeds="no") {
	global $TBLPREFIX, $GEDCOMID, $GEDCOM, $famlist, $indilist, $gm_lang, $DBCONN, $SHOW_MARRIED_NAMES, $GEDCOMS, $COMBIKEY;
	$tfamlist = array();
	$temp = $SHOW_MARRIED_NAMES;
	$SHOW_MARRIED_NAMES = false;
	$myindilist = GetSurnameIndis($surname, $allgeds);
	$SHOW_MARRIED_NAMES = $temp;
	$select = array();
	foreach($myindilist as $gid=>$indi) {
		$indi = $indilist[$gid];
		$ct = preg_match_all("/1 FAMS @(.*)@/", $indi["gedcom"], $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$famid = $match[$i][1];
			if ($COMBIKEY) $thisged = SplitKey($gid, "gedid");
			else $thisged = $GEDCOMID;
			$select[JoinKey($famid, $thisged)] = true;
		}
	}
	
	$selection = "";
	foreach ($select as $id => $value) {
		$selection .= "'".$id."', ";
	}
	if (!empty($selection)) {
		$selection = substr($selection, 0, strlen($selection)-2);
		GetFamlist($allgeds, $selection);
	}
	if (count($famlist) > 0) {
		$select = array();
		foreach ($famlist as $key => $fam) {
			if (!empty($fam["HUSB"]) && !isset($indilist[JoinKey($fam["HUSB"], $fam["gedfile"])])) $select[JoinKey($fam["HUSB"], $fam["gedfile"])] = true;
			if (!empty($fam["WIFE"]) && !isset($indilist[JoinKey($fam["WIFE"], $fam["gedfile"])])) $select[JoinKey($fam["WIFE"], $fam["gedfile"])] = true;
		}
		$selection = "";
		if (count($select) > 0) {
			foreach ($select as $id => $value) {
				$selection .= "'".$id."', ";
			}
			$selection = substr($selection, 0, strlen($selection)-2);
			GetIndilist($allgeds, $selection, false);
		}
	}
	foreach($myindilist as $gid=>$indi) {
		$indi = $indilist[$gid];
		$ct = preg_match_all("/1 FAMS @(.*)@/", $indi["gedcom"], $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$famid = $match[$i][1];
			$thisged = splitkey($gid, "ged");
			$thisgedid = splitkey($gid, "gedid");			
			$famrec = FindFamilyRecord($famid, $thisged);
			if ($COMBIKEY) $fkey = JoinKey($famid, $thisgedid);
			else $fkey = $famid;
			if ($famlist[$fkey]["HUSB"]==splitkey($gid, "id")) {
				$HUSB = $famlist[$fkey]["HUSB"];
				$WIFE = $famlist[$fkey]["WIFE"];
			}
			else {
				$HUSB = $famlist[$fkey]["WIFE"];
				$WIFE = $famlist[$fkey]["HUSB"];
			}
			$hname = "";
			foreach($indi["names"] as $indexval => $namearray) {
				if (stristr($namearray[2], $surname)!==false) $hname = SortableNameFromName($namearray[0]);
			}
			if (!empty($hname)) {
				SwitchGedcom($thisged);
				$wname = GetSortableName($WIFE);
				if (hasRTLText($hname)) {
					$indirec = FindPersonRecord($WIFE);
					if ($COMBIKEY) $wkey = JoinKey($WIFE, $GEDCOMID);
					else $wkey = $WIFE;
					if (isset($indilist[$wkey])) {
						foreach($indilist[$wkey]["names"] as $n=>$namearray) {
							if (hasRTLText($namearray[0])) {
								$wname = SortableNameFromName($namearray[0]);
								break;
							}
						}
					}
				}
				SwitchGedcom();
				$name = $hname ." + ". $wname;
				$famlist[$fkey]["name"] = $name;
				$tfamlist[$fkey] = $famlist[$fkey];
			}
		}
	}
	//-- handle the special case for @N.N. when families don't have any husb or wife
	//-- SHOULD WE SHOW THE UNDEFINED? MA
	if ($surname=="@N.N.") {
		$sql = "SELECT * FROM ".$TBLPREFIX."families WHERE f_husb=''";
		if ($allgeds != "yes") $sql .=  "AND f_file='".$GEDCOMID."'";
		$sql .= "UNION SELECT * FROM ".$TBLPREFIX."families WHERE f_wife=''";
		if ($allgeds != "yes") $sql .=  "AND f_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		if ($res->NumRows()>0) {
			while($row = $res->FetchAssoc()){
				$fam = array();
				$hname = GetSortableName($row["f_husb"]);
				$wname = GetSortableName($row["f_wife"]);
				if (empty($hname)) $hname = "@N.N., @P.N.";
				if (empty($wname)) $wname = "@N.N., @P.N.";
				if (empty($row["f_husb"])) $name = $hname." + ".$wname;
				else $name = $wname." + ".$hname;
				$fam["name"] = $name;
				$fam["HUSB"] = $row["f_husb"];
				$fam["WIFE"] = $row["f_wife"];
				$fam["CHIL"] = $row["f_chil"];
				$fam["gedcom"] = $row["f_gedcom"];
				$fam["gedfile"] = $row["f_file"];
				if ($COMBIKEY) $key = $row["f_key"];
				else $key = $row["f_id"];
				$tfamlist[$key] = $fam;
				//-- cache the items in the lists for improved speed
				$famlist[$key] = $fam;
			}
		}
		$res->FreeResult();
	}
	return $tfamlist;
}

//-- function to find the gedcom id for the given rin
function FindRinId($rin) {
	global $TBLPREFIX, $GEDCOMID, $DBCONN;

	$sql = "SELECT i_id FROM ".$TBLPREFIX."individuals WHERE i_rin='".$rin."' AND i_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		return $row["i_id"];
	}
	return $rin;
}

function DeleteGedcom($ged) {
	global $INDEX_DIRECTORY, $TBLPREFIX, $DBCONN, $GEDCOMS;

	$dbged = $GEDCOMS[$ged]["id"];
	$sql = "DELETE FROM ".$TBLPREFIX."blocks WHERE b_username='".$DBCONN->EscapeQuery($ged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."changes WHERE ch_gedfile='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."dates WHERE d_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."eventcache WHERE ge_gedcom='".$DBCONN->EscapeQuery(get_gedcom_from_id($dbged))."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."families WHERE f_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."favorites WHERE fv_file='".$DBCONN->EscapeQuery(get_gedcom_from_id($ged))."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."individual_family WHERE if_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."individuals WHERE i_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."log WHERE l_gedcom='".$DBCONN->EscapeQuery(get_gedcom_from_id($dbged))."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."media WHERE m_gedfile='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."names WHERE n_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."news WHERE n_username='".$DBCONN->EscapeQuery(get_gedcom_from_id($ged))."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."other WHERE o_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."other_mapping WHERE om_gedfile='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."placelinks WHERE pl_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."places WHERE p_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."sources WHERE s_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."source_mapping WHERE sm_gedfile='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."statscache WHERE gs_gedcom='".$DBCONN->EscapeQuery(get_gedcom_from_id($dbged))."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."counters WHERE c_id LIKE '%[".$DBCONN->EscapeQuery($dbged)."]%'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."users_gedcoms WHERE ug_gedfile='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."pdata WHERE pd_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."soundex WHERE s_file='".$DBCONN->EscapeQuery($dbged)."'";
	$res = NewQuery($sql);
}

//-- return the current size of the given list
//- list options are indilist famlist sourcelist and otherlist
function GetListSize($list) {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $GEDCOMS, $GEDCOMID;

	switch($list) {
		case "indilist":
			$sql = "SELECT count(i_id) FROM ".$TBLPREFIX."individuals WHERE i_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "famlist":
			$sql = "SELECT count(f_id) FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "sourcelist":
			$sql = "SELECT count(s_id) FROM ".$TBLPREFIX."sources WHERE s_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "otherlist":
			$sql = "SELECT count(o_id) FROM ".$TBLPREFIX."other WHERE o_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "medialist":
			$sql = "SELECT count(m_id) FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."'";
			$res = NewQuery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "notelist":
			$sql = "SELECT count(o_id) FROM ".$TBLPREFIX."other WHERE o_file='".$GEDCOMID."' AND o_type='NOTE'";
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
	global $GEDCOM, $TBLPREFIX, $FILE, $DBCONN, $GEDCOMS, $gm_username, $chcache;
	global $MEDIA_ID_PREFIX, $FAM_ID_PREFIX, $GEDCOM_ID_PREFIX, $SOURCE_ID_PREFIX, $REPO_ID_PREFIX, $NOTE_ID_PREFIX;
	
	$cidchanges = array();
	if ($all) $sql = "SELECT ch_id, ch_cid, ch_gid, ch_gedfile, ch_old, ch_new, ch_type, ch_user, ch_time FROM ".$TBLPREFIX."changes WHERE ch_gedfile = '".$gedfile."' ORDER BY ch_id ASC";
	else $sql = "SELECT ch_id, ch_cid, ch_gid, ch_gedfile, ch_old, ch_new, ch_type, ch_user, ch_time FROM ".$TBLPREFIX."changes WHERE ch_cid = '".$cid."' AND ch_gedfile = '".$gedfile."' ORDER BY ch_id ASC";
	$res = NewQuery($sql);
	while ($row = $res->FetchAssoc()) {
		$cidchanges[$row["ch_id"]]["cid"] = $row["ch_cid"];
		$cidchanges[$row["ch_id"]]["gid"] = $row["ch_gid"];
		$cidchanges[$row["ch_id"]]["gedfile"] = $row["ch_gedfile"];
		$cidchanges[$row["ch_id"]]["old"] = $row["ch_old"];
		$cidchanges[$row["ch_id"]]["new"] = $row["ch_new"];
		$cidchanges[$row["ch_id"]]["type"] = $row["ch_type"];
		$cidchanges[$row["ch_id"]]["user"] = $row["ch_user"];
		$cidchanges[$row["ch_id"]]["time"] = $row["ch_time"];
	}
	if (count($cidchanges) > 0) {
		foreach ($cidchanges as $id => $details) {
			$FILE = get_gedcom_from_id($details["gedfile"]);
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
				$update_id = UpdateRecord(CheckGedcom($gedrec, true, $details["user"], $details["time"]));
			}
			
			// NOTE: Add new ID
			// NOTE: If the old is empty and the new is a new record make sure we just store the new record
			else if (empty($details["old"]) && preg_match("/0\s@(.*)@/", $details["new"]) > 0) {
				// print "New gedrec: ".$details["new"]."<br />";
				$update_id = UpdateRecord(CheckGedcom($details["new"], true, $details["user"], $details["time"]));
			}
			
			// Note: Delete ID
			// NOTE: if old is not empty and new is  empty, AND it's 0-level, the record needs to be deleted
			else if (!empty($details["old"]) && empty($details["new"])&& preg_match("/0\s@(.*)@/", $details["old"]) > 0) {
				$update_id = UpdateRecord(CheckGedcom(FindGedcomRecord($details["gid"]), true, $details["user"], $details["time"]), true);
				
				// NOTE: Delete change records related to this record
				$sql = "select ch_cid from ".$TBLPREFIX."changes where ch_gid = '".$details["gid"]."' AND ch_gedfile = '".$details["gedfile"]."'";
				$res = NewQuery($sql);
				while ($row = $res->FetchAssoc()) {
					RejectChange($row["ch_cid"], $details["gedfile"]);
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
				$update_id = UpdateRecord(CheckGedcom($gedrec, true, $details["user"], $details["time"]));
			}
			WriteToLog("AcceptChange-> Accepted change for ".$details["gid"].". ->".$gm_username."<-", "I", "G", get_gedcom_from_id($gedfile));
		}
		ResetCaches($GEDCOM);
		ResetChangeCaches();
	}
	// NOTE: record has been imported in DB, now remove the change
	foreach ($cidchanges as $id => $value) {
		$sql = "DELETE from ".$TBLPREFIX."changes where ch_cid = '".$value["cid"]."'";
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
	global $GEDCOMS, $GEDCOM, $TBLPREFIX, $manual_save, $gm_username;
	
	// NOTE: Get the details of the change id, to check if we need to unlock any records
	$sql = "SELECT ch_type, ch_gid from ".$TBLPREFIX."changes where ch_cid = '".$cid."' AND ch_gedfile = '".$gedfile."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()) {
		$unlock_changes = array("raw_edit", "reorder_families", "reorder_children", "delete_source", "delete_indi", "delete_family", "delete_repo");
		if (in_array($row["ch_type"], $unlock_changes)) {
			$sql = "select ch_cid, ch_type from ".$TBLPREFIX."changes where ch_gid = '".$row["ch_gid"]."' and ch_gedfile = '".$gedfile."' order by ch_cid ASC";
			$res2 = NewQuery($sql);
			while($row2 = $res2->FetchAssoc()) {
				$sqlcid = "UPDATE ".$TBLPREFIX."changes SET ch_delete = '0' WHERE ch_cid = '".$row2["ch_cid"]."'";
				$rescid = NewQuery($sqlcid);
			}
		}
	}
	
	if ($all) {
		$sql = "DELETE from ".$TBLPREFIX."changes where ch_gedfile = '".$gedfile."'";
		if ($res = NewQuery($sql)) {
			WriteToLog("RejectChange-> Rejected all changes for $gedfile "." ->" . $gm_username ."<-", "I", "G", get_gedcom_from_id($gedfile));
			return true;
		}
		else return false;
	}
	else {
		$sql = "DELETE from ".$TBLPREFIX."changes where ch_cid = '".$cid."' AND ch_gedfile = '".$gedfile."'";
		if ($res = NewQuery($sql)) {
			WriteToLog("RejectChange-> Rejected change $cid - $gedfile "." ->" . $gm_username ."<-", "I", "G", get_gedcom_from_id($gedfile));
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
	global $TBLPREFIX, $GEDCOM, $DBCONN, $GEDCOMS, $FILE, $GEDCOMID, $PEDIGREE_ROOT_ID, $Users, $Privacy;

	if (empty($FILE)) $FILE = $GEDCOM;
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
	
	$sql = "SELECT pl_p_id FROM ".$TBLPREFIX."placelinks WHERE pl_gid='".$DBCONN->EscapeQuery($gid)."' AND pl_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	$placeids = array();
	while($row = $res->fetchRow()) {
		$placeids[] = $row[0];
	}
	$sql = "DELETE FROM ".$TBLPREFIX."placelinks WHERE pl_gid='".$DBCONN->EscapeQuery($gid)."' AND pl_file='".$GEDCOMID."'";
	$res = NewQuery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."dates WHERE d_key='".$DBCONN->EscapeQuery(JoinKey($gid, $GEDCOMID))."'";
	$res = NewQuery($sql);

	//-- delete any unlinked places
	foreach($placeids as $indexval => $p_id) {
		$sql = "SELECT count(pl_p_id) FROM ".$TBLPREFIX."placelinks WHERE pl_p_id=$p_id AND pl_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$row = $res->fetchRow();
		if ($row[0]==0) {
			$sql = "DELETE FROM ".$TBLPREFIX."places WHERE p_id=$p_id AND p_file='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
	}

	//-- delete any MM links to this pid
		$sql = "DELETE FROM ".$TBLPREFIX."media_mapping WHERE mm_gid='".$DBCONN->EscapeQuery($gid)."' AND mm_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
	
	if ($type=="INDI") {
		// First reset the isdead status for the surrounding records. 
		ResetIsDeadLinked($gid, "INDI");
		$sql = "DELETE FROM ".$TBLPREFIX."individuals WHERE i_id='".$DBCONN->EscapeQuery($gid)."' AND i_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."names WHERE n_gid='".$DBCONN->EscapeQuery($gid)."' AND n_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."soundex WHERE s_gid='".$DBCONN->EscapeQuery($kgid)."'";
		$res = NewQuery($sql);
		// Only delete the fam-indi info if the whole individual is deleted. 
		// Otherwise the info does not get reconstructed as some of it is in the family records (order).
		if ($delete) {
			$sql = "DELETE FROM ".$TBLPREFIX."individual_family WHERE if_pkey='".JoinKey($DBCONN->EscapeQuery($gid), $GEDCOMID)."'";
			$res = NewQuery($sql);
		}
		$sql = "DELETE FROM ".$TBLPREFIX."source_mapping WHERE sm_gid='".$DBCONN->EscapeQuery($gid)."' AND sm_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."other_mapping WHERE om_gid='".$DBCONN->EscapeQuery($gid)."' AND om_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
	}
	else if ($type=="FAM") {
		// First reset the isdead status for the surrounding records. 
		ResetIsDeadLinked($gid, "FAM");
		$sql = "DELETE FROM ".$TBLPREFIX."families WHERE f_id='".$DBCONN->EscapeQuery($gid)."' AND f_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		// Only delete the fam-indi info if the whole family is deleted. 
		// Otherwise the info does not get reconstructed as most of it is in the individual records.
		if ($delete) {
			$sql = "DELETE FROM ".$TBLPREFIX."individual_family WHERE if_fkey='".JoinKey($DBCONN->EscapeQuery($gid), $GEDCOMID)."'";
			$res = NewQuery($sql);
		}
		$sql = "DELETE FROM ".$TBLPREFIX."source_mapping WHERE sm_gid='".$DBCONN->EscapeQuery($gid)."' AND sm_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."other_mapping WHERE om_gid='".$DBCONN->EscapeQuery($gid)."' AND om_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
	}
	else if ($type=="SOUR") {
		$sql = "DELETE FROM ".$TBLPREFIX."sources WHERE s_id='".$DBCONN->EscapeQuery($gid)."' AND s_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		// We must preserve the links if the record is just changed and not deleted. 
		if ($delete) {
			$sql = "DELETE FROM ".$TBLPREFIX."source_mapping WHERE sm_sid='".$DBCONN->EscapeQuery($gid)."' AND sm_gedfile='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
	}
	else if ($type == "OBJE") {
		$sql = "DELETE FROM ".$TBLPREFIX."media WHERE m_media='".$DBCONN->EscapeQuery($gid)."' AND m_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."source_mapping WHERE sm_gid='".$DBCONN->EscapeQuery($gid)."' AND sm_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."other_mapping WHERE om_gid='".$DBCONN->EscapeQuery($gid)."' AND om_gedfile='".$GEDCOMID."'";
	}
	else {
		$sql = "DELETE FROM ".$TBLPREFIX."other WHERE o_id='".$DBCONN->EscapeQuery($gid)."' AND o_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."source_mapping WHERE sm_gid='".$DBCONN->EscapeQuery($gid)."' AND sm_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
		// We must preserve the links if the record is just changed and not deleted. 
		if ($delete) {
			$sql = "DELETE FROM ".$TBLPREFIX."other_mapping WHERE om_gid='".$DBCONN->EscapeQuery($gid)."' AND om_gedfile='".$GEDCOMID."'";
			$res = NewQuery($sql);
		}
	}
	if ($delete) {
		if ($type == "FAM" || $type = "INDI" || $type == "SOUR" || $type == "OBJE") {
			// Delete favs
			$sql = "DELETE FROM ".$TBLPREFIX."favorites WHERE fv_gid='".$gid."' AND fv_type='".$type."' AND fv_file='".$GEDCOM."'";
			$res = NewQuery($sql);
		}
		if ($type == "INDI") {
			// Clear users
			$Users->ClearUserGedcomIDs($gid, $GEDCOM);
			if ($PEDIGREE_ROOT_ID == $gid) {
				$PEDIGREE_ROOT_ID = "";
				$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_pedigree_root_id='' WHERE gc_gedcom='".$GEDCOM."'";
				$res = NewQuery($sql);
			}
		}
		// Clear privacy
		$Privacy->ClearPrivacyGedcomIDs($gid, $GEDCOM);
	}

	if (!$delete) {
		ImportRecord($indirec, true);
	}
}

/**
 * get the top surnames
 * @param int $num	how many surnames to return
 * @return array
 */
function GetTopSurnames($num) {
	global $TBLPREFIX, $DBCONN, $COMMON_NAMES_REMOVE, $GEDCOMID;

	//-- Exclude the common surnames to be removed
	$delnames = array();
	if ($COMMON_NAMES_REMOVE != "") {
		$delnames = preg_split("/[,;] /", $COMMON_NAMES_REMOVE);
	}
	$delstri = "";
	$delstrn = "";
	foreach($delnames as $key => $delname) {
		$delstri .= " AND i_surname<>'".$delname."'";
		$delstrn .= " AND n_surname<>'".$delname."'";
	}
	//-- Perform the query
	$surnames = array();
	$sql = "(SELECT COUNT(n_surname) as count, n_surname FROM ".$TBLPREFIX."names WHERE n_file='".$GEDCOMID."' AND n_type!='C' AND n_surname<>'@N.N.'".$delstrn." GROUP BY n_surname) ORDER BY count DESC LIMIT ".$num;
	$res = NewQuery($sql);
	if ($res) {
		while($row = $res->FetchRow()) {
			if (isset($surnames[Str2Upper($row[1])]["match"])) $surnames[Str2Upper($row[1])]["match"] += $row[0];
			else {
				$surnames[Str2Upper($row[1])]["name"] = $row[1];
				$surnames[Str2Upper($row[1])]["match"] = $row[0];
			}
		}
		$res->FreeResult();
	}
	return $surnames;
}

/**
 * get next unique id for the given table
 * @param string $table 	the name of the table
 * @param string $field		the field to get the next number for
 * @return int the new id
 */
function GetNextId($table, $field) {
	global $TBLPREFIX;

	$newid = 0;
	$sql = "SELECT MAX(".$field.") FROM ".$TBLPREFIX.$table;
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
 * Retrieve the array of faqs from the DB table blocks
 *
 * @package Genmod
 * @subpackage FAQ
 * @param int $id		The FAQ ID to retrieve
 * @return array $faqs	The array containing the FAQ items
 */
function GetFaqData($id='') {
	global $TBLPREFIX, $GEDCOM;
	
	$faqs = array();
	// Read the faq data from the DB
	$sql = "SELECT b_id, b_location, b_order, b_config FROM ".$TBLPREFIX."blocks WHERE b_username='$GEDCOM' AND (b_location='header' OR b_location = 'body')";
	if ($id != '') $sql .= "AND b_order='".$id."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$faqs[$row["b_order"]][$row["b_location"]]["text"] = unserialize($row["b_config"]);
		$faqs[$row["b_order"]][$row["b_location"]]["pid"] = $row["b_id"];
	}
	ksort($faqs);
	return $faqs;
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
function WriteToLog($LogString, $type="I", $cat="S", $ged="", $chkconn = true) {
	global $TBLPREFIX, $GEDCOM, $GEDCOMS, $gm_username, $DBLAYER, $INDEX_DIRECTORY;
	
	$user = $gm_username;
	
	// -- Remove the " from the logstring, as this disturbs the export
	$LogString = str_replace("\"", "'", $LogString);
	
	// If Type is error, set to new for warning on admin pages
	if ($type == "E") $new = "1";
	else $new = "0";
	
	if ($chkconn && (!is_object($DBLAYER) || (isset($DBLAYER->connected) && !$DBLAYER->connected))) {
		if ($cat == "S") {
			$emlog = $INDEX_DIRECTORY."emergency_syslog.txt";
			$string = "INSERT INTO ".$TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_gedcom, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($user)."', '".addslashes($LogString)."', '', '".$new."')\r\n";
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
		$sql = "INSERT INTO ".$TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_gedcom, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($user)."', '".addslashes($LogString)."', '', '".$new."')";
		$res = NewQuery($sql);
		return;
	}
	if ($cat == "G") {
		$sql = "INSERT INTO ".$TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_gedcom, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($user)."', '".addslashes($LogString)."', '".$ged."', '".$new."')";
		$res = NewQuery($sql);
		return;
	}
	if ($cat == "F") {
		if (!isset($ged)) return;
		if (count($ged) == 0) return;
		foreach($ged as $indexval => $value) {
			$sql = "INSERT INTO ".$TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_gedcom, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($user)."', '".addslashes($LogString)."', '".$value."', '".$new."')";
			$res = NewQuery($sql);
		}
		return;
	}
}

/**
 * Read the Log records from the database for display
 *
 * The function reads the records that are logged for
 * either the Syetem Log, the Gedcom Log or the Search
 * Log. It returns the records in an array for further 
 * processing in the log viewer.
 *
 * @author	Genmod Development Team
 * @param		string	$cat	Category of log records:
 *								S = System Log
 *								G = Gedcom Log
 *								F = Search Log
 * @param		integer	$max	Maximum number of records to be returned
 * @param		string	$type	Type of record:
 *								I = Information
 *								W = Warning
 *								E = Error
 * @param		string	$ged	Used with Gedcom Log and Search Log
 *								Gedcom the Log record applies to
 * @param		boolean $last	If true, return oldest log entries
 * @param		boolean $count	If true, return the number of logrecords matching criteria
 * @return 		array			Array with log records
 */
function ReadLog($cat, $max="20", $type="", $ged="", $last=false, $count=false) {
	global $TBLPREFIX;
	
	if (!$count) {
		$sql = "SELECT * FROM ".$TBLPREFIX."log WHERE l_category='".$cat."'";
		if (!empty($type)) $sql .= " AND l_type='".$type."'";
		if (!empty($ged)) $sql .= " AND l_gedcom='".$ged."'";
		if ($last == false) $sql .= " ORDER BY l_num DESC";
		else $sql .= " ORDER BY l_num ASC";
		if ($max != "0") $sql .= " LIMIT ".$max;
		$res = NewQuery($sql);
		$loglines = array();
		if ($res) {
			while($log_row = $res->FetchAssoc($res->result)){
				$logline = array();
				$logline["type"] = $log_row["l_type"];
				$logline["category"] = $log_row["l_category"];
				$logline["time"] = $log_row["l_timestamp"];
				$logline["ip"] = $log_row["l_ip"];
				$logline["user"] = $log_row["l_user"];
				$logline["text"] = $log_row["l_text"];
				$logline["gedcom"] = $log_row["l_gedcom"];
				$loglines[] = $logline;
			}
		}
		$res->FreeResult();
		return $loglines;
	}
	else {
		$sql = "SELECT COUNT(l_type) FROM ".$TBLPREFIX."log WHERE l_category='".$cat."'";
		if (!empty($type)) $sql .= " AND l_type='".$type."'";
		if (!empty($ged)) $sql .= " AND l_gedcom='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			$number = $res->FetchRow();
			return $number[0];
		}
	}
}

function NewLogRecs($cat, $ged="") {
	global $TBLPREFIX;
	
	$sql = "SELECT count('i_type') FROM ".$TBLPREFIX."log WHERE l_category='".$cat."' AND l_type='E' AND l_new='1'";
	if (!empty($ged)) $sql .= " AND l_gedcom='".$ged."'";
	$res = NewQuery($sql);
	if ($res) {
		$number = $res->FetchRow();
		return $number[0];
	}
	return false;
}

function HaveReadNewLogrecs($cat, $ged="") {
	global $TBLPREFIX;
	
	$sql = "UPDATE ".$TBLPREFIX."log SET l_new='0' WHERE l_category='".$cat."' AND l_type='E' AND l_new='1'";
	if (!empty($ged)) $sql .= " AND l_gedcom='".$ged."'";
	$res = NewQuery($sql);
}

function ImportEmergencyLog() {
	global $TBLPREFIX, $gm_lang, $INDEX_DIRECTORY;

	// If we cannot read/delete the file, don't process it.
	$filename = $INDEX_DIRECTORY."emergency_syslog.txt";
	if (!FileIsWriteable($filename)) return $gm_lang["emergency_log_noprocess"];
	
	// Read the contents
	$handle = fopen($filename, "r");
	$contents = fread($handle, filesize($filename));
	fclose($handle);
	$lines = split("\r\n", $contents);
	
	//Process the queries
	foreach($lines as $key=>$line) {
		if (strlen($line) > 6 && substr($line, 0, 6) == "INSERT") $res = NewQuery($line);
	}

	//Delete the file
	unlink($filename);
	
	return $gm_lang["emergency_log_exists"];
}
	
function IsChangedFact($gid, $oldfactrec) {
	global $GEDCOMID, $TBLPREFIX, $gm_username, $show_changes, $Users;
	
//print "checking ".$gid." ".$oldfactrec."<br />";
	if ((!isset($show_changes) || $show_changes != "no") && $Users->UserCanEditOwn($gm_username, $gid) && GetChangeData(true, $gid, true)) {
		$string = trim($oldfactrec);
		if (empty($string)) return false;
		$sql = "SELECT ch_old, ch_new FROM ".$TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_gedfile = '".$GEDCOMID."' ORDER BY ch_time ASC";
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
	global $GEDCOMID, $TBLPREFIX, $show_changes, $gm_username, $Users;
	
	if ((!isset($show_changes) || $show_changes != "no") && $Users->UserCanEditOwn($gm_username, $gid) && GetChangeData(true, $gid, true)) {
		$sql = "SELECT ch_old, ch_new FROM ".$TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_fact = '".$fact."' AND ch_gedfile = '".$GEDCOMID."' ORDER BY ch_time ASC";
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
	global $GEDCOMID, $TBLPREFIX, $show_changes, $gm_username;
	global $gm_lang;
	
	$facts = array();
	$newfacts = array();
	if ((!isset($show_changes) || $show_changes != "no") && GetChangeData(true, $gid, true)) {
		$sql = "SELECT ch_old, ch_new FROM ".$TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_gedfile = '".$GEDCOMID."' ORDER BY ch_time ASC";
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
						$fact["old"] .= "\n2 DATE (".strtolower($gm_lang["delete"]).")";
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
	global $GEDCOM;
	
	if (empty($gedrec)) return false;
	$ct = preg_match_all("/\d\sOBJE\s@(.*)@/", $gedrec, $match);
	for ($i=0;$i<$ct;$i++) {
		if (GetChangeData(true, $match[1][$i], true)) return true;
	}
	return false;
}

/**
 * Store the GEDCOMS array in the database
 *
 * The function takes the GEDCOMS array and stores all
 * content in the database, including DEFAULT_GEDCOM.
 *
 * @author	Genmod Development Team
 */
function StoreGedcoms() {
	global $GEDCOMS, $gm_lang, $INDEX_DIRECTORY, $DEFAULT_GEDCOM, $COMMON_NAMES_THRESHOLD, $GEDCOM, $CONFIGURED, $TBLPREFIX, $DBCONN;

	if (!$CONFIGURED) return false;
	uasort($GEDCOMS, "GedcomSort");
	$maxid = 0;
	foreach ($GEDCOMS as $name => $details) {
		if (isset($details["id"]) && $details["id"] > $maxid) $maxid = $details["id"];
	}
	// -- For now, we update the gedcoms table by rewriting it
	$sql = "DELETE FROM ".$TBLPREFIX."gedcoms";
	$res = NewQuery($sql);
	
	$maxid++;
	foreach($GEDCOMS as $indexval => $GED) {
		$GED["config"] = str_replace($INDEX_DIRECTORY, "\${INDEX_DIRECTORY}", $GED["config"]);
		if (isset($GED["privacy"])) $GED["privacy"] = str_replace($INDEX_DIRECTORY, "\${INDEX_DIRECTORY}", $GED["privacy"]);
		else $GED["privacy"] = "privacy.php";
		$GED["path"] = str_replace($INDEX_DIRECTORY, "\${INDEX_DIRECTORY}", $GED["path"]);
		$GED["title"] = stripslashes($GED["title"]);
		$GED["title"] = preg_replace("/\"/", "\\\"", $GED["title"]);
		// TODO: Commonsurnames from an old gedcom are used
		// TODO: Default GEDCOM is changed to last uploaded GEDCOM

		// NOTE: Set the GEDCOM ID
		if (!isset($GED["id"]) || (empty($GED["id"]))) $GED["id"] = $maxid;

		if (empty($GED["commonsurnames"])) {
			if ($GED["gedcom"]==$GEDCOM) {
				$GED["commonsurnames"] = "";
				$surnames = GetCommonSurnames($COMMON_NAMES_THRESHOLD);
				foreach($surnames as $indexval => $surname) {
					$GED["commonsurnames"] .= $surname["name"].", ";
				}
			}
			else $GED["commonsurnames"]="";
		}
		$GEDCOMS[$GED["gedcom"]]["commonsurnames"] = $GED["commonsurnames"];
//		$GED["commonsurnames"] = addslashes($GED["commonsurnames"]);
		if ($GED["gedcom"] == $DEFAULT_GEDCOM) $is_default = "Y";
		else $is_default = "N";
		$sql = "INSERT INTO ".$TBLPREFIX."gedcoms VALUES('".$DBCONN->EscapeQuery($GED["gedcom"])."','".$DBCONN->EscapeQuery($GED["config"])."','".$DBCONN->EscapeQuery($GED["privacy"])."','".$DBCONN->EscapeQuery($GED["title"])."','".$DBCONN->EscapeQuery($GED["path"])."','".$DBCONN->EscapeQuery($GED["id"])."','".$DBCONN->EscapeQuery($GED["commonsurnames"])."','".$DBCONN->EscapeQuery($is_default)."')";
		$res = NewQuery($sql);
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
	global $GEDCOMS, $DEFAULT_GEDCOM, $DEFAULT_GEDCOMID, $TBLPREFIX, $INDEX_DIRECTORY;
	$GEDCOMS=array();
	$DEFAULT_GEDCOM = "";
	$DEFAULT_GEDCOMID = "";
	$sql = "SELECT * FROM ".$TBLPREFIX."gedcoms ORDER BY g_title";
	$res = NewQuery($sql);
	if ($res) {
		$ct = $res->NumRows();
		$i = "0";
		if ($ct) {
			while($row = $res->FetchAssoc()){
				$g = array();
				$g["gedcom"] = $row["g_gedcom"];
				$g["config"] = str_replace("\${INDEX_DIRECTORY}", $INDEX_DIRECTORY, $row["g_config"]);
				$g["privacy"] = str_replace("\${INDEX_DIRECTORY}", $INDEX_DIRECTORY, $row["g_privacy"]);
				$g["title"] = $row["g_title"];
				$g["path"] = str_replace("\${INDEX_DIRECTORY}", $INDEX_DIRECTORY, $row["g_path"]);
				$g["id"] = $row["g_id"];
				$g["commonsurnames"] = $row["g_commonsurnames"];
				if ($row["g_isdefault"] == "Y") {
					$DEFAULT_GEDCOM = $row["g_gedcom"];
					$DEFAULT_GEDCOMID = $row["g_id"];
				}
				$GEDCOMS[$row["g_gedcom"]] = $g;
				if ($i == "0") {
					$DEFAULT_GEDCOM = $row["g_gedcom"];
					$DEFAULT_GEDCOMID = $row["g_id"];
				}
				$i++;
			}
			$res->FreeResult();
		}
	}
}

/**
 * Update page counters
 *
 * The function updates the hit counters for the index
 * pages of the different gedcoms and for the individuals.
 * Hits are stored in the database.
 *
 * @author	Genmod Development Team
 * @param		string	$id		Indi id or "Index" for index page. Format: I5[myged.ged]
 * @return 	number	$hits	The new value for the hitcounter
 */
function UpdateCounter($id, $bot=false) {
	global $TBLPREFIX;

	if ($bot) $sql = "SELECT c_bot_number FROM ".$TBLPREFIX."counters WHERE (c_id='".$id."')";
	else $sql = "SELECT c_number FROM ".$TBLPREFIX."counters WHERE (c_id='".$id."')";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	if ($ct == "0") {
		if ($bot) $sql = "INSERT INTO ".$TBLPREFIX."counters VALUES ('".$id."', '0', '1')";
		else $sql = "INSERT INTO ".$TBLPREFIX."counters VALUES ('".$id."', '1', '0')";
	  	$res = NewQuery($sql);
		return 1;
	}
	else {
		while($row = $res->FetchAssoc()){
			if ($bot) $hits = $row["c_bot_number"];
			else $hits = $row["c_number"];
		}
		$res->FreeResult();
		if ($bot) $sql = "UPDATE ".$TBLPREFIX."counters SET c_bot_number=c_bot_number+1 WHERE c_id='".$id."'"; 
		else $sql = "UPDATE ".$TBLPREFIX."counters SET c_number=c_number+1 WHERE c_id='".$id."'"; 
  		$res = NewQuery($sql);
		return $hits+1;
	}
}

/** Store the Gedcom configuration settings in the database
 *
 * The function stores all GEDCOM configuration settings in the database.
 * It also sets the execution time limit to the new value.
 *
 * @author	Genmod Development Team
 * @param		array	$settings	Array with GEDCOM settings
**/
function StoreGedcomConfig($settings) {
	global $TBLPREFIX, $GEDCOM, $GEDCONF;
	
	// Clear the cache
	$GEDCONF = array();

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
		
/** Read the Gedcom configuration settings from the database
 *
 * The function reads the GEDCOM configuration settings from the database.
 * It also sets the max execution time to the new value, if it's the current GEDCOM.
 *
 * @author	Genmod Development Team
 * @param		string	$gedcom		GEDCOM name for which the values are to be retrieved.
 * @return 	boolean		true if success, false if failed
**/
function ReadGedcomConfig($gedcom) {
	global $TBLPREFIX, $GEDCOM, $GEDCONF;

	if (!isset($GEDCONF)) $GEDCONF = array();
	
	if (isset($GEDCONF[$gedcom])) {
		foreach ($GEDCONF[$gedcom] as $var => $value) {
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
			$GEDCONF[$gedcom] = $gc;
			$res->FreeResult($res->result);
		}
	}
	//-- This is copied from the config_gedcom.php
	if ($gedcom == $GEDCOM) @set_time_limit($TIME_LIMIT);
	return true;
}
		
/** Delete Gedcom configuration settings from the database
 *
 * The function deletes GEDCOM configuration settings from the database.
 *
 * @author	Genmod Development Team
 * @param		string	$gedcom		GEDCOM name for which the values are to be deleted.
**/
function DeleteGedcomConfig($gedcom) {
	global $TBLPREFIX, $DBLAYER, $GEDCONF;

	if (!$DBLAYER->connected) return false;
	$GEDCONF = array();
	$sql = "DELETE FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$gedcom."'";
	$res = NewQuery($sql);
	return;
}		

/** Export a table and write the result to file
 *
 * This function makes dumps of MySQL tables into a file.
 * It can also join several dumps into one file to keep them together.
 * The filename will default to the last read table name.
 * As Genmod uses linebreaks in the database fields, the SQL files
 * CANNOT be imported by DB-management tools.
 *
 * @author	Genmod Development Team
 * @param		string/array	$table		String or array with table names to be exported.
 * @param		string			$join	String yes/no to dump multiple tables in one file or create multiple files.
 * @param		string			$newname	Only valid if one file or multiple joined files: filename to use for output.
 * @return	array			$fn		Array with names of created files
 *
**/
function ExportTable($table, $join="no", $newname="") {
	global $TBLPREFIX, $INDEX_DIRECTORY;

	$tables = array();
	$fn = array();
	if (!is_array($table)) $tables[] = $table;
	else $tables = $table;
	$outstr = "";
	foreach($tables as $tabkey=>$tabname) {
		$sql = "SHOW COLUMNS FROM ".$TBLPREFIX.$tabname;
		$res1 = NewQuery($sql);
		$fstring = " (";
		while ($fieldrow = $res1->FetchAssoc()) $fstring .= $fieldrow["Field"].",";
		$fstring = substr($fstring, 0, -1);
		$fstring .= ") ";
		$outstr .= "DELETE FROM ".$TBLPREFIX.$tabname."\r\n";
		$sql = "SELECT * FROM ".$TBLPREFIX.$tabname;
		$res = NewQuery($sql);
		$ct = $res->NumRows($res->result);
		if ($ct != "0") {
			while ($row = $res->FetchAssoc($res->result)) {
				$line = "INSERT INTO ".$TBLPREFIX.$tabname.$fstring."VALUES (";
				$i = 0;
				foreach ($row as $key=>$value) {
					if ($i != "0") $line .= ", ";
					$i++;
					$line .= "'".mysql_real_escape_string($value)."'";
				}
				$line .= ")\r\n";
				$outstr .= $line;
			}
		}
		if (($tabkey == count($tables)-1 && $join == "yes") || $join == "no") {
			if (!empty($newname) && ($join == "yes" || count($tables) == "1")) $tabname = $newname;
			if (file_exists($INDEX_DIRECTORY."export_".$tabname.".sql")) unlink($INDEX_DIRECTORY."export_".$tabname.".sql");

			$fp = fopen($INDEX_DIRECTORY."export_".$tabname.".sql", "w");
			if ($fp) {
				fwrite($fp, $outstr);
				fclose($fp);
				$fn[] = $INDEX_DIRECTORY."export_".$tabname.".sql";
			}
			else return "";
		}
	}
	return $fn;
}

/** Import table(s) from a file
 *
 * This function imports dumps of MySQL tables into the database.
 * If an error is encountered, execution stops and the error is returned.
 * Be extremely careful changing this function, as it deals with query lines
 * spread over multiple lines in the input file.
 *
 * @author	Genmod Development Team
 * @param		string		$fn		Name of the file to be imported
 * @return		string		$error	Either the empty string, or the MySQL error message
 *
**/
function ImportTable($fn) {
	global $TBLPREFIX, $INDEX_DIRECTORY;

	if (file_exists($INDEX_DIRECTORY.$fn)) $sqlines = file($INDEX_DIRECTORY.$fn);
	else return false;

	$sqline = "";
	foreach($sqlines as $key=>$sql) {
		$sqline .= $sql;
		if ((substr(ltrim($sqline), 0, 6) == "INSERT" && substr(rtrim($sqline), -2) == "')") || substr(ltrim($sqline), 0, 6) == "DELETE") {
			$res = NewQuery($sqline);
			$error = mysql_error();
			if (!empty($error)) return $error;
			$sqline = "";
		}
	}
	return "";
}


function GetNewFams($pid) {
	global $GEDCOM, $TBLPREFIX, $GEDCOMID;
	
	$newfams = array();

	if (!empty($pid) && GetChangeData(true, $pid, true, "","")) {
		$rec = GetChangeData(false, $pid, true, "gedlines","");
		$gedrec = $rec[$GEDCOM][$pid];
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
	global $GEDCOM, $GEDCOMS, $TBLPREFIX, $changes, $gm_lang, $GEDCOMID;
	global $chcache, $chstatcache;
	
	// NOTE: If the file does not have an ID, go back
	if (!isset($GEDCOMID)) return false;
	
	// Initialise the results cache
	if (!isset($chcache)) $chcache = array();
	
	// Initialise the status cache
	if (!isset($chstatcache)) {
		$chstatcache = array();
		$sql = "SELECT ch_gid, ch_gedfile FROM ".$TBLPREFIX."changes";
		$resc = NewQuery($sql);
		if($resc) {
			while ($row = $resc->FetchAssoc()) {
				$chstatcache[$row["ch_gid"]][$row["ch_gedfile"]] = true;
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
	if ($thisged) $whereclause .= "ch_gedfile = '".$GEDCOMID."'";
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
		if ($data == "gedcoms") $selectclause = "SELECT ch_gedfile ";
		else {
			$selectclause = "SELECT ch_gid, ch_type, ch_fact, ch_gedfile, ch_old, ch_new";
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

	$sql = $selectclause."FROM ".$TBLPREFIX."changes";
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
				$gedfiles[$row["ch_gedfile"]] = get_gedcom_from_id($row["ch_gedfile"]);
			}
			$chcache[$sql] = $gedfiles;
			return $gedfiles;
		}
		else {
			// NOTE: Construct the changed gedcom record
			$gedlines = array();
			while ($row = $res->FetchAssoc($res->result)) {
				$gedname = get_gedcom_from_id($row["ch_gedfile"]);
				$chgid = $row["ch_gid"];
				if (!isset($gedlines[$gedname][$chgid])) {
					$gedlines[$gedname][$chgid] = trim(FindGedcomRecord($chgid, $gedname));
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
					$gedlines[$gedname][$chgid] = $gm_lang["record_to_be_deleted"];
				}
				
				// NOTE: Replace any other, change or delete from ID
				// NOTE: If new is empty or filled, the old needs to be replaced
				else $gedlines[$gedname][$chgid] = str_replace(trim($row["ch_old"]), $row["ch_new"], $gedlines[$gedname][$chgid]);

				if (isset($row["ch_user"]) && isset($row["ch_time"])) {
require_once("includes/functions_edit.php"); // for checkgedcom
					$gedrecord = $gedlines[$gedname][$chgid];
					if (empty($gedrecord)) {
						// deleted record
						$gedrecord = trim(FindGedcomRecord($chgid, $gedname));
					}
					//LERMAN
					$gedrecord = CheckGedcom($gedrecord, true, $row["ch_user"], $row["ch_time"]);
					$gedlines[$gedname][$chgid] = trim($gedrecord);
				}
			}
//			print_r($gedlines);
//			print "<br /><br />";
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

function GetChangeNames($pid) {
	global $GEDCOM, $GEDCOMS, $TBLPREFIX, $changes, $gm_lang, $GEDCOMID, $gm_username, $show_changes, $Users;
	
	$name = array();
	if ((!isset($show_changes) || (isset($show_changes) && $show_changes !="no")) && $Users->UserCanEditOwn($gm_username, $pid)) $onlyold = false;
	else $onlyold = true;

	if(!isset($pid) || empty($pid)) return $name;
	$newindi = false;
	// First see if the indi exists or is new
	$indirec = FindGedcomRecord($pid);
	$fromchange = false;
	if (empty($indirec) && !$onlyold) {
		$newindi = true;
		// And see if it's a new indi
		if (GetChangeData(true, $pid, true, "", "INDI,FAMC")) {
			$rec = GetChangeData(false, $pid, true, "gedlines", "INDI,FAMC");
			$indirec = $rec[$GEDCOM][$pid];
			$fromchange = true;
		}
	}
	// Check if the indi is flagged for delete
	$deleted = false;
	if (!$onlyold && GetChangeData(true, $pid, true, "", "INDI")) {
		$new = GetChangeData(false, $pid, true, "gedlines", "INDI");
		if (empty($new[$GEDCOM][$pid])) $deleted = true;
	}

	if (empty($indirec)) return false;
	$result = "aa";
	$num = 1;
	while($result != "") {
		$result = GetSubrecord(1, "1 NAME", $indirec, $num);
		if (!empty($result)) {
			if ($deleted) $resultnew = "";
			else $resultnew = $result;
			if ($fromchange) $name[] = array("old"=>"", "new"=>$resultnew);
			else $name[] = array("old"=>$result, "new"=>$resultnew);
		}
		$num++;
	}
	if ($deleted) return $name;
	
	// we have the original names, now we get all additions and changes TODO: DELETE
	if (!$onlyold && GetChangeData(true, $pid, true)) {
		$sql = "SELECT ch_type, ch_fact, ch_old, ch_new FROM ".$TBLPREFIX."changes WHERE ch_gid='".$pid."' AND ch_fact='NAME' AND ch_gedfile='".$GEDCOMID."' ORDER BY ch_id";
		$res = NewQuery($sql);
//		if (!$res) return false;
	
		// Loop through the changes and apply them to the name records
		while ($row = $res->FetchAssoc($res->result)) {
			if ($row["ch_type"] == "add_name") {
				$name[] = array("old"=>"", "new"=>$row["ch_new"]);
			}
			if ($row["ch_type"] == "edit_name") {
				foreach($name as $key => $namerecs) {
					if (trim($namerecs["new"]) == trim($row["ch_old"])) {
						$name[$key]["new"] = $row["ch_new"];
					}
				}
			}
			if ($row["ch_type"] == "delete_name") {
				foreach($name as $key => $namerecs) {
					if (trim($namerecs["new"]) == trim($row["ch_old"])) {
						$name[$key]["new"] = $row["ch_new"];
					}
				}
			}
		}
	}
	return $name;
}

function GetCachedEvents($action, $daysprint, $filter, $onlyBDM="no", $skipfacts) {
	global $gm_lang, $month, $year, $day, $monthtonum, $monthstart;
	global $GEDCOM, $DEBUG, $ASC, $IGNORE_FACTS, $IGNORE_YEAR;
	global $USE_RTL_FUNCTIONS, $DAYS_TO_SHOW_LIMIT;
	global $CIRCULAR_BASE, $TBLPREFIX;
	
	$found_facts = array();
	// Add 1 to day to start from tomorrow
	if ($action == "upcoming") $monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day+1,$year);
	else $monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day,$year);
	$mstart = date("n", $monthstart);

	// Look for cached Facts data
	$cache_load = false;
	$cache_refresh = false;
	// Retrieve the last change date
	$sql = "SELECT gc_gedcom, gc_last_".$action." FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$GEDCOM."'";
	$res = NewQuery($sql);
	if ($res) {
		while ($row = $res->FetchAssoc()) {
			$mday = $row["gc_last_".$action];
		}
	}
// $mday = 0;  // to force cache rebuild
	if ($mday==$monthstart) {
		$cache_load = true;
//		print "Retrieve from cache";
	}
	else {
		$sql = "DELETE FROM ".$TBLPREFIX."eventcache WHERE ge_cache='".$action."' AND ge_gedcom='".$GEDCOM."'";
		$res = NewQuery($sql);
	}
	
// Search database for raw Indi data if no cache was found
	if (!$cache_load) {
//		print "Rebuild cache";
		// Substract 1 to make # of days correct: including start date
		$dstart = date("j", $monthstart);
		if ($action == "upcoming") {
			$monthend = $monthstart + (60*60*24*($DAYS_TO_SHOW_LIMIT-1));
			$dend = date("j", $monthstart+(60*60*24*($DAYS_TO_SHOW_LIMIT-1)));
			$mend = date("n", $monthstart+(60*60*24*($DAYS_TO_SHOW_LIMIT-1)));
		}
		else {
			$monthend = $monthstart;
			$dend = $dstart;
			$mend = date("n", $monthstart);
		}
		$indilist = array();
		$indilist = SearchIndisDateRange($dstart, $mstart, "", $dend, $mend, "", $filter, "no", $skipfacts);
		// Search database for raw Family data if no cache was found
		$famlist = array();
		$famlist = SearchFamsDateRange($dstart, $mstart, "", $dend, $mend, "", "no", $skipfacts);

		// Apply filter criteria and perform other transformations on the raw data
		foreach($indilist as $gid=>$indi) {
			$facts = GetAllSubrecords($indi["gedcom"], $skipfacts, false, false, false);
			foreach($facts as $key=>$factrec) {
				$date = 0; //--- MA @@@
				$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
				if ($hct>0) {
					if ($USE_RTL_FUNCTIONS) {
						$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
						if ($dct>0) {
							$hebrew_date = ParseDate(trim($match[1]));
							$date = JewishGedcomDateToCurrentGregorian($hebrew_date);
						}
					}
				}
				else {
					$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
					if ($dct>0) $date = ParseDate(trim($match[1]));
				}
				if (!empty($date[0]["mon"]) && !empty($date[0]["day"])) {
					if ($date[0]["mon"]< $mstart) $y = $year+1;
					else $y = $year;
					$datestamp = mktime(1,0,0,$date[0]["mon"],$date[0]["day"],$y);
					if (($datestamp >= $monthstart) && ($datestamp<=$monthend)) {
						// Strip useless information:
						//   NOTE, ADDR, OBJE, SOUR, PAGE, DATA, TEXT
						$factrec = preg_replace("/\d\s+(NOTE|ADDR|OBJE|SOUR|PAGE|DATA|TEXT|CONT|CONC|QUAY|CAUS|CEME)\s+(.+)\n/", "", $factrec);
						if (preg_match("/1 SEX M/", $indi["gedcom"])>0) $gender = "M";
						else if (preg_match("/1 SEX F/", $indi["gedcom"])>0) $gender = "F";
						else $gender = "";
						$fct = preg_match("/1\s(\w+)/", $factrec, $fact);
						$found_facts[] = array($gid, $factrec, "INDI", $datestamp, CheckNN(GetSortableName($gid)), $gender, $fact[1], $indi["isdead"]);
					}
				}
			}
		}
		foreach($famlist as $gid=>$fam) {
			$facts = GetAllSubrecords($fam["gedcom"], $skipfacts, false, false, false);
			foreach($facts as $key=>$factrec) {
				$date = 0; //--- MA @@@
				$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
				if ($hct>0) {
					if ($USE_RTL_FUNCTIONS) {
						$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
						$hebrew_date = ParseDate(trim($match[1]));
						$date = JewishGedcomDateToCurrentGregorian($hebrew_date);
					}
				}
				else {
					$ct = preg_match("/2 DATE (.+)/", $factrec, $match);
					if ($ct>0) $date = ParseDate(trim($match[1]));
				}
				if (!empty($date[0]["mon"]) && !empty($date[0]["day"])) {
					if ($date[0]["mon"]< $mstart) $y = $year+1;
					else $y = $year;
					$datestamp = mktime(1,0,0,$date[0]["mon"],$date[0]["day"],$y);
					if (($datestamp >= $monthstart) && ($datestamp<=$monthend)) {
						// Strip useless information:
						//   NOTE, ADDR, OBJE, SOUR, PAGE, DATA, TEXT
						$factrec = preg_replace("/\d\s+(NOTE|ADDR|OBJE|SOUR|PAGE|DATA|TEXT|CONT|CONC|QUAY|CAUS|CEME)\s+(.+)\n/", "", $factrec);
						if (IsDeadId($fam["HUSB"]) && IsDeadId($fam["WIFE"])) $isdead = "1";
						else $isdead = 0;
						$fct = preg_match("/1\s(\w+)/", $factrec, $fact);
						$found_facts[] = array($gid, $factrec, "FAM", $datestamp, "", "", $fact[1], $isdead);
					}
				}
			}
		}
		// Sort the data
		$CIRCULAR_BASE = $mstart;
		$ASC = 0;
		$IGNORE_FACTS = 1;
		$IGNORE_YEAR = 1;
		uasort($found_facts, "CompareFacts");
//		SortFacts($found_facts); No sortfacts here!
//		reset($found_facts);
		foreach ($found_facts as $key => $factr) {
			$sql = "INSERT INTO ".$TBLPREFIX."eventcache VALUES('0','".mysql_real_escape_string($GEDCOM)."', '".$action."', '".$factr[0]."', '".$factr[7]."', '".$factr[6]."', '".mysql_real_escape_string($factr[1])."', '".$factr[2]."', '".$factr[3]."', '".mysql_real_escape_string($factr[4])."', '".$factr[5]."')";
			$res = NewQuery($sql);
			$error = mysql_error();
			if (!empty($error)) print $error."<br />";
		}
		
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_".$action."='".$monthstart."' WHERE gc_gedcom='".$GEDCOM."'";
		$res = NewQuery($sql);
	}
	
	// load the cache from DB

	$monthend = $monthstart + (60*60*24*($daysprint-1));
	$found_facts = array();
	$sql = "SELECT ge_gid, ge_factrec, ge_type, ge_datestamp, ge_name, ge_gender FROM ".$TBLPREFIX."eventcache WHERE ge_cache='".$action."' AND ge_gedcom='".$GEDCOM."' AND ge_datestamp BETWEEN ".$monthstart." AND ".$monthend;
	if ($onlyBDM == "yes") $sql .= " AND ge_fact IN ('BIRT', 'DEAT', 'MARR')";
	if ($filter == "alive") $sql .= " AND ge_isdead=0";
	$sql .= " ORDER BY ge_order";
	$res = NewQuery($sql);
	if($res) {
		while ($row = $res->FetchRow()) {
			$found_facts[] = $row;
		}
	}
	return $found_facts;
}

function LastBlockCacheDate() {
	global $TBLPREFIX, $GEDCOM;
	
	$sql = "SELECT gc_last_upcoming, gc_last_today, gc_last_stats FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$GEDCOM."'";
	$res = NewQuery($sql);
	if ($res) {
		$row = $res->fetchAssoc();
		return $row;
	}
	else return false;
}

function ResetCaches($file="") {
	global $TBLPREFIX, $GEDCOM;
	
	// Reset todays events cache
	$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_today='0', gc_last_upcoming='0', gc_last_stats='0', gc_last_plotdata='0' ";
	if (!empty($file)) $sql .= "WHERE gc_gedcom='".$file."'";
	$res = NewQuery($sql);
}

function GetCachedStatistics() {
	global $GEDCOM, $TBLPREFIX, $gm_lang, $GEDCOMS, $monthtonum, $GEDCOMID, $DBCONN;
	
	// First see if the cache must be refreshed
	$sql = "SELECT gc_last_stats FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$GEDCOM."'";
	$res = NewQuery($sql);
	if ($res->NumRows() == 0) $cache_load = false;
	else {
		$row = $res->FetchRow();
		if ($row[0] !='0') {
			$cache_load = true;
		}
		else {
			$sql = "DELETE FROM ".$TBLPREFIX."statscache WHERE gs_gedcom='".$GEDCOM."'";
			$res = NewQuery($sql);
			$cache_load = false;
		}
	}
	$stats = array();
	// The title must be generated every time because the language may differ
	$stats["gs_title"] = "";
	$head = FindGedcomRecord("HEAD");
	$ct=preg_match("/1 SOUR (.*)/", $head, $match);
	
	if ($ct>0) {
		$softrec = GetSubRecord(1, "1 SOUR", $head);
		$tt= preg_match("/2 NAME (.*)/", $softrec, $tmatch);
		if ($tt>0) $title = trim($tmatch[1]);
		else $title = trim($match[1]);
		if (!empty($title)) {
				$text = str_replace("#SOFTWARE#", $title, $gm_lang["gedcom_created_using"]);
				$tt = preg_match("/2 VERS (.*)/", $softrec, $tmatch);
				if ($tt>0) $version = trim($tmatch[1]);
				else $version="";
				$text = str_replace("#VERSION#", $version, $text);
				$stats["gs_title"] .= $text;
		}
	}
	$ct=preg_match("/1 DATE (.*)/", $head, $match);
	if ($ct>0) {
		$date = trim($match[1]);
		$ct2 = preg_match("/2 TIME (.*)/", $head, $match);
		if ($ct2 > 0) $time = trim($match[1]);
		else $time = "";	
		if (empty($title)) {
			$text = str_replace("#DATE#", GetChangedDate($date), $gm_lang["gedcom_created_on"]);
			$text = str_replace("#TIME#", $time, $text);
		}
		else {
			$text = str_replace("#DATE#", GetChangedDate($date), $gm_lang["gedcom_created_on2"]);
			$text = str_replace("#TIME#", $time, $text);
		}
		$stats["gs_title"] .= " ".$text;
	}

	if (!$cache_load) {

		//-- total unique surnames
		$sql = "SELECT count(surnames) FROM (";
		$sql .= "SELECT distinct n_surname as surnames FROM ".$TBLPREFIX."names WHERE n_file='".$GEDCOMID."'";
		$sql .= ") as sn GROUP BY surnames";
		$res = NewQuery($sql);
		$stats["gs_nr_surnames"] = $res->NumRows();
		$res->FreeResult();

		$stats["gs_nr_fams"] = GetListSize("famlist");
		$stats["gs_nr_sources"] = GetListSize("sourcelist");
		$stats["gs_nr_other"] = GetListSize("otherlist");
		$stats["gs_nr_media"] = GetListSize("medialist");

		//-- total events
		$sql = "SELECT COUNT(d_gid) FROM ".$TBLPREFIX."dates WHERE d_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		$row = $res->FetchRow();
		$stats["gs_nr_events"] = $row[0];
		$res->FreeResult();

		// NOTE: Get earliest birth year
		$sql = "SELECT d_gid, d_year, d_month, FIELD(d_month";
		foreach($monthtonum as $month=>$mon) $sql .= ", '".$month."'";
		$sql .= ") as d_mon, d_day FROM ".$TBLPREFIX."dates WHERE d_file = '".$GEDCOMID."' AND d_fact = 'BIRT' AND d_year != '0' AND d_type IS NULL ORDER BY d_year ASC, d_mon ASC, d_day ASC LIMIT 1";
		// print $sql;
		$res = NewQuery($sql);
		$row = $res->FetchRow();
		$res->FreeResult();
		$stats["gs_earliest_birth_year"] = $row[1];
		$stats["gs_earliest_birth_gid"] = $row[0];
		
		// NOTE: Get the latest birth year
		$sql = "select d_gid, d_year, d_month, FIELD(d_month";
		foreach($monthtonum as $month=>$mon) $sql .= ", '".$month."'";
		$sql .= ") as d_mon, d_day from ".$TBLPREFIX."dates where d_file = '".$GEDCOMID."' and d_fact = 'BIRT' and d_year != '0' and d_type is null ORDER BY d_year DESC, d_mon DESC , d_day DESC LIMIT 1";
		$res = NewQuery($sql);
		$row = $res->FetchRow();
		$res->FreeResult();
		$stats["gs_latest_birth_year"] = $row[1];
		$stats["gs_latest_birth_gid"] = $row[0];

		// NOTE: Get the person who lived the longest
		$sql = "SELECT death.d_year-birth.d_year AS age, death.d_gid FROM ".$TBLPREFIX."dates AS death, ".$TBLPREFIX."dates AS birth WHERE birth.d_gid=death.d_gid AND death.d_file='".$GEDCOMID."' and birth.d_file=death.d_file AND birth.d_fact='BIRT' AND death.d_fact='DEAT' AND birth.d_year>0 AND death.d_year>0 AND birth.d_type IS NULL AND death.d_type IS NULL ORDER BY age DESC limit 1";
		$res = NewQuery($sql);
		$row = $res->FetchRow();
		$res->FreeResult();
		$stats["gs_longest_live_years"] = $row[0];
		$stats["gs_longest_live_gid"] = $row[1];
		
		//-- avg age at death
		$sql = "SELECT AVG(death.d_year-birth.d_year) AS age FROM ".$TBLPREFIX."dates AS death, ".$TBLPREFIX."dates AS birth WHERE birth.d_gid=death.d_gid AND death.d_file='".$GEDCOMID."' AND birth.d_file=death.d_file AND birth.d_fact='BIRT' AND death.d_fact='DEAT' AND birth.d_year>0 AND death.d_year>0 AND birth.d_type IS NULL AND death.d_type IS NULL";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->FetchRow();
			$stats["gs_avg_age"] = floor($row[0]);
		}
		else $stats["gs_avg_age"] = "";
					
		//-- most children
		$sql = "SELECT f_numchil, f_id FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMID."' ORDER BY f_numchil DESC LIMIT 10";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->FetchRow();
			$res->FreeResult();
			$stats["gs_most_children_nr"] = $row[0];
			$stats["gs_most_children_gid"] = $row[1];
		}
		else {
			$stats["gs_most_children_nr"] = "";
			$stats["gs_most_children_gid"] = "";
		}

		//-- avg number of children
		$sql = "SELECT AVG(f_numchil) FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMID."'";
		$res = NewQuery($sql, false);
		if ($res) {
			$row = $res->FetchRow();
			$res->FreeResult();
			$stats["gs_avg_children"] = $row[0];
		}
		else $stats["gs_avg_children"] = "";
		
		$sql = "INSERT INTO ".$TBLPREFIX."statscache ";
		$sqlf = "(gs_gedcom";
		$sqlv = "('".$GEDCOM."'";
		foreach($stats as $skey => $svalue) {
			if ($skey != "gs_title") {
				$sqlf .= ", ".$skey;
				$sqlv .= ", '".$svalue."'";
			}
		}
		$sqlf .= ")";
		$sqlv .= ")";
		$sql .= $sqlf." VALUES ".$sqlv;
		$res = NewQuery($sql);
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_stats='".time()."' WHERE gc_gedcom='".$GEDCOM."'";
		$res = NewQuery($sql);
	}
	$sql = "SELECT * FROM ".$TBLPREFIX."statscache WHERE gs_gedcom='".$GEDCOM."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc($res->result)){
		foreach ($row as $key => $value) {
			$stats[$key] = $value;
		}
	}
	return $stats;
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
	global $TBLPREFIX, $TOTAL_COUNT, $GEDCOM, $GEDCOMS, $GEDCOMID, $indilist;
	
	$uindilist = array();
	
	$sql = "SELECT i_id, i_gedcom, i_file, i_isdead, n_name, n_letter, n_surname, n_type FROM ".$TBLPREFIX."individuals LEFT JOIN ".$TBLPREFIX."names ON i_key=n_key LEFT JOIN ".$TBLPREFIX."individual_family ON i_key=if_pkey WHERE if_pkey IS NULL AND i_file='".$GEDCOMID."'";

	$res = NewQuery($sql);
	if ($res) {
		$TOTAL_COUNT++;
		while($row = $res->FetchAssoc()){
			$row = db_cleanup($row);
			if (!isset($indilist[$row["i_id"]])) {
				$indi = array();
				$indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$indi["isdead"] = $row["i_isdead"];
				$indi["gedcom"] = $row["i_gedcom"];
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
	global $TBLPREFIX;
	
	$flist = array();
	$sql = "SELECT i_gedcom, i_id FROM ".$TBLPREFIX."individuals WHERE i_file = '".$GEDCOMID."' AND i_gedcom LIKE '%1 SEX F%' AND i_gedcom LIKE '%1 FAMS%'";
	$res = NewQuery($sql);
	$ct = $res->NumRows($res->result);
	while($row = $res->FetchAssoc($res->result)){
		$fem = array();
		$fem["gedcom"] = $row["i_gedcom"];
		$row = db_cleanup($row);
		$flist[$row["i_id"]] = $fem;
	}
	$res->FreeResult();
	return $flist;
}

//-- get the famlist from the datastore
function GetFamListWithMARR() {
	global $GEDCOMID, $DBCONN;
	global $TBLPREFIX;

	$famlist = array();
	$sql = "SELECT f_id, f_gedcom FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMID."' AND f_gedcom LIKE '%1 MARR%'";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	if ($ct > 0) {
		while($row = $res->FetchAssoc()){
			$famlist[$row["f_id"]]["gedcom"] = $row["f_gedcom"];
		}
	}
	$res->FreeResult();
	return $famlist;
}

function HasOtherChanges($pid, $change_id, $gedid="") {
	global $TBLPREFIX, $GEDCOMID;
	
	if (empty($gedid)) $gedid = $GEDCOMID;
	if (GetChangeData(true, $pid, true)) {
		$sql = "SELECT count(ch_id) FROM ".$TBLPREFIX."changes WHERE ch_gedfile='".$gedid."' AND ch_gid='".$pid."' AND ch_cid<>'".$change_id."'";
		$res = NewQuery($sql);
		$row = $res->FetchRow();
		return $row[0];
	}
	else return false;
}

function ShowSourceFromAnyGed() {
	global $TBLPREFIX, $gm_username;
	global $PRIV_PUBLIC, $PRIV_USER, $PRIV_NONE, $PRIV_HIDE, $SHOW_SOURCES, $Users;
		
	$acclevel = $Users->getUserAccessLevel($gm_username);
	$sql = "SELECT p_show_sources FROM ".$TBLPREFIX."privacy";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()) {
		if ($$row["0"] >= $acclevel) {
			$res->FreeResult();
			return true;
		}
	}
	// also check the current setting, as it may not be in the database
	if ($SHOW_SOURCES >= $acclevel) return true;
	return false;
}

/* This function returns the state of a given language variable
** -1 = undetermined
**  0 = existing and translated
**  1 = existing and not translated
**  2 = not existing
*/
function GetLangvarStatus($var, $language, $type="help") {
	global $TBLPREFIX;

	if ($type == "help") {
		$sql = "SELECT lg_english, lg_".$language." FROM ".$TBLPREFIX."language_help WHERE lg_string='".$var."'";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return 2;
		$lang = $res->FetchRow();
		if (empty($lang[1])) return 1;
		else return 0;
	}
	return -1;
}

function GetLangVarString($var, $value, $type) {
	global $TBLPREFIX;

	// This gets the langvar in the gedcom's language
	if ($type == "gedcom" || $type = "gedcomid") {
		if ($type = "gedcomid") $value = get_gedcom_from_id($value);
		$sql = "SELECT gc_gedcomlang FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$value."'";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return false;
		$lang = $res->FetchRow();
		$language = $lang[0];
		$type = "lang";
	}
	else $language = $value;
	// This gets the langvar in the parameter language
	if ($type == "lang") {
		$sql = "SELECT lg_english, lg_".$language." FROM ".$TBLPREFIX."language WHERE lg_string='".$var."'";
		$res = NewQuery($sql);
		$lang = $res->FetchRow();
		if (!empty($lang[1])) return $lang[1];
		else return $lang[0];
	}
}
	
function AddSourceLink($sour, $gid, $gedrec, $gedid, $type) {
	global $TBLPREFIX, $GEDCOMID, $DBCONN;
	
	$sql = "INSERT INTO ".$TBLPREFIX."source_mapping (sm_id, sm_sid, sm_type, sm_gid, sm_gedfile, sm_gedrec) VALUES ('0', '".$sour."', '".$type."', '".$gid."', '".$GEDCOMID."', '".$DBCONN->EscapeQuery($gedrec)."')";
	$res = NewQuery($sql);
	if ($res) {
		$res->FreeResult();
		return true;
	}
	else return false;
}

function AddOtherLink($note, $gid, $type, $gedid) {
	global $TBLPREFIX, $GEDCOMID, $DBCONN;
	
	$sql = "INSERT INTO ".$TBLPREFIX."other_mapping (om_id, om_oid, om_gid, om_type, om_gedfile) VALUES ('0', '".$note."', '".$gid."', '".$type."', '".$GEDCOMID."')";
	$res = NewQuery($sql);
	if ($res) {
		$res->FreeResult();
		return true;
	}
	else return false;
}

function GetNoteLinks($oid, $type="", $applypriv=true) {
	global $TBLPREFIX, $GEDCOMID;

	if (empty($oid)) return false;
	
	$links = array();
	$sql = 	"SELECT om_gid, om_type FROM ".$TBLPREFIX."other_mapping WHERE om_oid='".$oid."' AND om_gedfile='".$GEDCOMID."'";
	if (!empty($type)) $sql .= " AND om_type='".$type."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		if (!$applypriv || ShowFact("NOTE", $row["om_gid"], $row["om_type"])) {
			if (empty($type)) $links[] = array($row["om_gid"], $row["om_type"]);
			else $links[] = $row["om_gid"];
		}
	}
	return $links;
}
	
function GetRepoLinks($rid, $type="", $applypriv=true) {
	global $TBLPREFIX, $GEDCOMID;

	if (empty($rid)) return false;
	
	$links = array();
	$sql = 	"SELECT om_gid, om_type FROM ".$TBLPREFIX."other_mapping WHERE om_oid='".$rid."' AND om_gedfile='".$GEDCOMID."'";
	if (!empty($type)) $sql .= " AND om_type='".$type."'";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		if (!$applypriv || ShowFact("REPO", $row["om_gid"], $row["om_type"])) {
			if (empty($type)) $links[] = array($row["om_gid"], $row["om_type"]);
			else $links[] = $row["om_gid"];
		}
	}
	return $links;
}	

function GetSourceLinks($pid, $type="", $applypriv=true, $getfamindi=true) {
	global $TBLPREFIX, $GEDCOMID;
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
	$sql = "SELECT sm_gid, sm_type FROM ".$TBLPREFIX."source_mapping WHERE sm_sid='".$pid."' AND sm_gedfile='".$GEDCOMID."'";
	if (!empty($type)) $sql .= " AND sm_type='".$type."'";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()){
		$added = false;
		if (!$applypriv) {
			$links[0][] = $row[0];
			$added = true;
		}
		else {
			if (ShowFact("SOUR", $row[0], $type)) {
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
	global $TBLPREFIX, $GEDCOMID, $alllinks, $LINK_PRIVACY;
	
	if (!isset($alllinks)) $alllinks = array();
	if ($LINK_PRIVACY) {
		$famsel = array();
		$indisel = array();	
	}
	$sql = "SELECT sm_sid, sm_gid, sm_type FROM ".$TBLPREFIX."source_mapping WHERE sm_gedfile='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()){
		$alllinks[$row[0]][0][] = $row[1];
		if (ShowFact("SOUR", $row[0], $row[2])) {
			$alllinks[$row[0]][1][] = $row[1];
		}
		if ($LINK_PRIVACY) {
			if ($row[2] == "INDI") $indisel[] = $row[1];
			else if ($row[2] == "FAM") $famsel[] = $row[1];
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

function GetLastChangeDate($type, $pid, $gedid, $head=false) {
	global $TBLPREFIX, $DBCONN;
	
	if ($type == "INDI") $gedrec = FindPersonRecord($pid, get_gedcom_from_id($gedid), true, true);
	if ($type == "FAM")	$gedrec = FindFamilyRecord($pid, get_gedcom_from_id($gedid), true, true);
	if ($type == "SOUR") $gedrec = FindSourceRecord($pid, get_gedcom_from_id($gedid), true, true);
	if ($type == "OBJE") $gedrec = FindMediaRecord($pid, get_gedcom_from_id($gedid), true, true);
	if ($type == "REPO") $gedrec = FindRepoRecord($pid, get_gedcom_from_id($gedid), true, true);
	if ($type == "NOTE") $gedrec = FindOtherRecord($pid, get_gedcom_from_id($gedid), true, true);
	if (empty($gedrec)) return false;
		
	$factrec = GetSubRecord(1, "1 CHAN", $gedrec, 1);
	if (empty($factrec)) {
		if (!$head) return false;
		$sql = "SELECT o_gedcom, o_file FROM ".$TBLPREFIX."other WHERE o_type LIKE 'HEAD' AND o_file='".$DBCONN->EscapeQuery($gedid)."'";
		$res = NewQuery($sql);
		if ($res->NumRows()!=0) {
			$row = $res->fetchRow();
			$res->FreeResult();
			$gedrec = $row[0];
		}
		else return false;
		$factrec = GetSubRecord(1, "1 DATE", $gedrec, 1);
		if (empty($factrec)) return false;
		$date = GetGedcomValue("DATE", 1, $factrec, "", false);
		if (empty($date)) return false;
		$time = GetGedcomValue("TIME", 2, $factrec, "", false);
		//WriteToLog("Retrieved date and time from HEAD record", "I", "S");
	}
	else {
		$date = GetGedcomValue("DATE", 2, $factrec, "", false);
		if (empty($date)) return false;
		$time = GetGedcomValue("TIME", 3, $factrec, "", false);
	}

	return strtotime($date." ".$time);
}

function GetRecentChangeFacts($day, $month, $year, $days) {
	global $monthtonum, $Users, $gm_username, $SHOW_SOURCES, $TOTAL_QUERIES;
	
	$dayindilist = array();
	$dayfamlist = array();
	$daysourcelist = array();
	$dayrepolist = array();
	$daymedialist = array();
	$found_facts = array();

	$monthstart = mktime(1,0,0,$monthtonum[strtolower($month)],$day,$year);
	
	$mmon = strtolower(date("M", $monthstart));
	$mmon2 = strtolower(date("M", $monthstart-(60*60*24*$days)));
	$mday2 = date("d", $monthstart-(60*60*24*$days));
	$myear2 = date("Y", $monthstart-(60*60*24*$days));

	$fromdate = $myear2.date("m", $monthstart-(60*60*24*$days)).$mday2;
	if ($day < 10)
		$mday3 = "0".$day;
	else $mday3 = $day;
	
	$todate = $year.date("m", $monthstart).$mday3;
	
	$dayindilist = SearchIndisDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", "no", "", false, "CHAN");
	$dayfamlist = SearchFamsDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "no", "", false, "CHAN");
	if ($SHOW_SOURCES >= $Users->getUserAccessLevel($gm_username)) $dayrepolist = SearchOtherDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");
	if ($SHOW_SOURCES >= $Users->getUserAccessLevel($gm_username)) $daysourcelist = SearchSourcesDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");
	$daymedialist = SearchMediaDateRange($mday2, $monthtonum[$mmon2], $myear2, $mday3, $monthtonum[$mmon], $year, "", false, "CHAN");

	if (count($dayindilist)>0 || count($dayfamlist)>0 || count($daysourcelist)>0 || count($dayrepolist) > 0 || count($daymedialist) > 0) {
		$found_facts = array();
		$last_total = $TOTAL_QUERIES;
		foreach($dayindilist as $gid=>$indi) {
			$disp = displayDetailsByID($gid);
			if ($disp) {
				$factrec = GetSubRecord(1, "1 CHAN", $indi["gedcom"], 1);
				$found_facts[] = array($gid, $factrec, "INDI");
			}
		}
		foreach($dayfamlist as $gid=>$fam) {
			$disp = displayDetailsByID($gid, "FAM");
			if ($disp) {
				$factrec = GetSubRecord(1, "1 CHAN", $fam["gedcom"], 1);
				$found_facts[] = array($gid, $factrec, "FAM");
			}
		}
		foreach($daysourcelist as $gid=>$source) {
			$disp = displayDetailsByID($gid, "SOUR", 1, true);
			if ($disp) {
				$factrec = GetSubRecord(1, "1 CHAN", $source["gedcom"], 1);
				$found_facts[] = array($gid, $factrec, "SOUR");
			}
		}
		foreach($dayrepolist as $rid=>$repo) {
			if ($repo["type"] == "REPO") {
				$disp = displayDetailsByID($rid, "REPO");
				if ($disp) {
					$factrec = GetSubRecord(1, "1 CHAN", $repo["gedcom"], 1);
					$found_facts[] = array($rid, $factrec, "REPO");
				}
			}
		}
		foreach($daymedialist as $mid=>$media) {
			$disp = displayDetailsByID($mid, "OBJE", 1, true);
			if ($disp) {
				$factrec = GetSubRecord(1, "1 CHAN", $media["gedcom"], 1);
				$found_facts[] = array($mid, $factrec, "OBJE");
			}
		}
	}
	return $found_facts;
}
?>