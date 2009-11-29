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
		global $gm_lang;
	
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
		if	($handle)	{
			$i = fclose($handle);
			$err_write = true;
		}
		return($err_write);
	}
	
	public function PrintGedcom($ged, $convert, $remove, $zip, $privatize_export, $privatize_export_level, $gedname, $embedmm) {
		GLOBAL $GEDCOMID, $gm_lang, $GM_BASE_DIRECTORY, $gm_username, $gm_user;
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
		$head = FindGedcomRecord("HEAD");
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
				$head .= "1 PLAC\r\n2 FORM ".$gm_lang["default_form"]."\r\n";
			}
		}
		else {
			$head = "0 HEAD\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS ".GM_VERSION." ".GM_VERSION_RELEASE."\r\n1 DEST DISKETTE\r\n1 DATE ".date("j M Y")."\r\n2 TIME ".date("h:i:s")."\r\n";
			$head .= "1 GEDC\r\n2 VERS 5.5\r\n2 FORM LINEAGE-LINKED\r\n1 CHAR ".GedcomConfig::$CHARACTER_SET."\r\n1 PLAC\r\n2 FORM ".$gm_lang["default_form"]."\r\n";
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
			if (($listitem["status"]="ok") && (strstr(strtolower($listitem["filename"]), ".")==".ged")) {
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
								if ($subrec != $newrec) ReplaceGedrec($gedid, $subrec, $newrec, "EMAIL", $change_id, "edit_fact", $GEDCOMID, "INDI");
							}
						}
						if (!$found) ReplaceGedrec($gedid, "", "1 EMAIL ".$user->email."\r\n2 RESN privacy\r\n2 SOUR ".$sourstring."\r\n", "EMAIL", $change_id, "add_fact", $GEDCOMID, "INDI");
					}
				}
			}
			$GEDCOMID = $oldged;
		}
	}
}
?>