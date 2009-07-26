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

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Media {
	
	public $classname = "Media";	// Name of this class
	
	private $totalmediaitems = 0;	// Total media items found, before privacy
	private $medialist = array();	// Array of media items found
	private $mediainlist = 0;		// Total media items returned after applying privacy
	
	public function __get($property) {
		switch ($property) {
			case "totalmediaitems":
				return $this->totalmediaitems;
				break;
			case "medialist":
				return $this->medialist;
				break;
			case "mediainlist":
				return $this->mediainlist;
				break;
			case "lastitem":
				return end($this->medialist);
				break;
		}
	}
	/*
	** Retrieve the medialist including all linked records.
	** Privacy is applied
	** @param $count	return <count> random images
	** @param $start	which item to start with (counted after privacy is applied)
	** @param $max		how many max to return (counted after privacy is applied
	*/	
	public function RetrieveMedia($count=0, $start=0, $max=0) {
		global $TBLPREFIX, $GEDCOMID;
		$found = 0;
		$added = 0;
		// sort the data on title, if absent on the filename with heading . and / stripped.
		if ($count == 0) $sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' ORDER BY k";
		else $sql = "SELECT * FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' ORDER BY RAND() LIMIT ".$count;
		$db = NewQuery($sql);
		$this->totalmediaitems = $db->NumRows();
		while($row = $db->FetchAssoc()) {
			$media =& MediaItem::GetInstance($row);
			if ($media->disp) {
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
		
	public function RetrieveFilterMedia($filter, $start=0, $max=0) {
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
			$media =& MediaItem::GetInstance($row);
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

	public function RetrieveFilterMediaList($filter) {
		global $GEDCOMID, $TBLPREFIX;
		$sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."'";
		if (!empty($filter)) $sql .= " AND (m_titl LIKE '%".$filter."%' OR m_file LIKE '%".$filter."%')";
		$sql .= " ORDER BY k";
		$db = NewQuery($sql);
		$this->totalmediaitems = $db->NumRows();
		while($row = $db->FetchAssoc()) {
			$media =& MediaItem::GetInstance($row);
			if ($media->disp) {
				$this->medialist[$row["m_media"]."_".$row["m_gedfile"]] = $media;
			}
		}
		$this->mediainlist = count($this->medialist);
		$db->FreeResult();
	}

	
	//-- search through the gedcom records for media, full text
	public function FTSearchMedia($query, $allgeds=false, $ANDOR="AND") {
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
	private function RetrieveMediaLink() {
		global $TBLPREFIX, $GEDCOMID, $LINK_PRIVACY;
		if (count($this->medialist) > 0) {
			$sql = "SELECT * FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$GEDCOMID."' AND mm_media in (";
			foreach($this->medialist as $key => $media) {
				$sql .= "'".$media->xref."',";
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
	
//	function mediasort($a, $b) {
//		if (!empty($a->title)) $atitl = $a->title;
//		else $atitl = $a->filename;
//		if (!empty($b->title)) $btitl = $b->title;
//		else $btitl = $b->filename;
//		return strnatcasecmp($atitl, $btitl);
//	}
}
?>