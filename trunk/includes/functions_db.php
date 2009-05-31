<?php
/**
 * PEAR:DB specific functions file
 *
 * This file implements the datastore functions necessary for Genmod to use an SQL database as its
 * datastore. This file also implements array caches for the database tables.  Whenever data is
 * retrieved from the database it is stored in a cache.  When a database access is requested the
 * cache arrays are checked first before querying the database.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2002 to 2005  GM Development Team
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
 * @version $Id: functions_db.php,v 1.108 2006/05/07 11:35:56 roland-d Exp $
 * @package Genmod
 * @subpackage DB
 */
if (strstr($_SERVER["SCRIPT_NAME"],"functions")) {
	print "Now, why would you want to do that.	You're not hacking are you?";
	exit;
}

//-- load the PEAR:DB files
if (!class_exists("DB")) require_once('includes/DB.php');

//-- set the REGEXP status of databases
$REGEXP_DB = true;

//-- uncomment the following line to turn on sql query logging
//$SQL_LOG = true;

/**
 * query the database
 *
 * this function will perform the given SQL query on the database
 * @param string $sql		the sql query to execture
 * @param boolean $show_error	whether or not to show any error messages
 * @return Object the connection result
 */
function dbquery($sql, $show_error=true) {
	global $DBCONN, $TOTAL_QUERIES, $INDEX_DIRECTORY, $SQL_LOG, $LAST_QUERY, $CONFIGURED;

	if (!$CONFIGURED) return false;
	if (!isset($DBCONN)) {
		//print "No Connection";
		return false;
	}
	//-- make sure a database connection has been established
	if (DB::isError($DBCONN)) {
		print $DBCONN->getCode()." ".$DBCONN->getMessage();
		return $DBCONN;
	}
	$res = $DBCONN->query($sql);
	$LAST_QUERY = $sql;
//	print $sql."<br />";
//	var_dump(debug_backtrace());
//	print "<br /><br />";
	$TOTAL_QUERIES++;
	if (!empty($SQL_LOG)) {
		$fp = fopen($INDEX_DIRECTORY."/sql_log.txt", "a");
		fwrite($fp, date("Y-m-d h:i:s")."\t".$_SERVER["SCRIPT_NAME"]."\t".$sql."\r\n");
		fclose($fp);
	}
	if (DB::isError($res)) {
		if ($show_error) print "<span class=\"error\"><b>ERROR:".$res->getCode()." ".$res->getMessage()." <br />SQL:</b>".$res->getUserInfo()."</span><br /><br />\n";
	}
	return $res;
}

/**
 * query the database and return the first row
 *
 * this function will perform the given SQL query on the database and return the first row in the result set
 * @param string $sql		the sql query to execture
 * @param boolean $show_error	whether or not to show any error messages
 * @return array the found row
 */
function dbgetrow($sql, $show_error=true) {
	global $DBCONN, $TOTAL_QUERIES, $INDEX_DIRECTORY, $SQL_LOG;
	//-- make sure a database connection has been established
	if (DB::isError($DBCONN)) {
		return false;
	}

	$row = $DBCONN->getRow($sql);
	$TOTAL_QUERIES++;
	if (!empty($SQL_LOG)) {
		$fp = fopen($INDEX_DIRECTORY."/sql_log.txt", "a");
		fwrite($fp, date("Y-m-d h:m:s")."\t".$_SERVER["SCRIPT_NAME"]."\t$sql\n");
		fclose($fp);
	}
	if (DB::isError($row)) {
		if ($show_error) print "<span class=\"error\"><b>ERROR:".$row->getMessage()." <br />SQL:</b>$sql</span><br /><br />\n";
	}
	return $row;
}

/**
 * prepare an item to be updated in the database
 *
 * add slashes and convert special chars so that it can be added to db
 * @param mixed $item		an array or string to be prepared for the database
 */
function db_prep($item) {
	global $DBCONN;

	if (is_array($item)) {
		foreach($item as $key=>$value) {
			$item[$key]=db_prep($value);
		}
		return $item;
	}
	else {
		if (DB::isError($DBCONN)) return $item;
		if (is_object($DBCONN)) return $DBCONN->escapeSimple($item);
		//-- use the following commented line to convert between character sets
		//return $DBCONN->escapeSimple(iconv("iso-8859-1", "UTF-8", $item));
	}
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
 * check if a gedcom has been imported into the database
 *
 * this function checks the database to see if the given gedcom has been imported yet.
 * @param string $ged the filename of the gedcom to check for import
 * @return bool return true if the gedcom has been imported otherwise returns false
 */
function check_for_import($ged) {
	global $TBLPREFIX, $BUILDING_INDEX, $DBCONN, $GEDCOMS;

	$sql = "SELECT count(i_id) FROM ".$TBLPREFIX."individuals WHERE i_file='".$DBCONN->escapeSimple($GEDCOMS[$ged]["id"])."'";
	$res = dbquery($sql, false);
	if (!DB::isError($res)) {
		$row = $res->fetchRow();
		$res->free();
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
function find_family_record($famid, $gedfile="") {
	global $TBLPREFIX;
	global $GEDCOMS, $GEDCOM, $famlist, $DBCONN;

	if (empty($famid)) return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	
	if (isset($famlist[$famid]["gedcom"])&&($famlist[$famid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $famlist[$famid]["gedcom"];

	$sql = "SELECT f_gedcom, f_file, f_husb, f_wife FROM ".$TBLPREFIX."families WHERE f_id LIKE '".$DBCONN->escapeSimple($famid)."' AND f_file='".$DBCONN->escapeSimple($GEDCOMS[$gedfile]["id"])."'";
	$res = dbquery($sql);
	$row = $res->fetchRow();

	$famlist[$famid]["gedcom"] = $row[0];
	$famlist[$famid]["gedfile"] = $row[1];
	$famlist[$famid]["husb"] = $row[2];
	$famlist[$famid]["wife"] = $row[3];
	find_person_record($row[2]);
	find_person_record($row[3]);
	$res->free();
	return $row[0];
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
function find_person_record($pid, $gedfile="") {
	global $gm_lang;
	global $TBLPREFIX;
	global $GEDCOM, $GEDCOMS;
	global $BUILDING_INDEX, $indilist, $DBCONN;

	if (empty($pid)) return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;

	if (is_int($GEDCOM)) $GEDCOM = get_gedcom_from_id($GEDCOM);
	//-- first check the indilist cache
	// cache is unreliable for use with different gedcoms in user favorites (sjouke)
	if ((isset($indilist[$pid]["gedcom"]))&&($indilist[$pid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $indilist[$pid]["gedcom"];

	$sql = "SELECT i_gedcom, i_name, i_isdead, i_file, i_letter, i_surname FROM ".$TBLPREFIX."individuals WHERE i_id LIKE '".$DBCONN->escapeSimple($pid)."' AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$gedfile]["id"])."'";
	$res = dbquery($sql);
	if (!DB::isError($res)) {
		if ($res->numRows()==0) {
			return false;
		}
		$row = $res->fetchRow();
		$indilist[$pid]["gedcom"] = $row[0];
		$indilist[$pid]["names"] = get_indi_names($row[0]);
		$indilist[$pid]["isdead"] = $row[2];
		$indilist[$pid]["gedfile"] = $row[3];
		$res->free();
		return $row[0];
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
function find_gedcom_record($pid, $gedfile = "") {
	global $gm_lang, $TBLPREFIX, $GEDCOMS, $MEDIA_ID_PREFIX;
	global $GEDCOM, $indilist, $famlist, $sourcelist, $otherlist, $DBCONN;

	if (empty($pid)) return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	if ((isset($indilist[$pid]["gedcom"]))&&($indilist[$pid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $indilist[$pid]["gedcom"];
	if ((isset($famlist[$pid]["gedcom"]))&&($famlist[$pid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $famlist[$pid]["gedcom"];
	if ((isset($sourcelist[$pid]["gedcom"]))&&($sourcelist[$pid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $sourcelist[$pid]["gedcom"];
	if ((isset($repolist[$pid]["gedcom"])) && ($repolist[$pid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $repolist[$pid]["gedcom"];
	if ((isset($otherlist[$pid]["gedcom"]))&&($otherlist[$pid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $otherlist[$pid]["gedcom"];
	if ((isset($medialist[$pid]["gedcom"]))&&($medialist[$pid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $medialist[$pid]["gedcom"];
	
	$sql = "SELECT o_gedcom, o_file FROM ".$TBLPREFIX."other WHERE o_id LIKE '".$DBCONN->escapeSimple($pid)."' AND o_file='".$DBCONN->escapeSimple($GEDCOMS[$gedfile]["id"])."'";
	$res = dbquery($sql);
	if ($res->numRows()!=0) {
		$row = $res->fetchRow();
		$res->free();
		$otherlist[$pid]["gedcom"] = $row[0];
		$otherlist[$pid]["gedfile"] = $row[1];
		return $row[0];
	}
	$gedrec = find_person_record($pid, $gedfile);
	if (empty($gedrec)) {
		$gedrec = find_family_record($pid, $gedfile);
		if (empty($gedrec)) {
			$gedrec = find_source_record($pid, $gedfile);
			if (empty($gedrec)) {
				$gedrec = find_media_record($pid, $gedfile);
				if (empty($gedrec)) return false;
			}
		}
	}
	return $gedrec;
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
function find_source_record($sid, $gedfile="") {
	global $gm_lang;
	global $TBLPREFIX, $GEDCOMS;
	global $GEDCOM, $sourcelist, $DBCONN;

	if ($sid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	
	if (isset($sourcelist[$sid]["gedcom"]) && ($sourcelist[$sid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $sourcelist[$sid]["gedcom"];

	$sql = "SELECT s_gedcom, s_name, s_file FROM ".$TBLPREFIX."sources WHERE s_id LIKE '".$DBCONN->escapeSimple($sid)."' AND s_file='".$DBCONN->escapeSimple($GEDCOMS[$gedfile]["id"])."'";
	$res = dbquery($sql);
	if ($res->numRows()!=0) {
		$row = $res->fetchRow();
		$sourcelist[$sid]["name"] = stripslashes($row[1]);
		$sourcelist[$sid]["gedcom"] = $row[0];
		$sourcelist[$sid]["gedfile"] = $row[2];
		$res->free();
		return $row[0];
	}
	else {
		return false;
	}
}


/**
 * Find a repository record by its ID
 * @param string $rid	the record id
 * @param string $gedfile	the gedcom file id
 */
function find_repo_record($rid, $gedfile="") {
	global $TBLPREFIX, $GEDCOMS;
	global $GEDCOM, $repolist, $DBCONN;

	if ($rid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	
	if (isset($repolist[$rid]["gedcom"]) && ($repolist[$rid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $repolist[$rid]["gedcom"];

	$sql = "SELECT o_id, o_gedcom, o_file FROM ".$TBLPREFIX."other WHERE o_type='REPO' AND o_id LIKE '".$DBCONN->escapeSimple($rid)."' AND o_file='".$DBCONN->escapeSimple($GEDCOMS[$gedfile]["id"])."'";
	$res = dbquery($sql);
	if ($res->numRows()!=0) {
		$row = $res->fetchRow();
		$tt = preg_match("/1 NAME (.*)/", $row[1], $match);
		if ($tt == "0") $name = $row[0]; else $name = $match[1];
		$repolist[$rid]["name"] = stripslashes($name);
		$repolist[$rid]["gedcom"] = $row[1];
		$repolist[$rid]["gedfile"] = $row[2];
		$res->free();
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
function find_media_record($rid, $gedfile='') {
	global $TBLPREFIX, $GEDCOMS;
	global $GEDCOM, $medialist, $DBCONN;

	if ($rid=="") return false;
	if (empty($gedfile)) $gedfile = $GEDCOM;
	
	//-- first check for the record in the cache
	if (isset($medialist[$rid]["gedcom"]) && ($medialist[$rid]["gedfile"]==$GEDCOMS[$gedfile]["id"])) return $medialist[$rid]["gedcom"];

	$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE m_media LIKE '".$DBCONN->escapeSimple($rid)."' AND m_gedfile='".$DBCONN->escapeSimple($GEDCOMS[$gedfile]["id"])."'";
	$res = dbquery($sql);
	if ($res->numRows()!=0) {
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		$medialist[$rid]["ext"] = $row["m_ext"];
		$medialist[$rid]["title"] = $row["m_titl"];
		$medialist[$rid]["file"] = check_media_depth($row["m_file"]);
		$medialist[$rid]["gedcom"] = $row["m_gedrec"];
		$medialist[$rid]["gedfile"] = $row["m_gedfile"];
		$res->free();
		return $row["m_gedrec"];
	}
	else {
		return false;
	}
}

/**
 * find and return the id of the first person in the gedcom
 * @return string the gedcom xref id of the first person in the gedcom
 */
function find_first_person() {
	global $GEDCOM, $TBLPREFIX, $GEDCOMS, $DBCONN;
	$sql = "SELECT i_id FROM ".$TBLPREFIX."individuals WHERE i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ORDER BY i_id LIMIT 1";
	$row = dbgetrow($sql);
	return $row[0];
}

//=================== IMPORT FUNCTIONS ======================================

/**
 * import record into database
 *
 * this function will parse the given gedcom record and add it to the database
 * @param string $indirec the raw gedcom record to parse
 * @param boolean $update whether or not this is an updated record that has been accepted
 */
function import_record($indirec, $update=false) {
	global $DBCONN, $gid, $type, $indilist,$famlist,$sourcelist,$otherlist, $TOTAL_QUERIES, $prepared_statement;
	global $TBLPREFIX, $GEDCOM_FILE, $FILE, $gm_lang, $USE_RIN, $gdfp, $placecache;
	global $ALPHABET_upper, $ALPHABET_lower, $place_id, $WORD_WRAPPED_NOTES, $GEDCOMS, $media_count;
	
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
	if ($update) {
		update_places($gid, $indirec, $update);
		update_dates($gid, $indirec);
	}
	
	$indirec = update_media($gid, $indirec, $update);
	
	if ($type == "INDI") {
		$indirec = cleanup_tags_y($indirec);
		$ct = preg_match_all("/1 FAMS @(.*)@/", $indirec, $match, PREG_SET_ORDER);
		$sfams = "";
		for($j=0; $j<$ct; $j++) {
			$sql = "INSERT INTO ".$TBLPREFIX."individual_spouse VALUES('', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."', '".$DBCONN->escapeSimple($gid)."', '".$match[$j][1]."')";
			$res = dbquery($sql);
			$sfams .= $match[$j][1].";";
		}
		$ct = preg_match_all("/1 FAMC @(.*)@/", $indirec, $match, PREG_SET_ORDER);
		$cfams = "";
		for($j=0; $j<$ct; $j++) {
			$sql = "INSERT INTO ".$TBLPREFIX."individual_child VALUES('', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."', '".$DBCONN->escapeSimple($gid)."', '".$match[$j][1]."')";
			$res = dbquery($sql);
			$cfams .= $match[$j][1].";";
		}
		$isdead = -1;
		$indi = array();
		$names = get_indi_names($indirec, true);
		$soundex_codes = GetSoundexStrings($names);
		$j=0;
		foreach($names as $indexval => $name) {
			if ($j>0) {
				// if ($update) $sql = "UPDATE ".$TBLPREFIX."names SET n_name = '".$DBCONN->escapeSimple($name[0])."', n_letter = '".$DBCONN->escapeSimple($name[1])."', n_surname = '".$DBCONN->escapeSimple($name[2])."', n_type = '".$DBCONN->escapeSimple($name[3])."' WHERE n_gid = '".$DBCONN->escapeSimple($gid)."' AND n_file = '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."'";
				$sql = "INSERT INTO ".$TBLPREFIX."names VALUES('".$DBCONN->escapeSimple($gid)."','".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."','".$DBCONN->escapeSimple($name[0])."','".$DBCONN->escapeSimple($name[1])."','".$DBCONN->escapeSimple($name[2])."','".$DBCONN->escapeSimple($name[3])."')";
				$res = dbquery($sql);
			}
			$j++;
		}
		$indi["names"] = $names;
		$indi["isdead"] = $isdead;
		$indi["gedcom"] = $indirec;
		$indi["gedfile"] = $GEDCOMS[$FILE]["id"];
		$indi["sex"] = get_gedcom_value("SEX", 1, $indirec, '', false);
		if ($USE_RIN) {
			$ct = preg_match("/1 RIN (.*)/", $indirec, $match);
			if ($ct>0) $rin = trim($match[1]);
			else $rin = $gid;
			$indi["rin"] = $rin;
		}
		else $indi["rin"] = $gid;
		
		// if ($update) $sql = "UPDATE ".$TBLPREFIX."individuals SET i_rin = '".$DBCONN->escapeSimple($indi["rin"])."', i_name = '".$DBCONN->escapeSimple($names[0][0])."', i_isdead = '-1', i_gedcom = '".$DBCONN->escapeSimple($indi["gedcom"])."', i_letter = '".$DBCONN->escapeSimple($names[0][1])."', i_surname = '".$DBCONN->escapeSimple($names[0][2])."' WHERE i_id = '".$DBCONN->escapeSimple($gid)."' AND i_file = '".$DBCONN->escapeSimple($indi["gedfile"])."'";
		$sql = "INSERT INTO ".$TBLPREFIX."individuals VALUES ('".$DBCONN->escapeSimple($gid)."','".$DBCONN->escapeSimple($indi["gedfile"])."','".$DBCONN->escapeSimple($indi["rin"])."','".$DBCONN->escapeSimple($names[0][0])."',-1,'".$DBCONN->escapeSimple($indi["gedcom"])."','".$DBCONN->escapeSimple($names[0][1])."','".$DBCONN->escapeSimple($names[0][2])."','".$soundex_codes["sn_russel"]."','".$soundex_codes["sn_dm"]."','".$soundex_codes["fn_russel"]."','".$soundex_codes["fn_dm"]."','".$indi["sex"]."')";
		$res = dbquery($sql);
		if (DB::isError($res)) {
		   // die(__LINE__." ".__FILE__."  ".$res->getMessage());
		}
	}
	else if ($type == "FAM") {
		$indirec = cleanup_tags_y($indirec);
		$parents = array();
		$ct = preg_match("/1 HUSB @(.*)@/", $indirec, $match);
		if ($ct>0) $parents["HUSB"]=$match[1];
		else $parents["HUSB"]=false;
		$ct = preg_match("/1 WIFE @(.*)@/", $indirec, $match);
		if ($ct>0) $parents["WIFE"]=$match[1];
		else $parents["WIFE"]=false;
		$ct = preg_match_all("/\d CHIL @(.*)@/", $indirec, $match, PREG_SET_ORDER);
		$chil = "";
		for($j=0; $j<$ct; $j++) {
			$chil .= $match[$j][1].";";
		}
		$fam = array();
		$fam["HUSB"] = $parents["HUSB"];
		$fam["WIFE"] = $parents["WIFE"];
		$fam["CHIL"] = $chil;
		$fam["gedcom"] = $indirec;
		$fam["gedfile"] = $GEDCOMS[$FILE]["id"];
		// if ($update) $sql = "UPDATE ".$TBLPREFIX."families SET f_husb = '".$DBCONN->escapeSimple($fam["HUSB"])."', f_wife = '".$DBCONN->escapeSimple($fam["WIFE"])."', f_chil = '".$DBCONN->escapeSimple($fam["CHIL"])."', f_gedcom = '".$DBCONN->escapeSimple($fam["gedcom"])."', f_numchil = '".$DBCONN->escapeSimple($ct)."' WHERE f_id = '".$DBCONN->escapeSimple($gid)."' AND f_file = '".$DBCONN->escapeSimple($fam["gedfile"])."'";
		$sql = "INSERT INTO ".$TBLPREFIX."families (f_id, f_file, f_husb, f_wife, f_chil, f_gedcom, f_numchil) VALUES ('".$DBCONN->escapeSimple($gid)."','".$DBCONN->escapeSimple($fam["gedfile"])."','".$DBCONN->escapeSimple($fam["HUSB"])."','".$DBCONN->escapeSimple($fam["WIFE"])."','".$DBCONN->escapeSimple($fam["CHIL"])."','".$DBCONN->escapeSimple($fam["gedcom"])."','".$DBCONN->escapeSimple($ct)."')";
		$res = dbquery($sql);
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
		// if ($update) $sql = "UPDATE ".$TBLPREFIX."sources SET s_name = '".$DBCONN->escapeSimple($name)."', s_gedcom = '".$DBCONN->escapeSimple($indirec)."' WHERE s_id = '".$DBCONN->escapeSimple($gid)."' AND s_file = '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."'";
		$sql = "INSERT INTO ".$TBLPREFIX."sources VALUES ('".$DBCONN->escapeSimple($gid)."','".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."','".$DBCONN->escapeSimple($name)."','".$DBCONN->escapeSimple($indirec)."')";
		$res = dbquery($sql);
	}
	else if ($type=="OBJE") {
		//-- don't duplicate OBJE records
		//-- OBJE records are imported by update_media function
	}
	else if (preg_match("/_/", $type)==0) {
		if ($type=="HEAD") {
			$ct=preg_match("/1 DATE (.*)/", $indirec, $match);
			if ($ct == 0) {
				$indirec = trim($indirec);
				$indirec .= "\r\n1 DATE ".date("d")." ".date("M")." ".date("Y");
			}
		}
		// if ($update) $sql = "UPDATE ".$TBLPREFIX."other SET o_type = '".$DBCONN->escapeSimple($type)."', o_gedcom = '".$DBCONN->escapeSimple($indirec)."' WHERE o_id = '".$DBCONN->escapeSimple($gid)."' AND o_file = '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."'";
		$sql = "INSERT INTO ".$TBLPREFIX."other VALUES ('".$DBCONN->escapeSimple($gid)."','".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."','".$DBCONN->escapeSimple($type)."','".$DBCONN->escapeSimple($indirec)."')";
		$res = dbquery($sql);
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
function update_isdead($gid, $indi) {
	global $TBLPREFIX, $USE_RIN, $indilist, $DBCONN;
	$isdead = 0;
	$isdead = is_dead($indi["gedcom"]);
	if (empty($isdead)) $isdead = 0;
	$sql = "UPDATE ".$TBLPREFIX."individuals SET i_isdead=$isdead WHERE i_id LIKE '".$DBCONN->escapeSimple($gid)."' AND i_file='".$DBCONN->escapeSimple($indi["gedfile"])."'";
	$res = dbquery($sql);
	if (isset($indilist[$gid])) $indilist[$gid]["isdead"] = $isdead;
	return $isdead;
}

/**
 * reset the i_isdead column
 * 
 * This function will reset the i_isdead column with the default -1 so that all is dead status
 * items will be recalculated.
 */
function reset_isdead() {
	global $TBLPREFIX, $GEDCOMS, $GEDCOM, $DBCONN;
	
	$sql = "UPDATE ".$TBLPREFIX."individuals SET i_isdead=-1 WHERE i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	dbquery($sql);
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
function add_new_name($gid, $newname, $letter, $surname, $indirec) {
	global $TBLPREFIX, $USE_RIN, $indilist, $FILE, $DBCONN, $GEDCOMS;

	$indilist[$gid]["names"][] = array($newname, $letter, $surname, 'C');
	$indilist[$gid]["gedcom"] = $indirec;
	$sql = "INSERT INTO ".$TBLPREFIX."names VALUES('".$DBCONN->escapeSimple($gid)."','".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."','".$DBCONN->escapeSimple($newname)."','".$DBCONN->escapeSimple($letter)."','".$DBCONN->escapeSimple($surname)."','C')";
	$res = dbquery($sql);
	$soundex_codes = GetSoundexStrings($indilist[$gid]["names"]);
	$sql = "UPDATE ".$TBLPREFIX."individuals SET i_gedcom='".$DBCONN->escapeSimple($indirec)."',i_snsoundex='".$soundex_codes["sn_russel"]."',i_sndmsoundex='".$soundex_codes["sn_dm"]."',i_fnsoundex='".$soundex_codes["fn_russel"]."',i_fndmsoundex='".$soundex_codes["fn_dm"]."' WHERE i_id='".$DBCONN->escapeSimple($gid)."' AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."'";
	$res = dbquery($sql);
}

/**
 * extract all places from the given record and insert them
 * into the places table
 * @param string $indirec
 */
function update_places($gid, $indirec, $update=false) {
	global $FILE, $placecache, $TBLPREFIX, $DBCONN, $GEDCOMS;

	if (!isset($placecache)) $placecache = array();
	//-- import all place locations
	$pt = preg_match_all("/\d PLAC (.*)/", $indirec, $match, PREG_SET_ORDER);
	for($i=0; $i<$pt; $i++) {
		$place = trim($match[$i][1]);
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
					$sql = "INSERT INTO ".$TBLPREFIX."placelinks VALUES($parent_id, '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."')";
					$res = dbquery($sql);
				}
			}
			else {
				$skip = false;
				if ($update) {
					$sql = "SELECT p_id FROM ".$TBLPREFIX."places WHERE p_place LIKE '".$DBCONN->escapeSimple($place)."' AND p_level=$level AND p_parent_id='$parent_id' AND p_file='".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."'";
					$res = dbquery($sql);
					if ($res->numRows()>0) {
						$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
						$res->free();
						$parent_id = $row["p_id"];
						$skip=true;
						$placecache[$key] = array($parent_id, $gid.",");
						$sql = "INSERT INTO ".$TBLPREFIX."placelinks VALUES($parent_id, '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."')";
						$res = dbquery($sql);
					}
				}
				if (!$skip) {
					if (!isset($place_id)) {
						$place_id = get_next_id("places", "p_id");
					}
					else $place_id++;
					$sql = "INSERT INTO ".$TBLPREFIX."places VALUES($place_id, '".$DBCONN->escapeSimple($place)."', $level, '$parent_id', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."')";
					$res = dbquery($sql);
					$parent_id = $place_id;
					$placecache[$key] = array($parent_id, $gid.",");
					$sql = "INSERT INTO ".$TBLPREFIX."placelinks VALUES($place_id, '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."')";
					$res = dbquery($sql);
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
function update_dates($gid, $indirec) {
	global $FILE, $TBLPREFIX, $DBCONN, $GEDCOMS;
	$count = 0;
	// NOTE: Check if the record has dates, if not return
	$pt = preg_match("/\d DATE (.*)/", $indirec, $match);
	if ($pt==0) return 0;
	
	// NOTE: Get all facts
	preg_match_all("/(\d)\s(\w+)\r\n/", $indirec, $facts, PREG_SET_ORDER);
	
	// NOTE: Get all the level 1 records
	foreach($facts as $key => $subfact) {
		$fact = $subfact[2];
		$subrec = get_sub_record($subfact[1], $fact, $indirec);
		$count_dates = preg_match("/\d DATE (.*)/", $subrec, $dates);
		if ($count_dates > 0) {
			$datestr = trim($dates[1]);
			$date = parse_date($datestr);
			if (empty($date[0]["day"])) $date[0]["day"] = 0;
			$sql = "INSERT INTO ".$TBLPREFIX."dates VALUES('".$DBCONN->escapeSimple($date[0]["day"])."','".$DBCONN->escapeSimple(str2upper($date[0]["month"]))."','".$DBCONN->escapeSimple($date[0]["year"])."','".$DBCONN->escapeSimple($fact)."','".$DBCONN->escapeSimple($gid)."','".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."',";
			if (isset($date[0]["ext"])) {
				preg_match("/@#D(.*)@/", $date[0]["ext"], $extract_type);
				$date_types = array("@#DGREGORIAN@","@#DJULIAN@","@#DHEBREW@","@#DFRENCH R@", "@#DROMAN@", "@#DUNKNOWN@");
				if (isset($extract_type[0]) && in_array($extract_type[0], $date_types)) $sql .= "'".$extract_type[0]."')";
				else $sql .= "NULL)";
			}
			else $sql .= "NULL)";
			$res = dbquery($sql);
			$count++;
		}
	}
	return $count;
}

/**
 * import media items from record
 * @return string	an updated record
 */
function update_media($gid, $indirec, $update=false) {
	global $GEDCOMS, $FILE, $TBLPREFIX, $DBCONN, $MEDIA_ID_PREFIX, $media_count, $found_ids;
	global $zero_level_media;
	
	if (!isset($media_count)) $media_count = 0;
	if (!isset($found_ids)) $found_ids = array();
	if (!isset($zero_level_media)) $zero_level_media = false;
	
	//-- handle level 0 media OBJE seperately
	$ct = preg_match("/0 @(.*)@ OBJE/", $indirec, $match);
	if ($ct>0) {
		$old_m_media = $match[1];
		$found = false;
		if ($update) {
			$new_m_media = $old_m_media;
		}
		else {
			if (array_key_exists($match[1], $found_ids)) {
				$new_m_media = $found_ids[$match[1]]["new_id"];
				$found = true;
			}
			else {
				$new_m_media = get_new_xref("OBJE");
				$found_ids[$match[1]]["old_id"] = $match[1];
				$found_ids[$match[1]]["new_id"] = $new_m_media;
			}
		}
		$indirec = preg_replace("/@".$old_m_media."@/", "@".$new_m_media."@", $indirec);
		$title = get_gedcom_value("TITL", 2, $indirec);
		if (strlen(trim($title)) == 0) $title = get_gedcom_value("TITL", 1, $indirec);
		$file = get_gedcom_value("FILE", 1, $indirec);
		$et = preg_match("/(\.\w+)$/", $file, $ematch);
		$ext = "";
		if ($et>0) $ext = substr(trim($ematch[1]),1);
		if ($found) {
			$sql = "UPDATE ".$TBLPREFIX."media SET m_ext = '".$DBCONN->escapeSimple($ext)."', m_titl = '".$DBCONN->escapeSimple($title)."', m_file = '".$DBCONN->escapeSimple($file)."', m_gedrec = '".$DBCONN->escapeSimple($indirec)."' WHERE m_media = '".$new_m_media."'";
			$res = dbquery($sql);
		}
		else {
			$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
			$sql .= " VALUES('0', '".$DBCONN->escapeSimple($new_m_media)."', '".$DBCONN->escapeSimple($ext)."', '".$DBCONN->escapeSimple($title)."', '".$DBCONN->escapeSimple($file)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."', '".$DBCONN->escapeSimple($indirec)."')";
			$res = dbquery($sql);
		}
		$found = false;
		return $indirec;
	}
	
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
			if (preg_match("/[1-9]\sOBJE\s(.*)$/", $line, $match) != 0) {
				// NOTE: Check if objlevel greater is than 0, if so then store the current object record
				if ($objlevel > 0) {
					$title = get_gedcom_value("TITL", $objlevel+1, $objrec);
					if (strlen(trim($title)) == 0) $title = get_gedcom_value("TITL", $objlevel+2, $objrec);
					$file = get_gedcom_value("FILE", $objlevel+1, $objrec);
					$m_media = get_new_xref("OBJE");
					$et = preg_match("/(\.\w+)$/", $file, $ematch);
					$ext = "";
					if ($et>0) $ext = substr(trim($ematch[1]),1);
					// NOTE: Make sure 1 OBJE @M1@ is treated correctly
					if (preg_match("/\d+\s\w+\s@(.*)@/", $objrec) > 0) $objrec = preg_replace("/@(.*)@/", "@".$m_media."@", $objrec);
					else $objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
					$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
					$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
					$sql .= " VALUES('0', '".$DBCONN->escapeSimple($m_media)."', '".$DBCONN->escapeSimple($ext)."', '".$DBCONN->escapeSimple($title)."', '".$DBCONN->escapeSimple($file)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."', '".$DBCONN->escapeSimple($objrec)."')";
					$res = dbquery($sql);
					$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec)";
					$sql .= " VALUES ('0', '".$DBCONN->escapeSimple($m_media)."', '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($count)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]['id'])."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@')."')";
					$res = dbquery($sql);
					$media_count++;
					$count++;
					// NOTE: Add the new media object to the record
					$newrec .= $objlevel." OBJE @".$m_media."@\r\n";
					
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
							$new_mm_media = get_new_xref("OBJE");
						}
						// NOTE: Put both IDs in the found_ids array in case we later find the 0-level
						// NOTE: The 0-level ID will have to be changed also
						$found_ids[$old_mm_media]["old_id"] = $old_mm_media;
						$found_ids[$old_mm_media]["new_id"] = $new_mm_media;
						
						if (!$update) {
							// NOTE: We found a media reference but no media item yet, we need to create an empty
							// NOTE: media object, so we do not have orhpaned media mapping links
							$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
							$sql .= " VALUES('0', '".$DBCONN->escapeSimple($new_mm_media)."', '', '', '', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."', '0 @".$DBCONN->escapeSimple($new_mm_media)."@ OBJE\r\n')";
							$res = dbquery($sql);
							
							// NOTE: Add the mapping to the media reference
							$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
							$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec) ";
							$sql .= "VALUES ('0', '".$DBCONN->escapeSimple($new_mm_media)."', '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($count)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]['id'])."', '".$line."')";
							$res = dbquery($sql);
						}
						else {
							// NOTE: This is an online update. Let's see if we already have a media mapping for this item
							$sql = "SELECT mm_media FROM ".$TBLPREFIX."media_mapping WHERE mm_media = '".$new_mm_media."' AND mm_gedfile = '".$GEDCOMS[$FILE]["id"]."'";
							$res = dbquery($sql);
							$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
							if (count($row) == 0) {
								// NOTE: Add the mapping to the media reference
								$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
								$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec) ";
								$sql .= "VALUES ('0', '".$DBCONN->escapeSimple($new_mm_media)."', '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($count)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]['id'])."', '".$line."')";
								$res = dbquery($sql);
							}
						}
					}
					else if (array_key_exists($old_mm_media, $found_ids) && !empty($match[1])) {
						if (!$update) {
							$new_mm_media = $found_ids[$old_mm_media]["new_id"];
							$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
							$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec) ";
							$sql .= "VALUES ('0', '".$DBCONN->escapeSimple($new_mm_media)."', '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($count)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]['id'])."', '".$line."')";
							$res = dbquery($sql);
						}
						else {
							// NOTE: This is an online update. Let's see if we already have a media mapping for this item
							$sql = "SELECT mm_media FROM ".$TBLPREFIX."media_mapping WHERE mm_media = '".$new_mm_media."' AND mm_gedfile = '".$GEDCOMS[$FILE]["id"]."'";
							$res = dbquery($sql);
							$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
							if (count($row) == 0) {
								// NOTE: Add the mapping to the media reference
								$line = preg_replace("/@(.*)@/", "@$new_mm_media@", $line);
								$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec) ";
								$sql .= "VALUES ('0', '".$DBCONN->escapeSimple($new_mm_media)."', '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($count)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]['id'])."', '".$line."')";
								$res = dbquery($sql);
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
			else if (preg_match("/[1-9]\sOBJE$/", $line, $match)) {
				if (!empty($objrec)) {
					$title = get_gedcom_value("TITL", $objlevel+1, $objrec);
					if (strlen(trim($title)) == 0) $title = get_gedcom_value("TITL", $objlevel+2, $objrec);
					$file = get_gedcom_value("FILE", $objlevel+1, $objrec);
					$m_media = get_new_xref("OBJE");
					$et = preg_match("/(\.\w+)$/", $file, $ematch);
					$ext = "";
					if ($et>0) $ext = substr(trim($ematch[1]),1);
					$objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
					$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
					$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
					$sql .= " VALUES('0', '".$DBCONN->escapeSimple($m_media)."', '".$DBCONN->escapeSimple($ext)."', '".$DBCONN->escapeSimple($title)."', '".$DBCONN->escapeSimple($file)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."', '".$DBCONN->escapeSimple($objrec)."')";
					$res = dbquery($sql);
					$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec)";
					$sql .= " VALUES ('0', '".$DBCONN->escapeSimple($m_media)."', '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($count)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]['id'])."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@')."')";
					$res = dbquery($sql);
					$media_count++;
					$count++;
					// NOTE: Add the new media object to the record
					$newrec .= $objlevel." OBJE @".$m_media."@\r\n";
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
						$title = get_gedcom_value("TITL", $objlevel+1, $objrec);
						if (strlen(trim($title)) == 0) $title = get_gedcom_value("TITL", $objlevel+2, $objrec);
						$file = get_gedcom_value("FILE", $objlevel+1, $objrec);
						$et = preg_match("/(\.\w+)$/", $file, $ematch);
						$ext = "";
						if ($et>0) $ext = substr(trim($ematch[1]),1);
						if ($objrec{0} != 0) {
							$m_media = get_new_xref("OBJE");
							if (preg_match("/^\d+\s\w+\s@(.*)@/", $objrec) > 0) {
								$objrec = preg_replace("/@(.*)@/", "@".$m_media."@", $objrec);
							}
							else $objrec = preg_replace("/ OBJE/", " @".$m_media."@ OBJE", $objrec);
							$objrec = preg_replace("/^(\d+) /me", "($1-$objlevel).' '", $objrec);
							$sql = "INSERT INTO ".$TBLPREFIX."media (m_id, m_media, m_ext, m_titl, m_file, m_gedfile, m_gedrec)";
							$sql .= " VALUES('0', '".$DBCONN->escapeSimple($m_media)."', '".$DBCONN->escapeSimple($ext)."', '".$DBCONN->escapeSimple($title)."', '".$DBCONN->escapeSimple($file)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]["id"])."', '".$DBCONN->escapeSimple($objrec)."')";
							$res = dbquery($sql);
							$sql = "INSERT INTO ".$TBLPREFIX."media_mapping (mm_id, mm_media, mm_gid, mm_order, mm_gedfile, mm_gedrec)";
							$sql .= " VALUES ('0', '".$DBCONN->escapeSimple($m_media)."', '".$DBCONN->escapeSimple($gid)."', '".$DBCONN->escapeSimple($count)."', '".$DBCONN->escapeSimple($GEDCOMS[$FILE]['id'])."', '".addslashes(''.$objlevel.' OBJE @'.$m_media.'@')."')";
							$res = dbquery($sql);
						}
						else {
							$oldid = preg_match("/0\s@(.*)@\sOBJE/", $objrec, $newmatch);
							$m_media = $newmatch[1];
							$sql = "UPDATE ".$TBLPREFIX."media SET m_ext = '".$DBCONN->escapeSimple($ext)."', m_titl = '".$DBCONN->escapeSimple($title)."', m_file = '".$DBCONN->escapeSimple($file)."', m_gedrec = '".$DBCONN->escapeSimple($objrec)."' WHERE m_media = '".$m_media."'";
							$res = dbquery($sql);
						}
						$media_count++;
						$count++;
						$objrec = "";
						if ($key == $ct_lines-1 && $level>$objlevel) $line = $objlevel." OBJE @".$m_media."@\r\n";
						else $line = $objlevel." OBJE @".$m_media."@\r\n".$line;
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
			if (!$inobj && !empty($line)) $newrec .= $line."\r\n";
		}
	}
	return $newrec;
}

/**
 * Create database schema
 *
 * function that checks if the database exists and creates tables
 * automatically handles version updates
 */
function setup_database() {
	global $TBLPREFIX, $gm_lang, $DBCONN, $gm_username;
	
	//---------- Check if tables exist
	$tables_exist=false;
	$has_rin = false;
	$has_places = false;
	$has_place_gid = false;
	$has_first_letter = false;
	$has_surname = false;
	$has_names = false;
	$has_placelinks = false;
	$has_research = false;
	$has_dates = false;
	$has_numchil = false;
	$has_media = false;
	$has_media_mapping = false;
	$has_changes = false;
	$has_gedcoms = false;
	$has_counters = false;
	$has_gedconf = false;
	$has_privacy = false;
	$has_language = false;
	$has_language_help = false;
	$has_pages = false;
	$has_eventcache = false;
	$has_statscache = false;
	$has_individual_spouse = false;
	$has_individual_child = false;

	$data = $DBCONN->getListOf('tables');
	foreach($data as $indexval => $table) {
		if ($table==$TBLPREFIX."individuals") {
			$tables_exist=true;
			$info = $DBCONN->tableInfo($TBLPREFIX."individuals");
			foreach($info as $indexval => $field) {
				if ($field["name"]=="i_rin") $has_rin = true;
				if ($field["name"]=="i_letter") $has_first_letter = true;
				if ($field["name"]=="i_surname") $has_surname = true;
			}
		}
		if ($table==$TBLPREFIX."places") {
			$has_places = true;
			$info = $DBCONN->tableInfo($TBLPREFIX."places");
			foreach($info as $indexval => $field) {
				if ($field["name"]=="p_gid") $has_place_gid = true;
			}
		}
		if ($table==$TBLPREFIX."families") {
			$info = $DBCONN->tableInfo($TBLPREFIX."families");
			foreach($info as $indexval => $field) {
				if ($field["name"]=="f_name") {
					$fsql = "ALTER TABLE ".$TBLPREFIX."families DROP COLUMN f_name";
					$fres = dbquery($fsql);
				}
				if ($field["name"]=="f_numchil") {
					$has_numchil = true;
				}
			}
		}
		if ($table==$TBLPREFIX."names") {
			$has_names = true;
		}
		if ($table==$TBLPREFIX."placelinks") {
			$has_placelinks = true;
		}
		if ($table==$TBLPREFIX."dates") {
			$has_dates = true;
		}
		if ($table==$TBLPREFIX."changes") {
			$has_changes = true;
		}
		if ($table==$TBLPREFIX."media") {
			$has_media = true;
		}
		if ($table==$TBLPREFIX."media_mapping") {
			$has_media_mapping = true;
		}
		if ($table==$TBLPREFIX."gedcoms") {
			$has_gedcoms = true;
		}
		if ($table==$TBLPREFIX."counters") {
			$has_counters = true;
		}
		if ($table==$TBLPREFIX."gedconf") {
			$has_gedconf = true;
		}
		if ($table==$TBLPREFIX."privacy") {
			$has_privacy = true;
		}
		if ($table==$TBLPREFIX."language") {
			$has_language = true;
		}
		if ($table==$TBLPREFIX."language_help") {
			$has_language_help = true;
		}
		if ($table==$TBLPREFIX."pages") {
			$has_pages = true;
		}
		if ($table==$TBLPREFIX."eventcache") {
			$has_eventcache = true;
		}
		if ($table==$TBLPREFIX."statscache") {
			$has_statscache = true;
		}
		if ($table==$TBLPREFIX."individual_spouse") {
			$has_individual_spouse = true;
		}
		if ($table==$TBLPREFIX."individual_child") {
			$has_individual_child = true;
		}
	}

	if (($tables_exist)&&(!$has_rin || !$has_first_letter)) {
		$sql = "DROP table if exists ";
		$sql .= $TBLPREFIX."individuals, ";
		$sql .= $TBLPREFIX."families, ";
		$sql .= $TBLPREFIX."sources, ";
		$sql .= $TBLPREFIX."other, ";
		$sql .= $TBLPREFIX."places, ";
		$sql .= $TBLPREFIX."places, ";
		$sql .= $TBLPREFIX."media, ";
		$sql .= $TBLPREFIX."media_mapping, ";
		$sql .= $TBLPREFIX."individual_spouse";
		$sql .= $TBLPREFIX."individual_child";
		$res = dbquery($sql);
		$tables_exist = false;
	}

	if (!$tables_exist) {
		$sql = "CREATE TABLE ".$TBLPREFIX."individuals (
		i_id VARCHAR(255), 
		i_file INT, 
		i_rin VARCHAR(255), 
		i_name VARCHAR(255), 
		i_isdead INT DEFAULT 1, 
		i_gedcom TEXT, 
		i_letter VARCHAR(5), 
		i_surname VARCHAR(100), 
		i_snsoundex VARCHAR(255), 
		i_sndmsoundex VARCHAR(255), 
		i_fnsoundex VARCHAR(255), 
		i_fndmsoundex VARCHAR(255),
		i_gender CHAR(1)
		)";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX indi_id ON ".$TBLPREFIX."individuals (i_id)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX indi_name ON ".$TBLPREFIX."individuals (i_name)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX indi_letter ON ".$TBLPREFIX."individuals (i_letter)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX indi_file ON ".$TBLPREFIX."individuals (i_file)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX indi_surn ON ".$TBLPREFIX."individuals (i_surname)";
			$res = dbquery($sql);
			print $gm_lang["created_indis"]."<br />\n";
			$has_surname=true;
		}
		else {
			print $gm_lang["created_indis_fail"]."<br />\n";
			exit;
		}

		$sql = "CREATE TABLE ".$TBLPREFIX."families (f_id VARCHAR(255), f_file INT, f_husb VARCHAR(255), f_wife VARCHAR(255), f_chil TEXT, f_gedcom TEXT)";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX fam_id ON ".$TBLPREFIX."families (f_id)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX fam_file ON ".$TBLPREFIX."families (f_file)";
			$res = dbquery($sql);
			print $gm_lang["created_fams"]."<br />\n";
		}
		else {
			print $gm_lang["created_fams_fail"]."<br />\n";
			exit;
		}
		$sql = "CREATE TABLE ".$TBLPREFIX."sources (s_id VARCHAR(255), s_file INT, s_name VARCHAR(255), s_gedcom TEXT)";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX sour_id ON ".$TBLPREFIX."sources (s_id)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX sour_name ON ".$TBLPREFIX."sources (s_name)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX sour_file ON ".$TBLPREFIX."sources (s_file)";
			$res = dbquery($sql);
			print $gm_lang["created_sources"]."<br />\n";
		}
		else {
			print $gm_lang["created_sources_fail"]."<br />\n";
			exit;
		}

		$sql = "CREATE TABLE ".$TBLPREFIX."other (o_id VARCHAR(255), o_file INT, o_type VARCHAR(20), o_gedcom TEXT)";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX other_id ON ".$TBLPREFIX."other (o_id)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX other_file ON ".$TBLPREFIX."other (o_file)";
			$res = dbquery($sql);
			print $gm_lang["created_other"]."<br />\n";
		}
		else {
			print $gm_lang["created_other_fail"]."<br />\n";
			exit;
		}

		$sql = "CREATE TABLE ".$TBLPREFIX."places (p_id INT NOT NULL, p_place VARCHAR(150), p_level INT, p_parent_id INT, p_file INT, PRIMARY KEY(p_id))";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX place_place ON ".$TBLPREFIX."places (p_place)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX place_level ON ".$TBLPREFIX."places (p_level)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX place_parent ON ".$TBLPREFIX."places (p_parent_id)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX place_file ON ".$TBLPREFIX."places (p_file)";
			$res = dbquery($sql);
			print $gm_lang["created_places"]."<br />\n";
		}
		else {
			print $gm_lang["created_places_fail"]."<br />\n";
			exit;
		}
		$sql = "CREATE TABLE ".$TBLPREFIX."placelinks (pl_p_id INT, pl_gid VARCHAR(255), pl_file INT)";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX plindex_place ON ".$TBLPREFIX."placelinks (pl_p_id)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX plindex_gid ON ".$TBLPREFIX."placelinks (pl_gid)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX plindex_file ON ".$TBLPREFIX."placelinks (pl_file)";
			$res = dbquery($sql);
			print $gm_lang["created_placelinks"]."<br />\n";
		}
		else {
			print $gm_lang["created_placelinks_fail"]."<br />\n";
			exit;
		}
		$sql = "CREATE TABLE ".$TBLPREFIX."media (m_id int(11) auto_increment, m_media VARCHAR(15), m_ext varchar(6), m_titl varchar(255), m_file varchar(255), m_gedfile int(11), m_gedrec text, PRIMARY KEY (m_id))";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX m_media ON ".$TBLPREFIX."media (m_media)";
			$res = dbquery($sql);
			$has_media = true;
		}
		else {
			print $gm_lang["created_media_fail"]."<br />\n";
			exit;
		}
		$sql = "CREATE TABLE ".$TBLPREFIX."media_mapping (mm_id int(11) auto_increment, mm_media varchar(15) NOT NULL default '', mm_gid varchar(15) NOT NULL default '', mm_order int(11) NOT NULL default '0', mm_gedfile int(11) default NULL, mm_gedrec text, PRIMARY KEY (mm_id), KEY mm_media (mm_media))"; 
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX mm_mediamapping ON ".$TBLPREFIX."media_mapping (mm_media)";
			$res = dbquery($sql);
			$has_media_mapping = true;
		}
		else {
			print $gm_lang["created_media_mapping_fail"]."<br />\n";
			exit;
		}
	}
	else if (!$has_places) {
		$sql = "CREATE TABLE ".$TBLPREFIX."places (p_id INT NOT NULL, p_place VARCHAR(150), p_level INT, p_parent_id INT, p_file INT, PRIMARY KEY(p_id))";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX place_place ON ".$TBLPREFIX."places (p_place)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX place_level ON ".$TBLPREFIX."places (p_level)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX place_parent ON ".$TBLPREFIX."places (p_parent_id)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX place_file ON ".$TBLPREFIX."places (p_file)";
			$res = dbquery($sql);
			print $gm_lang["created_places"]; print "<br />\n";
		}
		else {
			print $gm_lang["created_places_fail"]; print "<br />\n";
			exit;
		}
	}
	else if ($has_place_gid) {
		$sql = "ALTER TABLE ".$TBLPREFIX."places DROP COLUMN p_gid";
		$res = dbquery($sql);
	}
	else if (!$has_placelinks) {
		$sql = "CREATE TABLE ".$TBLPREFIX."placelinks (pl_p_id INT, pl_gid VARCHAR(255), pl_file INT)";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX plindex_place ON ".$TBLPREFIX."placelinks (pl_p_id)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX plindex_gid ON ".$TBLPREFIX."placelinks (pl_gid)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX plindex_file ON ".$TBLPREFIX."placelinks (pl_file)";
			$res = dbquery($sql);
			print $gm_lang["created_places"]; print "<br />\n";
		}
		else {
			print $gm_lang["created_places_fail"]; print "<br />\n";
			exit;
		}
	}
	if (!$has_names) {
		$sql = "CREATE TABLE ".$TBLPREFIX."names (n_gid VARCHAR(255), n_file INT, n_name VARCHAR(255), n_letter VARCHAR(5), n_surname VARCHAR(100), n_type VARCHAR(10))";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX name_gid ON ".$TBLPREFIX."names (n_gid)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX name_name ON ".$TBLPREFIX."names (n_name)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX name_letter ON ".$TBLPREFIX."names (n_letter)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX name_type ON ".$TBLPREFIX."names (n_type)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX name_surn ON ".$TBLPREFIX."names (n_surname)";
			$res = dbquery($sql);
		}
		if (!$has_surname) {
			$sql = "ALTER TABLE ".$TBLPREFIX."individuals ADD COLUMN i_surname VARCHAR(100)";
			$res = dbquery($sql);
		}
	}
	else if (!$has_surname) {
		$sql = "ALTER TABLE ".$TBLPREFIX."individuals ADD COLUMN i_surname VARCHAR(100)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX indi_surn ON ".$TBLPREFIX."individuals (i_surname)";
		$res = dbquery($sql);
		if ($has_names) {
			$sql = "ALTER TABLE ".$TBLPREFIX."names ADD COLUMN n_surname VARCHAR(100), n_type VARCHAR(10)";
			$res = dbquery($sql);
			$sql = "CREATE INDEX name_surn ON ".$TBLPREFIX."names (n_surname)";
			$res = dbquery($sql);
		}
	}
	if (!$has_dates) {
		$sql = "CREATE TABLE ".$TBLPREFIX."dates (d_day INT, d_month VARCHAR(5), d_year INT, d_fact VARCHAR(10), d_gid VARCHAR(255), d_file INT, d_type VARCHAR(13))";
		$res = dbquery($sql);
		$sql = "CREATE INDEX date_day ON ".$TBLPREFIX."dates (d_day)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX date_month ON ".$TBLPREFIX."dates (d_month)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX date_year ON ".$TBLPREFIX."dates (d_year)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX date_fact ON ".$TBLPREFIX."dates (d_fact)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX date_gid ON ".$TBLPREFIX."dates (d_gid)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX date_file ON ".$TBLPREFIX."dates (d_file)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX date_type ON ".$TBLPREFIX."dates (d_type)";
		$res = dbquery($sql);
	}
	if (!$has_numchil) {
		$fsql = "ALTER TABLE ".$TBLPREFIX."families ADD f_numchil INT";
		$fres = dbquery($fsql);
	}
	if (!$has_changes) {
		$sql = "CREATE TABLE ".$TBLPREFIX."changes (
			`ch_id` int(11) NOT NULL auto_increment,
			`ch_cid` int(11) NOT NULL default '0',
			`ch_gid` varchar(255) NOT NULL default '',
			`ch_gedfile` int(11) NOT NULL default '0',
			`ch_type` varchar(25) NOT NULL default '',
			`ch_user` varchar(255) NOT NULL default '',
			`ch_time` int(11) NOT NULL default '0',
			`ch_fact` varchar(15) default NULL,
			`ch_old` text,
			`ch_new` text,
			`ch_delete` tinyint(1) NOT NULL default '0',
			PRIMARY KEY  (`ch_id`),
			KEY `ch_gid` (`ch_gid`)
			)";
		$res = dbquery($sql);
	}
	if (!$has_media) {
		$sql = "CREATE TABLE ".$TBLPREFIX."media (m_id int(11) NOT NULL auto_increment, m_media varchar(15) NOT NULL default '', m_ext varchar(6) NOT NULL default '', m_titl varchar(255) default NULL, m_file varchar(255) NOT NULL default '', m_gedfile int(11) default NULL, m_gedrec text, PRIMARY KEY (m_id), KEY m_media (m_media))";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX m_media_id ON ".$TBLPREFIX."media (m_media)";
			$res = dbquery($sql);
		}
		else {
			print $gm_lang["created_media_fail"]."<br />\n";
			exit;
		}
	}
	if (!$has_media_mapping) {
		$sql = "CREATE TABLE ".$TBLPREFIX."media_mapping (mm_id int(11) auto_increment, mm_media varchar(15) NOT NULL default '', mm_gid varchar(15) NOT NULL default '', mm_order int(11) NOT NULL default '0', mm_gedfile int(11) default NULL, mm_gedrec text, PRIMARY KEY (mm_id), KEY mm_media (mm_media))"; 
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX mm_media_id ON ".$TBLPREFIX."media_mapping (mm_media)";
			$res = dbquery($sql);
		}
		else {
			print $gm_lang["created_media_mapping_fail"]."<br />\n";
			exit;
		}
	}
	if (!$has_gedcoms) {
		$sql = "CREATE TABLE ".$TBLPREFIX."gedcoms (g_gedcom varchar(64) NOT NULL default '', g_config varchar(64), g_privacy varchar(64), g_title varchar(50), g_path varchar(64), g_id int(11) NOT NULL default '0', g_commonsurnames text, g_isdefault varchar(2), PRIMARY KEY (g_gedcom))"; 
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX g_id_id ON ".$TBLPREFIX."gedcoms (g_id)";
			$res = dbquery($sql);
		}
		else {
			print $gm_lang["created_gedcoms_fail"]."<br />\n";
			exit;
		}
	}
	if (!$has_counters) {
		$sql = "CREATE TABLE ".$TBLPREFIX."counters (c_id varchar(120) NOT NULL default '', c_number int(11) NOT NULL default '0', PRIMARY KEY (c_id))"; 
		$res = dbquery($sql);
		if(DB::isError($res)) {
			print $gm_lang["created_counters_fail"]."<br />\n";
			exit;
		}
	}
	if (!$has_gedconf) {
		$sql = "CREATE TABLE ".$TBLPREFIX."gedconf (
		gc_gedcom VARCHAR (64) NOT NULL default '',
		gc_language varchar (64),  
		gc_calendar_format VARCHAR (21), 
		gc_display_jewish_thousands TINYINT(1), 
		gc_display_jewish_gereshayim TINYINT(1), 
		gc_jewish_ashkenaz_pronunciation TINYINT(1), 
		gc_use_rtl_functions TINYINT(1),
		gc_character_set VARCHAR (6),
		gc_enable_multi_language TINYINT(1), 
		gc_default_pedigree_generations INT (3), 
		gc_max_pedigree_generations INT (3), 
		gc_max_descendancy_generations INT (3), 
		gc_use_rin TINYINT(1), 
		gc_pedigree_root_id VARCHAR (10), 
		gc_gedcom_id_prefix VARCHAR (6), 
		gc_source_id_prefix VARCHAR (6), 
		gc_repo_id_prefix VARCHAR (6), 
		gc_fam_id_prefix VARCHAR (6), 
		gc_media_id_prefix VARCHAR (6), 
		gc_pedigree_full_details TINYINT(1), 
		gc_pedigree_layout TINYINT(1), 
		gc_show_empty_boxes TINYINT(1), 
		gc_zoom_boxes VARCHAR (10), 
		gc_link_icons VARCHAR (10), 
		gc_abbreviate_chart_labels TINYINT(1), 
		gc_show_parents_age TINYINT(1), 
		gc_hide_live_people TINYINT(1), 
		gc_require_authentication TINYINT(1), 
		gc_welcome_text_auth_mode INT (2), 
		gc_welcome_text_auth_mode_4 TEXT, 
		gc_welcome_text_cust_head TINYINT(1), 
		gc_check_child_dates  TINYINT(1), 
		gc_show_gedcom_record TINYINT(1), 
		gc_allow_edit_gedcom TINYINT(1), 
		gc_postal_code TINYINT(1), 
		gc_alpha_index_lists TINYINT(1), 
		gc_name_from_gedcom TINYINT(1), 
		gc_show_married_names TINYINT(1), 
		gc_show_id_numbers TINYINT(1), 
		gc_show_fam_id_numbers TINYINT(1), 
		gc_show_pedigree_places TINYINT(1), 
		gc_multi_media TINYINT(1), 
		gc_media_external TINYINT(1), 
		gc_media_directory VARCHAR (64), 
		gc_media_directory_levels INT (2), 
		gc_show_highlight_images TINYINT(1), 
		gc_use_thumbs_main TINYINT(1), 
		gc_thumbnail_width INT (5), 
		gc_auto_generate_thumbs TINYINT(1), 
		gc_hide_gedcom_errors TINYINT(1), 
		gc_word_wrapped_notes TINYINT(1), 
		gc_gedcom_default_tab INT (2), 
		gc_show_context_help TINYINT(1), 
		gc_contact_email VARCHAR (64), 
		gc_contact_method VARCHAR (15), 
		gc_webmaster_email VARCHAR (64), 
		gc_support_method VARCHAR (15), 
		gc_home_site_url VARCHAR (64), 
		gc_home_site_text VARCHAR (64), 
		gc_favicon VARCHAR (64), 
		gc_theme_dir VARCHAR (64), 
		gc_allow_theme_dropdown TINYINT(1), 
		gc_show_stats TINYINT(1), 
		gc_show_counter TINYINT(1), 
		gc_days_to_show_limit INT (4), 
		gc_common_names_threshold INT (4), 
		gc_common_names_add	text, 
		gc_common_names_remove	text, 
		gc_meta_author VARCHAR (64), 
		gc_meta_publisher VARCHAR (64), 
		gc_meta_copyright VARCHAR (64), 
		gc_meta_description	VARCHAR (64), 
		gc_meta_page_topic VARCHAR (64), 
		gc_meta_audience VARCHAR (64),
		gc_meta_page_type VARCHAR (64), 
		gc_meta_robots VARCHAR (64), 
		gc_meta_revisit VARCHAR (64), 
		gc_meta_keywords VARCHAR (64), 
		gc_meta_title  VARCHAR (64), 
		gc_meta_surname_keywords TINYINT(1), 
		gc_chart_box_tags VARCHAR (64), 
		gc_use_quick_update	TINYINT(1), 
		gc_show_quick_resn TINYINT(1), 
		gc_quick_add_facts VARCHAR (255), 
		gc_quick_required_facts VARCHAR (255), 
		gc_quick_add_famfacts VARCHAR (255), 
		gc_quick_required_famfacts VARCHAR (255), 
		gc_show_lds_at_glance TINYINT(1), 
		gc_underline_name_quotes TINYINT(1), 
		gc_split_places TINYINT(1), 
		gc_show_relatives_events VARCHAR (255), 
		gc_expand_relatives_events TINYINT(1), 
		gc_edit_autoclose TINYINT(1), 
		gc_sour_facts_unique varchar(255), 
		gc_sour_facts_add VARCHAR (255), 
		gc_repo_facts_unique VARCHAR (255), 
		gc_repo_facts_add VARCHAR (255), 
		gc_indi_facts_unique VARCHAR (255), 
		gc_indi_facts_add VARCHAR (255), 
		gc_fam_facts_unique VARCHAR (255), 
		gc_fam_facts_add VARCHAR (255), 
		gc_rss_format VARCHAR (10),
		gc_time_limit INT (4),
		gc_last_change_email INT (11) DEFAULT '0',
		gc_last_upcoming INT(11) DEFAULT '0',  
		gc_last_today INT(11) DEFAULT '0',
		gc_last_stats INT(2) DEFAULT '0',
		PRIMARY KEY (gc_gedcom))"; 
		$res = dbquery($sql);
		if(DB::isError($res)) {
			print $gm_lang["created_gedconf_fail"]."<br />\n";
			exit;
		}
	}
	if (!$has_privacy) {
		$sql = "CREATE TABLE ".$TBLPREFIX."privacy (
		p_gedcom VARCHAR (64) NOT NULL default '',
		p_privacy_version varchar (6),  
		p_priv_hide INT (3), 
		p_priv_public INT (3), 
		p_priv_user INT (3), 
		p_priv_none INT (3), 
		p_show_dead_people varchar (12),  
		p_show_living_names varchar (12),  
		p_show_sources varchar (12), 
		p_max_alive_age INT (4),
		p_show_research_log varchar (12), 
		p_enable_clippings_cart varchar (12), 
		p_use_relationship_privacy TINYINT (1), 
		p_max_relation_path_length INT (3), 
		p_check_marriage_relations TINYINT (1), 
		p_privacy_by_year TINYINT (1), 
		p_privacy_by_resn TINYINT (1),
		p_person_privacy TEXT, 
		p_user_privacy TEXT, 
		p_global_facts TEXT, 
		p_person_facts TEXT, 
		PRIMARY KEY (p_gedcom))"; 
		$res = dbquery($sql);
		if(DB::isError($res)) {
			print $gm_lang["created_privacy_fail"]."<br />\n";
			exit;
		}
	}
	if (!$has_language) {
		$sql = "CREATE TABLE ".$TBLPREFIX."language (
			  `lg_string` varchar(255) NOT NULL default '',
			  `lg_english` text NOT NULL,
			  `lg_spanish` text NOT NULL,
			  `lg_german` text NOT NULL,
			  `lg_french` text NOT NULL,
			  `lg_hebrew` text NOT NULL,
			  `lg_arabic` text NOT NULL,
			  `lg_czech` text NOT NULL,
			  `lg_danish` text NOT NULL,
			  `lg_greek` text NOT NULL,
			  `lg_finnish` text NOT NULL,
			  `lg_hungarian` text NOT NULL,
			  `lg_italian` text NOT NULL,
			  `lg_lithuanian` text NOT NULL,
			  `lg_dutch` text NOT NULL,
			  `lg_norwegian` text NOT NULL,
			  `lg_polish` text NOT NULL,
			  `lg_portuguese-br` text NOT NULL,
			  `lg_russian` text NOT NULL,
			  `lg_swedish` text NOT NULL,
			  `lg_turkish` text NOT NULL,
			  `lg_vietnamese` text NOT NULL,
			  `lg_chinese` text NOT NULL,
			  `lg_last_update_date` int(11) NOT NULL default '0',
			  `lg_last_update_by` varchar(255) NOT NULL default '',
			  UNIQUE KEY `lg_string` (`lg_string`)
			) ";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX lang_string ON ".$TBLPREFIX."language (lg_string)";
			$res = dbquery($sql);
			print $gm_lang["created_language"]."<br />\n";
		}
		else {
			print $gm_lang["created_language_fail"]."<br />\n";
			exit;
		}
	}
	if (!$has_language_help) {
		$sql = "CREATE TABLE ".$TBLPREFIX."language_help (
			  `lg_string` varchar(255) NOT NULL default '',
			  `lg_english` text NOT NULL,
			  `lg_spanish` text NOT NULL,
			  `lg_german` text NOT NULL,
			  `lg_french` text NOT NULL,
			  `lg_hebrew` text NOT NULL,
			  `lg_arabic` text NOT NULL,
			  `lg_czech` text NOT NULL,
			  `lg_danish` text NOT NULL,
			  `lg_greek` text NOT NULL,
			  `lg_finnish` text NOT NULL,
			  `lg_hungarian` text NOT NULL,
			  `lg_italian` text NOT NULL,
			  `lg_lithuanian` text NOT NULL,
			  `lg_dutch` text NOT NULL,
			  `lg_norwegian` text NOT NULL,
			  `lg_polish` text NOT NULL,
			  `lg_portuguese-br` text NOT NULL,
			  `lg_russian` text NOT NULL,
			  `lg_swedish` text NOT NULL,
			  `lg_turkish` text NOT NULL,
			  `lg_vietnamese` text NOT NULL,
			  `lg_chinese` text NOT NULL,
			  `lg_last_update_date` int(11) NOT NULL default '0',
			  `lg_last_update_by` varchar(255) NOT NULL default '',
			  UNIQUE KEY `lg_string` (`lg_string`)
			) ;";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			$sql = "CREATE INDEX lang_help_string ON ".$TBLPREFIX."language_help (lg_string)";
			$res = dbquery($sql);
			print $gm_lang["created_language_help"]."<br />\n";
		}
		else {
			print $gm_lang["created_language_help_fail"]."<br />\n";
			exit;
		}
	}
	if (!$has_pages) {
		$sql = "CREATE TABLE `".$TBLPREFIX."pages` (
			`pag_id` int(11) NOT NULL auto_increment,
			`pag_content` longtext NOT NULL,
			`pag_title` varchar(40) NOT NULL default '',
			PRIMARY KEY  (`pag_id`)
			);";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			print $gm_lang["created_pages"]."<br />\n";
		}
		else {
			print $gm_lang["created_pages_fail"]."<br />\n";
			exit;
		}
	}

	if (!$has_eventcache) {
		$sql = "CREATE TABLE ".$TBLPREFIX."eventcache (
			ge_order INT(11) NOT NULL auto_increment,
			ge_gedcom varchar(64) NOT NULL,
			ge_cache varchar(10) NOT NULL,
			ge_gid varchar(10) NOT NULL,
			ge_isdead INT(2) NOT NULL default '0',
			ge_fact varchar(16) NOT NULL,
			ge_factrec text NOT NULL,
			ge_type varchar(5) NOT NULL,
			ge_datestamp INT(11) NOT NULL default '0',
			ge_name varchar(64) NOT NULL,
			ge_gender varchar(2) NOT NULL,
			KEY i_gedcom (ge_gedcom),
			KEY i_order (ge_order),
			KEY i_cache (ge_cache))";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			print $gm_lang["created_cache"]."<br />\n";
		}
		else {
			print $gm_lang["created_cache_fail"]."<br />\n";
			exit;
		}
	}

	if (!$has_statscache) {
		$sql = "CREATE TABLE ".$TBLPREFIX."statscache (
			gs_gedcom varchar(64) NOT NULL,
			gs_title varchar(255) NOT NULL default '',
			gs_nr_surnames INT(11) NOT NULL default '0',
			gs_nr_fams INT(11) NOT NULL default '0',
			gs_nr_sources INT(11) NOT NULL default '0',
			gs_nr_other INT(11) NOT NULL default '0',
			gs_nr_events INT(11) NOT NULL default '0',
			gs_earliest_birth_year INT(5) NOT NULL default '0',
			gs_earliest_birth_gid varchar(10) NOT NULL default '',
			gs_latest_birth_year INT(5) NOT NULL default '0',
			gs_latest_birth_gid varchar(10) NOT NULL default '',
			gs_longest_live_years INT(5) NOT NULL default '0',
			gs_longest_live_gid varchar(10) NOT NULL default '',
			gs_avg_age INT(4) NOT NULL default '0',
			gs_most_children_gid varchar(10) NOT NULL default '',
			gs_most_children_nr INT(3) NOT NULL default '0',
			gs_avg_children FLOAT NOT NULL default '0',
			PRIMARY KEY (gs_gedcom)
			)";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			print $gm_lang["created_scache"]."<br />\n";
		}
		else {
			print $gm_lang["created_scache_fail"]."<br />\n";
			exit;
		}
	}
	
	if (!$has_individual_spouse) {
		$sql = "CREATE TABLE ".$TBLPREFIX."individual_spouse (
			ID int(11) NOT NULL auto_increment,
			gedfile int(11) NOT NULL default '0',
			pid varchar(255) NOT NULL default '',
			family_id varchar(255) default NULL,
			PRIMARY KEY  (`ID`),
			KEY `pid` (`pid`)
			);";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			print $gm_lang["created_individual_spouse"]."<br />\n";
		}
		else {
			print $gm_lang["created_individual_spouse_fail"]."<br />\n";
			exit;
		}
	}
	
	if (!$has_individual_child) {
		$sql = "CREATE TABLE ".$TBLPREFIX."individual_child (
			ID int(11) NOT NULL auto_increment,
			gedfile int(11) NOT NULL default '0',
			pid varchar(255) NOT NULL default '',
			family_id varchar(255) default NULL,
			PRIMARY KEY  (`ID`),
			KEY `pid` (`pid`)
			);";
		$res = dbquery($sql);
		if(!DB::isError($res)) {
			print $gm_lang["created_individual_child"]."<br />\n";
		}
		else {
			print $gm_lang["created_individual_child_fail"]."<br />\n";
			exit;
		}
	}

	storeEnglish(true);
}

/**
 * delete a gedcom from the database
 *
 * deletes all of the imported data about a gedcom from the database
 * @param string $FILE	the gedcom to remove from the database
 */
function empty_database($FILE) {
	global $TBLPREFIX, $DBCONN, $GEDCOMS;

	$FILE = $DBCONN->escapeSimple($GEDCOMS[$FILE]["id"]);
	$sql = "DELETE FROM ".$TBLPREFIX."individuals WHERE i_file='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."families WHERE f_file='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."sources WHERE s_file='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."other WHERE o_file='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."places WHERE p_file='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."placelinks WHERE pl_file='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."names WHERE n_file='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."dates WHERE d_file='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."media WHERE m_gedfile='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."individual_spouse WHERE gedfile='$FILE'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."individual_child WHERE gedfile='$FILE'";
	$res = dbquery($sql);
	// Flush the caches
	ResetCaches(get_gedcom_from_id($FILE));
}

/**
 * perform any database cleanup
 *
 * during the import process it might be necessary to cleanup some database values.  In index mode
 * the file handles need to be closed.  For database mode we probably don't need to do anything in
 * this funciton.
 */
function cleanup_database() {
	global $DBCONN;
	//-- end the transaction
	$sql = "COMMIT";
	$res = dbquery($sql);
	$DBCONN->autoCommit(false);
	RETURN;
}

/**
 * get a list of all the source titles
 *
 * returns an array of all of the sourcetitles in the database.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#sources
 * @return array the array of source-titles
 */
function get_source_add_title_list() {
	global $sourcelist, $GEDCOM, $GEDCOMS;
	global $TBLPREFIX, $DBCONN;

	$sourcelist = array();

 	$sql = "SELECT s_id, s_file, s_file as s_name, s_gedcom FROM ".$TBLPREFIX."sources WHERE s_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' and ((s_gedcom LIKE '% _HEB %') || (s_gedcom LIKE '% ROMN %'));";

	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$source = array();
		$row = db_cleanup($row);
		$ct = preg_match("/\d ROMN (.*)/", $row["s_gedcom"], $match);
 		if ($ct==0) $ct = preg_match("/\d _HEB (.*)/", $row["s_gedcom"], $match);
		$source["name"] = $match[1];
		$source["gedcom"] = $row["s_gedcom"];
		$source["gedfile"] = $row["s_file"];
		$sourcelist[$row["s_id"]] = $source;
	}
	$res->free();

	return $sourcelist;
}

/**
 * get a list of all the sources
 *
 * returns an array of all of the sources in the database.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#sources
 * @return array the array of sources
 */
function get_source_list() {
	global $sourcelist, $GEDCOM, $GEDCOMS;
	global $TBLPREFIX, $DBCONN;

	$sourcelist = array();

	$sql = "SELECT * FROM ".$TBLPREFIX."sources WHERE s_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ORDER BY s_name";
	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$source = array();
		$source["name"] = $row["s_name"];
		$source["gedcom"] = $row["s_gedcom"];
		$row = db_cleanup($row);
		$source["gedfile"] = $row["s_file"];
//		$source["nr"] = 0;
		$sourcelist[$row["s_id"]] = $source;
	}
	$res->free();

	return $sourcelist;
}

//-- get the repositorylist from the datastore
function get_repo_list() {
	global $repolist, $GEDCOM, $GEDCOMS;
	global $TBLPREFIX, $DBCONN;
	
	$repolist = array();
	
	$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' AND o_type='REPO'";
	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$repo = array();
		$tt = preg_match("/1 NAME (.*)/", $row["o_gedcom"], $match);
		if ($tt == "0") $name = $row["o_id"]; else $name = $match[1];
		$repo["id"] = $row["o_id"];
		$repo["gedfile"] = $row["o_file"];
		$repo["type"] = $row["o_type"];
		$repo["gedcom"] = $row["o_gedcom"];
		$row = db_cleanup($row);
		$repolist[$name]= $repo;
	}
	$res->free();
	ksort($repolist);
	return $repolist;
}

//-- get the repositorylist from the datastore
function get_repo_id_list() {
	global $GEDCOM, $GEDCOMS;
	global $TBLPREFIX, $DBCONN;

	$repo_id_list = array();

	$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' AND o_type='REPO' ORDER BY o_id";
	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
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
	$res->free();
	return $repo_id_list;
}

/**
 * Get a list of all the repository titles
 *
 * returns an array of all of the repositorytitles in the database.
 * @link http://Genmod.sourceforge.net/devdocs/arrays.php#repositories
 * @return array the array of repository-titles
 */
function get_repo_add_title_list() {
	global $GEDCOM, $GEDCOMS;
	global $TBLPREFIX, $DBCONN;

	$repolist = array();

 	$sql = "SELECT o_id, o_file, o_file as o_name, o_type, o_gedcom FROM ".$TBLPREFIX."other WHERE o_type='REPO' AND o_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' and ((o_gedcom LIKE '% _HEB %') || (o_gedcom LIKE '% ROMN %'));";

	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$repo = array();
		$repo["gedcom"] = $row["o_gedcom"];
		$ct = preg_match("/\d ROMN (.*)/", $row["o_gedcom"], $match);
 		if ($ct==0) $ct = preg_match("/\d _HEB (.*)/", $row["o_gedcom"], $match);
		$repo["name"] = $match[1];
		$repo["id"] = "@".$row["o_id"]."@";
		$repo["gedfile"] = $row["o_file"];
		$repo["type"] = $row["o_type"];
		$row = db_cleanup($row);
		$repolist[$match[1]] = $repo;

	}
	$res->free();
	return $repolist;
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
function GetIndiList() {
	global $indilist, $GEDCOM, $GEDCOMS;
	global $TBLPREFIX, $INDILIST_RETRIEVED, $TOTAL_QUERIES;
	
	if ($INDILIST_RETRIEVED) return $indilist;
	$indilist = array();
	
	$sql = "SELECT names.*, i_gedcom, i_name, i_letter, i_surname, i_isdead, i_id, i_file ";
	$sql .= "FROM gm_individuals ";
	$sql .= "LEFT JOIN gm_names names ";
	$sql .= "ON i_file = names.n_file AND i_id = names.n_gid ";
	$sql .= "WHERE i_file = ".$GEDCOMS[$GEDCOM]["id"]." ";
	$sql .= "ORDER BY i_letter, i_surname ASC";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	$ct = mysql_num_rows($res);
	while($row = mysql_fetch_assoc($res)){
		$indi = array();
		$indi["gedcom"] = $row["i_gedcom"];
		$row = db_cleanup($row);
		$indi["names"][] = array($row["i_name"], $row["i_letter"], $row["i_surname"], "A");
		if (!empty($row["n_gid"])) $indi["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
		$indi["isdead"] = $row["i_isdead"];
		$indi["gedfile"] = $row["i_file"];
		$indi["letter"] = $row["i_letter"];
		$indilist[$row["i_id"]] = $indi;
	}
	mysql_free_result($res);
	$INDILIST_RETRIEVED = true;
	return $indilist;
}


//-- get the assolist from the datastore
function get_asso_list($type = "all") {
	global $assolist, $GEDCOM, $DBCONN, $GEDCOMS;
	global $TBLPREFIX, $ASSOLIST_RETRIEVED;

	if ($ASSOLIST_RETRIEVED) return $assolist;
	$assolist = array();

	$oldged = $GEDCOM;
	if (($type == "all") || ($type == "fam")) {
		$sql = "SELECT f_id, f_file, f_gedcom, f_husb, f_wife FROM ".$TBLPREFIX."families WHERE f_gedcom LIKE '% ASSO %'";
		$res = dbquery($sql);
		$ct = $res->numRows();
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			$asso = array();
			$asso["type"] = "fam";
			$pid2 = $row["f_id"]."[".$row["f_file"]."]";
			$asso["gedcom"] = $row["f_gedcom"];
			$asso["gedfile"] = $row["f_file"];
			// Get the family names
			$GEDCOM = get_gedcom_from_id($row["f_file"]);
			$hname = get_sortable_name($row["f_husb"], "", "", true);
			$wname = get_sortable_name($row["f_wife"], "", "", true);
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
		$res->free();
	}

	if (($type == "all") || ($type == "indi")) {
		$sql = "SELECT i_id, i_file, i_gedcom FROM ".$TBLPREFIX."individuals WHERE i_gedcom LIKE '% ASSO %'";
		$res = dbquery($sql);
		$ct = $res->numRows();
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			$asso = array();
			$asso["type"] = "indi";
			$pid2 = $row["i_id"]."[".$row["i_file"]."]";
			$asso["gedcom"] = $row["i_gedcom"];
			$asso["gedfile"] = $row["i_file"];
			$asso["name"] = get_indi_names($row["i_gedcom"]);
			$ca = preg_match_all("/\d ASSO @(.*)@/", $row["i_gedcom"], $match, PREG_SET_ORDER);
			for ($i=0; $i<$ca; $i++) {
				$pid = $match[$i][1]."[".$row["i_file"]."]";
				$assolist[$pid][$pid2] = $asso;
			}
			$row = db_cleanup($row);
		}
		$res->free();
	}
	
	$GEDCOM = $oldged;

	$ASSOLIST_RETRIEVED = true;
	return $assolist;
}

//-- get the famlist from the datastore
function get_fam_list() {
	global $famlist, $GEDCOM, $indilist, $DBCONN, $GEDCOMS;
	global $TBLPREFIX, $FAMLIST_RETRIEVED;

	if ($FAMLIST_RETRIEVED) return $famlist;
	$famlist = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."families WHERE f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$fam = array();
		$fam["gedcom"] = $row["f_gedcom"];
		$row = db_cleanup($row);
		$hname = get_sortable_name($row["f_husb"]);
		$wname = get_sortable_name($row["f_wife"]);
		$name = "";
		if (!empty($hname)) $name = $hname;
		else $name = "@N.N., @P.N.";

		if (!empty($wname)) $name .= " + ".$wname;
		else $name .= " + @N.N., @P.N.";

		$fam["name"] = $name;
		$fam["HUSB"] = $row["f_husb"];
		$fam["WIFE"] = $row["f_wife"];
		$fam["CHIL"] = $row["f_chil"];
		$fam["gedfile"] = $row["f_file"];
		$famlist[$row["f_id"]] = $fam;
	}
	$res->free();
	$FAMLIST_RETRIEVED = true;
	return $famlist;
}

//-- get the otherlist from the datastore
function get_other_list() {
	global $otherlist, $GEDCOM, $DBCONN, $GEDCOMS;
	global $TBLPREFIX;

	$otherlist = array();

	$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$source = array();
		$source["gedcom"] = $row["o_gedcom"];
		$row = db_cleanup($row);
		$source["type"] = $row["o_type"];
		$source["gedfile"] = $row["o_file"];
		$otherlist[$row["o_id"]]= $source;
	}
	$res->free();
	return $otherlist;
}

//-- search through the gedcom records for individuals
/**
 * Search the database for individuals that match the query
 *
 * uses a regular expression to search the gedcom records of all individuals and returns an
 * array list of the matching individuals
 *
 * @author	yalnifj
 * @param	string $query a regular expression query to search for
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myindilist array with all individuals that matched the query
 */
function search_indis($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	$myindilist = array();
//	if ($REGEXP_DB) $term = "REGEXP";
//	else $term = "LIKE";
//	if (!is_array($query)) $sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals WHERE (i_gedcom $term '".$DBCONN->escapeSimple(strtoupper($query))."' OR i_gedcom $term '".$DBCONN->escapeSimple(str2upper($query))."' OR i_gedcom $term '".$DBCONN->escapeSimple(str2lower($query))."')";
	if (!is_array($query)) $sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals WHERE (i_gedcom REGEXP '".$DBCONN->escapeSimple($query)."')";
	else {
		$sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
//			$sql .= "(i_gedcom $term '".$DBCONN->escapeSimple(str2upper($q))."' OR i_gedcom $term '".$DBCONN->escapeSimple(str2lower($q))."')";
			$sql .= "(i_gedcom REGEXP '".$DBCONN->escapeSimple($q)."')";
			$i++;
		}
		$sql .= ")";
	}
	if (!$allgeds) $sql .= " AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "i_file='".$DBCONN->escapeSimple($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}
	$res = dbquery($sql);
	if (!DB::isError($res)) {
		while($row = $res->fetchRow()){
			$row = db_cleanup($row);
			if (count($allgeds) > 1) {
				$myindilist[$row[0]."[".$row[2]."]"]["names"] = get_indi_names($row[3]);
				$myindilist[$row[0]."[".$row[2]."]"]["gedfile"] = $row[2];
				$myindilist[$row[0]."[".$row[2]."]"]["gedcom"] = $row[3];
				$myindilist[$row[0]."[".$row[2]."]"]["isdead"] = $row[4];
				if ($myindilist[$row[0]."[".$row[2]."]"]["gedfile"] == $GEDCOM) $indilist[$row[0]] = $myindilist[$row[0]."[".$row[2]."]"];
			}
			else {
				$myindilist[$row[0]]["names"] = get_indi_names($row[3]);
				$myindilist[$row[0]]["gedfile"] = $row[2];
				$myindilist[$row[0]]["gedcom"] = $row[3];
				$myindilist[$row[0]]["isdead"] = $row[4];
				if ($myindilist[$row[0]]["gedfile"] == $GEDCOM) $indilist[$row[0]] = $myindilist[$row[0]];
			}
		}
		$res->free();
	}
	return $myindilist;
}

//-- search through the gedcom records for individuals in families
function search_indis_fam($add2myindilist) {
	global $TBLPREFIX, $GEDCOM, $indilist, $myindilist;

	$sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals";
	$res = dbquery($sql);
	while($row = $res->fetchRow()){
		if (isset($add2myindilist[$row[0]])){
			$add2my_fam=$add2myindilist[$row[0]];
			$row = db_cleanup($row);
			$myindilist[$row[0]]["names"] = get_indi_names($row[3]);
			$myindilist[$row[0]]["gedfile"] = $row[2];
			$myindilist[$row[0]]["gedcom"] = $row[3].$add2my_fam;
			$myindilist[$row[0]]["isdead"] = $row[4];
			$indilist[$row[0]] = $myindilist[$row[0]];
		}
	}
	$res->free();
	return $myindilist;
}

function search_indis_year_range($startyear, $endyear, $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS;

	$myindilist = array();
	$sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals WHERE (";
	$i=$startyear;
	while($i <= $endyear) {
		if ($i > $startyear) $sql .= " OR ";
		if ($REGEXP_DB) $sql .= "i_gedcom REGEXP '".$DBCONN->escapeSimple("2 DATE[^\n]* ".$i)."'";
		else $sql .= "i_gedcom LIKE '".$DBCONN->escapeSimple("%2 DATE%".$i)."%'";
		$i++;
	}
	$sql .= ")";
	if (!$allgeds) $sql .= " AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$myindilist[$row[0]]["names"] = get_indi_names($row[3]);
		$myindilist[$row[0]]["gedfile"] = $row[2];
		$myindilist[$row[0]]["gedcom"] = $row[3];
		$myindilist[$row[0]]["isdead"] = $row[4];
		$indilist[$row[0]] = $myindilist[$row[0]];
	}
	$res->free();
	return $myindilist;
}


//-- search through the gedcom records for individuals
function search_indis_names($query, $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	
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
	if (empty($query)) $sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals";
	else if (!is_array($query)) $sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals WHERE i_name REGEXP '".$DBCONN->escapeSimple($query)."'";
	else {
		$sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if (!empty($q)) {
				if ($i>0) $sql .= " AND ";
				$sql .= "i_name REGEXP '".$DBCONN->escapeSimple($q)."'";
				$i++;
			}
		}
		$sql .= ")";
	}
	if (!$allgeds) $sql .= " AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if ($allgeds) $key = $row[0]."[".$row[2]."]";
		else $key = $row[0];
		if (isset($indilist[$key])) $myindilist[$key] = $indilist[$key];
		else {
			$myindilist[$key]["names"] = get_indi_names($row[3]);
			$myindilist[$key]["gedfile"] = $row[2];
			$myindilist[$key]["gedcom"] = $row[3];
			$myindilist[$key]["isdead"] = $row[4];
			if ($allgeds) $indilist[$key] = $myindilist[$key];
			else $indilist[$key] = $myindilist[$key];
		}
	}
	$res->free();
	if (!is_array($query)) $sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_id=n_gid AND i_file=n_file AND n_name REGEXP '".$DBCONN->escapeSimple($query)."'";
	else {
		$sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_id=n_gid AND i_file=n_file AND (";
		$i=0;
		foreach($query as $indexval => $q) {
			if (!empty($q)) {
				if ($i>0) $sql .= " AND ";
				$sql .= "n_name REGEXP '".$DBCONN->escapeSimple($q)."'";
				$i++;
			}
		}
		$sql .= ")";
	}
	if (!$allgeds) $sql .= " AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if ($allgeds) $key = $row[0]."[".$row[2]."]";
		else $key = $row[0];
		if (!isset($myindilist[$key])) {
			if (isset($indilist[$key])) $myindilist[$key] = $indilist[$key];
			else {
				$myindilist[$key]["names"] = get_indi_names($row[3]);
				$myindilist[$key]["gedfile"] = $row[2];
				$myindilist[$key]["gedcom"] = $row[3];
				$myindilist[$key]["isdead"] = $row[4];
				$indilist[$key] = $myindilist[$key];
			}
		}
	}
	$res->free();
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
function search_indis_dates($day="", $month="", $year="", $fact="", $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	$myindilist = array();
	
	$sql = "SELECT i_id, i_name, i_file, i_gedcom, i_isdead, i_letter, i_surname, d_gid, d_fact FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."individuals WHERE i_id=d_gid AND i_file=d_file ";
	if (!empty($day)) $sql .= "AND d_day='".$DBCONN->escapeSimple($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".$DBCONN->escapeSimple($month)."' ";
	if (!empty($year)) $sql .= "AND d_year='".$DBCONN->escapeSimple($year)."' ";
	if (!empty($fact)) {
		$sql .= "AND (";
		$facts = preg_split("/[,:; ]/", $fact);
		$i=0;
		foreach($facts as $fact) {
			if ($i!=0) $sql .= " OR ";
			$ct = preg_match("/!(\w+)/", $fact, $match);
			if ($ct > 0) {
				$fact = $match[1];
				$sql .= "d_fact!='".$DBCONN->escapeSimple($fact)."'";
			}
			else {
				$sql .= "d_fact='".$DBCONN->escapeSimple($fact)."'";
			}
			$i++;
		}
		$sql .= ") ";
	}
	if (!$allgeds) $sql .= "AND d_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ";
	$sql .= "GROUP BY i_id ORDER BY d_year, d_month, d_day DESC";
	$res = dbquery($sql);
	if (!DB::isError($res)) {
		while($row = $res->fetchRow()){
			$row = db_cleanup($row);
			if ($allgeds) {
				$myindilist[$row[0]."[".$row[2]."]"]["names"] = get_indi_names($row[3]);
				$myindilist[$row[0]."[".$row[2]."]"]["gedfile"] = $row[2];
				$myindilist[$row[0]."[".$row[2]."]"]["gedcom"] = $row[3];
				$myindilist[$row[0]."[".$row[2]."]"]["isdead"] = $row[4];
				if ($myindilist[$row[0]."[".$row[2]."]"]["gedfile"] == $GEDCOM) $indilist[$row[0]] = $myindilist[$row[0]."[".$row[2]."]"];
			}
			else {
				$myindilist[$row[0]]["names"] = get_indi_names($row[3]);
				$myindilist[$row[0]]["gedfile"] = $row[2];
				$myindilist[$row[0]]["gedcom"] = $row[3];
				$myindilist[$row[0]]["isdead"] = $row[4];
				if ($myindilist[$row[0]]["gedfile"] == $GEDCOM) $indilist[$row[0]] = $myindilist[$row[0]];
			}
		}
		$res->free();
	}
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
function search_indis_daterange($dstart="1", $mstart="1", $dend="31", $mend="12", $filter="all", $onlyBDM="no", $skipfacts="", $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	$myindilist = array();
//print "Dstart: ".$dstart."<br />";
//print "Mstart: ".date("M", mktime(1,0,0,$mstart,1))."<br />";
//print "Dend: ".$dend."<br />";
//print "Mend: ".date("M", mktime(1,0,0,$mend,1))."<br />";
	$sql = "SELECT i_id, i_file, i_gedcom, i_isdead FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."individuals WHERE i_id=d_gid AND i_file=d_file ";
	//-- Compute start
	// SQL for 1 day
	if ($dstart == $dend && $mstart == $mend) {
	$sql .= " AND d_day=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
	}
	// SQL for dates in 1 month
	else if ($mstart == $mend) {
		$sql .= " AND d_day BETWEEN ".$dstart." AND ".$dend." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
	}
	// SQL for >=2 months
	else {
		$sql .= " AND d_day!='0' AND ((d_day>=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."')";
		//-- intermediate months
		if ($mend < $mstart) $mend += 12;
		for($i=$mstart+1; $i<$mend;$i++) {
			if ($i>12) $m = $i-12;
			else $m = $i;
			$sql .= " OR d_month='".date("M", mktime(1,0,0,$m,1))."'";
		}
		//-- End 
		$sql .= " OR (d_day<=".$dend." AND d_month='".date("M", mktime(1,0,0,$mend,1))."'))";
	}
	if ($onlyBDM == "yes") $sql .= " AND d_fact NOT IN ('BIRT', 'DEAT')";
	if ($filter == "alive") $sql .= "AND i_isdead!='0'";
	if (!empty($skipfacts)) {
		$skip = preg_split("/[;, ]/", $skipfacts);
		$sql .= " AND d_fact NOT IN (";
		$i = 0;
		foreach ($skip as $key=>$value) {
			if ($i != 0 ) $sql .= ", ";
			$i++; 
			$sql .= "'".$value."'";
		}
		$sql .= ")";
	}

	if (!$allgeds) $sql .= " AND d_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ";
	$sql .= "GROUP BY i_id ORDER BY d_year, d_month, d_day DESC";
//	print $sql;
	$res = dbquery($sql);
	if (!DB::isError($res)) {
		while($row = $res->fetchRow()){
			$row = db_cleanup($row);
			if ($allgeds) {
				$myindilist[$row[0]."[".$row[1]."]"]["names"] = get_indi_names($row[2]);
				$myindilist[$row[0]."[".$row[1]."]"]["gedfile"] = $row[1];
				$myindilist[$row[0]."[".$row[1]."]"]["gedcom"] = $row[2];
				$myindilist[$row[0]."[".$row[1]."]"]["isdead"] = $row[3];
				if ($myindilist[$row[0]."[".$row[1]."]"]["gedfile"] == $GEDCOM) $indilist[$row[0]] = $myindilist[$row[0]."[".$row[1]."]"];
			}
			else {
				$myindilist[$row[0]]["names"] = get_indi_names($row[2]);
				$myindilist[$row[0]]["gedfile"] = $row[1];
				$myindilist[$row[0]]["gedcom"] = $row[2];
				$myindilist[$row[0]]["isdead"] = $row[3];
				if ($myindilist[$row[0]]["gedfile"] == $GEDCOM) $indilist[$row[0]] = $myindilist[$row[0]];
			}
		}
		$res->free();
	}
	return $myindilist;
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
function search_fams_daterange($dstart="1", $mstart="1", $dend="31", $mend="12", $onlyBDM="no", $skipfacts="", $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOM, $GEDCOMS;
	$myfamlist = array();
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."families WHERE f_id=d_gid AND f_file=d_file ";

	// SQL for 1 day
	if ($dstart == $dend && $mstart == $mend) {
	$sql .= " AND d_day=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
	}
	// SQL for dates in 1 month
	else if ($mstart == $mend) {
		$sql .= " AND d_day BETWEEN ".$dstart." AND ".$dend." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."'";
	}
	// SQL for >=2 months
	else {
		$sql .= " AND d_day!='0' AND ((d_day>=".$dstart." AND d_month='".date("M", mktime(1,0,0,$mstart,1))."')";
		//-- intermediate months
		if ($mend < $mstart) $mend += 12;
		for($i=$mstart+1; $i<$mend;$i++) {
			if ($i>12) $m = $i-12;
			else $m = $i;
			$sql .= " OR d_month='".date("M", mktime(1,0,0,$m,1))."'";
		}
		//-- End 
		$sql .= " OR (d_day<=".$dend." AND d_month='".date("M", mktime(1,0,0,$mend,1))."'))";
	}
	
	if ($onlyBDM == "yes") $sql .= " AND d_fact='MARR'";
	if (!empty($skipfacts)) {
		$skip = preg_split("/[;, ]/", $skipfacts);
		$sql .= " AND d_fact NOT IN (";
		$i = 0;
		foreach ($skip as $key=>$value) {
			if ($i != 0 ) $sql .= ", ";
			$i++; 
			$sql .= "'".$value."'";
		}
		$sql .= ")";
	}
	
	if (!$allgeds) $sql .= " AND d_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ";
	$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";

	$res = dbquery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		$hname = get_sortable_name($row[1]);
		$wname = get_sortable_name($row[2]);
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
	$res->free();
	return $myfamlist;
}

//-- search through the gedcom records for families
function search_fams($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	
	$myfamlist = array();
	if (!is_array($query)) $sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (f_gedcom REGEXP '".$DBCONN->escapeSimple($query)."')";
	else {
		$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(f_gedcom REGEXP '".$DBCONN->escapeSimple($q)."')";
			$i++;
		}
		$sql .= ")";
	}
	
	if (!$allgeds) $sql .= " AND f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0, $max=count($allgeds); $i<$max; $i++) {
			$sql .= "f_file='".$DBCONN->escapeSimple($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < $max-1) $sql .= " OR ";
		}
		$sql .= ")";
	}
	
	$res = dbquery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		if ($allnames == true) {
			$hname = get_sortable_name($row[1], "", "", true);
			$wname = get_sortable_name($row[2], "", "", true);
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
			$hname = get_sortable_name($row[1]);
			$wname = get_sortable_name($row[2]);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = $hname." + ".$wname;
		}
		if (count($allgeds) > 1) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOM = $gedold;
	$res->free();
	return $myfamlist;
}

//-- search through the gedcom records for families
function search_fams_names($query, $ANDOR="AND", $allnames=false, $gedcnt=1) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	
	$myfamlist = array();
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (";
	$i=0;
	foreach($query as $indexval => $q) {
		if ($i>0) $sql .= " $ANDOR ";
		$sql .= "((f_husb='".$DBCONN->escapeSimple($q[0])."' OR f_wife='".$DBCONN->escapeSimple($q[0])."') AND f_file='".$DBCONN->escapeSimple($GEDCOMS[$q[1]]["id"])."')";
		$i++;
	}
	$sql .= ")";

	$res = dbquery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		if ($allnames == true) {
			$hname = get_sortable_name($row[1], "", "", true);
			$wname = get_sortable_name($row[2], "", "", true);
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
			$hname = get_sortable_name($row[1]);
			$wname = get_sortable_name($row[2]);
			if (empty($hname)) $hname = "@N.N.";
			if (empty($wname)) $wname = "@N.N.";
			$name = $hname." + ".$wname;
		}
		if ($gedcnt > 1) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOM = $gedold;
	$res->free();
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
function search_fams_members($query, $allgeds=false, $ANDOR="AND", $allnames=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS;
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
	
	if (!$allgeds) $sql .= " AND f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0, $max=count($allgeds); $i<$max; $i++) {
			$sql .= "f_file='".$DBCONN->escapeSimple($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < $max-1) $sql .= " OR ";
		}
		$sql .= ")";
	}
	$res = dbquery($sql);
	$i=0;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		if ($allnames == true) {
			$hname = get_sortable_name($row[1], "", "", true);
			$wname = get_sortable_name($row[2], "", "", true);
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
			$hname = get_sortable_name($row[1]);
			$wname = get_sortable_name($row[2]);
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
	$res->free();
	return $myfamlist;
}

//-- search through the gedcom records for families with daterange
function search_fams_year_range($startyear, $endyear, $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS;

	$myfamlist = array();
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom FROM ".$TBLPREFIX."families WHERE (";
	$i=$startyear;
	while($i <= $endyear) {
		if ($i > $startyear) $sql .= " OR ";
		if ($REGEXP_DB) $sql .= "f_gedcom REGEXP '".$DBCONN->escapeSimple("2 DATE[^\n]* ".$i)."'";
		else $sql .= "f_gedcom LIKE '".$DBCONN->escapeSimple("%2 DATE%".$i)."%'";
		$i++;
	}
	$sql .= ")";
	if (!$allgeds) $sql .= " AND f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$hname = get_sortable_name($row[1]);
		$wname = get_sortable_name($row[2]);
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = $hname." + ".$wname;
		$myfamlist[$row[0]]["name"] = $name;
		$myfamlist[$row[0]]["gedfile"] = $row[3];
		$myfamlist[$row[0]]["gedcom"] = $row[4];
		$famlist[$row[0]] = $myfamlist[$row[0]];
	}
	$res->free();
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
function search_fams_dates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOM, $GEDCOMS;
	$myfamlist = array();
	
	$sql = "SELECT f_id, f_husb, f_wife, f_file, f_gedcom, d_gid, d_fact FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."families WHERE f_id=d_gid AND f_file=d_file ";
	if (!empty($day)) $sql .= "AND d_day='".$DBCONN->escapeSimple($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".$DBCONN->escapeSimple(str2upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".$DBCONN->escapeSimple($year)."' ";
	if (!empty($fact)) {
		$sql .= "AND (";
		$facts = preg_split("/[,:; ]/", $fact);
		$i=0;
		foreach($facts as $fact) {
			if ($i!=0) $sql .= " OR ";
			$ct = preg_match("/!(\w+)/", $fact, $match);
			if ($ct > 0) {
				$fact = $match[1];
				$sql .= "d_fact!='".$DBCONN->escapeSimple(str2upper($fact))."'";
			}
			else {
				$sql .= "d_fact='".$DBCONN->escapeSimple(str2upper($fact))."'";
			}
			$i++;
		}
		$sql .= ") ";
	}
	if (!$allgeds) $sql .= "AND d_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ";
	$sql .= "GROUP BY f_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = dbquery($sql);
	$gedold = $GEDCOM;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$GEDCOM = get_gedcom_from_id($row[3]);
		$hname = get_sortable_name($row[1]);
		$wname = get_sortable_name($row[2]);
		if (empty($hname)) $hname = "@N.N.";
		if (empty($wname)) $wname = "@N.N.";
		$name = $hname." + ".$wname;
		if ($allgeds) {
			$myfamlist[$row[0]."[".$row[3]."]"]["name"] = $name;
			$myfamlist[$row[0]."[".$row[3]."]"]["gedfile"] = $row[3];
			$myfamlist[$row[0]."[".$row[3]."]"]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]."[".$row[3]."]"];
		}
		else {
			$myfamlist[$row[0]]["name"] = $name;
			$myfamlist[$row[0]]["gedfile"] = $row[3];
			$myfamlist[$row[0]]["gedcom"] = $row[4];
			$famlist[$row[0]] = $myfamlist[$row[0]];
		}
	}
	$GEDCOM = $gedold;
	$res->free();
	return $myfamlist;
}

//-- search through the gedcom records for sources
function search_sources($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS;
	
	$mysourcelist = array();	
	if (!is_array($query)) $sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".$TBLPREFIX."sources WHERE (s_gedcom REGEXP '".$DBCONN->escapeSimple($query)."')";
	else {
		$sql = "SELECT s_id, s_name, s_file, s_gedcom FROM ".$TBLPREFIX."sources WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(s_gedcom REGEXP '".$DBCONN->escapeSimple($q)."')";
			$i++;
		}
		$sql .= ")";
	}	
	if (!$allgeds) $sql .= " AND s_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "s_file='".$DBCONN->escapeSimple($GEDCOMS[$allgeds[$i]]["id"])."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}

	$res = dbquery($sql);
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
	$res->free();
	return $mysourcelist;
}

/**
 * Search the dates table for sources that had events on the given day
 *
 * @author	yalnifj
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function search_sources_dates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	$mysourcelist = array();
	
	$sql = "SELECT s_id, s_name, s_file, s_gedcom, d_gid FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."sources WHERE s_id=d_gid AND s_file=d_file ";
	if (!empty($day)) $sql .= "AND d_day='".$DBCONN->escapeSimple($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".$DBCONN->escapeSimple(str2upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".$DBCONN->escapeSimple($year)."' ";
	if (!empty($fact)) $sql .= "AND d_fact='".$DBCONN->escapeSimple(str2upper($fact))."' ";
	if (!$allgeds) $sql .= "AND d_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ";
	$sql .= "GROUP BY s_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = dbquery($sql);
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
	$res->free();
	return $mysourcelist;
}

//-- search through the gedcom records for sources
function search_repos($query, $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS;
	
	$myrepolist = array();
	$sql = "SELECT o_id, o_file, o_gedcom FROM ".$TBLPREFIX."other WHERE o_type='REPO' AND (o_gedcom REGEXP '".$DBCONN->escapeSimple($query)."')";
	if (!$allgeds) $sql .= " AND o_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		$tt = preg_match("/1 NAME (.*)/", $row[2], $match);
		if ($tt == "0") $name = $row[0]; else $name = $match[1];
		if ($allgeds) {
			$myrepolist[$row[0]."[".$row[1]."]"]["name"] = $name;
			$myrepolist[$row[0]."[".$row[1]."]"]["gedfile"] = $row[1];
			$myrepolist[$row[0]."[".$row[1]."]"]["gedcom"] = $row[2];
		}
		else {
			$myrepolist[$row[0]]["name"] = $name;
			$myrepolist[$row[0]]["gedfile"] = $row[1];
			$myrepolist[$row[0]]["gedcom"] = $row[2];
		}
	}
	$res->free();
	return $myrepolist;
}

/**
 * Search the dates table for other records that had events on the given day
 *
 * @author	yalnifj
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myfamlist array with all individuals that matched the query
 */
function search_other_dates($day="", $month="", $year="", $fact="", $allgeds=false) {
	global $TBLPREFIX, $GEDCOM, $famlist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	$myrepolist = array();
	
	$sql = "SELECT o_id, o_file, o_type, o_gedcom, d_gid FROM ".$TBLPREFIX."dates, ".$TBLPREFIX."other WHERE o_id=d_gid AND o_file=d_file ";
	if (!empty($day)) $sql .= "AND d_day='".$DBCONN->escapeSimple($day)."' ";
	if (!empty($month)) $sql .= "AND d_month='".$DBCONN->escapeSimple(str2upper($month))."' ";
	if (!empty($year)) $sql .= "AND d_year='".$DBCONN->escapeSimple($year)."' ";
	if (!empty($fact)) $sql .= "AND d_fact='".$DBCONN->escapeSimple(str2upper($fact))."' ";
	if (!$allgeds) $sql .= "AND d_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ";
	$sql .= "GROUP BY o_id ORDER BY d_year, d_month, d_day DESC";
	
	$res = dbquery($sql);
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
	$res->free();
	return $myrepolist;
}

/**
 * get place parent ID
 * @param array $parent
 * @param int $level
 * @return int
 */
function get_place_parent_id($parent, $level) {
	global $DBCONN, $TBLPREFIX, $GEDCOM, $GEDCOMS;

	$parent_id=0;
	for($i=0; $i<$level; $i++) {
		$escparent=preg_replace("/\?/","\\\\\\?", $DBCONN->escapeSimple($parent[$i]));
		$psql = "SELECT p_id FROM ".$TBLPREFIX."places WHERE p_level=".$i." AND p_parent_id=$parent_id AND p_place LIKE '".$escparent."' AND p_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ORDER BY p_place";
		$res = dbquery($psql);
		$row = $res->fetchRow();
		$res->free();
		if (empty($row[0])) break;
		$parent_id = $row[0];
	}
	return $parent_id;
}

/**
 * find all of the places in the hierarchy
 * The $parent array holds the parent hierarchy of the places
 * we want to get.  The level holds the level in the hierarchy that
 * we are at.
 */
function get_place_list() {
	global $numfound, $j, $level, $parent, $found;
	global $GEDCOM, $TBLPREFIX, $placelist, $positions, $DBCONN, $GEDCOMS;

	// --- find all of the place in the file
	if ($level==0) $sql = "SELECT p_place FROM ".$TBLPREFIX."places WHERE p_level=0 AND p_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ORDER BY p_place";
	else {
		$parent_id = get_place_parent_id($parent, $level);
		$sql = "SELECT p_place FROM ".$TBLPREFIX."places WHERE p_level=$level AND p_parent_id=$parent_id AND p_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ORDER BY p_place";
	}
	$res = dbquery($sql);
	while ($row = $res->fetchRow()) {
		$placelist[] = $row[0];
		$numfound++;
	}
	$res->free();
}

/**
 * get all of the place connections
 * @param array $parent
 * @param int $level
 * @return array
 */
function get_place_positions($parent, $level) {
	global $positions, $TBLPREFIX, $GEDCOM, $DBCONN, $GEDCOMS;

	$p_id = get_place_parent_id($parent, $level);
	$sql = "SELECT DISTINCT pl_gid FROM ".$TBLPREFIX."placelinks WHERE pl_p_id=$p_id AND pl_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	while ($row = $res->fetchRow()) {
		$positions[] = $row[0];
	}
	return $positions;
}

function search_places($sql, $splace) {
	global $placelist;

	$res = dbquery($sql);
	$k=0;
	while($row = $res->fetchRow()){
		$row = db_cleanup($row);
		print " ";
		if ($k%4000 == 0) print "\n";
		// -- put all the places into an array
		if (empty($splace)) $ct = preg_match_all("/\d PLAC (.*)/", $row[1], $match, PREG_SET_ORDER);
		else $ct = preg_match_all("/\d PLAC (.*$splace.*)/i", $row[1], $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$place = $match[$i][1];
			$place=trim($place);

			$place=preg_replace("/[\.\"\><]/", "", $place);
			$levels = preg_split ("/,/", $place);		// -- split the place into comma seperated values
			$levels = array_reverse($levels);				// -- reverse the array so that we get the top level first
			$placetext="";
			$j=0;
			foreach($levels as $indexval => $level) {
				if ($j>0) $placetext .= ", ";
				$placetext .= trim($level);
				$j++;
			}
			$placelist[] = $placetext;
			$k++;
		}//--end for
	}//-- end while
	$res->free();
}

//-- find all of the places
function find_place_list($place) {
	global $GEDCOM, $TBLPREFIX, $placelist, $indilist, $famlist, $sourcelist, $otherlist, $DBCONN, $GEDCOMS;
	
	$sql = "SELECT p_id, p_place, p_parent_id  FROM ".$TBLPREFIX."places WHERE p_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ORDER BY p_parent_id, p_id";
	$res = dbquery($sql);
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
				$upperplace = str2upper($pplace);
				if (!isset($found[$upperplace])) {
					$found[$upperplace] = $pplace;
				}
			}
		}
		$placelist = array_values($found);
	}
}

function find_media($sql, $type) {
	global $ct, $medialist, $MEDIA_DIRECTORY, $foundlist, $GM_IMAGE_DIR, $GM_IMAGES;
	$res = dbquery($sql);
	while($row = $res->fetchRow()){
		print " ";
		find_media_in_record($row[0]);
	}
	$res->free();
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

function GetIndiAlpha() {
	global $CHARACTER_SET, $TBLPREFIX, $GEDCOM, $LANGUAGE, $SHOW_MARRIED_NAMES;
	global $TOTAL_QUERIES, $GEDCOMS;
	$indialpha = array();
	
	$sql = "SELECT DISTINCT i_letter AS alpha ";
	$sql .= "FROM ".$TBLPREFIX."individuals ";
	$sql .= "WHERE i_file = ".$GEDCOMS[$GEDCOM]["id"]." ";
	$sql .= "UNION ";
	$sql .= "SELECT DISTINCT n_letter AS alpha ";
	$sql .= "FROM ".$TBLPREFIX."names ";
	$sql .= "WHERE n_file = ".$GEDCOMS[$GEDCOM]["id"];
	
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	
	$hungarianex = array("DZS", "CS", "DZ" , "GY", "LY", "NY", "SZ", "TY", "ZS");
	$danishex = array("OE", "AE", "AA");
	while($row = mysql_fetch_assoc($res)){
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
	mysql_free_result($res);
	return $indialpha;
}

//-- get the first character in the list
function get_fam_alpha() {
	global $CHARACTER_SET, $TBLPREFIX, $GEDCOM, $LANGUAGE, $famalpha, $DBCONN, $GEDCOMS;

	$famalpha = array();
	$sql = "SELECT DISTINCT i_letter as alpha FROM ".$TBLPREFIX."individuals WHERE i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' AND i_gedcom LIKE '%1 FAMS%' ORDER BY alpha";
	$res = dbquery($sql);
	
	$hungarianex = array("DZS", "CS", "DZ" , "GY", "LY", "NY", "SZ", "TY", "ZS");
	$danishex = array("OE", "AE", "AA");
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
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
	$res->free();
	$sql = "SELECT DISTINCT n_letter as alpha FROM ".$TBLPREFIX."names, ".$TBLPREFIX."individuals WHERE i_file=n_file AND i_id=n_gid AND n_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' AND i_gedcom LIKE '%1 FAMS%' ORDER BY alpha";
	$res = dbquery($sql);
	
	$hungarianex = array("DZS", "CS", "DZ" , "GY", "LY", "NY", "SZ", "TY", "ZS");
	$danishex = array("OE", "AE", "AA");
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
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
	$res->free();
	$sql = "SELECT f_id FROM ".$TBLPREFIX."families WHERE f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' AND (f_husb='' || f_wife='')";
	$res = dbquery($sql);
	if ($res->numRows()>0) {
		$famalpha["@"] = "@";
	}
	$res->free();
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
function GetAlphaIndis($letter) {
	global $TBLPREFIX, $GEDCOM, $LANGUAGE, $indilist, $surname, $SHOW_MARRIED_NAMES;
	global $TOTAL_QUERIES, $DBCONN, $GEDCOMS;

	$tindilist = array();
	$search_letter = "";
	
	// NOTE: Determine what letter to search for depending on the active language
	if ($LANGUAGE == "hungarian"){
		if (strlen($letter) >= 2) $search_letter = "'".$DBCONN->escapeSimple($letter)."' ";
		else {
			if ($letter == "C") $text = "CS";
			else if ($letter == "D") $text = "DZ";
			else if ($letter == "G") $text = "GY";
			else if ($letter == "L") $text = "LY";
			else if ($letter == "N") $text = "NY";
			else if ($letter == "S") $text = "SZ";
			else if ($letter == "T") $text = "TY";
			else if ($letter == "Z") $text = "ZS";
			if (isset($text)) $search_letter = "(i_letter = '".$DBCONN->escapeSimple($letter)."' AND i_letter != '".$DBCONN->escapeSimple($text)."') ";
			else $search_letter = "i_letter LIKE '".$DBCONN->escapeSimple($letter)."%' ";
		}
	}
	else if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian") {
		if ($letter == "Ø") $text = "OE";
		else if ($letter == "Æ") $text = "AE";
		else if ($letter == "Å") $text = "AA";
		if (isset($text)) $search_letter = "(i_letter = '".$DBCONN->escapeSimple($letter)."' OR i_letter = '".$DBCONN->escapeSimple($text)."') ";
		else if ($letter=="A") $search_letter = "i_letter LIKE '".$DBCONN->escapeSimple($letter)."' ";
		else $search_letter = "i_letter LIKE '".$DBCONN->escapeSimple($letter)."%' ";
	}
	else $search_letter = "i_letter LIKE '".$DBCONN->escapeSimple($letter)."%' ";
	
	// NOTE: Select the records from the individual table
	$sql = "SELECT i_id, i_name, i_isdead, i_letter, i_surname, i_gedcom, i_file, ' ' as n_type ";
	$sql .= "FROM ".$TBLPREFIX."individuals ";
	$sql .= "WHERE ".$search_letter;
	// NOTE: Add some optimization if the surname is set to speed up the lists
	if (!empty($surname)) $sql .= "AND i_surname LIKE '%".$DBCONN->escapeSimple($surname)."%' ";
	$sql .= "AND i_file = ".$GEDCOMS[$GEDCOM]["id"]." ";
	// NOTE: Combine both queries
	$sql .= "UNION ";
	// NOTE: Select the records from the names table
	$sql .= "SELECT i_id, n_name, i_isdead, n_letter, n_surname, i_gedcom, n_file, n_type ";
	$sql .= "FROM ".$TBLPREFIX."names, ".$TBLPREFIX."individuals ";
	$sql .= "WHERE n_gid = i_id ";
	$sql .= "AND ".str_replace("i_letter", "n_letter", $search_letter);
	// NOTE: Add some optimization if the surname is set to speed up the lists
	if (!empty($surname)) $sql .= "AND i_surname LIKE '%".$DBCONN->escapeSimple($surname)."%' ";
	// NOTE: Do not retrieve married names if the user does not want to see them
	if (!$SHOW_MARRIED_NAMES) $sql .= "AND n_type NOT LIKE 'C' ";
	// NOTE: Make the selection on the currently active gedcom
	$sql .= "AND n_file = ".$GEDCOMS[$GEDCOM]["id"]." ";
	$sql .= "AND i_file = ".$GEDCOMS[$GEDCOM]["id"];
	
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	while($row = mysql_fetch_assoc($res)){
		$row = db_cleanup($row);
		if (substr($row["i_letter"], 0, 1) == substr($letter, 0, 1) || (isset($text) ? substr($row["i_letter"], 0, 1) == substr($text, 0, 1) : FALSE)){
			if (!isset($indilist[$row["i_id"]])) {
				$indi = array();
				$indi["names"] = array(array($row["i_name"], $row["i_letter"], $row["i_surname"], 'P'));
				$indi["isdead"] = $row["i_isdead"];
				$indi["gedcom"] = $row["i_gedcom"];
				$indi["gedfile"] = $row["i_file"];
				$tindilist[$row["i_id"]] = $indi;
				// NOTE: Cache the item in the $indilist for improved speed
				$indilist[$row["i_id"]] = $indi;
			}
			else {
				$indilist[$row["i_id"]]["names"][] = array($row["i_name"], $row["i_letter"], $row["i_surname"], $row["n_type"]);
				$tindilist[$row["i_id"]] = $indilist[$row["i_id"]];
			}
			
		}
	}
	mysql_free_result($res);
	return $tindilist;
}

/**
 * Get Individuals with a given surname
 *
 * This function finds all of the individuals who have the given surname
 * @param string $surname	The surname to search on
 * @return array	$indilist array
 */
function get_surname_indis($surname) {
	global $TBLPREFIX, $GEDCOM, $LANGUAGE, $indilist, $SHOW_MARRIED_NAMES, $DBCONN, $GEDCOMS;

	$tindilist = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."individuals WHERE i_surname LIKE '".$DBCONN->escapeSimple($surname)."' ";
	$sql .= "AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$row = db_cleanup($row);
		$indi = array();
		$indi["names"] = array(array($row["i_name"], $row["i_letter"], $row["i_surname"], "P"));
		$indi["isdead"] = $row["i_isdead"];
		$indi["gedcom"] = $row["i_gedcom"];
		$indi["gedfile"] = $row["i_file"];
		$indilist[$row["i_id"]] = $indi;
		$tindilist[$row["i_id"]] = $indilist[$row["i_id"]];
	}
	$res->free();

	$sql = "SELECT i_id, i_name, i_file, i_isdead, i_gedcom, i_letter, i_surname, n_letter, n_name, n_surname, n_letter, n_type FROM ".$TBLPREFIX."individuals, ".$TBLPREFIX."names WHERE i_id=n_gid AND i_file=n_file AND n_surname LIKE '".$DBCONN->escapeSimple($surname)."' ";
	if (!$SHOW_MARRIED_NAMES) $sql .= "AND n_type!='C' ";
	$sql .= "AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."' ORDER BY n_surname";
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$row = db_cleanup($row);
		if (isset($indilist[$row["i_id"]])) {
			$indilist[$row["i_id"]]["names"][] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
			$tindilist[$row["i_id"]] = $indilist[$row["i_id"]];
		}
		else {
			$indi = array();
			$indi["names"] = array(array($row["i_name"], $row["i_letter"], $row["i_surname"], "P"), array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]));
			$indi["isdead"] = $row["i_isdead"];
			$indi["gedcom"] = $row["i_gedcom"];
			$indi["gedfile"] = $row["i_file"];
			$indilist[$row["i_id"]] = $indi;
			$tindilist[$row["i_id"]] = $indilist[$row["i_id"]];
		}
	}
	$res->free();
	return $tindilist;
}

/**
 * Get Families Starting with a letter
 *
 * This function finds all of the families who start with the given letter
 * @param string $letter	The letter to search on
 * @return array	$indilist array
 * @see GetAlphaIndis()
 * @see http://www.Genmod.net/devdocs/arrays.php#famlist
 */
function get_alpha_fams($letter) {
	global $TBLPREFIX, $GEDCOM, $famlist, $indilist, $gm_lang, $LANGUAGE, $SHOW_MARRIED_NAMES, $DBCONN, $GEDCOMS;
	$tfamlist = array();
	$temp = $SHOW_MARRIED_NAMES;
	$SHOW_MARRIED_NAMES = false;
	$myindilist = GetAlphaIndis($letter);
	$SHOW_MARRIED_NAMES = $temp;
	if ($letter=="(" || $letter=="[" || $letter=="?") $letter = "\\".$letter;
	foreach($myindilist as $gid=>$indi) {
		$ct = preg_match_all("/1 FAMS @(.*)@/", $indi["gedcom"], $match, PREG_SET_ORDER);
		$surnames = array();
		for($i=0; $i<$ct; $i++) {
			$famid = $match[$i][1];
			$famrec = find_family_record($famid);
			if ($famlist[$famid]["husb"]==$gid) {
				$HUSB = $famlist[$famid]["husb"];
				$WIFE = $famlist[$famid]["wife"];
			}
			else {
				$HUSB = $famlist[$famid]["wife"];
				$WIFE = $famlist[$famid]["husb"];
			}
			$hname="";
			$surnames = array();
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
						$surnames[str2upper($namearray[2])] = $namearray[2];
						$hname = sortable_name_from_name($namearray[0]);
					}
				}
			}
			if (!empty($hname)) {
				$wname = get_sortable_name($WIFE);
				if (hasRTLText($hname)) {
					$indirec = find_person_record($WIFE);
					if (isset($indilist[$WIFE])) {
						foreach($indilist[$WIFE]["names"] as $n=>$namearray) {
							if (hasRTLText($namearray[0])) {
								$wname = sortable_name_from_name($namearray[0]);
								break;
							}
						}
					}
				}
				$name = $hname ." + ". $wname;
				$famlist[$famid]["name"] = $name;
				if (!isset($famlist[$famid]["surnames"])||count($famlist[$famid]["surnames"])==0) $famlist[$famid]["surnames"] = $surnames;
				else gm_array_merge($famlist[$famid]["surnames"], $surnames);
				$tfamlist[$famid] = $famlist[$famid];
			}
		}
	}

	//-- handle the special case for @N.N. when families don't have any husb or wife
	//-- SHOULD WE SHOW THE UNDEFINED? MA
	if ($letter=="@") {
		$sql = "SELECT * FROM ".$TBLPREFIX."families WHERE (f_husb='' OR f_wife='') AND f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
		if ($res->numRows()>0) {
			while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
				$fam = array();
				$hname = get_sortable_name($row["f_husb"]);
				$wname = get_sortable_name($row["f_wife"]);
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
		$res->free();
	}
	return $tfamlist;
}

/**
 * Get Families with a given surname
 *
 * This function finds all of the individuals who have the given surname
 * @param string $surname	The surname to search on
 * @return array	$indilist array
 * @see http://www.Genmod.net/devdocs/arrays.php#indilist
 */
function get_surname_fams($surname) {
	global $TBLPREFIX, $GEDCOM, $famlist, $indilist, $gm_lang, $DBCONN, $SHOW_MARRIED_NAMES, $GEDCOMS;
	$tfamlist = array();
	$temp = $SHOW_MARRIED_NAMES;
	$SHOW_MARRIED_NAMES = false;
	$myindilist = get_surname_indis($surname);
	$SHOW_MARRIED_NAMES = $temp;
	foreach($myindilist as $gid=>$indi) {
		$ct = preg_match_all("/1 FAMS @(.*)@/", $indi["gedcom"], $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			$famid = $match[$i][1];
			$famrec = find_family_record($famid);
			if ($famlist[$famid]["husb"]==$gid) {
				$HUSB = $famlist[$famid]["husb"];
				$WIFE = $famlist[$famid]["wife"];
			}
			else {
				$HUSB = $famlist[$famid]["wife"];
				$WIFE = $famlist[$famid]["husb"];
			}
			$hname = "";
			foreach($indi["names"] as $indexval => $namearray) {
				if (stristr($namearray[2], $surname)!==false) $hname = sortable_name_from_name($namearray[0]);
			}
			if (!empty($hname)) {
				$wname = get_sortable_name($WIFE);
				if (hasRTLText($hname)) {
					$indirec = find_person_record($WIFE);
					if (isset($indilist[$WIFE])) {
						foreach($indilist[$WIFE]["names"] as $n=>$namearray) {
							if (hasRTLText($namearray[0])) {
								$wname = sortable_name_from_name($namearray[0]);
								break;
							}
						}
					}
				}
				$name = $hname ." + ". $wname;
				$famlist[$famid]["name"] = $name;
				$tfamlist[$famid] = $famlist[$famid];
			}
		}
	}

	//-- handle the special case for @N.N. when families don't have any husb or wife
	//-- SHOULD WE SHOW THE UNDEFINED? MA
	if ($surname=="@N.N.") {
		$sql = "SELECT * FROM ".$TBLPREFIX."families WHERE (f_husb='' OR f_wife='') AND f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
		if ($res->numRows()>0) {
			while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
				$fam = array();
				$hname = get_sortable_name($row["f_husb"]);
				$wname = get_sortable_name($row["f_wife"]);
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
				$tfamlist[$row["f_id"]] = $fam;
				//-- cache the items in the lists for improved speed
				$famlist[$row["f_id"]] = $fam;
			}
		}
		$res->free();
	}
	return $tfamlist;
}

//-- function to find the gedcom id for the given rin
function find_rin_id($rin) {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $GEDCOMS;

	$sql = "SELECT i_id FROM ".$TBLPREFIX."individuals WHERE i_rin='$rin' AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		return $row["i_id"];
	}
	return $rin;
}

function delete_gedcom($ged) {
	global $INDEX_DIRECTORY, $TBLPREFIX, $gm_changes, $DBCONN, $GEDCOMS;

	$dbged = $GEDCOMS[$ged]["id"];
	$sql = "DELETE FROM ".$TBLPREFIX."individuals WHERE i_file='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."families WHERE f_file='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."sources WHERE s_file='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."other WHERE o_file='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."places WHERE p_file='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."favorites WHERE fv_file='".$DBCONN->escapeSimple($ged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."news WHERE n_username='".$DBCONN->escapeSimple($ged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."blocks WHERE b_username='".$DBCONN->escapeSimple($ged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."names WHERE n_file='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."placelinks WHERE pl_file='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."dates WHERE d_file='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."media WHERE m_gedfile='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$DBCONN->escapeSimple($dbged)."'";
	$res = dbquery($sql);
}

//-- return the current size of the given list
//- list options are indilist famlist sourcelist and otherlist
function get_list_size($list) {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $GEDCOMS;

	switch($list) {
		case "indilist":
			$sql = "SELECT count(i_id) FROM ".$TBLPREFIX."individuals WHERE i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
			$res = dbquery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "famlist":
			$sql = "SELECT count(f_id) FROM ".$TBLPREFIX."families WHERE f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
			$res = dbquery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "sourcelist":
			$sql = "SELECT count(s_id) FROM ".$TBLPREFIX."sources WHERE s_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
			$res = dbquery($sql);
			while($row = $res->fetchRow()) return $row[0];
		break;
		case "otherlist":
			$sql = "SELECT count(o_id) FROM ".$TBLPREFIX."other WHERE o_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
			$res = dbquery($sql);
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
function accept_change($cid, $gedfile, $all=false) {
	global $GEDCOM, $TBLPREFIX, $FILE, $DBCONN, $GEDCOMS, $gm_username;
	global $MEDIA_ID_PREFIX, $FAM_ID_PREFIX, $GEDCOM_ID_PREFIX, $SOURCE_ID_PREFIX, $REPO_ID_PREFIX;
	
	$cidchanges = array();
	if ($all) $sql = "SELECT ch_id, ch_cid, ch_gid, ch_gedfile, ch_old, ch_new FROM ".$TBLPREFIX."changes WHERE ch_gedfile = '".$gedfile."'";
	else $sql = "SELECT ch_id, ch_cid, ch_gid, ch_gedfile, ch_old, ch_new FROM ".$TBLPREFIX."changes WHERE ch_cid = '".$cid."' AND ch_gedfile = '".$gedfile."'";
	$res = dbquery($sql);
	while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$cidchanges[$row["ch_id"]]["cid"] = $row["ch_cid"];
		$cidchanges[$row["ch_id"]]["gid"] = $row["ch_gid"];
		$cidchanges[$row["ch_id"]]["gedfile"] = $row["ch_gedfile"];
		$cidchanges[$row["ch_id"]]["old"] = $row["ch_old"];
		$cidchanges[$row["ch_id"]]["new"] = $row["ch_new"];
	}
	if (count($cidchanges) > 0) {
		foreach ($cidchanges as $id => $details) {
			$gedrec = find_gedcom_record($details["gid"], get_gedcom_from_id($details["gedfile"]));
			// NOTE: Import the record
			$FILE = get_gedcom_from_id($details["gedfile"]);
			$update_id = "";
			if (empty($gedrec)) $gedrec = "";
			// NOTE: If old is empty, just add the new data makes sure it is not new record
			if (empty($details["old"]) && !empty($details["new"]) && preg_match("/0\s@(.*)@/", $details["new"]) == 0) {
				$gedrec .= "\r\n".$details["new"];
				$update_id = update_record($gedrec);
			}
			// NOTE: If the old is empty and the new is a new record make sure we just store the new record
			else if (empty($details["old"]) && preg_match("/0\s@(.*)@/", $details["new"]) > 0) {
				$update_id = update_record($details["new"]);
			}
			// NOTE: if both old and new are empty, the record needs to be deleted
			else if (empty($details["old"]) && empty($details["new"])) {
				$update_id = update_record(find_gedcom_record($details["gid"]), true);
				
				// NOTE: Delete change records related to this record
				$sql = "select ch_cid from ".$TBLPREFIX."changes where ch_gid = '".$details["gid"]."' AND ch_gedfile = '".$details["gedfile"]."'";
				$res = dbquery($sql);
				while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					reject_change($row["ch_cid"], $details["gedfile"]);
				}
				
			}
			// NOTE: If new is empty or filled, the old needs to be replaced
			else {
				$gedrec = str_replace($details["old"], $details["new"], $gedrec);
				$update_id = update_record($gedrec);
			}
			WriteToLog("Accepted change $cid - $gedfile "." ->" . $gm_username ."<-", "I", "G", get_gedcom_from_id($gedfile));
		}
		ResetCaches();
	}
	// NOTE: record has been imported in DB, now remove the change
	foreach ($cidchanges as $id => $value) {
		$sql = "DELETE from ".$TBLPREFIX."changes where ch_cid = '".$value["cid"]."'";
		$res = dbquery($sql);
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
function reject_change($cid, $gedfile, $all=false) {
	global $GEDCOMS, $GEDCOM, $TBLPREFIX, $manual_save, $gm_username;
	
	// NOTE: Get the details of the change id, to check if we need to unlock any records
	$sql = "SELECT ch_type, ch_gid from ".$TBLPREFIX."changes where ch_cid = '".$cid."' AND ch_gedfile = '".$gedfile."'";
	$res = dbquery($sql);
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	$unlock_changes = array("raw_edit", "reorder_families", "reorder_children", "delete_source", "delete_indi", "delete_family", "delete_repo");
	if (in_array($row["ch_type"], $unlock_changes)) {
		$sql = "select ch_cid, ch_type from ".$TBLPREFIX."changes where ch_gid = '".$row["ch_gid"]."' and ch_gedfile = '".$gedfile."' order by ch_cid ASC";
		$res = dbquery($sql);
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$sqlcid = "UPDATE ".$TBLPREFIX."changes SET ch_delete = '0' WHERE ch_cid = '".$row["ch_cid"]."'";
			$rescid = dbquery($sqlcid);
		}
	}
	
	if ($all) {
		$sql = "DELETE from ".$TBLPREFIX."changes where ch_gedfile = '".$gedfile."'";
		if ($res = dbquery($sql)) {
			WriteToLog("Rejected all changes for $gedfile "." ->" . $gm_username ."<-", "I", "G", get_gedcom_from_id($gedfile));
			return true;
		}
		else return false;
	}
	else {
		$sql = "DELETE from ".$TBLPREFIX."changes where ch_cid = '".$cid."' AND ch_gedfile = '".$gedfile."'";
		if ($res = dbquery($sql)) {
			WriteToLog("Rejected change $cid - $gedfile "." ->" . $gm_username ."<-", "I", "G", get_gedcom_from_id($gedfile));
			return true;
		}
		else return false;
	}
}

/**
 * update a record in the database
 * @param string $indirec
 */
function update_record($indirec, $delete=false) {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $GEDCOMS, $FILE;
	
	if (empty($FILE)) $FILE = $GEDCOM;
	
	$tt = preg_match("/0 @(.+)@ (.+)/", $indirec, $match);
	if ($tt>0) {
		$gid = trim($match[1]);
		$type = trim($match[2]);
	}
	else {
		print "ERROR: Invalid gedcom record.";
		return false;
	}

	$sql = "SELECT pl_p_id FROM ".$TBLPREFIX."placelinks WHERE pl_gid='".$DBCONN->escapeSimple($gid)."' AND pl_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	$placeids = array();
	while($row = $res->fetchRow()) {
		$placeids[] = $row[0];
	}
	$sql = "DELETE FROM ".$TBLPREFIX."placelinks WHERE pl_gid='".$DBCONN->escapeSimple($gid)."' AND pl_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);
	$sql = "DELETE FROM ".$TBLPREFIX."dates WHERE d_gid='".$DBCONN->escapeSimple($gid)."' AND d_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
	$res = dbquery($sql);

	//-- delete any unlinked places
	foreach($placeids as $indexval => $p_id) {
		$sql = "SELECT count(pl_p_id) FROM ".$TBLPREFIX."placelinks WHERE pl_p_id=$p_id AND pl_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
		$row = $res->fetchRow();
		if ($row[0]==0) {
			$sql = "DELETE FROM ".$TBLPREFIX."places WHERE p_id=$p_id AND p_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
			$res = dbquery($sql);
		}
	}

	if ($type=="INDI") {
		$sql = "DELETE FROM ".$TBLPREFIX."individuals WHERE i_id LIKE '".$DBCONN->escapeSimple($gid)."' AND i_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
		$sql = "DELETE FROM ".$TBLPREFIX."names WHERE n_gid LIKE '".$DBCONN->escapeSimple($gid)."' AND n_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
	}
	else if ($type=="FAM") {
		$sql = "DELETE FROM ".$TBLPREFIX."families WHERE f_id LIKE '".$DBCONN->escapeSimple($gid)."' AND f_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
	}
	else if ($type=="SOUR") {
		$sql = "DELETE FROM ".$TBLPREFIX."sources WHERE s_id LIKE '".$DBCONN->escapeSimple($gid)."' AND s_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
	}
	else if ($type == "OBJE") {
		$sql = "DELETE FROM ".$TBLPREFIX."media WHERE m_media LIKE '".$DBCONN->escapeSimple($gid)."' AND m_gedfile='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
	}
	else {
		$sql = "DELETE FROM ".$TBLPREFIX."other WHERE o_id LIKE '".$DBCONN->escapeSimple($gid)."' AND o_file='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";
		$res = dbquery($sql);
	}
	if (!$delete) {
		import_record($indirec, true);
	}
}

/**
 * get the top surnames
 * @param int $num	how many surnames to return
 * @return array
 */
function get_top_surnames($num) {
	global $TBLPREFIX, $GEDCOM, $DBCONN, $GEDCOMS, $COMMON_NAMES_REMOVE;

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
	$sql = "(SELECT COUNT(i_surname) as count, i_surname from ".$TBLPREFIX."individuals WHERE i_file='".$GEDCOMS[$GEDCOM]["id"]."' AND i_surname<>'@N.N.'".$delstri." GROUP BY i_surname) UNION ALL (SELECT COUNT(n_surname) as count, n_surname FROM ".$TBLPREFIX."names WHERE n_file='".$GEDCOMS[$GEDCOM]["id"]."' AND n_type!='C' AND n_surname<>'@N.N.'".$delstrn." GROUP BY n_surname) ORDER BY count DESC LIMIT ".$num;
	$res = dbquery($sql);
	if (!DB::isError($res)) {
		while($row = $res->fetchRow()) {
			if (isset($surnames[str2upper($row[1])]["match"])) $surnames[str2upper($row[1])]["match"] += $row[0];
			else {
				$surnames[str2upper($row[1])]["name"] = $row[1];
				$surnames[str2upper($row[1])]["match"] = $row[0];
			}
		}
		$res->free();
	}
	return $surnames;
}

/**
 * get next unique id for the given table
 * @param string $table 	the name of the table
 * @param string $field		the field to get the next number for
 * @return int the new id
 */
function get_next_id($table, $field) {
	global $TBLPREFIX;

	$newid = 0;
	$sql = "SELECT MAX($field) FROM ".$TBLPREFIX.$table;
	$res = dbquery($sql);
	if (!DB::isError($res)) {
		$row = $res->fetchRow();
		$res->free();
		$newid = $row[0];
	}
	$newid++;
	return $newid;
}

/**
 * Retrieve the array of faqs from the DB table blocks
 * @param int $id		The FAQ ID to retrieven
 * @return array $faqs	The array containing the FAQ items
 */
function get_faq_data($id='') {
	global $TBLPREFIX, $GEDCOM;
	
	$faqs = array();
	// Read the faq data from the DB
	$sql = "SELECT b_id, b_location, b_order, b_config FROM ".$TBLPREFIX."blocks WHERE b_username='$GEDCOM' AND (b_location='header' OR b_location = 'body')";
	if ($id != '') $sql .= "AND b_order='".$id."'";
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$faqs[$row["b_order"]][$row["b_location"]]["text"] = unserialize($row["b_config"]);
		$faqs[$row["b_order"]][$row["b_location"]]["pid"] = $row["b_id"];
	}
	ksort($faqs);
	return $faqs;
}

function delete_fact($linenum, $pid, $gedrec) {
	global $record, $linefix, $gm_lang, $TBLPREFIX, $DBCONN;
	if (!empty($linenum)) {
		if ($linenum==0) {
			if (delete_gedrec($pid)) print $gm_lang["gedrec_deleted"];
		}
		else {
			$gedlines = preg_split("/[\r\n]+/", $gedrec);
			// NOTE: The array_pop is used to kick off the last empty element on the array
			// NOTE: To prevent empty lines in the GEDCOM
			// DEBUG: Records without line breaks are imported as 1 big string
			if ($linefix > 0) array_pop($gedlines);
			$newged = "";
			// NOTE: Add all lines that are before the fact to be deleted
			for($i=0; $i<$linenum; $i++) {
				$newged .= trim($gedlines[$i])."\r\n";
			}
			if (isset($gedlines[$linenum])) {
				$fields = preg_split("/\s/", $gedlines[$linenum]);
				$glevel = $fields[0];
				$ctlines = count($gedlines);
				$i++;
				if ($i<$ctlines) {
					// Remove the fact
					while((isset($gedlines[$i]))&&($gedlines[$i]{0}>$glevel)) $i++;
					// Add the remaining lines
					while($i<$ctlines) {
						$newged .= trim($gedlines[$i])."\r\n";
						$i++;
					}
				}
			}
			if ($newged != "")  return $newged;
		}
	}
	$GEDCOM = $oldged;
}

/**
 * check if the log database tables exist
 * 
 * If the tables don't exist then create them
 * If the tables do exist check if they need to be upgraded 
 * to the latest version of the database schema.
 * @author	Genmod Development Team
 * @return 	boolean	True is table exists and is checked or updated
 */
function checkLogTableExists() {
	global $TBLPREFIX, $DBCONN, $CHECKED_LOGTABLES;

	//-- make sure we only run this function once
	if (!empty($CHECKED_LOGTABLES) && $CHECKED_LOGTABLES==true) return true;
	$CHECKED_LOGTABLES = true;
	
	$has_table = false;
	$has_type = false;
	$has_category = false;
	$has_timestamp = false;
	$has_ip = false;
	$has_user = false;
	$has_text = false;
	$has_ged = false;

	check_DB();
	$data = $DBCONN->getListOf('tables');
	if (count($data)>0) {
		foreach($data as $indexval => $table) {
			if ($table==$TBLPREFIX."log") {
				$has_table = true;
				$info = $DBCONN->tableInfo($TBLPREFIX."log");
				if (DB::isError($info)) {
					print "<span class=\"error\"><b>ERROR:".$info->getCode()." ".$info->getMessage()." <br />SQL:</b>".$info->getUserInfo()."</span><br /><br />\n";
					exit;
				}
				foreach($info as $indexval => $field) {
					if ($field["name"]=="l_type") $has_type = true;
					if ($field["name"]=="l_category") $has_category = true;
					if ($field["name"]=="l_timestamp") $has_timestamp = true;
					if ($field["name"]=="l_ip") $has_ip = true;
					if ($field["name"]=="l_user") $has_user = true;
					if ($field["name"]=="l_text") $has_text = true;
					if ($field["name"]=="l_gedcom") $has_ged = true;
				}
				if (!$has_type) {
					$sql = "ALTER TABLE ".$TBLPREFIX."log ADD COLUMN (l_type VARCHAR(2))";
					$pres = dbquery($sql);
					$sql = "CREATE INDEX type_letter ON ".$TBLPREFIX."log (l_type)";
					$res = dbquery($sql);
				}
				if (!$has_category) {
					$sql = "ALTER TABLE ".$TBLPREFIX."log ADD COLUMN (l_category VARCHAR(2))";
					$pres = dbquery($sql);
					$sql = "CREATE INDEX category_letter ON ".$TBLPREFIX."log (l_category)";
					$res = dbquery($sql);
				}
				if (!$has_timestamp) {
					$sql = "ALTER TABLE ".$TBLPREFIX."log ADD COLUMN (l_timestamp VARCHAR(15))";
					$pres = dbquery($sql);
					$sql = "CREATE INDEX time_order ON ".$TBLPREFIX."log (l_timestamp)";
					$res = dbquery($sql);
				}
				if (!$has_ip) {
					$sql = "ALTER TABLE ".$TBLPREFIX."log ADD COLUMN (l_ip VARCHAR(15))";
					$pres = dbquery($sql);
				}
				if (!$has_user) {
					$sql = "ALTER TABLE ".$TBLPREFIX."log ADD COLUMN (l_user VARCHAR(30))";
					$pres = dbquery($sql);
				}
				if (!$has_text) {
					$sql = "ALTER TABLE ".$TBLPREFIX."log ADD COLUMN (l_text TEXT)";
					$pres = dbquery($sql);
				}
				if (!$has_ged) {
					$sql = "ALTER TABLE ".$TBLPREFIX."log ADD COLUMN (l_gedcom VARCHAR(30))";
					$pres = dbquery($sql);
				}
			}
		}
	}
	if (!$has_table) {
		$sql = "CREATE TABLE ".$TBLPREFIX."log (l_type VARCHAR(2), l_category VARCHAR(2), l_timestamp VARCHAR(15), l_ip VARCHAR(15), l_user VARCHAR(30), l_text TEXT, l_gedcom VARCHAR(30))";
		$res = dbquery($sql);
		$sql = "CREATE INDEX type_letter ON ".$TBLPREFIX."log (l_type)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX category_letter ON ".$TBLPREFIX."log (l_category)";
		$res = dbquery($sql);
		$sql = "CREATE INDEX time_order ON ".$TBLPREFIX."log (l_timestamp)";
		$res = dbquery($sql);
	}
	return true;
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
 * @param		string	$type		Type of record:
 *									I = Information
 *									W = Warning
 *									E = Error
 * @param		string	$cat		 Category of log records:
 *									S = System Log
 *									G = Gedcom Log
 *									F = Search Log
 * @param		string	$ged		Used with Gedcom Log and Search Log
 *									Gedcom the Log record applies to
 */
function WriteToLog($LogString, $type="I", $cat="S", $ged="") {
	global $TBLPREFIX, $GEDCOM, $GEDCOMS, $gm_username;

	checkLogTableExists();
	$user = $gm_username;

	// -- Remove the " from the logstring, as this disturbs the export
	$LogString = str_replace("\"", "'", $LogString);
	
	if ($cat == "S") $sql = "INSERT INTO ".$TBLPREFIX."log VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($user)."', '".addslashes($LogString)."', '')";
	if ($cat == "G") $sql = "INSERT INTO ".$TBLPREFIX."log VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($user)."', '".addslashes($LogString)."', '".$ged."')";
	if ($cat == "F") {
		if (!isset($ged)) return;
		if (count($ged) == 0) return;
		foreach($ged as $indexval => $value) {
			$sql = "INSERT INTO ".$TBLPREFIX."log VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".addslashes($user)."', '".addslashes($LogString)."', '".$value."')";
			$res = dbquery($sql);
		}
		return;
	}
	$res = dbquery($sql);
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
	global $TBLPREFIX, $TOTAL_QUERIES;
	
	if (!$count) {
		$sql = "SELECT * FROM ".$TBLPREFIX."log WHERE l_category='".$cat."'";
		if (!empty($type)) $sql .= " AND l_type='".$type."'";
		if (!empty($ged)) $sql .= " AND l_gedcom='".$ged."'";
		if ($last == false) $sql .= " ORDER BY l_timestamp DESC";
		else $sql .= " ORDER BY l_timestamp ASC";
		if ($max != "0") $sql .= " LIMIT ".$max;
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		$loglines = array();
		if ($res) {
			while($log_row = mysql_fetch_assoc($res)){
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
		mysql_free_result($res);
		return $loglines;
	}
	else {
		$sql = "SELECT COUNT(l_type) FROM ".$TBLPREFIX."log WHERE l_category='".$cat."'";
		if (!empty($type)) $sql .= " AND l_type='".$type."'";
		if (!empty($ged)) $sql .= " AND l_gedcom='".$ged."'";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		if ($res) {
			$number = mysql_fetch_row($res);
			return $number[0];
		}
	}
}
/**
 * Check the changes tabel if a change is present
 *
 * The function takes the record id and checks in 
 * the changes table if it is present. If it is present,
 * it will either return the gedcom record or if the 
 * $status is requested, it will return true or false 
 * is returned depending if change is present in database.
 *
 * @author	Genmod Development Team
 * @param		string	$gid		The record ID
 * @param		boolean	$status	If set to true, true or false is returned depending if change is present in database
 * @param		boolean	$whichfiles	If set to true, array of GEDCOMS with changes is returned
 * @return 	boolean|string	boolean if status is requested, string if the gedcom record needs to be returned
 * @return	array	if $whichfiles is requested, array of GEDCOMS with changes ir returned
 */

function change_present($gid="",$status=false, $whichfiles=false, $fact="") {
	global $GEDCOM, $GEDCOMS, $TBLPREFIX, $changes, $gm_lang, $TOTAL_QUERIES;
	
	// NOTE: If the file does not have an ID, go back
	if (!isset($GEDCOMS[$GEDCOM]["id"])) return false;
	
	
	if ($gid == "" && $status && $whichfiles) $sql = "SELECT DISTINCT ch_gedfile FROM ".$TBLPREFIX."changes";
	else if ($gid == "") {
		$sql = "SELECT ch_old, ch_new FROM ".$TBLPREFIX."changes WHERE ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."'";
		$status = true;
	}
	else if ($gid != "" && $status && $fact != "") {
		$sql = "SELECT ch_new FROM ".$TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_fact = '".$fact."' AND ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."'";
	}
	else $sql = "SELECT ch_old, ch_new FROM ".$TBLPREFIX."changes WHERE ch_gid = '".$gid."' AND ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."'";
	// NOTE: Execute the SQL query
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	if (!$res) return false;	
	
	// NOTE: Return gedcoms which have changes
	if ($gid == "" && $status && $whichfiles) {
		$gedfiles = array();
		while ($row = mysql_fetch_assoc($res)) {
			$gedfiles[$row["ch_gedfile"]] = get_gedcom_from_id($row["ch_gedfile"]);
		}
		return $gedfiles;
	}
	
	// NOTE: Retun status
	if ($status) {
		$row = mysql_fetch_row($res);
		if (!empty($row)) return true;
		else return false;
	}
	
	// NOTE: Construct the changed gedcom record
	$oldged = find_gedcom_record($gid);
	while ($row = mysql_fetch_assoc($res)) {
		// NOTE: If old is empty, just add the new data makes sure it is not new record
		if (empty($row["ch_old"]) && !empty($row["ch_new"]) && preg_match("/0\s@(.*)@/", $row["ch_new"]) == 0) {
			$oldged .= "\r\n".$row["ch_new"];
		}
		// NOTE: If the old is empty and the new is a new record make sure we just store the new record
		else if (empty($row["ch_old"]) && preg_match("/0\s@(.*)@/", $row["ch_new"]) > 0) {
			$oldged = $row["ch_new"];
		}
		// NOTE: if both old and new are empty, the record needs to be deleted
		else if (empty($row["ch_old"]) && empty($row["ch_new"])) {
			$oldged = $gm_lang["record_to_be_deleted"];
		}
		// NOTE: If new is empty or filled, the old needs to be replaced
		else $oldged = str_replace($row["ch_old"], $row["ch_new"], $oldged);
	}
	return $oldged;
}

function retrieve_changed_fact($gid, $fact) {
	global $GEDCOMS, $GEDCOM, $TBLPREFIX, $TOTAL_QUERIES;
	
	$sql = "SELECT ch_new FROM ".$TBLPREFIX."changes where ch_gid = '".$gid."' AND ch_fact = '".$fact."' AND ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."'";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	$row = mysql_fetch_row($res);
	return $row[0];
}

/**
 * Store the GEDCOMS array in the database
 *
 * The function takes the GEDCOMS array and stores all
 * content in the database, including DEFAULT_GEDCOM.
 *
 * @author	Genmod Development Team
 */
function store_gedcoms() {
	global $GEDCOMS, $gm_lang, $INDEX_DIRECTORY, $DEFAULT_GEDCOM, $COMMON_NAMES_THRESHOLD, $GEDCOM, $CONFIGURED, $TBLPREFIX, $DBCONN, $TOTAL_QUERIES;

	if (!$CONFIGURED) return false;
	uasort($GEDCOMS, "gedcomsort");
	$maxid = 0;
	foreach ($GEDCOMS as $name => $details) {
		if (isset($details["id"]) && $details["id"] > $maxid) $maxid = $details["id"];
	}
	// -- For now, we update the gedcoms table by rewriting it
	$sql = "DELETE FROM ".$TBLPREFIX."gedcoms";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	
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
				$surnames = get_common_surnames($COMMON_NAMES_THRESHOLD);
				foreach($surnames as $indexval => $surname) {
					$GED["commonsurnames"] .= $surname["name"].", ";
				}
			}
			else $GED["commonsurnames"]="";
		}
		$GEDCOMS[$GED["gedcom"]]["commonsurnames"] = $GED["commonsurnames"];
		$GED["commonsurnames"] = addslashes($GED["commonsurnames"]);
		if ($GED["gedcom"] == $DEFAULT_GEDCOM) $is_default = "Y";
		else $is_default = "N";
		$sql = "INSERT INTO ".$TBLPREFIX."gedcoms VALUES('".$DBCONN->escapeSimple($GED["gedcom"])."','".$DBCONN->escapeSimple($GED["config"])."','".$DBCONN->escapeSimple($GED["privacy"])."','".$DBCONN->escapeSimple($GED["title"])."','".$DBCONN->escapeSimple($GED["path"])."','".$DBCONN->escapeSimple($GED["id"])."','".$DBCONN->escapeSimple($GED["commonsurnames"])."','".$DBCONN->escapeSimple($is_default)."')";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
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
	global $GEDCOMS, $DEFAULT_GEDCOM, $TBLPREFIX, $INDEX_DIRECTORY, $TOTAL_QUERIES;
	$GEDCOMS=array();
	$DEFAULT_GEDCOM = "";
	if (check_db()) {
		$sql = "SELECT * FROM ".$TBLPREFIX."gedcoms ORDER BY g_id";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		if ($res) {
			$ct = mysql_num_rows($res);
			$i = "0";
			if ($ct) {
				while($row = mysql_fetch_assoc($res)){
					$g = array();
//					$row = db_cleanup($row);
					$g["gedcom"] = $row["g_gedcom"];
					$g["config"] = str_replace("\${INDEX_DIRECTORY}", $INDEX_DIRECTORY, $row["g_config"]);
					$g["privacy"] = str_replace("\${INDEX_DIRECTORY}", $INDEX_DIRECTORY, $row["g_privacy"]);
					$g["title"] = $row["g_title"];
					$g["path"] = str_replace("\${INDEX_DIRECTORY}", $INDEX_DIRECTORY, $row["g_path"]);
					$g["id"] = $row["g_id"];
					$g["commonsurnames"] = $row["g_commonsurnames"];
					if ($row["g_isdefault"] == "Y") $DEFAULT_GEDCOM = $row["g_gedcom"];
					$GEDCOMS[$row["g_gedcom"]] = $g;
					if ($i == "0") $DEFAULT_GEDCOM = $row["g_gedcom"];
					$i++;
				}
				mysql_free_result($res);
			}
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
function UpdateCounter($id) {
	global $TBLPREFIX;

	$sql = "SELECT c_number FROM ".$TBLPREFIX."counters WHERE (c_id='".$id."')"; 
	$res = dbquery($sql);
	$ct = $res->numRows();
	if ($ct == "0") {
		$sql = "INSERT INTO ".$TBLPREFIX."counters VALUES ('".$id."', '1')";
	  	$res = dbquery($sql);
		return 1;
	}
	else {
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			$hits = $row["c_number"];
		}
		$res->free();
		$sql = "UPDATE ".$TBLPREFIX."counters SET c_number=c_number+1 WHERE c_id='".$id."'"; 
  		$res = dbquery($sql);
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
	global $TBLPREFIX, $TOTAL_QUERIES, $GEDCOM;
	
	// -- First see if the settings already exist
	$sql = "SELECT gc_gedcom FROM ".$TBLPREFIX."gedconf WHERE (gc_gedcom='".$settings["gedcom"]."')";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	$ct = mysql_num_rows($res);
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
  		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
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
  		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
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
	global $TBLPREFIX, $TOTAL_QUERIES, $GEDCOM;

	if (!check_db()) return false;
	$sql = "SELECT * FROM ".$TBLPREFIX."gedconf WHERE (gc_gedcom='".$gedcom."')";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	if ($res) {
		$ct = mysql_num_rows($res);
		if ($ct == "0") return false;
		while($row = mysql_fetch_assoc($res)){
			foreach ($row as $key => $value) {
				$var = strtoupper(substr($key, 3));
				global $$var;
				$$var = $value;
			}
		}
		mysql_free_result($res);
		//-- This is copied from the config_gedcom.php
		if ($gedcom == $GEDCOM) @set_time_limit($TIME_LIMIT);
		return true;
	}
}
		
/** Delete Gedcom configuration settings from the database
 *
 * The function deletes GEDCOM configuration settings from the database.
 *
 * @author	Genmod Development Team
 * @param		string	$gedcom		GEDCOM name for which the values are to be deleted.
**/
function DeleteGedcomConfig($gedcom) {
	global $TBLPREFIX, $TOTAL_QUERIES;

	if (!check_db()) return false;
	$sql = "DELETE FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$gedcom."'";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	return;
}		

/** Store the Gedcom privacy settings in the database
 *
 * The function stores the GEDCOM privacysettings in the database.
 *
 * @author	Genmod Development Team
 * @param		array	$settings		Array with GEDCOM privacy settings
**/
function StorePrivacy($settings) {
	global $TBLPREFIX, $TOTAL_QUERIES;
	// -- First see if the settings already exist
	$sql = "SELECT p_gedcom FROM ".$TBLPREFIX."privacy WHERE (p_gedcom='".$settings["gedcom"]."')";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	$ct = mysql_num_rows($res);
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
			$col .= "p_".$key;
			$i++;
			switch($key) {
				case "person_facts":
					$val .= "'".serialize($value)."'";
					break;
				case "global_facts":
					$val .= "'".serialize($value)."'";
					break;
				case "user_privacy":
					$val .= "'".serialize($value)."'";
					break;
				case "person_privacy":
					$val .= "'".serialize($value)."'";
					break;
				default:
					$val .= "'".$value."'";
			}				
		}
		$col .= ")";
		$val .= ")";
		$sql = "INSERT INTO ".$TBLPREFIX."privacy ".$col." VALUES ".$val;
  		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
	}
	else {
		$i = "0";
		$str = "";
		foreach ($settings as $key => $value) {
			if ($i > 0) $str .= ", ";
			$i++;
			$str .= "p_".$key."=";
			switch($key) {
				case "person_facts":
					$str .= "'".serialize($value)."'";
					break;
				case "global_facts":
					$str .= "'".serialize($value)."'";
					break;
				case "user_privacy":
					$str .= "'".serialize($value)."'";
					break;
				case "person_privacy":
					$str .= "'".serialize($value)."'";
					break;
				default:
					$str .= "'".$value."'";
			}				
		}
		$sql = "UPDATE ".$TBLPREFIX."privacy SET ".$str." WHERE p_gedcom='".$settings["gedcom"]."'";
  		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
	}
	return;
}		

/** 
 * Read the Gedcom privacy settings from the database
 *
 * The function reads the GEDCOM privacy settings from the database.
 *
 * @author	Genmod Development Team
 * @param		string	$gedcom		Name of GEDCOM for which the settings should be retrieved
 * @return	boolean				False: failed, true: success
 **/
function ReadPrivacy($gedcom) {
	global $TBLPREFIX, $TOTAL_QUERIES;

	if (!check_db()) return false;
	$sql = "SELECT * FROM ".$TBLPREFIX."privacy WHERE (p_gedcom='".$gedcom."')";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	if ($res) {
		$ct = mysql_num_rows($res);
		if ($ct == "0") return false;
		while($row = mysql_fetch_assoc($res)){
			foreach ($row as $key => $value) {
				$var = substr($key, 2);
				if (($var == "global_facts") || ($var == "user_privacy")) {
					global $$var;
					$$var = unserialize($value);
					$temp = array();
					foreach ($$var as $key1 => $value1) {
						foreach ($value1 as $key2 => $value2) {
							$temp[$key1][$key2] = $$value2;
						}
					}
					$$var = $temp;
				}
				else {
					if ($var == "person_privacy") {
						global $$var;
						$$var = unserialize($value);
						$temp = array();
						foreach ($$var as $key1 => $value1) {
							$temp[$key1] = $$value1;
						}
						$$var = $temp;
					}
					else {
						if ($var == "person_facts") {
							global $$var;
							$$var = unserialize($value);
							$temp = array();
							foreach ($$var as $key1 => $value1) {
								foreach ($value1 as $key2 => $value2) {
									foreach($value2 as $key3 => $value3) {
										$temp[$key1][$key2][$key3] = $$value3;
									}
								}
							}
							$$var = $temp;
						}
						else {	
							$var = strtoupper($var);
							global $$var;
							$a = substr($value, 0, 5);
							if ($a == "PRIV_") $$var = $$value;
							else $$var = $value;
						}
					}
				}
			}
		}
		mysql_free_result($res);
		return true;
	}
}		

/** Delete the Gedcom privacy settings from the database
 *
 * The function deletes the GEDCOM privacysettings from the database.
 *
 * @author	Genmod Development Team
 * @param		string	$gedcom		Name of GEDCOM for which the settings should be deleted
 * @return		boolean				False: failed, true: success
 **/
function DeletePrivacy($gedcom) {
	global $TBLPREFIX, $TOTAL_QUERIES;

	if (!check_db()) return false;
	$sql = "DELETE FROM ".$TBLPREFIX."privacy WHERE p_gedcom='".$gedcom."'";
	$res = mysql_query($sql);
	$ct = mysql_num_rows($res);
	$TOTAL_QUERIES++;
	if ($ct == "0") return false;
	mysql_free_result($res);
	return true;
}

/**
 * Check if the record is locked for editing
 */
function record_locked($gid) {
	global $GEDCOMS, $GEDCOM, $TBLPREFIX;
	$sql = "SELECT ch_cid FROM ".$TBLPREFIX."changes WHERE ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."' AND ch_gid = '".$gid."' AND ch_new = '' AND ch_old = ''";
	$res = dbquery($sql);
	if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) return true;
	else return false;
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
 * @param		string			$join		String yes/no to dump multiple tables in one file or create multiple files.
 * @param		string			$newname	Only valid if one file or multiple joined files: filename to use for output.
 * @return		array			$fn			Array with names of created files
 *
**/
function ExportTable($table, $join="no", $newname="") {
	global $TBLPREFIX, $INDEX_DIRECTORY, $TOTAL_QUERIES;

	$tables = array();
	$fn = array();
	if (!is_array($table)) $tables[] = $table;
	else $tables = $table;
	$outstr = "";
	foreach($tables as $tabkey=>$tabname) {
		$outstr .= "DELETE FROM ".$TBLPREFIX.$tabname."\r\n";
		$sql = "SELECT * FROM ".$TBLPREFIX.$tabname;
		$res = mysql_query($sql);
		$ct = mysql_num_rows($res);
		$TOTAL_QUERIES++;
		if ($ct != "0") {
			while ($row = mysql_fetch_assoc($res)) {
				$line = "INSERT INTO ".$TBLPREFIX.$tabname." VALUES (";
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
	global $TBLPREFIX, $INDEX_DIRECTORY, $TOTAL_QUERIES;

	if (file_exists($INDEX_DIRECTORY.$fn)) $sqlines = file($INDEX_DIRECTORY.$fn);
	else return false;

	$sqline = "";
	foreach($sqlines as $key=>$sql) {
		$sqline .= $sql;
		if ((substr(ltrim($sqline), 0, 6) == "INSERT" && substr(rtrim($sqline), -2) == "')") || substr(ltrim($sqline), 0, 6) == "DELETE") {
			$res = mysql_query($sqline);
			$error = mysql_error();
			if (!empty($error)) return $error;
			$TOTAL_QUERIES++;
			$sqline = "";
		}
	}
	return "";
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
	global $GEDCOM, $GEDCOMS, $TBLPREFIX, $changes, $gm_lang, $TOTAL_QUERIES;
	// NOTE: If the file does not have an ID, go back
	if (!isset($GEDCOMS[$GEDCOM]["id"])) return false;
	
	$whereclause = "";
	if ($thisged) $whereclause .= "ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."'";
	if (!empty($gid)) {
		if (!empty($whereclause)) $whereclause .= " AND ";
		$whereclause .= "ch_gid = '".$gid."'";
	}
	if (!empty($fact)) {
		if (!empty($whereclause)) $whereclause .= " AND ";
		$whereclause .= "ch_fact = '".$fact."'";
	}
	
	if ($status) $selectclause = "SELECT COUNT(ch_id) ";
	else {
		if ($data == "gedcoms") $selectclause = "SELECT ch_gedfile ";
		else {
			$selectclause = "SELECT ch_gid, ch_type, ch_fact, ch_gedfile, ch_old, ch_new ";
			$whereclause .= " ORDER BY ch_gid, ch_cid";
		}
	}
	
	$sql = $selectclause."FROM ".$TBLPREFIX."changes";
	if (!empty($whereclause)) $sql .= " WHERE ".$whereclause;
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	if (!$res) return false;	
	
	if($status) {
		$row = mysql_fetch_row($res);
		return $row;
	}
	else {
		if ($data == "gedcoms") {
			// NOTE: Return gedcoms which have changes
			$gedfiles = array();
			while ($row = mysql_fetch_assoc($res)) {
				$gedfiles[$row["ch_gedfile"]] = get_gedcom_from_id($row["ch_gedfile"]);
			}
			return $gedfiles;
		}
		else {
			// NOTE: Construct the changed gedcom record
			$gedlines = array();
			while ($row = mysql_fetch_assoc($res)) {
				$gedname = get_gedcom_from_id($row["ch_gedfile"]);
				$chgid = $row["ch_gid"];
				$gedlines[$gedname][$chgid] = find_gedcom_record($chgid);
				// NOTE: If old is empty, just add the new data makes sure it is not new record
				if (empty($row["ch_old"]) && !empty($row["ch_new"]) && preg_match("/0\s@(.*)@/", $row["ch_new"]) == 0) {
					$gedlines[$gedname][$chgid] .= "\r\n".$row["ch_new"];
				}
				// NOTE: If the old is empty and the new is a new record make sure we just store the new record
				else if (empty($row["ch_old"]) && preg_match("/0\s@(.*)@/", $row["ch_new"]) > 0) {
					$gedlines[$gedname][$chgid] = $row["ch_new"];
				}
				// NOTE: if both old and new are empty, the record needs to be deleted
				else if (empty($row["ch_old"]) && empty($row["ch_new"])) {
					$gedlines[$gedname][$chgid] = $gm_lang["record_to_be_deleted"];
				}
				// NOTE: If new is empty or filled, the old needs to be replaced
				else $gedlines[$gedname][$chgid] = str_replace($row["ch_old"], $row["ch_new"], $gedlines[$gedname][$chgid]);
			}
			return $gedlines;
		}
	}
}

function GetCachedEvents($action, $daysprint, $filter, $onlyBDM="no", $skipfacts) {
	global $gm_lang, $month, $year, $day, $monthtonum, $monthstart;
	global $GEDCOM, $DEBUG, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $TOTAL_QUERIES;
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
	$res = mysql_query($sql);
	if ($res) {
		while ($row = mysql_fetch_assoc($res)) {
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
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
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
		$indilist = search_indis_daterange($dstart, $mstart, $dend, $mend, $filter, "no", $skipfacts);
		// Search database for raw Family data if no cache was found
		$famlist = array();
		$famlist = search_fams_daterange($dstart, $mstart, $dend, $mend, "no", $skipfacts);

		// Apply filter criteria and perform other transformations on the raw data
		foreach($indilist as $gid=>$indi) {
			$facts = get_all_subrecords($indi["gedcom"], $skipfacts, false, false, false);
			foreach($facts as $key=>$factrec) {
				$date = 0; //--- MA @@@
				$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
				if ($hct>0) {
					if ($USE_RTL_FUNCTIONS) {
						$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
						if ($dct>0) {
							$hebrew_date = parse_date(trim($match[1]));
							$date = jewishGedcomDateToCurrentGregorian($hebrew_date);
						}
					}
				}
				else {
					$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
					if ($dct>0) $date = parse_date(trim($match[1]));
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
						$found_facts[] = array($gid, $factrec, "INDI", $datestamp, check_NN(get_sortable_name($gid)), $gender, $fact[1], $indi["isdead"]);
					}
				}
			}
		}
		foreach($famlist as $gid=>$fam) {
			$facts = get_all_subrecords($fam["gedcom"], $skipfacts, false, false, false);
			foreach($facts as $key=>$factrec) {
				$date = 0; //--- MA @@@
				$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $factrec, $match);
				if ($hct>0) {
					if ($USE_RTL_FUNCTIONS) {
						$dct = preg_match("/2 DATE (.+)/", $factrec, $match);
						$hebrew_date = parse_date(trim($match[1]));
						$date = jewishGedcomDateToCurrentGregorian($hebrew_date);
					}
				}
				else {
					$ct = preg_match("/2 DATE (.+)/", $factrec, $match);
					if ($ct>0) $date = parse_date(trim($match[1]));
				}
				if (!empty($date[0]["mon"]) && !empty($date[0]["day"])) {
					if ($date[0]["mon"]< $mstart) $y = $year+1;
					else $y = $year;
					$datestamp = mktime(1,0,0,$date[0]["mon"],$date[0]["day"],$y);
					if (($datestamp >= $monthstart) && ($datestamp<=$monthend)) {
						// Strip useless information:
						//   NOTE, ADDR, OBJE, SOUR, PAGE, DATA, TEXT
						$factrec = preg_replace("/\d\s+(NOTE|ADDR|OBJE|SOUR|PAGE|DATA|TEXT|CONT|CONC|QUAY|CAUS|CEME)\s+(.+)\n/", "", $factrec);
						if (is_dead_id($fam["HUSB"]) && is_dead_id($fam["WIFE"])) $isdead = "1";
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
		uasort($found_facts, "compare_facts");
		reset($found_facts);
		foreach ($found_facts as $key => $factr) {
			$sql = "INSERT INTO ".$TBLPREFIX."eventcache VALUES('','".mysql_real_escape_string($GEDCOM)."', '".$action."', '".$factr[0]."', '".$factr[7]."', '".$factr[6]."', '".mysql_real_escape_string($factr[1])."', '".$factr[2]."', '".$factr[3]."', '".mysql_real_escape_string($factr[4])."', '".$factr[5]."')";
			$res = mysql_query($sql);
			$error = mysql_error();
			if (!empty($error)) print $error."<br />";
			$TOTAL_QUERIES++;
		}
		
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_".$action."='".$monthstart."' WHERE gc_gedcom='".$GEDCOM."'";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
	}
	
	// load the cache from DB

	$monthend = $monthstart + (60*60*24*($daysprint-1));
	$found_facts = array();
	$sql = "SELECT ge_gid, ge_factrec, ge_type, ge_datestamp, ge_name, ge_gender FROM ".$TBLPREFIX."eventcache WHERE ge_cache='".$action."' AND ge_gedcom='".$GEDCOM."' AND ge_datestamp BETWEEN ".$monthstart." AND ".$monthend;
	if ($onlyBDM == "yes") $sql .= " AND ge_fact IN ('BIRT', 'DEAT', 'MARR')";
	if ($filter == "alive") $sql .= " AND ge_isdead=0";
	$sql .= " ORDER BY ge_order";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	if($res) {
		while ($row = mysql_fetch_row($res)) {
			$found_facts[] = $row;
		}
	}
	return $found_facts;
}

function ResetCaches($file="") {
	global $TBLPREFIX, $GEDCOM, $TOTAL_QUERIES;
	
	if ($file == "") $file = $GEDCOM;
	// Reset todays events cache
	$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_today='0', gc_last_upcoming='0', gc_last_stats='0' WHERE gc_gedcom='".$file."'";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
}

function GetCachedStatistics() {
	global $GEDCOM, $TBLPREFIX, $TOTAL_QUERIES, $gm_lang, $GEDCOMS, $monthtonum;
	
	// First see if the cache must be refreshed
	$sql = "SELECT gc_last_stats FROM ".$TBLPREFIX."gedconf WHERE gc_gedcom='".$GEDCOM."'";
	$res = mysql_query($sql);
	$TOTAL_QUERIES++;
	if (mysql_num_rows($res) == 0) $cache_load = false;
	else {
		$row = mysql_fetch_row($res);
		if ($row[0] !='0') {
			$cache_load = true;
		}
		else {
			$sql = "DELETE FROM ".$TBLPREFIX."statscache WHERE gs_gedcom='".$GEDCOM."'";
			$res &= mysql_query($sql);
			$TOTAL_QUERIES++;
			$cache_load = false;
		}
	}
	if (!$cache_load) {
		$stats = array();
		$stats["gs_title"] = "";
		$head = find_gedcom_record("HEAD");
		$ct=preg_match("/1 SOUR (.*)/", $head, $match);
		
		if ($ct>0) {
			$softrec = get_sub_record(1, "1 SOUR", $head);
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
			if (empty($title)) $text = str_replace("#DATE#", get_changed_date($date), $gm_lang["gedcom_created_on"]);
			else $text = str_replace("#DATE#", get_changed_date($date), $gm_lang["gedcom_created_on2"]);
			$stats["gs_title"] .= $text;
		}

		//-- total unique surnames
		$sql = "SELECT COUNT(i_surname) FROM ".$TBLPREFIX."individuals WHERE i_file='".$GEDCOMS[$GEDCOM]["id"]."' GROUP BY i_surname";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		$stats["gs_nr_surnames"] = mysql_num_rows($res);
		mysql_free_result($res);

		$stats["gs_nr_fams"] = get_list_size("famlist");
		$stats["gs_nr_sources"] = get_list_size("sourcelist");
		$stats["gs_nr_other"] = get_list_size("otherlist");

		//-- total events
		$sql = "SELECT COUNT(d_gid) FROM ".$TBLPREFIX."dates WHERE d_file='".$GEDCOMS[$GEDCOM]["id"]."'";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		$row = mysql_fetch_row($res);
		$stats["gs_nr_events"] = $row[0];
		mysql_free_result($res);

		// NOTE: Get earliest birth year
		$sql = "select d_gid, d_year, d_month, FIELD(d_month";
		foreach($monthtonum as $month=>$mon) $sql .= ", '".$month."'";
		$sql .= ") as d_mon, d_day from ".$TBLPREFIX."dates where d_file = '".$GEDCOMS[$GEDCOM]["id"]."' and d_fact = 'BIRT' and d_year != '0' and d_type is null ORDER BY d_year ASC, d_mon ASC, d_day ASC LIMIT 1";
		// print $sql;
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		$row = mysql_fetch_row($res);
		mysql_free_result($res);
		$stats["gs_earliest_birth_year"] = $row[1];
		$stats["gs_earliest_birth_gid"] = $row[0];
		
		// NOTE: Get the latest birth year
		$sql = "select d_gid, d_year, d_month, FIELD(d_month";
		foreach($monthtonum as $month=>$mon) $sql .= ", '".$month."'";
		$sql .= ") as d_mon, d_day from ".$TBLPREFIX."dates where d_file = '".$GEDCOMS[$GEDCOM]["id"]."' and d_fact = 'BIRT' and d_year != '0' and d_type is null ORDER BY d_year DESC, d_mon DESC , d_day DESC LIMIT 1";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		$row = mysql_fetch_row($res);
		mysql_free_result($res);
		$stats["gs_latest_birth_year"] = $row[1];
		$stats["gs_latest_birth_gid"] = $row[0];

		// NOTE: Get the person who lived the longest
		$sql = "select death.d_year-birth.d_year as age, death.d_gid from ".$TBLPREFIX."dates as death, ".$TBLPREFIX."dates as birth where birth.d_gid=death.d_gid AND death.d_file='".$GEDCOMS[$GEDCOM]["id"]."' and birth.d_file=death.d_file AND birth.d_fact='BIRT' and death.d_fact='DEAT' AND birth.d_year>0 and death.d_year>0 and birth.d_type is null and death.d_type is null ORDER BY age DESC limit 1";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		$row = mysql_fetch_row($res);
		mysql_free_result($res);
		$stats["gs_longest_live_years"] = $row[0];
		$stats["gs_longest_live_gid"] = $row[1];
		
		//-- avg age at death
		$sql = "select avg(death.d_year-birth.d_year) as age from ".$TBLPREFIX."dates as death, ".$TBLPREFIX."dates as birth where birth.d_gid=death.d_gid AND death.d_file='".$GEDCOMS[$GEDCOM]["id"]."' and birth.d_file=death.d_file AND birth.d_fact='BIRT' and death.d_fact='DEAT' AND birth.d_year>0 and death.d_year>0 and birth.d_type is null and death.d_type is null";
		$res = dbquery($sql, false);
		if (!DB::isError($res)) {
			$row = $res->fetchRow();
			$stats["gs_avg_age"] = $row[0];
		}
		else $stats["gs_avg_age"] = "";
					
		//-- most children
		$sql = "SELECT f_numchil, f_id FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' ORDER BY f_numchil DESC LIMIT 10";
		$res = dbquery($sql);
		if (!DB::isError($res)) {
			$row = $res->fetchRow();
			$res->free();
			$stats["gs_most_children_nr"] = $row[0];
			$stats["gs_most_children_gid"] = $row[1];
		}
		else {
			$stats["gs_most_children_nr"] = "";
			$stats["gs_most_children_gid"] = "";
		}

		//-- avg number of children
		$sql = "SELECT avg(f_numchil) from ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."'";
		$res = dbquery($sql, false);
		if (!DB::isError($res)) {
			$row = $res->fetchRow();
			$res->free();
			$stats["gs_avg_children"] = $row[0];
		}
		else $stats["gs_avg_children"] = "";
		
		$sql = "INSERT INTO ".$TBLPREFIX."statscache ";
		$sqlf = "(gs_gedcom";
		$sqlv = "('".$GEDCOM."'";
		foreach($stats as $skey => $svalue) {
			$sqlf .= ", ".$skey;
			$sqlv .= ", '".$svalue."'";
		}
		$sqlf .= ")";
		$sqlv .= ")";
		$sql .= $sqlf." VALUES ".$sqlv;
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_stats='1' WHERE gc_gedcom='".$GEDCOM."'";
		$res = mysql_query($sql);
		$error = mysql_error();
		if (!empty($error)) print $error;
		$TOTAL_QUERIES++;
	}
	
	$stats = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."statscache WHERE gs_gedcom='".$GEDCOM."'";
	$res = mysql_query($sql);
	while($row = mysql_fetch_assoc($res)){
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
	global $TBLPREFIX, $TOTAL_COUNT, $GEDCOM, $GEDCOMS;
	
	$indis = array();
	$fams = array();
	// $sql = "SELECT i_id FROM ".$TBLPREFIX."individuals WHERE i_gedcom NOT REGEXP 'FAM?' AND i_file='".$GEDCOMS[$GEDCOM]["id"]."' ORDER BY i_name";
	$sql = "SELECT i_id ";
	$sql .= "FROM ".$TBLPREFIX."individuals ";
	$sql .= "LEFT JOIN ".$TBLPREFIX."individual_spouse ";
	$sql .= "ON ".$TBLPREFIX."individual_spouse.pid=".$TBLPREFIX."individuals.i_id ";
	$sql .= "LEFT JOIN ".$TBLPREFIX."individual_child ";
	$sql .= "ON ".$TBLPREFIX."individual_child.pid=".$TBLPREFIX."individuals.i_id ";
	$sql .= "WHERE ".$TBLPREFIX."individual_spouse.pid IS NULL ";
	$sql .= "AND ".$TBLPREFIX."individual_child.pid IS NULL ";
	$sql .= "AND i_file = '".$GEDCOMS[$GEDCOM]["id"]."' ";
	$sql .= "ORDER BY i_name ";
	
	$res = mysql_query($sql);
	$TOTAL_COUNT++;
	while($row = mysql_fetch_assoc($res)){
		foreach ($row as $key => $value) {
			$indis[] = $value;
		}
	}
	
	// we need families whose members are only linked to this family
	/**
	$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS temp_fama ";
	$sql .= "SELECT f_id AS family, f_husb AS member, 'H' AS type FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' AND f_husb != ''";
	$sql .= " UNION ";
	$sql .= "SELECT f_id AS family, f_wife AS member, 'W' AS type FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' and f_wife != ''";
	$sql .= " UNION ";
	$sql .= "SELECT f_id AS family, SUBSTRING_INDEX( f_chil, ';', 1 ) AS member, 'C' AS type FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' and f_chil != '' AND SUBSTRING_INDEX( f_chil, ';', 1 ) != ''";
	for ($i = 2; $i <= 17; $i++) {
		$sql .= " UNION ";
		$sql .= "SELECT f_id AS family, SUBSTRING_INDEX(SUBSTRING_INDEX( f_chil, ';', $i ) , ';' , -1 ) AS member, 'C' AS type FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' AND f_chil != '' AND SUBSTRING_INDEX(SUBSTRING_INDEX( f_chil, ';', $i ) , ';' , -1 ) != ''";
	}
	$res = mysql_query($sql);
	if (!$res) die('Invalid query: ' . mysql_error());
	
	$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS temp_famb ";
	$sql .= "SELECT f_id AS family, f_husb AS member, 'H' AS type FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' AND f_husb != ''";
	$sql .= " UNION ";
	$sql .= "SELECT f_id AS family, f_wife AS member, 'W' AS type FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' and f_wife != ''";
	$sql .= " UNION ";
	$sql .= "SELECT f_id AS family, SUBSTRING_INDEX( f_chil, ';', 1 ) AS member, 'C' AS type FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' and f_chil != '' AND SUBSTRING_INDEX( f_chil, ';', 1 ) != ''";
	for ($i = 2; $i <= 17; $i++) {
		$sql .= " UNION ";
		$sql .= "SELECT f_id AS family, SUBSTRING_INDEX(SUBSTRING_INDEX( f_chil, ';', $i ) , ';' , -1 ) AS member, 'C' AS type FROM ".$TBLPREFIX."families WHERE f_file='".$GEDCOMS[$GEDCOM]["id"]."' AND f_chil != '' AND SUBSTRING_INDEX(SUBSTRING_INDEX( f_chil, ';', $i ) , ';' , -1 ) != ''";
	}
	print $sql;
	$res = mysql_query($sql);
	if (!$res) die('Invalid query: ' . mysql_error());
	
	$TOTAL_COUNT++;
	
	$sql = "select DISTINCT a.* from temp_fama a where EXISTS (SELECT b.* FROM temp_famb b WHERE b.member=a.member AND b.family != a.family) order by 1,2";
	$sql = "SELECT a.family, a.member FROM temp_fama a WHERE (SELECT b.* FROM temp_famb b WHERE a.member = b.member ";
	$sql .= "AND a.family != b.family) LIMIT 0 , 30";
	$sql = "EXPLAIN SELECT temp_fama.*, temp_famb.* FROM temp_fama, temp_famb WHERE (SELECT b.* FROM temp_famb b WHERE b.member=temp_fama.member AND b.family != temp_fama.family) LIMIT 3";
	$sql = "EXPLAIN SELECT a.*, b.* FROM temp_fama a, temp_famb b WHERE b.member=a.member AND b.family != a.family LIMIT 3";
	$sql = "EXPLAIN SELECT temp_fama.*, temp_famb.* FROM temp_fama, temp_famb WHERE (SELECT b.* FROM temp_famb b WHERE b.member='F1') LIMIT 3";
	$sql = "DESCRIBE gm_families";
	print $sql;
	/**
	SELECT DISTINCT store_type FROM stores
	WHERE EXISTS (SELECT * FROM cities_stores
                WHERE cities_stores.store_type = stores.store_type);
			 
	$res = mysql_query($sql);
	if (!$res) die('Invalid query: ' . mysql_error());
	
	while($row = mysql_fetch_assoc($res)) {
		// $fams[$row["family"]] = $row;
		?><pre><?php
		print_r($row);
		?></pre><?php
	}
	*/
	return $indis;
}
?>