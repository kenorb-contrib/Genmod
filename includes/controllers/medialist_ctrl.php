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

class MediaListController {
	
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
			default:
				print "<span class=\"error\">Invalid property ".$property." for __get in ".get_class($this)." class</span><br />";
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
		global $GEDCOMID;
		$found = 0;
		$added = 0;
		// sort the data on title, if absent on the filename with heading . and / stripped.
		if ($count == 0) $sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' ORDER BY k";
		else $sql = "SELECT * FROM ".TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' ORDER BY RAND() LIMIT ".$count;
		$db = NewQuery($sql);
		$this->totalmediaitems = $db->NumRows();
		while($row = $db->FetchAssoc()) {
			$media = MediaItem::GetInstance($row["m_media"], $row);
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
	}
		
	public function RetrieveFilterMedia($filter, $start=0, $max=0) {
		global $GEDCOMID;
		$found = 0;
		$added = 0;
		$t = 1;
		if ($t == 1) {
		$sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' AND m_media IN
		(SELECT mm_media FROM ".TBLPREFIX."media_mapping WHERE mm_gid IN
			(SELECT n_gid FROM ".TBLPREFIX."names WHERE n_name LIKE '%".$filter."%' AND n_file = '".$GEDCOMID."'
			UNION
			SELECT f_id FROM ".TBLPREFIX."families f
			JOIN ".TBLPREFIX."names i
			ON f_husb = n_key
			WHERE f.f_file = '".$GEDCOMID."'
			AND i.n_file = '".$GEDCOMID."'
			AND n_name LIKE '%".$filter."%'
			UNION
			SELECT f_id FROM ".TBLPREFIX."families f
			JOIN ".TBLPREFIX."names i
			ON f_wife = n_key
			WHERE f.f_file = '".$GEDCOMID."'
			AND i.n_file = '".$GEDCOMID."'
			AND n_name LIKE '%".$filter."%'
			UNION
			SELECT s_id FROM ".TBLPREFIX."sources WHERE s_name LIKE '%".$filter."%' AND s_file = '".$GEDCOMID."')
			AND mm_gedfile = '".$GEDCOMID."'
		) 
		OR
		((m_titl LIKE '%".$filter."%' OR m_gedrec LIKE '%".$filter."%') AND m_gedfile = '".$GEDCOMID."')
		ORDER BY k";
		}
		else {
		$sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' AND m_media IN
		(SELECT mm_media FROM ".TBLPREFIX."media_mapping WHERE CONCAT(mm_gid,'[".$GEDCOMID."]') IN
			(SELECT n_key FROM ".TBLPREFIX."names WHERE n_name LIKE '%".$filter."%' AND n_file = '".$GEDCOMID."'
			UNION
			SELECT if_fkey FROM ".TBLPREFIX."names 
			JOIN ".TBLPREFIX."individual_family 
			ON if_pkey = n_key  
			WHERE n_name LIKE '%".$filter."%' AND if_role='S' AND n_file='".$GEDCOMID."'
			UNION
			SELECT CONCAT(s_id,'[".$GEDCOMID."]') FROM ".TBLPREFIX."sources WHERE s_name LIKE '%".$filter."%' AND s_file = '".$GEDCOMID."')
			AND mm_gedfile = '".$GEDCOMID."'
		) 
		OR
		((m_titl LIKE '%".$filter."%' OR m_gedrec LIKE '%".$filter."%') AND m_gedfile = '".$GEDCOMID."')
		ORDER BY k";
		}
		$db = NewQuery($sql);
		$this->totalmediaitems = $db->NumRows();
		while($row = $db->FetchAssoc()) {
			$media =& MediaItem::GetInstance($row["m_media"], $row);
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
	}

	public function RetrieveFilterMediaList($filter) {
		global $GEDCOMID;
		$sql = "SELECT *, concat(m_titl, if(substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),1,1)='/',substr(if(substr(m_file,1,1)='.',substr(m_file,2),m_file),2),if(substr(m_file,1,1)='.',substr(m_file,2),m_file))) as k FROM ".TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."'";
		if (!empty($filter)) $sql .= " AND (m_titl LIKE '%".$filter."%' OR m_file LIKE '%".$filter."%')";
		$sql .= " ORDER BY k";
		$db = NewQuery($sql);
		$this->totalmediaitems = $db->NumRows();
		while($row = $db->FetchAssoc()) {
			$media =& MediaItem::GetInstance($row["m_media"], $row);
			if ($media->disp) {
				$this->medialist[$row["m_media"]."_".$row["m_gedfile"]] = $media;
			}
		}
		$this->mediainlist = count($this->medialist);
		$db->FreeResult();
	}
}
?>