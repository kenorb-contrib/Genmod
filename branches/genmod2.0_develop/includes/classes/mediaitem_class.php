<?php
/**
 * Class file for media item objects
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
 * @subpackage DataModel
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class MediaItem extends Media {
	
	var $m_id = 0;
	var $m_media = "";
	var $m_ext = "";
	var $m_titl = ""; 
	var $m_file = "";
	var $m_gedfile = 0;
	var $m_gedrec = "";
	var $m_level = 0;
	var $links = array();
	var $linked = false;
	var $showchanges = false;
	var $mediadeleted = false;
	var $title = "";
	var $disp = false;
	var $medianew = false;
	var $mediachanged = false;

	public function __construct($details, $changed = false) {
		global $TBLPREFIX, $GEDCOMID, $gm_username, $GEDCOM, $Users, $MEDIA_IN_DB, $SERVER_URL, $MediaFS, $show_changes, $MEDIA_DIRECTORY;
		
		$mimetypedetect = new MimeTypeDetect();
		if ((!isset($show_changes) || $show_changes != "no" || $changed) && $Users->UserCanEdit($gm_username)) {
			$this->showchanges = true;
			if (is_array($details)) $mm = $details["m_media"];
			else $mm = $details;
			if (GetChangeData(true, $mm, true)) $this->mediachanged = true;
		}
		
		// We only have an ID. Find some info!
		if (!is_array($details)) {
			// Get all from the DB
			$media_id = $details;
			$sql = "SELECT * FROM ".$TBLPREFIX."media where m_media = '".$media_id."' AND m_gedfile = '".$GEDCOMID."'";
			$res = NewQuery($sql);
			if ($res->NumRows() > 0) $details = $res->FetchAssoc($res);
			else {
				// we must build the record
				$this->m_media = $details;
				$details = array();
				$details["m_media"] = $this->m_media;
				$details["m_gedrec"] = FindMediaRecord($this->m_media);
				$details["m_id"] = 0;
				$details["m_gedfile"] = $GEDCOMID;
			}
			if (!$changed) {
				$details["m_file"] = GetGedcomValue("FILE", 1, $details["m_gedrec"]);
			}
		}
		if (is_array($details)) {
			// -- Fill some more values
			// is changed?
			// initial gedcom record if empty
			if (empty($details["m_gedrec"])) {
				$details["m_gedrec"] = "0 OBJE @".$this->m_media."@\r\n";
				$this->medianew = true;
			}
			// changed gedcom record if present and can display
			if ($this->mediachanged && $this->showchanges) {
				// We really want the new thumb and title in the header!
				$rec = GetChangeData(false, $details["m_media"], true, "gedlines", "");
				$newgedrec = $rec[$GEDCOM][$details["m_media"]];
				$details["m_titl"] = GetMediaDescriptor($details["m_media"], $newgedrec);
				$details["m_file"] = GetGedcomValue("FILE", 1, $newgedrec);
			}
			else {
				$details["m_file"] = GetGedcomValue("FILE", 1, $details["m_gedrec"]);
				$details["m_titl"] = GetMediaDescriptor($details["m_media"], $details["m_gedrec"]);
			}
			// extension
			$et = preg_match("/(\.\w+)$/", $details["m_file"], $ematch);
			$details["m_ext"] = "";
			if ($et>0) $details["m_ext"] = substr(trim($ematch[1]),1);
			$this->m_id = $details["m_id"];
			$this->m_media = $details["m_media"];
			$this->m_ext = $details["m_ext"];
			$this->m_titl = trim($details["m_titl"]);
			$this->m_file = RelativePathFile(FilenameDecode($MediaFS->CheckMediaDepth($details["m_file"])));
			if (empty($this->m_titl)) $this->m_titl = $details["m_file"];
			$this->m_gedfile = $details["m_gedfile"];
			$this->m_gedrec = $details["m_gedrec"];
			$this->m_level = substr(trim($this->m_gedrec), 0, 1);
//			print "Generating object for ".$this->m_file."<br />";
			if (stristr($this->m_file, "://")) $this->m_fileobj = new MFile($this->m_file);
			else $this->m_fileobj = new MFile($MEDIA_DIRECTORY.$this->m_file);
			$this->disp = displayDetailsByID($this->m_media, "OBJE", 2, true);
//			print "mfile: ".$this->m_file."<br />";
//			print_r($this->m_fileobj);
			if ($this->showchanges && GetChangeData(true, $this->m_media, true, "", "")) {
				$rec = GetChangeData(false, $this->m_media, true, "gedlines", "");
				if (empty($rec[$GEDCOM][$this->m_media])) $this->mediadeleted = true;
			}
		}
	}
	
	/**
	 * Check if privacy options allow this record to be displayed
	 * @return boolean
	 */
	function canDisplayDetails() {
		return $this->disp;
	}

	function getTitle() {
		return $this->m_titl;
	}
	
		/**
	 * get media facts array
	 * @return array
	 */
	function getMediaFacts() {
		$this->parseFacts();
		SortFacts($this->mediafacts, "OBJE");
		return $this->mediafacts;
	}
	
	/**
	 * Parse the facts from the individual record
	 */
	function parseFacts() {
		if (isset($this->mediafacts)) return;
		$this->mediafacts = array();
		// No privacy on factlevel here. This is done at print-level.
		$this->allmediasubs = GetAllSubrecords($this->m_gedrec, "", true, false, false);
		foreach ($this->allmediasubs as $key => $subrecord) {
			$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
			if ($ft>0) {
				$fact = $match[1];
				$gid = trim(str_replace("@", "", $match[2]));
			}
			else {
				$fact = "";
				$gid = "";
			}
			$fact = trim($fact);
			if (!isset($count[$fact])) $count[$fact] = 1;
			else $count[$fact]++;
			$this->mediafacts[] = array($fact, $subrecord, $count[$fact]);
		}
		$newrecs = RetrieveNewFacts($this->m_media);
		foreach($newrecs as $key=> $newrec) {
			$ft = preg_match("/1\s(\w+)(.*)/", $newrec, $match);
			if ($ft>0) {
				$fact = $match[1];
				$gid = trim(str_replace("@", "", $match[2]));
			}
			else {
				$fact = "";
				$gid = "";
			}
			$fact = trim($fact);
			if (!isset($count[$fact])) $count[$fact] = 1;
			else $count[$fact]++;
			$this->mediafacts[] = array($fact, $newrec, $count[$fact], "new");
		}
	}
	/**
	 * get the list of individuals connected to this MM object
	 * @return array
	 */
	function getMediaIndis() {
		global $GEDCOMID;
		
		if (isset($this->indilist)) return $this->indilist;
		$links = $this->GetMediaLinks($this->m_media, "INDI");
		if (is_array($links) && count($links) > 0) {
			$links = implode("[".$GEDCOMID."]','", $links);
			$links .= "[".$GEDCOMID."]'";
			$links = "'".$links;
			$this->indilist = GetIndiList("", $links, true);
			uasort($this->indilist, "ItemSort");
		}
		else $this->indilist = array();
		return $this->indilist;
		
	}
	
	/**
	 * get the list of families connected to this MM object
	 * @return array
	 */
	function getMediaFams() {
		global $REGEXP_DB, $GEDCOMID;
		
		if (isset($this->famlist)) return $this->famlist;
		$links = $this->GetMediaLinks($this->m_media, "FAM");
		if (is_array($links) && count($links) > 0) {
			$links = implode("[".$GEDCOMID."]','", $links);
			$links .= "[".$GEDCOMID."]'";
			$links = "'".$links;
			$this->famlist = GetFamList("", $links);
			uasort($this->famlist, "ItemSort");
		}
		else $this->famlist = array();
		return $this->famlist;
	}

	/**
	 * get the list of sources connected to this MM object
	 * @return array
	 */
	function getMediaSources() {
		global $REGEXP_DB;
		if (isset($this->sourcelist)) return $this->sourcelist;
		$links = $this->GetMediaLinks($this->m_media, "SOUR");
		if (is_array($links) && count($links) > 0) {
			$links = implode("','", $links);
			$links .= "'";
			$links = "'".$links;
			$this->sourcelist = GetSourceList($links);
			uasort($this->sourcelist, "SourceSort");
		}
		else $this->sourcelist = array();
		return $this->sourcelist;
	}
	
	/**
	 * get the list of repositories connected to this MM object
	 * @return array
	 */
	function getMediaRepos() {
		global $REGEXP_DB;
		if (isset($this->repolist)) return $this->repolist;
		$links = $this->GetMediaLinks($this->m_media, "REPO");
		if (is_array($links) && count($links) > 0) {
			$links = implode("','", $links);
			$links .= "'";
			$links = "'".$links;
			$this->repolist = GetRepoList("", $links);
		}
		else $this->repolist = array();
		return $this->repolist;
	}

	/** Get the ID's linked to this media
	*/
	function GetMediaLinks($pid, $type="", $applypriv=true) {
		global $TBLPREFIX, $GEDCOMID;
	
		if (empty($pid)) return false;

		$links = array();	
		$sql = "SELECT mm_gid FROM ".$TBLPREFIX."media_mapping WHERE mm_media='".$pid."'";
		if (!empty($type)) $sql .= " AND mm_type='".$type."'";
		$sql .= " AND mm_gedfile='".$GEDCOMID."'";
		$res = NewQuery($sql);
		while($row = $res->FetchRow()){
			if (!$applypriv) {
				$links[] = $row[0];
			}
			else {
				if (ShowFact("OBJE", $row[0], $type)) {
					$links[] = $row[0];
				}
			}
		}
		return $links;
	}
	
}
?>