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
 * @version $Id: functions_db.php 29 2022-07-17 13:18:20Z Boudewijn $
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

// This function checks if a record exists. It also considers pending changes.
function CheckExists($pid, $type="") {

	if (empty($pid)) return false;
	$gedrec = "";
	if (in_array($type, array("INDI", "FAM", "SOUR", "REPO", "OBJE", "NOTE"))) {
		$object = ConstructObject($pid, $type, GedcomConfig::$GEDCOMID);
		// $object is either false (doesn't exist or the object
		if (!$object) return false;
		else return true;
	}
	else return false;
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
	
	// Check if a user object exists
	if (is_object($gm_user)) $usr = addslashes($gm_user->username);
	else $usr = "";
	
	if ($chkconn && (!is_object($DBCONN) || (isset($DBCONN->connected) && !$DBCONN->connected))) {
		if ($cat == "S") {
			$emlog = INDEX_DIRECTORY."emergency_syslog.txt";
			$string = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".$usr."', '".addslashes($LogString)."', '', '".$new."')\r\n";
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
		$sql = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."',".time().", '".$_SERVER['REMOTE_ADDR']."', '".$usr."', '".addslashes($LogString)."', '', '".$new."')";
		$res = NewQuery($sql);
		return;
	}
	if ($cat == "G") {
		$sql = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".$usr."', '".addslashes($LogString)."', '".$gedid."', '".$new."')";
		$res = NewQuery($sql);
		return;
	}
	if ($cat == "F") {
		if (!isset($gedid)) return;
		if (count($gedid) == 0) return;
		foreach($gedid as $indexval => $value) {
			$sql = "INSERT INTO ".TBLPREFIX."log (l_type, l_category, l_timestamp, l_ip, l_user, l_text, l_file, l_new) VALUES('".$type."','".$cat."','".time()."', '".$_SERVER['REMOTE_ADDR']."', '".$usr."', '".addslashes($LogString)."', '".$value."', '".$new."')";
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
	global $GEDCOMS, $DEFAULT_GEDCOMID;
	
	$GEDCOMS=array();
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
					$DEFAULT_GEDCOMID = $row["g_file"];
				}
				$GEDCOMS[$row["g_file"]] = $g;
				if ($i == "0") {
					$DEFAULT_GEDCOMID = $row["g_file"];
				}
				$i++;
			}
			$res->FreeResult();
		}
	}
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

function GetLastChangeDate($type, $pid, $gedid, $head=false) {
	
	$object = ConstructObject($pid, $type, $gedid);

	// pid does not exist or whatever, the function returned false
	if ($object == false) return false;
	
	// return the date/time from the CHAN record
	if ($object->lastchanged != "") return $object->lastchanged;
	
	// if not found, take the header record of the gedcom
	if ($head) $object =& Header::GetInstance("HEAD", "", $gedid); 
	// WriteToLog("Retrieved date and time from HEAD record", "I", "S");
	return $object->lastchanged;
}


?>