<?php
/**
 * Class file for media objects
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

include_once("mime_type_detect.class.php");
 
class Media {
	
	var $classname = "media";
	var $totalmediaitems = 0;
	var $medialist = array();
	var $mediainlist = 0;
	
	function CountMediaItems() {
		return $this->totalmediaitems;
	}
	/*
	** Retrieve the medialist including all linked records.
	** Privacy is applied
	** @param $count	return <count> random images
	** @param $start	which item to start with (counted after privacy is applied)
	** @param $max		how many max to return (counted after privacy is applied
	*/	
	function RetrieveMedia($count=0, $start=0, $max=0) {
		global $TBLPREFIX, $GEDCOMID;
		$found = 0;
		$added = 0;
		// sort the data on title, if absent on the filename with heading . and / stripped.
		if ($count == 0) $sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' ORDER BY k";
		else $sql = "SELECT * FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' ORDER BY RAND() LIMIT ".$count;
		$db = NewQuery($sql);
		$this->totalmediaitems = $db->NumRows();
		while($row = $db->FetchAssoc()) {
			$media = new MediaItem($row);
			if ($media->disp == true) {
				if ($count) {
					$this->medialist[$row["m_media"]."_".$row["m_gedfile"]] = $media;
					$added++;
					if ($added == $max) break;
				}
				else {	
					$found++;
					if ($found > $start) {
						$this->medialist[$row["m_media"]."_".$row["m_gedfile"]] = $media;
						$added++;
					}
					if ($max != 0 && $added == $max+1) break;
				}
			}
		}
		$this->mediainlist = count($this->medialist);
		$db->FreeResult();
		$this->RetrieveMediaLink();
	}
		
	function RetrieveFilterMedia($filter, $start=0, $max=0) {
		global $GEDCOMID, $TBLPREFIX;
		$found = 0;
		$added = 0;
		$t = 1;
		if ($t == 1) {
		$sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' AND m_media IN
		(SELECT mm_media FROM ".$TBLPREFIX."media_mapping WHERE mm_gid IN
			(SELECT n_gid FROM ".$TBLPREFIX."names WHERE n_name LIKE '%".$filter."%' AND n_file = '".$GEDCOMID."'
			UNION
			SELECT f_id FROM ".$TBLPREFIX."families f
			JOIN ".$TBLPREFIX."names i
			ON f_husb = n_key
			WHERE f.f_file = '".$GEDCOMID."'
			AND i.n_file = '".$GEDCOMID."'
			AND n_name LIKE '%".$filter."%'
			UNION
			SELECT f_id FROM ".$TBLPREFIX."families f
			JOIN ".$TBLPREFIX."names i
			ON f_wife = n_key
			WHERE f.f_file = '".$GEDCOMID."'
			AND i.n_file = '".$GEDCOMID."'
			AND n_name LIKE '%".$filter."%'
			UNION
			SELECT s_id FROM ".$TBLPREFIX."sources WHERE s_name LIKE '%".$filter."%' AND s_file = '".$GEDCOMID."')
			AND mm_gedfile = '".$GEDCOMID."'
		) 
		OR
		((m_titl LIKE '%".$filter."%' OR m_gedrec LIKE '%".$filter."%') AND m_gedfile = '".$GEDCOMID."')
		ORDER BY k";
		}
		else {
		$sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' AND m_media IN
		(SELECT mm_media FROM ".$TBLPREFIX."media_mapping WHERE CONCAT(mm_gid,'[".$GEDCOMID."]') IN
			(SELECT n_key FROM ".$TBLPREFIX."names WHERE n_name LIKE '%".$filter."%' AND n_file = '".$GEDCOMID."'
			UNION
			SELECT if_fkey FROM ".$TBLPREFIX."names 
			JOIN ".$TBLPREFIX."individual_family 
			ON if_pkey = n_key  
			WHERE n_name LIKE '%".$filter."%' AND if_role='S' AND n_file='".$GEDCOMID."'
			UNION
			SELECT CONCAT(s_id,'[".$GEDCOMID."]') FROM ".$TBLPREFIX."sources WHERE s_name LIKE '%".$filter."%' AND s_file = '".$GEDCOMID."')
			AND mm_gedfile = '".$GEDCOMID."'
		) 
		OR
		((m_titl LIKE '%".$filter."%' OR m_gedrec LIKE '%".$filter."%') AND m_gedfile = '".$GEDCOMID."')
		ORDER BY k";
		}
		$db = NewQuery($sql);
		$this->totalmediaitems = $db->NumRows();
		while($row = $db->FetchAssoc()) {
			$media = new MediaItem($row);
			if ($media->disp) {
				$found++;
				if ($found > $start) {
					$this->medialist[$row["m_media"]."_".$row["m_gedfile"]] = $media;
					$added++;
				}
				if ($max != 0 && $added == $max+1) break;
			}
		}
		$this->mediainlist = count($this->medialist);
		$db->FreeResult();
		$this->RetrieveMediaLink();
	}

	function RetrieveFilterMediaList($filter) {
		global $GEDCOMID, $TBLPREFIX;
		$sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."'";
		if (!empty($filter)) $sql .= " AND (m_titl LIKE '%".$filter."%' OR m_file LIKE '%".$filter."%')";
		$sql .= " ORDER BY k";
		$db = NewQuery($sql);
		$this->totalmediaitems = $db->NumRows();
		while($row = $db->FetchAssoc()) {
			$media = new MediaItem($row);
			if ($media->disp) {
				$this->medialist[$row["m_media"]."_".$row["m_gedfile"]] = $media;
			}
		}
		$db->FreeResult();
	}

	
	//-- search through the gedcom records for media, full text
	function FTSearchMedia($query, $allgeds=false, $ANDOR="AND") {
		global $TBLPREFIX, $GEDCOM, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID, $ftminwlen, $ftmaxwlen, $media_hide, $media_total;
		
		// Get the min and max search word length
		GetFTWordLengths();
	
		$cquery = ParseFTSearchQuery($query);
		$addsql = "";
		
		$mlen = GetFTMinLen($cquery);
		
		if ($mlen < $ftminwlen || HasMySQLStopwords($cquery)) {
			if (isset($cquery["includes"])) {
				foreach ($cquery["includes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " ".$keyword["operator"]." m_gedrec REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " ".$keyword["operator"]." m_gedrec REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			if (isset($cquery["excludes"])) {
				foreach ($cquery["excludes"] as $index => $keyword) {
					if (HasChinese($keyword["term"])) $addsql .= " AND m_gedrec NOT REGEXP '".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."'";
					else $addsql .= " AND m_gedrec NOT REGEXP '[[:<:]]".$DBCONN->EscapeQuery($keyword["term"]).$keyword["wildcard"]."[[:>:]]'";
				}
			}
			$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE (".substr($addsql,4).")";
		}
		else {
			$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE (MATCH (m_gedrec) AGAINST ('".$DBCONN->EscapeQuery($query)."' IN BOOLEAN MODE))";
		}
	
		if (!$allgeds) $sql .= " AND m_gedfile='".$GEDCOMID."'";
		
		if ((is_array($allgeds) && count($allgeds) != 0) && count($allgeds) != count($GEDCOMS)) {
			$sql .= " AND (";
			for ($i=0; $i<count($allgeds); $i++) {
				$sql .= "m_gedfile='".$DBCONN->EscapeQuery($GEDCOMS[$allgeds[$i]]["id"])."'";
				if ($i < count($allgeds)-1) $sql .= " OR ";
			}
			$sql .= ")";
		}
	
		$media_total = array();
		$media_hide = array();
		$res = NewQuery($sql);
		if ($res) {
	 		while ($row = $res->FetchAssoc()) {
		 		$media = array();
		 		SwitchGedcom($row["m_gedfile"]);
				$media_total[$row["m_media"]."[".$GEDCOM."]"] = 1;
		 		if (DisplayDetailsByID($row["m_media"], "OBJE", 1, true)) {
					$media = array();
					$media["gedfile"] = $GEDCOMID;
					$media["name"] = GetMediaDescriptor($row["m_media"], $row["m_gedrec"]);
					$this->medialist[JoinKey($row["m_media"], $row["m_gedfile"])] = $media;
	 			}
		 		else $media_hide[$row["m_media"]."[".$GEDCOM."]"] = 1;
		 		SwitchGedcom();
	 		}
		}
	}
	
	/* Retrieves the medialinks for the items.
	** If link privacy is on, privacy is already checked 2 levels deep	
	** If off, the linked items are checked
	*/
	function RetrieveMediaLink() {
		global $TBLPREFIX, $GEDCOMID, $LINK_PRIVACY;
		if (count($this->medialist) > 0) {
			$sql = "SELECT * FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$GEDCOMID."' AND mm_media in (";
			foreach($this->medialist as $key => $media) {
				$sql .= "'".$media->m_media."',";
			}
			$sql = substr($sql, 0, -1);
			$sql .= ")";
			$res = NewQuery($sql);
			while($row = $res->FetchAssoc()){
				$type = $row["mm_type"];
				// This will hide the links if the media item can be shown (link privacy off)
				// if link privacy is on, the item will not show at all
				if (!$LINK_PRIVACY || (DisplayDetailsByID($row["mm_gid"], $type) && ShowFactDetails("OBJE", $row["mm_gid"]))) {
					$this->medialist[stripslashes($row["mm_media"])."_".$row["mm_gedfile"]]->links[stripslashes($row["mm_gid"])] = $type;
					$this->medialist[stripslashes($row["mm_media"])."_".$row["mm_gedfile"]]->linked = true;
				}
			}
		}
	}
	function mediasort($a, $b) {
		if (!empty($a->m_titl)) $atitl = $a->m_titl;
		else $atitl = $a->m_file;
		if (!empty($b->m_titl)) $btitl = $b->m_titl;
		else $btitl = $b->m_file;
		return strnatcasecmp($atitl, $btitl);
	}
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

	function MediaItem($details, $changed = false) {
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