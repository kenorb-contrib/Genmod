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
	
	public function PrintGedcom($ged, $convert, $remove, $zip, $privatize_export, $privatize_export_level, $gedname, $embedmm, $embednote, $addaction) {
		GLOBAL $GM_BASE_DIRECTORY, $gm_username, $gm_user;
		
		if ($zip == "yes") {
			$gedout = fopen($gedname, "w");
		}
		else $gedout = "";
	
		if ($privatize_export == "yes") {
			UserController::CreateExportUser($privatize_export_level);
			if (isset($_SESSION)) {
				$_SESSION["org_user"] = $gm_user->username;
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
		$sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_id='HEAD' AND o_file='".GedcomConfig::$GEDCOMID."'";
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
	
		$sql = "SELECT i_key, i_gedrec, i_file, i_id, i_isdead, n_id, n_name, n_surname, n_nick, n_type, n_letter, n_fletter".($addaction == "yes" ? ", a_pid, a_type, a_file, a_id, a_repo, a_text, a_status" : "")." FROM ".TBLPREFIX."individuals INNER JOIN ".TBLPREFIX."names ON i_key=n_key ".($addaction == "yes" ? "LEFT JOIN ".TBLPREFIX."actions ON (a_file=i_file AND a_pid=i_id)" : "")." WHERE i_file=".GedcomConfig::$GEDCOMID." ORDER BY CAST(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(i_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned), n_id";
		$res = NewQuery($sql);
		if ($res) {
			$actionstr = "";
			$key = "";
			$keyn = "";
			while($row = $res->FetchAssoc()) {
				if ($key != $row["i_key"]) {
					if ($key != "") {
						$person->names_read = true;
						self::PrintGedcomObject($person, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout, $actionstr);
						$actionstr = "";
					}
					$person = null;
					$key = $row["i_key"];
					$person = new Person($row["i_id"], $row, $row["i_id"]);
				}
				if ($keyn != $row["n_id"]) {
					$person->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
					$keyn = $row["n_id"];
				}
				if ($addaction == "yes" && !is_null($row["a_id"])) {
					$action = new ActionItem($row);
					$actionstr .= $action->GetGedcomString();
				}
			}
			if ($key != "") {
				$person->names_read = true;
				self::PrintGedcomObject($person, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout, $actionstr);
			}
		}
		
		$sql = "SELECT f_id, f_gedrec, f_file".($addaction == "yes" ? ", a_pid, a_type, a_file, a_id, a_repo, a_text, a_status" : "")."  FROM ".TBLPREFIX."families ".($addaction == "yes" ? "LEFT JOIN ".TBLPREFIX."actions ON (a_file=f_file AND a_pid=f_id)" : "")." WHERE f_file=".GedcomConfig::$GEDCOMID." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(f_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
		$res = NewQuery($sql);
		if ($res) {
			$actionstr = "";
			$key = "";
			while($row = $res->FetchAssoc()){
				if ($key != $row["f_id"]) {
					if ($key != "") {
						self::PrintGedcomObject($family, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout, $actionstr);
						$actionstr = "";
					}
					$key = $row["f_id"];
					$family = new Family($row["f_id"], $row, $row["f_file"]);
				}
				if ($addaction == "yes" && !is_null($row["a_id"])) {
					$action = new ActionItem($row);
					$actionstr .= $action->GetGedcomString();
				}
			}
			if ($key != "") self::PrintGedcomObject($family, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout, $actionstr);
			$res->FreeResult();
		}
	
		$sql = "SELECT s_key, s_id, s_file, s_gedrec FROM ".TBLPREFIX."sources WHERE s_file=".GedcomConfig::$GEDCOMID." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(s_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchAssoc()){
				$source = null;
				$source = new Source($row["s_id"], $row, $row["s_file"]);
				self::PrintGedcomObject($source, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout);
			}
			$res->FreeResult();
		}
		
		if ($embedmm != "yes") {
			$sql = "SELECT m_media, m_file, m_gedrec, m_ext, m_mfile FROM ".TBLPREFIX."media WHERE m_file=".GedcomConfig::$GEDCOMID." ORDER BY cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(m_media),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
			$res = NewQuery($sql);
			if ($res) {
				while($row = $res->FetchAssoc()){
					$media = new MediaItem($row["m_media"], $row, $row["m_file"]);
					self::PrintGedcomObject($media, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout);
				}
				$res->FreeResult();
			}
		}
	
		$sql = "SELECT o_gedrec, o_type, o_file, o_id".($addaction == "yes" ? ", a_pid, a_type, a_file, a_id, a_repo, a_text, a_status" : "")." FROM ".TBLPREFIX."other ".($addaction == "yes" ? "LEFT JOIN ".TBLPREFIX."actions ON (a_file=o_file AND a_repo=o_id)" : "")." WHERE o_file=".GedcomConfig::$GEDCOMID." ORDER BY o_type, cast(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(LOWER(o_id),'a',''),'b',''),'c',''),'d',''),'e',''),'f',''),'g',''),'h',''),'i',''),'j',''),'k',''),'l',''),'m',''),'n',''),'o',''),'p',''),'q',''),'r',''),'s',''),'t',''),'u',''),'v',''),'w',''),'x',''),'y',''),'z','') as unsigned)";
		$res = NewQuery($sql);
		if ($res) {
			$actionstr = "";
			$nkey = "";
			$object = "";
			while($row = $res->FetchAssoc()) {
				if ($nkey != $row["o_id"]) {
					if ($nkey != "" && is_object($object)) {
						// we must check for an object, because a HEAD line doesn't create one.
						self::PrintGedcomObject($object, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout, $actionstr);
						$actionstr = "";
					}
					$nkey = $row["o_id"];
					$key = $row["o_type"];
					if ($key != "HEAD" && $key != "TRLR") {
						if ($key != "NOTE" || $embednote != "yes") {
							if ($key == "NOTE") $object = new Note($row["o_id"], $row, $row["o_file"]);
							if ($key == "REPO") $object = new Repository($row["o_id"], $row, $row["o_file"]);
							if ($key == "SUBM") $object = new Submitter($row["o_id"], $row, $row["o_file"]);
						}
						else $object = "";
					}
					else $object = "";
				}
				if ($addaction == "yes" && !is_null($row["a_id"]) && is_null($row["a_pid"])) {
					$action = new ActionItem($row);
					$actionstr .= $action->GetGedcomString();
				}
			}
			if ($nkey != "" && is_object($object)) self::PrintGedcomObject($object, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout, $actionstr);
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
			$media =& MediaItem::GetInstance($mmid, "", GedcomConfig::$GEDCOMID);
			$mediarec = $media->gedrec;
			// Remove the CHAN record
			$pos1 = strpos($mediarec, "1 CHAN");
			if ($pos1 !== false) {
				$pos2 = strpos($mediarec, "\n1", $pos1+4);
				if ($pos2 === false) $pos2 = strlen($mediarec);
				$newgedrec = substr($mediarec, 0, $pos1);
				$newgedrec .= substr($mediarec, $pos2);
				$mediarec = $newgedrec;
			}
			// Correct the level
			$oldlevel = $mediarec[0];
			$mediarec = preg_replace("/\n(\d) /e", "'\n'.SumNums($1, $level).' '", $mediarec);
			$mediarec = preg_replace("/0 @.+@ OBJE\s*\r\n/", "", $mediarec);
			$mediarec = $level." OBJE\r\n".$mediarec."\r\n";
			$gedrec = preg_replace("/$level OBJE @$mmid@\s*/", $mediarec, $gedrec);
		}
		return $gedrec;
	}
	
	public function EmbedNote($gedrec) {
	
		$ct = preg_match_all("/\n(\d) NOTE @(.+)@/", $gedrec, $match);
		for ($i=1;$i<=$ct;$i++) {
			$nid = $match[2][$i-1];
			$level = $match[1][$i-1];
			$note =& Note::GetInstance($nid, "", GedcomConfig::$GEDCOMID);
			$noterec = $note->gedrec;
			// Remove the CHAN record
			$pos1 = strpos($noterec, "1 CHAN");
			if ($pos1 !== false) {
				$pos2 = strpos($noterec, "\n1", $pos1+4);
				if ($pos2 === false) $pos2 = strlen($noterec);
				$newgedrec = substr($noterec, 0, $pos1);
				$newgedrec .= substr($noterec, $pos2);
				$noterec = $newgedrec;
			}
			// Correct the level
			$oldlevel = $noterec[0];
			$noterec = preg_replace("/\n(\d) /e", "'\n'.SumNums($1, $level).' '", $noterec);
			$noterec = preg_replace("/^0 @.+@ NOTE/", $level." NOTE", $noterec);
			$gedrec = preg_replace("/$level NOTE @$nid@\s*/", $noterec, $gedrec);
		}
		return $gedrec;
	}

	private function PrintGedcomObject(&$object, $privatize_export, $embedmm, $embednote, $remove, $convert, $zip, $gedout, $actionrec="") {
		
		if ($privatize_export == "yes") $rec = $object->oldprivategedrec;
		else $rec = $object->gedrec;
		if ($privatize_export != "yes" || $object->disp) {
			if ($embedmm == "yes") $rec = self::EmbedMM($rec);
			if ($embednote == "yes") $rec = self::EmbedNote($rec);
		}
		$rec = RemoveCustomTags($rec, $remove);
		$rec .= $actionrec;
		if ($convert == "yes") $rec = utf8_decode($rec);
		if ($zip == "yes") fwrite($gedout, $rec);
		else print $rec;
	}
		
	/**
	 * find the name of the first GEDCOM file in a zipfile
	 * @param string $zipfile	the path and filename
	 * @param boolean $extract  true = extract and return filename, false = return filename
	 * @return string		the path and filename of the gedcom file
	 */
	public function GetGedFromZip($zipfile, $extract=true) {
	
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
		
		if ($user->email != "" && $user->sync_gedcom == "Y") {
			$oldged = GedcomConfig::$GEDCOMID;
			foreach($user->gedcomid as $gedcid => $gedid) {
				if (!empty($gedid) && isset($GEDCOMS[$gedcid])) {
					GedcomConfig::$GEDCOMID = $gedcid;
					$sourstring = GetLangVarString("sync_mailsource", GedcomConfig::$GEDCOMID, "gedcomid");
					$person = Person::GetInstance($gedid, "", $gedcid);
					if ($person->ischanged) $indirec = $person->changedgedrec;
					else $indirec = $person->gedrec;
					if (!empty($indirec)) {
						$subrecords = GetAllSubrecords($indirec, "", false, false, false);
						$found = false;
						$sourstring = GetLangVarString("sync_mailsource", GedcomConfig::$GEDCOMID, "gedcomid");
						foreach ($subrecords as $key =>$subrec) {
							$change_id = EditFunctions::GetNewXref("CHANGE");
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
								if ($subrec != $newrec) EditFunctions::ReplaceGedrec($gedid, $subrec, $newrec, "EMAIL", $change_id, "edit_fact", GedcomConfig::$GEDCOMID, "INDI");
							}
						}
						if (!$found) EditFunctions::ReplaceGedrec($gedid, "", "1 EMAIL ".$user->email."\r\n2 RESN privacy\r\n2 SOUR ".$sourstring."\r\n", "EMAIL", $change_id, "add_fact", GedcomConfig::$GEDCOMID, "INDI");
					}
				}
			}
			GedcomConfig::$GEDCOMID = $oldged;
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
		global $GEDCOMS, $DEFAULT_GEDCOMID;
	
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
				if ($GED["gedcom"] == get_gedcom_from_id(GedcomConfig::$GEDCOMID)) {
					$GED["commonsurnames"] = "";
					$surnames = GetCommonSurnames(GedcomConfig::$COMMON_NAMES_THRESHOLD);
					foreach($surnames as $indexval => $surname) {
						$GED["commonsurnames"] .= $surname["name"].", ";
					}
				}
				else $GED["commonsurnames"]="";
			}
			if ($GED["id"] == $DEFAULT_GEDCOMID) $is_default = "Y";
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
	
	public function DeleteGedcom($gedid) {
	
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
	
	/**
	 * Read the Genmod News from the Genmod webserver
	 *
	 * The function reads the newsfile from the Genmod
	 * webserver and stores the data in an array.
	 * The array is returned and the data displayed on the admin page.
	 * News is fetched per session and stored in the session data.
	 * If no news is present in the newsfile on the server, nothing is displayed.
	 * If the newsfile cannot be opened, an error message is displayed.
	 * News format:
	 * [Item]
	 * [Date]mmm dd yyyy[/Date]
	 * [Type]<Normal|Urgent>[/Type]
	 * [Header]News header[/Header]
	 * [Text]News text[/Text]
	 * [/Item]
	 *
	 * @author	Genmod Development Team
	 * @return	array	Array with news items
	 */
	public function GetGMNewsItems() {
		global $NEWS_TYPE, $PROXY_ADDRESS, $PROXY_PORT;
	
		// -- If the news is already retrieved, get it from the session data.
		if(isset($_SESSION["gmnews"])) return $_SESSION["gmnews"];
	
		// -- Retrieve the news from the website
		$gmnews = array();
		if (!empty($PROXY_ADDRESS) && !empty($PROXY_PORT)) {
			$num = "(\\d|[1-9]\\d|1\\d\\d|2[0-4]\\d|25[0-5])";
			if (!preg_match("/^$num\\.$num\\.$num\\.$num$/", $PROXY_ADDRESS)) $ip = gethostbyname($PROXY_ADDRESS);
			else $ip = $PROXY_ADDRESS;
			$handle = @fsockopen($ip, $PROXY_PORT);
			if ($handle!=false) {
				$com = "GET http://www.genmod.net/gmnews.txt HTTP/1.1\r\nAccept: */*\r\nAccept-Language: de-ch\r\nAccept-Encoding: gzip, deflate\r\nUser-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)\r\nHost: $PROXY_ADDRESS:$PROXY_PORT\r\nConnection: Keep-Alive\r\n\r\n";
				fputs($handle, $com);
				$txt = fread($handle, 65535);
				fclose($handle);
				$txt = substr($txt, strpos($txt, "\r\n\r\n") + 4);
			}
		}
		else {
			@ini_set('user_agent','MSIE 4\.0b2;'); // force a HTTP/1.0 request
			@ini_set('default_socket_timeout', '5'); // timeout
			$handle = @fopen("http://www.genmod.net/gmnews.txt", "r");
			if ($handle!=false) {
				$txt = fread($handle, 65535);
				fclose($handle);
			}
		}
		if ($handle != false) {
			$txt = preg_replace("/[\r\n]/", "", $txt);
			$ct = preg_match_all("/\[Item](.+?)\[\/Item]/", $txt, $items);
			for ($i = 0; $i < $ct; $i++) {
				$item = array();
				$ct1 = preg_match("/\[Date](.+?)\[\/Date]/", $items[1][$i], $date);
				if ($ct1 > 0) $item["date"] = $date[1];
				else $item["date"] = "";
				$ct1 = preg_match("/\[Type](.+?)\[\/Type]/", $items[1][$i], $type);
				if ($ct1 > 0) $item["type"] = $type[1];
				else $item["type"] = "";
				$ct1 = preg_match("/\[Header](.+?)\[\/Header]/", $items[1][$i], $header);
				if ($ct1 > 0) $item["header"] = $header[1];
				else $item["header"] = "";
				$ct1 = preg_match("/\[Text](.+?)\[\/Text]/", $items[1][$i], $text);
				if ($ct1 > 0) $item["text"] = $text[1];
				else $item["text"] = "";
				if (($NEWS_TYPE == "Normal") || ($NEWS_TYPE == $item["type"])) $gmnews[] = $item;
			}
		}
		else {
			WriteToLog("GetGMNewsItems-> News cannot be reached on Genmod News Server", "E");
			$item["date"] = "";
			$item["type"] = "Urgent";
			$item["header"] = "Warning: News cannot be retrieved";
			$item["text"] = "Genmod cannot retrieve the news from the news server. If this problem persist after next logons, please report this on the <a href=\"http://www.genmod.net\">Genmod Help forum</a>";
			$gmnews[] = $item;
		}
		// -- Store the news in the session data
		$_SESSION["gmnews"] = $gmnews;
		return $gmnews;
	}
	
	/**
	 * reset the i_isdead column
	 * 
	 * This function will reset the i_isdead column with the default -1 so that all is dead status
	 * items will be recalculated.
	 */
	public function ResetIsDead() {
		
		$sql = "UPDATE ".TBLPREFIX."individuals SET i_isdead=-1 WHERE i_file='".GedcomConfig::$GEDCOMID."'";
		$res = NewQuery($sql);
		return $res;
	}

}
?>