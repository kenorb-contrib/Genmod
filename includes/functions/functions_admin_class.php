<?php
/**
 * Functions used in admin pages
 *
 * $Id$
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
 * @package Genmod
 * @subpackage Tools
 * @see validategedcom.php
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class AdminFunctions {
	
	/**
	 * Check if a gedcom file is downloadable over the internet
	 *
	 * @author opus27
	 * @param string $gedfile gedcom file
	 * @return mixed 	$url if file is downloadable, false if not
	 */
	public function CheckGedcomDownloadable($gedfile) {
	
		$url = "http://localhost/";
		if (substr($url,-1,1)!="/") $url .= "/";
		$url .= preg_replace("/ /", "%20", $gedfile);
		@ini_set('user_agent','MSIE 4\.0b2;'); // force a HTTP/1.0 request
		@ini_set('default_socket_timeout', '10'); // timeout
		$handle = @fopen ($url, "r");
		if ($handle==false) return false;
		// open successfull : now make sure this is a GEDCOM file
		$txt = fread ($handle, 80);
		fclose($handle);
		if (strpos($txt, " HEAD")==false) return false;
		return $url;
	}
	
	/* This function returns a list of directories
	*
	*/
	public function GetDirList($dirs, $recursive=true) {
		$dirlist = array();
		if (!is_array($dirs)) $dirlist[] = $dirs;
		else $dirlist = $dirs;
		foreach ($dirs as $key=>$dir) {
			$d = @dir($dir);
			if (is_object($d)) {
				while (false !== ($entry = $d->read())) {
					if ($entry != ".." && $entry != ".") {
						$entry = $dir.$entry."/";
						if(is_dir($entry)) {
							if ($recursive) $dirlist = array_merge($dirlist, self::GetDirList(array($entry)));
							else $dirlist[] = $entry;
						}
					}
				}
				$d->close();
			}
		}
		return $dirlist;
	}
	
	// This functions checks if an existing file is physically writeable
	// The standard PHP function only checks for the R/O attribute and doesn't
	// detect authorisation by ACL.
	public function FileIsWriteable($file) {

		$err_write = false;
		$handle = @fopen($file,"r+");
		if ($handle) {
			$i = fclose($handle);
			$err_write = true;
		}
		return($err_write);
	}
	
	public function PrintGedcom($ged, $convert, $remove, $zip, $privatize_export, $privatize_export_level, $gedname, $embedmm) {
		GLOBAL $GEDCOMID, $GM_BASE_DIRECTORY, $gm_username, $gm_user;
		if ($zip == "yes") {
			$gedout = fopen($gedname, "w");
		}
	
		if ($privatize_export == "yes") {
			UserController::CreateExportUser($privatize_export_level);
			if (isset($_SESSION)) {
				$_SESSION["org_user"] = $_SESSION["gm_user"];
				$_SESSION["gm_user"] = "export";
			}
			if (isset($HTTP_SESSION_VARS)) {
				$HTTP_SESSION_VARS["org_user"] = $HTTP_SESSION_VARS["gm_user"];
				$HTTP_SESSION_VARS["gm_user"] = "export";
			}
			$gm_username = UserController::GetUserName();
			$gm_user =& User::GetInstance($gm_username); 
		}
	
		SwitchGedcom($ged);
		$head = "";
		$sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_id='HEAD' AND o_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		if ($res->NumRows() > 0) {
			$row = $res->FetchAssoc();
			$head = $row["o_gedrec"];
		}
		if (!empty($head)) {
			$pos1 = strpos($head, "1 SOUR");
			if ($pos1!==false) {
				$pos2 = strpos($head, "\n1", $pos1+1);
				if ($pos2===false) $pos2 = strlen($head);
				$newhead = substr($head, 0, $pos1);
				$newhead .= substr($head, $pos2+1);
				$head = $newhead;
			}
			$pos1 = strpos($head, "1 DATE ");
			if ($pos1!=false) {
				$pos2 = strpos($head, "\n1", $pos1+1);
				if ($pos2===false) {
					$head = substr($head, 0, $pos1);
				}
				else {
					$head = substr($head, 0, $pos1).substr($head, $pos2+1);
				}
			}
			$head = trim($head);
			$head .= "\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS ".GM_VERSION." ".GM_VERSION_RELEASE."\r\n";
			$head .= "1 DATE ".date("j M Y")."\r\n";
			$head .= "2 TIME ".date("h:i:s")."\r\n";
			if (strstr($head, "1 PLAC")===false) {
				$head .= "1 PLAC\r\n2 FORM ".GM_LANG_default_form."\r\n";
			}
		}
		else {
			$head = "0 HEAD\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS ".GM_VERSION." ".GM_VERSION_RELEASE."\r\n1 DEST DISKETTE\r\n1 DATE ".date("j M Y")."\r\n2 TIME ".date("h:i:s")."\r\n";
			$head .= "1 GEDC\r\n2 VERS 5.5\r\n2 FORM LINEAGE-LINKED\r\n1 CHAR ".GedcomConfig::$CHARACTER_SET."\r\n1 PLAC\r\n2 FORM ".GM_LANG_default_form."\r\n";
		}
		if ($convert=="yes") {
			$head = preg_replace("/UTF-8/", "ANSI", $head);
			$head = utf8_decode($head);
		}
		$head = RemoveCustomTags($head, $remove);
		$head = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $head);
		if ($zip == "yes") fwrite($gedout, $head);
		else print $head;
	
		$sql = "SELECT i_gedrec FROM ".TBLPREFIX."individuals WHERE i_file=".$GEDCOMID." ORDER BY CAST(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(i_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchRow()){
				$rec = trim($row[0])."\r\n";
				if ($embedmm=="yes") $rec = self::EmbedMM($rec);
				$rec = RemoveCustomTags($rec, $remove);
				if ($privatize_export == "yes") $rec = PrivacyFunctions::PrivatizeGedcom($rec);
				if ($convert=="yes") $rec = utf8_decode($rec);
				if ($zip == "yes") fwrite($gedout, $rec);
				else print $rec;
			}
			$res->FreeResult();
		}
		
		$sql = "SELECT f_gedrec FROM ".TBLPREFIX."families WHERE f_file=".$GEDCOMID." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(f_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchRow()){
				$rec = trim($row[0])."\r\n";
				if ($embedmm=="yes") $rec = self::EmbedMM($rec);
				$rec = RemoveCustomTags($rec, $remove);
				if ($privatize_export == "yes") $rec = PrivacyFunctions::PrivatizeGedcom($rec);
				if ($convert=="yes") $rec = utf8_decode($rec);
				if ($zip == "yes") fwrite($gedout, $rec);
				else print $rec;
			}
			$res->FreeResult();
		}
	
		$sql = "SELECT s_gedrec FROM ".TBLPREFIX."sources WHERE s_file=".$GEDCOMID." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(s_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchRow()){
				$rec = trim($row[0])."\r\n";
				if ($embedmm=="yes") $rec = self::EmbedMM($rec);
				$rec = RemoveCustomTags($rec, $remove);
				if ($privatize_export == "yes") $rec = PrivacyFunctions::PrivatizeGedcom($rec);
				if ($convert=="yes") $rec = utf8_decode($rec);
				if ($zip == "yes") fwrite($gedout, $rec);
				else print $rec;
			}
			$res->FreeResult();
		}
		
		if ($embedmm != "yes") {
			$sql = "SELECT m_gedrec FROM ".TBLPREFIX."media WHERE m_file=".$GEDCOMID." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(m_media),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
			$res = NewQuery($sql);
			if ($res) {
				while($row = $res->FetchRow()){
					$rec = trim($row[0])."\r\n";
					$rec = RemoveCustomTags($rec, $remove);
					if ($privatize_export == "yes") $rec = PrivacyFunctions::PrivatizeGedcom($rec);
					if ($convert=="yes") $rec = utf8_decode($rec);
					if ($zip == "yes") fwrite($gedout, $rec);
					else print $rec;
				}
				$res->FreeResult();
			}
		}
	
		$sql = "SELECT o_gedrec, o_type FROM ".TBLPREFIX."other WHERE o_file=".$GEDCOMID." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(o_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchRow()){
				$rec = trim($row[0])."\r\n";
				$key = $row[1];
				if (($key!="HEAD")&&($key!="TRLR")) {
					$rec = RemoveCustomTags($rec, $remove);
					if ($privatize_export == "yes") $rec = PrivacyFunctions::PrivatizeGedcom($rec);
					if ($convert=="yes") $rec = utf8_decode($rec);
					if ($zip == "yes") fwrite($gedout, $rec);
					else print $rec;
				}
			}
			$res->FreeResult();
		}
	
		if ($zip == "yes") fwrite($gedout, "0 TRLR\r\n");
		else print "0 TRLR\r\n";
		
		if ($privatize_export == "yes") {
			if (isset($_SESSION)) {
				$_SESSION["gm_user"] = $_SESSION["org_user"];
			}
			if (isset($HTTP_SESSION_VARS)) {
				$HTTP_SESSION_VARS["gm_user"] = $HTTP_SESSION_VARS["org_user"];
			}
			UserController::DeleteUser("export");
			$gm_username = UserController::GetUserName();
			$gm_user =& User::GetInstance($gm_username); 
		}
		SwitchGedcom();
		if ($zip == "yes") {
			fclose($gedout);
		}
	}
	
	public function EmbedMM($gedrec) {
	
		$ct = preg_match_all("/\n(\d) OBJE @(.+)@/", $gedrec, $match);
		for ($i=1;$i<=$ct;$i++) {
			$mmid = $match[2][$i-1];
			$level = $match[1][$i-1];
			$mediarec = FindMediaRecord($mmid);
			$oldlevel = $mediarec[0];
			$mediarec = preg_replace("/\n(\d) /e", "'\n'.SumNums($1, $level).' '", $mediarec);
			$mediarec = preg_replace("/0 @.+@ OBJE\s*\r\n/", "", $mediarec);
			$mediarec = $level." OBJE\r\n".$mediarec."\r\n";
			$gedrec = preg_replace("/$level OBJE @$mmid@\s*/", $mediarec, $gedrec);
		}
		return $gedrec;
	}
	
	/**
	 * find the name of the first GEDCOM file in a zipfile
	 * @param string $zipfile	the path and filename
	 * @param boolean $extract  true = extract and return filename, false = return filename
	 * @return string		the path and filename of the gedcom file
	 */
	function GetGedFromZip($zipfile, $extract=true) {
	
		require_once "includes/pclzip.lib.php";
		$zip = new PclZip($zipfile);
	
		// if it's not a valid zip, just return the filename
		if (($list = $zip->listContent()) == 0) {
			return $zipfile;
		}
	
		// Determine the extract directory
		$slpos = strrpos($zipfile, "/");
		if (!$slpos) $slpos = strrpos($zipfile,"\\");
		if ($slpos) $path = substr($zipfile, 0, $slpos+1);
		else $path = INDEX_DIRECTORY;
		// Scan the files and return the first .ged found
		foreach($list as $key=>$listitem) {
			if (($listitem["status"] == "ok") && (strstr(strtolower($listitem["filename"]), ".") == ".ged")) {
				$filename = basename($listitem["filename"]);
				if ($extract == false) return $filename;
	
				// if the gedcom exists, save the old one. NOT to bak as it will be overwritten on import
				if (file_exists($path.$filename)) {
					if (file_exists($path.$filename.".old")) unlink($path.$filename.".old");
					copy($path.$filename, $path.$filename.".old");
					unlink($path.$filename);
				}
				if ($zip->extract(PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_PATH, $path, PCLZIP_OPT_BY_NAME, $listitem["filename"]) == 0) {
					print "ERROR cannot extract ZIP";
				}
				return $filename;
			}
		}
		return $zipfile;
	}
	
	public function UpdateUserIndiEmail($user) {
		global $GEDCOMS, $GEDCOMID;
		
		if ($user->email != "" && $user->sync_gedcom == "Y") {
			$oldged = $GEDCOMID;
			foreach($user->gedcomid as $gedcid => $gedid) {
				if (!empty($gedid) && isset($GEDCOMS[$gedcid])) {
					$GEDCOMID = $gedcid;
					$sourstring = GetLangVarString("sync_mailsource", $GEDCOMID, "gedcomid");
					$person = Person::GetInstance($gedid, "", $gedcid);
					if ($person->ischanged) $indirec = $person->changedgedrec;
					else $indirec = $person->gedrec;
					if (!empty($indirec)) {
						$subrecords = GetAllSubrecords($indirec, "", false, false, false);
						$found = false;
						$sourstring = GetLangVarString("sync_mailsource", $GEDCOMID, "gedcomid");
						foreach ($subrecords as $key =>$subrec) {
							$change_id = GetNewXref("CHANGE");
							if (preg_match("/(\d) (_?EMAIL .+)/", $subrec, $match)>0) {
								$found = true;
								$level = $match[1];
								if ($level == 1) {
									$newrec = "1 EMAIL ".$user->email."\r\n2 RESN privacy\r\n2 SOUR ".$sourstring."\r\n";
								}
								else {
									$oldrec = $match[0];
									$newrec = preg_replace("/(\d _?EMAIL)[^\r\n]*/", "$1 ".$user->email, $subrec);
								}
								if ($subrec != $newrec) EditFunctions::ReplaceGedrec($gedid, $subrec, $newrec, "EMAIL", $change_id, "edit_fact", $GEDCOMID, "INDI");
							}
						}
						if (!$found) EditFunctions::ReplaceGedrec($gedid, "", "1 EMAIL ".$user->email."\r\n2 RESN privacy\r\n2 SOUR ".$sourstring."\r\n", "EMAIL", $change_id, "add_fact", $GEDCOMID, "INDI");
					}
				}
			}
			$GEDCOMID = $oldged;
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
	 * @param		string	$gedid	Used with Gedcom Log and Search Log
	 *								Gedcomid the Log record applies to
	 * @param		boolean $last	If true, return oldest log entries
	 * @param		boolean $count	If true, return the number of logrecords matching criteria
	 * @return 		array			Array with log records
	 */
	public function ReadLog($cat, $max="20", $type="", $gedid="", $last=false, $count=false) {
	
		if (!$count) {
			$sql = "SELECT * FROM ".TBLPREFIX."log WHERE l_category='".$cat."'";
			if (!empty($type)) $sql .= " AND l_type='".$type."'";
			if (!empty($gedid) && $cat != "S") $sql .= " AND l_file='".$gedid."'";
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
					$logline["gedcomid"] = $log_row["l_file"];
					$loglines[] = $logline;
				}
			}
			$res->FreeResult();
			return $loglines;
		}
		else {
			$sql = "SELECT COUNT(l_type) FROM ".TBLPREFIX."log WHERE l_category='".$cat."'";
			if (!empty($type)) $sql .= " AND l_type='".$type."'";
			if (!empty($gedid) && $cat != "S") $sql .= " AND l_file='".$gedid."'";
			$res = NewQuery($sql);
			if ($res) {
				$number = $res->FetchRow();
				return $number[0];
			}
		}
	}
	
	public function NewLogRecs($cat, $gedid="") {
		
		$sql = "SELECT count('l_type') FROM ".TBLPREFIX."log WHERE l_category='".$cat."' AND l_type='E' AND l_new='1'";
		if (!empty($gedid)) $sql .= " AND l_file='".$gedid."'";
		$res = NewQuery($sql);
		if ($res) {
			$number = $res->FetchRow();
			return $number[0];
		}
		return false;
	}
	
	public function HaveReadNewLogrecs($cat, $gedid="") {
		
		$sql = "UPDATE ".TBLPREFIX."log SET l_new='0' WHERE l_category='".$cat."' AND l_type='E' AND l_new='1'";
		if (!empty($gedid) && $cat != "S") $sql .= " AND l_file='".$gedid."'";
		$res = NewQuery($sql);
	}
	
	public function ImportEmergencyLog() {
	
		// If we cannot read/delete the file, don't process it.
		$filename = INDEX_DIRECTORY."emergency_syslog.txt";
		if (!AdminFunctions::FileIsWriteable($filename)) return GM_LANG_emergency_log_noprocess;
		
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
		
		return GM_LANG_emergency_log_exists;
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
	public function ImportTable($fn) {
		
		if (file_exists(INDEX_DIRECTORY.$fn)) $sqlines = file(INDEX_DIRECTORY.$fn);
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
	public function ExportTable($table, $join="no", $newname="") {
	
		$tables = array();
		$fn = array();
		if (!is_array($table)) $tables[] = $table;
		else $tables = $table;
		$outstr = "";
		foreach($tables as $tabkey=>$tabname) {
			$sql = "SHOW COLUMNS FROM ".TBLPREFIX.$tabname;
			$res1 = NewQuery($sql);
			$fstring = " (";
			while ($fieldrow = $res1->FetchAssoc()) $fstring .= $fieldrow["Field"].",";
			$fstring = substr($fstring, 0, -1);
			$fstring .= ") ";
			$outstr .= "DELETE FROM ".TBLPREFIX.$tabname."\r\n";
			$sql = "SELECT * FROM ".TBLPREFIX.$tabname;
			$res = NewQuery($sql);
			$ct = $res->NumRows($res->result);
			if ($ct != "0") {
				while ($row = $res->FetchAssoc($res->result)) {
					$line = "INSERT INTO ".TBLPREFIX.$tabname.$fstring."VALUES (";
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
				if (file_exists(INDEX_DIRECTORY."export_".$tabname.".sql")) unlink(INDEX_DIRECTORY."export_".$tabname.".sql");
	
				$fp = fopen(INDEX_DIRECTORY."export_".$tabname.".sql", "w");
				if ($fp) {
					fwrite($fp, $outstr);
					fclose($fp);
					$fn[] = INDEX_DIRECTORY."export_".$tabname.".sql";
				}
				else return "";
			}
		}
		return $fn;
	}

	public function CalculateGedcomPath($inpath) {
		
		$path = "";
		$parts = preg_split("/[\/\\\]/", $inpath);
		$ctparts = count($parts)-1;
		if (count($parts) == 1) $path = INDEX_DIRECTORY;
		else {
			foreach ($parts as $key => $pathpart) {
				if ($key < $ctparts) $path .= $pathpart."/";
			}
		}
		return $path;
	}
	
	/**
	 * Store the GEDCOMS array in the database
	 *
	 * The function takes the GEDCOMS array and stores all
	 * content in the database, including DEFAULT_GEDCOM.
	 *
	 * @author	Genmod Development Team
	 */
	public function StoreGedcoms() {
		global $GEDCOMS, $DEFAULT_GEDCOM, $GEDCOMID;
	
		if (!CONFIGURED) return false;
		uasort($GEDCOMS, "GedcomSort");
		$maxid = 0;
		foreach ($GEDCOMS as $name => $details) {
			if (isset($details["id"]) && $details["id"] > $maxid) $maxid = $details["id"];
		}
		// -- For now, we update the gedcoms table by rewriting it
		$sql = "DELETE FROM ".TBLPREFIX."gedcoms";
		$res = NewQuery($sql);
		
		$maxid++;
		foreach($GEDCOMS as $indexval => $GED) {
			//print "<br /><br />Processing gedcom ".$indexval;
			//print_r($GED);
			$GED["path"] = str_replace(INDEX_DIRECTORY, "[INDEX_DIRECTORY]/", $GED["path"]);
			$GED["title"] = stripslashes($GED["title"]);
			$GED["title"] = preg_replace("/\"/", "\\\"", $GED["title"]);
			// TODO: Commonsurnames from an old gedcom are used
			// TODO: Default GEDCOM is changed to last uploaded GEDCOM
	
			// NOTE: Set the GEDCOM ID
			if (!isset($GED["id"]) || (empty($GED["id"]))) $GED["id"] = $maxid;
	
			if (empty($GED["commonsurnames"])) {
				if ($GED["gedcom"] == get_gedcom_from_id($GEDCOMID)) {
					$GED["commonsurnames"] = "";
					$surnames = GetCommonSurnames(GedcomConfig::$COMMON_NAMES_THRESHOLD);
					foreach($surnames as $indexval => $surname) {
						$GED["commonsurnames"] .= $surname["name"].", ";
					}
				}
				else $GED["commonsurnames"]="";
			}
			if ($GED["gedcom"] == $DEFAULT_GEDCOM) $is_default = "Y";
			else $is_default = "N";
			$sql = "INSERT INTO ".TBLPREFIX."gedcoms VALUES('".DbLayer::EscapeQuery($GED["id"])."','".DbLayer::EscapeQuery($GED["gedcom"])."','".DbLayer::EscapeQuery($GED["title"])."','".DbLayer::EscapeQuery($GED["path"])."','".DbLayer::EscapeQuery($GED["commonsurnames"])."','".DbLayer::EscapeQuery($is_default)."')";
			$res = NewQuery($sql);
		}
	}
	
	public function CheckUploadedGedcom($filename) {
		
		if (!isset($_FILES['GEDCOMPATH']) || filesize($_FILES['GEDCOMPATH']['tmp_name'])== 0 || (strstr(strtolower(trim($_FILES['GEDCOMPATH']['name'])), ".zip") == ".zip" && AdminFunctions::GetGedFromZip($_FILES['GEDCOMPATH']['tmp_name'], false) != $filename) || (strstr(strtolower(trim($_FILES['GEDCOMPATH']['name'])), ".ged") == ".ged" && $_FILES['GEDCOMPATH']['name'] != $filename)) {
			unlink($_FILES['GEDCOMPATH']['tmp_name']);
			return false;
		}
		return true;
	}
	
	public function MoveUploadedGedcom($file, $path) {
		
		if (file_exists($path.$_FILES['GEDCOMPATH']['name'])) {
			if (file_exists($path.$_FILES['GEDCOMPATH']['name'].".old")) unlink($path.$_FILES['GEDCOMPATH']['name'].".old");
			copy($path.$_FILES['GEDCOMPATH']['name'], $path.$_FILES['GEDCOMPATH']['name'].".old");
			unlink($path.$_FILES['GEDCOMPATH']['name']);
		}
		if (move_uploaded_file($_FILES['GEDCOMPATH']['tmp_name'], $path.$_FILES['GEDCOMPATH']['name'])) {
				WriteToLog("EditConfigGedcom-> Gedcom ".$path.$_FILES['GEDCOMPATH']['name']." uploaded", "I", "S");
		}
		// Get the gedcom name from the ZIP 
		if (strstr(strtolower(trim($_FILES['GEDCOMPATH']['name'])), ".zip") == ".zip") {
			return AdminFunctions::GetGedFromZip($path.$_FILES['GEDCOMPATH']['name']);
		}
		else return $file;
	}
}
?>