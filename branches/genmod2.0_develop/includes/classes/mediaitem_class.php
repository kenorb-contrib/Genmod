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
// changed gedrec ok
class MediaItem extends GedcomRecord {
	
	public $classname = "MediaItem";
	public $datatype = "OBJE";
	
	private $extension = null;
	private $title = null; 
	private $filename = null;
	private $level = 0;
	private $validmedia = null;
	public $fileobj = null;
	
	public $links = array(); // set in media class
	public $linked = false; // set in media class

	public function __construct($details, $gedrec="", $gedcomid="", $changed = false) {
		global $TBLPREFIX, $GEDCOMID, $MediaFS, $MEDIA_DIRECTORY;
		
		if (is_array($details)) parent::__construct($details["m_media"], $details["m_gedrec"], $details["m_gedfile"]);
		else parent::__construct($details, $gedrec, $gedcomid);
	
		$mimetypedetect = new MimeTypeDetect();
		
		if (is_array($details)) {
			// extension
			$this->extension = $details["m_ext"];
			$t = trim($details["m_titl"]);
			if (!empty($t) && $this->disp) $this->title = $t;
			if ($this->show_changes && $this->ThisChanged()) $this->filename = RelativePathFile(FilenameDecode($MediaFS->CheckMediaDepth(GetGedcomValue("FILE", 1, $this->GetChangedGedrec()))));
			else $this->filename = RelativePathFile(FilenameDecode($MediaFS->CheckMediaDepth($details["m_file"])));
		}
		else {
			if ($this->show_changes && $this->ThisChanged()) $this->filename = RelativePathFile(FilenameDecode($MediaFS->CheckMediaDepth(GetGedcomValue("FILE", 1, $this->GetChangedGedrec()))));
			else $this->filename = RelativePathFile(FilenameDecode($MediaFS->CheckMediaDepth(GetGedcomValue("FILE", 1,$this->gedrec))));
			
		}
		if (stristr($this->filename, "://")) $this->fileobj = new MFile($this->filename);
		else $this->fileobj = new MFile($MEDIA_DIRECTORY.$this->filename);
	}
	

	public function __get($property) {
		switch ($property) {
			case "title":
				return $this->getTitle();
				break;
			case "filename":
				return $this->filename;
				break;
			case "extension":
				return $this->getExtension();
				break;
			case "level":
				return $this->getLevel();
				break;
			case "validmedia":
				return $this->IsValidMedia();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	private function getTitle() {
		global $gm_lang;
		
		if (is_null($this->title)) {
			if ($this->disp) {
				if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;

				if (!ShowFactDetails("TITL", $this->xref, "OBJE") || !ShowFactDetails("FILE", $this->xref, "OBJE")) $this->title = $gm_lang["private"];
				else if (!ShowFact("TITL", $this->xref, "OBJE")) $this->title = $gm_lang["unknown"];
				else $this->title = $this->GetMediaDescriptor();
			}
			else $this->title = $gm_lang["private"];
		}
		return $this->title;
	}

	private function GetMediaDescriptor() {
		
		if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;
		
		$title = GetGedcomValue("TITL", 1, $gedrec);
		if (empty($title)) $title = GetGedcomValue("FILE:TITL", 1, $gedrec);
		if (empty($title)) $title = GetGedcomValue("FILE", 1, $gedrec);
		else $title = stripslashes($title);
		return $title;
	}
		
	private function getExtension() {
		
		if (is_null($this->extension)) {
			$et = preg_match("/(\.\w+)$/", $this->filename, $ematch);
			if ($et>0) $this->extension =  substr(trim($ematch[1]),1);
			else $this->extension = "";
		}
		return $this->extension;
	}
	
	private function getLevel() {
		if (is_null($this->level)) {
			if ($this->show_changes && $this->ThisChanged()) $this->level = substr(trim($this->GetChangedGedrec()), 0, 1);
			else $this->level = substr(trim($this->gedrec), 0, 1);
		}
		return $this->level;
	}

	private function IsValidMedia() {
		global $MEDIATYPE;
		
		if (is_null($this->validmedia)) {
			$this->validmedia = in_array(strtolower($this->GetExtension()), $MEDIATYPE);
		}
		return $this->validmedia;
	}
				
	/**
	 * get the list of individuals connected to this MM object
	 * @return array
	 */
	protected function GetLinksFromIndis() {
		global $TBLPREFIX, $indilist;

		if (!is_null($this->indilist)) return $this->indilist;
		$this->indilist = array();
		$this->indi_hide = 0;
		if (!isset($indilist)) $indilist = array();
		
		$sql = "SELECT DISTINCT i_key, i_gedcom, i_isdead, i_id, i_file  FROM ".$TBLPREFIX."media_mapping, ".$TBLPREFIX."individuals WHERE mm_media='".$this->xref."' AND mm_gedfile='".$this->gedcomid."' AND mm_type='INDI' AND mm_gid=i_id AND mm_gedfile=i_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$indi = array();
			$indi["gedcom"] = $row["i_gedcom"];
			$indi["isdead"] = $row["i_isdead"];
			$indi["gedfile"] = $row["i_file"];
			$indilist[$row["i_id"]] = $indi;
			$person = null;
			$person = new Person($row["i_id"], $row["i_gedcom"]);
			if ($person->disp_name) {
				$indilist[$row["i_id"]]["names"] = $person->name_array;
				$this->indilist[$row["i_key"]] = $person;
			}
			else $this->indi_hide++;
		}
		uasort($this->indilist, "ItemObjSort");
		$this->indi_count=count($this->indilist);
		return $this->indilist;
	}
	
	/**
	 * get the list of families connected to this MM object
	 * @return array
	 */
	protected function GetLinksFromFams() {
		global $TBLPREFIX, $famlist;

		if (!is_null($this->famlist)) return $this->famlist;
		$this->famlist = array();
		$this->fam_hide = 0;
		if (!isset($famlist)) $famlist = array();
		
		$sql = "SELECT DISTINCT f_key, f_gedcom, f_id, f_file, f_husb, f_wife  FROM ".$TBLPREFIX."media_mapping, ".$TBLPREFIX."families WHERE mm_media='".$this->xref."' AND mm_gedfile='".$this->gedcomid."' AND mm_type='FAM' AND mm_gid=f_id AND mm_gedfile=f_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$fam = array();
			$fam["gedcom"] = $row["f_gedcom"];
			$fam["gedfile"] = $row["f_file"];
			$fam["HUSB"] = SplitKey($row["f_husb"], "id");
			$fam["WIFE"] = SplitKey($row["f_wife"], "id");
			$famlist[$row["f_id"]] = $fam;
			$family = null;
			$family = new Family($row["f_id"], $row["f_gedcom"]);
			if ($family->disp) {
				$this->famlist[$row["f_key"]] = $family;
			}
			else $this->fam_hide++;
		}
		uasort($this->famlist, "ItemObjSort");
		$this->fam_count = count($this->famlist);
		return $this->famlist;
	}

	/**
	 * get the list of sources connected to a record
	 * @return array
	 */
	protected function GetLinksFromSources() {
		global $TBLPREFIX, $sourcelist;

		if(!is_null($this->sourcelist)) return $this->sourcelist;
		if (!isset($sourcelist)) $sourcelist = array();
		$this->sourcelist = array();
		$this->sour_hide = 0;
		
		$sql = 	"SELECT DISTINCT s_key, s_id, s_gedcom, s_file FROM ".$TBLPREFIX."media_mapping, ".$TBLPREFIX."sources WHERE mm_media='".$this->xref."' AND mm_gedfile='".$this->gedcomid."' AND mm_type='SOUR' AND s_file=mm_gedfile AND s_id=mm_gid";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$source = null;
			$sourcelist[$row["s_id"]]["gedcom"] = $row["s_gedcom"];
			$sourcelist[$row["s_id"]]["gedfile"] = $row["s_file"];
			$source = new Source($row["s_id"], $row["s_gedcom"]);
			if ($source->disp) $this->sourcelist[$row["s_key"]] = $source;
			else $this->sour_hide++;
		}
		uasort($this->sourcelist, "GedcomObjSort");
		$this->sour_count = count($this->sourcelist);
		return $this->sourcelist;
	}
	
	/**
	 * get the list of repositories connected to this record
	 * @return array
	 */
	protected function GetLinksFromRepos() {
		global $TBLPREFIX;
		
		if (!is_null($this->repolist)) return $this->repolist;
		
		$this->repolist = array();
		$this->repo_hide = 0;
		
		// repositories can be linked from 
		$sql = 	"SELECT o_id, o_gedcom FROM ".$TBLPREFIX."media_mapping, ".$TBLPREFIX."other WHERE mm_media='".$this->xref."' AND mm_gedfile='".$this->gedcomid."' AND mm_type='REPO' AND o_type='REPO' AND o_file=mm_gedfile AND o_id=mm_gid";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$repo = null;
			$repo = new Repository($row["o_id"], $row["o_gedcom"], $this->gedcomid);
			if ($repo->disp) $this->repolist[$row["o_key"]] = $repo;
			else $this->repo_hide++;
		}
		uasort($this->repolist, "ItemSort");
		$this->repo_count=count($this->repolist);
		return $this->repolist;
	}

	/** Get the ID's linked to this media
	*/
	public function GetMediaLinks($pid, $type="", $applypriv=true) {
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
		
	// Prints the information for media in a list view
	public function PrintListMedia() {
		
		if (!$this->disp) return false;
		if (begRTLText($this->GetTitle())) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
		else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		print "\n\t\t\t<a href=\"mediadetail.php?mid=$this->xref&amp;gedid=".$this->gedcomid."\" class=\"list_item\">".PrintReady($this->title);
		print $this->addxref;
		print "</a>\n";
		print "</li>\n";
	}
	
	
}
?>