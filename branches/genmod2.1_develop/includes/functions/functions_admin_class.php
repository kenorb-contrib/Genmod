<?php
/**
 * Functions used in admin pages
 *
 * $Id: functions_admin_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
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
						$entry = str_replace("//", "/", $dir."/".$entry);
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
		GLOBAL $gm_username, $gm_user;
		
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
			// $mediarec = preg_replace("/\n(\d) /e", "'\n'.SumNums($1, $level).' '", $mediarec);
			$mediarec = substr(preg_replace_callback("/\n(\d)/", function($matches) use($level) {return $matches[1]+$level;}, $mediarec),1);
			$mediarec = preg_replace("/@.+@ OBJE/", "", $mediarec);
			$mediarec = $level." OBJE".$mediarec."\r\n";
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
			// $noterec = preg_replace("/\n(\d) /e", "'\n'.SumNums($1, $level).' '", $noterec);
			$noterec = substr(preg_replace_callback("/\n(\d)/", function($matches) use($level) {return $matches[1]+$level;}, $noterec),1);
			$noterec = preg_replace("/@.+@ NOTE/", "", $noterec);
			$noterec = $level." NOTE".$noterec."\r\n";
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
					$sourstring = self::GetLangVarString("sync_mailsource", GedcomConfig::$GEDCOMID, "gedcomid");
					$person = Person::GetInstance($gedid, "", $gedcid);
					if ($person->ischanged) $indirec = $person->changedgedrec;
					else $indirec = $person->gedrec;
					if (!empty($indirec)) {
						$subrecords = GetAllSubrecords($indirec, "", false, false, false);
						$found = false;
						$sourstring = self::GetLangVarString("sync_mailsource", GedcomConfig::$GEDCOMID, "gedcomid");
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
			if ($last == false) $sql .= " ORDER BY l_timestamp DESC";
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
					$logline["text"] = self::SplitLine($log_row["l_text"], 100);
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
	public function SplitLine($text, $max) {
		
		$arr = explode("<br />", $text);
		$newarr = array();
		foreach($arr as $key => $line) {
			while (strlen($line) > $max) {
				$newarr[] = substr($line, 0, 80);
				$line = substr($line, 80);
			}
			$newarr[] = $line;
		}
		return implode("<br />", $newarr);
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
		$lines = explode("\r\n", $contents);
		
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
				$error = ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
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
						$line .= "'".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."'";
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
					$surnames = NameFunctions::GetCommonSurnames(GedcomConfig::$COMMON_NAMES_THRESHOLD);
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
	
	public function CheckUploadedGedcom($filename, $uploadname="", $checkzip=true) {
		
		if ($uploadname == "") $uploadname = "GEDCOMPATH";
		if (!isset($_FILES[$uploadname]) 
			|| filesize($_FILES[$uploadname]['tmp_name'])== 0 
			|| ($checkzip && strstr(strtolower(trim($_FILES[$uploadname]['name'])), ".zip") == ".zip" && AdminFunctions::GetGedFromZip($_FILES[$uploadname]['tmp_name'], false) != $filename) 
			|| (strstr(strtolower(trim($_FILES[$uploadname]['name'])), ".ged") == ".ged" && $_FILES[$uploadname]['name'] != $filename)) {
			unlink($_FILES[$uploadname]['tmp_name']);
			return false;
		}
		return true;
	}
	
	public function MoveUploadedGedcom($file, $path, $uploadname="") {
		
		if ($uploadname == "") $uploadname = "GEDCOMPATH";
		if (file_exists($path.$_FILES[$uploadname]['name'])) {
			if (file_exists($path.$_FILES[$uploadname]['name'].".old")) unlink($path.$_FILES[$uploadname]['name'].".old");
			copy($path.$_FILES[$uploadname]['name'], $path.$_FILES[$uploadname]['name'].".old");
			unlink($path.$_FILES[$uploadname]['name']);
		}
		if (move_uploaded_file($_FILES[$uploadname]['tmp_name'], $path.$_FILES[$uploadname]['name'])) {
				WriteToLog("MoveUploadedGedcom-&gt; Gedcom ".$path.$_FILES[$uploadname]['name']." uploaded", "I", "S");
		}
		// Get the gedcom name from the ZIP 
		if (strstr(strtolower(trim($_FILES[$uploadname]['name'])), ".zip") == ".zip") {
			return AdminFunctions::GetGedFromZip($path.$_FILES[$uploadname]['name']);
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
	
		// -- If the news is already retrieved, get it from the session data.
		if(isset($_SESSION["gmnews"])) return $_SESSION["gmnews"];
		
		// -- If no news server is specified, return nothing
		if (SystemConfig::$GM_NEWS_SERVER == "") return array();
	
		// -- Retrieve the news from the website
		$gmnews = array();
		if (SystemConfig::$PROXY_ADDRESS != "" && SystemConfig::$PROXY_PORT != "") {
			$num = "(\\d|[1-9]\\d|1\\d\\d|2[0-4]\\d|25[0-5])";
			if (!preg_match("/^$num\\.$num\\.$num\\.$num$/", SystemConfig::$PROXY_ADDRESS)) $ip = gethostbyname(SystemConfig::$PROXY_ADDRESS);
			else $ip = SystemConfig::$PROXY_ADDRESS;
			$handle = @fsockopen($ip, SystemConfig::$PROXY_PORT);
			if ($handle!=false) {
				$com = "GET ".SystemConfig::$GM_NEWS_SERVER."gmnews.txt HTTP/1.1\r\nAccept: */*\r\nAccept-Language: de-ch\r\nAccept-Encoding: gzip, deflate\r\nUser-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)\r\nHost: ".SystemConfig::$PROXY_ADDRESS.":".SystemConfig::$PROXY_PORT."\r\n";
				if (SystemConfig::$PROXY_USER != "") $com .= "Proxy-Authorization: Basic ".base64_encode(SystemConfig::$PROXY_USER.":".SystemConfig::$PROXY_PASSWORD) . "\r\n";
				$com .= "Connection: Keep-Alive\r\n\r\n";
				fputs($handle, $com);
				$txt = fread($handle, 65535);
				fclose($handle);
				$txt = substr($txt, strpos($txt, "\r\n\r\n") + 4);
			}
		}
		else {
			@ini_set('user_agent','MSIE 4\.0b2;'); // force a HTTP/1.0 request
			@ini_set('default_socket_timeout', '5'); // timeout
			$handle = @fopen(SystemConfig::$GM_NEWS_SERVER."gmnews.txt", "r");
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
				if (SystemConfig::$NEWS_TYPE == "Normal" || SystemConfig::$NEWS_TYPE == $item["type"]) $gmnews[] = $item;
			}
		}
		else {
			WriteToLog("GetGMNewsItems-&gt; News cannot be reached on Genmod News Server", "E");
			$item["date"] = "";
			$item["type"] = "Urgent";
			$item["header"] = "Warning: News cannot be retrieved";
			$item["text"] = "Genmod cannot retrieve the news from the news server. If this problem persist after next logons, please report this on the <a href=\"https://www.sourceforge.net/projects/genmod\">Genmod Help forum</a>";
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
	
	/**
	 * Stores the languages in the database, from the text files
	 *
	 * The function reads the language files, one language at a time and
	 * stores the data in the TBLPREFIX_language and TBLPREFIX_help_language
	 * tables.
	 *
	 * @author	Genmod Development Team
	 * @param		$setup		boolean	If we are not in setupmode we need to drop and recreate the tables first
	 * @param		$only_english	boolean	If the language table is corrupted, only import English otherwise the script takes too long
	 * @return	boolean	true or false depending on the outcome
	 */
	public function StoreEnglish($setup=false,$only_english=false) {
		global $gm_user, $language_settings;

		if (!self::CheckBom(false))	return false;
		
		if (!$setup) {
			// Empty the table
			$sql = "TRUNCATE TABLE ".TBLPREFIX."language";
			if (!$result = NewQuery($sql)) {
				WriteToLog("StoreEnglish-&gt; Language table could not be dropped", "E", "S");
				return false;
			}
			// Empty the table
			$sql = "TRUNCATE TABLE ".TBLPREFIX."language_help";
			if (!$result = NewQuery($sql)) {
				WriteToLog("StoreEnglish-&gt; Language help table could not be dropped", "E", "S");
				return false;
			}
			// Empty the table
			$sql = "TRUNCATE TABLE ".TBLPREFIX."facts";
			if (!$result = NewQuery($sql)) {
				WriteToLog("StoreEnglish-&gt; Facts table could not be dropped", "E", "S");
				return false;
			}
		}
		
		if (file_exists("languages/lang.en.txt")) {
			// NOTE: Import the English language into the database
			$lines = file("languages/lang.en.txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				// Cleanup the data
				if (!isset($data[1])) print "Error with language string ".$line;
				$data[0] = substr(trim($data[0]), 1);
				$data[1] = substr(trim($data[1]), 0, -1);
	//			print $data[0]." - ".$data[1]."<br />";
				// NOTE: Add the language variable to the language array
				
	//			$gm_lang[$data[0]] = $data[1];
				// NOTE: Store the language variable in the database
				if (!isset($data[1])) WriteToLog("StoreEnglish-&gt; Invalid language string ".$line, "E", "S");
				else {
					$sql = "INSERT INTO ".TBLPREFIX."language VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_user->username."')";
					if (!$result = NewQuery($sql)) {
						WriteToLog("StoreEnglish-&gt; Could not add language string ".$line." for language English to table ", "E", "S");
					}
					else $result->FreeResult();
				 }
			}
			
			$sql = "SELECT ls_gm_langname FROM ".TBLPREFIX."lang_settings WHERE ls_gm_langname='english'";
			$res = NewQuery($sql);
			if ($res->NumRows() > 0 ) {
				$sql = "UPDATE ".TBLPREFIX."lang_settings SET ls_translated = '0', ls_md5_lang = '".md5_file("languages/lang.en.txt")."', ls_md5_help = '".md5_file("languages/help_text.en.txt")."', ls_md5_facts = '".md5_file("languages/facts.en.txt")."' WHERE ls_gm_langname='english'";
				$res = NewQuery($sql);
			}
			else {
				$sql = "INSERT INTO ".TBLPREFIX."lang_settings (ls_gm_langname, ls_translated, ls_md5_lang, ls_md5_help, ls_md5_facts) VALUES ('english', '0','".md5_file("languages/lang.en.txt")."', '".md5_file("languages/help_text.en.txt")."', '".md5_file("languages/facts.en.txt")."')";
				$res = NewQuery($sql);
			}
			WriteToLog("StoreEnglish-&gt; English language added to the database", "I", "S");
			if ($only_english) WriteToLog("StoreEnglish-&gt; Additional languages are not restored", "W", "S");
		}
		
		if (file_exists("languages/facts.en.txt")) {
			// NOTE: Import the English facts into the database
			$lines = file("languages/facts.en.txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				// Cleanup the data
				$data[0] = substr(trim($data[0]), 1);
				$data[1] = substr(trim($data[1]), 0, -1);
	//			print $data[0]." - ".$data[1]."<br />";
				// NOTE: Add the facts variable to the facts array
	//			$factarray[$data[0]] = $data[1];
				
				
				// NOTE: Store the language variable in the database
				if (!isset($data[1])) WriteToLog("StoreEnglish-&gt; Invalid facts string ".$line, "E", "S");
				else {
					$sql = "INSERT INTO ".TBLPREFIX."facts VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_user->username."')";
					if (!$result = NewQuery($sql)) {
						WriteToLog("StoreEnglish-&gt; Could not add facts string ".$line." for language English to table ", "E", "S");
					}
					else $result->FreeResult();
				 }
			}
			WriteToLog("StoreEnglish-&gt; English facts added to the database", "I", "S");
			if ($only_english) WriteToLog("StoreEnglish-&gt; Additional facts languages are not restored", "W", "S");
		}
		
		if (!$only_english) {
			if (file_exists("languages/help_text.en.txt")) {
				// NOTE: Import the English language help into the database
				$lines = file("languages/help_text.en.txt");
				foreach ($lines as $key => $line) {
					$data = preg_split("/\";\"/", $line, 2);
					// NOTE: Add the help language variable to the language array
	//				$gm_lang[$data[0]] = $data[1];
					
					if (!isset($data[1])) WriteToLog("StoreEnglish-&gt; Invalid language help string ".$line, "E", "S");
					else {
						$data[0] = substr(trim($data[0]), 1);
						$data[1] = substr(trim($data[1]), 0, -1);
						$sql = "INSERT INTO ".TBLPREFIX."language_help VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_user->username."')";
						if (!$result = NewQuery($sql)) {
							WriteToLog("StoreEnglish-&gt; Could not add language help string ".$line." for language English to table ", "E", "S");
						}
						else {
	//						set_time_limit(10);
							$result->FreeResult();
						}
					}
				}
				WriteToLog("StoreEnglish-&gt; English help added to the database", "I", "S");
			}
			
			// Add all active languages if english is not specified
			
			foreach ($language_settings as $name => $value) {
				if ($value["gm_lang_use"] && $name != "english") {
					self::StoreLanguage($name);
				}
			}
		}
		return true;
	}
	/**
	 * Store a language into the database
	 *
	 * The function first reads the regular language file and imports it into the
	 * database. After that it reads the help file and imports it into the database.          
	 *
	 * @author	Genmod Development Team
	 * @param	     string	     $storelang	The name of the language to store
	 */
	public function StoreLanguage($storelang) {
		global $gm_user, $language_settings;
		
		if (!self::CheckBom(false))	return false;
	
		if (file_exists("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
			$lines = file("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				if (!isset($data[1])) WriteToLog("StoreLanguage-&gt; Invalid language string ".$line, "E", "S");
				else {
	       		    $data[0] = substr(ltrim($data[0]), 1);
	                $data[1] = substr(rtrim($data[1]), 0, -1);
	                if ($storelang == "english") {
		                $sql = "SELECT lg_english FROM ".TBLPREFIX."language WHERE lg_string='".$data[0]."'";
		                $res = NewQuery($sql);
		                if ($res->NumRows() == 0) {
							$sql = "INSERT INTO ".TBLPREFIX."language VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_user->username."')";
							if (!$result = NewQuery($sql)) {
								WriteToLog("StoreLanguage-&gt; Could not add language string ".$line." for language English to table ", "E", "S");
							}
							else $result->FreeResult();
						}
						else {
							$res->FreeResult();
			       			$sql = "UPDATE ".TBLPREFIX."language SET `lg_".$storelang."` = '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_user->username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
	    		   			if (!$result = NewQuery($sql)) {
	            	            WriteToLog("StoreLanguage-&gt; Could not update language string ".$line." for language ".$storelang." to table ", "E", "S");
		                    }
							else $result->FreeResult();
						}
					}
					else {
		       			$sql = "UPDATE ".TBLPREFIX."language SET `lg_".$storelang."` = '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_user->username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
	    	   			if (!$result = NewQuery($sql)) {
	           	            WriteToLog("StoreLanguage-&gt; Could not update language string ".$line." for language ".$storelang." to table ", "E", "S");
		                }
						else $result->FreeResult();
					}
	  		    }
		    }
		}
		if (file_exists("languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
			$lines = file("languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				if (!isset($data[1])) WriteToLog("StoreLanguage-&gt; Invalid language help string ".$line, "E", "S");
				else {
	                $data[0] = substr(ltrim($data[0]), 1);
	                $data[1] = substr(rtrim($data[1]), 0, -1);
	                if ($storelang == "english") {
		                $sql = "SELECT lg_english FROM ".TBLPREFIX."language_help WHERE lg_string='".$data[0]."'";
		                $res = NewQuery($sql);
		                if ($res->NumRows() == 0) {
							$sql = "INSERT INTO ".TBLPREFIX."language_help VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_user->username."')";
							if (!$result = NewQuery($sql)) {
								WriteToLog("StoreLanguage-&gt; Could not add language help string ".$line." for language English to table ", "E", "S");
							}
							else $result->FreeResult();
						}
						else {
							$res->FreeResult();
		                	$sql = "UPDATE ".TBLPREFIX."language_help SET `lg_".$storelang."` = '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_user->username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
	    	            	if (!$result = NewQuery($sql)) {
	        	                  WriteToLog("StoreLanguage-&gt; Could not update language help string ".$line." for language ".$storelang." to table ", "E", "S");
	            	        }
							else $result->FreeResult();
						}
	               	}
	               	else {
		            	$sql = "UPDATE ".TBLPREFIX."language_help SET `lg_".$storelang."` = '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_user->username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
	    	            if (!$result = NewQuery($sql)) {
	        	            WriteToLog("StoreLanguage-&gt; Could not update language help string ".$line." for language ".$storelang." to table ", "E", "S");
	            	    }
						else $result->FreeResult();
	               	}
	          	}
	     	}
	 	}
		if (file_exists("languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
			$lines = file("languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				if (!isset($data[1])) WriteToLog("StoreLanguage-&gt; Invalid facts string ".$line, "E", "S");
				else {
	                $data[0] = substr(ltrim($data[0]), 1);
	                $data[1] = substr(rtrim($data[1]), 0, -1);
	                if ($storelang == "english") {
		                $sql = "SELECT lg_english FROM ".TBLPREFIX."facts WHERE lg_string='".$data[0]."'";
		                $res = NewQuery($sql);
		                if ($res->NumRows() == 0) {
							$sql = "INSERT INTO ".TBLPREFIX."facts VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', '".$gm_user->username."')";
							if (!$result = NewQuery($sql)) {
								WriteToLog("StoreLanguage-&gt; Could not add facts string ".$line." for language English to table ", "E", "S");
							}
							else $result->FreeResult();
						}
						else {
							$res->FreeResult();
	    	            	$sql = "UPDATE ".TBLPREFIX."facts SET `lg_".$storelang."` = '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_user->username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
	        	        	if (!$result = NewQuery($sql)) {
	            	              WriteToLog("StoreLanguage-&gt; Could not add facts string ".$line." for language ".$storelang." to table ", "E", "S");
	                	    }
							else $result->FreeResult();
						}
	               	}
	               	else {
	   	            	$sql = "UPDATE ".TBLPREFIX."facts SET `lg_".$storelang."` = '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', lg_last_update_date='".time()."', lg_last_update_by='".$gm_user->username."' WHERE lg_string = '".$data[0]."' LIMIT 1";
	       	        	if (!$result = NewQuery($sql)) {
							WriteToLog("StoreLanguage-&gt; Could not add facts string ".$line." for language ".$storelang." to table ", "E", "S");
	                	}
						else $result->FreeResult();
	               	}
	          	}
	     	}
	 	}
		$sql = "SELECT ls_gm_langname FROM ".TBLPREFIX."lang_settings WHERE ls_gm_langname='".$storelang."'";
		$res = NewQuery($sql);
		if ($res->NumRows() > 0 ) {
			$sql = "UPDATE ".TBLPREFIX."lang_settings SET ls_translated = '0', ls_md5_lang = '".md5_file("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")."', ls_md5_help = '".md5_file("languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")."', ls_md5_facts = '".md5_file("languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")."' WHERE ls_gm_langname='".$storelang."'";
			$res = NewQuery($sql);
		}
		else {
			$sql = "INSERT INTO ".TBLPREFIX."lang_settings (ls_gm_langname, ls_translated, ls_md5_lang, ls_md5_help, ls_md5_facts) VALUES ('".$storelang."', '0','".md5_file("languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")."', '".md5_file("languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")."', '".md5_file("languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")."')";
			$res = NewQuery($sql);
		}
	     
	}
	/**
	 * Remove a language into the database
	 *
	 * The function removes a language from the database by making the column empty
	 *
	 * @author	Genmod Development Team
	 * @param	     string	     $storelang	The name of the language to remove
	 */
	public function RemoveLanguage($removelang) {
		
		if ($removelang != "english") {
			$sql = "UPDATE  ".TBLPREFIX."language SET lg_".$removelang."=''";
			$result = NewQuery($sql);
			$sql = "UPDATE  ".TBLPREFIX."language_help SET lg_".$removelang."=''";
			$result = NewQuery($sql);
			$sql = "UPDATE  ".TBLPREFIX."facts SET lg_".$removelang."=''";
			$result = NewQuery($sql);
			$sql = "UPDATE ".TBLPREFIX."lang_settings SET ls_translated='0', ls_md5_lang='', ls_md5_help='', ls_md5_facts='' WHERE ls_gm_langname='".$removelang."'";
			$result = NewQuery($sql);
			
		}
	}

	// Used for editing languages. Returns an array with facts and their description in the designated language
	public function LoadFacts($language) {
		global $gm_language;
		
		if (isset($gm_language[$language]) && CONFIGURED) {
			$temp = array();
			$sql = "SELECT `lg_string`, `lg_".$language."` FROM `".TBLPREFIX."facts";
			$sql .= "` WHERE `lg_".$language."` != ''";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc($res->result)) {
					$temp[$row["lg_string"]] = $row["lg_".$language];
				}
				return $temp;
			}
			else return false;
		}
	}
	
	/**
	 * Write a translated string
	 *
	 * <Long description of your function. 
	 * What does it do?
	 * How does it work?
	 * All that goes into the long description>
	 *
	 * @author	Genmod Development Team
	 * @param		string	$string	The translated string
	 * @param		string	$value	The string to update
	 * @param		string	$language2	The language in which the string is translated
	 * @return 	boolean	true if the update succeeded|false id the update failed
	 */
	
	public function WriteString($string, $value, $language2, $file_type="") {
		
		if ($file_type == "facts") {
			$sql = "UPDATE ".TBLPREFIX."facts";
			$sql .= " SET lg_".$language2."='".DbLayer::EscapeQuery($string)."' WHERE lg_string='".$value."'";
		}
		else {
			// If the string doesn't exist yet, we first enter the English text.
			$sql = "INSERT INTO ".TBLPREFIX."language";
			if (substr($value, -5) == "_help") $sql .= "_help";
			$sql .= " (lg_string, lg_english) VALUES ('".$value."', '".DbLayer::EscapeQuery($string)."')";
			$sql .= " ON DUPLICATE KEY UPDATE lg_".$language2."='".DbLayer::EscapeQuery($string)."'";
		}
		if ($res = NewQuery($sql)) {
			if ($res->AffectedRows() > 0) {
				$sql = "UPDATE ".TBLPREFIX."lang_settings SET ls_translated='1' WHERE ls_gm_langname='".$language2."'";
				$res2 = NewQuery($sql);
				return true;
			}
			else return false;
		}
		else return false;
	}
	
	public function StoreLangVars($vars) {
		
		$string = "";
		$first = true;
		$sql = "UPDATE ".TBLPREFIX."lang_settings SET ";
		foreach ($vars as $fname => $fvalue) {
			if (!$first) $string .= ", ";
			$first = false;
	//		if ($fvalue == false) $fvalue = "0";
	//		else $fvalue = "1";
			$string .= "ls_".$fname."='".DbLayer::EscapeQuery($fvalue)."'";
		}
		$sql .= $string;
		$sql .= " WHERE ls_gm_langname='".$vars["gm_langname"]."'";
		$res = NewQuery($sql);
		return $res;
	}	
	
	// Activate the given language
	public function ActivateLanguage($lang) {
		
		$sql = "UPDATE ".TBLPREFIX."lang_settings SET ls_gm_lang_use='1' WHERE ls_gm_langname='".$lang."'";
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
	
	// Activate the given language
	public function DeactivateLanguage($lang) {
		
		$sql = "UPDATE ".TBLPREFIX."lang_settings SET ls_gm_lang_use='0' WHERE ls_gm_langname='".$lang."'";
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
	
	/** Determines whether a language is in use or not
	 ** if $lang is empty, return the full array
	 ** else return true or false
	 */
	public function LanguageInUse($lang="") {
		global $GEDCOMS;
		static $configuredlanguages, $inuse;
		
		if (!isset($configuredlanguages)) {
			$configuredlanguages = array();
			$inuse = array();
	
			// Read GEDCOMS configuration and collect language data
			foreach ($GEDCOMS as $key => $value) {
				SwitchGedcom($value["gedcom"]);
				if (!isset($configuredlanguages["gedcom"][GedcomConfig::$GEDCOMLANG][$value["gedcom"]])) $configuredlanguages["gedcom"][GedcomConfig::$GEDCOMLANG][$value["gedcom"]] = TRUE;
				$inuse[GedcomConfig::$GEDCOMLANG] = true;
			}
			// Restore the current settings
			SwitchGedcom();
			
			// Read user configuration and collect language data
			$users = UserController::GetUsers("username","asc");
			foreach($users as $username=>$user) {
				if (!isset($configuredlanguages["users"][$user->language][$username])) $configuredlanguages["users"][$user->language][$username] = TRUE;
				$inuse[$user->language] = true;
			}
			$inuse["english"] = true;
		}
		if (empty($lang)) return $configuredlanguages;
		if (array_key_exists($lang, $inuse)) return $inuse[$lang];
		else return false;
	}
	
	/* Get the language file and translation info for the admin messages
	 */
	public function GetLangfileInfo($impexp) {
		global $language_settings;
		static $implangs, $explangs;
		
		if (!isset($implangs)) {
			$sql = "SELECT ls_gm_langname, ls_md5_lang, ls_md5_help, ls_md5_facts, ls_translated FROM ".TBLPREFIX."lang_settings";
			$res = NewQuery($sql);
			if ($res->NumRows() > 0) {
				$implangs = array();
				$explangs = array();
				while($lang = $res->FetchRow()) {
					if ($lang[4] == 1) $explangs[] = array("name" => constant("GM_LANG_lang_name_".$lang[0]), "lang" => $lang[0]);
					if (file_exists($language_settings[$lang[0]]["gm_language"]) 
					&& file_exists($language_settings[$lang[0]]["helptextfile"]) 
					&& file_exists($language_settings[$lang[0]]["factsfile"])) {
						if ($language_settings[$lang[0]]["gm_lang_use"] == true && (md5_file($language_settings[$lang[0]]["gm_language"]) != $lang[1] ||
							md5_file($language_settings[$lang[0]]["helptextfile"]) != $lang[2] ||
							md5_file($language_settings[$lang[0]]["factsfile"]) != $lang[3])) {
							$implangs[] = array("name" => constant("GM_LANG_lang_name_".$lang[0]), "lang" => $lang[0]);
						}
					}
				}
			}
		}
		if ($impexp == "import") return $implangs;
		else return $explangs;
	}
	
	//-----------------------------------------------------------------
	private function Mask_lt($dstring)
	{
	  $dummy = str_replace("<", "&lt;", $dstring);
	  return $dummy;
	}
	
	//-----------------------------------------------------------------
	private function Mask_gt($dstring)
	{
	  $dummy = str_replace(">", "&gt;", $dstring);
	  return $dummy;
	}
	
	//-----------------------------------------------------------------
	private function Mask_quot($dstring)
	{
	  $dummy = str_replace("\"", "&quot;", $dstring);
	  return $dummy;
	}
	
	//-----------------------------------------------------------------
	private function Mask_amp($dstring)
	{
	  $dummy = str_replace("&", "&amp;", $dstring);
	  return $dummy;
	}
	
	//-----------------------------------------------------------------
	public function Mask_all($dstring)
	{
	  $dummy = self::Mask_lt(self::Mask_gt(self::mask_quot(self::Mask_amp($dstring))));
	  return $dummy;
	}
	
	//-----------------------------------------------------------------*/
	public function CheckBom($text=true){
		global $language_settings;
		
		$success = true;
		$check = false;
		$output = "";
		foreach ($language_settings as $key => $language) {
			// Check if language is active
			if ($language["gm_lang_use"] == true) {
				// Check language file
				if (file_exists($language["gm_language"])) {
					$str = file_get_contents($language["gm_language"]);
					if (ord($str{0}) == 239 && ord($str{1}) == 187 && ord($str{2}) == 191) {
						$check = true;
						$output .= "<span class=\"Warning\">".GM_LANG_bom_found.substr($language["gm_language"], 10).".</span>";
						$output .= "<br />";
						$writetext = htmlentities(substr($str,3, strlen($str)));
						if (!$handle = @fopen($language["gm_language"], "w")){
							$output .= "<span class=\"Warning\">";
							$output .= str_replace("#lang_filename#", substr($language["gm_language"], 10), GM_LANG_no_open) . "<br /><br />";
							$output .= "</span>";
							$success = false;
						}
						if (@fwrite($handle,html_entity_decode($writetext)) === FALSE) {
							$output .= "<span class=\"Warning\">";
							$output .= str_replace("#lang_filename#", substr($language["gm_language"], 10), GM_LANG_lang_file_write_error) . "<br /><br />";
							$output .= "</span>";
							$success = false;
						}
					}
				}
				else {
					$output .= "<span class=\"Warning\">";
					$output .= str_replace("#lang_filename#", substr($language["gm_language"], 10), GM_LANG_no_open) . "<br /><br />";
					$output .= "</span>";
				}
				
				// Check help file
				if (file_exists($language["helptextfile"])) {
					if (filesize($language['helptextfile']) > 0) {
						$str = file_get_contents($language["helptextfile"]);
						if (ord($str{0}) == 239 && ord($str{1}) == 187 && ord($str{2}) == 191) {
							$check = true;
							$output .= "<span class=\"Warning\">".GM_LANG_bom_found.substr($language["helptextfile"], 10).".</span>";
							$output .= "<br />";
							$writetext = htmlentities(substr($str,3, strlen($str)));
							if (!$handle = @fopen($language["helptextfile"], "w")){
								$output .= "<span class=\"Warning\">";
								$output .= str_replace("#lang_filename#", substr($language["helptextfile"], 10), GM_LANG_no_open) . "<br /><br />";
								$output .= "</span>";
								$success = false;
							}
							if (@fwrite($handle,html_entity_decode($writetext)) === FALSE) {
								$output .= "<span class=\"Warning\">";
								$output .= str_replace("#lang_filename#", substr($language["helptextfile"], 10), GM_LANG_lang_file_write_error) . "<br /><br />";
								$output .= "</span>";
								$success = false;
							}
						}
					}
				}
				else {
					$output .= "<span class=\"Warning\">";
					$output .= str_replace("#lang_filename#", substr($language["helptextfile"], 10), GM_LANG_no_open) . "<br /><br />";
					$output .= "</span>";
				}
				// Check facts file
				if (file_exists($language["factsfile"])) {
					$str = file_get_contents($language["factsfile"]);
					if (ord($str{0}) == 239 && ord($str{1}) == 187 && ord($str{2}) == 191) {
						$check = true;
						$output .= "<span class=\"Warning\">".GM_LANG_bom_found.substr($language["factsfile"], 10).".</span>";
						$output .= "<br />";
						$writetext = htmlentities(substr($str,3, strlen($str)));
						if (!$handle = @fopen($language["factsfile"], "w")){
							$output .= "<span class=\"Warning\">";
							$output .= str_replace("#lang_filename#", substr($language["factsfile"], 10), GM_LANG_no_open) . "<br /><br />";
							$output .= "</span>";
							$success = false;
						}
						if (@fwrite($handle,html_entity_decode($writetext)) === FALSE) {
							$output .= "<span class=\"Warning\">";
							$output .= str_replace("#lang_filename#", substr($language["factsfile"], 10), GM_LANG_lang_file_write_error) . "<br /><br />";
							$output .= "</span>";
							$success = false;
						}
					}
				}
				else {
					$output .= "<span class=\"Warning\">";
					$output .= str_replace("#lang_filename#", substr($language["factsfile"], 10), GM_LANG_no_open) . "<br /><br />";
					$output .= "</span>";
				}
			}
		}
		if ($check == false) $output .= GM_LANG_bom_not_found;
		if ($text) return $output;
		else return $success;
	}
	
	public function GetGMFileList($dirs="", $recursive=false) {
		static $include_dirs, $recurse_dirs;
		
		if (!isset($include_dirs)) $include_dirs = array("blocks", "fonts", "hooks/logout", "images", "images/buttons", "images/flags", "images/media", "images/small", "includes", "includes/classes", "includes/controllers", "includes/functions", "includes/values", "index", "install", "install/images", "languages", "media", "media/thumbs", "places", "places/AUS", "places/AUT", "places/BEL", "places/BRA", "places/CAN", "places/CHE", "places/CZE", "places/DEU", "places/DNK", "places/ENG", "places/ESP", "places/FIN", "places/flags", "places/FRA", "places/GBR", "places/HUN", "places/IDN", "places/IND", "places/IRL", "places/ISR", "places/ITA", "places/KEN", "places/LBA", "places/NLD", "places/NOR", "places/NZL", "places/PAK", "places/POL", "places/PRT", "places/ROM", "places/RUS", "places/SCT", "places/SVK", "places/SWE", "places/TUR", "places/UKR", "places/USA", "places/WLS", "places/ZAF", "reports", "themes/clear", "themes/standard", "ufpdf");
//		if (!isset($include_dirs)) $include_dirs = array("places", "themes");

		$dirlist = array();
		if (!is_array($dirs)) $dirs = array($dirs);
	
		foreach ($dirs as $key=>$dir) {
			$d = dir(($dir == "" ? "includes/.." : $dir));
			if (is_object($d)) {
				while (false !== ($entry = $d->read())) {
					if ($entry != ".." && $entry != "." && $entry != ".svn") {
						$direntry = $dir.$entry."/";
						if(is_dir($direntry)) {
							if (in_array($dir.$entry, $include_dirs)) {
								$dirlist = array_merge($dirlist, self::GetGMFileList(array($direntry), true));
							}
						}
						else {
							print $dir.$entry."<br />";
							if ($entry != "md5_files.php") $dirlist[$dir.$entry] = md5_file($dir.$entry);
						}
					}
				}
				$d->close();
			}
		}
		if ($recursive) return $dirlist;
		@unlink(INDEX_DIRECTORY."md5_files.php");
		$handle = @fopen(INDEX_DIRECTORY."md5_files.php", "wb");
		if (!$handle) return false;
		fwrite($handle, "<"."?php\n");
		fwrite($handle, "if (preg_match(\"/\Wmd5_files.php/\", \$_SERVER[\"SCRIPT_NAME\"])>0) {\n");
		fwrite($handle, "\$INTRUSION_DETECTED = true;\n");
		fwrite($handle, "}\n");
		fwrite($handle, "// This file is for checking if the right files exist in a Genmod installation\n");
		fwrite($handle, "// It is created with the hidden function admin_maint.php?action=createmd5\n");
		fwrite($handle, "// It should be run on an existing, clean and freshly setup installation with files of the latest SVN version.\n\n");
		fwrite($handle, "\$md5_array = array();\n");
		foreach($dirlist as $file => $md5) {
			fwrite($handle, "\$md5_array[\"".addslashes($file)."\"] = \"".$md5."\";\n");
		}
		fwrite($handle, "?>\n");
		fclose($handle);
		return true;
	}
	
	public function CheckGMFileList() {
		
		require_once("includes/values/md5_files.php");
		$message = "";
		foreach($md5_array as $file => $md5) {
			$file = stripslashes($file);
			if (!file_exists($file)) {
				$message .= GM_LANG_file_absent."&nbsp;".$file."<br />";
			}
			elseif (md5_file($file) != $md5) {
				$message .= GM_LANG_wrong_md5."&nbsp;".$file."<br />";
			}
		}
		if (empty($message)) return GM_LANG_md5_ok;
		else return $message;
	}
	
	public function MakeTransTab($gedfile, $merge_ged) {
		
		$fp = fopen($gedfile, "r");
		if (!$fp) return false;
		//-- read the gedcom and test it in 8KB chunks
		SwitchGedcom($merge_ged);
		$lastindi = EditFunctions::GetNewXref("INDI");
		$lastindi = substr($lastindi,(strlen(GedcomConfig::$GEDCOM_ID_PREFIX)));
		$lastfam = EditFunctions::GetNewXref("FAM");
		$lastfam = substr($lastfam,(strlen(GedcomConfig::$FAM_ID_PREFIX)));
		$lastobje = EditFunctions::GetNewXref("OBJE");
		$lastobje = substr($lastobje,(strlen(GedcomConfig::$MEDIA_ID_PREFIX)));
		$lastsour = EditFunctions::GetNewXref("SOUR");
		$lastsour = substr($lastsour,(strlen(GedcomConfig::$SOURCE_ID_PREFIX)));
		$lastrepo = EditFunctions::GetNewXref("REPO");
		$lastrepo = substr($lastrepo,(strlen(GedcomConfig::$REPO_ID_PREFIX)));
		$lastnote = EditFunctions::GetNewXref("NOTE");
		$lastnote = substr($lastnote,(strlen(GedcomConfig::$NOTE_ID_PREFIX)));
		$inditab = array();
		$famtab = array();
		$objetab = array();
		$sourtab = array();
		$repotab = array();
		$notetab = array();
		$fcontents = "";
		$end = false;
		$pos1 = 0;
		while(!$end) {
			$pos2 = 0;
			// NOTE: find the start of the next record
			if (strlen($fcontents) > $pos1+1) $pos2 = strpos($fcontents, "\n0", $pos1+1);
			while((!$pos2)&&(!feof($fp))) {
				$fcontents .= fread($fp, 1024*8);
				$pos2 = strpos($fcontents, "\n0", $pos1+1);
			}
	
			//-- pull the next record out of the file
			if ($pos2) $indirec = substr($fcontents, $pos1, $pos2-$pos1);
			else $indirec = substr($fcontents, $pos1);

			// Get all xrefs
			$ct = preg_match_all("/\n\d\s+@(.*)@\s(\w*)/", $indirec, $match);
			if ($ct>0) {
				for ($i=0;$i<$ct;$i++) {
					$match[2][$i] = trim($match[2][$i]);
					// print "Handling ".$match[1][$i]." at ".$pos1."<br />";
					switch ($match[2][$i]) {
						case "INDI": 
							$inditab[$match[1][$i]] = GedcomConfig::$GEDCOM_ID_PREFIX.$lastindi;
							$lastindi++;
							break;
						case "FAM":
							$famtab[$match[1][$i]] = GedcomConfig::$FAM_ID_PREFIX.$lastfam;
							$lastfam++;
							break;
						case "OBJE":
							$objetab[$match[1][$i]] = GedcomConfig::$MEDIA_ID_PREFIX.$lastobje;
							$lastobje++;
							break;
						case "SOUR":
							$sourtab[$match[1][$i]] = GedcomConfig::$SOURCE_ID_PREFIX.$lastsour;
							$lastsour++;
							break;
						case "REPO":
							$repotab[$match[1][$i]] = GedcomConfig::$REPO_ID_PREFIX.$lastrepo;
							$lastrepo++;
							break;
						case "NOTE":
							$notetab[$match[1][$i]] = GedcomConfig::$NOTE_ID_PREFIX.$lastnote;
							$lastnote++;
							break;
						default:
							// print "Not found type: ".$match[2][$i]." ID: ".$match[1][$i]." <br /><br />";
							// ignored
							break;
					}
				}
			}
			//-- move the cursor to the start of the next record
			$pos1 = 0;
			$fcontents = substr($fcontents, $pos2);
			if ($pos2 == 0 && feof($fp)) $end = true;
		}
		fclose($fp);
		$transtab = array();
		$transtab["INDI"] = $inditab;
		$transtab["FAM"] = $famtab;
		$transtab["OBJE"] = $objetab;
		$transtab["SOUR"] = $sourtab;
		$transtab["REPO"] = $repotab;
		$transtab["NOTE"] = $notetab;
		@unlink(INDEX_DIRECTORY."transtab.txt");
		$fp = fopen(INDEX_DIRECTORY."transtab.txt", "wb");
		if ($fp) {
			fwrite($fp, serialize($transtab));
			fclose($fp);
		}
		return true;
	}		

	private function GetLangVarString($var, $value, $type) {
	
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
	
	public function AdminLink ($link, $text, $help="", $helpText="", $show_desc="") {
		
		print "<div class=\"AdminColumnLeftLink\">";
		if (!empty($help)) PrintHelpLink($help, $helpText, $show_desc);
		print "<a href=\"".$link."\">".$text."</a>";
		print "</div>";
	}
}
?>